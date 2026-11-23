const userModal = document.getElementById("userModal");
const userForm = document.getElementById("userForm");
const userFormMessage = document.getElementById("userFormMessage");
const saveUserBtn = document.getElementById("saveUserBtn");

// Open modal
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

// Close modal
function closeUserModal() {
  userModal.classList.add("hidden");
  userModal.classList.remove("flex");

  userFormMessage.textContent = "";
  userFormMessage.classList.add("hidden");
}

// Close when clicking outside
userModal.addEventListener("click", function (event) {
  if (event.target === userModal) {
    closeUserModal();
  }
});

// Close with Escape key
document.addEventListener("keydown", function (event) {
  if (event.key === "Escape" && !userModal.classList.contains("hidden")) {
    closeUserModal();
  }
});

// Submit form using AJAX
userForm.addEventListener("submit", async function (event) {
  event.preventDefault();

  if (!userForm.reportValidity()) {
    return;
  }

  saveUserBtn.disabled = true;
  saveUserBtn.textContent = "Creating...";

  userFormMessage.classList.add("hidden");

  try {
    const response = await fetch(userForm.action, {
      method: "POST",
      body: new FormData(userForm),
      credentials: "same-origin",
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(result.message || "Unable to create user.");
    }

    closeUserModal();

    // Refresh users table and statistics
    window.location.reload();
  } catch (error) {
    userFormMessage.textContent =
      error.message || "Something went wrong. Please try again.";

    userFormMessage.classList.remove("hidden");
  } finally {
    saveUserBtn.disabled = false;
    saveUserBtn.textContent = "Create User";
  }
});
