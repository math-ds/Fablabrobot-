(() => {
  let requeteEnCours = null;
  let timerRecherche = null;

  function estPageCommentairesAdmin() {
    const params = new URLSearchParams(window.location.search);
    return params.get("page") === "admin-comments";
  }

  function normaliserTexte(value) {
    return String(value ?? "").trim();
  }

  function appliquerEtatChargement(actif) {
    const tableContainer = document.querySelector(".table-container");
    if (!tableContainer) return;
    tableContainer.classList.toggle("is-loading", Boolean(actif));
  }

  function construireUrlDepuisForm(form) {
    const url = new URL(window.location.href);
    const donnees = new FormData(form);

    url.searchParams.set("page", "admin-comments");
    url.searchParams.delete("ajax");
    url.searchParams.delete("p");

    const q = normaliserTexte(donnees.get("q"));
    if (q !== "") {
      url.searchParams.set("q", q);
    } else {
      url.searchParams.delete("q");
    }

    const type = normaliserTexte(donnees.get("type")).toLowerCase();
    if (type !== "" && type !== "all") {
      url.searchParams.set("type", type);
    } else {
      url.searchParams.delete("type");
    }

    return url;
  }

  async function chargerDashboard(url, { pushState = true } = {}) {
    if (!estPageCommentairesAdmin()) {
      window.location.href = url.toString();
      return;
    }

    if (window.AdminDashboardAjax && typeof window.AdminDashboardAjax.load === "function") {
      await window.AdminDashboardAjax.load(url, {
        pushState,
        scrollToTop: false,
        preserveLocalState: false,
        showErrorToast: true,
      });
      return;
    }

    if (requeteEnCours) {
      requeteEnCours.abort();
    }
    const controller = new AbortController();
    requeteEnCours = controller;
    appliquerEtatChargement(true);

    try {
      const response = await fetch(url.toString(), {
        method: "GET",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
        signal: controller.signal,
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const html = await response.text();
      const parser = new DOMParser();
      const documentReponse = parser.parseFromString(html, "text/html");

      const dashboardCourant = document.querySelector(".dashboard");
      const dashboardSuivant = documentReponse.querySelector(".dashboard");
      if (
        !(dashboardCourant instanceof HTMLElement) ||
        !(dashboardSuivant instanceof HTMLElement)
      ) {
        window.location.href = url.toString();
        return;
      }

      dashboardCourant.outerHTML = dashboardSuivant.outerHTML;

      if (pushState) {
        window.history.pushState({}, "", `${url.pathname}${url.search}`);
      }
    } catch (error) {
      if (error?.name === "AbortError") {
        return;
      }
      console.error("Erreur AJAX admin commentaires:", error);
      if (
        typeof ToastNotification !== "undefined" &&
        typeof ToastNotification.erreur === "function"
      ) {
        ToastNotification.erreur("Impossible de charger les commentaires.");
      } else {
        window.location.href = url.toString();
      }
    } finally {
      if (requeteEnCours === controller) {
        requeteEnCours = null;
      }
      appliquerEtatChargement(false);
    }
  }

  async function supprimerCommentaire(id, auteur) {
    if (!window.confirm(`Etes-vous sur de vouloir supprimer le commentaire de "${auteur}" ?`)) {
      return;
    }

    try {
      const data = await AjaxHelper.post("?page=admin-comments", {
        action: "delete",
        id,
      });

      if (!data.success) {
        throw { data };
      }

      ToastNotification.succes(data.message || "Commentaire supprime avec succes");

      const deletedCount = Math.max(
        1,
        Number.parseInt(String(data?.data?.deleted_count || "1"), 10) || 1
      );

      if (
        window.AdminDashboardAjax &&
        typeof window.AdminDashboardAjax.refreshAfterDelete === "function"
      ) {
        await window.AdminDashboardAjax.refreshAfterDelete({
          deletedRows: deletedCount,
          preserveLocalState: false,
          scrollToTop: false,
        });
      } else {
        window.location.reload();
      }
    } catch (error) {
      ToastNotification.erreur(error?.data?.message || "Erreur lors de la suppression");
    }
  }

  document.addEventListener("submit", (event) => {
    const form =
      event.target instanceof Element ? event.target.closest(".comments-filters-form") : null;
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    event.preventDefault();
    const url = construireUrlDepuisForm(form);
    chargerDashboard(url, { pushState: true });
  });

  document.addEventListener("change", (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!(target instanceof HTMLSelectElement) || target.id !== "typeFilter") {
      return;
    }

    const form = target.closest(".comments-filters-form");
    if (form instanceof HTMLFormElement) {
      form.requestSubmit();
    }
  });

  document.addEventListener("input", (event) => {
    if (!estPageCommentairesAdmin()) {
      return;
    }

    const target = event.target instanceof Element ? event.target : null;
    if (!(target instanceof HTMLInputElement) || target.id !== "champRecherche") {
      return;
    }

    const form = target.closest(".comments-filters-form");
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    if (timerRecherche) {
      clearTimeout(timerRecherche);
    }

    timerRecherche = window.setTimeout(() => {
      const url = construireUrlDepuisForm(form);
      chargerDashboard(url, { pushState: true });
    }, 280);
  });

  document.addEventListener("click", (event) => {
    const target = event.target instanceof Element ? event.target : null;
    const resetLink = target ? target.closest(".comments-filters-actions a.btn") : null;
    if (!(resetLink instanceof HTMLAnchorElement)) {
      return;
    }

    event.preventDefault();
    const href = String(resetLink.getAttribute("href") || "").trim();
    if (!href) {
      return;
    }

    const url = new URL(href, window.location.href);
    chargerDashboard(url, { pushState: true });
  });

  window.addEventListener("popstate", () => {
    if (!estPageCommentairesAdmin()) {
      return;
    }
    const url = new URL(window.location.href);
    chargerDashboard(url, { pushState: false });
  });

  window.supprimerCommentaire = supprimerCommentaire;
})();
