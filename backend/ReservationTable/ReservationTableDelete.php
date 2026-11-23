<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../database/session.php';
require_once __DIR__ . '/../database/conn.php';

// =========================================================================
// 1. AUTHENTICATION & METHOD CHECK
// =========================================================================

$currentUser = auth();

if ($currentUser === null) {
    header('Location: ' . url('/'));
    exit;
}

$redirectUrl = url('/frontend/ReservationTable/ReservationTable.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectUrl);
    exit;
}

// =========================================================================
// 2. DATABASE CONNECTION
// =========================================================================

$pdo = db();

if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit('Database connection is not available.');
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// =========================================================================
// 3. VALIDATE INPUTS
// =========================================================================

$action = trim((string) ($_POST['action'] ?? 'delete_table'));
$tableId = filter_var(
    $_POST['table_id'] ?? '',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($action !== 'delete_table') {
    $_SESSION['flash_error'] = 'Invalid delete action.';
    header('Location: ' . $redirectUrl);
    exit;
}

if ($tableId === false) {
    $_SESSION['flash_error'] = 'Invalid table selected for deletion.';
    header('Location: ' . $redirectUrl);
    exit;
}

// =========================================================================
// 4. DELETE FROM `restaurant_tables`
// =========================================================================

try {
    $lookupStmt = $pdo->prepare("
        SELECT table_number, table_name
        FROM restaurant_tables
        WHERE table_id = :table_id
        LIMIT 1
    ");
    $lookupStmt->execute([':table_id' => $tableId]);
    $table = $lookupStmt->fetch();

    if (!$table) {
        $_SESSION['flash_error'] = 'Restaurant table not found.';
        header('Location: ' . $redirectUrl);
        exit;
    }

    $deleteStmt = $pdo->prepare("
        DELETE FROM restaurant_tables
        WHERE table_id = :table_id
    ");
    $deleteStmt->execute([':table_id' => $tableId]);

    $displayName = 'Table ' . (string) $table['table_number'];
    if (!empty($table['table_name'])) {
        $displayName .= ' (' . (string) $table['table_name'] . ')';
    }

    $_SESSION['flash_success'] = "{$displayName} has been deleted successfully.";
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        $_SESSION['flash_error'] = 'Cannot delete this table because it is already linked to reservation assignments.';
    } else {
        $_SESSION['flash_error'] = 'Failed to delete table from the database.';
    }
}

header('Location: ' . $redirectUrl);
exit;