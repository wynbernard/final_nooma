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
userModal.addEventListener("click", function (event) {
  if (event.target === userModal) {
    closeUserModal();
  }
});

// ==========================================
// EDIT USER MODAL
// ==========================================

const editUserModal = document.getElementById("editUserModal");
const editUserForm = document.getElementById("editUserForm");
const editUserFormMessage = document.getElementById("editUserFormMessage");
const updateUserBtn = document.getElementById("updateUserBtn");

// Open Edit Modal and populate fields
function openEditUserModal(user) {
  editUserForm.reset();

  editUserForm.querySelector('[name="action"]').value = "update";

  document.getElementById("edit_user_id").value = user.user_id;
  document.getElementById("edit_full_name").value = user.full_name;
  document.getElementById("edit_username").value = user.username;
  document.getElementById("edit_role").value = user.role;

  document.getElementById("edit_is_active").checked =
    Number(user.is_active) === 1;

  // Password is optional when editing
  document.getElementById("edit_password").required = false;
  document.getElementById("edit_password").value = "";

  editUserFormMessage.textContent = "";
  editUserFormMessage.classList.add("hidden");

  updateUserBtn.disabled = false;
  updateUserBtn.textContent = "Update User";

  editUserModal.classList.remove("hidden");
  editUserModal.classList.add("flex");

  document.getElementById("edit_full_name").focus();
}

// Close Edit Modal
function closeEditUserModal() {
  editUserModal.classList.add("hidden");
  editUserModal.classList.remove("flex");

  editUserFormMessage.textContent = "";
  editUserFormMessage.classList.add("hidden");
}

// Close Edit Modal when clicking outside
editUserModal.addEventListener("click", function (event) {
  if (event.target === editUserModal) {
    closeEditUserModal();
  }
});

// ==========================================
// ESCAPE KEY
// ==========================================

document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    if (!userModal.classList.contains("hidden")) {
      closeUserModal();
    }

    if (!editUserModal.classList.contains("hidden")) {
      closeEditUserModal();
    }
  }
});

// ==========================================
// REUSABLE AJAX FORM SUBMISSION
// ==========================================

async function submitUserForm(form, button, messageElement, actionLabel) {
  if (!form.reportValidity()) {
    return;
  }

  button.disabled = true;
  button.textContent = actionLabel === "create" ? "Creating..." : "Updating...";

  messageElement.textContent = "";
  messageElement.classList.add("hidden");

  try {
    const response = await fetch(form.action, {
      method: "POST",
      body: new FormData(form),
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(result.message || "Unable to save user.");
    }

    showNotification(result.message || "User saved successfully.", "success");

    // Close the appropriate modal
    if (actionLabel === "create") {
      closeUserModal();
    } else {
      closeEditUserModal();
    }

    // Refresh users table and statistics
    window.setTimeout(() => window.location.reload(), 1200);
  } catch (error) {
    const message = error.message || "Something went wrong. Please try again.";
    messageElement.textContent = message;

    messageElement.classList.remove("hidden");
    showNotification(message, "error");
  } finally {
    button.disabled = false;
    button.textContent =
      actionLabel === "create" ? "Create User" : "Update User";
  }
}

// ==========================================
// CREATE FORM SUBMISSION
// ==========================================

userForm.addEventListener("submit", function (event) {
  event.preventDefault();

  submitUserForm(userForm, saveUserBtn, userFormMessage, "create");
});

// ==========================================
// EDIT FORM SUBMISSION
// ==========================================

editUserForm.addEventListener("submit", function (event) {
  event.preventDefault();

  submitUserForm(editUserForm, updateUserBtn, editUserFormMessage, "update");
});
