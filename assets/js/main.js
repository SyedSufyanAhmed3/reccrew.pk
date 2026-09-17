/* ===================================================================
   RAC Crew — main.js (Master Upgrade)
   Navbar behavior, mobile menu drawer, Before/After slider, WhatsApp.
   =================================================================== */

(function () {
  "use strict";

  var PHONE_INTL = "923124986998";
  var DEFAULT_WA_MESSAGE = "Hello RAC Crew, I would like to inquire about your AC/HVAC services.";

  function buildWhatsAppLinks() {
    var links = document.querySelectorAll("[data-whatsapp]");
    links.forEach(function (el) {
      var msg = el.getAttribute("data-wa-message") || DEFAULT_WA_MESSAGE;
      el.setAttribute("href", "https://wa.me/" + PHONE_INTL + "?text=" + encodeURIComponent(msg));
      el.setAttribute("target", "_blank");
      el.setAttribute("rel", "noopener noreferrer");
    });
  }

  function initHeader() {
    var header = document.querySelector(".site-header");
    if (!header) return;
    var onScroll = function () {
      if (window.scrollY > 15) {
        header.classList.add("scrolled");
      } else {
        header.classList.remove("scrolled");
      }
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
  }

  function initMobileNav() {
    var toggle = document.querySelector(".nav-toggle");
    var links = document.querySelector(".nav-links");
    var drawer = document.querySelector(".mobile-drawer");
    var closeBtn = document.querySelector(".mobile-drawer-close");
    var overlay = document.querySelector(".mobile-drawer-overlay");

    if (!toggle) return;

    function closeMenu() {
      if (links) links.classList.remove("open");
      if (drawer) drawer.classList.remove("open");
      document.body.classList.remove("menu-open");
      toggle.setAttribute("aria-expanded", "false");
    }

    function openMenu() {
      if (drawer) {
        drawer.classList.add("open");
      } else if (links) {
        links.classList.add("open");
      }
      document.body.classList.add("menu-open");
      toggle.setAttribute("aria-expanded", "true");
    }

    toggle.addEventListener("click", function () {
      var isOpen = (drawer && drawer.classList.contains("open")) || (links && links.classList.contains("open"));
      isOpen ? closeMenu() : openMenu();
    });

    if (closeBtn) closeBtn.addEventListener("click", closeMenu);
    if (overlay) overlay.addEventListener("click", closeMenu);

    document.querySelectorAll(".mobile-nav-links a, .nav-links a").forEach(function (a) {
      a.addEventListener("click", closeMenu);
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeMenu();
    });
  }

  function markActiveLink() {
    var path = window.location.pathname.split("/").pop() || "index.html";
    if (path === "") path = "index.html";
    document.querySelectorAll(".nav-links a[href], .mobile-nav-links a[href]").forEach(function (a) {
      var href = a.getAttribute("href").split("/").pop();
      if (href === path) a.classList.add("active");
    });
  }

  function initBeforeAfterSliders() {
    var sliders = document.querySelectorAll(".ba-slider");
    sliders.forEach(function (slider) {
      var after = slider.querySelector(".ba-after");
      var handle = slider.querySelector(".ba-handle");
      if (!after || !handle) return;

      var isDragging = false;

      function setPosition(clientX) {
        var rect = slider.getBoundingClientRect();
        var offsetX = clientX - rect.left;
        if (offsetX < 0) offsetX = 0;
        if (offsetX > rect.width) offsetX = rect.width;
        var pct = (offsetX / rect.width) * 100;
        after.style.width = pct + "%";
        handle.style.left = pct + "%";
      }

      handle.addEventListener("mousedown", function (e) {
        isDragging = true;
        e.preventDefault();
      });

      window.addEventListener("mouseup", function () {
        isDragging = false;
      });

      window.addEventListener("mousemove", function (e) {
        if (!isDragging) return;
        setPosition(e.clientX);
      });

      handle.addEventListener("touchstart", function (e) {
        isDragging = true;
      }, { passive: true });

      window.addEventListener("touchend", function () {
        isDragging = false;
      }, { passive: true });

      window.addEventListener("touchmove", function (e) {
        if (!isDragging) return;
        if (e.touches.length > 0) {
          setPosition(e.touches[0].clientX);
        }
      });

      slider.addEventListener("click", function (e) {
        if (e.target.closest(".ba-handle")) return;
        setPosition(e.clientX);
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    buildWhatsAppLinks();
    initHeader();
    initMobileNav();
    markActiveLink();
    initBeforeAfterSliders();
  });
})();
