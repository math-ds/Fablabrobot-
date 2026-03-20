let imagePreviewInitialized = false;
let articleSearchTimer = null;

function estPageAdminArticles() {
  if (document.querySelector("[data-admin-articles='1']")) {
    return true;
  }
  const params = new URLSearchParams(window.location.search);
  return params.get("page") === "admin-articles";
}

function construireUrlAdminArticles(q = "", page = 1) {
  const url = new URL(window.location.href);
  url.searchParams.set("page", "admin-articles");
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

async function chargerArticlesAdmin(q = "", page = 1, options = {}) {
  const url = construireUrlAdminArticles(q, page);
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
  const modal = document.getElementById("modaleArticle");
  const closeBtn = document.querySelector(".close-modal");
  const formulaire = document.getElementById("formulaireArticle");

  if (closeBtn) closeBtn.addEventListener("click", fermerModale);
  if (modal) window.addEventListener("click", (e) => e.target === modal && fermerModale());

  if (formulaire) {
    formulaire.addEventListener("submit", async function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const action = formData.get("action");
      const boutonSoumettre = this.querySelector('button[type="submit"]');

      boutonSoumettre.disabled = true;
      boutonSoumettre.textContent = "Envoi en cours...";

      try {
        const data = await AjaxHelper.post("?page=admin-articles", formData);

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

    const openCreateBtn = target.closest("[data-articles-open-create]");
    if (openCreateBtn) {
      event.preventDefault();
      ouvrirModale("create");
      return;
    }

    const editBtn = target.closest("[data-article-edit-id]");
    if (editBtn) {
      event.preventDefault();
      const id = Number.parseInt(String(editBtn.getAttribute("data-article-edit-id") || "0"), 10);
      if (id > 0) {
        editerArticle(id);
      }
      return;
    }

    const deleteBtn = target.closest("[data-article-delete-id]");
    if (deleteBtn) {
      event.preventDefault();
      const id = Number.parseInt(
        String(deleteBtn.getAttribute("data-article-delete-id") || "0"),
        10
      );
      const titre = String(deleteBtn.getAttribute("data-article-delete-title") || "cet article");
      if (id > 0) {
        supprimerArticle(id, titre);
      }
      return;
    }

    const closeModalBtn = target.closest("[data-article-close-modal]");
    if (closeModalBtn) {
      event.preventDefault();
      fermerModale();
    }
  });

  document.addEventListener("change", (event) => {
    const input = event.target instanceof HTMLInputElement ? event.target : null;
    if (!input || !input.matches("[data-article-image-file-input]")) {
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
  if (!estPageAdminArticles()) {
    return;
  }

  const input = event.target instanceof HTMLInputElement ? event.target : null;
  if (!input || input.id !== "champRecherche") {
    return;
  }

  if (articleSearchTimer) {
    clearTimeout(articleSearchTimer);
  }

  articleSearchTimer = window.setTimeout(() => {
    void chargerArticlesAdmin(input.value, 1, {
      pushState: true,
      scrollToTop: false,
      preserveLocalState: false,
    });
  }, 320);
});

window.addEventListener("popstate", () => {
  if (!estPageAdminArticles()) {
    return;
  }

  const url = new URL(window.location.href);
  const q = String(url.searchParams.get("q") || "");
  const p = Number.parseInt(String(url.searchParams.get("p") || "1"), 10);

  void chargerArticlesAdmin(q, Number.isFinite(p) && p > 0 ? p : 1, {
    pushState: false,
    scrollToTop: false,
    preserveLocalState: false,
  });
});

function ouvrirModale(action) {
  const modal = document.getElementById("modaleArticle");
  if (!modal) return;

  if (!imagePreviewInitialized) {
    ImagePreviewHelper.init({
      inputId: "image_url",
      containerId: "conteneurApercuImage",
      imgId: "apercuImage",
      spinnerId: "spinnerChargementImage",
      errorId: "erreurApercuImage",
      useProxy: true,
    });
    imagePreviewInitialized = true;
  }

  document.getElementById("titreModale").textContent =
    action === "create" ? "Nouvel Article" : "Modifier l'Article";

  const boutonSoumettre = document.querySelector('#formulaireArticle button[type="submit"]');
  if (boutonSoumettre) {
    boutonSoumettre.textContent = action === "create" ? "Créer" : "Modifier";
  }

  document.getElementById("actionFormulaire").value = action;
  document.getElementById("formulaireArticle").reset();
  document.getElementById("idArticle").value = "";

  ImagePreviewHelper.reset({
    containerId: "conteneurApercuImage",
    imgId: "apercuImage",
    errorId: "erreurApercuImage",
  });

  const localPreviewContainer = document.getElementById("localPreviewArticleContainer");
  if (localPreviewContainer) {
    localPreviewContainer.style.display = "none";
  }

  modal.classList.add("active");
}

function fermerModale() {
  const modal = document.getElementById("modaleArticle");
  if (modal) modal.classList.remove("active");
}

function editerArticle(id) {
  ouvrirModale("update");
  document.getElementById("idArticle").value = id;

  const donneesArticles = document.getElementById("donneesArticles");
  if (donneesArticles) {
    try {
      const articles = JSON.parse(donneesArticles.textContent);
      const article = articles.find((a) => a.id == id);

      if (article) {
        document.getElementById("titre").value = article.titre || "";
        document.getElementById("contenu").value = article.contenu || "";
        document.getElementById("image_url").value = article.image_url || "";
        const selectCat = document.getElementById("categorieAdmin");
        if (selectCat) {
          definirValeurSelectCategorie(selectCat, article.categorie || "");
        }

        if (article.image_url) {
          const imagePreviewSrc = buildArticleImageUrl(article.image_url);
          ImagePreviewHelper.preview(imagePreviewSrc, {
            containerId: "conteneurApercuImage",
            imgId: "apercuImage",
            spinnerId: "spinnerChargementImage",
            errorId: "erreurApercuImage",
            useProxy: true,
          });
        } else {
          ImagePreviewHelper.reset({
            containerId: "conteneurApercuImage",
            imgId: "apercuImage",
            errorId: "erreurApercuImage",
          });
        }
      }
    } catch (e) {
      console.error("Erreur parsing JSON:", e);
    }
  }
}

async function supprimerArticle(id, titre) {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer "${titre}" ?`)) {
    return;
  }

  try {
    const data = await AjaxHelper.post("?page=admin-articles", {
      action: "delete",
      article_id: id,
    });

    if (data.success) {
      ToastNotification.succes(data.message || "Article supprimé avec succès");

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

function rechercherArticles() {
  if (!estPageAdminArticles()) {
    return;
  }
  const valeur = String(document.getElementById("champRecherche")?.value || "");
  void chargerArticlesAdmin(valeur, 1);
}

function previewLocalImage(input) {
  ImagePreviewHelper.previewLocal(input, "localPreviewArticle", "localPreviewArticleContainer");
}

function buildArticleImageUrl(imageSource) {
  const value = String(imageSource || "").trim();
  if (value === "") {
    return "";
  }

  if (/^https?:\/\//i.test(value)) {
    return value;
  }

  if (/^images\/articles\//i.test(value) || /^images\//i.test(value)) {
    return value;
  }

  return `images/articles/${value.replace(/^\/+/, "")}`;
}

function definirValeurSelectCategorie(selectElement, categorie) {
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
  } else if (normalisee.includes("intelligence") && normalisee.includes("artificielle")) {
    canonique = "Intelligence Artificielle";
  } else if (normalisee.includes("robot")) {
    canonique = "Robotique";
  } else if (normalisee.includes("program")) {
    canonique = "Programmation";
  } else if (normalisee.includes("conception")) {
    canonique = "Conception";
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

function mettreAJourTableArticles(action, article) {
  const tbody = document.querySelector("#tableauArticles tbody");
  const donneesArticles = document.getElementById("donneesArticles");

  if (action === "create" && article) {
    const nouvelleLigne = creerLigneArticle(article);
    tbody.insertBefore(nouvelleLigne, tbody.firstChild);

    if (donneesArticles) {
      try {
        const articles = JSON.parse(donneesArticles.textContent);
        articles.unshift(article); // Ajouter au début
        donneesArticles.textContent = JSON.stringify(articles);
      } catch (e) {
        console.error("Erreur mise à jour JSON:", e);
      }
    }

    mettreAJourCompteurArticles(1);
  } else if (action === "update" && article) {
    const ligneExistante = document.querySelector(`tr[data-article-id="${article.id}"]`);
    if (ligneExistante) {
      const nouvelleLigne = creerLigneArticle(article);
      ligneExistante.replaceWith(nouvelleLigne);
    }

    if (donneesArticles) {
      try {
        const articles = JSON.parse(donneesArticles.textContent);
        const index = articles.findIndex((a) => a.id == article.id);
        if (index !== -1) {
          articles[index] = article;
          donneesArticles.textContent = JSON.stringify(articles);
        }
      } catch (e) {
        console.error("Erreur mise à jour JSON:", e);
      }
    }
  }
}

function creerLigneArticle(article) {
  const tr = document.createElement("tr");
  tr.setAttribute("data-article-id", article.id);

  const escapeHtml = (value) =>
    String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");

  const parseDate = new Date(article.created_at || article.updated_at || "");
  const date = Number.isNaN(parseDate.getTime()) ? "" : parseDate.toLocaleDateString("fr-FR");

  const titreBrut = String(article.titre ?? "");
  const titre = escapeHtml(titreBrut);
  const extrait = escapeHtml(String(article.contenu ?? "").substring(0, 80));
  const auteur = escapeHtml(article.auteur_nom || "N/A");
  const categorie = escapeHtml(article.categorie || "");

  const imageSource = String(article.image_url || "").trim();
  const imageSrc = escapeHtml(buildArticleImageUrl(imageSource));

  tr.innerHTML = `
    <td class="table-image-cell">
      ${
        imageSource !== ""
          ? `
        <div class="image-container">
          <img src="${imageSrc}"
               alt="${titre}"
               class="article-thumb"
               data-proxy-src-on-error="${imageSrc}">
          <div class="no-image-fallback admin-hidden">
            <i class="fas fa-link"></i>
            <span>URL enregistree</span>
          </div>
        </div>
      `
          : `
        <div class="modern-placeholder no-image">
          <i class="fas fa-newspaper"></i>
        </div>
      `
      }
    </td>
    <td><strong class="admin-text-primary">${titre}</strong></td>
    <td class="admin-text-muted">${extrait}...</td>
    <td>${auteur}</td>
    <td>${categorie ? `<span class="badge-categorie">${categorie}</span>` : '<span class="admin-text-muted-sm">&mdash;</span>'}</td>
    <td>${date}</td>
    <td class="text-center">
      <button type="button" class="btn btn-warning btn-sm" data-article-edit-id="${article.id}" title="Modifier">
        <i class="fas fa-edit"></i>
      </button>
      <button type="button" class="btn btn-danger btn-sm" data-article-delete-id="${article.id}" data-article-delete-title="${titre}" title="Supprimer">
        <i class="fas fa-trash"></i>
      </button>
    </td>
  `;

  return tr;
}

function mettreAJourCompteurArticles(delta) {
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
