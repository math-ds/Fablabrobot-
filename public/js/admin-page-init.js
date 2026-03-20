(() => {
  const ADMIN_AJAX_MODE = true;

  function initialiserSoumissionFormulairesDepuisLiens() {
    document.addEventListener("click", (event) => {
      const trigger =
        event.target instanceof Element ? event.target.closest("[data-submit-parent-form]") : null;
      if (!trigger) {
        return;
      }

      const form = trigger.closest("form");
      if (!form) {
        return;
      }

      event.preventDefault();
      form.requestSubmit();
    });
  }

  function initialiserRecherche() {
    if (
      typeof RechercheHelper === "undefined" ||
      typeof RechercheHelper.initialiser !== "function"
    ) {
      return;
    }

    document.querySelectorAll("input[data-search-target]").forEach((input) => {
      if (!(input instanceof HTMLInputElement)) {
        return;
      }
      if (input.dataset.searchBound === "1") {
        return;
      }

      const cible = String(input.dataset.searchTarget || "").trim();
      if (cible === "") {
        return;
      }

      if (!input.id) {
        input.id = `search-${Math.random().toString(36).slice(2, 10)}`;
      }

      RechercheHelper.initialiser(input.id, cible);
      input.dataset.searchBound = "1";
    });
  }

  function afficherFlashAdmin() {
    const flashNode = document.getElementById("adminFlashData");
    if (!flashNode) {
      return;
    }

    const type = String(flashNode.dataset.flashType || "info")
      .trim()
      .toLowerCase();
    const message = String(flashNode.dataset.flashMessage || "").trim();
    flashNode.remove();

    if (message === "" || typeof ToastNotification === "undefined") {
      return;
    }

    const methodMap = {
      success: "succes",
      succes: "succes",
      danger: "erreur",
      error: "erreur",
      erreur: "erreur",
      warning: "avertissement",
      avertissement: "avertissement",
      info: "info",
    };
    const method = methodMap[type] || "info";

    if (typeof ToastNotification[method] === "function") {
      ToastNotification[method](message);
    }
  }

  function normaliserLibelleColonne(texte) {
    return String(texte || "")
      .replace(/\s+/g, " ")
      .replace(/[:*]+$/g, "")
      .trim();
  }

  function normaliserCleColonne(texte) {
    return normaliserLibelleColonne(texte)
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "");
  }

  function prioriteMobileColonne(cle) {
    const priorites = {
      id: 5,
      type: 10,
      image: 15,
      miniature: 15,
      utilisateur: 20,
      contact: 20,
      auteur: 20,
      titre: 20,
      "nom-titre": 20,
      sujet: 30,
      categorie: 35,
      role: 35,
      statut: 35,
      "video-associee": 40,
      plateforme: 40,
      description: 50,
      message: 50,
      extrait: 50,
      "contenu-du-commentaire": 50,
      technologies: 50,
      date: 70,
      "supprime-le": 70,
      "date-de-publication": 70,
      actions: 90,
    };

    return priorites[cle] || 60;
  }

  function appliquerEtiquettesColonnes(table) {
    if (!(table instanceof HTMLTableElement)) {
      return;
    }

    const entetes = Array.from(table.querySelectorAll("thead th")).map((cellule) => {
      const classes = cellule.className || "";
      const libelle = normaliserLibelleColonne(cellule.textContent);
      let cle = normaliserCleColonne(libelle);

      if (/\bcol-actions\b/.test(classes)) {
        cle = "actions";
      } else if (/\bcol-date\b/.test(classes)) {
        cle = "date";
      }

      return { libelle, cle };
    });

    if (entetes.length === 0 || entetes.every((entete) => entete.libelle === "")) {
      return;
    }

    table.querySelectorAll("tbody tr").forEach((ligne) => {
      const cellules = Array.from(ligne.children).filter(
        (element) => element instanceof HTMLTableCellElement && element.tagName === "TD"
      );

      cellules.forEach((cellule, index) => {
        if (cellule.hasAttribute("colspan")) {
          cellule.removeAttribute("data-label");
          return;
        }

        const entete = entetes[index] || null;
        if (!entete || entete.libelle === "") {
          cellule.removeAttribute("data-label");
          cellule.removeAttribute("data-col");
          cellule.style.removeProperty("order");
          return;
        }

        const { libelle, cle } = entete;
        if (libelle !== "") {
          if (cellule.getAttribute("data-label") !== libelle) {
            cellule.setAttribute("data-label", libelle);
          }
          if (cellule.getAttribute("data-col") !== cle) {
            cellule.setAttribute("data-col", cle);
          }
          const orderValue = String(prioriteMobileColonne(cle));
          if (cellule.style.order !== orderValue) {
            cellule.style.order = orderValue;
          }
        }
      });
    });
  }

  function initialiserTableauxMobile() {
    const tables = document.querySelectorAll(".table-container table, .users-table table");
    if (tables.length === 0) {
      return;
    }

    tables.forEach((table) => {
      if (!(table instanceof HTMLTableElement)) {
        return;
      }

      appliquerEtiquettesColonnes(table);
    });
  }

  function lireValeurFiltreActif(bouton) {
    if (!(bouton instanceof HTMLElement)) {
      return "";
    }

    const candidats = [
      bouton.getAttribute("data-contact-filter"),
      bouton.getAttribute("data-users-filter"),
      bouton.getAttribute("data-filter"),
      bouton.getAttribute("data-comments-filter"),
    ];

    for (const valeur of candidats) {
      const texte = String(valeur || "").trim();
      if (texte !== "") {
        return texte.toLowerCase();
      }
    }

    return "";
  }

  function filtreLocalActif() {
    const rechercheActive = Array.from(document.querySelectorAll("input[data-search-target]")).some(
      (input) => input instanceof HTMLInputElement && input.value.trim() !== ""
    );

    if (rechercheActive) {
      return true;
    }

    const filtresActifs = Array.from(
      document.querySelectorAll(".filters .filter-btn.active:not([data-server-filter='1'])")
    )
      .map((btn) => lireValeurFiltreActif(btn))
      .filter((valeur) => valeur !== "");

    return filtresActifs.some((valeur) => valeur !== "all" && valeur !== "tous");
  }

  function recupererLignesTableau() {
    const champRecherche = document.querySelector("input[data-search-target]");
    if (champRecherche instanceof HTMLInputElement) {
      const cible = String(champRecherche.dataset.searchTarget || "").trim();
      if (cible !== "") {
        const lignesCible = Array.from(document.querySelectorAll(cible)).filter(
          (ligne) => ligne instanceof HTMLTableRowElement
        );
        if (lignesCible.length > 0) {
          return lignesCible;
        }
      }
    }

    const lignes = Array.from(
      document.querySelectorAll(".users-table tbody tr, .table-container tbody tr")
    ).filter((ligne) => ligne instanceof HTMLTableRowElement);

    return Array.from(new Set(lignes));
  }

  function estLigneVisible(ligne) {
    if (!(ligne instanceof HTMLTableRowElement)) {
      return false;
    }

    if (ligne.classList.contains("hidden")) {
      return false;
    }

    const style = window.getComputedStyle(ligne);
    return style.display !== "none" && style.visibility !== "hidden";
  }

  function obtenirComptageResultats() {
    const lignes = recupererLignesTableau();
    if (lignes.length === 0) {
      return { visibles: 0, total: 0 };
    }

    const visibles = lignes.filter(estLigneVisible).length;
    return { visibles, total: lignes.length };
  }

  function formaterLibelleResultats(nombre) {
    const total = Math.max(0, Number(nombre) || 0);
    return `${total} résultat${total > 1 ? "s" : ""}`;
  }

  function obtenirTableContainerPrincipal() {
    const conteneur = document.querySelector(".table-container");
    return conteneur instanceof HTMLElement ? conteneur : null;
  }

  function synchroniserMessageAucunResultatLocal(filtreActif, comptage) {
    const tableContainer = obtenirTableContainerPrincipal();
    if (!tableContainer) {
      return;
    }

    const messageExistant = tableContainer.querySelector(".admin-no-results");
    const lignes = recupererLignesTableau();
    const doitAfficher =
      Boolean(filtreActif) && lignes.length > 0 && (comptage?.visibles ?? 0) === 0;

    if (!doitAfficher) {
      if (messageExistant instanceof HTMLElement) {
        messageExistant.remove();
      }
      tableContainer.classList.remove("has-local-no-results");
      return;
    }

    tableContainer.classList.add("has-local-no-results");

    if (messageExistant instanceof HTMLElement) {
      return;
    }

    const message = document.createElement("div");
    message.className = "admin-no-results";
    message.setAttribute("role", "status");
    message.setAttribute("aria-live", "polite");
    message.innerHTML =
      '<i class="fas fa-search" aria-hidden="true"></i><span>Aucun résultat pour les filtres actuels.</span>';

    const zoneTable = tableContainer.querySelector(".users-table");
    if (zoneTable instanceof HTMLElement) {
      zoneTable.insertAdjacentElement("afterend", message);
    } else {
      tableContainer.appendChild(message);
    }
  }

  function basculerPaginationLocale() {
    const paginations = document.querySelectorAll(".pagination-nav");
    const filtreActif = filtreLocalActif();
    const comptage = obtenirComptageResultats();

    if (paginations.length > 0) {
      paginations.forEach((pagination) => {
        const info = pagination.querySelector(".pagination-info");
        if (info instanceof HTMLElement && !pagination.dataset.infoOriginal) {
          pagination.dataset.infoOriginal = info.innerHTML;
        }

        if (filtreActif) {
          pagination.classList.add("pagination-mode-filtre");
          pagination.classList.remove("pagination-masquee");
          if (info instanceof HTMLElement) {
            info.innerHTML = `<span class="pagination-total">${formaterLibelleResultats(comptage.visibles)}</span>`;
          }
        } else {
          pagination.classList.remove("pagination-mode-filtre");
          if (info instanceof HTMLElement && pagination.dataset.infoOriginal) {
            info.innerHTML = pagination.dataset.infoOriginal;
          }
        }
      });
    }

    synchroniserMessageAucunResultatLocal(filtreActif, comptage);
  }

  function obtenirDashboardPrincipal() {
    const dashboard = document.querySelector(".dashboard");
    return dashboard instanceof HTMLElement ? dashboard : null;
  }

  function appliquerEtatChargementDashboard(actif) {
    const dashboard = obtenirDashboardPrincipal();
    if (!dashboard) {
      return;
    }

    dashboard.classList.toggle("is-loading", Boolean(actif));
    dashboard.setAttribute("aria-busy", actif ? "true" : "false");
  }

  function appliquerEtatChargementTables(actif) {
    document.querySelectorAll(".table-container, .users-table").forEach((bloc) => {
      if (bloc instanceof HTMLElement) {
        bloc.classList.toggle("is-loading", Boolean(actif));
      }
    });
  }

  function focaliserTitrePrincipal() {
    const titre = document.querySelector(".dashboard h1");
    if (!(titre instanceof HTMLElement)) {
      return;
    }

    if (!titre.hasAttribute("tabindex")) {
      titre.setAttribute("tabindex", "-1");
    }
    titre.focus({ preventScroll: true });
  }

  function initialiserPaginationLocale() {
    if (window.__adminPaginationLocaleBound === true) {
      return;
    }
    window.__adminPaginationLocaleBound = true;

    let timerMiseAJour = null;
    const planifierMiseAJour = () => {
      if (timerMiseAJour) {
        clearTimeout(timerMiseAJour);
      }
      timerMiseAJour = window.setTimeout(() => {
        window.requestAnimationFrame(basculerPaginationLocale);
      }, 240);
    };

    basculerPaginationLocale();

    document.addEventListener("input", (event) => {
      const cible = event.target instanceof Element ? event.target : null;
      if (!cible || !cible.matches("input[data-search-target]")) {
        return;
      }
      planifierMiseAJour();
    });

    document.addEventListener("click", (event) => {
      const bouton =
        event.target instanceof Element ? event.target.closest(".filters .filter-btn") : null;
      if (!bouton) {
        return;
      }
      if (bouton instanceof HTMLElement && bouton.dataset.serverFilter === "1") {
        return;
      }
      planifierMiseAJour();
    });

    document.addEventListener("admin:dashboard-replaced", () => {
      planifierMiseAJour();
    });
  }

  function synchroniserScriptsDonnees(documentReponse) {
    const ids = ["donneesArticles", "donneesProjets", "donneesVideos", "donneesCorbeille"];
    ids.forEach((id) => {
      const courant = document.getElementById(id);
      const suivant = documentReponse.getElementById(id);

      if (courant && suivant) {
        courant.textContent = suivant.textContent;
      }
    });
  }

  function synchroniserMetaCsrf(documentReponse) {
    const courant = document.querySelector('meta[name="csrf-token"]');
    const suivant = documentReponse.querySelector('meta[name="csrf-token"]');

    if (!(suivant instanceof HTMLMetaElement)) {
      return;
    }

    const token = String(suivant.getAttribute("content") || "").trim();
    if (token === "") {
      return;
    }

    document.querySelectorAll('input[name="csrf_token"]').forEach((champ) => {
      if (champ instanceof HTMLInputElement) {
        champ.value = token;
      }
    });

    if (courant instanceof HTMLMetaElement) {
      courant.setAttribute("content", token);
      return;
    }

    document.head.appendChild(suivant.cloneNode(true));
  }

  function remplacerDashboardDepuisReponse(documentReponse) {
    const dashboardCourant = document.querySelector(".dashboard");
    const dashboardSuivant = documentReponse.querySelector(".dashboard");

    if (!dashboardCourant || !dashboardSuivant) {
      return false;
    }

    dashboardCourant.outerHTML = dashboardSuivant.outerHTML;
    synchroniserScriptsDonnees(documentReponse);
    synchroniserMetaCsrf(documentReponse);
    initialiserRecherche();
    initialiserTableauxMobile();
    basculerPaginationLocale();
    afficherFlashAdmin();
    focaliserTitrePrincipal();
    document.dispatchEvent(new CustomEvent("admin:dashboard-replaced"));
    return true;
  }

  function remonterEnHautContenuAdmin() {
    const dashboard = document.querySelector(".dashboard");
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

  let requetePaginationAjaxEnCours = false;

  function capturerEtatFiltresLocaux() {
    const recherches = Array.from(document.querySelectorAll("input[data-search-target]"))
      .map((input, index) => {
        if (!(input instanceof HTMLInputElement)) {
          return null;
        }
        return { index, valeur: input.value };
      })
      .filter((item) => item !== null);

    const filtresActifs = Array.from(document.querySelectorAll(".filters"))
      .map((bloc, index) => {
        if (!(bloc instanceof HTMLElement)) {
          return null;
        }
        if (bloc.dataset.serverFiltering === "1") {
          return null;
        }
        const actif = bloc.querySelector(".filter-btn.active");
        if (!(actif instanceof HTMLElement)) {
          return null;
        }
        const valeur = lireValeurFiltreActif(actif);
        if (valeur === "") {
          return null;
        }
        return { index, valeur };
      })
      .filter((item) => item !== null);

    return { recherches, filtresActifs };
  }

  function restaurerEtatFiltresLocaux(etat) {
    if (!etat || typeof etat !== "object") {
      return;
    }

    if (Array.isArray(etat.recherches)) {
      const champs = Array.from(document.querySelectorAll("input[data-search-target]"));
      etat.recherches.forEach((item) => {
        if (!item || typeof item.index !== "number") {
          return;
        }
        const champ = champs[item.index];
        if (!(champ instanceof HTMLInputElement)) {
          return;
        }
        const valeur = String(item.valeur || "");
        if (champ.value === valeur) {
          return;
        }
        champ.value = valeur;
        champ.dispatchEvent(new Event("input", { bubbles: true }));
      });
    }

    if (Array.isArray(etat.filtresActifs)) {
      const blocs = Array.from(document.querySelectorAll(".filters"));
      etat.filtresActifs.forEach((item) => {
        if (!item || typeof item.index !== "number") {
          return;
        }
        const bloc = blocs[item.index];
        if (!(bloc instanceof HTMLElement)) {
          return;
        }
        if (bloc.dataset.serverFiltering === "1") {
          return;
        }
        const boutons = Array.from(bloc.querySelectorAll(".filter-btn"));
        const boutonCible = boutons.find(
          (btn) => btn instanceof HTMLElement && lireValeurFiltreActif(btn) === item.valeur
        );
        if (!(boutonCible instanceof HTMLElement)) {
          return;
        }
        if (!boutonCible.classList.contains("active")) {
          boutonCible.click();
        }
      });
    }
  }

  function compterLignesTableauxAdmin() {
    const lignes = Array.from(
      document.querySelectorAll(".users-table tbody tr, .table-container tbody tr")
    ).filter((ligne) => {
      if (!(ligne instanceof HTMLTableRowElement)) {
        return false;
      }

      const cellules = Array.from(ligne.children).filter(
        (element) => element instanceof HTMLTableCellElement && element.tagName === "TD"
      );

      if (cellules.length === 0) {
        return false;
      }

      const lignePlaceholder = cellules.some((cellule) => cellule.hasAttribute("colspan"));
      return !lignePlaceholder;
    });

    return lignes.length;
  }

  function numeroPageCourante() {
    const url = new URL(window.location.href);
    const brute = Number.parseInt(String(url.searchParams.get("p") || "1"), 10);
    if (!Number.isFinite(brute) || brute < 1) {
      return 1;
    }
    return brute;
  }

  async function chargerDashboardAdminParAjax(url, options = {}) {
    if (!document.querySelector(".admin-container")) {
      return false;
    }

    const {
      pushState = true,
      scrollToTop = false,
      preserveLocalState = false,
      showErrorToast = true,
    } = options;

    if (requetePaginationAjaxEnCours) {
      return false;
    }
    requetePaginationAjaxEnCours = true;

    const etatLocal = preserveLocalState ? capturerEtatFiltresLocaux() : null;
    const urlCible = new URL(url.toString(), window.location.href);
    urlCible.searchParams.delete("ajax");
    const urlRequete = new URL(urlCible.toString());
    urlRequete.searchParams.set("_ts", String(Date.now()));
    const abortController = new AbortController();
    const timeoutId = window.setTimeout(() => abortController.abort(), 15000);

    appliquerEtatChargementDashboard(true);
    appliquerEtatChargementTables(true);

    try {
      const response = await fetch(urlRequete.toString(), {
        method: "GET",
        cache: "no-store",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
        signal: abortController.signal,
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const html = await response.text();
      const parser = new DOMParser();
      const documentReponse = parser.parseFromString(html, "text/html");

      const remplace = remplacerDashboardDepuisReponse(documentReponse);
      if (!remplace) {
        window.location.href = urlCible.toString();
        return false;
      }

      if (etatLocal) {
        restaurerEtatFiltresLocaux(etatLocal);
      }

      if (pushState) {
        window.history.pushState({}, "", `${urlCible.pathname}${urlCible.search}`);
      }

      if (scrollToTop) {
        remonterEnHautContenuAdmin();
      }

      return true;
    } catch (error) {
      console.error("Erreur AJAX dashboard admin:", error);
      const estTimeout = Boolean(error && error.name === "AbortError");
      if (
        showErrorToast &&
        typeof ToastNotification !== "undefined" &&
        typeof ToastNotification.erreur === "function"
      ) {
        ToastNotification.erreur(
          estTimeout
            ? "Le chargement a expiré. Vérifiez votre serveur puis réessayez."
            : "Impossible de charger la page demandée."
        );
      }
      return false;
    } finally {
      window.clearTimeout(timeoutId);
      requetePaginationAjaxEnCours = false;
      const paginationFinale = document.querySelector(".pagination-nav");
      if (paginationFinale instanceof HTMLElement) {
        paginationFinale.classList.remove("is-loading");
      }
      appliquerEtatChargementDashboard(false);
      appliquerEtatChargementTables(false);
    }
  }

  async function rafraichirDashboardAdminCourant(options = {}) {
    const {
      scrollToTop = false,
      preserveLocalState = true,
      pushState = false,
      showErrorToast = true,
    } = options;

    return chargerDashboardAdminParAjax(window.location.href, {
      pushState,
      scrollToTop,
      preserveLocalState,
      showErrorToast,
    });
  }

  async function rafraichirDashboardApresSuppression(options = {}) {
    const {
      deletedRows = 1,
      scrollToTop = false,
      preserveLocalState = true,
      showErrorToast = true,
    } = options;

    const urlBase = new URL(window.location.href);
    const pageActuelle = numeroPageCourante();
    const lignesAvantRefresh = compterLignesTableauxAdmin();
    const seuil = Math.max(1, Number.parseInt(String(deletedRows), 10) || 1);
    let pageCible = pageActuelle;

    if (pageActuelle > 1 && lignesAvantRefresh <= seuil) {
      pageCible = pageActuelle - 1;
    }

    const pagesTeste = new Set();

    while (pageCible >= 1) {
      const urlCible = new URL(urlBase.toString());
      if (pageCible <= 1) {
        urlCible.searchParams.delete("p");
      } else {
        urlCible.searchParams.set("p", String(pageCible));
      }

      const cleTest = `${urlCible.pathname}?${urlCible.searchParams.toString()}`;
      if (pagesTeste.has(cleTest)) {
        break;
      }
      pagesTeste.add(cleTest);

      const ok = await chargerDashboardAdminParAjax(urlCible, {
        pushState: true,
        scrollToTop,
        preserveLocalState,
        showErrorToast,
      });

      if (!ok) {
        return false;
      }

      const lignesApresRefresh = compterLignesTableauxAdmin();
      if (lignesApresRefresh > 0 || pageCible <= 1) {
        return true;
      }

      pageCible -= 1;
    }

    return false;
  }

  function exposerApiDashboardAjax() {
    function naviguerVers(url) {
      const cible = new URL(url.toString(), window.location.href);
      window.location.assign(`${cible.pathname}${cible.search}`);
      return true;
    }

    function naviguerApresSuppression(options = {}) {
      const deletedRows = Math.max(1, Number.parseInt(String(options.deletedRows ?? 1), 10) || 1);
      const urlBase = new URL(window.location.href);
      const pageActuelle = numeroPageCourante();
      const lignesAvantRefresh = compterLignesTableauxAdmin();
      let pageCible = pageActuelle;

      if (pageActuelle > 1 && lignesAvantRefresh <= deletedRows) {
        pageCible = pageActuelle - 1;
      }

      if (pageCible <= 1) {
        urlBase.searchParams.delete("p");
      } else {
        urlBase.searchParams.set("p", String(pageCible));
      }

      return naviguerVers(urlBase);
    }

    window.AdminDashboardAjax = {
      refreshCurrent: async (options = {}) => {
        if (!ADMIN_AJAX_MODE) {
          return naviguerVers(window.location.href);
        }
        const ok = await rafraichirDashboardAdminCourant(options);
        if (!ok) {
          return naviguerVers(window.location.href);
        }
        return true;
      },
      refreshAfterDelete: async (options = {}) => {
        if (!ADMIN_AJAX_MODE) {
          return naviguerApresSuppression(options);
        }
        const ok = await rafraichirDashboardApresSuppression(options);
        if (!ok) {
          return naviguerApresSuppression(options);
        }
        return true;
      },
      load: async (url, options = {}) => {
        if (!ADMIN_AJAX_MODE) {
          return naviguerVers(url);
        }
        const ok = await chargerDashboardAdminParAjax(url, options);
        if (!ok) {
          return naviguerVers(url);
        }
        return true;
      },
    };
  }

  exposerApiDashboardAjax();

  function initialiserPaginationAjaxAdmin() {
    if (!ADMIN_AJAX_MODE) {
      return;
    }

    if (!document.querySelector(".admin-container")) {
      return;
    }

    if (window.__adminPaginationAjaxBound === true) {
      return;
    }
    window.__adminPaginationAjaxBound = true;

    document.addEventListener("click", async (event) => {
      if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
      }

      const lienPagination =
        event.target instanceof Element ? event.target.closest(".pagination-nav a") : null;

      if (!(lienPagination instanceof HTMLAnchorElement)) {
        return;
      }

      const href = String(lienPagination.getAttribute("href") || "").trim();
      if (href === "") {
        return;
      }

      event.preventDefault();

      const url = new URL(href, window.location.href);

      const pagination = lienPagination.closest(".pagination-nav");
      if (pagination instanceof HTMLElement) {
        pagination.classList.add("is-loading");
      }
      const ok = await chargerDashboardAdminParAjax(url, {
        pushState: true,
        scrollToTop: true,
        preserveLocalState: false,
        showErrorToast: true,
      });
      if (!ok) {
        window.location.assign(`${url.pathname}${url.search}`);
      }
    });
  }

  function initialiserRaccourcisRechercheAdmin() {
    document.addEventListener("keydown", (event) => {
      const cible = event.target instanceof HTMLElement ? event.target : null;
      const cibleEditable = Boolean(
        cible && (cible.matches("input, textarea, select") || cible.isContentEditable)
      );

      if (
        event.key === "/" &&
        !event.ctrlKey &&
        !event.metaKey &&
        !event.altKey &&
        !cibleEditable
      ) {
        const champRecherche = document.querySelector(
          "input[data-search-target], input#champRecherche"
        );
        if (champRecherche instanceof HTMLInputElement) {
          event.preventDefault();
          champRecherche.focus();
          champRecherche.select();
        }
      }

      if (
        event.key === "Escape" &&
        cible instanceof HTMLInputElement &&
        cible.matches("input[data-search-target], input#champRecherche")
      ) {
        if (cible.value.trim() === "") {
          return;
        }
        cible.value = "";
        cible.dispatchEvent(new Event("input", { bubbles: true }));
      }
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    initialiserRecherche();
    initialiserTableauxMobile();
    initialiserPaginationLocale();
    initialiserPaginationAjaxAdmin();
    initialiserRaccourcisRechercheAdmin();
    afficherFlashAdmin();

    exposerApiDashboardAjax();
  });

  initialiserSoumissionFormulairesDepuisLiens();
})();
