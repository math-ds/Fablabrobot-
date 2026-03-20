(() => {
  let articleImageTimeout = null;
  let articleSubmitting = false;

  function ouvrirModaleArticle() {
    const modal = document.getElementById("modaleArticleCreation");
    const form = document.getElementById("formArticle");
    const preview = document.getElementById("articleImagePreviewContainer");
    const localPreview = document.getElementById("articleLocalPreviewContainer");

    if (!modal) {
      return;
    }

    modal.classList.add("active");
    if (form) {
      form.reset();
    }
    if (preview) {
      preview.classList.remove("active");
    }
    if (localPreview) {
      localPreview.classList.remove("active");
    }
  }

  function fermerModaleArticle() {
    const modal = document.getElementById("modaleArticleCreation");
    if (modal) {
      modal.classList.remove("active");
    }
  }

  function openModalArticleDetail(id) {
    const modal = document.getElementById(`modal-article-${id}`);
    if (!modal) {
      return;
    }
    modal.classList.add("active");
    document.body.style.overflow = "hidden";
  }

  function closeModalArticleDetail(id) {
    const modal = document.getElementById(`modal-article-${id}`);
    if (!modal) {
      return;
    }
    modal.classList.remove("active");
    if (!document.querySelector(".modal-detail.active")) {
      document.body.style.overflow = "auto";
    }
  }

  function previewArticleImage(url) {
    const preview = document.getElementById("articleImagePreview");
    const container = document.getElementById("articleImagePreviewContainer");
    const spinner = document.getElementById("articleLoadingSpinner");

    if (!preview || !container || !spinner) {
      return;
    }

    if (articleImageTimeout) {
      clearTimeout(articleImageTimeout);
    }

    container.classList.remove("active");
    spinner.classList.remove("active");

    if (url && String(url).trim() !== "") {
      container.classList.add("active");
      spinner.classList.add("active");
      preview.style.opacity = "0.3";

      articleImageTimeout = setTimeout(() => {
        spinner.classList.remove("active");
        preview.style.opacity = "1";
      }, 5000);

      preview.src = url;
      preview.onload = () => {
        clearTimeout(articleImageTimeout);
        spinner.classList.remove("active");
        preview.style.opacity = "1";
      };
      preview.onerror = () => {
        clearTimeout(articleImageTimeout);
        spinner.classList.remove("active");
        container.classList.remove("active");
      };
    }
  }

  function previewArticleLocalImage(input) {
    if (!input.files || !input.files[0]) {
      return;
    }

    const file = input.files[0];
    const allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"];
    if (!allowedTypes.includes(file.type)) {
      afficherToastArticle(
        "error",
        "Type de fichier non autorise. Utilisez JPG, PNG, GIF ou WebP."
      );
      input.value = "";
      return;
    }

    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
      afficherToastArticle("error", "Le fichier est trop volumineux. Taille maximale : 5 Mo.");
      input.value = "";
      return;
    }

    const reader = new FileReader();
    const container = document.getElementById("articleLocalPreviewContainer");
    const image = document.getElementById("articleLocalPreview");
    if (!container || !image) {
      return;
    }

    reader.onload = (event) => {
      image.src = String(event.target?.result || "");
      container.classList.add("active");
    };

    reader.onerror = () => {
      afficherToastArticle("error", "Erreur lors de la lecture du fichier.");
      input.value = "";
    };

    reader.readAsDataURL(file);
  }

  function validerFormArticle() {
    const titre = document.getElementById("article_titre");
    const contenu = document.getElementById("article_contenu");
    let isValid = true;

    document.querySelectorAll('small[id$="_error"]').forEach((element) => {
      element.style.display = "none";
    });

    if (!titre || titre.value.trim().length < 5 || titre.value.trim().length > 200) {
      const error = document.getElementById("titre_error");
      if (error) {
        error.textContent = "Le titre doit contenir entre 5 et 200 caracteres";
        error.style.display = "block";
      }
      isValid = false;
    }

    if (!contenu || contenu.value.trim().length < 10 || contenu.value.trim().length > 10000) {
      const error = document.getElementById("contenu_error");
      if (error) {
        error.textContent = "Le contenu doit contenir entre 10 et 10 000 caracteres";
        error.style.display = "block";
      }
      isValid = false;
    }

    return isValid;
  }

  function afficherToastArticle(type, message) {
    if (typeof PublicNotification !== "undefined") {
      if (type === "success") {
        PublicNotification.succes(message);
        return;
      }
      PublicNotification.erreur(message);
      return;
    }

    if (typeof ToastNotification !== "undefined" && type === "success") {
      ToastNotification.succes(message);
      return;
    }

    if (typeof ToastNotification !== "undefined") {
      ToastNotification.erreur(message);
      return;
    }

    console[type === "success" ? "log" : "error"](message);
  }

  function initialiserSoumissionFormulaire() {
    const formArticle = document.getElementById("formArticle");
    if (!formArticle) {
      return;
    }

    formArticle.addEventListener("submit", async (event) => {
      event.preventDefault();

      if (articleSubmitting) {
        return;
      }

      const submitBtn = formArticle.querySelector('button[type="submit"]');
      if (!submitBtn) {
        return;
      }

      articleSubmitting = true;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publication...';

      if (!validerFormArticle()) {
        articleSubmitting = false;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Publier';
        return;
      }

      const formData = new FormData(formArticle);

      try {
        const csrfToken = document
          .querySelector('meta[name="csrf-token"]')
          ?.getAttribute("content");
        const response = await fetch("?page=article_enregistrer", {
          method: "POST",
          body: formData,
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            ...(csrfToken ? { "X-CSRF-Token": csrfToken } : {}),
          },
          credentials: "same-origin",
        });

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        if (result.success) {
          afficherToastArticle("success", result.message || "Article publie avec succes.");
          formArticle.reset();
          const preview = document.getElementById("articleImagePreviewContainer");
          const localPreview = document.getElementById("articleLocalPreviewContainer");
          if (preview) {
            preview.classList.remove("active");
          }
          if (localPreview) {
            localPreview.classList.remove("active");
          }
          fermerModaleArticle();
          window.dispatchEvent(new CustomEvent("catalog:article-created"));
        } else {
          afficherToastArticle("error", result.message || "Une erreur est survenue");
        }
      } catch (error) {
        console.error("Erreur:", error);
        afficherToastArticle(
          "error",
          "Erreur lors de la publication de l'article. Veuillez réessayer."
        );
      } finally {
        articleSubmitting = false;
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Publier';
      }
    });
  }

  function initialiserInteractionsGlobales() {
    document.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        const trigger =
          event.target instanceof Element
            ? event.target.closest("[data-article-open-modal]")
            : null;
        if (!(trigger instanceof Element)) {
          return;
        }

        if (
          event.target instanceof Element &&
          event.target.closest("a, button, input, textarea, select, [contenteditable='true']")
        ) {
          return;
        }

        event.preventDefault();
        const id = Number.parseInt(
          String(trigger.getAttribute("data-article-open-modal") || ""),
          10
        );
        if (Number.isFinite(id)) {
          openModalArticleDetail(id);
        }
        return;
      }

      if (event.key !== "Escape") {
        return;
      }

      const modalCreation = document.getElementById("modaleArticleCreation");
      if (modalCreation && modalCreation.classList.contains("active")) {
        fermerModaleArticle();
      }

      document.querySelectorAll(".modal-detail.active").forEach((modalDetail) => {
        modalDetail.classList.remove("active");
      });

      if (!document.querySelector(".modal-detail.active")) {
        document.body.style.overflow = "auto";
      }
    });

    document.addEventListener("click", (event) => {
      const trigger = event.target instanceof Element ? event.target : null;
      if (trigger) {
        if (trigger.closest("[data-favori-toggle]")) {
          return;
        }

        const stopPropagationLink = trigger.closest(".js-stop-propagation");
        if (stopPropagationLink) {
          event.stopPropagation();
          return;
        }

        const openCreation = trigger.closest("[data-article-open-creation]");
        if (openCreation) {
          ouvrirModaleArticle();
          return;
        }

        const closeCreation = trigger.closest("[data-article-close-creation]");
        if (closeCreation) {
          fermerModaleArticle();
          return;
        }

        const openModal = trigger.closest("[data-article-open-modal]");
        if (openModal) {
          const id = Number.parseInt(
            String(openModal.getAttribute("data-article-open-modal") || ""),
            10
          );
          if (Number.isFinite(id)) {
            openModalArticleDetail(id);
          }
          return;
        }

        const closeModal = trigger.closest("[data-article-close-modal]");
        if (closeModal) {
          const id = Number.parseInt(
            String(closeModal.getAttribute("data-article-close-modal") || ""),
            10
          );
          if (Number.isFinite(id)) {
            closeModalArticleDetail(id);
          }
          return;
        }
      }

      const modalCreation = document.getElementById("modaleArticleCreation");
      if (modalCreation && event.target === modalCreation) {
        fermerModaleArticle();
      }

      const modalDetail =
        event.target instanceof Element ? event.target.closest(".modal-detail") : null;
      if (modalDetail && event.target === modalDetail) {
        modalDetail.classList.remove("active");
        if (!document.querySelector(".modal-detail.active")) {
          document.body.style.overflow = "auto";
        }
      }
    });
  }

  function initialiserFallbackImages(root = document) {
    root.querySelectorAll("img.js-fallback-next-image").forEach((image) => {
      if (image.dataset.fallbackBound === "1") {
        return;
      }
      image.dataset.fallbackBound = "1";
      image.addEventListener("error", () => {
        image.style.display = "none";
        const next = image.nextElementSibling;
        if (next) {
          next.style.display = "flex";
        }
      });
    });
  }

  function initialiserPreviewInput() {
    const input = document.getElementById("article_image_url");
    if (!input) {
      return;
    }

    input.addEventListener("input", () => {
      previewArticleImage(input.value);
    });

    const fileInput = document.getElementById("article_image_file");
    if (fileInput) {
      fileInput.addEventListener("change", () => {
        previewArticleLocalImage(fileInput);
      });
    }
  }

  function initialiser() {
    initialiserInteractionsGlobales();
    initialiserSoumissionFormulaire();
    initialiserPreviewInput();
    initialiserFallbackImages();
  }

  window.ouvrirModaleArticle = ouvrirModaleArticle;
  window.fermerModaleArticle = fermerModaleArticle;
  window.openModalArticleDetail = openModalArticleDetail;
  window.closeModalArticleDetail = closeModalArticleDetail;
  window.previewArticleImage = previewArticleImage;
  window.previewArticleLocalImage = previewArticleLocalImage;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialiser);
  } else {
    initialiser();
  }
})();
