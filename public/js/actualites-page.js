(() => {
  let chargementEnCours = false;

  function pageActualitesActive() {
    return new URLSearchParams(window.location.search).get("page") === "actualites";
  }

  function shellActualites() {
    return document.querySelector("[data-actualites-shell]");
  }

  function formFiltre() {
    return document.querySelector("[data-actualites-filter-form]");
  }

  function construireUrlActualites(rawUrl) {
    const url = new URL(rawUrl, window.location.href);
    if (!url.searchParams.get("page")) {
      url.searchParams.set("page", "actualites");
    }
    return url;
  }

  function urlRelativeSansAjax(url) {
    const copie = new URL(url.toString());
    copie.searchParams.delete("ajax");
    return `${copie.pathname}${copie.search}${copie.hash}`;
  }

  function appliquerEtatChargement(actif) {
    const shell = shellActualites();
    if (shell) {
      shell.classList.toggle("is-loading", actif);
    }
  }

  function cibleScrollContenuActualites() {
    return document.querySelector(".featured-section") || shellActualites();
  }

  function remonterEnHautContenuActualites() {
    const cible = cibleScrollContenuActualites();
    if (!cible) {
      return;
    }

    const prefersReducedMotion =
      window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    const offset = 92;
    const top = Math.max(
      0,
      Math.round(cible.getBoundingClientRect().top + window.scrollY - offset)
    );

    window.scrollTo({
      top,
      behavior: prefersReducedMotion ? "auto" : "smooth",
    });
  }

  async function chargerListeActualites(rawUrl, options = {}) {
    const { pushState = true, scrollToContentTop = false } = options;
    if (!pageActualitesActive()) {
      return false;
    }
    if (chargementEnCours) {
      return false;
    }
    if (typeof window.fetch !== "function" || typeof DOMParser === "undefined") {
      return false;
    }

    const shellCourant = shellActualites();
    if (!shellCourant) {
      return false;
    }

    chargementEnCours = true;
    appliquerEtatChargement(true);

    try {
      const url = construireUrlActualites(rawUrl);
      url.searchParams.set("ajax", "1");

      const response = await fetch(url.toString(), {
        method: "GET",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const html = await response.text();
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, "text/html");
      const nouveauShell = doc.querySelector("[data-actualites-shell]");
      const shellActuel = shellActualites();

      if (!nouveauShell || !shellActuel) {
        throw new Error("Reponse AJAX actualites invalide.");
      }

      shellActuel.replaceWith(nouveauShell);

      const inputRecherche = document.getElementById("searchInput");
      if (inputRecherche instanceof HTMLInputElement) {
        inputRecherche.value = url.searchParams.get("q") || "";
      }
      const selectSource = document.getElementById("actualitesSource");
      if (selectSource instanceof HTMLSelectElement) {
        selectSource.value = url.searchParams.get("source") || "";
      }

      if (pushState) {
        window.history.pushState({ actualitesAjax: true }, "", urlRelativeSansAjax(url));
      }

      if (scrollToContentTop) {
        window.requestAnimationFrame(() => {
          remonterEnHautContenuActualites();
        });
      }

      return true;
    } catch (error) {
      console.error("Erreur pagination actualites:", error);
      return false;
    } finally {
      chargementEnCours = false;
      appliquerEtatChargement(false);
    }
  }

  function urlDepuisForm(form) {
    const action = String(form.getAttribute("action") || window.location.href).trim();
    const url = construireUrlActualites(action);
    const donnees = new FormData(form);

    for (const [cle] of Array.from(url.searchParams.entries())) {
      if (cle !== "page") {
        url.searchParams.delete(cle);
      }
    }

    donnees.forEach((valeur, cle) => {
      const texte = String(valeur || "").trim();
      if (texte === "") {
        return;
      }
      url.searchParams.set(cle, texte);
    });
    url.searchParams.delete("p");

    return url;
  }

  function initialiserInteractions() {
    document.addEventListener("submit", (event) => {
      const form =
        event.target instanceof Element
          ? event.target.closest("[data-actualites-filter-form]")
          : null;
      if (!(form instanceof HTMLFormElement)) {
        return;
      }
      event.preventDefault();

      const url = urlDepuisForm(form);
      chargerListeActualites(url.toString(), { pushState: true, scrollToContentTop: true }).catch(
        () => {
          window.location.href = urlRelativeSansAjax(url);
        }
      );
    });

    document.addEventListener("change", (event) => {
      const select =
        event.target instanceof Element ? event.target.closest("[data-actualites-source]") : null;
      if (!(select instanceof HTMLSelectElement)) {
        return;
      }

      const form = formFiltre();
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      const url = urlDepuisForm(form);
      chargerListeActualites(url.toString(), { pushState: true, scrollToContentTop: true }).catch(
        () => {
          window.location.href = urlRelativeSansAjax(url);
        }
      );
    });

    document.addEventListener("click", (event) => {
      const reset =
        event.target instanceof Element ? event.target.closest("[data-actualites-reset]") : null;
      if (reset) {
        event.preventDefault();
        const form = formFiltre();
        if (form instanceof HTMLFormElement) {
          form.reset();
        }

        chargerListeActualites("?page=actualites", {
          pushState: true,
          scrollToContentTop: true,
        }).catch(() => {
          window.location.href = "?page=actualites";
        });
        return;
      }

      const paginationLink =
        event.target instanceof Element ? event.target.closest(".pagination-nav a") : null;

      if (!(paginationLink instanceof HTMLAnchorElement)) {
        return;
      }

      if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }

      event.preventDefault();
      const href = String(paginationLink.getAttribute("href") || "").trim();
      if (href === "") {
        return;
      }

      chargerListeActualites(href, { pushState: true, scrollToContentTop: true }).catch(() => {
        window.location.href = href;
      });
    });

    window.addEventListener("popstate", () => {
      if (!pageActualitesActive()) {
        return;
      }

      chargerListeActualites(window.location.href, {
        pushState: false,
        scrollToContentTop: true,
      }).catch(() => {
        window.location.reload();
      });
    });
  }

  function init() {
    if (!pageActualitesActive()) {
      return;
    }
    initialiserInteractions();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
