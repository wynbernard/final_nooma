<div
    id="editReservationModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
    role="dialog"
    aria-modal="true"
    aria-labelledby="editReservationModalTitle"
>
    <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl">
        <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-4">
            <div>
                <h2 id="editReservationModalTitle" class="text-lg font-bold text-slate-900">
                    Edit Reservation
                </h2>
                <p id="editReservationCodeLabel" class="mt-0.5 text-xs font-medium text-slate-500"></p>
            </div>
            <button
                type="button"
                id="closeEditReservationModal"
                aria-label="Close edit reservation modal"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>

        <form
            id="editReservationForm"
            method="POST"
            action="../../backend/Reservation/ReservationUpdate.php"
            class="space-y-4 p-6"
        >
            <input type="hidden" id="edit_reservation_id" name="reservation_id" value="">
            <input type="hidden" id="edit_return_date" name="return_date" value="">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="edit_res_date" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Date <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="date"
                        id="edit_res_date"
                        name="reservation_date"
                        required
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>

                <div>
                    <label for="edit_start_time" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Start Time <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="time"
                        id="edit_start_time"
                        name="start_time"
                        required
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>

                <div>
                    <label for="edit_end_time" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        End Time <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="time"
                        id="edit_end_time"
                        name="end_time"
                        required
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="edit_party_size" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Number of Guests <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="edit_party_size"
                        name="party_size"
                        min="1"
                        max="200"
                        required
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>

                <div>
                    <label for="edit_table_ids" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Assign Table(s) <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="edit_table_ids"
                        name="table_ids[]"
                        multiple
                        required
                        class="mt-1.5 h-24 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                        <?php foreach ($availableTables as $tbl): ?>
                            <option value="<?= e((int) $tbl['table_id']) ?>" data-capacity="<?= e((int) $tbl['capacity']) ?>">
                                Table <?= e($tbl['table_number']) ?>
                                <?= !empty($tbl['table_name']) ? ' - ' . e($tbl['table_name']) : '' ?>
                                (<?= e((int) $tbl['capacity']) ?> pax)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p id="editTableCapacityNote" class="mt-1 text-xs text-slate-500">
                        Hold Ctrl/Cmd or click to select one or more tables.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="edit_customer_name" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Customer Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="edit_customer_name"
                        name="customer_name"
                        required
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>

                <div>
                    <label for="edit_phone_number" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Phone Number
                    </label>
                    <input
                        type="tel"
                        id="edit_phone_number"
                        name="phone_number"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>

                <div>
                    <label for="edit_status_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="edit_status_id"
                        name="status_id"
                        required
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                        <?php if (!empty($statuses)): ?>
                            <?php foreach ($statuses as $statusRow): ?>
                                <option value="<?= e((int) $statusRow['status_id']) ?>">
                                    <?= e($statusRow['status_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="1">Pending</option>
                            <option value="2">Confirmed</option>
                            <option value="3">Seated</option>
                            <option value="4">Completed</option>
                            <option value="5">Cancelled</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="edit_occasion" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Occasion
                    </label>
                    <input
                        type="text"
                        id="edit_occasion"
                        name="occasion"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>

                <div>
                    <label for="edit_source" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Booking Source
                    </label>
                    <select
                        id="edit_source"
                        name="source"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                        <option value="Walk-in">Walk-in</option>
                        <option value="Phone">Phone</option>
                        <option value="Website">Website</option>
                        <option value="Social Media">Social Media</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:grid-cols-2">
                <div>
                    <label for="edit_deposit_amount" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Deposit Amount (₱)
                    </label>
                    <input
                        type="number"
                        id="edit_deposit_amount"
                        name="deposit_amount"
                        min="0"
                        step="0.01"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>

                <div class="flex items-end pb-1">
                    <label class="inline-flex cursor-pointer items-center gap-2.5 text-sm font-medium text-slate-700">
                        <input
                            type="checkbox"
                            id="edit_deposit_paid"
                            name="deposit_paid"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                        >
                        Deposit Paid
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="edit_special_requests" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Special Requests
                    </label>
                    <textarea
                        id="edit_special_requests"
                        name="special_requests"
                        rows="2"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    ></textarea>
                </div>

                <div>
                    <label for="edit_internal_notes" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Internal Notes
                    </label>
                    <textarea
                        id="edit_internal_notes"
                        name="internal_notes"
                        rows="2"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    ></textarea>
                </div>
            </div>

            <footer class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <button
                    type="button"
                    id="cancelEditReservationModal"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    <i class="fa-solid fa-floppy-disk"></i>
                    Update Reservation
                </button>
            </footer>
        </form>
    </div>
</div>
