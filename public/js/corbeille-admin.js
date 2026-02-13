/**
 * Corbeille Admin - Gestion des éléments supprimés
 */

// Récupérer le token CSRF
const csrfToken = document
  .querySelector('meta[name="csrf-token"]')
  ?.getAttribute("content");

// Fonction pour afficher les toasts
function afficherToast(message, type = "success") {
  const toast = document.getElementById("toastNotification");
  toast.textContent = message;
  toast.className = `toast-notification show ${type}`;

  setTimeout(() => {
    toast.classList.remove("show");
  }, 3000);
}

// Gestion des filtres
document.querySelectorAll(".filter-btn").forEach((btn) => {
  btn.addEventListener("click", function () {
    // Activer le bouton cliqué
    document
      .querySelectorAll(".filter-btn")
      .forEach((b) => b.classList.remove("active"));
    this.classList.add("active");

    const filter = this.dataset.filter;
    const rows = document.querySelectorAll("tbody tr[data-type]");

    rows.forEach((row) => {
      if (filter === "tous" || row.dataset.type === filter) {
        row.classList.remove("hidden");
      } else {
        row.classList.add("hidden");
      }
    });
  });
});

// Gestion de la restauration
document.querySelectorAll(".btn-restore").forEach((btn) => {
  btn.addEventListener("click", function () {
    const id = this.dataset.id;
    const type = this.dataset.type;
    const row = this.closest("tr");

    // Récupérer les données de l'élément depuis le script JSON
    const donneesCorbeille = JSON.parse(
      document.getElementById("donneesCorbeille").textContent,
    );
    const element = donneesCorbeille.find((e) => e.id == id && e.type === type);

    if (!element) {
      afficherToast("Élément introuvable", "error");
      return;
    }

    // Remplir la modale avec les données de l'élément
    const badges = {
      article: { color: "blue", icon: "fa-newspaper", label: "Article" },
      projet: { color: "green", icon: "fa-project-diagram", label: "Projet" },
      video: { color: "red", icon: "fa-video", label: "Vidéo" },
      utilisateur: { color: "purple", icon: "fa-user", label: "Utilisateur" },
      message: { color: "orange", icon: "fa-envelope", label: "Message" },
    };

    const badge = badges[element.type] || {
      color: "gray",
      icon: "fa-question",
      label: "Inconnu",
    };

    document.getElementById("restoreElementType").className =
      `element-type badge-${badge.color}`;
    document.getElementById("restoreElementType").innerHTML =
      `<i class="fas ${badge.icon}"></i> ${badge.label}`;

    // Nom / Titre
    let nom = "";
    switch (element.type) {
      case "article":
      case "projet":
      case "video":
        nom = element.titre || element.title || "Sans titre";
        break;
      case "utilisateur":
        nom = element.nom || "Sans nom";
        break;
      case "message":
        nom = `${element.nom} - ${element.sujet}`;
        break;
    }
    document.getElementById("restoreElementTitle").textContent = nom;

    // Description
    let description = "";
    switch (element.type) {
      case "article":
      case "projet":
      case "video":
        description = element.description || "";
        break;
      case "utilisateur":
        description = element.email || "";
        break;
      case "message":
        description = element.message || "";
        break;
    }
    document.getElementById("restoreElementDescription").textContent =
      description;

    // Date
    const date = new Date(element.deleted_at);
    document.getElementById("restoreElementDate").textContent =
      date.toLocaleDateString("fr-FR", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });

    // Stocker les données pour la confirmation
    document.getElementById("confirmRestoreBtn").dataset.id = id;
    document.getElementById("confirmRestoreBtn").dataset.type = type;
    document.getElementById("confirmRestoreBtn").dataset.row = row.rowIndex;

    // Afficher la modale
    document.getElementById("restoreModal").classList.add("active");
  });
});

// Gestion de la confirmation de restauration
document
  .getElementById("confirmRestoreBtn")
  .addEventListener("click", async function () {
    const id = this.dataset.id;
    const type = this.dataset.type;
    const rowIndex = this.dataset.row;
    const row = document.querySelector(`tbody tr:nth-child(${rowIndex})`);

    // Fermer la modale
    fermerModale("restoreModal");

    try {
      const formData = new FormData();
      formData.append("id", id);
      formData.append("type", type);
      formData.append("csrf_token", csrfToken);

      const response = await fetch("?page=admin-corbeille&action=restaurer", {
        method: "POST",
        headers: {
          "X-Requested-With": "XMLHttpRequest",
        },
        body: formData,
      });

      const result = await response.json();

      if (result.success) {
        afficherToast(result.message, "success");
        // Retirer la ligne du tableau avec animation
        if (row) {
          row.style.opacity = "0";
          setTimeout(() => {
            row.remove();
            // Si plus d'éléments, afficher l'état vide
            if (document.querySelectorAll("tbody tr").length === 0) {
              location.reload();
            }
            // Mettre à jour les compteurs
            mettreAJourCompteurs();
          }, 300);
        }
      } else {
        afficherToast(result.message, "error");
      }
    } catch (error) {
      console.error("Erreur:", error);
      afficherToast("Erreur lors de la restauration", "error");
    }
  });

// Gestion de la suppression définitive
document.querySelectorAll(".btn-delete-permanent").forEach((btn) => {
  btn.addEventListener("click", async function () {
    const id = this.dataset.id;
    const type = this.dataset.type;
    const row = this.closest("tr");

    if (
      !confirm(
        "⚠️ ATTENTION !\n\nCette action est IRRÉVERSIBLE.\n\nVoulez-vous vraiment supprimer définitivement cet élément ?",
      )
    ) {
      return;
    }

    try {
      const formData = new FormData();
      formData.append("id", id);
      formData.append("type", type);
      formData.append("csrf_token", csrfToken);

      const response = await fetch(
        "?page=admin-corbeille&action=supprimer-definitivement",
        {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
          body: formData,
        },
      );

      const result = await response.json();

      if (result.success) {
        afficherToast(result.message, "success");
        // Retirer la ligne du tableau avec animation
        row.style.opacity = "0";
        setTimeout(() => {
          row.remove();
          // Si plus d'éléments, afficher l'état vide
          if (document.querySelectorAll("tbody tr").length === 0) {
            location.reload();
          }
          // Mettre à jour les compteurs
          mettreAJourCompteurs();
        }, 300);
      } else {
        afficherToast(result.message, "error");
      }
    } catch (error) {
      console.error("Erreur:", error);
      afficherToast("Erreur lors de la suppression", "error");
    }
  });
});

// Gestion du bouton "Restaurer tout"
const restaurerTousBtn = document.getElementById("restaurerTousBtn");
if (restaurerTousBtn) {
  restaurerTousBtn.addEventListener("click", async function () {
    const totalElements = document.querySelectorAll("tbody tr").length;

    if (
      !confirm(
        `Vous êtes sur le point de restaurer tous les ${totalElements} éléments de la corbeille.\n\nVoulez-vous continuer ?`,
      )
    ) {
      return;
    }

    try {
      const formData = new FormData();
      formData.append("csrf_token", csrfToken);

      const response = await fetch(
        "?page=admin-corbeille&action=restaurer-tous",
        {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
          body: formData,
        },
      );

      const result = await response.json();

      if (result.success) {
        afficherToast(result.message, "success");
        setTimeout(() => {
          location.reload();
        }, 1500);
      } else {
        afficherToast(result.message, "error");
      }
    } catch (error) {
      console.error("Erreur:", error);
      afficherToast(
        "Erreur lors de la restauration de tous les éléments",
        "error",
      );
    }
  });
}

// Gestion du bouton "Vider la corbeille"
const viderCorbeilleBtn = document.getElementById("viderCorbeilleBtn");
if (viderCorbeilleBtn) {
  viderCorbeilleBtn.addEventListener("click", async function () {
    const totalElements = document.querySelectorAll("tbody tr").length;

    if (
      !confirm(
        `⚠️ ATTENTION !\n\nVous êtes sur le point de supprimer DÉFINITIVEMENT tous les ${totalElements} éléments de la corbeille.\n\nCette action est IRRÉVERSIBLE.\n\nVoulez-vous continuer ?`,
      )
    ) {
      return;
    }

    // Double confirmation pour une action aussi critique
    if (
      !confirm(
        "Êtes-vous vraiment sûr ? Cette action ne peut pas être annulée.",
      )
    ) {
      return;
    }

    try {
      const formData = new FormData();
      formData.append("csrf_token", csrfToken);

      const response = await fetch(
        "?page=admin-corbeille&action=vider-corbeille",
        {
          method: "POST",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
          },
          body: formData,
        },
      );

      const result = await response.json();

      if (result.success) {
        afficherToast(result.message, "success");
        setTimeout(() => {
          location.reload();
        }, 1500);
      } else {
        afficherToast(result.message, "error");
      }
    } catch (error) {
      console.error("Erreur:", error);
      afficherToast("Erreur lors du vidage de la corbeille", "error");
    }
  });
}

// Fonction pour mettre à jour les compteurs dans les boutons de filtre
function mettreAJourCompteurs() {
  const rows = document.querySelectorAll("tbody tr[data-type]");
  const compteurs = {
    tous: rows.length,
    article: 0,
    projet: 0,
    video: 0,
    utilisateur: 0,
    message: 0,
  };

  rows.forEach((row) => {
    const type = row.dataset.type;
    if (compteurs[type] !== undefined) {
      compteurs[type]++;
    }
  });

  // Mettre à jour le texte des boutons
  document.querySelectorAll(".filter-btn").forEach((btn) => {
    const filter = btn.dataset.filter;
    if (compteurs[filter] !== undefined) {
      const icon = btn.querySelector("i").outerHTML;
      const label = btn.textContent.split("(")[0].trim();
      btn.innerHTML = `${icon} ${label} (${compteurs[filter]})`;
    }
  });
}

// Fonction pour fermer les modales
function fermerModale(modalId) {
  document.getElementById(modalId).classList.remove("active");
}

// Fermer les modales en cliquant en dehors
document.addEventListener("click", function (event) {
  if (event.target.classList.contains("corbeille-modal")) {
    event.target.classList.remove("active");
  }
});

// Animation d'entrée pour les lignes
document.addEventListener("DOMContentLoaded", () => {
  const rows = document.querySelectorAll("tbody tr");
  rows.forEach((row, index) => {
    row.style.opacity = "0";
    row.style.transform = "translateY(20px)";
    setTimeout(() => {
      row.style.transition = "opacity 0.3s ease, transform 0.3s ease";
      row.style.opacity = "1";
      row.style.transform = "translateY(0)";
    }, index * 50);
  });
});
