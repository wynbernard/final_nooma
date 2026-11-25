<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../database/session.php';
require_once __DIR__ . '/../database/conn.php';

// 1. Protect endpoint: login required
$currentUser = auth();
if ($currentUser === null) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        exit;
    }
    header('Location: ' . url('/'));
    exit;
}

// Enforce read-only GET requests
if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Read-only endpoint.']);
    exit;
}

$pdo = db();
if (!$pdo instanceof PDO) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
}

$search = trim((string) ($_GET['search'] ?? ''));

$sql = "
    SELECT
        user_id,
        full_name,
        username,
        role,
        is_active,
        number_of_days,
        created_at
    FROM users
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE full_name LIKE :name
           OR username LIKE :username
           OR role LIKE :role
    ";

    $keyword = "%{$search}%";
    $params  = [
        'name'     => $keyword,
        'username' => $keyword,
        'role'     => $keyword,
    ];
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
$stmt->closeCursor();

// Compute aggregate stats in a single pass
$statsStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_users,
        COALESCE(SUM(is_active = 1), 0) AS active_users,
        COALESCE(SUM(is_active = 0), 0) AS inactive_users
    FROM users
");
$statsRow = $statsStmt->fetch() ?: [];
$statsStmt->closeCursor();

$totalUsers    = (int) ($statsRow['total_users'] ?? 0);
$activeUsers   = (int) ($statsRow['active_users'] ?? 0);
$inactiveUsers = (int) ($statsRow['inactive_users'] ?? 0);