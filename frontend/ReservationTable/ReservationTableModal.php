<!-- ADD TABLE MODAL -->
<div
    id="addTableModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
    role="dialog"
    aria-modal="true"
    aria-labelledby="addTableModalTitle"
>
    <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h2 id="addTableModalTitle" class="text-lg font-bold text-slate-900">
                    Add Restaurant Table
                </h2>
                <p class="mt-0.5 text-xs text-slate-500">
                    Configure table details, seating capacity, and floor layout coordinates.
                </p>
            </div>

            <button
                type="button"
                id="closeAddTableModalBtn"
                aria-label="Close modal"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
            >
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form
            id="addTableForm"
            method="POST"
            action="../../backend/ReservationTable/ReservationTable.php"
            class="space-y-5 p-6"
        >
            <!-- Basic Table Info -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="table_number" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Table Number <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="table_number"
                        name="table_number"
                        required
                        placeholder="e.g. T-01 or 1"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                    >
                </div>
                <div>
                    <label for="area_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Area <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="area_id"
                        name="area_id"
                        required
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                    >
                        <option value="">Select an area</option>
                        <?php foreach ($availableAreas as $area): ?>
                            <option value="<?= e($area['area_id']) ?>">
                                <?= e($area['area_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="table_name" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Table Name
                    </label>
                    <input
                        type="text"
                        id="table_name"
                        name="table_name"
                        placeholder="e.g. Window Booth"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                    >
                </div>
                <div>
                    <label for="capacity" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Capacity (Seats) <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="capacity"
                        name="capacity"
                        min="1"
                        max="100"
                        value="4"
                        required
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                    >
                </div>
            </div>

            <!-- Capacity, Shape & Status -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                

                <div>
                    <label for="shape" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Shape
                    </label>
                    <select
                        id="shape"
                        name="shape"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                    >
                        <option value="rectangle" selected>Rectangle</option>
                        <option value="square">Square</option>
                        <option value="round">Round</option>
                        <option value="oval">Oval</option>
                    </select>
                </div>

                <div>
                    <label for="is_active" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Status
                    </label>
                    <select
                        id="is_active"
                        name="is_active"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                    >
                        <option value="1" selected>Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            

            <!-- Notes -->
            <div>
                <label for="notes" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                    Notes
                </label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="3"
                    placeholder="Optional notes (e.g. near kitchen, high chair accessible)..."
                    class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                ></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                <button
                    type="button"
                    id="cancelAddTableModalBtn"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-navy-800 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-900 focus:outline-none focus:ring-2 focus:ring-navy-700 focus:ring-offset-2"
                >
                    <i class="fa-solid fa-plus"></i>
                    Save Table
                </button>
            </div>
        </form>

    </div>
</div>
<!-- EDIT TABLE MODAL -->
<div
    id="editTableModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
    role="dialog"
    aria-modal="true"
    aria-labelledby="editTableModalTitle"
>
    <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div>
                <h2 id="editTableModalTitle" class="text-lg font-bold text-slate-900">
                    Edit Restaurant Table
                </h2>
                <p class="mt-0.5 text-xs text-slate-500">
                    Update table details, seating capacity, and status.
                </p>
            </div>

            <button
                type="button"
                id="closeEditTableModalBtn"
                aria-label="Close modal"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
            >
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form
            id="editTableForm"
            method="POST"
            action="../../backend/ReservationTable/ReservationTableUpdate.php"
            class="space-y-5 p-6"
        >
            <input type="hidden" name="action" value="update_table">
            <input type="hidden" id="edit_table_id" name="table_id">
            <input type="hidden" id="edit_area_id" name="area_id">
            <input type="hidden" id="edit_x_position" name="x_position">
            <input type="hidden" id="edit_y_position" name="y_position">
            <input type="hidden" id="edit_width" name="width">
            <input type="hidden" id="edit_height" name="height">
            <input type="hidden" id="edit_rotation" name="rotation">

            <!-- Basic Table Info -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="edit_table_number" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Table Number <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="edit_table_number"
                        name="table_number"
                        required
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                    >
                </div>

                <div>
                    <label for="edit_table_name" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Table Name
                    </label>
                    <input
                        type="text"
                        id="edit_table_name"
                        name="table_name"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                    >
                </div>

                <div>
                    <label for="edit_capacity" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Capacity (Seats) <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="edit_capacity"
                        name="capacity"
                        min="1"
                        max="100"
                        required
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                    >
                </div>
            </div>

            <!-- Shape & Status -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="edit_shape" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Shape
                    </label>
                    <select
                        id="edit_shape"
                        name="shape"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                    >
                        <option value="rectangle">Rectangle</option>
                        <option value="square">Square</option>
                        <option value="round">Round</option>
                        <option value="oval">Oval</option>
                    </select>
                </div>

                <div>
                    <label for="edit_is_active" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Status
                    </label>
                    <select
                        id="edit_is_active"
                        name="is_active"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                    >
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="edit_notes" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                    Notes
                </label>
                <textarea
                    id="edit_notes"
                    name="notes"
                    rows="3"
                    class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-navy-700 focus:ring-2 focus:ring-navy-700/10"
                ></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                <button
                    type="button"
                    id="cancelEditTableModalBtn"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-navy-800 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-navy-900 focus:outline-none focus:ring-2 focus:ring-navy-700 focus:ring-offset-2"
                >
                    <i class="fa-solid fa-check"></i>
                    Update Table
                </button>
            </div>
        </form>

    </div>
</div>
<!-- DELETE TABLE MODAL -->
<div
    id="deleteTableModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
    role="dialog"
    aria-modal="true"
    aria-labelledby="deleteTableModalTitle"
>
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl">

        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h2 id="deleteTableModalTitle" class="text-lg font-bold text-slate-900">
                        Delete Restaurant Table
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500">
                        This action cannot be undone.
                    </p>
                </div>
            </div>

            <button
                type="button"
                id="closeDeleteTableModalBtn"
                aria-label="Close modal"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
            >
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Modal Body & Form -->
        <form
            id="deleteTableForm"
            method="POST"
            action="../../backend/ReservationTable/ReservationTableDelete.php"
            class="space-y-5 p-6"
        >
            <input type="hidden" name="action" value="delete_table">
            <input type="hidden" id="delete_table_id" name="table_id">

            <p class="text-sm text-slate-600">
                Are you sure you want to permanently delete
                <span id="delete_table_label" class="font-semibold text-slate-900">this table</span>?
            </p>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                <button
                    type="button"
                    id="cancelDeleteTableModalBtn"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                >
                    <i class="fa-solid fa-trash"></i>
                    Delete Table
                </button>
            </div>
        </form>

    </div>
</div>
<script src="../../frontend/js/ReservationTable.js"></script>