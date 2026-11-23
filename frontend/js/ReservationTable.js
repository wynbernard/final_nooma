(function () {
  const modal = document.getElementById("addTableModal");
  const form = document.getElementById("addTableForm");
  const closeBtn = document.getElementById("closeAddTableModalBtn");
  const cancelBtn = document.getElementById("cancelAddTableModalBtn");

  window.openAddTableModal = function () {
    if (!modal) return;
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    document.getElementById("table_number")?.focus();
  };

  window.closeAddTableModal = function () {
    if (!modal) return;
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    form?.reset();
  };

  closeBtn?.addEventListener("click", closeAddTableModal);
  cancelBtn?.addEventListener("click", closeAddTableModal);

  modal?.addEventListener("click", function (event) {
    if (event.target === modal) {
      closeAddTableModal();
    }
  });

  document.addEventListener("keydown", function (event) {
    if (
      event.key === "Escape" &&
      modal &&
      !modal.classList.contains("hidden")
    ) {
      closeAddTableModal();
    }
  });
})();

(function () {
  const modal = document.getElementById("editTableModal");
  const form = document.getElementById("editTableForm");
  const closeBtn = document.getElementById("closeEditTableModalBtn");
  const cancelBtn = document.getElementById("cancelEditTableModalBtn");

  window.openEditTableModal = function (table) {
    if (!modal || !table) return;

    document.getElementById("edit_table_id").value = table.table_id ?? "";
    document.getElementById("edit_area_id").value = table.area_id ?? "";
    document.getElementById("edit_table_number").value =
      table.table_number ?? "";
    document.getElementById("edit_table_name").value = table.table_name ?? "";
    document.getElementById("edit_capacity").value = table.capacity ?? 4;
    document.getElementById("edit_shape").value = String(
      table.shape || "rectangle",
    ).toLowerCase();
    document.getElementById("edit_x_position").value = table.x_position ?? 0;
    document.getElementById("edit_y_position").value = table.y_position ?? 0;
    document.getElementById("edit_width").value = table.width ?? 100;
    document.getElementById("edit_height").value = table.height ?? 100;
    document.getElementById("edit_rotation").value = table.rotation ?? 0;
    document.getElementById("edit_is_active").value = String(
      Number(table.is_active) === 1 ? 1 : 0,
    );
    document.getElementById("edit_notes").value = table.notes ?? "";

    modal.classList.remove("hidden");
    modal.classList.add("flex");
    document.getElementById("edit_table_number")?.focus();
  };

  window.closeEditTableModal = function () {
    if (!modal) return;
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    form?.reset();
  };

  closeBtn?.addEventListener("click", closeEditTableModal);
  cancelBtn?.addEventListener("click", closeEditTableModal);

  modal?.addEventListener("click", function (event) {
    if (event.target === modal) {
      closeEditTableModal();
    }
  });

  document.addEventListener("keydown", function (event) {
    if (
      event.key === "Escape" &&
      modal &&
      !modal.classList.contains("hidden")
    ) {
      closeEditTableModal();
    }
  });
})();

(function () {
  const modal = document.getElementById("deleteTableModal");
  const form = document.getElementById("deleteTableForm");
  const closeBtn = document.getElementById("closeDeleteTableModalBtn");
  const cancelBtn = document.getElementById("cancelDeleteTableModalBtn");
  const idInput = document.getElementById("delete_table_id");
  const labelEl = document.getElementById("delete_table_label");

  window.openDeleteTableModal = function (table) {
    if (!modal || !table) return;

    if (idInput) {
      idInput.value = table.table_id ?? "";
    }

    if (labelEl) {
      const numberText = table.table_number
        ? `Table ${table.table_number}`
        : `#${table.table_id}`;
      const nameText = table.table_name ? ` (${table.table_name})` : "";
      labelEl.textContent = `${numberText}${nameText}`;
    }

    modal.classList.remove("hidden");
    modal.classList.add("flex");
    cancelBtn?.focus();
  };

  window.closeDeleteTableModal = function () {
    if (!modal) return;
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    form?.reset();
  };

  closeBtn?.addEventListener("click", closeDeleteTableModal);
  cancelBtn?.addEventListener("click", closeDeleteTableModal);

  modal?.addEventListener("click", function (event) {
    if (event.target === modal) {
      closeDeleteTableModal();
    }
  });

  document.addEventListener("keydown", function (event) {
    if (
      event.key === "Escape" &&
      modal &&
      !modal.classList.contains("hidden")
    ) {
      closeDeleteTableModal();
    }
  });
})();
