
<?php
require_once __DIR__ . '/../../backend/Reservation/Reservation.php';

$firstDaySunday = (int) $monthDate->format('w');
$monthYear = e($monthDate->format('F Y'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reservations Calendar | NOOMA</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="../css/reservation.css">
</head>

<body class="min-h-screen text-slate-900">

<div class="flex min-h-screen">

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="flex min-w-0 flex-1 flex-col lg:ml-64">

        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <main class="flex-1 p-4 md:p-6">

            <!-- PAGE HEADER -->
            <div class="mb-6 flex flex-col justify-between gap-4 lg:flex-row lg:items-center">

                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Reservations Calendar
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Manage bookings with live MySQL database sync.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">

                    <!-- MONTH AND YEAR -->
                    <div class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-800">
                        <i class="fa-regular fa-calendar text-slate-500"></i>
                        <?= e($monthDate->format('F Y')) ?>
                    </div>

                    <!-- MONTH NAVIGATION -->
                    <div class="flex overflow-hidden rounded-lg border border-slate-300 bg-white">

                        <a href="?month=<?= e($previousMonth) ?>"
                        class="flex h-10 w-10 items-center justify-center text-slate-700 transition hover:bg-slate-100"
                        aria-label="Previous month">
                            <i class="fa-solid fa-chevron-left text-xs"></i>
                        </a>

                        <div class="w-px bg-slate-200"></div>

                        <a href="?month=<?= e($nextMonthLink) ?>"
                        class="flex h-10 w-10 items-center justify-center text-slate-700 transition hover:bg-slate-100"
                        aria-label="Next month">
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </a>

                    </div>

                </div>

            </div>

            <!-- CALENDAR AND BOOKINGS -->
            <div class="grid grid-cols-1 gap-5 2xl:grid-cols-3">

                <!-- CALENDAR GRID -->
                <section class="min-w-0 rounded-xl border border-slate-300 bg-white p-4 md:p-5 2xl:col-span-2">

                    <div class="mb-6">
                        <h2 class="text-base font-semibold text-slate-900">
                            Calendar Grid
                        </h2>
                    </div>

                    <div class="grid grid-cols-7 gap-1.5 sm:gap-2">

                        <!-- WEEKDAYS: SUNDAY FIRST -->
                        <?php foreach (
                            ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
                            as $weekday
                        ): ?>
                            <div class="py-2 text-center text-xs font-medium text-slate-400 sm:text-sm">
                                <?= e($weekday) ?>
                            </div>
                        <?php endforeach; ?>

                        <!-- EMPTY DAYS BEFORE MONTH START -->
                        <?php for ($i = 0; $i < $firstDaySunday; $i++): ?>
                            <div class="calendar-empty"></div>
                        <?php endfor; ?>

                        <!-- MONTH DAYS -->
                        <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>

                            <?php
                            $dateKey = $monthDate->format('Y-m-')
                                . str_pad((string) $day, 2, '0', STR_PAD_LEFT);

                            $events = $calendarEvents[$dateKey] ?? [];

                            $isToday = $dateKey === $today;
                            $isInitial = $dateKey === $initialDate;
                            ?>

                            <button
                                type="button"
                                data-calendar-date="<?= e($dateKey) ?>"
                                class="calendar-day <?= $isToday ? 'today' : '' ?> <?= $isInitial ? 'selected' : '' ?>">

                                <!-- DAY NUMBER -->
                                <div class="flex items-center justify-between">
                                    <span class="day-number">
                                        <?= $day ?>
                                    </span>

                                    <?php if (count($events) > 0): ?>
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    <?php endif; ?>
                                </div>

                                <!-- BOOKING COUNT -->
                                <?php if (count($events) > 0): ?>
                                    <span class="booking-count">
                                        <?= count($events) ?>
                                        <?= count($events) === 1 ? 'booking' : 'bookings' ?>
                                    </span>
                                <?php endif; ?>

                                <!-- AVAILABILITY -->
                                <span class="availability-label mt-auto">
                                    Available
                                </span>

                            </button>

                        <?php endfor; ?>

                    </div>

                </section>

                <!-- DAILY BOOKINGS PANEL -->
                <aside class="flex min-h-[500px] flex-col rounded-xl border border-slate-300 bg-white">

                    <!-- PANEL HEADER -->
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 p-4">

                       <div class="flex min-w-0 items-center gap-3">
                            <i class="fa-regular fa-calendar-check text-slate-700"></i>

                            <button
                                type="button"
                                id="selectedDateLabel"
                                data-date="<?= e($initialDate) ?>"
                                title="Show bookings for this date"
                                class="truncate text-left text-sm font-semibold text-slate-900 underline-offset-4 hover:text-slate-600 hover:underline">
                                Bookings for <?= e($initialDate) ?>
                            </button>
                        </div>
                       <div class="flex shrink-0 items-center gap-2">
                            <a id="asideAddReservationBtn"
                            href="create.php?date=<?= e($initialDate) ?>"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                                <i class="fa-solid fa-plus"></i>
                                Add Reservation
                            </a>

                            <a href="create.php"
                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                Make available
                            </a>
                        </div>

                    </div>

                    <!-- BOOKINGS -->
                    <div id="dailyReservationList"
                         class="flex flex-1 flex-col gap-3 overflow-y-auto p-4">

                        <div class="flex flex-1 flex-col items-center justify-center py-16 text-center">

                            <i class="fa-regular fa-user mb-3 text-3xl text-slate-300"></i>

                            <p class="text-sm text-slate-400">
                                No bookings on this date
                            </p>

                        </div>

                    </div>

                </aside>

            </div>

        </main>

        <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    </div>

</div>

<!-- PHP DATA FOR JAVASCRIPT -->
<script>
    window.reservationCalendarData = {
        events: <?= $calendarJson ?>,
        initialDate: <?= json_encode($initialDate) ?>
    };
</script>

<script src="../js/Reservation.js" defer></script>

<div
    id="reservationDetailsModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="reservationDetailsTitle">
    <section class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-xl">
        <header class="flex items-center justify-between border-b border-slate-100 p-5">
            <h2 id="reservationDetailsTitle" class="text-lg font-bold text-slate-900">
                Reservation details
            </h2>
            <button
                type="button"
                id="closeReservationModal"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900"
                aria-label="Close reservation details">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>
        <div id="reservationDetailsContent" class="flex flex-col gap-5 p-5"></div>
    </section>
</div>

</body>
</html>
