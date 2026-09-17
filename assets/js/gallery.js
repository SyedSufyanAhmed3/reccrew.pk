/* ===================================================================
   RAC Crew — gallery.js
   Category filtering + lightbox with keyboard support.
   =================================================================== */

(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var filterButtons = document.querySelectorAll(".filter-btn");
    var galleryItems = document.querySelectorAll(".gallery-item");
    var lightbox = document.getElementById("lightbox");
    if (!galleryItems.length) return;

    var visibleItems = Array.prototype.slice.call(galleryItems);
    var currentIndex = 0;

    // ---- Filtering ----
    filterButtons.forEach(function (btn) {
      btn.addEventListener("click", function () {
        filterButtons.forEach(function (b) { b.classList.remove("active"); });
        btn.classList.add("active");
        var cat = btn.getAttribute("data-filter");

        galleryItems.forEach(function (item) {
          var match = cat === "all" || item.getAttribute("data-category") === cat;
          item.classList.toggle("hidden", !match);
        });

        visibleItems = Array.prototype.slice.call(galleryItems).filter(function (item) {
          return !item.classList.contains("hidden");
        });
      });
    });

    // ---- Lightbox ----
    if (!lightbox) return;
    var lbImg = lightbox.querySelector("img");
    var lbCaption = lightbox.querySelector(".lightbox-caption");
    var closeBtn = lightbox.querySelector(".lightbox-close");
    var prevBtn = lightbox.querySelector(".lightbox-nav.prev");
    var nextBtn = lightbox.querySelector(".lightbox-nav.next");

    function openLightbox(item) {
      visibleItems = Array.prototype.slice.call(galleryItems).filter(function (i) {
        return !i.classList.contains("hidden");
      });
      currentIndex = visibleItems.indexOf(item);
      renderLightbox();
      lightbox.classList.add("open");
      lightbox.setAttribute("aria-hidden", "false");
      closeBtn.focus();
    }

    function renderLightbox() {
      var item = visibleItems[currentIndex];
      if (!item) return;
      var img = item.querySelector("img");
      lbImg.src = img.src;
      lbImg.alt = img.alt || "";
      lbCaption.textContent = img.alt || "";
    }

    function closeLightbox() {
      lightbox.classList.remove("open");
      lightbox.setAttribute("aria-hidden", "true");
    }

    function step(delta) {
      if (!visibleItems.length) return;
      currentIndex = (currentIndex + delta + visibleItems.length) % visibleItems.length;
      renderLightbox();
    }

    galleryItems.forEach(function (item) {
      item.addEventListener("click", function () { openLightbox(item); });
      item.setAttribute("tabindex", "0");
      item.setAttribute("role", "button");
      item.addEventListener("keydown", function (e) {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          openLightbox(item);
        }
      });
    });

    closeBtn.addEventListener("click", closeLightbox);
    prevBtn.addEventListener("click", function () { step(-1); });
    nextBtn.addEventListener("click", function () { step(1); });
    lightbox.addEventListener("click", function (e) {
      if (e.target === lightbox) closeLightbox();
    });
    document.addEventListener("keydown", function (e) {
      if (!lightbox.classList.contains("open")) return;
      if (e.key === "Escape") closeLightbox();
      if (e.key === "ArrowLeft") step(-1);
      if (e.key === "ArrowRight") step(1);
    });
  });
})();
