/* ===================================================================
   RAC Crew — animations.js
   Scroll-reveal via IntersectionObserver. Fully skipped when the user
   has requested reduced motion.
   =================================================================== */

(function () {
  "use strict";

  var prefersReduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function initReveal() {
    var items = document.querySelectorAll(".reveal");
    if (!items.length) return;

    if (prefersReduced || !("IntersectionObserver" in window)) {
      items.forEach(function (el) { el.classList.add("is-in"); });
      return;
    }

    var observer = new IntersectionObserver(
      function (entries, obs) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-in");
            obs.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15, rootMargin: "0px 0px -40px 0px" }
    );

    items.forEach(function (el) { observer.observe(el); });
  }

  // Auto-tag common content blocks as reveal targets if not already tagged
  function autoTagReveal() {
    var selectors = [
      ".service-card", ".tech-card", ".project-card", ".section-head",
      ".gallery-item", ".form-card", ".contact-info-card"
    ];
    selectors.forEach(function (sel) {
      document.querySelectorAll(sel).forEach(function (el, i) {
        if (!el.classList.contains("reveal")) {
          el.classList.add("reveal");
          if (i % 3 === 1) el.classList.add("reveal-delay-1");
          if (i % 3 === 2) el.classList.add("reveal-delay-2");
        }
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    autoTagReveal();
    initReveal();
  });
})();
