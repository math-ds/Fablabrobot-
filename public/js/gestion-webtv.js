/**
 * Gestion WebTV - Admin
 */

document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("modaleVideo");
  const closeBtn = document.querySelector(".close-modal");
  const formulaire = document.getElementById("formulaireVideo");

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
        const data = await AjaxHelper.post("?page=admin-webtv", formData);

        if (data.success) {
          ToastNotification.succes(data.message);
          fermerModale();

          // Mettre à jour la table dynamiquement
          mettreAJourTableVideos(action, data.data?.video);
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
  const modal = document.getElementById("modaleVideo");
  if (!modal) return;

  const titre = document.getElementById("titreModale");
  titre.textContent =
    action === "update"
      ? "Modifier la vidéo YouTube"
      : "Nouvelle vidéo YouTube";

  // Mettre à jour le texte du bouton
  const boutonSoumettre = document.querySelector('#formulaireVideo button[type="submit"]');
  if (boutonSoumettre) {
    boutonSoumettre.textContent = action === "create" ? "Ajouter" : "Modifier";
  }

  document.getElementById("actionFormulaire").value = action;
  document.getElementById("formulaireVideo").reset();
  document.getElementById("idVideo").value = "";
  document.getElementById("youtube_url").value = "";
  traiterUrlYoutube("");

  if (action === "update" && data) {
    document.getElementById("idVideo").value = data.id || "";
    document.getElementById("titre").value = data.titre || "";
    document.getElementById("description").value = data.description || "";
    document.getElementById("categorie").value = data.categorie || "";
    document.getElementById("vignette").value = data.vignette || "";

    // Déclencher l'aperçu si une URL YouTube existe
    if (data.youtube_url) {
      document.getElementById("youtube_url").value = data.youtube_url;
      traiterUrlYoutube(data.youtube_url);
    }
  }

  modal.classList.add("active");
}

function fermerModale() {
  const modal = document.getElementById("modaleVideo");
  if (modal) modal.classList.remove("active");
}

function editerVideo(id) {
  // Récupérer les données complètes depuis le JSON embarqué
  const donneesVideos = document.getElementById("donneesVideos");
  if (donneesVideos) {
    try {
      const videos = JSON.parse(donneesVideos.textContent);
      const video = videos.find((v) => v.id == id);

      if (video) {
        ouvrirModale("update", video);
      }
    } catch (e) {
      console.error("Erreur parsing JSON:", e);
    }
  }
}

function traiterUrlYoutube(url) {
  const vignetteInput = document.getElementById("vignette");
  const placeholder = document.getElementById("placeholderMiniature");
  const conteneurApercuImage = document.getElementById("conteneurApercuImage");

  if (!url.trim()) {
    vignetteInput.value = "";
    if (conteneurApercuImage) conteneurApercuImage.style.display = "none";
    if (placeholder) placeholder.style.display = "block";
    ImagePreviewHelper.reset({
      containerId: 'conteneurApercuImage',
      imgId: 'apercuImage',
      errorId: 'erreurApercuImage'
    });
    return;
  }

  // Extraire l'ID YouTube de l'URL
  let videoId = null;
  const patterns = [
    /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/,
    /youtube\.com\/v\/([a-zA-Z0-9_-]{11})/,
  ];

  for (const pattern of patterns) {
    const match = url.match(pattern);
    if (match) {
      videoId = match[1];
      break;
    }
  }

  if (videoId) {
    const thumbnailUrl = `https://img.youtube.com/vi/${videoId}/hqdefault.jpg`;
    vignetteInput.value = thumbnailUrl;

    // Masquer le placeholder et afficher l'aperçu avec ImagePreviewHelper
    if (placeholder) placeholder.style.display = "none";
    if (conteneurApercuImage) conteneurApercuImage.style.display = "block";

    ImagePreviewHelper.preview(thumbnailUrl, {
      containerId: 'conteneurApercuImage',
      imgId: 'apercuImage',
      spinnerId: 'spinnerChargementImage',
      errorId: 'erreurApercuImage',
      useProxy: true
    });
  } else {
    vignetteInput.value = "";
    if (conteneurApercuImage) conteneurApercuImage.style.display = "none";
    if (placeholder) placeholder.style.display = "block";

    // Afficher l'erreur car l'URL YouTube n'est pas valide
    const erreur = document.getElementById("erreurApercuImage");
    if (erreur) erreur.style.display = "block";
  }
}

async function supprimerVideo(id, titre) {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer "${titre}" ?`)) {
    return;
  }

  try {
    const data = await AjaxHelper.post("?page=admin-webtv", {
      action: "delete",
      id: id,
    });

    if (data.success) {
      ToastNotification.succes(data.message || "Vidéo supprimée avec succès");

      // Supprimer la ligne du tableau avec animation
      const ligne = document.querySelector(`tr[data-video-id="${id}"]`);
      if (ligne) {
        ligne.style.transition = "opacity 0.3s";
        ligne.style.opacity = "0";
        setTimeout(() => ligne.remove(), 300);
      }

      // Mettre à jour le compteur
      const compteurs = document.querySelectorAll(".stats-badge, .card-value");
      compteurs.forEach((compteur) => {
        const match = compteur.textContent.match(/\d+/);
        if (match) {
          const actuel = parseInt(match[0]);
          compteur.textContent = compteur.textContent.replace(
            /\d+/,
            actuel - 1,
          );
        }
      });
    }
  } catch (error) {
    ToastNotification.erreur(
      error.data?.message || "Erreur lors de la suppression",
    );
  }
}

function rechercherVideos() {
  const valeur = document.getElementById("champRecherche").value.toLowerCase();
  document.querySelectorAll("#tableauVideos tbody tr").forEach((ligne) => {
    ligne.style.display = ligne.textContent.toLowerCase().includes(valeur)
      ? ""
      : "none";
  });
}

// Fonction pour mettre à jour la table des vidéos dynamiquement
function mettreAJourTableVideos(action, video) {
  const tbody = document.querySelector("#tableauVideos tbody");
  const donneesVideos = document.getElementById("donneesVideos");

  if (action === "create" && video) {
    // Ajouter une nouvelle ligne
    const nouvelleLigne = creerLigneVideo(video);
    tbody.insertBefore(nouvelleLigne, tbody.firstChild);

    // Mettre à jour le JSON embarqué
    if (donneesVideos) {
      try {
        const videos = JSON.parse(donneesVideos.textContent);
        videos.unshift(video); // Ajouter au début
        donneesVideos.textContent = JSON.stringify(videos);
      } catch (e) {
        console.error("Erreur mise à jour JSON:", e);
      }
    }

    // Mettre à jour le compteur
    mettreAJourCompteurVideos(1);
  } else if (action === "update" && video) {
    // Mettre à jour la ligne existante
    const ligneExistante = document.querySelector(
      `tr[data-video-id="${video.id}"]`,
    );
    if (ligneExistante) {
      const nouvelleLigne = creerLigneVideo(video);
      ligneExistante.replaceWith(nouvelleLigne);
    }

    // Mettre à jour le JSON embarqué
    if (donneesVideos) {
      try {
        const videos = JSON.parse(donneesVideos.textContent);
        const index = videos.findIndex((v) => v.id == video.id);
        if (index !== -1) {
          videos[index] = video;
          donneesVideos.textContent = JSON.stringify(videos);
        }
      } catch (e) {
        console.error("Erreur mise à jour JSON:", e);
      }
    }
  }
}

// Fonction pour créer une ligne de tableau pour une vidéo
function creerLigneVideo(video) {
  const tr = document.createElement("tr");
  tr.setAttribute("data-video-id", video.id);

  const date = new Date(video.created_at).toLocaleDateString("fr-FR");

  // Échapper les caractères spéciaux pour HTML
  const titre = video.titre
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
  const categorie = video.categorie
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
  const vignette = video.vignette
    ? video.vignette.replace(/"/g, "&quot;").replace(/'/g, "&#39;")
    : "";
  const titreJs = video.titre.replace(/'/g, "\\'").replace(/"/g, '\\"');

  tr.innerHTML = `
    <td style="text-align: center;">
      ${
        vignette
          ? `
        <img src="${vignette}"
             alt="${titre}"
             class="video-thumb">
      `
          : `
        <div class="no-image">
          <i class="fas fa-video"></i>
        </div>
      `
      }
    </td>
    <td><strong style="color: var(--primary-color);">${titre}</strong></td>
    <td style="color: var(--text-muted);">${categorie}</td>
    <td>
      <span style="color:#ff4d4d;"><i class="fab fa-youtube"></i> YouTube</span>
    </td>
    <td>${date}</td>
    <td>
      <div class="table-actions">
        <button class="btn btn-warning btn-small" onclick="editerVideo(${video.id})" title="Modifier">
          <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-danger btn-small" onclick="supprimerVideo(${video.id}, '${titreJs}')" title="Supprimer">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </td>
  `;

  return tr;
}

// Fonction pour mettre à jour le compteur de vidéos
function mettreAJourCompteurVideos(delta) {
  const compteurs = document.querySelectorAll(".stats-badge, .card-value");
  compteurs.forEach((compteur) => {
    const match = compteur.textContent.match(/\d+/);
    if (match) {
      const actuel = parseInt(match[0]);
      const nouveau = Math.max(0, actuel + delta);
      compteur.textContent = compteur.textContent.replace(/\d+/, nouveau);
    }
  });
}

console.log("📹 WebTV prête");
