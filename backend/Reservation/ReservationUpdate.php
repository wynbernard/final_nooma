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

$reservationDate = trim((string) ($_POST['reservation_date'] ?? ''));
$returnDate      = trim((string) ($_POST['return_date'] ?? ''));
$startTime       = trim((string) ($_POST['start_time'] ?? ''));
$endTime         = trim((string) ($_POST['end_time'] ?? ''));
$customerName    = trim((string) ($_POST['customer_name'] ?? ''));
$phone           = trim((string) ($_POST['phone_number'] ?? $_POST['phone'] ?? ''));
$occasion        = trim((string) ($_POST['occasion'] ?? ''));
$source          = trim((string) ($_POST['source'] ?? 'Walk-in'));
$specialRequests = trim((string) ($_POST['special_requests'] ?? ''));
$internalNotes   = trim((string) ($_POST['internal_notes'] ?? ''));

$partySize = filter_var(
    $_POST['party_size'] ?? '',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 200]]
);

$statusId = filter_var(
    $_POST['status_id'] ?? '',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$depositAmountRaw = $_POST['deposit_amount'] ?? '0';
$depositAmount    = is_numeric($depositAmountRaw) ? round((float) $depositAmountRaw, 2) : -1.0;
$depositPaid      = isset($_POST['deposit_paid']) && (string) $_POST['deposit_paid'] === '1' ? 1 : 0;

$normalizeTime = static function (string $time): ?string {
    if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $m)) {
        return "{$m[1]}:{$m[2]}:00";
    }
    if (preg_match('/^([01]\d|2[0-3]):([0-5]\d):([0-5]\d)$/', $time, $m)) {
        return "{$m[1]}:{$m[2]}:{$m[3]}";
    }
    return null;
};

$startTimeSql = $normalizeTime($startTime);
$endTimeSql   = $normalizeTime($endTime);

$returnDateObject = DateTime::createFromFormat('!Y-m-d', $returnDate);
if (
    !$returnDateObject ||
    $returnDateObject->format('Y-m-d') !== $returnDate
) {
    $returnDate = $reservationDate;
}

$errors = [];

if ($reservationId === false) {
    $errors[] = 'Invalid reservation ID.';
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reservationDate)) {
    $errors[] = 'Please provide a valid reservation date.';
} else {
    [$y, $m, $d] = array_map('intval', explode('-', $reservationDate));
    if (!checkdate($m, $d, $y)) {
        $errors[] = 'Reservation date is not a valid calendar date.';
    }
}

if ($startTimeSql === null || $endTimeSql === null) {
    $errors[] = 'Please choose valid start and end times.';
} else {
    $startMinutes = ((int) substr($startTimeSql, 0, 2) * 60)
        + (int) substr($startTimeSql, 3, 2);
    $endMinutes = ((int) substr($endTimeSql, 0, 2) * 60)
        + (int) substr($endTimeSql, 3, 2);
    $startsAfterMidnight = $startMinutes < 2 * 60;
    $endsNextDay = !$startsAfterMidnight && $endMinutes <= $startMinutes;

    if (
        (!$startsAfterMidnight && $startMinutes < 11 * 60) ||
        ($startsAfterMidnight && ($endMinutes <= $startMinutes || $endMinutes > 2 * 60)) ||
        ($endsNextDay && $endMinutes > 2 * 60)
    ) {
        $errors[] = 'Reservation times must be between 11:00 AM and 2:00 AM the next day.';
    }
}

if ($partySize === false) {
    $errors[] = 'Number of guests must be between 1 and 200.';
}

if ($customerName === '' || mb_strlen($customerName) > 150) {
    $errors[] = 'Customer name is required (up to 150 characters).';
}

if ($phone !== '' && mb_strlen($phone) > 40) {
    $errors[] = 'Phone number must be 40 characters or fewer.';
}

if ($statusId === false) {
    $errors[] = 'Please select a valid reservation status.';
}

if ($depositAmount < 0) {
    $errors[] = 'Deposit amount cannot be negative.';
}

$rawTableIds = $_POST['table_ids'] ?? [];
if (!is_array($rawTableIds)) {
    $rawTableIds = [];
}

$tableIds = [];
foreach ($rawTableIds as $rawId) {
    $id = filter_var($rawId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id !== false && !in_array($id, $tableIds, true)) {
        $tableIds[] = $id;
    }
}

if (empty($tableIds)) {
    $errors[] = 'Please assign at least one table for this reservation.';
}

if (!empty($errors)) {
    $_SESSION['flash_error'] = implode(' ', $errors);
    header('Location: ' . $redirectUrl . ($returnDate !== '' ? '?date=' . urlencode($returnDate) : ''));
    exit;
}

$startDateTimeSql = "{$reservationDate} {$startTimeSql}";
$endDate = new DateTimeImmutable($reservationDate);
if ($endsNextDay) {
    $endDate = $endDate->modify('+1 day');
}
$endDateTimeSql = $endDate->format('Y-m-d') . " {$endTimeSql}";

// =========================================================================
// 4. VALIDATE & UPDATE WITHIN TRANSACTION
// =========================================================================

try {
    $pdo->beginTransaction();

    $existingResStmt = $pdo->prepare("
        SELECT reservation_id, reservation_code, customer_id
        FROM reservations
        WHERE reservation_id = :reservation_id
        LIMIT 1
    ");
    $existingResStmt->execute([':reservation_id' => $reservationId]);
    $existingRes = $existingResStmt->fetch();
    $existingResStmt->closeCursor();

    if (!$existingRes) {
        throw new RuntimeException('Reservation not found.');
    }

    $statusStmt = $pdo->prepare("
        SELECT status_id
        FROM reservation_statuses
        WHERE status_id = :status_id
        LIMIT 1
    ");
    $statusStmt->execute([':status_id' => $statusId]);
    $statusRow = $statusStmt->fetch();
    $statusStmt->closeCursor();

    if (!$statusRow) {
        throw new RuntimeException('Selected reservation status does not exist.');
    }

    $placeholders = implode(',', array_fill(0, count($tableIds), '?'));
    $tableStmt = $pdo->prepare("
        SELECT table_id, table_number, capacity, is_active
        FROM restaurant_tables
        WHERE table_id IN ({$placeholders})
    ");
    $tableStmt->execute($tableIds);
    $foundTables = $tableStmt->fetchAll();
    $tableStmt->closeCursor();

    if (count($foundTables) !== count($tableIds)) {
        throw new RuntimeException('One or more selected tables could not be found.');
    }

    $totalCapacity = 0;
    foreach ($foundTables as $tbl) {
        if ((int) ($tbl['is_active'] ?? 1) !== 1) {
            throw new RuntimeException("Table {$tbl['table_number']} is currently inactive.");
        }
        $totalCapacity += (int) ($tbl['capacity'] ?? 0);
    }

    if ($totalCapacity < (int) $partySize) {
        throw new RuntimeException(
            "Selected table capacity ({$totalCapacity} seats) is less than the guest count ({$partySize})."
        );
    }

    // Overlap check excluding the reservation being edited
    $conflictParams = array_merge(
        [$endDateTimeSql, $startDateTimeSql, $reservationId],
        $tableIds
    );

    $conflictStmt = $pdo->prepare("
        SELECT r.reservation_code, t.table_number
        FROM reservation_tables rt
        INNER JOIN reservations r ON r.reservation_id = rt.reservation_id
        INNER JOIN restaurant_tables t ON t.table_id = rt.table_id
        LEFT JOIN reservation_statuses rs ON rs.status_id = r.status_id
        WHERE r.start_at < ?
          AND r.end_at > ?
          AND r.reservation_id <> ?
          AND LOWER(COALESCE(rs.status_name, '')) NOT IN ('cancelled', 'canceled', 'no-show', 'no show')
          AND rt.table_id IN ({$placeholders})
        LIMIT 1
    ");
    $conflictStmt->execute($conflictParams);
    $conflict = $conflictStmt->fetch();
    $conflictStmt->closeCursor();

    if ($conflict) {
        throw new RuntimeException(
            "Table {$conflict['table_number']} conflicts with Booking {$conflict['reservation_code']} during that time slot."
        );
    }

    // Update linked customer or resolve/create one in `customers` (`full_name`, `phone`)
    $phoneNull  = $phone !== '' ? $phone : null;
    $customerId = !empty($existingRes['customer_id']) ? (int) $existingRes['customer_id'] : null;

    if ($customerId !== null) {
        $updCustStmt = $pdo->prepare("
            UPDATE customers
            SET full_name = :full_name,
                phone = COALESCE(:phone, phone)
            WHERE customer_id = :customer_id
        ");
        $updCustStmt->execute([
            ':full_name'   => $customerName,
            ':phone'       => $phoneNull,
            ':customer_id' => $customerId,
        ]);
        $updCustStmt->closeCursor();
    } else {
        $insCustStmt = $pdo->prepare("
            INSERT INTO customers (full_name, phone)
            VALUES (:full_name, :phone)
        ");
        $insCustStmt->execute([
            ':full_name' => $customerName,
            ':phone'     => $phoneNull,
        ]);
        $customerId = (int) $pdo->lastInsertId();
        $insCustStmt->closeCursor();
    }

    // Update reservation record
    $updateResStmt = $pdo->prepare("
        UPDATE reservations
        SET customer_id = :customer_id,
            status_id = :status_id,
            start_at = :start_at,
            end_at = :end_at,
            party_size = :party_size,
            occasion = :occasion,
            source = :source,
            deposit_amount = :deposit_amount,
            deposit_paid = :deposit_paid,
            special_requests = :special_requests,
            internal_notes = :internal_notes
        WHERE reservation_id = :reservation_id
    ");

    $updateResStmt->execute([
        ':customer_id'      => $customerId,
        ':status_id'        => $statusId,
        ':start_at'         => $startDateTimeSql,
        ':end_at'           => $endDateTimeSql,
        ':party_size'       => (int) $partySize,
        ':occasion'         => $occasion !== '' ? $occasion : null,
        ':source'           => $source !== '' ? $source : 'Walk-in',
        ':deposit_amount'   => $depositAmount,
        ':deposit_paid'     => $depositPaid,
        ':special_requests' => $specialRequests !== '' ? $specialRequests : null,
        ':internal_notes'   => $internalNotes !== '' ? $internalNotes : null,
        ':reservation_id'   => $reservationId,
    ]);
    $updateResStmt->closeCursor();

    // Refresh `reservation_tables` assignments
    $delTablesStmt = $pdo->prepare("
        DELETE FROM reservation_tables
        WHERE reservation_id = :reservation_id
    ");
    $delTablesStmt->execute([':reservation_id' => $reservationId]);
    $delTablesStmt->closeCursor();

    $linkTableStmt = $pdo->prepare("
        INSERT INTO reservation_tables (reservation_id, table_id)
        VALUES (:reservation_id, :table_id)
    ");

    foreach ($tableIds as $tid) {
        $linkTableStmt->execute([
            ':reservation_id' => $reservationId,
            ':table_id'       => $tid,
        ]);
        $linkTableStmt->closeCursor();
    }

    $pdo->commit();

    $_SESSION['flash_success'] = "Reservation {$existingRes['reservation_code']} for {$customerName} updated.";
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = $e->getMessage();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Failed to update reservation in the database.';
}

header('Location: ' . $redirectUrl . '?date=' . urlencode($returnDate));
exit;