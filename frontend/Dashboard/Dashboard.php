
<?php
require_once __DIR__ . '/../../backend/database/session.php';

if (auth() === null) {
    header('Location: ' . url('/'));
    exit;
}

$pageTitle = "Dashboard";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $pageTitle ?> - NOOMA</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50">

    <?php include '../includes/sidebar.php'; ?>

    <div id="overlay"
        class="fixed inset-0 z-40 hidden bg-black/50 lg:hidden"></div>

    <div class="min-h-screen lg:ml-64 flex flex-col">

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

                <button class="rounded-lg bg-[#1D3B70] px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                    + New Reservation
                </button>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex justify-between">
                        <p class="text-sm text-slate-500">Total Reservations</p>
                        <span class="text-xl text-blue-600">▤</span>
                    </div>
                    <h2 class="mt-4 text-3xl font-bold text-slate-800">248</h2>
                    <p class="mt-2 text-xs text-green-600">+12.5% from last month</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex justify-between">
                        <p class="text-sm text-slate-500">Today's Reservations</p>
                        <span class="text-xl text-indigo-600">▣</span>
                    </div>
                    <h2 class="mt-4 text-3xl font-bold text-slate-800">32</h2>
                    <p class="mt-2 text-xs text-green-600">+8.2% from yesterday</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex justify-between">
                        <p class="text-sm text-slate-500">Total Customers</p>
                        <span class="text-xl text-purple-600">♙</span>
                    </div>
                    <h2 class="mt-4 text-3xl font-bold text-slate-800">1,284</h2>
                    <p class="mt-2 text-xs text-green-600">+15.3% this month</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex justify-between">
                        <p class="text-sm text-slate-500">Available Tables</p>
                        <span class="text-xl text-green-600">▦</span>
                    </div>
                    <h2 class="mt-4 text-3xl font-bold text-slate-800">18</h2>
                    <p class="mt-2 text-xs text-slate-500">Out of 30 tables</p>
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

                        <a href="#" class="text-sm font-semibold text-blue-700 hover:underline">
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

                                <?php
                                $reservations = [
                                    ["John Smith", "Nov 19, 2026", 4, "Confirmed"],
                                    ["Maria Santos", "Nov 19, 2026", 2, "Pending"],
                                    ["David Cruz", "Nov 20, 2026", 6, "Confirmed"],
                                    ["Anna Reyes", "Nov 20, 2026", 3, "Cancelled"]
                                ];

                                foreach ($reservations as $reservation):
                                ?>

                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-medium text-slate-800">
                                        <?= htmlspecialchars($reservation[0]) ?>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-slate-500">
                                        <?= htmlspecialchars($reservation[1]) ?>
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        <?= $reservation[2] ?>
                                    </td>

                                    <td class="px-6 py-4">
                                        <?php
                                        $statusColors = [
                                            "Confirmed" => "bg-green-100 text-green-700",
                                            "Pending" => "bg-yellow-100 text-yellow-700",
                                            "Cancelled" => "bg-red-100 text-red-700"
                                        ];

                                        $color = $statusColors[$reservation[3]];
                                        ?>

                                        <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $color ?>">
                                            <?= htmlspecialchars($reservation[3]) ?>
                                        </span>
                                    </td>
                                </tr>

                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Overview -->
                <div class="rounded-xl border border-slate-200 bg-white p-6">

                    <h3 class="font-bold text-slate-800">Reservation Overview</h3>
                    <p class="mt-1 text-xs text-slate-500">Booking status summary</p>

                    <div class="mt-7 space-y-6">

                        <div>
                            <div class="mb-2 flex justify-between text-sm">
                                <span class="text-slate-600">Confirmed</span>
                                <span class="font-semibold text-slate-800">75%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full w-3/4 rounded-full bg-green-500"></div>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex justify-between text-sm">
                                <span class="text-slate-600">Pending</span>
                                <span class="font-semibold text-slate-800">15%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full w-[15%] rounded-full bg-yellow-500"></div>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex justify-between text-sm">
                                <span class="text-slate-600">Cancelled</span>
                                <span class="font-semibold text-slate-800">10%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full w-[10%] rounded-full bg-red-500"></div>
                            </div>
                        </div>

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
