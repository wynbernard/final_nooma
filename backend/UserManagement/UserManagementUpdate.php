<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../database/session.php';
require_once __DIR__ . '/../database/conn.php';

// =========================================================================
// 1. AUTHENTICATION, AUTHORIZATION & METHOD CHECK
// =========================================================================

$currentUser = auth();

if ($currentUser === null) {
    header('Location: ' . url('/'));
    exit;
}

$redirectUrl = url('/frontend/UserManagement/UserManagement.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectUrl);
    exit;
}

$currentRole = strtolower((string) ($currentUser['role'] ?? ''));
if (!in_array($currentRole, ['admin', 'manager'], true)) {
    $_SESSION['flash_error'] = 'You do not have permission to update user accounts.';
    header('Location: ' . $redirectUrl);
    exit;
}

// =========================================================================
// 2. CSRF VALIDATION
// =========================================================================

$submittedCsrf = (string) ($_POST['_csrf'] ?? '');
$sessionCsrf   = (string) ($_SESSION['csrf'] ?? '');

if ($sessionCsrf === '' || $submittedCsrf === '' || !hash_equals($sessionCsrf, $submittedCsrf)) {
    $_SESSION['flash_error'] = 'Invalid security token. Please refresh and try again.';
    header('Location: ' . $redirectUrl);
    exit;
}

// =========================================================================
// 3. DATABASE CONNECTION (UNBUFFERED QUERIES)
// =========================================================================

$pdo = db();

if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database connection is not available.');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
}

// =========================================================================
// 4. EXTRACT & VALIDATE INPUTS
// =========================================================================

$action = trim((string) ($_POST['action'] ?? ''));
if ($action !== 'update') {
    $_SESSION['flash_error'] = 'Invalid user management action.';
    header('Location: ' . $redirectUrl);
    exit;
}

$userId = filter_var(
    $_POST['user_id'] ?? '',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$fullName = trim((string) ($_POST['full_name'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$role     = strtolower(trim((string) ($_POST['role'] ?? 'employee')));
$isActive = (string) ($_POST['is_active'] ?? '0') === '1' ? 1 : 0;

$hasNumberOfDays = array_key_exists('number_of_days', $_POST) && trim((string) $_POST['number_of_days']) !== '';
$numberOfDays    = null;
if ($hasNumberOfDays) {
    $parsedDays = filter_var(
        $_POST['number_of_days'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0, 'max_range' => 3650]]
    );
    if ($parsedDays === false) {
        $numberOfDays = -1; // Flag validation error below
    } else {
        $numberOfDays = (int) $parsedDays;
    }
}

$allowedRoles = ['admin', 'manager', 'cashier', 'employee'];
$errors       = [];

if ($userId === false) {
    $errors[] = 'Invalid user ID.';
}

if ($fullName === '' || mb_strlen($fullName) > 150) {
    $errors[] = 'Full name is required (up to 150 characters).';
}

if ($username === '' || mb_strlen($username) > 50 || !preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
    $errors[] = 'Username is required (up to 50 characters; letters, numbers, dots, underscores, or hyphens only).';
}

if ($password !== '') {
    $len = strlen($password);
    if ($len < 8 || $len > 72) {
        $errors[] = 'New password must be between 8 and 72 characters.';
    }
}

if (!in_array($role, $allowedRoles, true)) {
    $errors[] = 'Invalid user role selected.';
}

if ($numberOfDays !== null && $numberOfDays < 0) {
    $errors[] = 'Number of days must be a valid non-negative integer.';
}

if ($role === 'admin' && $currentRole !== 'admin') {
    $errors[] = 'Only an admin can assign the admin role.';
}

$currentUserId = (int) ($currentUser['user_id'] ?? $currentUser['id'] ?? 0);
if ($userId !== false && $currentUserId > 0 && (int) $userId === $currentUserId && $isActive === 0) {
    $errors[] = 'You cannot deactivate your own active account.';
}

if (!empty($errors)) {
    $_SESSION['flash_error'] = implode(' ', $errors);
    header('Location: ' . $redirectUrl);
    exit;
}

// =========================================================================
// 5. UPDATE USER WITHIN TRANSACTION
// =========================================================================

try {
    $pdo->beginTransaction();

    $findUserStmt = $pdo->prepare("
        SELECT user_id, username, role, number_of_days
        FROM users
        WHERE user_id = :user_id
        LIMIT 1
    ");
    $findUserStmt->execute([':user_id' => (int) $userId]);
    $existingUser = $findUserStmt->fetch();
    $findUserStmt->closeCursor();

    if (!$existingUser) {
        throw new RuntimeException('User account not found.');
    }

    if (strtolower((string) $existingUser['role']) === 'admin' && $currentRole !== 'admin') {
        throw new RuntimeException('Only an admin can edit an admin account.');
    }

    $dupStmt = $pdo->prepare("
        SELECT user_id
        FROM users
        WHERE LOWER(username) = LOWER(:username)
          AND user_id <> :user_id
        LIMIT 1
    ");
    $dupStmt->execute([
        ':username' => $username,
        ':user_id'  => (int) $userId,
    ]);
    $duplicate = $dupStmt->fetch();
    $dupStmt->closeCursor();

    if ($duplicate) {
        throw new RuntimeException("Username \"{$username}\" is already taken by another account.");
    }

    // Keep existing `number_of_days` if the modal doesn't send `number_of_days`
    $finalDays = $hasNumberOfDays
        ? $numberOfDays
        : ($existingUser['number_of_days'] !== null ? (int) $existingUser['number_of_days'] : null);

    if ($password !== '') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if ($passwordHash === false) {
            throw new RuntimeException('Failed to securely hash the new password.');
        }

        $updateStmt = $pdo->prepare("
            UPDATE users
            SET full_name = :full_name,
                username = :username,
                password_hash = :password_hash,
                role = :role,
                is_active = :is_active,
                number_of_days = :number_of_days,
                updated_at = NOW()
            WHERE user_id = :user_id
        ");
        $updateStmt->execute([
            ':full_name'     => $fullName,
            ':username'      => $username,
            ':password_hash' => $passwordHash,
            ':role'          => $role,
            ':is_active'     => $isActive,
            ':number_of_days' => $finalDays,
            ':user_id'       => (int) $userId,
        ]);
        $updateStmt->closeCursor();
    } else {
        $updateStmt = $pdo->prepare("
            UPDATE users
            SET full_name = :full_name,
                username = :username,
                role = :role,
                is_active = :is_active,
                number_of_days = :number_of_days,
                updated_at = NOW()
            WHERE user_id = :user_id
        ");
        $updateStmt->execute([
            ':full_name'     => $fullName,
            ':username'      => $username,
            ':role'          => $role,
            ':is_active'     => $isActive,
            ':number_of_days' => $finalDays,
            ':user_id'       => (int) $userId,
        ]);
        $updateStmt->closeCursor();
    }

    $pdo->commit();

    $_SESSION['flash_success'] = "User account \"{$username}\" updated successfully.";
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = $e->getMessage();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Failed to update user account in the database.';
}

header('Location: ' . $redirectUrl);
exit;