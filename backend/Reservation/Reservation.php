<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../database/session.php';
require_once __DIR__ . '/../database/conn.php';

// =========================================================================
// 1. AUTHENTICATION
// =========================================================================

$currentUser = auth();

if ($currentUser === null) {
    header('Location: ' . url('/'));
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
// 3. HELPER FUNCTION
// =========================================================================

function e($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

// =========================================================================
// 4. CALENDAR DATE
// =========================================================================

$requestedDate = trim((string) ($_GET['date'] ?? ''));
$requestedDateObject = DateTime::createFromFormat('!Y-m-d', $requestedDate);
$requestedDateIsValid = $requestedDateObject instanceof DateTime
    && $requestedDateObject->format('Y-m-d') === $requestedDate;

$month = trim((string) ($_GET['month'] ?? (
    $requestedDateIsValid ? $requestedDateObject->format('Y-m') : date('Y-m')
)));

if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

$monthDate = DateTime::createFromFormat('!Y-m', $month);

if (
    !$monthDate ||
    $monthDate->format('Y-m') !== $month
) {
    $monthDate = new DateTime('first day of this month');
}

$monthStart = $monthDate->format('Y-m-01');

$nextMonth = (clone $monthDate)
    ->modify('+1 month')
    ->format('Y-m-01');
$availabilityEnd = (new DateTimeImmutable($nextMonth))
    ->modify('+1 day')
    ->format('Y-m-d');

$nextMonthLink = (clone $monthDate)
    ->modify('+1 month')
    ->format('Y-m');

$previousMonth = (clone $monthDate)
    ->modify('-1 month')
    ->format('Y-m');

$daysInMonth = (int) $monthDate->format('t');
$firstDaySunday = (int) $monthDate->format('w'); // 0 (Sun) - 6 (Sat)
$firstDay = $firstDaySunday;

$today = date('Y-m-d');

// =========================================================================
// 5. FETCH RESERVATIONS
// =========================================================================

$sql = "
    SELECT
        r.reservation_id,
        r.reservation_code,
        r.customer_id,
        c.full_name AS customer_name,
        c.phone AS customer_phone,
        r.status_id,
        r.party_size,
        r.start_at,
        r.end_at,
        r.occasion,
        r.special_requests,
        r.internal_notes,
        r.source,
        r.deposit_amount,
        r.deposit_paid,
        r.created_at,
        r.updated_at,

        COALESCE(
            s.status_name,
            CONCAT('Status #', r.status_id)
        ) AS status_name

    FROM reservations r

    LEFT JOIN reservation_statuses s
        ON s.status_id = r.status_id

    LEFT JOIN customers c
        ON c.customer_id = r.customer_id

    WHERE r.start_at >= :month_start
      AND r.start_at < :availability_end

    ORDER BY r.start_at ASC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':month_start' => $monthStart,
    ':availability_end' => $availabilityEnd,
]);

$reservations = $stmt->fetchAll();

// =========================================================================
// 5A. FETCH ACTIVE TABLES FOR RESERVATION ASSIGNMENT
// =========================================================================

$availableTablesStmt = $pdo->query("
    SELECT table_id, table_number, table_name, capacity
    FROM restaurant_tables
    WHERE is_active = 1
    ORDER BY area_id ASC, table_number ASC, table_id ASC
");
$availableTables = $availableTablesStmt->fetchAll();

// =========================================================================
// 5B. FETCH ASSIGNED RESERVATION TABLES
// =========================================================================

$tablesByReservation = [];

$reservationIds = array_values(array_unique(array_filter(array_map(
    static fn(array $row): int => (int) ($row['reservation_id'] ?? 0),
    $reservations
))));

if ($reservationIds !== []) {
    $placeholders = implode(', ', array_fill(0, count($reservationIds), '?'));

    $tableSql = "
        SELECT
            assigned_table.reservation_id,
            assigned_table.table_id,
            restaurant_table.table_name,
            restaurant_table.table_number,
            assigned_table.assigned_at
        FROM reservation_tables assigned_table
        LEFT JOIN restaurant_tables restaurant_table
            ON restaurant_table.table_id = assigned_table.table_id
        WHERE assigned_table.reservation_id IN ({$placeholders})
        ORDER BY assigned_table.assigned_at ASC, assigned_table.table_id ASC
    ";

    $tableStmt = $pdo->prepare($tableSql);
    $tableStmt->execute($reservationIds);

    while ($tableRow = $tableStmt->fetch()) {
        $resId = (int) $tableRow['reservation_id'];
        $tablesByReservation[$resId][] = [
            'table_id' => $tableRow['table_id'],
            'table_name' => $tableRow['table_name'],
            'table_number' => $tableRow['table_number'],
            'assigned_at' => $tableRow['assigned_at'],
        ];
    }
}

// =========================================================================
// 6. GROUP RESERVATIONS BY DATE
// =========================================================================

$calendarEvents = [];

foreach ($reservations as $reservation) {
    $startTimestamp = strtotime((string) $reservation['start_at']);
    $endTimestamp = strtotime((string) $reservation['end_at']);

    if ($startTimestamp === false || $endTimestamp === false) {
        continue;
    }

    $resId = (int) $reservation['reservation_id'];
    $assignedTables = $tablesByReservation[$resId] ?? [];
    $date = date('Y-m-d', $startTimestamp);

    $calendarEvents[$date][] = [
        'id' => $resId,
        'code' => $reservation['reservation_code'],
        'customer_id' => $reservation['customer_id'],
        'customer_name' => $reservation['customer_name'],
        'reservation_code' => $reservation['reservation_code'],
        'phone_number' => $reservation['customer_phone'],
        'status_id' => (int) $reservation['status_id'],
        'guests' => (int) $reservation['party_size'],
        'start' => date('h:i A', $startTimestamp),
        'end' => date('h:i A', $endTimestamp),
        'start_at' => date('Y-m-d H:i:s', $startTimestamp),
        'end_at' => date('Y-m-d H:i:s', $endTimestamp),
        'status' => $reservation['status_name'],
        'occasion' => $reservation['occasion'],
        'special_requests' => $reservation['special_requests'],
        'internal_notes' => $reservation['internal_notes'],
        'source' => $reservation['source'],
        'deposit_amount' => (float) ($reservation['deposit_amount'] ?? 0),
        'deposit_paid' => $reservation['deposit_paid'],
        'tables' => $assignedTables,
        'table_ids' => array_column($assignedTables, 'table_id'),
    ];
}

// =========================================================================
// 7. MONTHLY STATISTICS
// =========================================================================

$monthlyReservations = array_values(array_filter(
    $reservations,
    static fn(array $reservation): bool => $reservation['start_at'] < $nextMonth
));
$totalReservations = count($monthlyReservations);

$confirmedCount = 0;
$pendingCount = 0;
$cancelledCount = 0;
$otherCount = 0;

foreach ($monthlyReservations as $reservation) {
    $status = strtolower(
        trim((string) $reservation['status_name'])
    );

    switch ($status) {
        case 'confirmed':
            $confirmedCount++;
            break;

        case 'pending':
            $pendingCount++;
            break;

        case 'cancelled':
        case 'canceled':
            $cancelledCount++;
            break;

        default:
            $otherCount++;
            break;
    }
}

// =========================================================================
// 8. CALENDAR JSON
// =========================================================================

$calendarJson = json_encode(
    $calendarEvents,
    JSON_HEX_TAG |
    JSON_HEX_APOS |
    JSON_HEX_AMP |
    JSON_HEX_QUOT |
    JSON_THROW_ON_ERROR
);

// Initial selected date
$initialDate = $requestedDateIsValid && $requestedDateObject->format('Y-m') === $month
    ? $requestedDate
    : ((date('Y-m') === $month) ? $today : $monthStart);