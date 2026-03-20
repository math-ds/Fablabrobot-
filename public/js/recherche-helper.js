const RechercheHelper = {
  creerDebounce: function (callback, delai = 300) {
    let timeoutId = null;

    return (...args) => {
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
      timeoutId = setTimeout(() => callback(...args), delai);
    };
  },

  mettreAJourUrl: function (page, filtres = {}) {
    const params = new URLSearchParams({ page });

    Object.entries(filtres).forEach(([key, value]) => {
      const texte = String(value ?? "").trim();
      if (texte !== "" && texte !== "all") {
        params.set(key, texte);
      }
    });

    window.history.replaceState({}, "", `?${params.toString()}`);
  },

  initialiser: function (inputId, tableauSelector, delai = 300) {
    const champRecherche = document.getElementById(inputId);
    if (!champRecherche) return;

    const handler = this.creerDebounce(() => {
      RechercheHelper.filtrer(champRecherche.value, tableauSelector);
    }, delai);

    champRecherche.addEventListener("input", handler);
  },

  filtrer: function (valeur, tableauSelector) {
    const recherche = valeur.toLowerCase().trim();
    const lignes = document.querySelectorAll(tableauSelector);
    let compteur = 0;

    const getActiveFilterValue = () => {
      const active = document.querySelector(".filters .filter-btn.active");
      if (!(active instanceof HTMLElement)) {
        return "";
      }

      const candidates = [
        active.getAttribute("data-contact-filter"),
        active.getAttribute("data-users-filter"),
        active.getAttribute("data-filter"),
        active.getAttribute("data-comments-filter"),
      ];

      for (const value of candidates) {
        const normalized = String(value || "")
          .trim()
          .toLowerCase();
        if (normalized !== "") {
          return normalized;
        }
      }

      return "";
    };

    const lineMatchesActiveFilter = (ligne, filtreActif) => {
      if (!(ligne instanceof HTMLElement)) {
        return false;
      }

      if (filtreActif === "" || filtreActif === "all" || filtreActif === "tous") {
        return true;
      }

      const candidates = [
        String(ligne.dataset.statut || "").toLowerCase(),
        String(ligne.dataset.role || "").toLowerCase(),
        String(ligne.dataset.type || "").toLowerCase(),
      ];

      return candidates.includes(filtreActif);
    };

    const filtreActif = getActiveFilterValue();

    lignes.forEach((ligne) => {
      if (!(ligne instanceof HTMLElement)) {
        return;
      }

      if (ligne.classList.contains("hidden")) {
        ligne.style.display = "none";
        ligne.style.opacity = "0";
        return;
      }

      const texte = ligne.textContent.toLowerCase();
      const correspondRecherche = texte.includes(recherche);
      const correspondFiltre = lineMatchesActiveFilter(ligne, filtreActif);
      const correspond = correspondRecherche && correspondFiltre;

      if (correspond) {
        ligne.style.display = "";
        ligne.style.opacity = "0";
        setTimeout(() => {
          ligne.style.transition = "opacity 0.2s";
          ligne.style.opacity = "1";
        }, 10);
        compteur++;
      } else {
        ligne.style.transition = "opacity 0.2s";
        ligne.style.opacity = "0";
        setTimeout(() => {
          ligne.style.display = "none";
        }, 200);
      }
    });

    RechercheHelper.afficherCompteur(compteur, lignes.length, recherche);
  },

  afficherCompteur: function (trouve, total, recherche) {
    let compteur = document.getElementById("compteur-recherche");

    if (!compteur) {
      compteur = document.createElement("div");
      compteur.id = "compteur-recherche";
      compteur.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: rgba(0, 175, 167, 0.95);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        z-index: 9998;
        transition: all 0.3s;
      `;
      document.body.appendChild(compteur);
    }

    if (recherche === "") {
      compteur.style.opacity = "0";
      compteur.style.transform = "translateY(20px)";
      setTimeout(() => {
        if (compteur.parentNode) {
          compteur.remove();
        }
      }, 300);
    } else {
      compteur.style.opacity = "1";
      compteur.style.transform = "translateY(0)";

      if (trouve === 0) {
        compteur.style.background = "rgba(255, 107, 107, 0.95)";
        compteur.innerHTML = `<i class="fas fa-search"></i> Aucun résultat pour "${recherche}"`;
      } else {
        compteur.style.background = "rgba(0, 175, 167, 0.95)";
        compteur.innerHTML = `<i class="fas fa-check-circle"></i> ${trouve} résultat${trouve > 1 ? "s" : ""} sur ${total}`;
      }
    }
  },

  formaterCompteurInline: function (trouve, total, options = {}) {
    const shown = Math.max(0, Number(trouve) || 0);
    const grandTotal = Math.max(shown, Number(total) || 0);
    const unite = String(options.unite || "résultat");
    const hasFilters = Boolean(options.hasFilters);
    const plurielShown = shown > 1 ? "s" : "";
    const plurielTotal = grandTotal > 1 ? "s" : "";

    if (shown === 0 || grandTotal === 0) {
      return {
        empty: true,
        html: '<i class="fas fa-search"></i> <strong>Aucun résultat</strong>',
      };
    }

    if (hasFilters || grandTotal > shown) {
      return {
        empty: false,
        html: `<i class="fas fa-check-circle"></i> <strong>${shown} ${unite}${plurielShown}</strong> sur ${grandTotal} ${unite}${plurielTotal}`,
      };
    }

    return {
      empty: false,
      html: `<i class="fas fa-check-circle"></i> <strong>${shown} ${unite}${plurielShown}</strong>`,
    };
  },

  afficherCompteurInline: function (cible, trouve, total, options = {}) {
    const element = typeof cible === "string" ? document.getElementById(cible) : cible;
    if (!element) {
      return;
    }

    const rendu = this.formaterCompteurInline(trouve, total, options);
    element.classList.add("catalog-results-counter");
    element.classList.toggle("is-empty", Boolean(rendu.empty));
    element.innerHTML = rendu.html;
  },
};
