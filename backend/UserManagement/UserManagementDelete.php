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
function respondUserDelete(
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
        respondUserDelete(true, 401, false, 'Session expired. Please log in again.', url('/'));
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . url('/'));
    exit;
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    if ($isAjax) {
        respondUserDelete(true, 405, false, 'Method not allowed.', $redirectUrl);
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . $redirectUrl);
    exit;
}

$currentRole = strtolower((string) ($currentUser['role'] ?? ''));
if (!in_array($currentRole, ['admin', 'manager'], true)) {
    respondUserDelete($isAjax, 403, false, 'You do not have permission to delete user accounts.', $redirectUrl);
}

// =========================================================================
// 2. CSRF VALIDATION
// =========================================================================

$submittedCsrf = (string) ($_POST['_csrf'] ?? '');
$sessionCsrf   = (string) ($_SESSION['csrf'] ?? '');

if ($sessionCsrf === '' || $submittedCsrf === '' || !hash_equals($sessionCsrf, $submittedCsrf)) {
    respondUserDelete($isAjax, 403, false, 'Invalid security token. Please refresh and try again.', $redirectUrl);
}

// =========================================================================
// 3. DATABASE CONNECTION (UNBUFFERED QUERIES)
// =========================================================================

$pdo = db();

if (!$pdo instanceof PDO) {
    respondUserDelete($isAjax, 500, false, 'Database connection is not available.', $redirectUrl);
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
}

// =========================================================================
// 4. EXTRACT & VALIDATE INPUTS
// =========================================================================

$action = trim((string) ($_POST['action'] ?? 'delete'));
if ($action !== 'delete') {
    respondUserDelete($isAjax, 400, false, 'Invalid user delete action.', $redirectUrl);
}

$userId = filter_var(
    $_POST['user_id'] ?? '',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($userId === false) {
    respondUserDelete($isAjax, 422, false, 'Invalid user ID.', $redirectUrl);
}

$currentUserId = (int) ($currentUser['user_id'] ?? $currentUser['id'] ?? 0);
if ($currentUserId > 0 && (int) $userId === $currentUserId) {
    respondUserDelete($isAjax, 403, false, 'You cannot delete your own logged-in account.', $redirectUrl);
}

// =========================================================================
// 5. DELETE USER WITHIN TRANSACTION
// =========================================================================

try {
    $pdo->beginTransaction();

    $findStmt = $pdo->prepare("
        SELECT user_id, username, full_name, role
        FROM users
        WHERE user_id = :user_id
        LIMIT 1
    ");
    $findStmt->execute([':user_id' => (int) $userId]);
    $targetUser = $findStmt->fetch();
    $findStmt->closeCursor();

    if (!$targetUser) {
        throw new RuntimeException('User account not found.');
    }

    if (strtolower((string) $targetUser['role']) === 'admin' && $currentRole !== 'admin') {
        throw new RuntimeException('Only an admin can delete an admin account.');
    }

    $delStmt = $pdo->prepare("
        DELETE FROM users
        WHERE user_id = :user_id
    ");
    $delStmt->execute([':user_id' => (int) $userId]);
    $deletedCount = $delStmt->rowCount();
    $delStmt->closeCursor();

    if ($deletedCount === 0) {
        throw new RuntimeException('User account could not be deleted.');
    }

    $pdo->commit();

    $deletedUsername = (string) ($targetUser['username'] ?? "#{$userId}");
    respondUserDelete(
        $isAjax,
        200,
        true,
        "User account \"{$deletedUsername}\" deleted successfully.",
        $redirectUrl,
        ['user_id' => (int) $userId]
    );
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respondUserDelete($isAjax, 409, false, $e->getMessage(), $redirectUrl);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Foreign key constraint (SQLSTATE 23000) if another table references this user_id
    if ($e->getCode() === '23000') {
        respondUserDelete(
            $isAjax,
            409,
            false,
            'Cannot delete this user because other records reference this account. Deactivate it instead.',
            $redirectUrl
        );
    }
    respondUserDelete($isAjax, 500, false, 'Failed to delete user account from the database.', $redirectUrl);
}