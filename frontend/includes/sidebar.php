<?php
// Get current PHP filename
$currentPage = basename((string) ($_SERVER['PHP_SELF'] ?? ''));

// Resolve current user role safely from auth() or session
$sidebarUser = function_exists('auth') ? auth() : ($_SESSION['user'] ?? null);
$userRole = strtolower(trim((string) (
    $sidebarUser['role']
    ?? $_SESSION['role']
    ?? ''
)));

// Roles allowed to access Users Management
$canManageUsers = in_array($userRole, ['admin'], true);

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

<!-- Mobile Backdrop -->
<div id="sidebarBackdrop"
    class="fixed inset-0 z-40 hidden bg-slate-950/60 backdrop-blur-sm lg:hidden"
    aria-hidden="true"></div>

<aside id="sidebar"
    class="group fixed inset-y-0 left-0 z-50 flex w-64 flex-col overflow-hidden bg-[#101B35] text-white transition-all duration-300 -translate-x-full lg:translate-x-0"
    data-collapsed="false">

    <!-- Logo & Collapse Controls -->
    <div class="flex h-20 shrink-0 items-center justify-between border-b border-white/10 px-4">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-xl font-bold">
                N
            </div>

            <div class="sidebar-label min-w-0 transition-opacity duration-200">
                <h1 class="truncate text-lg font-bold tracking-wide">NOOMA</h1>
                <p class="truncate text-xs text-slate-400">Management System</p>
            </div>
        </div>

        <button
            type="button"
            id="sidebarCollapseBtn"
            title="Collapse sidebar"
            aria-label="Toggle sidebar collapse"
            class="hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-300 transition hover:bg-white/10 hover:text-white lg:inline-flex">
            <span id="sidebarCollapseIcon" class="text-sm font-semibold">⟨</span>
        </button>

        <button
            type="button"
            id="mobileSidebarCloseBtn"
            aria-label="Close sidebar"
            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-300 transition hover:bg-white/10 hover:text-white lg:hidden">
            <span>✕</span>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav flex-1 space-y-2 overflow-y-auto px-3 py-6">

        <!-- Main Menu (visible to cashier, manager, admin) -->
        <p class="sidebar-label mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
            Main Menu
        </p>

        <a href="../Dashboard/Dashboard.php"
            title="Dashboard"
            class="flex items-center gap-3 rounded-lg px-3.5 py-3 text-sm transition <?= activeMenu('Dashboard.php') ?>">
            <span class="shrink-0 text-center">▦</span>
            <span class="sidebar-label truncate">Dashboard</span>
        </a>

        <a href="../Reservation/Reservation.php"
            title="Reservations"
            class="flex items-center gap-3 rounded-lg px-3.5 py-3 text-sm transition <?= activeMenu('Reservation.php') ?>">
            <span class="shrink-0 text-center">▤</span>
            <span class="sidebar-label truncate">Reservations</span>
        </a>

        <a href="../ReservationTable/ReservationTable.php"
            title="Table Management"
            class="flex items-center gap-3 rounded-lg px-3.5 py-3 text-sm transition <?= activeMenu('ReservationTable.php') ?>">
            <span class="shrink-0 text-center">▣</span>
            <span class="sidebar-label truncate">Table Management</span>
        </a>

        <!-- Management (hidden for cashier) -->
        <?php if ($canManageUsers): ?>
            <p class="sidebar-label mb-3 mt-8 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                Management
            </p>

            <a href="../UserManagement/UserManagement.php"
                title="Users Management"
                class="flex items-center gap-3 rounded-lg px-3.5 py-3 text-sm transition <?= activeMenu('UserManagement.php') ?>">
                <span class="shrink-0 text-center">♧</span>
                <span class="sidebar-label truncate">Users Management</span>
            </a>
        <?php endif; ?>

    </nav>

    <!-- Sidebar Bottom -->
    <div class="shrink-0 border-t border-white/10 p-3">

        <form method="POST" action="../../backend/Logout/logout.php">

            <input
                type="hidden"
                name="_csrf"
                value="<?= htmlspecialchars((string) ($_SESSION['csrf'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

            <button type="submit"
                title="Logout"
                class="flex w-full items-center gap-3 rounded-lg px-3.5 py-3 text-left text-sm text-red-300 transition hover:bg-red-500/10 hover:text-red-200">

                <span class="shrink-0 text-center">↪</span>
                <span class="sidebar-label truncate">Logout</span>

            </button>

        </form>

    </div>

</aside>

<script>
(function () {
    const STORAGE_KEY = "nooma_sidebar_collapsed";
    const sidebar = document.getElementById("sidebar");
    const backdrop = document.getElementById("sidebarBackdrop");
    const collapseBtn = document.getElementById("sidebarCollapseBtn");
    const collapseIcon = document.getElementById("sidebarCollapseIcon");
    const mobileCloseBtn = document.getElementById("mobileSidebarCloseBtn");

    if (!sidebar) return;

    function syncMainContentOffset(isCollapsed) {
        const pageLayouts = document.querySelectorAll(".page-layout");
        const isDesktop = window.innerWidth >= 1024;

        pageLayouts.forEach((layout) => {
            layout.style.marginLeft = isDesktop && isCollapsed ? "5rem" : "";
            layout.style.transition = isDesktop ? "margin-left 300ms" : "";
        });
    }

    function applyCollapsedState(collapsed) {
        const isCollapsed = Boolean(collapsed);
        sidebar.dataset.collapsed = isCollapsed ? "true" : "false";
        sidebar.classList.toggle("w-64", !isCollapsed);
        sidebar.classList.toggle("w-20", isCollapsed);

        sidebar.querySelectorAll(".sidebar-label").forEach((el) => {
            el.classList.toggle("hidden", isCollapsed);
        });

        sidebar.querySelectorAll("nav a, form button").forEach((item) => {
            item.classList.toggle("justify-center", isCollapsed);
            item.classList.toggle("px-2", isCollapsed);
            item.classList.toggle("px-3.5", !isCollapsed);
        });

        if (collapseIcon) {
            collapseIcon.textContent = isCollapsed ? "⟩" : "⟨";
        }

        syncMainContentOffset(isCollapsed);
    }

    window.toggleSidebarCollapse = function (forceState) {
        const current = sidebar.dataset.collapsed === "true";
        const next = typeof forceState === "boolean" ? forceState : !current;
        try {
            localStorage.setItem(STORAGE_KEY, next ? "1" : "0");
        } catch (_) {}
        applyCollapsedState(next);
    };

    window.toggleMobileSidebar = function (open) {
        const isOpen = typeof open === "boolean"
            ? open
            : sidebar.classList.contains("-translate-x-full");

        sidebar.classList.toggle("-translate-x-full", !isOpen);
        sidebar.classList.toggle("translate-x-0", isOpen);
        backdrop?.classList.toggle("hidden", !isOpen);
    };

    collapseBtn?.addEventListener("click", () => window.toggleSidebarCollapse());
    mobileCloseBtn?.addEventListener("click", () => window.toggleMobileSidebar(false));
    backdrop?.addEventListener("click", () => window.toggleMobileSidebar(false));

    window.addEventListener("resize", () => {
        syncMainContentOffset(sidebar.dataset.collapsed === "true");
        if (window.innerWidth >= 1024) {
            backdrop?.classList.add("hidden");
            sidebar.classList.remove("-translate-x-full");
        }
    });

    let savedState = false;
    try {
        savedState = localStorage.getItem(STORAGE_KEY) === "1";
    } catch (_) {}

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => applyCollapsedState(savedState), { once: true });
    } else {
        applyCollapsedState(savedState);
    }
})();
</script>