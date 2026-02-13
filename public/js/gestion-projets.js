/**
 * Gestion des Projets - Admin
 */

let imagePreviewInitialized = false;

document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("modaleProjet");
  const closeBtn = document.querySelector(".close-modal");
  const formulaire = document.getElementById("formulaireProjet");

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
        const data = await AjaxHelper.post("?page=admin-projets", formData);

        if (data.success) {
          ToastNotification.succes(data.message);
          fermerModale();

          // Mettre à jour la table dynamiquement
          mettreAJourTableProjets(action, data.data?.project);
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

function ouvrirModale(action, data = null) {
  const modal = document.getElementById("modaleProjet");
  if (!modal) return;

  // Initialiser l'aperçu d'image la première fois que le modal s'ouvre
  if (!imagePreviewInitialized) {
    ImagePreviewHelper.init({
      inputId: 'image_url',
      containerId: 'imagePreviewContainer',
      imgId: 'imagePreview',
      spinnerId: 'imageLoadingSpinner',
      errorId: 'imagePreviewError',
      useProxy: true
    });
    imagePreviewInitialized = true;
  }

  const titre = document.getElementById("titreModale");
  titre.textContent =
    action === "create" ? "Nouveau Projet" : "Modifier le Projet";

  // Mettre à jour le texte du bouton
  const boutonSoumettre = document.querySelector('#formulaireProjet button[type="submit"]');
  if (boutonSoumettre) {
    boutonSoumettre.textContent = action === "create" ? "Créer" : "Modifier";
  }

  document.getElementById("actionFormulaire").value = action;
  document.getElementById("formulaireProjet").reset();
  document.getElementById("idProjet").value = "";

  // Réinitialiser l'aperçu d'image avec le helper
  ImagePreviewHelper.reset({
    containerId: 'imagePreviewContainer',
    imgId: 'imagePreview',
    errorId: 'imagePreviewError'
  });

  // Réinitialiser l'aperçu local
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

  // Récupérer les données complètes depuis le JSON
  const donneesProjets = document.getElementById("donneesProjets");
  if (donneesProjets) {
    try {
      const projets = JSON.parse(donneesProjets.textContent);
      const projet = projets.find((p) => p.id == id);

      if (projet) {
        document.getElementById("title").value = projet.title || "";
        document.getElementById("description").value = projet.description || "";
        document.getElementById("auteur").value = projet.auteur || "";
        document.getElementById("description_detailed").value =
          projet.description_detailed || "";
        document.getElementById("technologies").value =
          projet.technologies || "";
        document.getElementById("features").value = projet.features || "";
        document.getElementById("challenges").value = projet.challenges || "";
        document.getElementById("image_url").value = projet.image_url || "";

        // Déclencher l'aperçu de l'image si une URL existe
        if (projet.image_url) {
          ImagePreviewHelper.preview(projet.image_url, {
            containerId: 'imagePreviewContainer',
            imgId: 'imagePreview',
            spinnerId: 'imageLoadingSpinner',
            errorId: 'imagePreviewError',
            useProxy: true
          });
        } else {
          // Réinitialiser l'aperçu si pas d'URL
          ImagePreviewHelper.reset({
            containerId: 'imagePreviewContainer',
            imgId: 'imagePreview',
            errorId: 'imagePreviewError'
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

      // Supprimer la ligne du tableau avec animation
      const ligne = document.querySelector(`tr[data-projet-id="${id}"]`);
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

function rechercherProjets() {
  const valeur = document.getElementById("champRecherche").value.toLowerCase();
  document.querySelectorAll("#tableauProjets tbody tr").forEach((ligne) => {
    ligne.style.display = ligne.textContent.toLowerCase().includes(valeur)
      ? ""
      : "none";
  });
}

function previewLocalImage(input) {
  ImagePreviewHelper.previewLocal(input, 'localPreview', 'localPreviewContainer');
}

// Fonction pour mettre à jour la table des projets dynamiquement
function mettreAJourTableProjets(action, projet) {
  const tbody = document.querySelector("#tableauProjets tbody");
  const donneesProjets = document.getElementById("donneesProjets");

  if (action === "create" && projet) {
    // Ajouter une nouvelle ligne
    const nouvelleLigne = creerLigneProjet(projet);
    tbody.insertBefore(nouvelleLigne, tbody.firstChild);

    // Mettre à jour le JSON embarqué
    if (donneesProjets) {
      try {
        const projets = JSON.parse(donneesProjets.textContent);
        projets.unshift(projet); // Ajouter au début
        donneesProjets.textContent = JSON.stringify(projets);
      } catch (e) {
        console.error("Erreur mise à jour JSON:", e);
      }
    }

    // Mettre à jour le compteur
    mettreAJourCompteurProjets(1);
  } else if (action === "update" && projet) {
    // Mettre à jour la ligne existante
    const ligneExistante = document.querySelector(
      `tr[data-projet-id="${projet.id}"]`,
    );
    if (ligneExistante) {
      const nouvelleLigne = creerLigneProjet(projet);
      ligneExistante.replaceWith(nouvelleLigne);
    }

    // Mettre à jour le JSON embarqué
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

// Fonction pour créer une ligne de tableau pour un projet
function creerLigneProjet(projet) {
  const tr = document.createElement("tr");
  tr.setAttribute("data-projet-id", projet.id);

  const date = new Date(projet.created_at).toLocaleDateString("fr-FR");

  // Échapper les caractères spéciaux pour HTML
  const title = projet.title
    .replace(/&/g, "&amp;")
    .replace(/</g, "<")
    .replace(/>/g, ">")
    .replace(/"/g, '"')
    .replace(/'/g, "&#39;");
  const description = projet.description
    .substring(0, 80)
    .replace(/&/g, "&amp;")
    .replace(/</g, "<")
    .replace(/>/g, ">");
  const auteur = projet.auteur
    ? projet.auteur.replace(/&/g, "&amp;").replace(/</g, "<").replace(/>/g, ">")
    : "N/A";
  const technologies = projet.technologies
    ? projet.technologies.substring(0, 40).replace(/&/g, "&amp;").replace(/</g, "<").replace(/>/g, ">")
    : "N/A";
  const imageUrl = projet.image_url
    ? projet.image_url.replace(/"/g, '"').replace(/'/g, "&#39;")
    : "";
  const titleJs = projet.title.replace(/'/g, "\\'").replace(/"/g, '\\"');

  // Construire l'URL de l'image
  let imageSrc = "";
  if (projet.image_url) {
    if (projet.image_url.startsWith("http://") || projet.image_url.startsWith("https://")) {
      imageSrc = imageUrl;
    } else {
      imageSrc = "images/projets/" + imageUrl;
    }
  }

  tr.innerHTML = `
    <td style="text-align: center; padding: 10px;">
      ${
        projet.image_url
          ? `
        <div class="image-container" style="display: inline-block; position: relative;">
          <img src="${imageSrc}"
               alt="${title}"
               class="project-image-thumb"
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
          <i class="fas fa-project-diagram"></i>
        </div>
      `
      }
    </td>
    <td><strong style="color: var(--primary-color);">${title}</strong></td>
    <td style="color: var(--text-muted);">${description}...</td>
    <td>${auteur}</td>
    <td style="color: var(--text-muted); font-size: 0.85rem;">${technologies}</td>
    <td>${date}</td>
    <td>
      <div class="table-actions">
        <button class="btn btn-warning btn-small" onclick='editerProjet(${projet.id})' title="Modifier">
          <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-danger btn-small" onclick="supprimerProjet(${projet.id}, '${titleJs}')" title="Supprimer">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </td>
  `;

  return tr;
}

// Fonction pour mettre à jour le compteur de projets
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

console.log("📁 Projets ready");
