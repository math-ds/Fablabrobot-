(() => {
  if (window.__corbeilleAdminBound === true) {
    return;
  }
  window.__corbeilleAdminBound = true;

  const ETIQUETTES_TYPE = {
    article: { color: "blue", icon: "fa-newspaper", label: "Article" },
    actualite: { color: "blue", icon: "fa-rss", label: "Actualité" },
    projet: { color: "green", icon: "fa-project-diagram", label: "Projet" },
    video: { color: "red", icon: "fa-video", label: "Video" },
    utilisateur: { color: "purple", icon: "fa-user", label: "Utilisateur" },
    message: { color: "orange", icon: "fa-envelope", label: "Message" },
  };

  let actionEnAttente = null;
  let rechercheTimer = null;

  function pageCorbeilleActive() {
    return Boolean(document.querySelector("[data-admin-corbeille='1']"));
  }

  function afficherToast(message, type = "success") {
    const texte = String(message || "").trim();
    if (texte === "") {
      return;
    }

    if (typeof ToastNotification !== "undefined") {
      const niveau = String(type || "info").toLowerCase();
      if (niveau === "success") {
        ToastNotification.succes(texte);
        return;
      }
      if (niveau === "warning") {
        ToastNotification.avertissement(texte);
        return;
      }
      if (niveau === "error" || niveau === "danger") {
        ToastNotification.erreur(texte);
        return;
      }
      ToastNotification.info(texte);
      return;
    }

    window.alert(texte);
  }

  function lireDonneesCorbeille() {
    const node = document.getElementById("donneesCorbeille");
    if (!node) {
      return [];
    }

    try {
      const data = JSON.parse(node.textContent || "[]");
      return Array.isArray(data) ? data : [];
    } catch (error) {
      return [];
    }
  }

  function trouverElement(id, type) {
    const idTexte = String(id || "").trim();
    const typeTexte = String(type || "").trim();
    if (idTexte === "" || typeTexte === "") {
      return null;
    }

    const donnees = lireDonneesCorbeille();
    return (
      donnees.find((item) => String(item.id) === idTexte && String(item.type) === typeTexte) || null
    );
  }

  function trouverLigne(id, type) {
    const lignes = document.querySelectorAll("#tableauCorbeille tbody tr[data-id][data-type]");
    for (const ligne of lignes) {
      if (!(ligne instanceof HTMLTableRowElement)) {
        continue;
      }
      if (
        String(ligne.dataset.id || "") === String(id) &&
        String(ligne.dataset.type || "") === String(type)
      ) {
        return ligne;
      }
    }
    return null;
  }

  function fermerModale(idModale) {
    const modale = document.getElementById(idModale);
    if (modale instanceof HTMLElement) {
      modale.classList.remove("active");
    }
  }

  function ouvrirModaleRestauration(element) {
    const badge = ETIQUETTES_TYPE[element.type] || {
      color: "gray",
      icon: "fa-question",
      label: "Inconnu",
    };

    const nodeType = document.getElementById("restoreElementType");
    const nodeTitre = document.getElementById("restoreElementTitle");
    const nodeDescription = document.getElementById("restoreElementDescription");
    const nodeDate = document.getElementById("restoreElementDate");

    if (nodeType) {
      nodeType.className = `element-type badge-${badge.color}`;
      nodeType.innerHTML = `<i class="fas ${badge.icon}"></i> ${badge.label}`;
    }

    if (nodeTitre) {
      let nom = "";
      switch (element.type) {
        case "article":
        case "actualite":
        case "projet":
        case "video":
          nom = element.titre || element.title || "Sans titre";
          break;
        case "utilisateur":
          nom = element.nom || "Sans nom";
          break;
        case "message":
          nom = `${element.nom || ""} - ${element.sujet || ""}`.trim();
          break;
        default:
          nom = "Element";
      }
      nodeTitre.textContent = nom;
    }

    if (nodeDescription) {
      let description = "";
      switch (element.type) {
        case "article":
        case "actualite":
        case "projet":
        case "video":
          description = element.description || "";
          break;
        case "utilisateur":
          description = element.email || "";
          break;
        case "message":
          description = element.message || "";
          break;
        default:
          description = "";
      }
      nodeDescription.textContent = description;
    }

    if (nodeDate) {
      const date = new Date(String(element.deleted_at || ""));
      if (!Number.isNaN(date.getTime())) {
        nodeDate.textContent = date.toLocaleDateString("fr-FR", {
          year: "numeric",
          month: "long",
          day: "numeric",
          hour: "2-digit",
          minute: "2-digit",
        });
      } else {
        nodeDate.textContent = "-";
      }
    }

    const modale = document.getElementById("restoreModal");
    if (modale instanceof HTMLElement) {
      modale.classList.add("active");
    }
  }

  async function postCorbeilleAction(action, payload = {}) {
    if (typeof AjaxHelper === "undefined" || typeof AjaxHelper.post !== "function") {
      throw new Error("AjaxHelper indisponible.");
    }

    const data = await AjaxHelper.post(
      `?page=admin-corbeille&action=${encodeURIComponent(action)}`,
      payload
    );

    if (!data?.success) {
      throw new Error(String(data?.message || "Erreur serveur."));
    }
    return data;
  }

  function filtreCorbeilleActif() {
    const actif = document.querySelector(".filter-btn.active");
    if (!(actif instanceof HTMLElement)) {
      return "tous";
    }
    const filtre = String(actif.dataset.filter || "tous")
      .trim()
      .toLowerCase();
    return filtre === "" ? "tous" : filtre;
  }

  function rechercheCorbeilleActuelle() {
    return String(document.getElementById("champRecherche")?.value || "").trim();
  }

  function construireUrlCorbeille(type = "tous", q = "", page = 1) {
    const url = new URL(window.location.href);
    url.searchParams.set("page", "admin-corbeille");
    url.searchParams.delete("action");

    const typeNormalise = String(type || "tous")
      .trim()
      .toLowerCase();
    if (typeNormalise === "" || typeNormalise === "tous") {
      url.searchParams.delete("type");
    } else {
      url.searchParams.set("type", typeNormalise);
    }

    const recherche = String(q || "").trim();
    if (recherche === "") {
      url.searchParams.delete("q");
    } else {
      url.searchParams.set("q", recherche);
    }

    const pageNormalisee = Number.parseInt(String(page ?? 1), 10);
    if (!Number.isFinite(pageNormalisee) || pageNormalisee <= 1) {
      url.searchParams.delete("p");
    } else {
      url.searchParams.set("p", String(pageNormalisee));
    }

    return url;
  }

  async function chargerCorbeilleFiltre(type = "tous", q = "", page = 1, options = {}) {
    const url = construireUrlCorbeille(type, q, page);
    const { pushState = true, scrollToTop = false, preserveLocalState = false } = options;
    if (window.AdminDashboardAjax && typeof window.AdminDashboardAjax.load === "function") {
      await window.AdminDashboardAjax.load(url, {
        pushState,
        scrollToTop,
        preserveLocalState,
        showErrorToast: true,
      });
      return;
    }

    window.location.href = url.toString();
  }

  function animerLignes() {
    if (!pageCorbeilleActive()) {
      return;
    }

    const rows = document.querySelectorAll("#tableauCorbeille tbody tr");
    rows.forEach((row, index) => {
      if (!(row instanceof HTMLElement) || row.dataset.animated === "1") {
        return;
      }
      row.dataset.animated = "1";
      row.style.opacity = "0";
      row.style.transform = "translateY(14px)";
      setTimeout(() => {
        row.style.transition = "opacity 0.24s ease, transform 0.24s ease";
        row.style.opacity = "1";
        row.style.transform = "translateY(0)";
      }, index * 35);
    });
  }

  async function traiterSuppressionLigne(action, id, type) {
    const result = await postCorbeilleAction(action, { id, type });

    afficherToast(result.message || "Action effectuee.", "success");

    if (
      window.AdminDashboardAjax &&
      typeof window.AdminDashboardAjax.refreshAfterDelete === "function"
    ) {
      const rafraichi = await window.AdminDashboardAjax.refreshAfterDelete({
        deletedRows: 1,
        preserveLocalState: true,
        scrollToTop: false,
      });
      if (!rafraichi) {
        window.location.reload();
      }
    } else {
      window.location.reload();
    }
  }

  document.addEventListener("click", async (event) => {
    if (!pageCorbeilleActive()) {
      return;
    }

    const cible = event.target instanceof Element ? event.target : null;
    if (!cible) {
      return;
    }

    const boutonFermer = cible.closest("[data-corbeille-close]");
    if (boutonFermer instanceof HTMLElement) {
      event.preventDefault();
      const idModale = String(boutonFermer.getAttribute("data-corbeille-close") || "").trim();
      if (idModale !== "") {
        fermerModale(idModale);
      }
      return;
    }

    if (cible.classList.contains("corbeille-modal")) {
      cible.classList.remove("active");
      return;
    }

    const boutonFiltre = cible.closest(".filter-btn");
    if (boutonFiltre instanceof HTMLButtonElement) {
      event.preventDefault();
      document.querySelectorAll(".filter-btn").forEach((btn) => btn.classList.remove("active"));
      boutonFiltre.classList.add("active");
      const type = String(boutonFiltre.dataset.filter || "tous");
      await chargerCorbeilleFiltre(type, rechercheCorbeilleActuelle(), 1, {
        pushState: true,
        scrollToTop: false,
        preserveLocalState: false,
      });
      return;
    }

    const boutonRestore = cible.closest(".btn-restore");
    if (boutonRestore instanceof HTMLButtonElement) {
      event.preventDefault();
      const id = String(boutonRestore.dataset.id || "");
      const type = String(boutonRestore.dataset.type || "");
      const element = trouverElement(id, type);

      if (!element) {
        afficherToast("Element introuvable.", "error");
        return;
      }

      actionEnAttente = { id, type };
      ouvrirModaleRestauration(element);
      return;
    }

    const boutonConfirmRestore = cible.closest("#confirmRestoreBtn");
    if (boutonConfirmRestore instanceof HTMLButtonElement) {
      event.preventDefault();
      fermerModale("restoreModal");
      if (!actionEnAttente) {
        afficherToast("Aucun element selectionne.", "error");
        return;
      }

      try {
        await traiterSuppressionLigne("restaurer", actionEnAttente.id, actionEnAttente.type);
      } catch (error) {
        afficherToast(
          error instanceof Error ? error.message : "Erreur lors de la restauration.",
          "error"
        );
      } finally {
        actionEnAttente = null;
      }
      return;
    }

    const boutonDelete = cible.closest(".btn-delete-permanent");
    if (boutonDelete instanceof HTMLButtonElement) {
      event.preventDefault();
      const id = String(boutonDelete.dataset.id || "");
      const type = String(boutonDelete.dataset.type || "");

      if (
        !window.confirm("Supprimer definitivement cet element ? Cette action est irreversible.")
      ) {
        return;
      }

      try {
        await traiterSuppressionLigne("supprimer-definitivement", id, type);
      } catch (error) {
        afficherToast(
          error instanceof Error ? error.message : "Erreur lors de la suppression.",
          "error"
        );
      }
      return;
    }

    const boutonRestoreAll = cible.closest("#restaurerTousBtn");
    if (boutonRestoreAll instanceof HTMLButtonElement) {
      event.preventDefault();
      const totalBadge = document.querySelector(".stats-badge[data-corbeille-total]");
      const total =
        totalBadge instanceof HTMLElement
          ? Math.max(0, Number.parseInt(String(totalBadge.dataset.corbeilleTotal || "0"), 10) || 0)
          : document.querySelectorAll("#tableauCorbeille tbody tr").length;
      if (!window.confirm(`Restaurer tous les ${total} éléments de cette corbeille ?`)) {
        return;
      }

      try {
        const result = await postCorbeilleAction("restaurer-tous", {});
        afficherToast(result.message || "Éléments restaurés avec succès.", "success");
        if (
          window.AdminDashboardAjax &&
          typeof window.AdminDashboardAjax.refreshAfterDelete === "function"
        ) {
          const rafraichi = await window.AdminDashboardAjax.refreshAfterDelete({
            deletedRows: Math.max(1, total),
            preserveLocalState: true,
            scrollToTop: false,
          });
          if (!rafraichi) {
            window.location.reload();
          }
        } else if (
          window.AdminDashboardAjax &&
          typeof window.AdminDashboardAjax.refreshCurrent === "function"
        ) {
          const rafraichi = await window.AdminDashboardAjax.refreshCurrent({
            preserveLocalState: true,
            scrollToTop: false,
          });
          if (!rafraichi) {
            window.location.reload();
          }
        } else {
          window.location.reload();
        }
      } catch (error) {
        afficherToast(
          error instanceof Error ? error.message : "Erreur lors de la restauration globale.",
          "error"
        );
      }
      return;
    }

    const boutonEmpty = cible.closest("#viderCorbeilleBtn");
    if (boutonEmpty instanceof HTMLButtonElement) {
      event.preventDefault();
      const totalBadge = document.querySelector(".stats-badge[data-corbeille-total]");
      const total =
        totalBadge instanceof HTMLElement
          ? Math.max(0, Number.parseInt(String(totalBadge.dataset.corbeilleTotal || "0"), 10) || 0)
          : document.querySelectorAll("#tableauCorbeille tbody tr").length;
      if (!window.confirm(`Vider la corbeille (${total} éléments) ?`)) {
        return;
      }
      if (!window.confirm("Confirmation finale: action definitive.")) {
        return;
      }

      try {
        const result = await postCorbeilleAction("vider-corbeille", {});
        afficherToast(result.message || "Corbeille videe avec succes.", "success");
        if (
          window.AdminDashboardAjax &&
          typeof window.AdminDashboardAjax.refreshAfterDelete === "function"
        ) {
          const rafraichi = await window.AdminDashboardAjax.refreshAfterDelete({
            deletedRows: Math.max(1, total),
            preserveLocalState: true,
            scrollToTop: false,
          });
          if (!rafraichi) {
            window.location.reload();
          }
        } else if (
          window.AdminDashboardAjax &&
          typeof window.AdminDashboardAjax.refreshCurrent === "function"
        ) {
          const rafraichi = await window.AdminDashboardAjax.refreshCurrent({
            preserveLocalState: true,
            scrollToTop: false,
          });
          if (!rafraichi) {
            window.location.reload();
          }
        } else {
          window.location.reload();
        }
      } catch (error) {
        afficherToast(error instanceof Error ? error.message : "Erreur lors du vidage.", "error");
      }
    }
  });

  document.addEventListener("input", (event) => {
    if (!pageCorbeilleActive()) {
      return;
    }
    const input = event.target instanceof HTMLInputElement ? event.target : null;
    if (!input || input.id !== "champRecherche") {
      return;
    }
    if (rechercheTimer) {
      clearTimeout(rechercheTimer);
    }
    rechercheTimer = window.setTimeout(async () => {
      await chargerCorbeilleFiltre(filtreCorbeilleActif(), input.value, 1, {
        pushState: true,
        scrollToTop: false,
        preserveLocalState: false,
      });
    }, 320);
  });

  window.addEventListener("popstate", () => {
    if (!pageCorbeilleActive()) {
      return;
    }

    const url = new URL(window.location.href);
    const type = String(url.searchParams.get("type") || "tous")
      .trim()
      .toLowerCase();
    const q = String(url.searchParams.get("q") || "");
    const p = Number.parseInt(String(url.searchParams.get("p") || "1"), 10);

    void chargerCorbeilleFiltre(
      type === "" ? "tous" : type,
      q,
      Number.isFinite(p) && p > 0 ? p : 1,
      {
        pushState: false,
        scrollToTop: false,
        preserveLocalState: false,
      }
    );
  });

  document.addEventListener("DOMContentLoaded", () => {
    if (!pageCorbeilleActive()) {
      return;
    }
    animerLignes();
  });

  document.addEventListener("admin:dashboard-replaced", () => {
    if (!pageCorbeilleActive()) {
      return;
    }
    animerLignes();
  });
})();
