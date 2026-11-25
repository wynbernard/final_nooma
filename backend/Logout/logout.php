<?php
declare(strict_types=1);

require_once __DIR__ . '/../database/session.php';

$homeUrl = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/') . '/';

$isAjax = (
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
);

function respondLogout(bool $isAjax, bool $success, string $message, string $redirect = '/'): never
{
    if ($isAjax) {
        http_response_code($success ? 200 : 403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'  => $success,
            'message'  => $message,
            'redirect' => $redirect,
        ]);
        exit;
    }

    header('Location: ' . $redirect);
    exit;
}

// 1. Enforce POST so logout cannot be triggered by a malicious <img> or link (CSRF protection)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondLogout($isAjax, false, 'Method not allowed.', $homeUrl);
}

// 2. Verify CSRF token
$submittedCsrf = $_POST['_csrf'] ?? '';
$sessionCsrf   = $_SESSION['csrf'] ?? '';

if (!is_string($submittedCsrf) || $sessionCsrf === '' || !hash_equals($sessionCsrf, $submittedCsrf)) {
    respondLogout($isAjax, false, 'Invalid or expired security token.', $homeUrl);
}

// 3. Clear all session variables
$_SESSION = [];

// 4. Delete the session cookie from the browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]
    );
}

// 5. Destroy the session on the server
session_destroy();

// 6. Start a clean session with a fresh CSRF token for the logged-out visitor
session_start([
    'cookie_secure' => isHttpsRequest(),
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);
session_regenerate_id(true);
$_SESSION['csrf'] = bin2hex(random_bytes(32));

respondLogout($isAjax, true, 'You have been logged out.', $homeUrl);