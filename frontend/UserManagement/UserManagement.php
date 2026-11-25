<?php
include '../../backend/UserManagement/UserManagement.php';
?>
<?php
$jsFile = __DIR__ . '/../js/UserManagement.js';
$jsVersion = file_exists($jsFile) ? filemtime($jsFile) : time();
?>
<script src="<?= e(url('/frontend/js/UserManagement.js')) ?>?v=<?= e($jsVersion) ?>" defer></script>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | NOOMA</title>
    <link rel="icon" type="image/png" href="<?= e(url('/frontend/public/assets/image.png')) ?>">

    <script src="https://cdn.tailwindcss.com"></script>

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

    <div class="flex min-w-0 flex-1 flex-col lg:ml-64">

        <!-- Navbar -->
        <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 p-4 md:p-8">

            <!-- Page Header -->
            <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">

                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        User Management
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Manage system users, roles, and account status.
                    </p>
                </div>         
            <button
                type="button"
                onclick="openUserModal('create')"
                class="inline-flex items-center justify-center rounded-lg bg-navy-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-900 focus:outline-none focus:ring-2 focus:ring-navy-500 focus:ring-offset-2"
            >
                <span class="mr-2 text-lg">+</span>
                Add New User
            </button>
            </div>

            <!-- Statistics -->
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Total Users
                    </p>

                    <h2 class="mt-2 text-3xl font-bold text-slate-900">
                        <?= number_format($totalUsers) ?>
                    </h2>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Active Users
                    </p>

                    <h2 class="mt-2 text-3xl font-bold text-green-600">
                        <?= number_format($activeUsers) ?>
                    </h2>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">
                        Inactive Users
                    </p>

                    <h2 class="mt-2 text-3xl font-bold text-red-600">
                        <?= number_format($inactiveUsers) ?>
                    </h2>
                </div>

            </div>

            <!-- User Table -->
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <!-- Table Header -->
                <div class="flex flex-col justify-between gap-4 border-b border-slate-200 p-5 md:flex-row md:items-center">

                    <div>
                        <h2 class="font-semibold text-slate-900">
                            System Users
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            <?= count($users) ?> user(s) found
                        </p>
                    </div>

                    <!-- Search -->
                    <form method="GET" class="flex w-full gap-2 md:w-auto">

                        <input
                            type="text"
                            name="search"
                            value="<?= e($search) ?>"
                            placeholder="Search users..."
                            class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10 md:w-72"
                        >

                        <button
                            type="submit"
                            class="rounded-lg bg-navy-800 px-4 py-2 text-sm font-medium text-white hover:bg-navy-900"
                        >
                            Search
                        </button>

                        <?php if ($search !== ''): ?>
                            <a
                                href="UserManagement.php"
                                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                            >
                                Clear
                            </a>
                        <?php endif; ?>

                    </form>

                </div>

                <!-- Responsive Table -->
                <div class="overflow-x-auto">

                    <table class="w-full min-w-[1000px] text-left text-sm">

                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">

                            <tr>
                                <th class="px-5 py-4">ID</th>
                                <th class="px-5 py-4">Full Name</th>
                                <th class="px-5 py-4">Username</th>
                                <th class="px-5 py-4">Role</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4">Created At</th>
                                <th class="px-5 py-4 text-center">Actions</th>
                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            <?php if (count($users) > 0): ?>

                                <?php foreach ($users as $user): ?>

                                    <?php
                                        $isActive = (int)$user['is_active'] === 1;
                                        $role = strtolower((string)$user['role']);
                                    ?>

                                    <tr class="transition hover:bg-slate-50">

                                        <td class="px-5 py-4 font-medium text-slate-600">
                                            #<?= e($user['user_id']) ?>
                                        </td>

                                        <td class="px-5 py-4 font-semibold text-slate-900">
                                            <?= e($user['full_name']) ?>
                                        </td>

                                        <td class="px-5 py-4 text-slate-600">
                                            <?= e($user['username']) ?>
                                        </td>

                                        <td class="px-5 py-4">
                                            <?php if ($role === 'admin'): ?>
                                                <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-700">
                                                    Admin
                                                </span>
                                            <?php else: ?>
                                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                                    <?= e(ucfirst($role)) ?>
                                                </span>
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


                                        <td class="px-5 py-4 text-slate-500">
                                            <?php
                                                echo !empty($user['created_at'])
                                                    ? e(date('M d, Y', strtotime($user['created_at'])))
                                                    : '—';
                                            ?>
                                        </td>

                                        <td class="px-5 py-4">
                                            <div class="flex items-center justify-center gap-2">
                                                <button
                                                    type="button"
                                                    onclick='openEditUserModal(<?= json_encode(
                                                        [
                                                            "user_id" => $user["user_id"],
                                                            "full_name" => $user["full_name"],
                                                            "username" => $user["username"],
                                                            "role" => $user["role"],
                                                            "is_active" => $user["is_active"]
                                                        ],
                                                        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                                                    ) ?>)'
                                                    class="inline-flex items-center gap-2 rounded-lg border border-blue-200 px-3 py-2 text-sm font-medium text-blue-700 transition hover:bg-blue-50"
                                                >
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                    Edit
                                                </button>
                                                <button
                                                    type="button"
                                                    onclick="toggleUserStatus(<?= (int) $user['user_id'] ?>, this)"
                                                    class="inline-flex items-center gap-2 rounded-lg border border-amber-200 px-3 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-50"
                                                >
                                                    <i class="fa-solid <?= $isActive ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                                    <?= $isActive ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                                <button
                                                    type="button"
                                                    onclick='openDeleteUserModal(<?= (int) $user["user_id"] ?>, <?= json_encode(
                                                        $user["full_name"],
                                                        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                                                    ) ?>)'
                                                    class="inline-flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50"
                                                >
                                                    <i class="fa-solid fa-trash-can"></i>
                                                    Delete
                                                </button>
                                            </div>
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center">

                                        <div class="text-3xl">👥</div>

                                        <p class="mt-3 font-semibold text-slate-700">
                                            No users found
                                        </p>

                                        <p class="mt-1 text-sm text-slate-500">
                                            Try another search keyword or add a new user.
                                        </p>

                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </main>

        <?php include 'UserManagementModal.php'; ?>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../includes/footer.php'; ?>

    </div>

</div>

</body>
</html>
