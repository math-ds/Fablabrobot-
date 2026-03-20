(() => {
  let favorisFilterLoading = false;

  function notifier(type, message) {
    const texte = String(message || "").trim();
    if (texte === "") {
      return;
    }

    if (typeof PublicNotification !== "undefined") {
      if (type === "success") {
        PublicNotification.succes(texte);
      } else {
        PublicNotification.erreur(texte);
      }
      return;
    }

    if (typeof ToastNotification !== "undefined") {
      if (type === "success") {
        ToastNotification.succes(texte);
      } else {
        ToastNotification.erreur(texte);
      }
    }
  }

  function extraireMessageErreur(error, fallbackMessage) {
    if (error instanceof Error) {
      const message = String(error.message || "").trim();
      if (message !== "") {
        return message;
      }
    }

    if (error && typeof error === "object") {
      const direct = String(error.message || "").trim();
      if (direct !== "") {
        return direct;
      }

      const nested = String(error?.data?.message || "").trim();
      if (nested !== "") {
        return nested;
      }
    }

    return fallbackMessage;
  }

  function setFavoriState(button, isFavori) {
    button.classList.toggle("is-active", Boolean(isFavori));
    button.setAttribute("aria-pressed", isFavori ? "true" : "false");
    button.setAttribute("title", isFavori ? "Retirer des favoris" : "Ajouter aux favoris");

    const icon = button.querySelector("i");
    if (icon) {
      icon.classList.remove("far", "fas");
      icon.classList.add(isFavori ? "fas" : "far", "fa-heart");
    }

    const label = button.querySelector("[data-favori-label]");
    if (label) {
      label.textContent = isFavori ? "Retirer des favoris" : "Ajouter aux favoris";
    }
  }

  function setLoading(button, loading) {
    if (loading) {
      button.dataset.loading = "1";
      button.setAttribute("aria-busy", "true");
      button.setAttribute("disabled", "disabled");
      return;
    }

    delete button.dataset.loading;
    button.removeAttribute("aria-busy");
    button.removeAttribute("disabled");
  }

  function updateAllMatchingButtons(typeContenu, contenuId, isFavori) {
    const selector = `[data-favori-toggle][data-favori-type="${typeContenu}"][data-favori-id="${contenuId}"]`;
    document.querySelectorAll(selector).forEach((button) => {
      setFavoriState(button, isFavori);
    });
  }

  function getFavorisShell() {
    return document.querySelector("[data-favoris-shell]");
  }

  function getFavorisContent() {
    return document.querySelector("[data-favoris-content]");
  }

  function isFavorisPage() {
    return Boolean(getFavorisShell() && getFavorisContent());
  }

  function setFavorisLoading(loading) {
    const shell = getFavorisShell();
    const content = getFavorisContent();
    if (shell) {
      shell.classList.toggle("is-loading", loading);
    }
    if (content) {
      content.classList.toggle("is-loading", loading);
    }
  }

  function toFavorisUrl(rawUrl) {
    const url = new URL(rawUrl, window.location.href);
    if (!url.searchParams.get("page")) {
      url.searchParams.set("page", "favoris");
    }
    return url;
  }

  function toRelativeUrl(url) {
    return `${url.pathname}${url.search}${url.hash}`;
  }

  async function chargerFavorisFiltreAjax(rawUrl, options = {}) {
    const { pushState = true } = options;
    if (!isFavorisPage()) {
      return false;
    }
    if (favorisFilterLoading) {
      return false;
    }
    if (typeof window.fetch !== "function" || typeof DOMParser === "undefined") {
      return false;
    }

    favorisFilterLoading = true;
    setFavorisLoading(true);

    try {
      const nextUrl = toFavorisUrl(rawUrl);
      const fetchUrl = new URL(nextUrl.toString());
      fetchUrl.searchParams.set("ajax", "1");

      const response = await window.fetch(fetchUrl.toString(), {
        method: "GET",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
      });

      if (!response.ok) {
        throw new Error(`Erreur HTTP ${response.status}`);
      }

      const html = await response.text();
      const parser = new DOMParser();
      const documentRecharge = parser.parseFromString(html, "text/html");
      const nouveauShell = documentRecharge.querySelector("[data-favoris-shell]");
      const nouveauContenu = documentRecharge.querySelector("[data-favoris-content]");
      const shellActuel = getFavorisShell();
      const contenuActuel = getFavorisContent();

      if (!nouveauShell || !nouveauContenu || !shellActuel || !contenuActuel) {
        throw new Error("Reponse AJAX favoris invalide.");
      }

      shellActuel.replaceWith(nouveauShell);
      contenuActuel.replaceWith(nouveauContenu);

      if (pushState) {
        window.history.pushState({ favorisAjax: true }, "", toRelativeUrl(nextUrl));
      }

      return true;
    } finally {
      favorisFilterLoading = false;
      setFavorisLoading(false);
    }
  }

  async function handleFavoriToggle(button) {
    if (button.dataset.loading === "1") {
      return;
    }
    if (typeof AjaxHelper === "undefined" || typeof AjaxHelper.post !== "function") {
      notifier("error", "Service favoris indisponible.");
      return;
    }

    const typeContenu = String(button.getAttribute("data-favori-type") || "").trim();
    const contenuId = Number.parseInt(String(button.getAttribute("data-favori-id") || "0"), 10);
    if (!typeContenu || !Number.isFinite(contenuId) || contenuId <= 0) {
      notifier("error", "Parametres favoris invalides.");
      return;
    }

    setLoading(button, true);
    try {
      const baseUrl = window.location.pathname.includes("/public/")
        ? window.location.pathname.split("/public/")[0] + "/public/"
        : "/Fablabrobot/public/";
      const response = await AjaxHelper.post(baseUrl + "?page=favoris-toggle", {
        type_contenu: typeContenu,
        contenu_id: contenuId,
      });

      if (!response?.success || !response?.data) {
        throw new Error(String(response?.message || "Erreur lors de la mise a jour."));
      }

      const isFavori = Boolean(response.data.is_favori);
      updateAllMatchingButtons(typeContenu, contenuId, isFavori);
      notifier(
        "success",
        String(response.message || (isFavori ? "Ajoute aux favoris." : "Retire des favoris."))
      );
      if (isFavorisPage() && !isFavori) {
        try {
          const reloaded = await chargerFavorisFiltreAjax(window.location.href, {
            pushState: false,
          });
          if (reloaded) {
            return;
          }
        } catch (error) {}
        window.location.reload();
        return;
      }
      document.dispatchEvent(
        new CustomEvent("favori:updated", {
          detail: {
            type_contenu: typeContenu,
            contenu_id: contenuId,
            is_favori: isFavori,
          },
        })
      );
    } catch (error) {
      notifier("error", extraireMessageErreur(error, "Erreur favoris."));
    } finally {
      setLoading(button, false);
    }
  }

  async function handleFavorisClear(button) {
    if (button.dataset.loading === "1") {
      return;
    }
    if (typeof AjaxHelper === "undefined" || typeof AjaxHelper.post !== "function") {
      notifier("error", "Service favoris indisponible.");
      return;
    }

    const totalFavoris = Number.parseInt(
      String(button.getAttribute("data-favoris-total") || "0"),
      10
    );
    if (!Number.isFinite(totalFavoris) || totalFavoris <= 0) {
      notifier("error", "Aucun favori a supprimer.");
      return;
    }

    const confirme = window.confirm("Supprimer tous vos favoris ?");
    if (!confirme) {
      return;
    }

    setLoading(button, true);
    setFavorisLoading(true);

    try {
      const baseUrl = window.location.pathname.includes("/public/")
        ? window.location.pathname.split("/public/")[0] + "/public/"
        : "/Fablabrobot/public/";
      const response = await AjaxHelper.post(baseUrl + "?page=favoris-clear", {});
      if (!response?.success) {
        throw new Error(String(response?.message || "Erreur lors de la suppression des favoris."));
      }

      notifier("success", String(response.message || "Tous les favoris ont ete supprimes."));
      const reloaded = await chargerFavorisFiltreAjax("?page=favoris&type=all", {
        pushState: true,
      });
      if (!reloaded) {
        window.location.href = "?page=favoris&type=all";
      }
    } catch (error) {
      notifier("error", extraireMessageErreur(error, "Erreur lors de la suppression des favoris."));
    } finally {
      setLoading(button, false);
      setFavorisLoading(false);
    }
  }

  function initFavoris() {
    document.addEventListener("click", (event) => {
      const clearButton =
        event.target instanceof Element ? event.target.closest("[data-favoris-clear]") : null;
      if (clearButton) {
        event.preventDefault();
        handleFavorisClear(clearButton);
        return;
      }

      const filtre =
        event.target instanceof Element ? event.target.closest("a[data-favoris-filter]") : null;
      if (filtre) {
        if (
          event.button !== 0 ||
          event.metaKey ||
          event.ctrlKey ||
          event.shiftKey ||
          event.altKey
        ) {
          return;
        }
        event.preventDefault();
        const href = String(filtre.getAttribute("href") || "").trim();
        if (href === "") {
          return;
        }

        chargerFavorisFiltreAjax(href, { pushState: true }).catch(() => {
          window.location.href = href;
        });
        return;
      }

      const target =
        event.target instanceof Element ? event.target.closest("[data-favori-toggle]") : null;
      if (!target) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      handleFavoriToggle(target);
    });

    window.addEventListener("popstate", () => {
      const page = new URLSearchParams(window.location.search).get("page");
      if (page !== "favoris" || !isFavorisPage()) {
        return;
      }

      chargerFavorisFiltreAjax(window.location.href, {
        pushState: false,
      }).catch(() => {
        window.location.reload();
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initFavoris);
  } else {
    initFavoris();
  }
})();
