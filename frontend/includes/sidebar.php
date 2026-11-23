
<?php
// Get current PHP filename
$currentPage = basename($_SERVER['PHP_SELF']);

// Active menu styling
function activeMenu(string $page): string
{
    global $currentPage;

    return $currentPage === $page
        ? 'bg-blue-600 text-white font-medium shadow-lg shadow-blue-900/20'
        : 'text-slate-300 hover:bg-white/10 hover:text-white';
}
?>

<link rel="stylesheet" href="../css/sidebar.css">
<aside id="sidebar"
    class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col overflow-hidden bg-[#101B35] text-white transition-transform duration-300 -translate-x-full lg:translate-x-0">

    <!-- Logo -->
    <div class="flex h-20 shrink-0 items-center gap-3 border-b border-white/10 px-6">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-xl font-bold">
            N
        </div>

        <div>
            <h1 class="text-lg font-bold tracking-wide">NOOMA</h1>
            <p class="text-xs text-slate-400">Management System</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav flex-1 space-y-2 overflow-y-auto px-4 py-6">

        <!-- Main Menu -->
        <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
            Main Menu
        </p>

        <a href="../Dashboard/Dashboard.php"
            class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm transition <?= activeMenu('Dashboard.php') ?>">
            <span>▦</span>
            Dashboard
        </a>

        <a href="../Reservation/Reservation.php"
            class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm transition <?= activeMenu('Reservation.php') ?>">
            <span>▤</span>
            Reservations
        </a>

        <a href="../ReservationTable/ReservationTable.php"
            class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm transition <?= activeMenu('ReservationTable.php') ?>">
            <span>▣</span>
            Table Management
        </a>

        <a href="../Customers/Customers.php"
            class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm transition <?= activeMenu('Customers.php') ?>">
            <span>♙</span>
            Customers
        </a>

        <!-- Management -->
        <p class="mb-3 mt-8 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
            Management
        </p>

        <a href="../Inventory/Inventory.php"
            class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm transition <?= activeMenu('Inventory.php') ?>">
            <span>▧</span>
            Inventory
        </a>

        <a href="../UserManagement/UserManagement.php"
            class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm transition <?= activeMenu('UserManagement.php') ?>">
            <span>♧</span>
            Users Management
        </a>

        <a href="../Reports/Reports.php"
            class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm transition <?= activeMenu('Reports.php') ?>">
            <span>▥</span>
            Reports
        </a>

        <!-- System -->
        <p class="mb-3 mt-8 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
            System
        </p>

        <a href="../Settings/Settings.php"
            class="flex items-center gap-3 rounded-lg px-4 py-3 text-sm transition <?= activeMenu('Settings.php') ?>">
            <span>⚙</span>
            Settings
        </a>

    </nav>

    <!-- Sidebar Bottom -->
    <div class="shrink-0 border-t border-white/10 p-4">

        <form method="POST" action="../../backend/Logout/logout.php">

            <input
                type="hidden"
                name="_csrf"
                value="<?= htmlspecialchars((string) $_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">

            <button type="submit"
                class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left text-sm text-red-300 transition hover:bg-red-500/10 hover:text-red-200">

                <span>↪</span>
                Logout

            </button>

        </form>

    </div>

</aside>
