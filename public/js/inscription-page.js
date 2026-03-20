(() => {
  document.addEventListener("DOMContentLoaded", () => {
    const password = document.getElementById("password");
    const confirmPassword = document.getElementById("confirm-password");
    if (!password || !confirmPassword) {
      return;
    }

    confirmPassword.addEventListener("input", () => {
      if (password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity("Les mots de passe ne correspondent pas");
      } else {
        confirmPassword.setCustomValidity("");
      }
    });

    password.addEventListener("input", () => {
      if (confirmPassword.value) {
        confirmPassword.dispatchEvent(new Event("input"));
      }
    });
  });
})();
