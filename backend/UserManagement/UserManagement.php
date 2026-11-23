<?php
declare(strict_types=1);

require_once __DIR__ . '/../database/session.php';
require_once __DIR__ . '/../database/conn.php';

// 1. Protect endpoint: login required
$currentUser = auth();
if ($currentUser === null) {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        exit;
    }
    header('Location: ' . url('/'));
    exit;
}

$pdo    = db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
);

/**
 * Helper to return JSON responses for AJAX requests or redirect with flash fallback.
 */
function respondUserCrud(bool $isAjax, bool $success, string $message, int $status = 200, array $extra = []): never
{
    if ($isAjax) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message,
        ], $extra));
        exit;
    }

    $_SESSION['flash'] = [
        'type'    => $success ? 'success' : 'error',
        'message' => $message,
    ];
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('/frontend/Users/users.php')));
    exit;
}

// =========================================================================
// 2. WRITE OPERATIONS: CREATE / UPDATE / DELETE / TOGGLE STATUS (POST)
// =========================================================================
if ($method === 'POST') {
    // Verify CSRF token
    $submittedCsrf = $_POST['_csrf'] ?? '';
    $sessionCsrf   = $_SESSION['csrf'] ?? '';

    if (!is_string($submittedCsrf) || $sessionCsrf === '' || !hash_equals($sessionCsrf, $submittedCsrf)) {
        respondUserCrud($isAjax, false, 'Security token expired. Please refresh and try again.', 403);
    }

    $action      = trim((string) ($_POST['action'] ?? ''));
    $allowedRoles = ['admin', 'staff', 'manager']; // Adjust roles to match your DB enum/rules

    try {
        switch ($action) {
            // -------------------------------------------------------------
            // CREATE USER
            // -------------------------------------------------------------
            case 'create':
                $fullName = trim((string) ($_POST['full_name'] ?? ''));
                $username = trim((string) ($_POST['username'] ?? ''));
                $email    = trim((string) ($_POST['email'] ?? ''));
                $password = (string) ($_POST['password'] ?? '');
                $role     = trim((string) ($_POST['role'] ?? 'staff'));
                $isActive = isset($_POST['is_active']) && (string) $_POST['is_active'] === '1' ? 1 : 0;

                if ($fullName === '' || $username === '' || $email === '' || $password === '') {
                    respondUserCrud($isAjax, false, 'All fields (Full Name, Username, Email, Password) are required.', 422);
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    respondUserCrud($isAjax, false, 'Please enter a valid email address.', 422);
                }

                if (mb_strlen($password) < 8) {
                    respondUserCrud($isAjax, false, 'Password must be at least 8 characters long.', 422);
                }

                if (!in_array($role, $allowedRoles, true)) {
                    $role = 'staff';
                }

                // Check for duplicate username or email
                $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username OR email = :email');
                $checkStmt->execute(['username' => $username, 'email' => $email]);
                if ((int) $checkStmt->fetchColumn() > 0) {
                    respondUserCrud($isAjax, false, 'Username or email already exists.', 409);
                }

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $insertStmt = $pdo->prepare('
                    INSERT INTO users (full_name, username, email, password, role, is_active, created_at)
                    VALUES (:full_name, :username, :email, :password, :role, :is_active, NOW())
                ');
                $insertStmt->execute([
                    'full_name' => $fullName,
                    'username'  => $username,
                    'email'     => $email,
                    'password'  => $hashedPassword,
                    'role'      => $role,
                    'is_active' => $isActive,
                ]);

                respondUserCrud($isAjax, true, 'User created successfully.', 201, [
                    'user_id' => (int) $pdo->lastInsertId(),
                ]);

            // -------------------------------------------------------------
            // UPDATE USER
            // -------------------------------------------------------------
            case 'update':
                $userId   = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
                $fullName = trim((string) ($_POST['full_name'] ?? ''));
                $username = trim((string) ($_POST['username'] ?? ''));
                $email    = trim((string) ($_POST['email'] ?? ''));
                $password = (string) ($_POST['password'] ?? ''); // Optional on update
                $role     = trim((string) ($_POST['role'] ?? 'staff'));
                $isActive = isset($_POST['is_active']) && (string) $_POST['is_active'] === '1' ? 1 : 0;

                if (!$userId || $userId <= 0) {
                    respondUserCrud($isAjax, false, 'Invalid user ID.', 422);
                }

                if ($fullName === '' || $username === '' || $email === '') {
                    respondUserCrud($isAjax, false, 'Full Name, Username, and Email are required.', 422);
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    respondUserCrud($isAjax, false, 'Please enter a valid email address.', 422);
                }

                if (!in_array($role, $allowedRoles, true)) {
                    $role = 'staff';
                }

                // Prevent duplicate username/email on another user
                $checkStmt = $pdo->prepare('
                    SELECT COUNT(*) FROM users
                    WHERE (username = :username OR email = :email) AND user_id != :user_id
                ');
                $checkStmt->execute([
                    'username' => $username,
                    'email'    => $email,
                    'user_id'  => $userId,
                ]);
                if ((int) $checkStmt->fetchColumn() > 0) {
                    respondUserCrud($isAjax, false, 'Another account already uses that username or email.', 409);
                }

                // Update password only if a new one was provided
                if ($password !== '') {
                    if (mb_strlen($password) < 8) {
                        respondUserCrud($isAjax, false, 'New password must be at least 8 characters long.', 422);
                    }

                    $updateStmt = $pdo->prepare('
                        UPDATE users
                        SET full_name = :full_name,
                            username  = :username,
                            email     = :email,
                            password  = :password,
                            role      = :role,
                            is_active = :is_active
                        WHERE user_id = :user_id
                    ');
                    $updateStmt->execute([
                        'full_name' => $fullName,
                        'username'  => $username,
                        'email'     => $email,
                        'password'  => password_hash($password, PASSWORD_DEFAULT),
                        'role'      => $role,
                        'is_active' => $isActive,
                        'user_id'   => $userId,
                    ]);
                } else {
                    $updateStmt = $pdo->prepare('
                        UPDATE users
                        SET full_name = :full_name,
                            username  = :username,
                            email     = :email,
                            role      = :role,
                            is_active = :is_active
                        WHERE user_id = :user_id
                    ');
                    $updateStmt->execute([
                        'full_name' => $fullName,
                        'username'  => $username,
                        'email'     => $email,
                        'role'      => $role,
                        'is_active' => $isActive,
                        'user_id'   => $userId,
                    ]);
                }

                respondUserCrud($isAjax, true, 'User updated successfully.');

            // -------------------------------------------------------------
            // TOGGLE ACTIVE / INACTIVE STATUS
            // -------------------------------------------------------------
            case 'toggle_status':
                $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$userId || $userId <= 0) {
                    respondUserCrud($isAjax, false, 'Invalid user ID.', 422);
                }

                $loggedInId = (int) ($currentUser['user_id'] ?? $currentUser['id'] ?? 0);
                if ($userId === $loggedInId) {
                    respondUserCrud($isAjax, false, 'You cannot deactivate your own active account.', 403);
                }

                $toggleStmt = $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE user_id = :user_id');
                $toggleStmt->execute(['user_id' => $userId]);

                respondUserCrud($isAjax, true, 'User status updated.');

            // -------------------------------------------------------------
            // DELETE USER
            // -------------------------------------------------------------
            case 'delete':
                $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$userId || $userId <= 0) {
                    respondUserCrud($isAjax, false, 'Invalid user ID.', 422);
                }

                $loggedInId = (int) ($currentUser['user_id'] ?? $currentUser['id'] ?? 0);
                if ($userId === $loggedInId) {
                    respondUserCrud($isAjax, false, 'You cannot delete your own logged-in account.', 403);
                }

                $deleteStmt = $pdo->prepare('DELETE FROM users WHERE user_id = :user_id');
                $deleteStmt->execute(['user_id' => $userId]);

                if ($deleteStmt->rowCount() === 0) {
                    respondUserCrud($isAjax, false, 'User not found or already deleted.', 404);
                }

                respondUserCrud($isAjax, true, 'User deleted successfully.');

            default:
                respondUserCrud($isAjax, false, 'Invalid CRUD action specified.', 400);
        }
    } catch (Throwable $e) {
        error_log('Users CRUD error: ' . $e->getMessage());
        respondUserCrud($isAjax, false, 'A database error occurred while processing the request.', 500);
    }
}

// =========================================================================
// 3. READ OPERATION: LIST / SEARCH USERS & STATS (GET)
// =========================================================================
$search = trim((string) ($_GET['search'] ?? ''));

$sql = "
    SELECT
        user_id,
        full_name,
        username,
        role,
        is_active,
        created_at
    FROM users
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE full_name LIKE :name
        OR username LIKE :username
        OR email LIKE :email
        OR role LIKE :role
    ";

    $keyword = "%{$search}%";

    $params = [
        'name'     => $keyword,
        'username' => $keyword,
        'email'    => $keyword,
        'role'     => $keyword,
    ];
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// User statistics
$statsRow = $pdo->query("
    SELECT
        COUNT(*) AS total_users,
        COALESCE(SUM(is_active = 1), 0) AS active_users,
        COALESCE(SUM(is_active = 0), 0) AS inactive_users
    FROM users
")->fetch();

$totalUsers    = (int) ($statsRow['total_users'] ?? 0);
$activeUsers   = (int) ($statsRow['active_users'] ?? 0);
$inactiveUsers = (int) ($statsRow['inactive_users'] ?? 0);

// If requested via AJAX GET (e.g., live search), return JSON directly
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'users'   => $users,
        'stats'   => [
            'total'    => $totalUsers,
            'active'   => $activeUsers,
            'inactive' => $inactiveUsers,
        ],
    ]);
    exit;
}