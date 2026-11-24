<?php
$generatedReservationCode = $generatedReservationCode
    ?? ('RSV-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2))));


// ---- Reservation hours ----
$slotStep     = 30;
$openFrom     = 11 * 60;
$openUntil    = 26 * 60;
$startDefault = '18:00';
$endDefault   = '20:00';

$slots = [];
for ($minutes = $openFrom; $minutes < $openUntil; $minutes += $slotStep) {
    $dayOffset = intdiv($minutes, 24 * 60);
    $minuteOfDay = $minutes % (24 * 60);
    $value = sprintf(
        '%02d:%02d',
        intdiv($minuteOfDay, 60),
        $minuteOfDay % 60
    );
    $label = date('g:i A', strtotime("2000-01-01 +{$minutes} minutes"));
    if ($dayOffset > 0) {
        $label .= ' (+1 day)';
    }

    $slots[] = [
        'value' => $value,
        'day_offset' => $dayOffset,
        'label' => $label,
    ];
}

$field  = 'mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10';
$label  = 'block text-xs font-semibold uppercase tracking-wide text-slate-600';
$chipOn = 'rounded-full border border-slate-300 bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-slate-400';

$reservationDateDisplay = (string) ($initialDate ?? '');
$reservationDateObject = DateTime::createFromFormat('Y-m-d', $reservationDateDisplay);
if ($reservationDateObject instanceof DateTime) {
    $reservationDateDisplay = $reservationDateObject->format('l, F j, Y');
}
?>

<!-- ADD RESERVATION MODAL -->
<div
    id="addReservationModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="addReservationModalTitle"
>
    <section class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-xl">

        <!-- Modal Header -->
        <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-4">
            <div>
                <h2 id="addReservationModalTitle" class="text-lg font-bold text-slate-900">
                    Add Reservation
                </h2>
                <p class="mt-0.5 text-xs text-slate-500">
                    Create a booking, set time slot and guest count, and assign tables.
                </p>
            </div>

            <div class="flex items-center gap-3">
                


                <button
                    type="button"
                    id="closeAddReservationModal"
                    aria-label="Close add reservation modal"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </header>
       
        <!-- Highlighted Centered Date Display Outside Header -->
        <div class="flex justify-center border-b border-slate-100 bg-slate-50/80 px-6 py-3">
            <div class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-1.5 text-sm font-bold text-slate-900 shadow-xs">
                <i class="fa-regular fa-calendar text-slate-600"></i>
                <span id="res_date_display"><?= e($reservationDateDisplay) ?></span>
            </div>
        </div>
        <!-- Modal Form -->
        <form
            id="addReservationForm"
            method="POST"
            action="../../backend/Reservation/ReservationCreate.php"
            class="space-y-5 p-6"
        >
            <input type="hidden" id="res_date" name="reservation_date" value="<?= e($initialDate) ?>" >
            <input type="hidden" id="res_start_day_offset" name="start_day_offset" value="0">
            <input type="hidden" id="res_end_day_offset" name="end_day_offset" value="0">
            <input type="hidden" name="action" value="create_reservation">
            <input type="hidden" id="res_code" name="reservation_code" value="<?= e($generatedReservationCode) ?>">

            <p id="reservationStepTitle" class="text-sm font-semibold text-slate-700" aria-live="polite">
                Step 1 of 2: Choose date, time, and table
            </p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" data-reservation-step="1">
                <div>
                    <label for="res_party_size" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Number of Guests <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="res_party_size"
                        name="party_size"
                        min="1"
                        max="200"
                        value="2"
                        required
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                    <p id="partySizeHint" class="mt-1.5 text-xs text-slate-500">
                        Select one or multiple tables to match the party size.
                    </p>
                </div>

                <div id="multiTableSelector" class="relative">
                    <div class="flex items-center justify-between">
                        <label for="tableSelectorTrigger" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                            Assign Table(s) <?php if (!empty($availableTables)): ?><span class="text-red-500">*</span><?php endif; ?>
                        </label>
                        <span id="selectedSeatsBadge" class="text-[11px] font-semibold text-slate-500">
                            0 / 2 seats
                        </span>
                    </div>

                    <!-- Hidden inputs submitted as `table_ids[]` -->
                    <div id="tableIdsHiddenContainer"></div>

                    <!-- Dropdown Trigger -->
                    <button
                        type="button"
                        id="tableSelectorTrigger"
                        aria-expanded="false"
                        class="mt-1.5 flex w-full items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-left text-sm text-slate-900 transition hover:border-slate-400 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10"
                    >
                        <span id="tableSelectorSummary" class="truncate text-slate-500">
                            -- Select Table(s) --
                        </span>
                        <i id="tableSelectorChevron" class="fa-solid fa-chevron-down shrink-0 text-xs text-slate-400 transition-transform duration-150"></i>
                    </button>

                    <!-- Dropdown Panel (Click to select 1 or more tables) -->
                    <div
                        id="tableSelectorPanel"
                        class="absolute left-0 right-0 z-30 mt-1.5 hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                    >
                        <div class="max-h-48 divide-y divide-slate-100 overflow-y-auto">
                            <?php if (!empty($availableTables)): ?>
                                <?php foreach ($availableTables as $tbl): ?>
                                    <?php
                                        $tableId     = (int) $tbl['table_id'];
                                        $tableNumber = (string) $tbl['table_number'];
                                        $tableName   = trim((string) ($tbl['table_name'] ?? ''));
                                        $capacity    = (int) ($tbl['capacity'] ?? 0);
                                        $label       = "Table {$tableNumber}" . ($tableName !== '' ? " - {$tableName}" : "");
                                    ?>
                                    <button
                                        type="button"
                                        data-table-item
                                        data-table-id="<?= e($tableId) ?>"
                                        data-table-capacity="<?= e($capacity) ?>"
                                        data-table-label="<?= e($label) ?>"
                                        aria-pressed="false"
                                        class="flex w-full items-center justify-between gap-2 px-3.5 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50"
                                    >
                                        <div class="flex min-w-0 items-center gap-2.5">
                                            <span
                                                data-check-box
                                                class="flex h-4 w-4 shrink-0 items-center justify-center rounded border border-slate-300 bg-white text-[10px] text-white transition"
                                            >
                                                <i class="fa-solid fa-check hidden"></i>
                                            </span>
                                            <span class="truncate font-medium text-slate-900">
                                                <?= e($label) ?>
                                            </span>
                                        </div>

                                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                                            <?= e($capacity) ?> pax
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="px-3.5 py-3 text-xs text-slate-400">
                                    No active tables available
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-3 py-2 text-xs">
                            <button
                                type="button"
                                id="clearTablesBtn"
                                class="font-medium text-slate-500 hover:text-slate-800"
                            >
                                Clear
                            </button>
                            <button
                                type="button"
                                id="closeTablePanelBtn"
                                class="rounded-md bg-slate-900 px-3 py-1 font-semibold text-white hover:bg-slate-800"
                            >
                                Done
                            </button>
                        </div>
                    </div>

                    <!-- Selected Table Chips -->
                    <div id="selectedTableChips" class="mt-2 hidden flex-wrap gap-1.5"></div>

                    <!-- Capacity & Required Validation Message -->
                    <p id="tableCapacityError" class="mt-1.5 hidden text-xs font-medium text-red-600"></p>
                </div>
            </div>

            <!-- Customer Name, Phone Number & Status -->
            <div class="grid hidden grid-cols-1 gap-4 sm:grid-cols-3"
                 data-reservation-step="2"
                 hidden>
                <div>
                    <label for="res_customer_name" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Customer Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="res_customer_name"
                        name="customer_name"
                        required
                        placeholder="e.g. Juan Dela Cruz"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>

                <div>
                    <label for="res_phone_number" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Phone Number <span class="font-normal text-slate-400">(Optional)</span>
                    </label>
                    <input
                        type="tel"
                        id="res_phone_number"
                        name="phone_number"
                        placeholder="e.g. 0917 123 4567"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>

                <div>
                    <label for="res_status_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select
                        id="res_status_id"
                        name="status_id"
                        required
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                        <?php if (!empty($statuses)): ?>
                            <?php foreach ($statuses as $statusRow): ?>
                                <option value="<?= e($statusRow['status_id']) ?>">
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

            <!-- Date & Time -->
            <div class="space-y-4" data-reservation-step="1">

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="<?= $label ?>">Start time <span class="text-red-500">*</span></label>
                        <input type="hidden" id="res_start_time" name="start_time" value="<?= e($startDefault) ?>">
                        <input type="hidden" id="res_end_time" name="end_time" value="<?= e($endDefault) ?>">
                        <div id="res_start_time_boxes" class="mt-1.5 grid max-h-48 grid-cols-3 gap-2 overflow-y-auto sm:grid-cols-4">
                            <?php foreach ($slots as $slot): ?>
                                <button
                                    type="button"
                                    data-start-time="<?= e($slot['value']) ?>"
                                    data-start-day-offset="<?= e($slot['day_offset']) ?>"
                                    aria-pressed="<?= $slot['value'] === $startDefault && $slot['day_offset'] === 0 ? 'true' : 'false' ?>"
                                    class="rounded-lg border px-2 py-2 text-xs font-semibold transition <?= $slot['value'] === $startDefault && $slot['day_offset'] === 0 ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 bg-white text-slate-700 hover:border-slate-500' ?>"
                                >
                                    <?= e($slot['label']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <span class="<?= $label ?>">Available time</span>
                        <div id="res_available_time_list" class="mt-1.5 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">
                            Choose a start time.
                        </div>
                    </div>
                </div>

                <!-- Quick duration + live summary -->
                <div class="flex flex-col gap-3 rounded-xl bg-slate-50 p-3.5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Quick duration">
                        <span class="mr-1 text-xs font-medium text-slate-500">Duration</span>
                        <?php foreach ([60 => '1 hr', 90 => '1.5 hrs', 120 => '2 hrs', 180 => '3 hrs'] as $mins => $text): ?>
                            <button type="button" data-duration="<?= $mins ?>" aria-pressed="false" class="<?= $chipOn ?>"><?= e($text) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <p id="res_time_summary" aria-live="polite" class="text-sm font-semibold text-slate-900"></p>
                </div>
            </div>

            <!-- Occasion, Source & Table IDs -->
            <div class="grid hidden grid-cols-1 gap-4 sm:grid-cols-2"
                 data-reservation-step="2"
                 hidden>
                <div>
                    <label for="res_occasion" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Occasion
                    </label>
                    <input
                        type="text"
                        id="res_occasion"
                        name="occasion"
                        placeholder="e.g. Birthday, Anniversary"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>

                <div>
                    <label for="res_source" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Booking Source
                    </label>
                    <select
                        id="res_source"
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
            <!-- Deposit Amount & Paid -->
            <div class="grid hidden grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-slate-50/70 p-4 sm:grid-cols-2"
                 data-reservation-step="2"
                 hidden>
                <div>
                    <label for="res_deposit_amount" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Deposit Amount (₱)
                    </label>
                    <input
                        type="number"
                        id="res_deposit_amount"
                        name="deposit_amount"
                        min="0"
                        step="0.01"
                        value="0.00"
                        class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    >
                </div>

                <div class="flex items-end pb-1">
                    <label class="inline-flex cursor-pointer items-center gap-2.5 text-sm font-medium text-slate-700">
                        <input
                            type="checkbox"
                            id="res_deposit_paid"
                            name="deposit_paid"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                        >
                        Deposit Paid
                    </label>
                </div>
            </div>

            <!-- Special Requests & Internal Notes -->
            <div class="grid hidden grid-cols-1 gap-4 sm:grid-cols-2"
                 data-reservation-step="2"
                 hidden>
                <div>
                    <label for="res_special_requests" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Special Requests
                    </label>
                    <textarea
                        id="res_special_requests"
                        name="special_requests"
                        rows="2"
                        placeholder="Allergies, high chair, seating preference..."
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    ></textarea>
                </div>

                <div>
                    <label for="res_internal_notes" class="block text-xs font-semibold uppercase tracking-wide text-slate-600">
                        Internal Notes
                    </label>
                    <textarea
                        id="res_internal_notes"
                        name="internal_notes"
                        rows="2"
                        placeholder="Staff notes (not shown to guest)..."
                        class="mt-1.5 w-full rounded-lg border border-slate-300 px-3.5 py-2 text-sm text-slate-900 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-900/10"
                    ></textarea>
                </div>
            </div>

            <!-- Modal Footer -->
            <footer class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                <button
                    type="button"
                    id="cancelAddReservationModal"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    id="backReservationStepBtn"
                    class="hidden rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    hidden
                >
                    Back
                </button>

                <button
                    type="button"
                    id="nextReservationStepBtn"
                    class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Continue
                    <i class="fa-solid fa-arrow-right ml-1"></i>
                </button>

                <button
                    type="submit"
                    id="saveReservationBtn"
                    class="hidden inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                    hidden
                >
                    <i class="fa-solid fa-plus"></i>
                    Save Reservation
                </button>
            </footer>

        </form>

    </section>
</div>