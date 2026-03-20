(() => {
  const ADMIN_ACTUALITES_AJAX_MODE = true;
  let requeteListeEnCours = null;

  function pageAdminActualitesActive() {
    return Boolean(document.querySelector("[data-admin-actualites]"));
  }

  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? String(meta.content || "") : "";
  }

  function notifierSucces(message) {
    const texte = String(message || "").trim();
    if (texte === "") {
      return;
    }

    if (
      typeof ToastNotification !== "undefined" &&
      typeof ToastNotification.succes === "function"
    ) {
      ToastNotification.succes(texte);
      return;
    }

    window.alert(texte);
  }

  function notifierErreur(message) {
    const texte = String(message || "").trim();
    if (texte === "") {
      return;
    }

    if (
      typeof ToastNotification !== "undefined" &&
      typeof ToastNotification.erreur === "function"
    ) {
      ToastNotification.erreur(texte);
      return;
    }

    window.alert(texte);
  }

  function afficherFallbackImage(image) {
    if (!(image instanceof HTMLImageElement)) {
      return;
    }

    image.style.display = "none";
    const container = image.closest(".image-container");
    if (!(container instanceof HTMLElement)) {
      return;
    }

    const fallback = container.querySelector(".no-image-fallback");
    if (fallback instanceof HTMLElement) {
      fallback.style.display = "flex";
    }
  }

  function initialiserFallbackImages(scope = document) {
    if (!(scope instanceof Document || scope instanceof HTMLElement)) {
      return;
    }

    scope.querySelectorAll("img.js-image-fallback").forEach((image) => {
      if (!(image instanceof HTMLImageElement)) {
        return;
      }

      if (image.complete && image.naturalWidth === 0) {
        afficherFallbackImage(image);
      }
    });
  }

  function setBusy(button, busy, pendingLabel) {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    if (busy) {
      button.dataset.originalHtml = button.innerHTML;
      button.disabled = true;
      button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${pendingLabel}`;
      return;
    }

    button.disabled = false;
    if (button.dataset.originalHtml) {
      button.innerHTML = button.dataset.originalHtml;
      delete button.dataset.originalHtml;
    }
  }

  async function postActionActualites(action, payload = {}) {
    if (typeof AjaxHelper !== "undefined" && typeof AjaxHelper.post === "function") {
      const data = await AjaxHelper.post("?page=admin-actualites", { action, ...payload });
      if (!data || !data.success) {
        throw new Error(String(data?.message || "Erreur serveur."));
      }
      return data;
    }

    const body = new URLSearchParams({ action, ...payload }).toString();
    const response = await fetch("?page=admin-actualites", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-CSRF-Token": csrfToken(),
        "X-Requested-With": "XMLHttpRequest",
      },
      body,
    });

    const data = await response.json();
    if (!response.ok || !data || !data.success) {
      throw new Error(String(data?.message || "Erreur serveur."));
    }

    return data;
  }

  function appliquerChargementListe(actif) {
    const dashboard = document.querySelector(".dashboard.actualites-admin");
    if (dashboard instanceof HTMLElement) {
      dashboard.classList.toggle("is-loading", Boolean(actif));
      dashboard.setAttribute("aria-busy", actif ? "true" : "false");
    }

    const tableContainer = document.querySelector(".actualites-admin .table-container");
    if (tableContainer instanceof HTMLElement) {
      tableContainer.classList.toggle("is-loading", Boolean(actif));
    }
  }

  function construireUrlFiltres(page = 1) {
    const url = new URL(window.location.href);
    url.searchParams.set("page", "admin-actualites");
    url.searchParams.delete("ajax");

    const champRecherche = document.getElementById("champRecherche");
    const filtreSource = document.getElementById("filterSource");

    const recherche = champRecherche instanceof HTMLInputElement ? champRecherche.value.trim() : "";
    const source = filtreSource instanceof HTMLSelectElement ? filtreSource.value.trim() : "";

    if (recherche === "") {
      url.searchParams.delete("q");
    } else {
      url.searchParams.set("q", recherche);
    }

    if (source === "") {
      url.searchParams.delete("source");
    } else {
      url.searchParams.set("source", source);
    }

    const pageNormalisee = Number.isFinite(Number(page)) ? Math.max(1, Number(page)) : 1;
    url.searchParams.set("p", String(pageNormalisee));

    return url;
  }

  function remonterEnHautContenuAdmin() {
    const dashboard =
      document.querySelector(".dashboard.actualites-admin") || document.querySelector(".dashboard");
    if (!(dashboard instanceof HTMLElement)) {
      return;
    }

    const topbar = document.querySelector(".admin-topbar");
    const hauteurTopbar = topbar instanceof HTMLElement ? topbar.getBoundingClientRect().height : 0;
    const marge = 12;
    const cible = Math.max(
      0,
      Math.round(dashboard.getBoundingClientRect().top + window.scrollY - hauteurTopbar - marge)
    );

    window.scrollTo({
      top: cible,
      behavior: "smooth",
    });
  }

  function remplacerDashboard(documentReponse) {
    const dashboardCourant =
      document.querySelector(".dashboard.actualites-admin") || document.querySelector(".dashboard");
    const dashboardSuivant =
      documentReponse.querySelector(".dashboard.actualites-admin") ||
      documentReponse.querySelector(".dashboard");

    if (!(dashboardCourant instanceof HTMLElement) || !(dashboardSuivant instanceof HTMLElement)) {
      return false;
    }

    dashboardCourant.outerHTML = dashboardSuivant.outerHTML;
    initialiserFallbackImages(document);
    return true;
  }

  async function chargerListeActualites(url, options = {}) {
    const { pushState = true, scrollToContentTop = false } = options;

    const urlNormalisee = new URL(url.toString(), window.location.href);
    urlNormalisee.searchParams.set("page", "admin-actualites");

    if (!ADMIN_ACTUALITES_AJAX_MODE) {
      window.location.assign(`${urlNormalisee.pathname}${urlNormalisee.search}`);
      return;
    }

    const fetchUrl = new URL(urlNormalisee.toString());
    fetchUrl.searchParams.set("ajax", "1");

    if (requeteListeEnCours) {
      requeteListeEnCours.abort();
    }
    const controller = new AbortController();
    requeteListeEnCours = controller;
    appliquerChargementListe(true);

    try {
      const response = await fetch(fetchUrl.toString(), {
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

      const remplace = remplacerDashboard(documentReponse);
      if (!remplace) {
        window.location.href = urlNormalisee.toString();
        return;
      }

      if (pushState) {
        urlNormalisee.searchParams.delete("ajax");
        window.history.pushState({}, "", `${urlNormalisee.pathname}${urlNormalisee.search}`);
      }

      if (scrollToContentTop) {
        remonterEnHautContenuAdmin();
      }
    } finally {
      if (requeteListeEnCours === controller) {
        requeteListeEnCours = null;
      }
      appliquerChargementListe(false);
    }
  }

  async function rafraichirListeCourante(options = {}) {
    const { scrollToContentTop = false } = options;
    await chargerListeActualites(window.location.href, { pushState: false, scrollToContentTop });
  }

  function initActualitesAdmin() {
    let timerRecherche = null;

    initialiserFallbackImages(document);

    document.addEventListener(
      "error",
      (event) => {
        const target = event.target;
        if (!(target instanceof HTMLImageElement)) {
          return;
        }
        if (!target.classList.contains("js-image-fallback")) {
          return;
        }
        afficherFallbackImage(target);
      },
      true
    );

    document.addEventListener("click", (event) => {
      if (!pageAdminActualitesActive()) {
        return;
      }

      const syncButton =
        event.target instanceof Element ? event.target.closest("#btnSynchroniser") : null;
      if (syncButton instanceof HTMLButtonElement) {
        event.preventDefault();
        const confirmation = window.confirm("Synchroniser les actualités depuis les flux RSS ?");
        if (!confirmation) {
          return;
        }

        setBusy(syncButton, true, "Synchronisation...");
        postActionActualites("synchroniser")
          .then((data) => {
            notifierSucces(String(data.message || "Synchronisation terminée."));
            return rafraichirListeCourante({ scrollToContentTop: true });
          })
          .catch((error) => {
            notifierErreur(error instanceof Error ? error.message : "Erreur de synchronisation.");
          })
          .finally(() => {
            setBusy(syncButton, false, "");
          });
        return;
      }

      const cleanButton =
        event.target instanceof Element ? event.target.closest("#btnNettoyer") : null;
      if (cleanButton instanceof HTMLButtonElement) {
        event.preventDefault();
        const confirmation = window.confirm("Supprimer les actualités de plus de 30 jours ?");
        if (!confirmation) {
          return;
        }

        setBusy(cleanButton, true, "Nettoyage...");
        postActionActualites("nettoyer", { jours: "30" })
          .then((data) => {
            notifierSucces(String(data.message || "Nettoyage terminé."));
            return rafraichirListeCourante({ scrollToContentTop: true });
          })
          .catch((error) => {
            notifierErreur(error instanceof Error ? error.message : "Erreur de nettoyage.");
          })
          .finally(() => {
            setBusy(cleanButton, false, "");
          });
        return;
      }

      const deleteButton =
        event.target instanceof Element ? event.target.closest("[data-actualite-delete-id]") : null;
      if (deleteButton instanceof HTMLButtonElement) {
        event.preventDefault();

        const id = Number.parseInt(String(deleteButton.dataset.actualiteDeleteId || "0"), 10);
        const titre = String(deleteButton.dataset.actualiteDeleteTitle || "").trim();
        if (!Number.isFinite(id) || id <= 0) {
          notifierErreur("ID actualité invalide.");
          return;
        }

        const confirmation = window.confirm(`Supprimer l'actualité "${titre}" ?`);
        if (!confirmation) {
          return;
        }

        setBusy(deleteButton, true, "Suppression...");
        postActionActualites("supprimer", { id: String(id) })
          .then(() => {
            notifierSucces("Actualité supprimée avec succès.");
            if (
              window.AdminDashboardAjax &&
              typeof window.AdminDashboardAjax.refreshAfterDelete === "function"
            ) {
              return window.AdminDashboardAjax.refreshAfterDelete({
                deletedRows: 1,
                preserveLocalState: false,
                scrollToTop: false,
              });
            }
            return rafraichirListeCourante({ scrollToContentTop: false });
          })
          .catch((error) => {
            notifierErreur(
              error instanceof Error ? error.message : "Erreur lors de la suppression."
            );
          })
          .finally(() => {
            setBusy(deleteButton, false, "");
          });
      }
    });

    document.addEventListener("input", (event) => {
      if (!pageAdminActualitesActive()) {
        return;
      }

      const target = event.target instanceof HTMLElement ? event.target : null;
      if (!target || target.id !== "champRecherche") {
        return;
      }

      if (timerRecherche) {
        clearTimeout(timerRecherche);
      }

      timerRecherche = window.setTimeout(() => {
        const url = construireUrlFiltres(1);
        chargerListeActualites(url, { pushState: true, scrollToContentTop: true }).catch(
          (error) => {
            if (error?.name === "AbortError") {
              return;
            }
            notifierErreur(
              error instanceof Error ? error.message : "Impossible de filtrer les actualités."
            );
          }
        );
      }, 260);
    });

    document.addEventListener("change", (event) => {
      if (!pageAdminActualitesActive()) {
        return;
      }

      const target = event.target instanceof HTMLElement ? event.target : null;
      if (!target || target.id !== "filterSource") {
        return;
      }

      const url = construireUrlFiltres(1);
      chargerListeActualites(url, { pushState: true, scrollToContentTop: true }).catch((error) => {
        if (error?.name === "AbortError") {
          return;
        }
        notifierErreur(
          error instanceof Error ? error.message : "Impossible de filtrer les actualités."
        );
      });
    });

    window.addEventListener("popstate", () => {
      if (!pageAdminActualitesActive()) {
        return;
      }

      chargerListeActualites(window.location.href, {
        pushState: false,
        scrollToContentTop: true,
      }).catch((error) => {
        if (error?.name === "AbortError") {
          return;
        }
        window.location.reload();
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initActualitesAdmin);
  } else {
    initActualitesAdmin();
  }
})();
