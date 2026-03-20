const PublicNotification = {
  initialiser() {
    if (
      typeof ToastNotification !== "undefined" &&
      typeof ToastNotification.initialiser === "function"
    ) {
      ToastNotification.initialiser();
    }
  },

  afficher(message, type = "info", duree) {
    if (
      typeof ToastNotification !== "undefined" &&
      typeof ToastNotification.afficher === "function"
    ) {
      ToastNotification.afficher(message, type, duree);
      return;
    }

    const text = String(message || "").trim();
    if (text !== "") {
      console.log(text);
    }
  },

  succes(message, duree) {
    this.afficher(message, "success", duree);
  },

  erreur(message, duree) {
    this.afficher(message, "danger", duree);
  },

  avertissement(message, duree) {
    this.afficher(message, "warning", duree);
  },

  info(message, duree) {
    this.afficher(message, "info", duree);
  },
};

document.addEventListener("DOMContentLoaded", () => {
  PublicNotification.initialiser();
});
