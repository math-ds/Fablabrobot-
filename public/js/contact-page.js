(() => {
  document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("contactForm");
    if (!form) {
      return;
    }

    const inputs = form.querySelectorAll("input, textarea, select");
    inputs.forEach((input) => {
      input.addEventListener("blur", () => {
        const value = "value" in input ? String(input.value || "").trim() : "";
        input.style.borderColor =
          value !== "" ? "var(--primary-color)" : "rgba(255, 255, 255, 0.1)";
      });
    });
  });
})();
