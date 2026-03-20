let imagePreviewInitialized = false;
let projetSearchTimer = null;

function estPageAdminProjets() {
  if (document.querySelector("[data-admin-projets='1']")) {
    return true;
  }
  const params = new URLSearchParams(window.location.search);
  return params.get("page") === "admin-projets";
}

function construireUrlAdminProjets(q = "", page = 1) {
  const url = new URL(window.location.href);
  url.searchParams.set("page", "admin-projets");
  url.searchParams.delete("action");

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

async function chargerProjetsAdmin(q = "", page = 1, options = {}) {
  const url = construireUrlAdminProjets(q, page);
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

document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("modaleProjet");
  const closeBtn = document.querySelector(".close-modal");
  const formulaire = document.getElementById("formulaireProjet");

  if (closeBtn) closeBtn.addEventListener("click", fermerModale);
  if (modal) window.addEventListener("click", (e) => e.target === modal && fermerModale());

  if (formulaire) {
    formulaire.addEventListener("submit", async function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const action = formData.get("action");
      const imageUrlValue = String(formData.get("image_url") || "").trim();
      const fileInput = this.querySelector("#imageFile");
      const hasLocalFile =
        fileInput instanceof HTMLInputElement &&
        fileInput.files !== null &&
        fileInput.files.length > 0;
      const boutonSoumettre = this.querySelector('button[type="submit"]');

      if (
        action === "update" &&
        !hasLocalFile &&
        imageUrlValue !== "" &&
        !isExternalImageUrl(imageUrlValue)
      ) {
        formData.set("image_url", "");
      }

      boutonSoumettre.disabled = true;
      boutonSoumettre.textContent = "Envoi en cours...";

      try {
        const data = await AjaxHelper.post("?page=admin-projets", formData);

        if (data.success) {
          ToastNotification.succes(data.message);
          fermerModale();

          if (
            window.AdminDashboardAjax &&
            typeof window.AdminDashboardAjax.refreshCurrent === "function"
          ) {
            await window.AdminDashboardAjax.refreshCurrent({
              preserveLocalState: false,
              scrollToTop: false,
            });
          } else {
            window.location.reload();
          }
        }
      } catch (error) {
        ToastNotification.erreur(error.data?.message || "Erreur lors de l'enregistrement");

        if (error.data?.error?.validation) {
          Object.values(error.data.error.validation).forEach((erreur) => {
            ToastNotification.erreur(erreur);
          });
        }
      } finally {
        boutonSoumettre.disabled = false;
        boutonSoumettre.textContent = action === "create" ? "Créer" : "Modifier";
      }
    });
  }

  document.addEventListener("click", (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) {
      return;
    }

    const openCreateBtn = target.closest("[data-projets-open-create]");
    if (openCreateBtn) {
      event.preventDefault();
      ouvrirModale("create");
      return;
    }

    const editBtn = target.closest("[data-projet-edit-id]");
    if (editBtn) {
      event.preventDefault();
      const id = Number.parseInt(String(editBtn.getAttribute("data-projet-edit-id") || "0"), 10);
      if (id > 0) {
        editerProjet(id);
      }
      return;
    }

    const deleteBtn = target.closest("[data-projet-delete-id]");
    if (deleteBtn) {
      event.preventDefault();
      const id = Number.parseInt(
        String(deleteBtn.getAttribute("data-projet-delete-id") || "0"),
        10
      );
      const titre = String(deleteBtn.getAttribute("data-projet-delete-title") || "ce projet");
      if (id > 0) {
        supprimerProjet(id, titre);
      }
      return;
    }

    const closeModalBtn = target.closest("[data-projet-close-modal]");
    if (closeModalBtn) {
      event.preventDefault();
      fermerModale();
    }
  });

  document.addEventListener("change", (event) => {
    const input = event.target instanceof HTMLInputElement ? event.target : null;
    if (!input || !input.matches("[data-projet-image-file-input]")) {
      return;
    }
    previewLocalImage(input);
  });

  document.addEventListener(
    "error",
    (event) => {
      const img = event.target instanceof HTMLImageElement ? event.target : null;
      if (!img) {
        return;
      }

      const originalSrc = String(img.getAttribute("data-proxy-src-on-error") || "");
      if (!originalSrc || img.dataset.proxyTried === "1") {
        return;
      }

      img.dataset.proxyTried = "1";
      if (typeof window.essayerImageProxy === "function") {
        window.essayerImageProxy(img, originalSrc);
      }
    },
    true
  );
});

document.addEventListener("input", (event) => {
  if (!estPageAdminProjets()) {
    return;
  }

  const input = event.target instanceof HTMLInputElement ? event.target : null;
  if (!input || input.id !== "champRecherche") {
    return;
  }

  if (projetSearchTimer) {
    clearTimeout(projetSearchTimer);
  }

  projetSearchTimer = window.setTimeout(() => {
    void chargerProjetsAdmin(input.value, 1, {
      pushState: true,
      scrollToTop: false,
      preserveLocalState: false,
    });
  }, 320);
});

window.addEventListener("popstate", () => {
  if (!estPageAdminProjets()) {
    return;
  }

  const url = new URL(window.location.href);
  const q = String(url.searchParams.get("q") || "");
  const p = Number.parseInt(String(url.searchParams.get("p") || "1"), 10);

  void chargerProjetsAdmin(q, Number.isFinite(p) && p > 0 ? p : 1, {
    pushState: false,
    scrollToTop: false,
    preserveLocalState: false,
  });
});

function ouvrirModale(action, data = null) {
  const modal = document.getElementById("modaleProjet");
  if (!modal) return;

  if (!imagePreviewInitialized) {
    ImagePreviewHelper.init({
      inputId: "image_url",
      containerId: "imagePreviewContainer",
      imgId: "imagePreview",
      spinnerId: "imageLoadingSpinner",
      errorId: "imagePreviewError",
      useProxy: true,
    });
    imagePreviewInitialized = true;
  }

  const titre = document.getElementById("titreModale");
  titre.textContent = action === "create" ? "Nouveau Projet" : "Modifier le Projet";

  const boutonSoumettre = document.querySelector('#formulaireProjet button[type="submit"]');
  if (boutonSoumettre) {
    boutonSoumettre.textContent = action === "create" ? "Créer" : "Modifier";
  }

  document.getElementById("actionFormulaire").value = action;
  document.getElementById("formulaireProjet").reset();
  document.getElementById("idProjet").value = "";
  const formulaireProjet = document.getElementById("formulaireProjet");
  if (formulaireProjet) {
    delete formulaireProjet.dataset.imageSourceOriginal;
  }

  ImagePreviewHelper.reset({
    containerId: "imagePreviewContainer",
    imgId: "imagePreview",
    errorId: "imagePreviewError",
  });

  const localPreviewContainer = document.getElementById("localPreviewContainer");
  if (localPreviewContainer) localPreviewContainer.style.display = "none";

  modal.classList.add("active");
}

function fermerModale() {
  const modal = document.getElementById("modaleProjet");
  if (modal) modal.classList.remove("active");
}

function editerProjet(id) {
  ouvrirModale("update");
  document.getElementById("idProjet").value = id;

  const donneesProjets = document.getElementById("donneesProjets");
  if (donneesProjets) {
    try {
      const projets = JSON.parse(donneesProjets.textContent);
      const projet = projets.find((p) => p.id == id);

      if (projet) {
        document.getElementById("title").value = projet.title || "";
        document.getElementById("description").value = projet.description || "";
        document.getElementById("description_detailed").value = projet.description_detailed || "";
        document.getElementById("technologies").value = projet.technologies || "";
        document.getElementById("features").value = projet.features || "";
        document.getElementById("challenges").value = projet.challenges || "";
        const imageSource = String(projet.image_url || "").trim();
        const imageSourceResolved = buildProjectImageUrl(imageSource);
        const imageUrlInput = document.getElementById("image_url");
        if (imageUrlInput) {
          imageUrlInput.value = imageSourceResolved;
        }
        const formulaireProjet = document.getElementById("formulaireProjet");
        if (formulaireProjet) {
          formulaireProjet.dataset.imageSourceOriginal = imageSourceResolved;
        }
        definirValeurSelectCategorieProjet(
          document.getElementById("categorieProjet"),
          projet.categorie || ""
        );

        if (imageSourceResolved) {
          ImagePreviewHelper.preview(imageSourceResolved, {
            containerId: "imagePreviewContainer",
            imgId: "imagePreview",
            spinnerId: "imageLoadingSpinner",
            errorId: "imagePreviewError",
            useProxy: true,
          });
        } else {
          ImagePreviewHelper.reset({
            containerId: "imagePreviewContainer",
            imgId: "imagePreview",
            errorId: "imagePreviewError",
          });
        }
      }
    } catch (e) {
      console.error("Erreur parsing JSON:", e);
    }
  }
}

async function supprimerProjet(id, titre) {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer "${titre}" ?`)) {
    return;
  }

  try {
    const data = await AjaxHelper.post("?page=admin-projets", {
      action: "delete",
      project_id: id,
    });

    if (data.success) {
      ToastNotification.succes(data.message || "Projet supprimé avec succès");

      if (
        window.AdminDashboardAjax &&
        typeof window.AdminDashboardAjax.refreshAfterDelete === "function"
      ) {
        await window.AdminDashboardAjax.refreshAfterDelete({
          deletedRows: 1,
          preserveLocalState: false,
          scrollToTop: false,
        });
      } else {
        window.location.reload();
      }
    }
  } catch (error) {
    ToastNotification.erreur(error.data?.message || "Erreur lors de la suppression");
  }
}

function rechercherProjets() {
  if (!estPageAdminProjets()) {
    return;
  }
  const valeur = String(document.getElementById("champRecherche")?.value || "");
  void chargerProjetsAdmin(valeur, 1);
}

function previewLocalImage(input) {
  ImagePreviewHelper.previewLocal(input, "localPreview", "localPreviewContainer");
}

function isExternalImageUrl(value) {
  return /^https?:\/\//i.test(String(value || "").trim());
}

function buildProjectImageUrl(imageSource) {
  const value = String(imageSource || "").trim();
  if (value === "") {
    return "";
  }

  if (/^https?:\/\//i.test(value)) {
    return value;
  }

  if (/^\/?images\//i.test(value)) {
    return value.replace(/^\/+/, "");
  }

  return `images/projets/${value.replace(/^\/+/, "")}`;
}

function definirValeurSelectCategorieProjet(selectElement, categorie) {
  if (!(selectElement instanceof HTMLSelectElement)) {
    return;
  }

  const valeur = String(categorie || "").trim();
  if (valeur === "") {
    selectElement.value = "";
    return;
  }

  const normalisee = valeur
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase();
  let canonique = valeur;

  if (normalisee.includes("lectronique")) {
    canonique = "Electronique";
  } else if (normalisee.includes("ecanique") || normalisee.includes("canique")) {
    canonique = "Mecanique";
  } else if (normalisee.includes("impression") && normalisee.includes("3d")) {
    canonique = "Impression 3D";
  } else if (normalisee.includes("drone")) {
    canonique = "Drone / FPV";
  } else if (normalisee.includes("robot")) {
    canonique = "Robotique";
  } else if (normalisee.includes("program")) {
    canonique = "Programmation";
  } else if (normalisee.includes("autre")) {
    canonique = "Autre";
  }

  const options = Array.from(selectElement.options);
  const optionExistante = options.find(
    (option) => String(option.value || "").trim().toLowerCase() === canonique.toLowerCase()
  );

  if (optionExistante) {
    selectElement.value = optionExistante.value;
    return;
  }

  const optionDynamique = selectElement.querySelector("option[data-dynamic-category='1']");
  if (optionDynamique) {
    optionDynamique.remove();
  }

  const option = document.createElement("option");
  option.value = canonique;
  option.textContent = canonique;
  option.setAttribute("data-dynamic-category", "1");
  selectElement.appendChild(option);
  selectElement.value = canonique;
}

function mettreAJourTableProjets(action, projet) {
  const tbody = document.querySelector("#tableauProjets tbody");
  const donneesProjets = document.getElementById("donneesProjets");

  if (action === "create" && projet) {
    const nouvelleLigne = creerLigneProjet(projet);
    tbody.insertBefore(nouvelleLigne, tbody.firstChild);

    if (donneesProjets) {
      try {
        const projets = JSON.parse(donneesProjets.textContent);
        projets.unshift(projet); // Ajouter au début
        donneesProjets.textContent = JSON.stringify(projets);
      } catch (e) {
        console.error("Erreur mise à jour JSON:", e);
      }
    }

    mettreAJourCompteurProjets(1);
  } else if (action === "update" && projet) {
    const ligneExistante = document.querySelector(`tr[data-projet-id="${projet.id}"]`);
    if (ligneExistante) {
      const nouvelleLigne = creerLigneProjet(projet);
      ligneExistante.replaceWith(nouvelleLigne);
    }

    if (donneesProjets) {
      try {
        const projets = JSON.parse(donneesProjets.textContent);
        const index = projets.findIndex((p) => p.id == projet.id);
        if (index !== -1) {
          projets[index] = projet;
          donneesProjets.textContent = JSON.stringify(projets);
        }
      } catch (e) {
        console.error("Erreur mise à jour JSON:", e);
      }
    }
  }
}

function creerLigneProjet(projet) {
  const tr = document.createElement("tr");
  tr.setAttribute("data-projet-id", projet.id);

  const escapeHtml = (value) =>
    String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");

  const parseDate = new Date(projet.created_at || projet.updated_at || "");
  const date = Number.isNaN(parseDate.getTime()) ? "" : parseDate.toLocaleDateString("fr-FR");

  const titreBrut = String(projet.title ?? "");
  const title = escapeHtml(titreBrut);
  const description = escapeHtml(String(projet.description ?? "").substring(0, 60));
  const auteur = escapeHtml(projet.auteur_nom || "N/A");
  const categorie = escapeHtml(projet.categorie || "");
  const technologies = escapeHtml(String(projet.technologies || "N/A").substring(0, 40));

  const imageSource = String(projet.image_url || "").trim();
  const imageSrc = imageSource !== "" ? escapeHtml(buildProjectImageUrl(imageSource)) : "";

  tr.innerHTML = `
    <td class="table-image-cell">
      ${
        imageSource !== ""
          ? `
        <div class="image-container">
          <img src="${imageSrc}"
               alt="${title}"
               class="project-thumb"
               data-proxy-src-on-error="${imageSrc}">
          <div class="no-image-fallback admin-hidden">
            <i class="fas fa-link"></i>
            <span>URL enregistree</span>
          </div>
        </div>
      `
          : `
        <div class="modern-placeholder no-image">
          <i class="fas fa-project-diagram"></i>
        </div>
      `
      }
    </td>
    <td><strong class="admin-text-primary">${title}</strong></td>
    <td class="admin-text-muted">${description}...</td>
    <td>${auteur}</td>
    <td>${categorie ? `<span class="badge-categorie">${categorie}</span>` : '<span class="admin-text-muted-sm">&mdash;</span>'}</td>
    <td class="admin-text-muted-compact">${technologies}</td>
    <td>${date}</td>
    <td class="text-center">
      <button type="button" class="btn btn-warning btn-sm" data-projet-edit-id="${projet.id}" title="Modifier">
        <i class="fas fa-edit"></i>
      </button>
      <button type="button" class="btn btn-danger btn-sm" data-projet-delete-id="${projet.id}" data-projet-delete-title="${title}" title="Supprimer">
        <i class="fas fa-trash"></i>
      </button>
    </td>
  `;

  return tr;
}

function mettreAJourCompteurProjets(delta) {
  const compteur = document.querySelector(".stats-badge");
  if (compteur) {
    const match = compteur.textContent.match(/\d+/);
    if (match) {
      const actuel = parseInt(match[0]);
      const nouveau = Math.max(0, actuel + delta);
      compteur.textContent = compteur.textContent.replace(/\d+/, nouveau);
    }
  }
}

window.essayerImageProxy = function (imgElement, originalUrl) {
  if (
    window.ImagePreviewHelper &&
    typeof window.ImagePreviewHelper.tryLoadViaProxyForElement === "function"
  ) {
    window.ImagePreviewHelper.tryLoadViaProxyForElement(imgElement, originalUrl, {
      fallbackSelector: ".no-image-fallback",
    });
  }
};
