const ToastNotification = {
  conteneur: null,
  timers: new WeakMap(),

  normaliserType(type) {
    const value = String(type || "info")
      .trim()
      .toLowerCase();
    const map = {
      success: "success",
      succes: "success",
      info: "info",
      warning: "warning",
      avertissement: "warning",
      danger: "danger",
      error: "danger",
      erreur: "danger",
    };
    return map[value] || "info";
  },

  normaliserDuree(type, duree) {
    const parsed = Number(duree);
    if (Number.isFinite(parsed) && parsed > 0) {
      return Math.max(1200, Math.min(parsed, 15000));
    }
    return type === "danger" ? 6000 : 4200;
  },

  obtenirIcone(type) {
    const icones = {
      success: '<i class="fas fa-check-circle" aria-hidden="true"></i>',
      danger: '<i class="fas fa-exclamation-circle" aria-hidden="true"></i>',
      warning: '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i>',
      info: '<i class="fas fa-info-circle" aria-hidden="true"></i>',
    };
    return icones[type] || icones.info;
  },

  securiserTexte(message) {
    return String(message ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  },

  initialiser() {
    if (this.conteneur) {
      return;
    }

    const existing = document.getElementById("toast-conteneur");
    if (existing) {
      this.conteneur = existing;
      return;
    }

    this.conteneur = document.createElement("div");
    this.conteneur.id = "toast-conteneur";
    this.conteneur.className = "toast-conteneur";
    this.conteneur.setAttribute("aria-live", "polite");
    this.conteneur.setAttribute("aria-atomic", "false");
    document.body.appendChild(this.conteneur);
  },

  planifierFermeture(toast, duree) {
    const start = Date.now();
    const timeoutId = window.setTimeout(() => this.fermer(toast), duree);
    this.timers.set(toast, { timeoutId, start, remaining: duree });
  },

  pauseFermeture(toast) {
    const timer = this.timers.get(toast);
    if (!timer) {
      return;
    }
    clearTimeout(timer.timeoutId);
    const elapsed = Date.now() - timer.start;
    timer.remaining = Math.max(600, timer.remaining - elapsed);
    this.timers.set(toast, timer);
  },

  reprendreFermeture(toast) {
    const timer = this.timers.get(toast);
    if (!timer) {
      return;
    }
    clearTimeout(timer.timeoutId);
    timer.start = Date.now();
    timer.timeoutId = window.setTimeout(() => this.fermer(toast), Math.max(600, timer.remaining));
    this.timers.set(toast, timer);
  },

  fermer(toast) {
    if (!(toast instanceof HTMLElement)) {
      return;
    }

    const timer = this.timers.get(toast);
    if (timer) {
      clearTimeout(timer.timeoutId);
      this.timers.delete(toast);
    }

    toast.classList.remove("toast-visible");
    window.setTimeout(() => {
      if (toast.parentNode) {
        toast.remove();
      }
    }, 220);
  },

  afficher(message, type = "info", duree = 4200) {
    this.initialiser();
    const text = String(message || "").trim();
    if (text === "") {
      return;
    }

    const normalizedType = this.normaliserType(type);
    const normalizedDuree = this.normaliserDuree(normalizedType, duree);
    const role = normalizedType === "danger" || normalizedType === "warning" ? "alert" : "status";

    const toast = document.createElement("div");
    toast.className = `toast toast-${normalizedType}`;
    toast.setAttribute("role", role);
    toast.setAttribute("aria-live", role === "alert" ? "assertive" : "polite");
    toast.innerHTML = `
      <div class="toast-icone">${this.obtenirIcone(normalizedType)}</div>
      <div class="toast-message">${this.securiserTexte(text)}</div>
      <button type="button" class="toast-fermer" aria-label="Fermer la notification">&times;</button>
    `;

    this.conteneur.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add("toast-visible"));

    const closeBtn = toast.querySelector(".toast-fermer");
    if (closeBtn) {
      closeBtn.addEventListener("click", () => this.fermer(toast));
    }

    toast.addEventListener("mouseenter", () => this.pauseFermeture(toast));
    toast.addEventListener("mouseleave", () => this.reprendreFermeture(toast));

    this.planifierFermeture(toast, normalizedDuree);
  },

  succes(message, duree) {
    this.afficher(message, "success", duree);
  },

  success(message, duree) {
    this.afficher(message, "success", duree);
  },

  erreur(message, duree) {
    this.afficher(message, "danger", duree);
  },

  error(message, duree) {
    this.afficher(message, "danger", duree);
  },

  danger(message, duree) {
    this.afficher(message, "danger", duree);
  },

  avertissement(message, duree) {
    this.afficher(message, "warning", duree);
  },

  warning(message, duree) {
    this.afficher(message, "warning", duree);
  },

  info(message, duree) {
    this.afficher(message, "info", duree);
  },
};

document.addEventListener("DOMContentLoaded", () => {
  ToastNotification.initialiser();
});
