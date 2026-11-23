
<!-- CREATE USER MODAL -->
<div
    id="userModal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
>
    <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">

        <!-- HEADER -->
        <div class="flex items-center justify-between border-b border-slate-100 bg-white px-6 py-5">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                    <i class="fa-solid fa-user-plus text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Create New User</h2>
                    <p class="mt-1 text-sm text-slate-500">Add a new account to your system.</p>
                </div>
            </div>

            <button
                type="button"
                onclick="closeUserModal()"
                class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                aria-label="Close modal"
            >
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- FORM -->
        <form
            id="userForm"
            action="../../backend/UserManagement/UserManagement.php"
            method="POST"
            autocomplete="off"
            class="flex-1 space-y-6 overflow-y-auto px-6 py-6"
        >
            <input type="hidden" name="_csrf"
                value="<?= htmlspecialchars((string)($_SESSION['csrf'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

            <input type="hidden" name="action" value="create">
            <input type="hidden" name="is_active" value="0">

            <!-- ERROR -->
            <div
                id="userFormMessage"
                class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                role="alert"
                aria-live="polite"
            ></div>

            <!-- PERSONAL INFORMATION -->
            <div class="space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-800">
                        <i class="fa-regular fa-id-card mr-2 text-blue-600"></i>
                        Personal Information
                    </h3>
                    <p class="mt-1 text-xs text-slate-500">Enter the user's basic information.</p>
                </div>

                <div>
                    <label for="full_name" class="mb-2 block text-sm font-semibold text-slate-700">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="150"
                        placeholder="e.g. Juan Dela Cruz"
                        required
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                    >
                </div>

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
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
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
                            placeholder="name@example.com"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>
                </div>
            </div>

            <!-- ACCOUNT SECURITY -->
            <div class="space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-800">
                        <i class="fa-solid fa-shield-halved mr-2 text-blue-600"></i>
                        Account Security
                    </h3>
                    <p class="mt-1 text-xs text-slate-500">Set a secure password for the new account.</p>
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">
                        Password <span class="text-red-500">*</span>
                    </label>

                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            minlength="8"
                            maxlength="72"
                            placeholder="Enter a secure password"
                            required
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-12 text-sm outline-none transition placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                        >

                        <button
                            type="button"
                            onclick="togglePasswordVisibility('password', this)"
                            class="absolute inset-y-0 right-0 px-4 text-slate-400 transition hover:text-blue-600"
                            aria-label="Show password"
                        >
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>

                    <p class="mt-2 text-xs text-slate-500">
                        <i class="fa-solid fa-circle-info mr-1"></i>
                        Use at least 8 characters.
                    </p>
                </div>
            </div>

            <!-- ROLE AND STATUS -->
            <div class="space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-800">
                        <i class="fa-solid fa-user-gear mr-2 text-blue-600"></i>
                        Access & Permissions
                    </h3>
                    <p class="mt-1 text-xs text-slate-500">Configure the user's system access.</p>
                </div>

                <div>
                    <label for="role" class="mb-2 block text-sm font-semibold text-slate-700">
                        User Role <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="role"
                        name="role"
                        required
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                    >
                        <option value="staff" selected>Staff</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <label class="flex cursor-pointer items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-blue-50/40">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Active Account</p>
                        <p class="mt-1 text-xs text-slate-500">Allow this user to log in to the system.</p>
                    </div>

                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        checked
                        class="h-5 w-5 cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                    >
                </label>
            </div>

            <!-- FOOTER -->
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    onclick="closeUserModal()"
                    class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="saveUserBtn"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-navy-800 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-900 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <i class="fa-solid fa-user-plus"></i>
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT USER MODAL -->
<div
    id="editUserModal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
>
    <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">

        <!-- HEADER -->
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                    <i class="fa-solid fa-user-pen text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Edit User Account</h2>
                    <p class="mt-1 text-sm text-slate-500">Update user details and permissions.</p>
                </div>
            </div>

            <button
                type="button"
                onclick="closeEditUserModal()"
                class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                aria-label="Close modal"
            >
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- FORM -->
        <form
            id="editUserForm"
            action="../../backend/UserManagement/UserManagement.php"
            method="POST"
            autocomplete="off"
            class="flex-1 space-y-6 overflow-y-auto px-6 py-6"
        >
            <input type="hidden" name="_csrf"
                value="<?= htmlspecialchars((string)($_SESSION['csrf'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" id="edit_user_id">

            <div
                id="editUserFormMessage"
                class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                role="alert"
                aria-live="polite"
            ></div>

            <!-- PERSONAL INFORMATION -->
            <div class="space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-800">
                        <i class="fa-regular fa-id-card mr-2 text-blue-600"></i>
                        Personal Information
                    </h3>
                    <p class="mt-1 text-xs text-slate-500">Modify the user's account details.</p>
                </div>

                <div>
                    <label for="edit_full_name" class="mb-2 block text-sm font-semibold text-slate-700">
                        Full Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="edit_full_name"
                        name="full_name"
                        maxlength="150"
                        required
                        placeholder="Enter full name"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                    >
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="edit_username" class="mb-2 block text-sm font-semibold text-slate-700">
                            Username <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="edit_username"
                            name="username"
                            maxlength="50"
                            required
                            placeholder="Enter username"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label for="edit_email" class="mb-2 block text-sm font-semibold text-slate-700">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="email"
                            id="edit_email"
                            name="email"
                            maxlength="150"
                            required
                            placeholder="name@example.com"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>
                </div>
            </div>

            <!-- PASSWORD -->
            <div class="space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-800">
                        <i class="fa-solid fa-shield-halved mr-2 text-blue-600"></i>
                        Change Password
                    </h3>
                    <p class="mt-1 text-xs text-slate-500">Leave blank if you don't want to change the password.</p>
                </div>

                <div>
                    <label for="edit_password" class="mb-2 block text-sm font-semibold text-slate-700">
                        New Password
                    </label>

                    <div class="relative">
                        <input
                            type="password"
                            id="edit_password"
                            name="password"
                            minlength="8"
                            maxlength="72"
                            placeholder="Enter a new password (optional)"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-12 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                        >

                        <button
                            type="button"
                            onclick="togglePasswordVisibility('edit_password', this)"
                            class="absolute inset-y-0 right-0 px-4 text-slate-400 transition hover:text-blue-600"
                            aria-label="Show password"
                        >
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ACCESS -->
            <div class="space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-800">
                        <i class="fa-solid fa-user-gear mr-2 text-blue-600"></i>
                        Access & Permissions
                    </h3>
                    <p class="mt-1 text-xs text-slate-500">Manage the user's role and account status.</p>
                </div>

                <div>
                    <label for="edit_role" class="mb-2 block text-sm font-semibold text-slate-700">
                        User Role <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="edit_role"
                        name="role"
                        required
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100"
                    >
                        <option value="staff">Staff</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <label class="flex cursor-pointer items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-200 hover:bg-blue-50/40">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Active Account</p>
                        <p class="mt-1 text-xs text-slate-500">Allow this user to log in to the system.</p>
                    </div>

                    <div>
                        <input type="hidden" name="is_active" value="0">
                        <input
                            type="checkbox"
                            id="edit_is_active"
                            name="is_active"
                            value="1"
                            class="h-5 w-5 cursor-pointer rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        >
                    </div>
                </label>
            </div>

            <!-- FOOTER -->
            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    onclick="closeEditUserModal()"
                    class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="updateUserBtn"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-navy-800 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-900 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
<script src="../../frontend/js/UserManagement.js" defer></script>