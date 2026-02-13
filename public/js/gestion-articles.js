/**
 * Gestion des Articles - Admin
 */

// Variable pour savoir si l'aperçu est déjà initialisé
let imagePreviewInitialized = false;

document.addEventListener("DOMContentLoaded", function () {
  // Configuration du modal et formulaire
  const modal = document.getElementById("modaleArticle");
  const closeBtn = document.querySelector(".close-modal");
  const formulaire = document.getElementById("formulaireArticle");

  if (closeBtn) closeBtn.addEventListener("click", fermerModale);
  if (modal)
    window.addEventListener(
      "click",
      (e) => e.target === modal && fermerModale(),
    );

  // Intercepter la soumission du formulaire pour AJAX
  if (formulaire) {
    formulaire.addEventListener("submit", async function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const action = formData.get("action");
      const boutonSoumettre = this.querySelector('button[type="submit"]');

      // Désactiver le bouton pendant l'envoi
      boutonSoumettre.disabled = true;
      boutonSoumettre.textContent = "Envoi en cours...";

      try {
        const data = await AjaxHelper.post("?page=admin-articles", formData);

        if (data.success) {
          ToastNotification.succes(data.message);
          fermerModale();

          // Mettre à jour la table dynamiquement
          mettreAJourTableArticles(action, data.data?.article);
        }
      } catch (error) {
        ToastNotification.erreur(
          error.data?.message || "Erreur lors de l'enregistrement",
        );

        // Afficher les erreurs de validation si présentes
        if (error.data?.error?.validation) {
          Object.values(error.data.error.validation).forEach((erreur) => {
            ToastNotification.erreur(erreur);
          });
        }
      } finally {
        // Réactiver le bouton
        boutonSoumettre.disabled = false;
        boutonSoumettre.textContent =
          action === "create" ? "Créer" : "Modifier";
      }
    });
  }
});

function ouvrirModale(action) {
  const modal = document.getElementById("modaleArticle");
  if (!modal) return;

  // Initialiser l'aperçu d'image la première fois que le modal s'ouvre
  if (!imagePreviewInitialized) {
    ImagePreviewHelper.init({
      inputId: 'image_url',
      containerId: 'conteneurApercuImage',
      imgId: 'apercuImage',
      spinnerId: 'spinnerChargementImage',
      errorId: 'erreurApercuImage',
      useProxy: true
    });
    imagePreviewInitialized = true;
  }

  document.getElementById("titreModale").textContent =
    action === "create" ? "Nouvel Article" : "Modifier l'Article";

  // Mettre à jour le texte du bouton
  const boutonSoumettre = document.querySelector(
    '#formulaireArticle button[type="submit"]',
  );
  if (boutonSoumettre) {
    boutonSoumettre.textContent = action === "create" ? "Créer" : "Modifier";
  }

  document.getElementById("actionFormulaire").value = action;
  document.getElementById("formulaireArticle").reset();
  document.getElementById("idArticle").value = "";

  // Réinitialiser l'aperçu d'image avec le helper
  ImagePreviewHelper.reset({
    containerId: 'conteneurApercuImage',
    imgId: 'apercuImage',
    errorId: 'erreurApercuImage'
  });

  // use class active so CSS centers the modal
  modal.classList.add("active");
}

function fermerModale() {
  const modal = document.getElementById("modaleArticle");
  if (modal) modal.classList.remove("active");
}

function editerArticle(id) {
  ouvrirModale("update");
  document.getElementById("idArticle").value = id;

  // Récupérer les données complètes depuis le JSON
  const donneesArticles = document.getElementById("donneesArticles");
  if (donneesArticles) {
    try {
      const articles = JSON.parse(donneesArticles.textContent);
      const article = articles.find((a) => a.id == id);

      if (article) {
        document.getElementById("titre").value = article.titre || "";
        document.getElementById("contenu").value = article.contenu || "";
        document.getElementById("auteur").value = article.auteur || "";
        document.getElementById("image_url").value = article.image_url || "";

        // Déclencher l'aperçu de l'image si une URL existe
        if (article.image_url) {
          ImagePreviewHelper.preview(article.image_url, {
            containerId: 'conteneurApercuImage',
            imgId: 'apercuImage',
            spinnerId: 'spinnerChargementImage',
            errorId: 'erreurApercuImage',
            useProxy: true
          });
        } else {
          // Réinitialiser l'aperçu si pas d'URL
          ImagePreviewHelper.reset({
            containerId: 'conteneurApercuImage',
            imgId: 'apercuImage',
            errorId: 'erreurApercuImage'
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

      // Supprimer la ligne du tableau avec animation
      const ligne = document.querySelector(`tr[data-article-id="${id}"]`);
      if (ligne) {
        ligne.style.transition = "opacity 0.3s";
        ligne.style.opacity = "0";
        setTimeout(() => ligne.remove(), 300);
      }

      // Mettre à jour le compteur
      const compteur = document.querySelector(".stats-badge");
      if (compteur) {
        const match = compteur.textContent.match(/\d+/);
        if (match) {
          const actuel = parseInt(match[0]);
          compteur.textContent = compteur.textContent.replace(
            /\d+/,
            actuel - 1,
          );
        }
      }
    }
  } catch (error) {
    ToastNotification.erreur(
      error.data?.message || "Erreur lors de la suppression",
    );
  }
}

function rechercherArticles() {
  const valeur = document.getElementById("champRecherche").value.toLowerCase();
  document.querySelectorAll("#tableauArticles tbody tr").forEach((ligne) => {
    ligne.style.display = ligne.textContent.toLowerCase().includes(valeur)
      ? ""
      : "none";
  });
}

// Fonction pour mettre à jour la table des articles dynamiquement
function mettreAJourTableArticles(action, article) {
  const tbody = document.querySelector("#tableauArticles tbody");
  const donneesArticles = document.getElementById("donneesArticles");

  if (action === "create" && article) {
    // Ajouter une nouvelle ligne
    const nouvelleLigne = creerLigneArticle(article);
    tbody.insertBefore(nouvelleLigne, tbody.firstChild);

    // Mettre à jour le JSON embarqué
    if (donneesArticles) {
      try {
        const articles = JSON.parse(donneesArticles.textContent);
        articles.unshift(article); // Ajouter au début
        donneesArticles.textContent = JSON.stringify(articles);
      } catch (e) {
        console.error("Erreur mise à jour JSON:", e);
      }
    }

    // Mettre à jour le compteur
    mettreAJourCompteurArticles(1);
  } else if (action === "update" && article) {
    // Mettre à jour la ligne existante
    const ligneExistante = document.querySelector(
      `tr[data-article-id="${article.id}"]`,
    );
    if (ligneExistante) {
      const nouvelleLigne = creerLigneArticle(article);
      ligneExistante.replaceWith(nouvelleLigne);
    }

    // Mettre à jour le JSON embarqué
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

// Fonction pour créer une ligne de tableau pour un article
function creerLigneArticle(article) {
  const tr = document.createElement("tr");
  tr.setAttribute("data-article-id", article.id);

  const date = new Date(article.created_at).toLocaleDateString("fr-FR");

  const imageSrc = article.image_url
    ? article.image_url.startsWith("http://") ||
      article.image_url.startsWith("https://")
      ? article.image_url
      : article.image_url
    : "";

  tr.innerHTML = `
    <td style="text-align: center; padding: 10px;">
      ${
        article.image_url
          ? `
        <div class="image-container" style="display: inline-block; position: relative;">
          <img src="${imageSrc.replace(/"/g, '"')}"
               alt="${article.titre.replace(/"/g, '"')}"
               class="article-image-thumb"
               style="width: 100px; height: 70px; object-fit: cover; border-radius: 8px; border: 2px solid var(--card-border); display: block;"
               onerror="essayerImageProxy(this, '${imageSrc.replace(/'/g, "\\'")}')">
          <div class="no-image-fallback" style="display: none; width: 100px; height: 70px; background: rgba(0, 175, 167, 0.1); border-radius: 8px; align-items: center; justify-content: center; flex-direction: column; color: var(--text-muted); font-size: 0.7rem; border: 2px dashed var(--card-border); padding: 5px; text-align: center;">
            <i class="fas fa-link" style="font-size: 1.2rem; margin-bottom: 3px;"></i>
            <span>URL enregistrée</span>
          </div>
        </div>
      `
          : `
        <div class="no-image" style="display: inline-flex; width: 100px; height: 70px; background: rgba(0, 175, 167, 0.1); border-radius: 8px; align-items: center; justify-content: center; color: var(--primary-color); font-size: 1.5rem; border: 2px dashed var(--card-border);">
          <i class="fas fa-newspaper"></i>
        </div>
      `
      }
    </td>
    <td><strong style="color: var(--primary-color);">${article.titre.replace(/</g, "<").replace(/>/g, ">")}</strong></td>
    <td style="color: var(--text-muted);">${article.contenu.substring(0, 80).replace(/</g, "<").replace(/>/g, ">")}...</td>
    <td>${article.auteur.replace(/</g, "<").replace(/>/g, ">")}</td>
    <td>${date}</td>
    <td>
      <div class="table-actions">
        <button class="btn btn-warning btn-small" onclick='editerArticle(${article.id})' title="Modifier">
          <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-danger btn-small" onclick="supprimerArticle(${article.id}, '${article.titre.replace(/'/g, "\\'")}')" title="Supprimer">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </td>
  `;

  return tr;
}

// Fonction pour mettre à jour le compteur d'articles
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

// Fonction pour essayer de charger via proxy (pour les images dans le tableau)
window.essayerImageProxy = function(imgElement, originalUrl) {
  const fallback = imgElement.nextElementSibling;
  if (fallback && fallback.classList.contains("no-image-fallback")) {
    imgElement.style.display = "none";
    fallback.style.display = "flex";
  }

  const proxyUrl = `proxy-image.php?url=${encodeURIComponent(originalUrl)}`;
  const proxyImg = new Image();

  proxyImg.onload = function () {
    imgElement.src = proxyUrl;
    imgElement.style.display = "block";
    if (fallback) fallback.style.display = "none";
  };

  proxyImg.onerror = function () {
    // Garder le fallback affiché
  };

  proxyImg.src = proxyUrl;
};

console.log("📄 Articles ready");
