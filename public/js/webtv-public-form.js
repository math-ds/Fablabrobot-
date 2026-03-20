(() => {
  let videoPublicSubmitting = false;

  function extraireIdYoutubePublic(url) {
    const value = String(url || "").trim();
    if (value === "") {
      return null;
    }

    const patterns = [
      /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/,
      /youtube\.com\/v\/([a-zA-Z0-9_-]{11})/,
    ];

    for (const pattern of patterns) {
      const match = value.match(pattern);
      if (match && match[1]) {
        return match[1];
      }
    }

    return null;
  }

  function mettreAJourPreviewYoutubePublic(url) {
    const container = document.getElementById("video_preview_container");
    const image = document.getElementById("video_preview_image");
    const spinner = document.getElementById("video_preview_spinner");
    const placeholder = document.getElementById("video_preview_placeholder");
    const error = document.getElementById("video_preview_error");
    const youtubeId = extraireIdYoutubePublic(url);

    if (!container || !image || !spinner || !placeholder || !error) {
      return;
    }

    error.style.display = "none";
    if (!youtubeId) {
      container.classList.add("active");
      image.style.display = "none";
      spinner.style.display = "none";
      placeholder.style.display = "flex";
      image.removeAttribute("src");
      return;
    }

    const thumbnailUrl = `https://img.youtube.com/vi/${youtubeId}/hqdefault.jpg`;
    container.classList.add("active");
    placeholder.style.display = "none";
    image.style.display = "none";
    spinner.style.display = "flex";

    image.onload = () => {
      spinner.style.display = "none";
      image.style.display = "block";
      error.style.display = "none";
    };

    image.onerror = () => {
      spinner.style.display = "none";
      image.style.display = "none";
      placeholder.style.display = "flex";
      error.style.display = "block";
    };

    image.src = thumbnailUrl;
  }

  function ouvrirModaleVideo() {
    const modal = document.getElementById("modaleVideoCreation");
    const form = document.getElementById("formVideoPublic");
    if (!modal) {
      return;
    }

    if (form) {
      form.reset();
    }

    document.querySelectorAll("#formVideoPublic .champ-erreur").forEach((element) => {
      element.textContent = "";
      element.style.display = "none";
    });

    mettreAJourPreviewYoutubePublic("");
    modal.classList.add("active");
  }

  function fermerModaleVideo() {
    const modal = document.getElementById("modaleVideoCreation");
    if (modal) {
      modal.classList.remove("active");
    }
  }

  function afficherToastVideoPublic(type, message) {
    if (typeof PublicNotification !== "undefined") {
      if (type === "success") {
        PublicNotification.succes(message);
        return;
      }
      PublicNotification.erreur(message);
      return;
    }

    if (typeof ToastNotification !== "undefined") {
      if (type === "success") {
        ToastNotification.succes(message);
        return;
      }
      ToastNotification.erreur(message);
      return;
    }

    console[type === "success" ? "log" : "error"](message);
  }

  function validerFormVideoPublic() {
    const titre = document.getElementById("video_titre");
    const description = document.getElementById("video_description");
    const categorie = document.getElementById("video_categorie");
    const youtube = document.getElementById("video_youtube_url");

    let valide = true;
    const resetErreur = (id) => {
      const el = document.getElementById(id);
      if (!el) {
        return;
      }
      el.textContent = "";
      el.style.display = "none";
    };

    resetErreur("video_titre_error");
    resetErreur("video_description_error");
    resetErreur("video_categorie_error");
    resetErreur("video_youtube_error");

    if (!titre || titre.value.trim().length < 3 || titre.value.trim().length > 200) {
      const err = document.getElementById("video_titre_error");
      if (err) {
        err.textContent = "Le titre doit contenir entre 3 et 200 caracteres.";
        err.style.display = "block";
      }
      valide = false;
    }

    if (
      !description ||
      description.value.trim().length < 5 ||
      description.value.trim().length > 1500
    ) {
      const err = document.getElementById("video_description_error");
      if (err) {
        err.textContent = "La description doit contenir entre 5 et 1500 caracteres.";
        err.style.display = "block";
      }
      valide = false;
    }

    if (!categorie || categorie.value.trim() === "") {
      const err = document.getElementById("video_categorie_error");
      if (err) {
        err.textContent = "Veuillez sélectionner une catégorie.";
        err.style.display = "block";
      }
      valide = false;
    }

    const youtubeValue = youtube ? youtube.value.trim() : "";
    if (
      youtubeValue === "" ||
      (!youtubeValue.includes("youtube.com") && !youtubeValue.includes("youtu.be"))
    ) {
      const err = document.getElementById("video_youtube_error");
      if (err) {
        err.textContent = "Veuillez saisir une URL YouTube valide.";
        err.style.display = "block";
      }
      valide = false;
    }

    return valide;
  }

  async function soumettreFormulaire(event) {
    event.preventDefault();
    if (videoPublicSubmitting) {
      return;
    }

    const formVideoPublic = event.currentTarget;
    const submitBtn = formVideoPublic.querySelector('button[type="submit"]');
    const submitHtml = submitBtn ? submitBtn.innerHTML : "";

    if (!validerFormVideoPublic()) {
      return;
    }

    videoPublicSubmitting = true;
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publication...';
    }

    try {
      const formData = new FormData(formVideoPublic);
      const response = await fetch("?page=webtv_enregistrer", {
        method: "POST",
        body: formData,
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
        credentials: "same-origin",
      });

      let result = null;
      const contentType = String(response.headers.get("content-type") || "").toLowerCase();
      if (contentType.includes("application/json")) {
        result = await response.json();
      } else {
        const texte = await response.text();
        if (texte.trim().startsWith("<!DOCTYPE") || texte.trim().startsWith("<html")) {
          throw new Error("Reponse serveur invalide. Rechargez la page puis reessayez.");
        }
        throw new Error("Reponse serveur inattendue.");
      }

      if (!response.ok || !result?.success) {
        throw new Error(result?.message || "Erreur lors de la publication de la video.");
      }

      afficherToastVideoPublic("success", result.message || "Video ajoutee avec succes.");
      formVideoPublic.reset();
      mettreAJourPreviewYoutubePublic("");
      fermerModaleVideo();
      window.dispatchEvent(new CustomEvent("catalog:webtv-created"));
    } catch (error) {
      afficherToastVideoPublic(
        "error",
        error?.message || "Erreur lors de la publication de la video."
      );
    } finally {
      videoPublicSubmitting = false;
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = submitHtml || '<i class="fas fa-paper-plane"></i> Publier';
      }
    }
  }

  function initialiser() {
    const formVideoPublic = document.getElementById("formVideoPublic");
    if (!formVideoPublic) {
      return;
    }

    formVideoPublic.addEventListener("submit", soumettreFormulaire);

    const youtubeInput = document.getElementById("video_youtube_url");
    if (youtubeInput) {
      youtubeInput.addEventListener("input", () => {
        mettreAJourPreviewYoutubePublic(youtubeInput.value);
      });
    }

    document.addEventListener("keydown", (event) => {
      if (event.key !== "Escape") {
        return;
      }
      const modal = document.getElementById("modaleVideoCreation");
      if (modal && modal.classList.contains("active")) {
        fermerModaleVideo();
      }
    });

    document.addEventListener("click", (event) => {
      const trigger = event.target instanceof Element ? event.target : null;
      if (trigger) {
        const openCreation = trigger.closest("[data-webtv-open-creation]");
        if (openCreation) {
          ouvrirModaleVideo();
          return;
        }

        const closeCreation = trigger.closest("[data-webtv-close-creation]");
        if (closeCreation) {
          fermerModaleVideo();
          return;
        }
      }

      const modal = document.getElementById("modaleVideoCreation");
      if (modal && event.target === modal) {
        fermerModaleVideo();
      }
    });
  }

  window.ouvrirModaleVideo = ouvrirModaleVideo;
  window.fermerModaleVideo = fermerModaleVideo;
  window.extraireIdYoutubePublic = extraireIdYoutubePublic;
  window.mettreAJourPreviewYoutubePublic = mettreAJourPreviewYoutubePublic;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialiser);
  } else {
    initialiser();
  }
})();
