"use strict";

function shiftCalendarDate(date, days) {
  const shifted = new Date(`${date}T12:00:00`);
  shifted.setDate(shifted.getDate() + days);
  return `${shifted.getFullYear()}-${String(shifted.getMonth() + 1).padStart(2, "0")}-${String(shifted.getDate()).padStart(2, "0")}`;
}

document.addEventListener("DOMContentLoaded", function () {
  // ================================================================
  // 1. CALENDAR DATA
  // ================================================================

  const calendarData = window.reservationCalendarData || {};

  const calendarEvents = calendarData.events || {};

  const dailyList = document.getElementById("dailyReservationList");

  const selectedDateLabel = document.getElementById("selectedDateLabel");

  const reservationModal = document.getElementById("reservationDetailsModal");

  const modalContent = document.getElementById("reservationDetailsContent");

  let activeDate = calendarData.initialDate || "";

  // ================================================================
  // 2. STATUS COLORS
  // ================================================================

  const statusClasses = {
    confirmed: "bg-green-100 text-green-700",
    pending: "bg-amber-100 text-amber-700",
    cancelled: "bg-red-100 text-red-700",
    canceled: "bg-red-100 text-red-700",
    seated: "bg-blue-100 text-blue-700",
    completed: "bg-slate-100 text-slate-700",
    "no-show": "bg-red-100 text-red-700",
  };

  // ================================================================
  // 3. HELPER FUNCTIONS
  // ================================================================

  function escapeHTML(value) {
    return String(value ?? "").replace(
      /[&<>"']/g,
      (char) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#039;",
        })[char],
    );
  }

  function peso(value) {
    return Number(value || 0).toLocaleString("en-PH", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }

  function formatDate(date) {
    const parsedDate = new Date(date + "T12:00:00");
    if (Number.isNaN(parsedDate.getTime())) {
      return String(date || "");
    }

    return parsedDate.toLocaleDateString("en-PH", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  function isCalendarDateFullyUnavailable(date) {
    const events = [
      ...(calendarEvents[date] || []),
      ...(calendarEvents[shiftCalendarDate(date, 1)] || []),
    ];
    const activeTableCount = Number(calendarData.activeTableCount || 0);
    if (activeTableCount <= 0 || events.length === 0) return false;

    for (
      let startMinutes = 660;
      startMinutes + 60 <= 1560;
      startMinutes += 30
    ) {
      const candidateStart = new Date(`${date}T00:00:00`);
      candidateStart.setMinutes(startMinutes);
      const candidateEnd = new Date(candidateStart.getTime() + 60 * 60 * 1000);

      const occupiedTableIds = new Set();
      events.forEach((reservation) => {
        const status = String(reservation.status || "").toLowerCase();
        if (status === "cancelled" || status === "canceled") return;

        const existingStart = reservation.start_at
          ? new Date(String(reservation.start_at).replace(" ", "T"))
          : null;
        const existingEnd = reservation.end_at
          ? new Date(String(reservation.end_at).replace(" ", "T"))
          : null;
        if (
          existingStart &&
          existingEnd &&
          candidateStart < existingEnd &&
          candidateEnd > existingStart
        ) {
          (reservation.table_ids || []).forEach((tableId) =>
            occupiedTableIds.add(String(tableId)),
          );
        }
      });

      if (occupiedTableIds.size < activeTableCount) return false;
    }

    return true;
  }

  function formatAssignedAt(assignedAt) {
    if (!assignedAt) return "";
    const normalized = String(assignedAt).replace(" ", "T");
    const parsed = new Date(normalized);
    if (Number.isNaN(parsed.getTime())) {
      return escapeHTML(assignedAt);
    }

    return escapeHTML(
      parsed.toLocaleString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      }),
    );
  }

  function normalizeTables(event) {
    if (Array.isArray(event?.tables) && event.tables.length > 0) {
      return event.tables;
    }

    if (Array.isArray(event?.table_ids) && event.table_ids.length > 0) {
      return event.table_ids.map((id) => ({
        table_id: id,
        assigned_at: null,
      }));
    }

    return [];
  }

  function tableLabel(entry) {
    if (entry.table_name) return String(entry.table_name);
    if (entry.table_number !== null && entry.table_number !== undefined) {
      return `Table ${entry.table_number}`;
    }

    return `Table #${entry.table_id}`;
  }

  function formatTableSummary(event) {
    const tables = normalizeTables(event);
    if (tables.length === 0) return "No table assigned";

    return tables.map((entry) => escapeHTML(tableLabel(entry))).join(", ");
  }

  function renderTableDetailsHTML(event) {
    const tables = normalizeTables(event);

    if (tables.length === 0) {
      return `
        <p class="mt-1 text-sm font-medium text-slate-500">
          No tables assigned yet
        </p>
      `;
    }

    return `
      <div class="mt-2 space-y-2">
        ${tables
          .map((entry) => {
            const assignedLabel = formatAssignedAt(entry.assigned_at);
            return `
              <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                <span class="font-semibold text-slate-800">
                  <i class="fa-solid fa-chair mr-1.5 text-slate-400"></i>
                  ${escapeHTML(tableLabel(entry))}
                </span>
                ${
                  assignedLabel
                    ? `<span class="text-slate-500">Assigned: ${assignedLabel}</span>`
                    : `<span class="text-slate-400">Assigned</span>`
                }
              </div>
            `;
          })
          .join("")}
      </div>
    `;
  }

  // ================================================================
  // 4. SHOW DAILY RESERVATIONS
  // ================================================================

  function showDay(date) {
    if (!date || !dailyList) return;

    activeDate = date;
    const events = calendarEvents[date] || [];

    // Keep the selected state on exactly one calendar day.
    document.querySelectorAll("[data-calendar-date]").forEach((day) => {
      day.classList.toggle("selected", day.dataset.calendarDate === date);
    });

    document.querySelectorAll("[data-calendar-date]").forEach((day) => {
      const unavailable = isCalendarDateFullyUnavailable(
        day.dataset.calendarDate,
      );
      day.disabled = unavailable;
      day.classList.toggle("cursor-not-allowed", unavailable);
      day.classList.toggle("opacity-50", unavailable);
      const availabilityLabel = day.querySelector(".availability-label");
      if (availabilityLabel) {
        const isPastDate = day.dataset.calendarDate < calendarData.today;
        availabilityLabel.textContent = isPastDate
          ? "Past date"
          : unavailable
            ? "Fully occupied"
            : "Available";
      }
    });

    // Update selected date label
    if (selectedDateLabel) {
      selectedDateLabel.dataset.date = date;
      selectedDateLabel.textContent = formatDate(date);
    }

    const reservationDateDisplay = document.getElementById("res_date_display");
    if (reservationDateDisplay) {
      reservationDateDisplay.textContent = formatDate(date);
    }

    const reservationDateInput = document.getElementById("res_date");
    if (reservationDateInput) {
      reservationDateInput.value = date;
    }
    window.refreshReservationTableAvailability?.();

    const addReservationButton = document.getElementById(
      "asideAddReservationBtn",
    );
    if (addReservationButton) {
      addReservationButton.dataset.date = date;
      const isPastDate = date < calendarData.today;
      addReservationButton.disabled = isPastDate;
      addReservationButton.title = isPastDate
        ? "Reservations cannot be added for past dates"
        : "";
      addReservationButton.classList.toggle("cursor-not-allowed", isPastDate);
      addReservationButton.classList.toggle("bg-slate-400", isPastDate);
      addReservationButton.classList.toggle("bg-slate-900", !isPastDate);
      addReservationButton.classList.toggle("hover:bg-slate-800", !isPastDate);
    }

    // No reservations
    if (events.length === 0) {
      dailyList.innerHTML = `
        <div class="py-10 text-center">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
            <i class="fa-regular fa-calendar-xmark text-xl"></i>
          </div>
          <p class="mt-3 font-semibold text-slate-700">No reservations</p>
          <p class="mt-1 text-sm text-slate-500">There are no bookings on this date.</p>
        </div>
            `;

      return;
    }

    // Display reservations
    dailyList.innerHTML = events
      .map((event, index) => {
        const status = String(event.status || "pending").toLowerCase();

        const badge = statusClasses[status] || "bg-blue-100 text-blue-700";

        const tableSummary = formatTableSummary(event);
        const reservationId = event.id ?? "";

        return `
          <div
            data-reservation-index="${index}"
            data-reservation-id="${escapeHTML(reservationId)}"
            class="daily-reservation-card rounded-xl border border-slate-200 bg-white p-4 text-left transition hover:border-blue-300 hover:bg-blue-50/40"
          >
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="font-bold text-slate-900">
                  ${escapeHTML(event.customer_name || "Unknown customer")}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                  <i class="fa-regular fa-clock mr-1"></i>
                  ${escapeHTML(event.start)} - ${escapeHTML(event.end)}
                </p>
              </div>

              <span class="rounded-full px-2.5 py-1 text-[10px] font-bold capitalize ${badge}">
                ${escapeHTML(event.status)}
              </span>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-4 text-sm text-slate-600">
              <span>
                <i class="fa-solid fa-users mr-1 text-slate-400"></i>
                ${escapeHTML(event.guests)} Guests
              </span>

              <span class="text-xs text-slate-500">
                <i class="fa-solid fa-chair mr-1 text-slate-400"></i>
                ${tableSummary}
              </span>
            </div>

            <div class="mt-3.5 flex items-center justify-between gap-2 border-t border-slate-100 pt-3">
              <button
                type="button"
                data-action="view-reservation"
                data-reservation-index="${index}"
                data-reservation-id="${escapeHTML(reservationId)}"
                class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700 transition hover:text-blue-800"
              >
                View details
                <i class="fa-solid fa-arrow-right"></i>
              </button>

              <div class="flex items-center gap-1.5">
                <button
                  type="button"
                  data-action="edit-reservation"
                  data-reservation-index="${index}"
                  data-reservation-id="${escapeHTML(reservationId)}"
                  class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                >
                  <i class="fa-solid fa-pen-to-square text-[11px]"></i>
                  Edit
                </button>

                <button
                  type="button"
                  data-action="delete-reservation"
                  data-reservation-index="${index}"
                  data-reservation-id="${escapeHTML(reservationId)}"
                  class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50/70 px-2.5 py-1 text-xs font-medium text-red-700 transition hover:border-red-300 hover:bg-red-100"
                >
                  <i class="fa-solid fa-trash-can text-[11px]"></i>
                  Delete
                </button>
              </div>
            </div>
          </div>
        `;
      })
      .join("");

    // Attach click events
    dailyList.querySelectorAll(".daily-reservation-card").forEach((card) => {
      card.addEventListener("click", function (clickEvent) {
        const actionButton = clickEvent.target.closest("[data-action]");
        const action = actionButton?.dataset.action;
        const index = Number(this.dataset.reservationIndex);

        if (action === "edit-reservation") {
          window.openEditReservationModal?.(
            events[index],
            actionButton.dataset.reservationId,
          );
          return;
        }

        if (action === "delete-reservation") {
          window.openDeleteReservationModal?.(
            events[index],
            actionButton.dataset.reservationId,
          );
          return;
        }

        showReservationDetails(date, index);
      });
    });
  }

  // ================================================================
  // 5. SHOW RESERVATION DETAILS
  // ================================================================

  function showReservationDetails(date, index) {
    const event = calendarEvents[date]?.[index];

    if (!event || !reservationModal || !modalContent) return;

    const status = String(event.status || "pending").toLowerCase();

    const badge = statusClasses[status] || "bg-blue-100 text-blue-700";

    const depositAmount = Number(event.deposit_amount || 0);

    // Assumes deposit_paid is a boolean field (0 or 1)
    const isDepositPaid = ["1", "true", "yes"].includes(
      String(event.deposit_paid).toLowerCase(),
    );

    const depositStatus = isDepositPaid ? "Paid" : "Not Paid";

    const tableDetailsHTML = renderTableDetailsHTML(event);

    modalContent.innerHTML = `

            <!-- CUSTOMER NAME -->
            <div>

                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Customer Name
                </p>

                <p class="mt-1 text-xl font-bold text-slate-900">
                    ${escapeHTML(event.customer_name || "Unknown customer")}
                </p>

            </div>


            <!-- DATE AND GUESTS -->
            <div class="grid grid-cols-2 gap-4">

                <div>

                    <p class="text-xs text-slate-400">
                        Date
                    </p>

                    <p class="mt-1 text-sm font-semibold text-slate-800">
                        ${escapeHTML(date)}
                    </p>

                </div>

                <div>

                    <p class="text-xs text-slate-400">
                        Guests
                    </p>

                    <p class="mt-1 text-sm font-semibold text-slate-800">
                        ${escapeHTML(event.guests)}
                    </p>

                </div>

            </div>


            <!-- TIME -->
            <div class="grid grid-cols-2 gap-4">

                <div>

                    <p class="text-xs text-slate-400">
                        Start Time
                    </p>

                    <p class="mt-1 text-sm font-semibold text-slate-800">
                        ${escapeHTML(event.start)}
                    </p>

                </div>

                <div>

                    <p class="text-xs text-slate-400">
                        End Time
                    </p>

                    <p class="mt-1 text-sm font-semibold text-slate-800">
                        ${escapeHTML(event.end)}
                    </p>

                </div>

            </div>


            <!-- TABLE RESERVATION DETAILS -->
            <div>

                <p class="text-xs text-slate-400">
                    Table Reservation Details
                </p>

                ${tableDetailsHTML}

            </div>


            <!-- CUSTOMER -->
            <div>

                <p class="text-xs text-slate-400">
                    Customer ID
                </p>

                <p class="mt-1 text-sm font-semibold text-slate-800">
                    ${escapeHTML(event.customer_id)}
                </p>

            </div>


            <!-- OCCASION -->
            <div>

                <p class="text-xs text-slate-400">
                    Occasion
                </p>

                <p class="mt-1 text-sm font-semibold text-slate-800">
                    ${escapeHTML(event.occasion || "Not specified")}
                </p>

            </div>


            <!-- SOURCE -->
            <div>

                <p class="text-xs text-slate-400">
                    Booking Source
                </p>

                <p class="mt-1 text-sm font-semibold text-slate-800">
                    ${escapeHTML(event.source || "Not specified")}
                </p>

            </div>


            <!-- SPECIAL REQUESTS -->
            <div>

                <p class="text-xs text-slate-400">
                    Special Requests
                </p>

                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">
                    ${escapeHTML(event.special_requests || "None")}
                </p>

            </div>


            <!-- INTERNAL NOTES -->
            <div>

                <p class="text-xs text-slate-400">
                    Internal Notes
                </p>

                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">
                    ${escapeHTML(event.internal_notes || "None")}
                </p>

            </div>


            <!-- DEPOSIT -->
            <div class="rounded-xl bg-slate-50 p-4">

                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Deposit Information
                </p>

                <div class="mt-3 flex justify-between text-sm">

                    <span class="text-slate-500">
                        Deposit Amount
                    </span>

                    <strong class="text-slate-900">
                        ₱${peso(depositAmount)}
                    </strong>

                </div>

                <div class="mt-3 flex justify-between text-sm">

                    <span class="text-slate-500">
                        Payment Status
                    </span>

                    <strong class="${isDepositPaid ? "text-green-600" : "text-amber-600"}">
                        ${depositStatus}
                    </strong>

                </div>

            </div>


            <!-- STATUS -->
            <div>

                <p class="text-xs text-slate-400">
                    Reservation Status
                </p>

                <span class="mt-2 inline-block rounded-full px-3 py-1 text-xs font-bold capitalize ${badge}">
                    ${escapeHTML(event.status)}
                </span>

            </div>


            <!-- EDIT BUTTON -->
            <a href="edit.php?id=${encodeURIComponent(event.id)}"
               class="flex w-full items-center justify-center gap-2 rounded-xl bg-navy-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-navy-900">

                <i class="fa-solid fa-pen-to-square"></i>

                Edit Reservation

            </a>

        `;

    reservationModal.classList.remove("hidden");

    reservationModal.classList.add("flex");
  }

  // ================================================================
  // 6. CLOSE MODAL
  // ================================================================

  function closeReservationDetails() {
    if (!reservationModal) return;

    reservationModal.classList.add("hidden");

    reservationModal.classList.remove("flex");
  }

  // Close button
  document
    .getElementById("closeReservationModal")
    ?.addEventListener("click", closeReservationDetails);

  // Close when clicking outside modal
  reservationModal?.addEventListener("click", function (event) {
    if (event.target === reservationModal) {
      closeReservationDetails();
    }
  });

  // Close using Escape key
  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      closeReservationDetails();
    }
  });

  // ================================================================
  // 7. CALENDAR DATE & SIDE DATE LABEL EVENTS
  // ================================================================

  document.querySelectorAll("[data-calendar-date]").forEach((day) => {
    day.addEventListener("click", function () {
      showDay(this.dataset.calendarDate);
    });
  });

  selectedDateLabel?.addEventListener("click", function () {
    const targetDate = this.dataset.date || activeDate;
    if (!targetDate) return;

    showDay(targetDate);

    const dayBtn = document.querySelector(
      `[data-calendar-date="${CSS.escape(targetDate)}"]`,
    );
    dayBtn?.scrollIntoView({ behavior: "smooth", block: "nearest" });
  });

  // ================================================================
  // 8. INITIAL SELECTED DATE
  // ================================================================

  if (activeDate) {
    showDay(activeDate);
  }
});

(function () {
  function formatReservationDate(date) {
    const parsedDate = new Date(date + "T12:00:00");
    if (Number.isNaN(parsedDate.getTime())) {
      return String(date || "");
    }

    return parsedDate.toLocaleDateString("en-PH", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
    });
  }

  function getModalEls() {
    return {
      modal: document.getElementById("addReservationModal"),
      form: document.getElementById("addReservationForm"),
      closeBtn: document.getElementById("closeAddReservationModal"),
      cancelBtn: document.getElementById("cancelAddReservationModal"),
      dateInput: document.getElementById("res_date"),
      dateDisplay: document.getElementById("res_date_display"),
      asideBtn: document.getElementById("asideAddReservationBtn"),
      nextBtn: document.getElementById("nextReservationStepBtn"),
      backBtn: document.getElementById("backReservationStepBtn"),
      saveBtn: document.getElementById("saveReservationBtn"),
      stepTitle: document.getElementById("reservationStepTitle"),
    };
  }

  function showReservationStep(step) {
    const { nextBtn, backBtn, saveBtn, stepTitle } = getModalEls();
    const isFirstStep = step === 1;

    document.querySelectorAll("[data-reservation-step]").forEach((section) => {
      const isVisible = section.dataset.reservationStep === String(step);
      section.hidden = !isVisible;
      section.classList.toggle("hidden", !isVisible);
    });

    if (stepTitle) {
      stepTitle.textContent = isFirstStep
        ? "Step 1 of 2: Choose date, time, and table"
        : "Step 2 of 2: Enter reservation details";
    }

    nextBtn?.classList.toggle("hidden", !isFirstStep);
    if (nextBtn) nextBtn.hidden = !isFirstStep;
    backBtn?.classList.toggle("hidden", isFirstStep);
    if (backBtn) backBtn.hidden = isFirstStep;
    saveBtn?.classList.toggle("hidden", isFirstStep);
    if (saveBtn) saveBtn.hidden = isFirstStep;
  }

  const { asideBtn } = getModalEls();
  asideBtn?.addEventListener("click", function () {
    window.openAddReservationModal(this.dataset.date);
  });

  const { nextBtn, backBtn } = getModalEls();
  nextBtn?.addEventListener("click", function () {
    const requiredFields = document.querySelectorAll(
      '#addReservationForm [data-reservation-step="1"] :required',
    );

    for (const field of requiredFields) {
      if (!field.reportValidity()) return;
    }

    const partySize = parseInt(
      document.getElementById("res_party_size")?.value || "0",
      10,
    );
    const selectedSeats = document.querySelectorAll(
      '[data-table-item][aria-pressed="true"]',
    ).length
      ? Array.from(
          document.querySelectorAll('[data-table-item][aria-pressed="true"]'),
        ).reduce(
          (total, item) =>
            total + (parseInt(item.dataset.tableCapacity || "0", 10) || 0),
          0,
        )
      : 0;
    const capacityError = document.getElementById("tableCapacityError");

    if (partySize > 0 && selectedSeats < partySize) {
      if (capacityError) {
        capacityError.textContent = `Selected tables provide ${selectedSeats} seats. Please select enough tables for ${partySize} guests.`;
        capacityError.classList.remove("hidden");
      }
      document.getElementById("tableSelectorTrigger")?.focus();
      return;
    }

    const startInput = document.getElementById("res_start_time");
    const endInput = document.getElementById("res_end_time");
    if (!startInput?.value || !endInput?.value) {
      document
        .querySelector(
          '[data-reservation-step="1"] [data-start-time]:not(:disabled)',
        )
        ?.focus();
      return;
    }

    showReservationStep(2);
    document.getElementById("res_customer_name")?.focus();
  });

  backBtn?.addEventListener("click", function () {
    showReservationStep(1);
    document.getElementById("res_party_size")?.focus();
  });

  window.openAddReservationModal = function (selectedDate) {
    const { modal, dateInput, dateDisplay, asideBtn } = getModalEls();
    if (!modal) {
      console.warn("addReservationModal not found in DOM.");
      return;
    }

    const activeDate =
      selectedDate ||
      asideBtn?.dataset.date ||
      document.getElementById("selectedDateLabel")?.dataset.date ||
      window.reservationCalendarData?.initialDate ||
      "";

    const today = window.reservationCalendarData?.today;
    if (activeDate && today && activeDate < today) return;

    if (dateInput && activeDate) {
      dateInput.value = activeDate;
    }
    if (dateDisplay && activeDate) {
      dateDisplay.textContent = formatReservationDate(activeDate);
    }
    window.refreshReservationTableAvailability?.();

    showReservationStep(1);
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    document.getElementById("res_party_size")?.focus();
  };

  window.closeAddReservationModal = function () {
    const { modal, form } = getModalEls();
    if (!modal) return;

    modal.classList.add("hidden");
    modal.classList.remove("flex");
    form?.reset();
    showReservationStep(1);
  };

  document.addEventListener("click", function (event) {
    const { modal, closeBtn, cancelBtn } = getModalEls();
    if (!modal) return;

    if (
      event.target === modal ||
      event.target.closest("#closeAddReservationModal") ||
      event.target.closest("#cancelAddReservationModal") ||
      event.target === closeBtn ||
      event.target === cancelBtn
    ) {
      window.closeAddReservationModal();
    }
  });

  document.addEventListener("keydown", function (event) {
    const { modal } = getModalEls();
    if (
      event.key === "Escape" &&
      modal &&
      !modal.classList.contains("hidden")
    ) {
      window.closeAddReservationModal();
    }
  });
})();

// TIME BOXES FOR ADD RESERVATION MODAL
(function () {
  const startInput = document.getElementById("res_start_time");
  const endInput = document.getElementById("res_end_time");
  const startDayOffsetInput = document.getElementById("res_start_day_offset");
  const endDayOffsetInput = document.getElementById("res_end_day_offset");
  const dateInput = document.getElementById("res_date");
  const startBoxes = Array.from(document.querySelectorAll("[data-start-time]"));
  const summary = document.getElementById("res_time_summary");
  const availableList = document.getElementById("res_available_time_list");
  const durationButtons = Array.from(
    document.querySelectorAll("[data-duration]"),
  );

  if (!startInput || !endInput || startBoxes.length === 0) return;

  let duration = 120;

  const ON = ["border-slate-900", "bg-slate-900", "text-white"];
  const OFF = ["border-slate-300", "bg-white", "text-slate-700"];
  const occupancyCache = new Map();

  function toMinutes(value) {
    const parts = String(value).split(":");
    return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
  }

  function toTimeText(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    const suffix = hours >= 12 ? "PM" : "AM";
    const displayHour = hours % 12 || 12;
    return `${displayHour}:${String(mins).padStart(2, "0")} ${suffix}`;
  }

  function getSelectedTableIds() {
    return Array.from(
      document.querySelectorAll('[data-table-item][aria-pressed="true"]'),
    ).map((item) => String(item.dataset.tableId));
  }

  function getOccupiedIntervals(date) {
    const editingId =
      document.getElementById("res_reservation_id")?.value || "";
    const cacheKey = `${date}|${editingId}`;
    if (occupancyCache.has(cacheKey)) return occupancyCache.get(cacheKey);

    const intervals = new Map();
    const calendarEvents = window.reservationCalendarData?.events || {};
    const events = [
      ...(calendarEvents[date] || []),
      ...(calendarEvents[shiftCalendarDate(date, 1)] || []),
    ];

    events.forEach((reservation) => {
      const status = String(reservation.status || "").toLowerCase();
      if (status === "cancelled" || status === "canceled") return;

      const existingStart = reservation.start_at
        ? new Date(String(reservation.start_at).replace(" ", "T"))
        : null;
      const existingEnd = reservation.end_at
        ? new Date(String(reservation.end_at).replace(" ", "T"))
        : null;

      if (
        !existingStart ||
        !existingEnd ||
        Number.isNaN(existingStart.getTime()) ||
        Number.isNaN(existingEnd.getTime())
      ) {
        return;
      }

      (reservation.table_ids || []).map(String).forEach((tableId) => {
        if (!intervals.has(tableId)) intervals.set(tableId, []);
        intervals.get(tableId).push({
          start: existingStart.getTime(),
          end: existingEnd.getTime(),
        });
      });
    });

    occupancyCache.set(cacheKey, intervals);
    return intervals;
  }

  function isStartAvailable(box) {
    const date = dateInput?.value || "";
    const startMinutes = toMinutes(box.dataset.startTime);
    const startDayOffset = Number(box.dataset.startDayOffset || 0);
    const elapsedStart = startDayOffset * 24 * 60 + startMinutes;
    if (elapsedStart < 660 || elapsedStart + duration > 1560) return false;

    const candidateDate = shiftCalendarDate(date, startDayOffset);
    const candidateStart = new Date(
      `${candidateDate}T${box.dataset.startTime}:00`,
    );
    const candidateEnd = new Date(
      candidateStart.getTime() + duration * 60 * 1000,
    );
    if (Number.isNaN(candidateStart.getTime())) return true;

    const selectedTableIds = getSelectedTableIds();
    if (selectedTableIds.length === 0) return true;

    const intervals = getOccupiedIntervals(date);

    return !selectedTableIds.some((tableId) =>
      (intervals.get(tableId) || []).some(
        (interval) =>
          candidateStart.getTime() < interval.end &&
          candidateEnd.getTime() > interval.start,
      ),
    );
  }

  function renderTimeBoxes() {
    const selected = startBoxes.find(
      (box) => box.getAttribute("aria-pressed") === "true",
    );

    startBoxes.forEach((box) => {
      const available = isStartAvailable(box);
      box.disabled = !available;
      box.classList.toggle("cursor-not-allowed", !available);
      box.classList.toggle("opacity-40", !available);
      box.classList.toggle("line-through", !available);
    });

    if (selected?.disabled) {
      const firstAvailable = startBoxes.find((box) => !box.disabled);
      startBoxes.forEach((box) => box.setAttribute("aria-pressed", "false"));
      if (firstAvailable) {
        firstAvailable.setAttribute("aria-pressed", "true");
        startInput.value = firstAvailable.dataset.startTime;
      }
    }

    const chosen = startBoxes.find(
      (box) => box.getAttribute("aria-pressed") === "true",
    );
    if (!chosen) {
      startInput.value = "";
      endInput.value = "";
      if (startDayOffsetInput) startDayOffsetInput.value = "0";
      if (endDayOffsetInput) endDayOffsetInput.value = "0";
      if (summary) summary.textContent = "";
      if (availableList) availableList.textContent = "No available time.";
      return;
    }

    const startMinutes = toMinutes(chosen.dataset.startTime);
    const startDayOffset = Number(chosen.dataset.startDayOffset || 0);
    const elapsedEnd = startDayOffset * 24 * 60 + startMinutes + duration;
    const endDayOffset = Math.floor(elapsedEnd / (24 * 60));
    const endMinutes = elapsedEnd % (24 * 60);
    const endValue = `${String(Math.floor(endMinutes / 60)).padStart(2, "0")}:${String(endMinutes % 60).padStart(2, "0")}`;
    if (startDayOffsetInput) {
      startDayOffsetInput.value = String(startDayOffset);
    }
    if (endDayOffsetInput) {
      endDayOffsetInput.value = String(endDayOffset);
    }
    endInput.value = endValue;
    startInput.value = chosen.dataset.startTime;

    if (summary) {
      const hours = duration / 60;
      const durationText =
        hours === 1
          ? "1 hr"
          : Number.isInteger(hours)
            ? `${hours} hrs`
            : `${hours.toFixed(1)} hrs`;
      const endLabel = `${toTimeText(endMinutes)}${endDayOffset > startDayOffset ? " (+1 day)" : ""}`;
      summary.textContent = `${toTimeText(startMinutes)} – ${endLabel} · ${durationText}`;
    }
    if (availableList) {
      const endLabel = `${toTimeText(endMinutes)}${endDayOffset > startDayOffset ? " (+1 day)" : ""}`;
      availableList.innerHTML = `<span class="font-semibold text-slate-900">${toTimeText(startMinutes)} – ${endLabel}</span><br><span class="text-xs">Available for the selected table(s)</span>`;
    }
  }

  function selectStart(box) {
    if (box.disabled) return;
    startBoxes.forEach((item) => item.setAttribute("aria-pressed", "false"));
    box.setAttribute("aria-pressed", "true");
    startBoxes.forEach((item) => {
      const selected = item.getAttribute("aria-pressed") === "true";
      item.classList.toggle("border-slate-900", selected);
      item.classList.toggle("bg-slate-900", selected);
      item.classList.toggle("text-white", selected);
      item.classList.toggle("border-slate-300", !selected);
      item.classList.toggle("bg-white", !selected);
      item.classList.toggle("text-slate-700", !selected);
    });
    renderTimeBoxes();
    window.refreshReservationTableAvailability?.();
  }

  startBoxes.forEach((box) =>
    box.addEventListener("click", function () {
      selectStart(box);
    }),
  );

  durationButtons.forEach((button) =>
    button.addEventListener("click", function () {
      duration = parseInt(button.dataset.duration, 10);
      durationButtons.forEach((item) => {
        const selected = item === button;
        item.setAttribute("aria-pressed", String(selected));
        ON.forEach((name) => item.classList.toggle(name, selected));
        OFF.forEach((name) => item.classList.toggle(name, !selected));
      });
      renderTimeBoxes();
      window.refreshReservationTableAvailability?.();
    }),
  );

  window.refreshReservationTimeAvailability = renderTimeBoxes;
  dateInput?.addEventListener("change", renderTimeBoxes);
  renderTimeBoxes();
})();

// LIVE TABLE AVAILABILITY AND MULTI-TABLE SELECTION
(function () {
  const partyInput = document.getElementById("res_party_size");
  const dateInput = document.getElementById("res_date");
  const startInput = document.getElementById("res_start_time");
  const endInput = document.getElementById("res_end_time");
  const startDayOffsetInput = document.getElementById("res_start_day_offset");
  const endDayOffsetInput = document.getElementById("res_end_day_offset");
  const tableItems = Array.from(document.querySelectorAll("[data-table-item]"));
  const hiddenContainer = document.getElementById("tableIdsHiddenContainer");
  const trigger = document.getElementById("tableSelectorTrigger");
  const panel = document.getElementById("tableSelectorPanel");
  const summary = document.getElementById("tableSelectorSummary");
  const chevron = document.getElementById("tableSelectorChevron");
  const chips = document.getElementById("selectedTableChips");
  const badge = document.getElementById("selectedSeatsBadge");
  const hint = document.getElementById("partySizeHint");
  const capacityError = document.getElementById("tableCapacityError");

  if (!partyInput || tableItems.length === 0) return;

  function parseLocalDateTime(date, time) {
    if (!date || !time) return null;
    const parsed = new Date(`${date}T${time}:00`);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }

  function getPartySize() {
    const partySize = parseInt(partyInput.value || "0", 10);
    return Number.isFinite(partySize) && partySize > 0 ? partySize : 0;
  }

  function getOccupiedTableIds() {
    const date = dateInput?.value || "";
    const candidateStart = parseLocalDateTime(
      shiftCalendarDate(date, Number(startDayOffsetInput?.value || 0)),
      startInput?.value,
    );
    const candidateEnd = parseLocalDateTime(
      shiftCalendarDate(date, Number(endDayOffsetInput?.value || 0)),
      endInput?.value,
    );
    if (!candidateStart || !candidateEnd) return new Set();

    const calendarEvents = window.reservationCalendarData?.events || {};
    const events = [
      ...(calendarEvents[date] || []),
      ...(calendarEvents[shiftCalendarDate(date, 1)] || []),
    ];
    const occupied = new Set();

    events.forEach((reservation) => {
      const status = String(reservation.status || "").toLowerCase();
      if (status === "cancelled" || status === "canceled") return;

      const existingStart = reservation.start_at
        ? new Date(String(reservation.start_at).replace(" ", "T"))
        : null;
      const existingEnd = reservation.end_at
        ? new Date(String(reservation.end_at).replace(" ", "T"))
        : null;

      if (
        existingStart &&
        existingEnd &&
        !Number.isNaN(existingStart.getTime()) &&
        !Number.isNaN(existingEnd.getTime()) &&
        candidateStart < existingEnd &&
        candidateEnd > existingStart
      ) {
        (reservation.table_ids || []).forEach((tableId) => {
          occupied.add(String(tableId));
        });
      }
    });

    return occupied;
  }

  function getSelectedItems() {
    return tableItems.filter(
      (item) => item.getAttribute("aria-pressed") === "true",
    );
  }

  function getSelectedSeats() {
    return getSelectedItems().reduce(
      (total, item) =>
        total + (parseInt(item.dataset.tableCapacity || "0", 10) || 0),
      0,
    );
  }

  function renderSelection() {
    const selected = getSelectedItems();
    const partySize = getPartySize();
    const seats = getSelectedSeats();

    if (hiddenContainer) {
      hiddenContainer.innerHTML = selected
        .map(
          (item) =>
            `<input type="hidden" name="table_ids[]" value="${item.dataset.tableId}">`,
        )
        .join("");
    }

    if (summary) {
      summary.textContent =
        selected.length === 0
          ? "-- Select Table(s) --"
          : selected.map((item) => item.dataset.tableLabel).join(", ");
      summary.classList.toggle("text-slate-500", selected.length === 0);
      summary.classList.toggle("text-slate-900", selected.length > 0);
    }

    if (badge) {
      badge.textContent = `${seats} / ${partySize || 0} seats`;
      badge.classList.toggle(
        "text-emerald-600",
        seats >= partySize && partySize > 0,
      );
      badge.classList.toggle(
        "text-slate-500",
        seats < partySize || partySize === 0,
      );
    }

    if (hint) {
      hint.textContent =
        partySize > 0
          ? `Select one or multiple tables to seat ${partySize} guests.`
          : "Enter the number of guests to continue.";
    }

    if (chips) {
      chips.innerHTML = selected
        .map(
          (item) =>
            `<span class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-700">${item.dataset.tableLabel}</span>`,
        )
        .join("");
      chips.classList.toggle("hidden", selected.length === 0);
      chips.classList.toggle("flex", selected.length > 0);
    }

    if (capacityError) {
      if (partySize > 0 && selected.length > 0 && seats < partySize) {
        capacityError.textContent = `Selected tables provide ${seats} seats. Select enough tables for ${partySize} guests.`;
        capacityError.classList.remove("hidden");
      } else if (partySize > 0 && selected.length === 0) {
        capacityError.textContent =
          "Please select at least one available table.";
        capacityError.classList.add("hidden");
      } else {
        capacityError.textContent = "";
        capacityError.classList.add("hidden");
      }
    }
  }

  function refreshAvailability() {
    window.refreshReservationTimeAvailability?.();
    const occupied = getOccupiedTableIds();

    tableItems.forEach((item) => {
      const isOccupied = occupied.has(String(item.dataset.tableId));
      item.hidden = isOccupied;
      item.disabled = isOccupied;
      item.setAttribute(
        "aria-pressed",
        isOccupied ? "false" : item.getAttribute("aria-pressed"),
      );
      if (isOccupied) {
        item.classList.add("hidden");
      } else {
        item.classList.remove("hidden");
      }
    });

    renderSelection();
  }

  window.refreshReservationTableAvailability = refreshAvailability;

  tableItems.forEach((item) => {
    item.addEventListener("click", function () {
      if (item.disabled) return;
      const isSelected = item.getAttribute("aria-pressed") === "true";
      item.setAttribute("aria-pressed", isSelected ? "false" : "true");
      item.dataset.selected = isSelected ? "false" : "true";

      const checkIcon = item.querySelector("[data-check-box] i");
      const checkBox = item.querySelector("[data-check-box]");
      if (checkIcon) checkIcon.classList.toggle("hidden", isSelected);
      if (checkBox) {
        checkBox.classList.toggle("border-slate-900", !isSelected);
        checkBox.classList.toggle("bg-slate-900", !isSelected);
        checkBox.classList.toggle("border-slate-300", isSelected);
        checkBox.classList.toggle("bg-white", isSelected);
      }

      refreshAvailability();
    });
  });

  partyInput.addEventListener("input", renderSelection);
  startInput?.addEventListener("change", refreshAvailability);
  endInput?.addEventListener("change", refreshAvailability);
  dateInput?.addEventListener("change", refreshAvailability);

  trigger?.addEventListener("click", function () {
    if (!panel) return;
    const isOpen = !panel.classList.contains("hidden");
    panel.classList.toggle("hidden", isOpen);
    trigger.setAttribute("aria-expanded", String(!isOpen));
    chevron?.classList.toggle("rotate-180", !isOpen);
  });

  document
    .getElementById("closeTablePanelBtn")
    ?.addEventListener("click", function () {
      panel?.classList.add("hidden");
      trigger?.setAttribute("aria-expanded", "false");
      chevron?.classList.remove("rotate-180");
    });

  document
    .getElementById("clearTablesBtn")
    ?.addEventListener("click", function () {
      tableItems.forEach((item) => {
        item.setAttribute("aria-pressed", "false");
        item.dataset.selected = "false";
        item.querySelector("[data-check-box] i")?.classList.add("hidden");
        const checkBox = item.querySelector("[data-check-box]");
        if (checkBox) {
          checkBox.classList.add("border-slate-300", "bg-white");
          checkBox.classList.remove("border-slate-900", "bg-slate-900");
        }
      });
      renderSelection();
      window.refreshReservationTimeAvailability?.();
    });

  document.addEventListener("click", function (event) {
    if (
      panel &&
      trigger &&
      !panel.contains(event.target) &&
      !trigger.contains(event.target)
    ) {
      panel.classList.add("hidden");
      trigger.setAttribute("aria-expanded", "false");
      chevron?.classList.remove("rotate-180");
    }
  });

  refreshAvailability();
})();

// EDIT RESERVATION MODAL
(function () {
  const editModal = document.getElementById("editReservationModal");
  const editForm = document.getElementById("editReservationForm");
  const closeBtn = document.getElementById("closeEditReservationModal");
  const cancelBtn = document.getElementById("cancelEditReservationModal");
  const codeLabel = document.getElementById("editReservationCodeLabel");
  const tableSelect = document.getElementById("edit_table_ids");
  const partyInput = document.getElementById("edit_party_size");
  const capacityNote = document.getElementById("editTableCapacityNote");

  function extractHHMM(val) {
    if (!val) return "";
    // Supports "2026-11-24 18:00:00", "2026-11-24T18:00:00", "18:00:00", or "6:00 PM"
    const timeMatch = String(val).match(
      /(\d{1,2}):(\d{2})(?::\d{2})?\s*(AM|PM)?/i,
    );
    if (!timeMatch) return "";
    let hours = parseInt(timeMatch[1], 10);
    const mins = timeMatch[2];
    const mer = (timeMatch[3] || "").toUpperCase();
    if (mer === "PM" && hours < 12) hours += 12;
    if (mer === "AM" && hours === 12) hours = 0;
    return `${String(hours).padStart(2, "0")}:${mins}`;
  }

  function extractDate(event) {
    if (
      event?.reservation_date &&
      /^\d{4}-\d{2}-\d{2}$/.test(event.reservation_date)
    ) {
      return event.reservation_date;
    }
    const raw = event?.start_at || event?.start || "";
    const m = String(raw).match(/(\d{4}-\d{2}-\d{2})/);
    if (m) return m[1];
    const activeDateEl = document.getElementById("res_date");
    return activeDateEl?.value || "";
  }

  function checkEditCapacity() {
    if (!tableSelect || !partyInput || !capacityNote) return true;
    const guests = Math.max(1, parseInt(partyInput.value || "1", 10));
    const selectedOptions = Array.from(tableSelect.selectedOptions);
    const totalSeats = selectedOptions.reduce(
      (sum, opt) => sum + parseInt(opt.dataset.capacity || "0", 10),
      0,
    );

    if (selectedOptions.length === 0) {
      capacityNote.textContent = "Select at least one table.";
      capacityNote.className = "mt-1 text-xs font-medium text-red-600";
      return false;
    }

    if (totalSeats < guests) {
      capacityNote.textContent = `Selected capacity (${totalSeats} seats) is less than ${guests} guests.`;
      capacityNote.className = "mt-1 text-xs font-medium text-red-600";
      return false;
    }

    capacityNote.textContent = `Selected capacity: ${totalSeats} seats for ${guests} guests.`;
    capacityNote.className = "mt-1 text-xs text-emerald-700";
    return true;
  }

  window.openEditReservationModal = function (event, reservationId) {
    if (!editModal || !editForm || !event) return;

    const id = reservationId || event.reservation_id || event.id || "";
    document.getElementById("edit_reservation_id").value = String(id);

    const returnDateInput = document.getElementById("edit_return_date");
    if (returnDateInput) {
      returnDateInput.value =
        document.getElementById("selectedDateLabel")?.dataset.date ||
        extractDate(event);
    }

    if (codeLabel) {
      codeLabel.textContent = event.reservation_code
        ? `Code: ${event.reservation_code}`
        : `Reservation #${id}`;
    }

    document.getElementById("edit_res_date").value = extractDate(event);
    document.getElementById("edit_start_time").value = extractHHMM(
      event.start_at || event.start,
    );
    document.getElementById("edit_end_time").value = extractHHMM(
      event.end_at || event.end,
    );
    document.getElementById("edit_party_size").value = String(
      event.party_size ?? event.guests ?? 2,
    );
    document.getElementById("edit_customer_name").value =
      event.customer_name || "";
    document.getElementById("edit_phone_number").value =
      event.phone || event.phone_number || "";
    document.getElementById("edit_occasion").value = event.occasion || "";
    document.getElementById("edit_source").value = event.source || "Walk-in";
    document.getElementById("edit_deposit_amount").value = String(
      event.deposit_amount ?? "0.00",
    );
    document.getElementById("edit_deposit_paid").checked =
      event.deposit_paid === 1 ||
      event.deposit_paid === true ||
      String(event.deposit_paid) === "1";
    document.getElementById("edit_special_requests").value =
      event.special_requests || "";
    document.getElementById("edit_internal_notes").value =
      event.internal_notes || "";

    const statusSelect = document.getElementById("edit_status_id");
    if (statusSelect && event.status_id) {
      statusSelect.value = String(event.status_id);
    }

    // Pre-select assigned tables
    const assignedIds = new Set(
      Array.isArray(event.table_ids)
        ? event.table_ids.map(String)
        : Array.isArray(event.tables)
          ? event.tables.map((t) => String(t.table_id ?? t.id ?? t))
          : [],
    );

    if (tableSelect) {
      Array.from(tableSelect.options).forEach((opt) => {
        opt.selected = assignedIds.has(String(opt.value));
      });
    }

    checkEditCapacity();
    editModal.classList.remove("hidden");
    editModal.classList.add("flex");
  };

  function closeEditModal() {
    if (!editModal) return;
    editModal.classList.add("hidden");
    editModal.classList.remove("flex");
  }

  closeBtn?.addEventListener("click", closeEditModal);
  cancelBtn?.addEventListener("click", closeEditModal);
  tableSelect?.addEventListener("change", checkEditCapacity);
  partyInput?.addEventListener("input", checkEditCapacity);

  editForm?.addEventListener("submit", (e) => {
    if (!checkEditCapacity()) {
      e.preventDefault();
    }
  });
})();

// DELETE RESERVATION MODAL
(function () {
  const deleteModal = document.getElementById("deleteReservationModal");
  const closeBtn = document.getElementById("closeDeleteReservationModal");
  const cancelBtn = document.getElementById("cancelDeleteReservationModal");

  const idInput = document.getElementById("delete_reservation_id");
  const dateInput = document.getElementById("delete_reservation_date");
  const customerEl = document.getElementById("deleteCustomerName");
  const codeEl = document.getElementById("deleteReservationCode");
  const scheduleEl = document.getElementById("deleteReservationSchedule");
  const guestsEl = document.getElementById("deleteReservationGuests");

  window.openDeleteReservationModal = function (event, reservationId) {
    if (!deleteModal) return;

    const id = reservationId || event?.reservation_id || event?.id || "";
    if (!id) return;

    const rawStart = event?.start_at || event?.start || "";
    const dateMatch = String(rawStart).match(/(\d{4}-\d{2}-\d{2})/);
    const activeDate =
      event?.reservation_date ||
      (dateMatch ? dateMatch[1] : "") ||
      document.getElementById("res_date")?.value ||
      "";

    if (idInput) idInput.value = String(id);
    if (dateInput) dateInput.value = activeDate;

    if (customerEl) {
      customerEl.textContent = event?.customer_name || "Unknown customer";
    }
    if (codeEl) {
      codeEl.textContent = event?.reservation_code || `#${id}`;
    }
    if (scheduleEl) {
      const startText = event?.start || event?.start_at || "";
      const endText = event?.end || event?.end_at || "";
      scheduleEl.textContent =
        activeDate && startText && endText
          ? `${activeDate} · ${startText} - ${endText}`
          : startText || activeDate || "-";
    }
    if (guestsEl) {
      const guests = event?.guests ?? event?.party_size;
      guestsEl.textContent = guests ? `${guests} pax` : "-";
    }

    deleteModal.classList.remove("hidden");
    deleteModal.classList.add("flex");
  };

  function closeDeleteModal() {
    if (!deleteModal) return;
    deleteModal.classList.add("hidden");
    deleteModal.classList.remove("flex");
  }

  closeBtn?.addEventListener("click", closeDeleteModal);
  cancelBtn?.addEventListener("click", closeDeleteModal);

  deleteModal?.addEventListener("click", (e) => {
    if (e.target === deleteModal) {
      closeDeleteModal();
    }
  });
})();
