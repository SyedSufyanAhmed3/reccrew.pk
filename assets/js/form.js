/* ===================================================================
   RAC Crew — form.js
   Client-side validation + AJAX submission for the Contact and Quote
   forms. Server-side (php/contact-handler.php, php/quote-handler.php)
   ALWAYS re-validates — this is a UX layer only, never a security one.
   =================================================================== */

(function () {
  "use strict";

  var PHONE_RE = /^[+]?[0-9\s-]{7,15}$/;
  var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function showFieldError(field, message) {
    var wrap = field.closest(".form-field");
    if (!wrap) return;
    wrap.classList.add("has-error");
    var msgEl = wrap.querySelector(".error-msg");
    if (msgEl) msgEl.textContent = message;
  }

  function clearFieldError(field) {
    var wrap = field.closest(".form-field");
    if (!wrap) return;
    wrap.classList.remove("has-error");
    var msgEl = wrap.querySelector(".error-msg");
    if (msgEl) msgEl.textContent = "";
  }

  function validateForm(form) {
    var valid = true;
    var fields = form.querySelectorAll("[data-validate]");

    fields.forEach(function (field) {
      clearFieldError(field);
      var rule = field.getAttribute("data-validate");
      var value = field.value.trim();

      if (rule.indexOf("required") !== -1 && !value) {
        showFieldError(field, "This field is required.");
        valid = false;
        return;
      }
      if (!value) return; // optional & empty, skip further checks

      if (rule.indexOf("email") !== -1 && !EMAIL_RE.test(value)) {
        showFieldError(field, "Enter a valid email address.");
        valid = false;
      }
      if (rule.indexOf("phone") !== -1 && !PHONE_RE.test(value)) {
        showFieldError(field, "Enter a valid phone number.");
        valid = false;
      }
      if (rule.indexOf("minlen10") !== -1 && value.length < 10) {
        showFieldError(field, "Please provide a bit more detail (min. 10 characters).");
        valid = false;
      }
    });

    return valid;
  }

  function setStatus(statusEl, type, message) {
    statusEl.textContent = message;
    statusEl.className = "form-status show " + type;
  }

  var lastFocusedBeforeModal = null;

  function openModal(modal) {
    lastFocusedBeforeModal = document.activeElement;
    modal.classList.add("open");
    modal.setAttribute("aria-hidden", "false");
    var focusTarget = modal.querySelector('[data-modal-close], .btn, button, a[href]');
    if (focusTarget) focusTarget.focus();
  }
  function closeModal(modal) {
    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
    if (lastFocusedBeforeModal && typeof lastFocusedBeforeModal.focus === "function") {
      lastFocusedBeforeModal.focus();
    }
  }

  function wireModalClosers() {
    document.querySelectorAll("[data-modal-close]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var modal = btn.closest(".modal-backdrop");
        if (modal) closeModal(modal);
      });
    });
    document.querySelectorAll(".modal-backdrop").forEach(function (modal) {
      modal.addEventListener("click", function (e) {
        if (e.target === modal) closeModal(modal);
      });
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") {
        document.querySelectorAll(".modal-backdrop.open").forEach(closeModal);
      }
    });
  }

  function initForm(formId, endpoint, successModalId, errorModalId) {
    var form = document.getElementById(formId);
    if (!form) return;
    var statusEl = form.querySelector(".form-status");
    var submitBtn = form.querySelector('[type="submit"]');
    var successModal = document.getElementById(successModalId);
    var errorModal = document.getElementById(errorModalId);

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      // Honeypot: if filled, silently drop (bot).
      var honeypot = form.querySelector('input[name="website"]');
      if (honeypot && honeypot.value) {
        return;
      }

      if (!validateForm(form)) {
        if (statusEl) setStatus(statusEl, "error", "Please fix the highlighted fields and try again.");
        return;
      }

      var formData = new FormData(form);
      submitBtn.disabled = true;
      var originalLabel = submitBtn.textContent;
      submitBtn.textContent = "Sending...";
      if (statusEl) statusEl.className = "form-status";

      fetch(endpoint, {
        method: "POST",
        body: formData,
        headers: { "X-Requested-With": "XMLHttpRequest" }
      })
        .then(function (res) { return res.json().catch(function () { return { success: false }; }); })
        .then(function (data) {
          if (data && data.success) {
            form.reset();
            if (successModal) openModal(successModal);
            if (statusEl) setStatus(statusEl, "success", data.message || "Request submitted successfully.");
          } else {
            if (errorModal) openModal(errorModal);
            if (statusEl) {
              setStatus(statusEl, "error", (data && data.message) ||
                "We couldn't send your request right now. Please call or WhatsApp us directly.");
            }
          }
        })
        .catch(function () {
          if (errorModal) openModal(errorModal);
          if (statusEl) {
            setStatus(statusEl, "error",
              "A connection error occurred. Please call or WhatsApp us directly.");
          }
        })
        .finally(function () {
          submitBtn.disabled = false;
          submitBtn.textContent = originalLabel;
        });
    });

    // Clear error state as the user types/fixes a field
    form.querySelectorAll("[data-validate]").forEach(function (field) {
      field.addEventListener("input", function () { clearFieldError(field); });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    wireModalClosers();
    initForm("contactForm", "php/contact-handler.php", "successModal", "errorModal");
    initForm("quoteForm", "php/quote-handler.php", "successModal", "errorModal");
  });
})();
