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

$action = trim((string) ($_POST['action'] ?? 'create_reservation'));
if ($action !== 'create_reservation') {
    $_SESSION['flash_error'] = 'Invalid reservation action.';
    header('Location: ' . $redirectUrl);
    exit;
}

$reservationDate = trim((string) ($_POST['reservation_date'] ?? ''));
$startTime       = trim((string) ($_POST['start_time'] ?? ''));
$endTime         = trim((string) ($_POST['end_time'] ?? ''));
$startDayOffset  = filter_var(
    $_POST['start_day_offset'] ?? '0',
    FILTER_VALIDATE_INT
);
$endDayOffset    = filter_var(
    $_POST['end_day_offset'] ?? '0',
    FILTER_VALIDATE_INT
);
$customerName    = trim((string) ($_POST['customer_name'] ?? ''));
$phone           = trim((string) ($_POST['phone_number'] ?? ''));
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

$errors = [];

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reservationDate)) {
    $errors[] = 'Please provide a valid reservation date.';
} else {
    [$y, $m, $d] = array_map('intval', explode('-', $reservationDate));
    if (!checkdate($m, $d, $y)) {
        $errors[] = 'Reservation date is not a valid calendar date.';
    } elseif ($reservationDate < date('Y-m-d')) {
        $errors[] = 'Reservations cannot be created for past dates.';
    }
}

if ($startTimeSql === null || $endTimeSql === null) {
    $errors[] = 'Please choose valid start and end times.';
} elseif (
    !in_array($startDayOffset, [0, 1], true) ||
    !in_array($endDayOffset, [0, 1], true)
) {
    $errors[] = 'Please choose a valid reservation time.';
} else {
    $startMinutes = ((int) substr($startTimeSql, 0, 2) * 60)
        + (int) substr($startTimeSql, 3, 2);
    $endMinutes = ((int) substr($endTimeSql, 0, 2) * 60)
        + (int) substr($endTimeSql, 3, 2);
    $elapsedStart = ($startDayOffset * 24 * 60) + $startMinutes;
    $elapsedEnd = ($endDayOffset * 24 * 60) + $endMinutes;

    if (
        $elapsedStart < 11 * 60 ||
        $elapsedStart >= 26 * 60 ||
        $elapsedEnd <= $elapsedStart ||
        $elapsedEnd > 26 * 60
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

$reservationCode = strtoupper(trim((string) ($_POST['reservation_code'] ?? '')));
if ($reservationCode === '') {
    $datePart        = str_replace('-', '', $reservationDate ?: date('Ymd'));
    $randomSuffix    = strtoupper(bin2hex(random_bytes(2)));
    $reservationCode = "RSV-{$datePart}-{$randomSuffix}";
}

if (!empty($errors)) {
    $_SESSION['flash_error'] = implode(' ', $errors);
    header('Location: ' . $redirectUrl . ($reservationDate !== '' ? '?date=' . urlencode($reservationDate) : ''));
    exit;
}

$reservationDateObject = new DateTimeImmutable($reservationDate);
$startDate = $reservationDateObject->modify("+{$startDayOffset} days");
$endDate = $reservationDateObject->modify("+{$endDayOffset} days");
$startDateTimeSql = $startDate->format('Y-m-d') . " {$startTimeSql}";
$endDateTimeSql = $endDate->format('Y-m-d') . " {$endTimeSql}";

// =========================================================================
// 4. VERIFY STATUS, TABLES, CAPACITY, TIME CONFLICTS & CUSTOMER
// =========================================================================

try {
    $pdo->beginTransaction();

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
        SELECT table_id, table_number, table_name, capacity, is_active
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

    // Overlap check using DATETIME start_at and end_at
    $conflictParams = array_merge(
        [$endDateTimeSql, $startDateTimeSql],
        $tableIds
    );

    $conflictStmt = $pdo->prepare("
        SELECT r.reservation_code, rt.table_id, t.table_number
        FROM reservation_tables rt
        INNER JOIN reservations r ON r.reservation_id = rt.reservation_id
        INNER JOIN restaurant_tables t ON t.table_id = rt.table_id
        LEFT JOIN reservation_statuses rs ON rs.status_id = r.status_id
        WHERE r.start_at < ?
          AND r.end_at > ?
          AND LOWER(COALESCE(rs.status_name, '')) NOT IN ('cancelled', 'canceled', 'no-show', 'no show')
          AND rt.table_id IN ({$placeholders})
        LIMIT 1
    ");
    $conflictStmt->execute($conflictParams);
    $conflict = $conflictStmt->fetch();
    $conflictStmt->closeCursor();

    if ($conflict) {
        throw new RuntimeException(
            "Table {$conflict['table_number']} is already booked during that time slot (Booking {$conflict['reservation_code']})."
        );
    }

    // Resolve or create customer in `customers` (`customer_id`, `full_name`, `phone`)
    $customerId = null;
    $phoneNull  = $phone !== '' ? $phone : null;

    if ($phoneNull !== null) {
        $findByPhoneStmt = $pdo->prepare("
            SELECT customer_id, full_name
            FROM customers
            WHERE phone = :phone
            LIMIT 1
        ");
        $findByPhoneStmt->execute([':phone' => $phoneNull]);
        $existingByPhone = $findByPhoneStmt->fetch();
        $findByPhoneStmt->closeCursor();

        if ($existingByPhone) {
            $customerId = (int) $existingByPhone['customer_id'];
            if (mb_strtolower(trim((string) $existingByPhone['full_name'])) !== mb_strtolower($customerName)) {
                $updNameStmt = $pdo->prepare("
                    UPDATE customers
                    SET full_name = :full_name
                    WHERE customer_id = :customer_id
                ");
                $updNameStmt->execute([
                    ':full_name'   => $customerName,
                    ':customer_id' => $customerId,
                ]);
                $updNameStmt->closeCursor();
            }
        }
    }

    if ($customerId === null) {
        $findByNameStmt = $pdo->prepare("
            SELECT customer_id, phone
            FROM customers
            WHERE LOWER(full_name) = LOWER(:full_name)
            LIMIT 1
        ");
        $findByNameStmt->execute([':full_name' => $customerName]);
        $existingByName = $findByNameStmt->fetch();
        $findByNameStmt->closeCursor();

        if ($existingByName) {
            $customerId = (int) $existingByName['customer_id'];
            if ($phoneNull !== null && empty($existingByName['phone'])) {
                $updPhoneStmt = $pdo->prepare("
                    UPDATE customers
                    SET phone = :phone
                    WHERE customer_id = :customer_id
                ");
                $updPhoneStmt->execute([
                    ':phone'       => $phoneNull,
                    ':customer_id' => $customerId,
                ]);
                $updPhoneStmt->closeCursor();
            }
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
    }

    // Ensure unique reservation_code
    $codeCheckStmt = $pdo->prepare("
        SELECT reservation_id
        FROM reservations
        WHERE reservation_code = :code
        LIMIT 1
    ");
    $codeCheckStmt->execute([':code' => $reservationCode]);
    $duplicateCode = $codeCheckStmt->fetch();
    $codeCheckStmt->closeCursor();

    if ($duplicateCode) {
        $reservationCode .= '-' . strtoupper(bin2hex(random_bytes(1)));
    }

    // Insert into `reservations`
    $insertResStmt = $pdo->prepare("
        INSERT INTO reservations (
            reservation_code,
            customer_id,
            status_id,
            start_at,
            end_at,
            party_size,
            occasion,
            source,
            deposit_amount,
            deposit_paid,
            special_requests,
            internal_notes
        ) VALUES (
            :reservation_code,
            :customer_id,
            :status_id,
            :start_at,
            :end_at,
            :party_size,
            :occasion,
            :source,
            :deposit_amount,
            :deposit_paid,
            :special_requests,
            :internal_notes
        )
    ");

    $insertResStmt->execute([
        ':reservation_code' => $reservationCode,
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
    ]);

    $reservationId = (int) $pdo->lastInsertId();
    $insertResStmt->closeCursor();

    // Link all selected tables in `reservation_tables`
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

    $_SESSION['flash_success'] = "Reservation {$reservationCode} created for {$customerName} on {$reservationDate}.";
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = $e->getMessage();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Failed to save reservation to the database.';
}

header('Location: ' . $redirectUrl . '?date=' . urlencode($reservationDate));
exit;