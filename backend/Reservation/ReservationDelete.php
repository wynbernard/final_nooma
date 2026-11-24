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

$redirectUrl = url('/frontend/Reservation/Reservation.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirectUrl);
    exit;
}

// =========================================================================
// 2. DATABASE CONNECTION (UNBUFFERED QUERIES)
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
// 3. EXTRACT & VALIDATE INPUTS
// =========================================================================

$reservationId = filter_var(
    $_POST['reservation_id'] ?? '',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$returnDate = trim((string) ($_POST['reservation_date'] ?? $_GET['date'] ?? ''));
$dateQuery  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $returnDate)
    ? '?date=' . urlencode($returnDate)
    : '';

if ($reservationId === false) {
    $_SESSION['flash_error'] = 'Invalid reservation ID.';
    header('Location: ' . $redirectUrl . $dateQuery);
    exit;
}

// =========================================================================
// 4. DELETE RESERVATION WITHIN TRANSACTION
// =========================================================================

try {
    $pdo->beginTransaction();

    $findStmt = $pdo->prepare("
        SELECT r.reservation_id, r.reservation_code, DATE(r.start_at) AS res_date, c.full_name
        FROM reservations r
        LEFT JOIN customers c ON c.customer_id = r.customer_id
        WHERE r.reservation_id = :reservation_id
        LIMIT 1
    ");
    $findStmt->execute([':reservation_id' => $reservationId]);
    $row = $findStmt->fetch();
    $findStmt->closeCursor();

    if (!$row) {
        throw new RuntimeException('Reservation not found.');
    }

    if ($dateQuery === '' && !empty($row['res_date'])) {
        $dateQuery = '?date=' . urlencode((string) $row['res_date']);
    }

    // Delete child rows from `reservation_tables` first
    $delTablesStmt = $pdo->prepare("
        DELETE FROM reservation_tables
        WHERE reservation_id = :reservation_id
    ");
    $delTablesStmt->execute([':reservation_id' => $reservationId]);
    $delTablesStmt->closeCursor();

    // Delete parent row from `reservations`
    $delResStmt = $pdo->prepare("
        DELETE FROM reservations
        WHERE reservation_id = :reservation_id
    ");
    $delResStmt->execute([':reservation_id' => $reservationId]);
    $delResStmt->closeCursor();

    $pdo->commit();

    $codePart     = !empty($row['reservation_code']) ? " {$row['reservation_code']}" : '';
    $customerPart = !empty($row['full_name']) ? " for {$row['full_name']}" : '';
    $_SESSION['flash_success'] = "Reservation{$codePart}{$customerPart} deleted.";
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = $e->getMessage();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Failed to delete reservation from the database.';
}

header('Location: ' . $redirectUrl . $dateQuery);
exit;