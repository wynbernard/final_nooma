
<!-- CREATE USER MODAL -->
<div
    id="userModal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
>
    <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5">
            <div>
                <h2 class="text-xl font-bold text-slate-800">
                    Add New User
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Create a new account for the system.
                </p>
            </div>

            <button
                type="button"
                onclick="closeUserModal()"
                class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                aria-label="Close modal"
            >
                &times;
            </button>
        </div>

        <!-- Modal Body -->
        <form
            id="userForm"
            action="../../backend/UserManagement/users_crud.php"
            method="POST"
            autocomplete="off"
            class="space-y-5 px-6 py-6"
        >
            <!-- Security and Action -->
            <input
                type="hidden"
                name="_csrf"
                value="<?= htmlspecialchars((string)($_SESSION['csrf'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            >

            <input type="hidden" name="action" value="create">

            <input type="hidden" name="is_active" value="0">

            <!-- Error Message -->
            <div
                id="userFormMessage"
                class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                role="alert"
                aria-live="polite"
            ></div>

            <!-- Full Name -->
            <div>
                <label for="full_name" class="mb-2 block text-sm font-semibold text-slate-700">
                    Full Name <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    maxlength="150"
                    placeholder="Enter full name"
                    required
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                >
            </div>

            <!-- Username and Email -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                <div>
                    <label for="username" class="mb-2 block text-sm font-semibold text-slate-700">
                        Username <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        maxlength="50"
                        placeholder="Enter username"
                        required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                    >
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">
                        Email Address <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="150"
                        placeholder="example@email.com"
                        required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                    >
                </div>

            </div>

            <!-- Password -->
            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">
                    Password <span class="text-red-500">*</span>
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    maxlength="72"
                    placeholder="Minimum 8 characters"
                    required
                    autocomplete="new-password"
                    class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                >

                <p class="mt-1 text-xs text-slate-500">
                    Password must contain at least 8 characters.
                </p>
            </div>

            <!-- Role -->
            <div>
                <label for="role" class="mb-2 block text-sm font-semibold text-slate-700">
                    User Role <span class="text-red-500">*</span>
                </label>

                <select
                    id="role"
                    name="role"
                    required
                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                >
                    <option value="staff" selected>Staff</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <!-- Account Status -->
            <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <div>
                    <p class="text-sm font-semibold text-slate-700">
                        Account Status
                    </p>
                    <p class="text-xs text-slate-500">
                        Allow this user to access the system.
                    </p>
                </div>

                <label class="relative inline-flex cursor-pointer items-center">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        checked
                        class="peer sr-only"
                    >

                    <div class="h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-blue-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                </label>
            </div>

            <!-- Modal Footer -->
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">

                <button
                    type="button"
                    onclick="closeUserModal()"
                    class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="saveUserBtn"
                    class="rounded-lg bg-navy-800 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-900 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Create User
                </button>

            </div>
        </form>
    </div>
</div>
<script src="../js/UserMAnagement.js" defer></script>