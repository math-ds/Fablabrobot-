let webtvSearchTimer = null;

function estPageAdminWebtv() {
  if (document.querySelector("[data-admin-webtv='1']")) {
    return true;
  }
  const params = new URLSearchParams(window.location.search);
  return params.get("page") === "admin-webtv";
}

function construireUrlAdminWebtv(q = "", page = 1) {
  const url = new URL(window.location.href);
  url.searchParams.set("page", "admin-webtv");
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

async function chargerWebtvAdmin(q = "", page = 1, options = {}) {
  const url = construireUrlAdminWebtv(q, page);
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
  const modal = document.getElementById("modaleVideo");
  const closeBtn = document.querySelector(".close-modal");
  const formulaire = document.getElementById("formulaireVideo");

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
        const data = await AjaxHelper.post("?page=admin-webtv", formData);

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

    const openCreateBtn = target.closest("[data-webtv-open-create]");
    if (openCreateBtn) {
      event.preventDefault();
      ouvrirModale("create");
      return;
    }

    const editBtn = target.closest("[data-webtv-edit]");
    if (editBtn) {
      event.preventDefault();
      const payload = String(editBtn.getAttribute("data-webtv-edit") || "");
      if (payload) {
        try {
          ouvrirModale("update", JSON.parse(payload));
        } catch (_error) {}
      }
      return;
    }

    const deleteBtn = target.closest("[data-webtv-delete-id]");
    if (deleteBtn) {
      event.preventDefault();
      const id = Number.parseInt(String(deleteBtn.getAttribute("data-webtv-delete-id") || "0"), 10);
      const titre = String(deleteBtn.getAttribute("data-webtv-delete-title") || "cette vidéo");
      if (id > 0) {
        supprimerVideo(id, titre);
      }
      return;
    }

    const closeBtn = target.closest("[data-webtv-close-modal]");
    if (closeBtn) {
      event.preventDefault();
      fermerModale();
    }
  });

  document.addEventListener("input", (event) => {
    const input = event.target instanceof HTMLInputElement ? event.target : null;
    if (!input || !input.matches("[data-webtv-youtube-input]")) {
      return;
    }
    traiterUrlYoutube(input.value);
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
      if (
        window.ImagePreviewHelper &&
        typeof window.ImagePreviewHelper.tryLoadViaProxyForElement === "function"
      ) {
        window.ImagePreviewHelper.tryLoadViaProxyForElement(img, originalSrc, {
          fallbackSelector: ".no-image-fallback",
        });
      }
    },
    true
  );
});

document.addEventListener("input", (event) => {
  if (!estPageAdminWebtv()) {
    return;
  }

  const input = event.target instanceof HTMLInputElement ? event.target : null;
  if (!input || input.id !== "champRecherche") {
    return;
  }

  if (webtvSearchTimer) {
    clearTimeout(webtvSearchTimer);
  }

  webtvSearchTimer = window.setTimeout(() => {
    void chargerWebtvAdmin(input.value, 1, {
      pushState: true,
      scrollToTop: false,
      preserveLocalState: false,
    });
  }, 320);
});

window.addEventListener("popstate", () => {
  if (!estPageAdminWebtv()) {
    return;
  }

  const url = new URL(window.location.href);
  const q = String(url.searchParams.get("q") || "");
  const p = Number.parseInt(String(url.searchParams.get("p") || "1"), 10);

  void chargerWebtvAdmin(q, Number.isFinite(p) && p > 0 ? p : 1, {
    pushState: false,
    scrollToTop: false,
    preserveLocalState: false,
  });
});

function ouvrirModale(action, data = null) {
  const modal = document.getElementById("modaleVideo");
  if (!modal) return;

  const titre = document.getElementById("titreModale");
  titre.textContent = action === "update" ? "Modifier la vidéo YouTube" : "Nouvelle vidéo YouTube";

  const boutonSoumettre = document.querySelector('#formulaireVideo button[type="submit"]');
  if (boutonSoumettre) {
    boutonSoumettre.textContent = action === "create" ? "Ajouter" : "Modifier";
  }

  document.getElementById("actionFormulaire").value = action;
  document.getElementById("formulaireVideo").reset();
  document.getElementById("idVideo").value = "";
  document.getElementById("youtube_url").value = "";
  const auteurAfficheInput = document.getElementById("auteur_affiche");
  if (auteurAfficheInput) {
    const auteurCourant = auteurAfficheInput.getAttribute("data-current-user") || "";
    auteurAfficheInput.value = auteurCourant;
  }
  traiterUrlYoutube("");

  if (action === "update" && data) {
    document.getElementById("idVideo").value = data.id || "";
    document.getElementById("titre").value = data.titre || "";
    document.getElementById("description").value = data.description || "";
    const categorieField = document.getElementById("categorie");
    if (categorieField) {
      const categorieValue = String(data.categorie || "");
      const hasOption = Array.from(categorieField.options || []).some(
        (option) => option.value === categorieValue
      );
      categorieField.value = hasOption ? categorieValue : "Autre";
    }
    document.getElementById("vignette").value = data.vignette || "";
    if (auteurAfficheInput) {
      auteurAfficheInput.value =
        typeof data.auteur_nom === "string" && data.auteur_nom.trim() !== ""
          ? data.auteur_nom
          : auteurAfficheInput.getAttribute("data-current-user") || "";
    }

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
  const erreur = document.getElementById("erreurApercuImage");

  if (!url.trim()) {
    vignetteInput.value = "";
    if (conteneurApercuImage) conteneurApercuImage.style.display = "none";
    if (placeholder) placeholder.style.display = "block";
    ImagePreviewHelper.reset({
      containerId: "conteneurApercuImage",
      imgId: "apercuImage",
      errorId: "erreurApercuImage",
    });
    return;
  }

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

    if (placeholder) placeholder.style.display = "none";
    if (conteneurApercuImage) conteneurApercuImage.style.display = "block";
    if (erreur) erreur.style.display = "none";

    ImagePreviewHelper.preview(thumbnailUrl, {
      containerId: "conteneurApercuImage",
      imgId: "apercuImage",
      spinnerId: "spinnerChargementImage",
      errorId: "erreurApercuImage",
      useProxy: true,
    });
  } else {
    vignetteInput.value = "";
    if (conteneurApercuImage) conteneurApercuImage.style.display = "none";
    if (placeholder) placeholder.style.display = "block";

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
function rechercherVideos() {
  if (!estPageAdminWebtv()) {
    return;
  }
  const valeur = String(document.getElementById("champRecherche")?.value || "");
  void chargerWebtvAdmin(valeur, 1);
}

function mettreAJourTableVideos(action, video) {
  const tbody = document.querySelector("#tableauVideos tbody");
  const donneesVideos = document.getElementById("donneesVideos");

  if (action === "create" && video) {
    const nouvelleLigne = creerLigneVideo(video);
    tbody.insertBefore(nouvelleLigne, tbody.firstChild);

    if (donneesVideos) {
      try {
        const videos = JSON.parse(donneesVideos.textContent);
        videos.unshift(video); // Ajouter au début
        donneesVideos.textContent = JSON.stringify(videos);
      } catch (e) {
        console.error("Erreur mise à jour JSON:", e);
      }
    }

    mettreAJourCompteurVideos(1);
  } else if (action === "update" && video) {
    const ligneExistante = document.querySelector(`tr[data-video-id="${video.id}"]`);
    if (ligneExistante) {
      const nouvelleLigne = creerLigneVideo(video);
      ligneExistante.replaceWith(nouvelleLigne);
    }

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

function creerLigneVideo(video) {
  const tr = document.createElement("tr");
  tr.setAttribute("data-video-id", video.id);

  const escapeHtml = (value) =>
    String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");

  const parseDate = new Date(video.created_at || video.updated_at || "");
  const date = Number.isNaN(parseDate.getTime()) ? "" : parseDate.toLocaleDateString("fr-FR");

  const titreBrut = String(video.titre ?? "");
  const titre = escapeHtml(titreBrut);
  const categorie = escapeHtml(video.categorie || "");
  const vignette = escapeHtml(video.vignette || "");
  const payloadEdition = JSON.stringify(video)
    .replace(/&/g, "\\u0026")
    .replace(/</g, "\\u003C")
    .replace(/>/g, "\\u003E")
    .replace(/'/g, "\\u0027");

  tr.innerHTML = `
    <td class="table-video-cell admin-text-center" data-label="Miniature" data-col="image">
      ${
        vignette
          ? `
        <div class="image-container">
          <img
            src="${vignette}"
            alt="Miniature"
            class="video-thumb"
            data-proxy-src-on-error="${vignette}">
          <div class="no-image-fallback admin-hidden">
            <i class="fas fa-link"></i>
            <span>Miniature indisponible</span>
          </div>
        </div>
      `
          : `
        <div class="modern-placeholder no-image video-placeholder">
          <i class="fas fa-video"></i>
        </div>
      `
      }
    </td>
    <td data-label="Titre" data-col="titre"><strong class="admin-text-primary">${titre}</strong></td>
    <td class="admin-text-muted" data-label="Categorie" data-col="categorie">
      <span class="badge-categorie">${categorie}</span>
    </td>
    <td data-label="Plateforme" data-col="plateforme">
      <span class="badge-categorie badge-platform-youtube"><i class="fab fa-youtube"></i> YouTube</span>
    </td>
    <td data-label="Date" data-col="date">${date}</td>
    <td class="text-center" data-label="Actions" data-col="actions">
      <button type="button" class="btn btn-warning btn-sm" data-webtv-edit='${payloadEdition}' title="Modifier">
        <i class="fas fa-edit"></i>
      </button>
      <button type="button" class="btn btn-danger btn-sm" data-webtv-delete-id="${video.id}" data-webtv-delete-title="${titre}" title="Supprimer">
        <i class="fas fa-trash"></i>
      </button>
    </td>
  `;

  return tr;
}

function mettreAJourCompteurVideos(delta) {
  const compteurs = document.querySelectorAll(".stats-badge, .card-value, .value");
  compteurs.forEach((compteur) => {
    const match = compteur.textContent.match(/\d+/);
    if (match) {
      const actuel = parseInt(match[0]);
      const nouveau = Math.max(0, actuel + delta);
      compteur.textContent = compteur.textContent.replace(/\d+/, nouveau);
    }
  });
}
