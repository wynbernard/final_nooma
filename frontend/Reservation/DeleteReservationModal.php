<div
    id="deleteReservationModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-xs"
    role="dialog"
    aria-modal="true"
    aria-labelledby="deleteReservationModalTitle"
>
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white shadow-xl">
        <header class="flex items-center justify-between gap-4 border-b border-slate-100 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <i class="fa-solid fa-trash-can text-sm"></i>
                </div>
                <div>
                    <h2 id="deleteReservationModalTitle" class="text-base font-bold text-slate-900">
                        Delete Reservation
                    </h2>
                    <p class="text-xs text-slate-500">
                        This action cannot be undone.
                    </p>
                </div>
            </div>

            <button
                type="button"
                id="closeDeleteReservationModal"
                aria-label="Close delete reservation modal"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>

        <form
            id="deleteReservationForm"
            method="POST"
            action="../../backend/Reservation/ReservationDelete.php"
            class="p-6"
        >
            <input type="hidden" id="delete_reservation_id" name="reservation_id" value="">
            <input type="hidden" id="delete_reservation_date" name="reservation_date" value="">

            <p class="text-sm text-slate-600">
                Are you sure you want to delete the reservation for
                <span id="deleteCustomerName" class="font-bold text-slate-900">this guest</span>?
            </p>

            <div id="deleteReservationMeta" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs text-slate-600">
                <div class="flex items-center justify-between py-0.5">
                    <span class="text-slate-400">Code</span>
                    <span id="deleteReservationCode" class="font-semibold text-slate-800">-</span>
                </div>
                <div class="flex items-center justify-between py-0.5">
                    <span class="text-slate-400">Schedule</span>
                    <span id="deleteReservationSchedule" class="font-semibold text-slate-800">-</span>
                </div>
                <div class="flex items-center justify-between py-0.5">
                    <span class="text-slate-400">Guests</span>
                    <span id="deleteReservationGuests" class="font-semibold text-slate-800">-</span>
                </div>
            </div>

            <footer class="mt-6 flex items-center justify-end gap-3">
                <button
                    type="button"
                    id="cancelDeleteReservationModal"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                >
                    <i class="fa-solid fa-trash-can text-xs"></i>
                    Delete Reservation
                </button>
            </footer>
        </form>
    </div>
</div>