<?php
require_once __DIR__ . '/../../backend/ReservationTable/ReservationTable.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | NOOMA</title>
    <link rel="icon" type="image/png" href="<?= e(url('/frontend/public/assets/image.png')) ?>">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: {
                            700: '#17365D',
                            800: '#102A43',
                            900: '#0B1F33'
                        }
                    }
                }
            }
        };
    </script>
</head>

<body class="bg-slate-100 text-slate-800">

<div class="flex min-h-screen">

    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="page-layout flex min-w-0 flex-1 flex-col lg:ml-64">

        <!-- Navbar -->
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 p-4 md:p-8">

            <!-- Page Header -->
            <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Table Management
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Manage restaurant tables, seating capacity, layout coordinates, and availability status.
                    </p>
                </div>

                <button
                    type="button"
                    id="openAddTableModalBtn"
                    onclick="openAddTableModal()"
                    class="inline-flex items-center justify-center rounded-lg bg-navy-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-900 focus:outline-none focus:ring-2 focus:ring-navy-700 focus:ring-offset-2"
                >
                    <span class="mr-2 text-lg">+</span>
                    Add New Table
                </button>
            </div>

            <!-- Statistics -->
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Total Tables
                    </p>

                    <h2 class="mt-2 text-3xl font-bold text-slate-900">
                        <?= number_format($totalTables) ?>
                    </h2>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Active Tables
                    </p>

                    <h2 class="mt-2 text-3xl font-bold text-green-600">
                        <?= number_format($activeTables) ?>
                    </h2>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Inactive Tables
                    </p>

                    <h2 class="mt-2 text-3xl font-bold text-red-600">
                        <?= number_format($inactiveTables) ?>
                    </h2>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Active Seating Capacity
                    </p>

                    <h2 class="mt-2 text-3xl font-bold text-navy-800">
                        <?= number_format($totalCapacity) ?>
                    </h2>
                </div>

            </div>

            <!-- Restaurant Tables Card -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <!-- Table Header & Filters -->
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-5 lg:flex-row lg:items-center">

                    <div>
                        <h2 class="font-semibold text-slate-900">
                            Restaurant Tables
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            <?= count($tables) ?> table(s) found
                        </p>
                    </div>

                    <!-- Search & Filter Form -->
                    <form method="GET" class="flex flex-wrap items-center gap-2">

                        <select
                            name="area_id"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                        >
                            <option value="">All Areas</option>
                            <?php foreach ($areas as $area): ?>
                                <?php $areaIdVal = (string) $area['area_id']; ?>
                                <option value="<?= e($areaIdVal) ?>" <?= $areaFilter === $areaIdVal ? 'selected' : '' ?>>
                                    Area #<?= e($areaIdVal) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select
                            name="status"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                        >
                            <option value="">All Statuses</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>

                        <input
                            type="text"
                            name="search"
                            value="<?= e($search) ?>"
                            placeholder="Search table #, name, shape, notes..."
                            class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10 sm:w-64"
                        >

                        <button
                            type="submit"
                            class="rounded-lg bg-navy-800 px-4 py-2 text-sm font-medium text-white hover:bg-navy-900"
                        >
                            Filter
                        </button>

                        <?php if ($search !== '' || $areaFilter !== '' || $statusFilter !== ''): ?>
                            <a
                                href="tables.php"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                            >
                                Clear
                            </a>
                        <?php endif; ?>

                    </form>

                </div>

                <!-- Responsive Table -->
                <div class="overflow-x-auto">

                    <table class="w-full min-w-[1150px] text-left text-sm">

                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">

                            <tr>
                                <th class="px-5 py-4">NO.</th>
                                <th class="px-5 py-4">Table</th>
                                <th class="px-5 py-4">Capacity</th>
                                <th class="px-5 py-4">Shape</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4">Notes</th>
                                <th class="px-5 py-4">Updated At</th>
                                <th class="px-5 py-4 text-center">Actions</th>
                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            <?php if (count($tables) > 0): ?>

                                <?php foreach ($tables as $index => $table): ?>

                                    <?php
                                        $rowNumber = $index + 1;
                                        $isActive = (int) ($table['is_active'] ?? 0) === 1;
                                        $shape = trim((string) ($table['shape'] ?? ''));
                                    ?>

                                    <tr class="transition hover:bg-slate-50">

                                        <td class="px-5 py-4 font-medium text-slate-600">
                                            #<?= $rowNumber ?>
                                        </td>

                                        <td class="px-5 py-4">
                                            <div class="font-semibold text-slate-900">
                                                <?= e($table['table_number']) ?>
                                            </div>
                                            <?php if (!empty($table['table_name'])): ?>
                                                <div class="text-xs text-slate-500">
                                                    <?= e($table['table_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                                <i class="fa-solid fa-users text-slate-400"></i>
                                                <?= e($table['capacity'] ?? 0) ?> Seats
                                            </span>
                                        </td>

                                        <td class="px-5 py-4">
                                            <?php if ($shape !== ''): ?>
                                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold capitalize text-blue-700">
                                                    <?= e($shape) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-slate-400">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="px-5 py-4">
                                            <?php if ($isActive): ?>
                                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                                    Active
                                                </span>
                                            <?php else: ?>
                                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                                    Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="max-w-[200px] px-5 py-4 text-slate-600">
                                            <p class="truncate" title="<?= e($table['notes'] ?? '') ?>">
                                                <?= !empty($table['notes']) ? e($table['notes']) : '—' ?>
                                            </p>
                                        </td>

                                        <td class="px-5 py-4 text-slate-500">
                                            <?php
                                                $timestamp = $table['updated_at'] ?: $table['created_at'];
                                                echo !empty($timestamp)
                                                    ? e(date('M d, Y', strtotime((string) $timestamp)))
                                                    : '—';
                                            ?>
                                        </td>

                                       <td class="px-5 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <button
                                                    type="button"
                                                    onclick='openEditTableModal(<?= json_encode([
                                                        "table_id"     => $table["table_id"],
                                                        "area_id"      => $table["area_id"],
                                                        "table_number" => $table["table_number"],
                                                        "table_name"   => $table["table_name"],
                                                        "capacity"     => $table["capacity"],
                                                        "shape"        => $table["shape"],
                                                        "x_position"   => $table["x_position"],
                                                        "y_position"   => $table["y_position"],
                                                        "width"        => $table["width"],
                                                        "height"       => $table["height"],
                                                        "rotation"     => $table["rotation"],
                                                        "is_active"    => $table["is_active"],
                                                        "notes"        => $table["notes"],
                                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 px-3 py-2 text-sm font-medium text-blue-700 transition hover:bg-blue-50"
                                                >
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                    Edit
                                                </button>

                                                <button
                                                    type="button"
                                                    onclick='openDeleteTableModal(<?= json_encode([
                                                        "table_id"     => $table["table_id"],
                                                        "table_number" => $table["table_number"],
                                                        "table_name"   => $table["table_name"],
                                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'
                                                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                >
                                                    <i class="fa-solid fa-trash"></i>
                                                    Delete
                                                </button>
                                            </div>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="9" class="px-5 py-12 text-center">

                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <i class="fa-solid fa-chair text-xl"></i>
                                        </div>

                                        <p class="mt-3 font-semibold text-slate-700">
                                            No tables found
                                        </p>

                                        <p class="mt-1 text-sm text-slate-500">
                                            Try adjusting your filters or add a new table.
                                        </p>

                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </main>

        <?php require_once __DIR__ . '/ReservationTableModal.php'; ?>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    </div>

</div>

<script>
    window.restaurantTablesData = <?= $tablesJson ?>;
</script>

</body>
</html>