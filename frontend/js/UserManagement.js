// ==========================================
// CREATE USER MODAL
// ==========================================

const userModal = document.getElementById("userModal");
const userForm = document.getElementById("userForm");
const userFormMessage = document.getElementById("userFormMessage");
const saveUserBtn = document.getElementById("saveUserBtn");

// Open Create Modal
function openUserModal() {
  userForm.reset();

  userForm.querySelector('[name="action"]').value = "create";
  userForm.querySelector('[name="password"]').required = true;

  userFormMessage.textContent = "";
  userFormMessage.classList.add("hidden");

  saveUserBtn.disabled = false;
  saveUserBtn.textContent = "Create User";

  userModal.classList.remove("hidden");
  userModal.classList.add("flex");

  document.getElementById("full_name").focus();
}

// Close Create Modal
function closeUserModal() {
  userModal.classList.add("hidden");
  userModal.classList.remove("flex");

  userFormMessage.textContent = "";
  userFormMessage.classList.add("hidden");
}

// Close Create Modal when clicking outside
userModal?.addEventListener("click", function (event) {
  if (event.target === userModal) {
    closeUserModal();
  }
});

// ADD MODAL
userForm?.addEventListener("submit", async (e) => {
  e.preventDefault();

  const msgBox = document.getElementById("userFormMessage");
  const submitBtn = document.getElementById("saveUserBtn");
  const formData = new FormData(userForm);
  const endpoint =
    userForm.getAttribute("action") ||
    "../../backend/UserManagement/UserManagementCreate.php";

  if (submitBtn) submitBtn.disabled = true;
  if (msgBox) {
    msgBox.classList.add("hidden");
    msgBox.textContent = "";
  }

  try {
    const response = await fetch(endpoint, {
      method: "POST",
      body: formData,
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
      },
    });

    const rawText = await response.text();
    let data;

    try {
      data = JSON.parse(rawText);
    } catch {
      throw new Error(
        `Server returned non-JSON (HTTP ${response.status}). Check endpoint path or session.`,
      );
    }

    if (!response.ok || !data.success) {
      throw new Error(data.message || "Failed to create user.");
    }

    // Success: close modal, show notification, and refresh list/page
    if (typeof closeUserModal === "function") closeUserModal();
    window.location.reload();
  } catch (err) {
    if (msgBox) {
      msgBox.textContent = err.message || "Error creating user.";
      msgBox.classList.remove("hidden");
    }
  } finally {
    if (submitBtn) submitBtn.disabled = false;
  }
});

// ==========================================
// EDIT USER MODAL
// ==========================================

const editUserModal = document.getElementById("editUserModal");
const editUserForm = document.getElementById("editUserForm");
const editUserFormMessage = document.getElementById("editUserFormMessage");
const updateUserBtn = document.getElementById("updateUserBtn");

// ==========================================
// DELETE USER MODAL
// ==========================================

const deleteUserModal = document.getElementById("deleteUserModal");
const deleteUserForm = document.getElementById("deleteUserForm");
const deleteUserFormMessage = document.getElementById("deleteUserFormMessage");
const deleteUserBtn = document.getElementById("deleteUserBtn");

function openDeleteUserModal(userId, fullName) {
  if (!deleteUserModal || !deleteUserForm) return;

  deleteUserForm.reset();
  document.getElementById("delete_user_id").value = userId;
  document.getElementById("deleteUserName").textContent =
    fullName || "this user";

  if (deleteUserFormMessage) {
    deleteUserFormMessage.textContent = "";
    deleteUserFormMessage.classList.add("hidden");
  }
  if (deleteUserBtn) deleteUserBtn.disabled = false;

  deleteUserModal.classList.remove("hidden");
  deleteUserModal.classList.add("flex");
  deleteUserBtn?.focus();
}

function closeDeleteUserModal() {
  if (!deleteUserModal) return;

  deleteUserModal.classList.add("hidden");
  deleteUserModal.classList.remove("flex");
  if (deleteUserFormMessage) {
    deleteUserFormMessage.textContent = "";
    deleteUserFormMessage.classList.add("hidden");
  }
}

deleteUserModal?.addEventListener("click", function (event) {
  if (event.target === deleteUserModal) closeDeleteUserModal();
});

deleteUserForm?.addEventListener("submit", async (event) => {
  event.preventDefault();
  if (deleteUserBtn) deleteUserBtn.disabled = true;
  if (deleteUserFormMessage) {
    deleteUserFormMessage.textContent = "";
    deleteUserFormMessage.classList.add("hidden");
  }

  try {
    const endpoint = deleteUserForm.getAttribute("action");
    if (!endpoint) {
      throw new Error("Delete endpoint is not configured.");
    }

    const response = await fetch(endpoint, {
      method: "POST",
      body: new FormData(deleteUserForm),
      credentials: "same-origin",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
      },
    });

    const rawText = await response.text();
    let data;
    try {
      data = JSON.parse(rawText);
    } catch {
      throw new Error(
        `Server returned non-JSON (HTTP ${response.status}). Check endpoint path or session.`,
      );
    }

    if (!response.ok || !data.success) {
      throw new Error(data.message || "Failed to delete user.");
    }

    closeDeleteUserModal();
    window.location.reload();
  } catch (error) {
    if (deleteUserFormMessage) {
      deleteUserFormMessage.textContent =
        error.message || "Error deleting user.";
      deleteUserFormMessage.classList.remove("hidden");
    }
  } finally {
    if (deleteUserBtn) deleteUserBtn.disabled = false;
  }
});

function openEditUserModal(user) {
  if (!editUserModal || !editUserForm || !user) return;

  editUserForm.reset();
  document.getElementById("edit_user_id").value = user.user_id;
  document.getElementById("edit_full_name").value = user.full_name || "";
  document.getElementById("edit_username").value = user.username || "";
  document.getElementById("edit_password").value = "";
  const role = (user.role || "employee").toLowerCase();
  document.getElementById("edit_role").value =
    role === "staff" ? "employee" : role;
  document.getElementById("edit_is_active").checked =
    Number(user.is_active) === 1;

  if (editUserFormMessage) {
    editUserFormMessage.textContent = "";
    editUserFormMessage.classList.add("hidden");
  }
  if (updateUserBtn) {
    updateUserBtn.disabled = false;
  }

  editUserModal.classList.remove("hidden");
  editUserModal.classList.add("flex");
  document.getElementById("edit_full_name").focus();
}

function closeEditUserModal() {
  if (!editUserModal) return;
  editUserModal.classList.add("hidden");
  editUserModal.classList.remove("flex");

  if (editUserFormMessage) {
    editUserFormMessage.textContent = "";
    editUserFormMessage.classList.add("hidden");
  }
}

editUserModal?.addEventListener("click", function (event) {
  if (event.target === editUserModal) {
    closeEditUserModal();
  }
});

document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    if (!userModal?.classList.contains("hidden")) closeUserModal();
    if (!editUserModal?.classList.contains("hidden")) closeEditUserModal();
    if (!deleteUserModal?.classList.contains("hidden")) closeDeleteUserModal();
  }
});

// ==========================================
// SHARED MODAL HELPERS
// ==========================================

function togglePasswordVisibility(inputId, button) {
  const input = document.getElementById(inputId);
  const icon = button?.querySelector("i");
  if (!input) return;

  input.type = input.type === "password" ? "text" : "password";
  if (icon) {
    icon.classList.toggle("fa-eye");
    icon.classList.toggle("fa-eye-slash");
  }
  button?.setAttribute(
    "aria-label",
    input.type === "password" ? "Show password" : "Hide password",
  );
}

window.openEditUserModal = openEditUserModal;
window.closeEditUserModal = closeEditUserModal;
window.openDeleteUserModal = openDeleteUserModal;
window.closeDeleteUserModal = closeDeleteUserModal;
window.togglePasswordVisibility = togglePasswordVisibility;
