"use strict";

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

    // Update selected date label
    if (selectedDateLabel) {
      selectedDateLabel.dataset.date = date;
      selectedDateLabel.textContent = formatDate(date);
    }

    // No reservations
    if (events.length === 0) {
      dailyList.innerHTML = `
                <div class="py-10 text-center">

                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">

                        <i class="fa-regular fa-calendar-xmark text-xl"></i>

                    </div>

                    <p class="mt-3 font-semibold text-slate-700">
                        No reservations
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        There are no bookings on this date.
                    </p>

                    <a href="create.php"
                       class="mt-4 inline-flex items-center gap-2 rounded-lg bg-navy-800 px-4 py-2.5 text-sm font-semibold text-white hover:bg-navy-900">

                        <i class="fa-solid fa-plus"></i>
                        Add Reservation

                    </a>

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

        return `

                <button
                    type="button"
                    data-reservation-index="${index}"
                    class="daily-reservation-card block w-full rounded-xl border border-slate-200 p-4 text-left transition hover:border-blue-300 hover:bg-blue-50/50">

                    <div class="flex items-start justify-between gap-2">

                        <div>

                            <p class="font-bold text-slate-900">
                                ${escapeHTML(event.customer_name || "Unknown customer")}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">

                                <i class="fa-regular fa-clock mr-1"></i>

                                ${escapeHTML(event.start)} -
                                ${escapeHTML(event.end)}

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

                    <p class="mt-3 text-xs font-semibold text-blue-700">

                        View details

                        <i class="fa-solid fa-arrow-right ml-1"></i>

                    </p>

                </button>

            `;
      })
      .join("");

    // Attach click events
    dailyList.querySelectorAll(".daily-reservation-card").forEach((card) => {
      card.addEventListener("click", function () {
        const index = Number(this.dataset.reservationIndex);

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
