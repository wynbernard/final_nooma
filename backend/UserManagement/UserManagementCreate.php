<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

ini_set('display_errors', '0');
error_reporting(E_ALL);

ob_start();

require_once __DIR__ . '/../database/session.php';
require_once __DIR__ . '/../database/conn.php';

$redirectUrl = url('/frontend/UserManagement/UserManagement.php');

$isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (!empty($_SERVER['HTTP_ACCEPT']) && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json'))
);

/**
 * Send JSON for AJAX requests or flash + redirect for standard form POSTs.
 */
function respondUserCreate(
    bool $isAjax,
    int $httpStatus,
    bool $success,
    string $message,
    string $redirectTo,
    array $extra = []
): never {
    if ($success) {
        $_SESSION['flash_success'] = $message;
        $_SESSION['flash'] = ['type' => 'success', 'message' => $message];
    } else {
        $_SESSION['flash_error'] = $message;
        $_SESSION['flash'] = ['type' => 'error', 'message' => $message];
    }

    if ($isAjax) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code($httpStatus);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message,
        ], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . $redirectTo);
    exit;
}

// =========================================================================
// 1. AUTHENTICATION, AUTHORIZATION & METHOD CHECK
// =========================================================================

$currentUser = auth();

if ($currentUser === null) {
    if ($isAjax) {
        respondUserCreate(true, 401, false, 'Session expired. Please log in again.', url('/'));
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . url('/'));
    exit;
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    if ($isAjax) {
        respondUserCreate(true, 405, false, 'Method not allowed.', $redirectUrl);
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . $redirectUrl);
    exit;
}

$currentRole = strtolower((string) ($currentUser['role'] ?? ''));
if (!in_array($currentRole, ['admin', 'manager'], true)) {
    respondUserCreate($isAjax, 403, false, 'You do not have permission to create user accounts.', $redirectUrl);
}

// =========================================================================
// 2. CSRF VALIDATION
// =========================================================================

$submittedCsrf = (string) ($_POST['_csrf'] ?? '');
$sessionCsrf   = (string) ($_SESSION['csrf'] ?? '');

if ($sessionCsrf === '' || $submittedCsrf === '' || !hash_equals($sessionCsrf, $submittedCsrf)) {
    respondUserCreate($isAjax, 403, false, 'Invalid security token. Please refresh and try again.', $redirectUrl);
}

// =========================================================================
// 3. DATABASE CONNECTION (UNBUFFERED QUERIES)
// =========================================================================

$pdo = db();

if (!$pdo instanceof PDO) {
    respondUserCreate($isAjax, 500, false, 'Database connection is not available.', $redirectUrl);
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
}

// =========================================================================
// 4. EXTRACT & VALIDATE INPUTS
// =========================================================================

$action = trim((string) ($_POST['action'] ?? 'create'));
if ($action !== 'create') {
    respondUserCreate($isAjax, 400, false, 'Invalid user creation action.', $redirectUrl);
}

$fullName = trim((string) ($_POST['full_name'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$role     = strtolower(trim((string) ($_POST['role'] ?? 'employee')));

// Hidden input sends "0" and checkbox sends "1" when checked -> $_POST['is_active'] is "1" when checked
$isActive = (string) ($_POST['is_active'] ?? '0') === '1' ? 1 : 0;

// number_of_days is optional and nullable (NULL when omitted, empty, or "null")
$rawDays = $_POST['number_of_days'] ?? null;
$numberOfDays = 0;
$numberOfDaysInvalid = false;

if ($rawDays !== null) {
    $trimmedDays = trim((string) $rawDays);
    if ($trimmedDays !== '' && strtolower($trimmedDays) !== 'null') {
        $parsedDays = filter_var(
            $trimmedDays,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0, 'max_range' => 3650]]
        );
        if ($parsedDays === false) {
            $numberOfDaysInvalid = true;
        } else {
            $numberOfDays = (int) $parsedDays;
        }
    }
}

$allowedRoles = ['admin', 'manager', 'cashier', 'employee'];
$errors       = [];

if ($fullName === '' || mb_strlen($fullName) > 150) {
    $errors[] = 'Full name is required (up to 150 characters).';
}

if ($username === '' || mb_strlen($username) > 50 || !preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
    $errors[] = 'Username is required (up to 50 characters; letters, numbers, dots, underscores, or hyphens only).';
}

$passwordLength = strlen($password);
if ($password === '' || $passwordLength < 8 || $passwordLength > 72) {
    $errors[] = 'Password must be between 8 and 72 characters.';
}

if (!in_array($role, $allowedRoles, true)) {
    $errors[] = 'Invalid user role selected.';
}

if ($role === 'admin' && $currentRole !== 'admin') {
    $errors[] = 'Only an admin can assign the admin role.';
}

if ($numberOfDaysInvalid) {
    $errors[] = 'Number of days must be empty/null or a valid integer between 0 and 3650.';
}

if (!empty($errors)) {
    respondUserCreate($isAjax, 422, false, implode(' ', $errors), $redirectUrl);
}

// =========================================================================
// 5. CREATE USER WITHIN TRANSACTION
// =========================================================================

try {
    $pdo->beginTransaction();

    $dupStmt = $pdo->prepare("
        SELECT user_id
        FROM users
        WHERE LOWER(username) = LOWER(:username)
        LIMIT 1
    ");
    $dupStmt->execute([':username' => $username]);
    $existingUser = $dupStmt->fetch();
    $dupStmt->closeCursor();

    if ($existingUser) {
        throw new RuntimeException("Username \"{$username}\" is already taken.");
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new RuntimeException('Failed to securely hash password.');
    }

    $insertStmt = $pdo->prepare("
        INSERT INTO users (
            full_name,
            username,
            password_hash,
            role,
            is_active,
            number_of_days,
            created_at,
            updated_at
        ) VALUES (
            :full_name,
            :username,
            :password_hash,
            :role,
            :is_active,
            :number_of_days,
            NOW(),
            NOW()
        )
    ");

    $insertStmt->bindValue(':full_name', $fullName, PDO::PARAM_STR);
    $insertStmt->bindValue(':username', $username, PDO::PARAM_STR);
    $insertStmt->bindValue(':password_hash', $passwordHash, PDO::PARAM_STR);
    $insertStmt->bindValue(':role', $role, PDO::PARAM_STR);
    $insertStmt->bindValue(':is_active', $isActive, PDO::PARAM_INT);
    $insertStmt->bindValue(
        ':number_of_days',
        $numberOfDays,
        $numberOfDays === null ? PDO::PARAM_NULL : PDO::PARAM_INT
    );

    $insertStmt->execute();
    $newUserId = (int) $pdo->lastInsertId();
    $insertStmt->closeCursor();

    $pdo->commit();

    respondUserCreate(
        $isAjax,
        201,
        true,
        "User account \"{$username}\" created successfully.",
        $redirectUrl,
        ['user_id' => $newUserId]
    );
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respondUserCreate($isAjax, 409, false, $e->getMessage(), $redirectUrl);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respondUserCreate($isAjax, 500, false, 'Failed to create user account in the database.', $redirectUrl);
}