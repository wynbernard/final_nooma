<?php
declare(strict_types=1);

require_once __DIR__ . '/../database/session.php';

function sendJsonResponse(int $status, array $data): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    sendJsonResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$csrf = (string) ($_POST['_csrf'] ?? '');
if (!hash_equals((string) $_SESSION['csrf'], $csrf)) {
    sendJsonResponse(403, ['success' => false, 'message' => 'Invalid or expired form token.']);
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($username === '' || mb_strlen($username) > 100 || mb_strlen($password) < 8) {
    sendJsonResponse(400, ['success' => false, 'message' => 'Enter a username and a password of at least 8 characters.']);
}

require_once __DIR__ . '/../database/conn.php';
try {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT user_id, full_name, username, password_hash, role FROM users WHERE username = :username AND is_active = 1 LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
} catch (PDOException $exception) {
    error_log('Login database error: ' . $exception->getMessage());
    sendJsonResponse(503, ['success' => false, 'message' => 'Login is temporarily unavailable. Please check the database configuration.']);
}

if (!$user || !password_verify($password, $user['password_hash'])) {
    sendJsonResponse(401, ['success' => false, 'message' => 'Invalid username or password.']);
}

session_regenerate_id(true);
$_SESSION['user'] = [
    'id' => (int) $user['user_id'],
    'name' => (string) $user['full_name'],
    'username' => (string) $user['username'],
    'role' => (string) $user['role'],
];

$_SESSION['csrf'] = bin2hex(random_bytes(32));
sendJsonResponse(200, [
    'success' => true,
    'message' => 'Welcome back, ' . (string) $user['full_name'] . '.',
    'redirect' => rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/') . '/frontend/Dashboard/Dashboard.php',
]);