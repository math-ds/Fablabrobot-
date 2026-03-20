(() => {
  function initialiserSoumissionFormulairesDepuisLiens() {
    document.addEventListener("click", (event) => {
      const target =
        event.target instanceof Element ? event.target.closest("[data-submit-parent-form]") : null;
      if (!target) {
        return;
      }

      const form = target.closest("form");
      if (!form) {
        return;
      }

      event.preventDefault();
      form.requestSubmit();
    });
  }

  function initialiserConfirmationsFormulaires() {
    document.addEventListener("submit", (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      const message = String(form.dataset.confirmMessage || "").trim();
      if (message === "") {
        return;
      }

      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    const boutonMenu = document.getElementById("boutonMenu");
    const menuMobile = document.getElementById("menuMobile");
    const menuMobileBackdrop = document.getElementById("menuMobileBackdrop");
    const barreNavigation = document.querySelector(".barre-navigation");
    const profilTrigger = document.getElementById("profilTrigger");
    const profilDropdown = document.getElementById("profilDropdown");
    const focusableSelector =
      "a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex='-1'])";

    function replacerFocusHorsMenuMobile() {
      if (!menuMobile) {
        return;
      }

      const elementActif = document.activeElement;
      if (!(elementActif instanceof HTMLElement)) {
        return;
      }

      if (!menuMobile.contains(elementActif)) {
        return;
      }

      if (boutonMenu instanceof HTMLElement) {
        boutonMenu.focus({ preventScroll: true });
      } else {
        elementActif.blur();
      }
    }

    function fermerMenuMobile() {
      if (!menuMobile || !boutonMenu) {
        return;
      }
      replacerFocusHorsMenuMobile();
      menuMobile.classList.remove("active");
      menuMobile.setAttribute("aria-hidden", "true");
      menuMobile.setAttribute("inert", "");
      boutonMenu.setAttribute("aria-expanded", "false");
      document.body.classList.remove("menu-mobile-open");
      if (menuMobileBackdrop) {
        menuMobileBackdrop.classList.remove("active");
      }
    }

    function ouvrirMenuMobile() {
      if (!menuMobile || !boutonMenu) {
        return;
      }
      menuMobile.classList.add("active");
      menuMobile.setAttribute("aria-hidden", "false");
      menuMobile.removeAttribute("inert");
      boutonMenu.setAttribute("aria-expanded", "true");
      document.body.classList.add("menu-mobile-open");
      if (menuMobileBackdrop) {
        menuMobileBackdrop.classList.add("active");
      }

      const premierFocusable = menuMobile.querySelector(focusableSelector);
      if (premierFocusable instanceof HTMLElement) {
        premierFocusable.focus({ preventScroll: true });
      }
    }

    if (boutonMenu && menuMobile) {
      if (!menuMobile.classList.contains("active")) {
        menuMobile.setAttribute("inert", "");
      }

      boutonMenu.addEventListener("click", () => {
        const estOuvert = menuMobile.classList.contains("active");
        if (estOuvert) {
          fermerMenuMobile();
        } else {
          ouvrirMenuMobile();
        }
      });

      menuMobile.querySelectorAll(".lien-nav").forEach((lien) => {
        lien.addEventListener("click", () => {
          fermerMenuMobile();
        });
      });

      if (menuMobileBackdrop) {
        menuMobileBackdrop.addEventListener("click", fermerMenuMobile);
      }

      document.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Node) || !menuMobile.classList.contains("active")) {
          return;
        }

        if (
          !menuMobile.contains(target) &&
          !boutonMenu.contains(target) &&
          !(barreNavigation && barreNavigation.contains(target))
        ) {
          fermerMenuMobile();
        }
      });

      document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && menuMobile.classList.contains("active")) {
          fermerMenuMobile();
        }
      });

      window.addEventListener("resize", () => {
        if (window.innerWidth >= 1024 && menuMobile.classList.contains("active")) {
          fermerMenuMobile();
        }
      });
    }

    if (profilTrigger && profilDropdown) {
      profilTrigger.addEventListener("click", (event) => {
        event.stopPropagation();
        const estOuvert = profilDropdown.classList.toggle("active");
        profilTrigger.setAttribute("aria-expanded", String(estOuvert));
      });

      document.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Node)) {
          return;
        }
        if (!profilTrigger.contains(target) && !profilDropdown.contains(target)) {
          profilDropdown.classList.remove("active");
          profilTrigger.setAttribute("aria-expanded", "false");
        }
      });
    }
  });

  initialiserSoumissionFormulairesDepuisLiens();
  initialiserConfirmationsFormulaires();
})();
