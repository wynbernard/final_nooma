<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../backend/database/session.php';
require_once __DIR__ . '/../../backend/database/conn.php';

if (auth() === null) {
    header('Location: ' . url('/'));
    exit;
}

$pdo = db();
$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$todayStart = $now->setTime(0, 0)->format('Y-m-d H:i:s');
$tomorrowStart = $now->setTime(0, 0)->modify('+1 day')->format('Y-m-d H:i:s');
$currentTime = $now->format('Y-m-d H:i:s');

$totalReservations = (int) $pdo->query('SELECT COUNT(*) FROM reservations')->fetchColumn();

$todayReservationsStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM reservations WHERE start_at >= :day_start AND start_at < :day_end'
);
$todayReservationsStmt->execute([
    ':day_start' => $todayStart,
    ':day_end' => $tomorrowStart,
]);
$todayReservations = (int) $todayReservationsStmt->fetchColumn();

$totalCustomers = (int) $pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();

$activeTables = (int) $pdo->query(
    'SELECT COUNT(*) FROM restaurant_tables WHERE is_active = 1'
)->fetchColumn();

$availableTablesStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM restaurant_tables table_row
    WHERE table_row.is_active = 1
      AND NOT EXISTS (
          SELECT 1
          FROM reservation_tables assigned_table
          INNER JOIN reservations r
              ON r.reservation_id = assigned_table.reservation_id
          LEFT JOIN reservation_statuses s
              ON s.status_id = r.status_id
          WHERE assigned_table.table_id = table_row.table_id
            AND r.start_at <= :current_start
            AND r.end_at > :current_end
            AND LOWER(COALESCE(s.status_name, '')) NOT IN (
                'cancelled',
                'canceled',
                'no-show',
                'no show'
            )
      )
");
$availableTablesStmt->execute([
    ':current_start' => $currentTime,
    ':current_end' => $currentTime,
]);
$availableTables = (int) $availableTablesStmt->fetchColumn();

$recentReservationsStmt = $pdo->query("
    SELECT
        c.full_name AS customer_name,
        r.start_at,
        r.party_size,
        COALESCE(s.status_name, CONCAT('Status #', r.status_id)) AS status_name
    FROM reservations r
    LEFT JOIN customers c
        ON c.customer_id = r.customer_id
    LEFT JOIN reservation_statuses s
        ON s.status_id = r.status_id
    ORDER BY r.created_at DESC, r.reservation_id DESC
    LIMIT 5
");
$recentReservations = $recentReservationsStmt->fetchAll();

$reservationStatusesStmt = $pdo->query("
    SELECT
        COALESCE(s.status_name, CONCAT('Status #', r.status_id)) AS status_name,
        COUNT(*) AS reservation_count
    FROM reservations r
    LEFT JOIN reservation_statuses s
        ON s.status_id = r.status_id
    GROUP BY r.status_id, s.status_name
    ORDER BY reservation_count DESC, status_name ASC
");
$reservationStatuses = $reservationStatusesStmt->fetchAll();

$statusColors = [
    'confirmed' => ['badge' => 'bg-green-100 text-green-700', 'bar' => 'bg-green-500'],
    'pending' => ['badge' => 'bg-yellow-100 text-yellow-700', 'bar' => 'bg-yellow-500'],
    'cancelled' => ['badge' => 'bg-red-100 text-red-700', 'bar' => 'bg-red-500'],
    'canceled' => ['badge' => 'bg-red-100 text-red-700', 'bar' => 'bg-red-500'],
];

$pageTitle = "Dashboard";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $pageTitle ?> - NOOMA</title>
    <link rel="icon" type="image/png" href="<?= e(url('/frontend/public/assets/image.png')) ?>">


    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50">

    <?php include '../includes/sidebar.php'; ?>

    <div id="overlay"
        class="fixed inset-0 z-40 hidden bg-black/50 lg:hidden"></div>

    <div class="page-layout min-h-screen lg:ml-64 flex flex-col">

        <?php include '../includes/navbar.php'; ?>

        <main class="flex-1 p-5 md:p-8">

            <!-- Page Heading -->
            <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">
                        Dashboard Overview
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        Monitor your restaurant operations and performance.
                    </p>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex justify-between">
                        <p class="text-sm text-slate-500">Total Reservations</p>
                        <span class="text-xl text-blue-600">▤</span>
                    </div>
                    <h2 class="mt-4 text-3xl font-bold text-slate-800"><?= number_format($totalReservations) ?></h2>
                    <p class="mt-2 text-xs text-slate-500">All-time bookings</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex justify-between">
                        <p class="text-sm text-slate-500">Today's Reservations</p>
                        <span class="text-xl text-indigo-600">▣</span>
                    </div>
                    <h2 class="mt-4 text-3xl font-bold text-slate-800"><?= number_format($todayReservations) ?></h2>
                    <p class="mt-2 text-xs text-slate-500">Bookings scheduled for today</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex justify-between">
                        <p class="text-sm text-slate-500">Total Customers</p>
                        <span class="text-xl text-purple-600">♙</span>
                    </div>
                    <h2 class="mt-4 text-3xl font-bold text-slate-800"><?= number_format($totalCustomers) ?></h2>
                    <p class="mt-2 text-xs text-slate-500">Registered customers</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex justify-between">
                        <p class="text-sm text-slate-500">Available Tables</p>
                        <span class="text-xl text-green-600">▦</span>
                    </div>
                    <h2 class="mt-4 text-3xl font-bold text-slate-800"><?= number_format($availableTables) ?></h2>
                    <p class="mt-2 text-xs text-slate-500">Available now out of <?= number_format($activeTables) ?> active tables</p>
                </div>

            </div>

            <!-- Main Content -->
            <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-3">

                <!-- Recent Reservations -->
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white xl:col-span-2">

                    <div class="flex items-center justify-between border-b border-slate-100 p-6">
                        <div>
                            <h3 class="font-bold text-slate-800">Recent Reservations</h3>
                            <p class="mt-1 text-xs text-slate-500">Latest customer bookings</p>
                        </div>

                        <a href="<?= htmlspecialchars(url('/frontend/Reservation/Reservation.php'), ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-semibold text-blue-700 hover:underline">
                            View All
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-6 py-4">Customer</th>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">Guests</th>
                                    <th class="px-6 py-4">Status</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">

                                <?php foreach ($recentReservations as $reservation): ?>

                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-medium text-slate-800">
                                        <?= htmlspecialchars((string) ($reservation['customer_name'] ?? 'Unknown customer'), ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-slate-500">
                                        <?= htmlspecialchars(date('M j, Y', strtotime((string) $reservation['start_at'])), ENT_QUOTES, 'UTF-8') ?>
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        <?= (int) $reservation['party_size'] ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <?php
                                        $statusName = (string) $reservation['status_name'];
                                        $statusColor = $statusColors[strtolower(trim($statusName))]['badge']
                                            ?? 'bg-slate-100 text-slate-700';
                                        ?>

                                        <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $statusColor ?>">
                                            <?= htmlspecialchars($statusName, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                </tr>

                                <?php endforeach; ?>

                                <?php if ($recentReservations === []): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-slate-500">
                                        No reservations to display.
                                    </td>
                                </tr>
                                <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Overview -->
                <div class="rounded-xl border border-slate-200 bg-white p-6">

                    <h3 class="font-bold text-slate-800">Reservation Overview</h3>
                    <p class="mt-1 text-xs text-slate-500">Booking status summary</p>

                    <div class="mt-7 space-y-6">
                        <?php if ($reservationStatuses === []): ?>
                            <p class="text-sm text-slate-500">No reservations to summarize.</p>
                        <?php else: ?>
                            <?php foreach ($reservationStatuses as $statusRow): ?>
                                <?php
                                $statusName = (string) $statusRow['status_name'];
                                $statusCount = (int) $statusRow['reservation_count'];
                                $statusPercentage = $totalReservations > 0
                                    ? ($statusCount / $totalReservations) * 100
                                    : 0;
                                $statusBar = $statusColors[strtolower(trim($statusName))]['bar']
                                    ?? 'bg-slate-500';
                                ?>
                                <div>
                                    <div class="mb-2 flex justify-between text-sm">
                                        <span class="text-slate-600"><?= htmlspecialchars($statusName, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="font-semibold text-slate-800"><?= number_format($statusPercentage, 1) ?>% (<?= number_format($statusCount) ?>)</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full <?= $statusBar ?>" style="width: <?= number_format($statusPercentage, 2, '.', '') ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </main>

        <?php include '../includes/footer.php'; ?>

    </div>

    <!-- Sidebar Toggle -->
    <script>
        const sidebar = document.getElementById('sidebar');
        const menuButton = document.getElementById('menuButton');
        const overlay = document.getElementById('overlay');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        menuButton.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
    </script>

</body>
</html>
