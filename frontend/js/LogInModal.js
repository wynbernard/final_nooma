(function () {
  var dlg = document.getElementById("login-modal");
  if (!dlg || typeof dlg.showModal !== "function") return;
  var form = document.getElementById("login-form"),
    msg = document.getElementById("login-msg");
  var pw = document.getElementById("login-password"),
    toggle = document.getElementById("toggle-pw");
  var html = document.documentElement;

  document.querySelectorAll("[data-open-login]").forEach(function (b) {
    b.addEventListener("click", function () {
      dlg.showModal();
      html.classList.add("overflow-hidden");
    document.getElementById("login-username").focus();
    });
  });
  dlg.querySelectorAll("[data-close-login]").forEach(function (b) {
    b.addEventListener("click", function () {
      dlg.close();
    });
  });
  dlg.addEventListener("click", function (e) {
    if (e.target === dlg) dlg.close();
  }); // backdrop click
  dlg.addEventListener("close", function () {
    html.classList.remove("overflow-hidden");
    msg.classList.add("hidden");
  });

  toggle.addEventListener("click", function () {
    var show = pw.type === "password";
    pw.type = show ? "text" : "password";
    toggle.textContent = show ? "Hide" : "Show";
    toggle.setAttribute("aria-pressed", String(show));
  });

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    try {
      var response = await fetch(form.action, {
        method: "POST",
        body: new FormData(form),
        headers: {
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
      });
      var data = await response.json();

      if (!response.ok || !data.success) {
        showNotification(data.message || "Unable to log in. Please try again.", "error");
        return;
      }

      showNotification(data.message || "Logged in successfully.", "success");
      setTimeout(function () {
        window.location.href = data.redirect || window.location.href;
      }, 1200);
    } catch (err) {
      showNotification("A network error occurred. Please try again.", "error");
    }
  });
})();
