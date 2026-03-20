(() => {
  document.addEventListener("DOMContentLoaded", () => {
    const flashNode = document.getElementById("publicFlashData");
    if (!flashNode) {
      return;
    }

    const flashType = String(flashNode.dataset.flashType || flashNode.dataset.type || "info")
      .trim()
      .toLowerCase();
    const flashMessage = String(
      flashNode.dataset.flashMessage || flashNode.dataset.message || ""
    ).trim();

    flashNode.remove();
    if (flashMessage === "") {
      return;
    }

    const typeMap = {
      success: "succes",
      succes: "succes",
      danger: "erreur",
      error: "erreur",
      erreur: "erreur",
      warning: "avertissement",
      avertissement: "avertissement",
      info: "info",
    };
    const method = typeMap[flashType] || "info";

    if (
      typeof ToastNotification !== "undefined" &&
      typeof ToastNotification[method] === "function"
    ) {
      ToastNotification[method](flashMessage);
      return;
    }

    if (
      typeof PublicNotification !== "undefined" &&
      typeof PublicNotification[method] === "function"
    ) {
      PublicNotification[method](flashMessage);
    }
  });
})();
