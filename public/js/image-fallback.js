(() => {
  document.addEventListener(
    "error",
    (event) => {
      const image = event.target;
      if (!(image instanceof HTMLImageElement)) {
        return;
      }

      if (!image.classList.contains("js-fallback-next-image")) {
        return;
      }

      image.style.display = "none";
      const next = image.nextElementSibling;
      if (next) {
        next.style.display = "flex";
      }
    },
    true
  );
})();
