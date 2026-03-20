(() => {
  let projetImageTimeout = null;
  let projetSubmitting = false;

  function ouvrirModaleProjet() {
    const modal = document.getElementById("modaleProjetCreation");
    const form = document.getElementById("formProjet");
    const previewUrl = document.getElementById("projetImagePreviewContainer");
    const previewLocal = document.getElementById("projetLocalPreviewContainer");

    if (!modal) {
      return;
    }

    modal.classList.add("active");
    if (form) {
      form.reset();
    }
    if (previewUrl) {
      previewUrl.classList.remove("active");
    }
    if (previewLocal) {
      previewLocal.classList.remove("active");
    }
  }

  function fermerModaleProjet() {
    const modal = document.getElementById("modaleProjetCreation");
    if (modal) {
      modal.classList.remove("active");
    }
  }

  function previewProjetImage(url) {
    const preview = document.getElementById("projetImagePreview");
    const container = document.getElementById("projetImagePreviewContainer");
    if (!preview || !container) {
      return;
    }

    if (projetImageTimeout) {
      clearTimeout(projetImageTimeout);
    }
    container.classList.remove("active");

    if (url && String(url).trim() !== "") {
      preview.style.opacity = "0.3";
      container.classList.add("active");

      projetImageTimeout = setTimeout(() => {
        preview.style.opacity = "1";
      }, 5000);

      preview.src = url;
      preview.onload = () => {
        clearTimeout(projetImageTimeout);
        preview.style.opacity = "1";
      };
      preview.onerror = () => {
        clearTimeout(projetImageTimeout);
        container.classList.remove("active");
      };
    }
  }

  function afficherToastProjet(type, message) {
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

  function previewProjetLocalImage(input) {
    if (!input.files || !input.files[0]) {
      return;
    }

    const file = input.files[0];
    const allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"];
    if (!allowedTypes.includes(file.type)) {
      afficherToastProjet("error", "Type de fichier non autorise. Utilisez JPG, PNG, GIF ou WebP.");
      input.value = "";
      return;
    }

    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
      afficherToastProjet("error", "Le fichier est trop volumineux. Taille maximale : 5 Mo.");
      input.value = "";
      return;
    }

    const reader = new FileReader();
    const container = document.getElementById("projetLocalPreviewContainer");
    const image = document.getElementById("projetLocalPreview");
    if (!container || !image) {
      return;
    }

    reader.onload = (event) => {
      image.src = String(event.target?.result || "");
      container.classList.add("active");
    };

    reader.onerror = () => {
      afficherToastProjet("error", "Erreur lors de la lecture du fichier.");
      input.value = "";
    };

    reader.readAsDataURL(file);
  }

  function validerFormProjet() {
    const titre = document.getElementById("projet_titre");
    const description = document.getElementById("projet_description");
    let isValid = true;

    document.querySelectorAll('small[id$="_error_projet"]').forEach((el) => {
      el.style.display = "none";
    });

    if (!titre || titre.value.trim().length < 3 || titre.value.trim().length > 200) {
      const error = document.getElementById("titre_error_projet");
      if (error) {
        error.textContent = "Le titre doit contenir entre 3 et 200 caracteres";
        error.style.display = "block";
      }
      isValid = false;
    }

    if (
      !description ||
      description.value.trim().length < 10 ||
      description.value.trim().length > 500
    ) {
      const error = document.getElementById("description_error_projet");
      if (error) {
        error.textContent = "La description doit contenir entre 10 et 500 caracteres";
        error.style.display = "block";
      }
      isValid = false;
    }

    return isValid;
  }

  async function soumettreFormProjet(event) {
    event.preventDefault();

    if (projetSubmitting) {
      return;
    }

    const form = event.currentTarget;
    const submitBtn = form.querySelector('button[type="submit"]');
    if (!submitBtn) {
      return;
    }

    projetSubmitting = true;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creation...';

    if (!validerFormProjet()) {
      projetSubmitting = false;
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Creer le projet';
      return;
    }

    const formData = new FormData(form);

    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
      const response = await fetch("?page=projet_enregistrer", {
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
        afficherToastProjet("success", result.message || "Projet cree avec succes.");
        form.reset();
        const previewUrl = document.getElementById("projetImagePreviewContainer");
        const previewLocal = document.getElementById("projetLocalPreviewContainer");
        if (previewUrl) {
          previewUrl.classList.remove("active");
        }
        if (previewLocal) {
          previewLocal.classList.remove("active");
        }
        fermerModaleProjet();
        window.dispatchEvent(new CustomEvent("catalog:projet-created"));
      } else {
        afficherToastProjet("error", result.message || "Une erreur est survenue");
      }
    } catch (error) {
      console.error("Erreur:", error);
      afficherToastProjet("error", "Erreur lors de la création du projet. Veuillez réessayer.");
    } finally {
      projetSubmitting = false;
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Creer le projet';
    }
  }

  function createParticles() {
    const particles = document.getElementById("particles");
    if (!particles || particles.dataset.ready === "1") {
      return;
    }

    const particleCount = 45;
    for (let i = 0; i < particleCount; i += 1) {
      const particle = document.createElement("div");
      particle.className = "particle";
      particle.style.left = `${Math.random() * 100}%`;
      particle.style.top = `${Math.random() * 100}%`;
      particle.style.animationDelay = `${Math.random() * 6}s`;
      particle.style.animationDuration = `${Math.random() * 3 + 3}s`;
      particles.appendChild(particle);
    }

    particles.dataset.ready = "1";
  }

  function openModalProjet(id) {
    const modal = document.getElementById(`modal-projet-${id}`);
    if (!modal) {
      return;
    }
    modal.classList.add("active");
    document.body.style.overflow = "hidden";
  }

  function closeModalProjet(id) {
    const modal = document.getElementById(`modal-projet-${id}`);
    if (!modal) {
      return;
    }
    modal.classList.remove("active");
    if (!document.querySelector(".modal-detail.active")) {
      document.body.style.overflow = "auto";
    }
  }

  function initialiserEventsGlobaux() {
    document.addEventListener("keydown", (event) => {
      if (event.key === "Enter" || event.key === " ") {
        const target =
          event.target instanceof Element ? event.target.closest("[data-projet-open-modal]") : null;
        if (!(target instanceof Element)) {
          return;
        }

        if (
          event.target instanceof Element &&
          event.target.closest("a, button, input, textarea, select, [contenteditable='true']")
        ) {
          return;
        }

        event.preventDefault();
        const id = Number.parseInt(String(target.getAttribute("data-projet-open-modal") || ""), 10);
        if (Number.isFinite(id)) {
          openModalProjet(id);
        }
        return;
      }

      if (event.key !== "Escape") {
        return;
      }

      const creationModal = document.getElementById("modaleProjetCreation");
      if (creationModal && creationModal.classList.contains("active")) {
        fermerModaleProjet();
      }

      document.querySelectorAll(".modal-detail.active").forEach((modal) => {
        modal.classList.remove("active");
      });

      if (!document.querySelector(".modal-detail.active")) {
        document.body.style.overflow = "auto";
      }
    });

    document.addEventListener("click", (event) => {
      const target = event.target instanceof Element ? event.target : null;
      if (target) {
        if (target.closest("[data-favori-toggle]")) {
          return;
        }

        const openCreation = target.closest("[data-projet-open-creation]");
        if (openCreation) {
          ouvrirModaleProjet();
          return;
        }

        const closeCreation = target.closest("[data-projet-close-creation]");
        if (closeCreation) {
          fermerModaleProjet();
          return;
        }

        const openModal = target.closest("[data-projet-open-modal]");
        if (openModal) {
          const id = Number.parseInt(
            String(openModal.getAttribute("data-projet-open-modal") || ""),
            10
          );
          if (Number.isFinite(id)) {
            openModalProjet(id);
          }
          return;
        }

        const closeModal = target.closest("[data-projet-close-modal]");
        if (closeModal) {
          const id = Number.parseInt(
            String(closeModal.getAttribute("data-projet-close-modal") || ""),
            10
          );
          if (Number.isFinite(id)) {
            closeModalProjet(id);
          }
          return;
        }
      }

      const creationModal = document.getElementById("modaleProjetCreation");
      if (creationModal && event.target === creationModal) {
        fermerModaleProjet();
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

  function initialiser() {
    createParticles();
    initialiserEventsGlobaux();

    const projetImageInput = document.getElementById("projet_image_url");
    if (projetImageInput) {
      projetImageInput.addEventListener("input", () => {
        previewProjetImage(projetImageInput.value);
      });
    }

    const projetFileInput = document.getElementById("projet_image_file");
    if (projetFileInput) {
      projetFileInput.addEventListener("change", () => {
        previewProjetLocalImage(projetFileInput);
      });
    }

    const formProjet = document.getElementById("formProjet");
    if (formProjet) {
      formProjet.addEventListener("submit", soumettreFormProjet);
    }

    initialiserFallbackImages();
  }

  window.ouvrirModaleProjet = ouvrirModaleProjet;
  window.fermerModaleProjet = fermerModaleProjet;
  window.previewProjetImage = previewProjetImage;
  window.previewProjetLocalImage = previewProjetLocalImage;
  window.openModalProjet = openModalProjet;
  window.closeModalProjet = closeModalProjet;

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialiser);
  } else {
    initialiser();
  }
})();
