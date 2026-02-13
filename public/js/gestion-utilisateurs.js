/**
 * Gestion des Utilisateurs - Admin
 */

function voirUtilisateur(utilisateur) {
  editerUtilisateur(utilisateur);
}

function editerUtilisateur(utilisateur) {
  const modal = document.getElementById("modaleUtilisateur");

  document.getElementById("actionFormulaire").value = "update";
  document.getElementById("idUtilisateur").value = utilisateur.id;
  document.getElementById("nomUtilisateur").value = utilisateur.nom;
  document.getElementById("emailUtilisateur").value = utilisateur.email;
  document.getElementById("roleUtilisateur").value = utilisateur.role;
  document.getElementById("motDePasseUtilisateur").value = "";

  if (modal) {
    modal.classList.add("active");
  }
}

function ouvrirModaleAjout() {
  const modal = document.getElementById("modaleUtilisateur");
  const form = document.querySelector("#modaleUtilisateur form");

  document.getElementById("actionFormulaire").value = "create";
  document.getElementById("idUtilisateur").value = "";

  if (form) {
    form.reset();
  }

  if (modal) {
    modal.classList.add("active");
  }
}

function fermerModale() {
  const modal = document.getElementById("modaleUtilisateur");
  if (modal) {
    modal.classList.remove("active");
  }
}

function rechercherUtilisateurs() {
  const valeur = document.getElementById("champRecherche").value.toLowerCase();
  document
    .querySelectorAll("#tableauUtilisateurs tbody tr")
    .forEach((ligne) => {
      ligne.style.display = ligne.textContent.toLowerCase().includes(valeur)
        ? ""
        : "none";
    });
}

function filtrerUtilisateurs(role) {
  const lignes = document.querySelectorAll("#tableauUtilisateurs tbody tr");
  lignes.forEach((ligne) => {
    const roleUtilisateur = ligne.dataset.role;
    ligne.style.display =
      role === "all" || roleUtilisateur === role ? "" : "none";
  });
}

// Fermer modal en cliquant dehors
document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("modaleUtilisateur");
  const formulaire = document.getElementById("formulaireUtilisateur");

  if (modal) {
    window.addEventListener("click", (e) => {
      if (e.target === modal) fermerModale();
    });
  }

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
        const data = await AjaxHelper.post(
          "?page=admin-utilisateurs",
          formData,
        );

        if (data.success) {
          ToastNotification.succes(data.message);
          fermerModale();

          // Mettre à jour la table dynamiquement
          mettreAJourTableUtilisateurs(action, data.data?.utilisateur);
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

async function supprimerUtilisateur(id, nom) {
  if (!confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${nom}" ?`)) {
    return;
  }

  try {
    const data = await AjaxHelper.post("?page=admin-utilisateurs", {
      action: "delete",
      user_id: id,
    });

    if (data.success) {
      ToastNotification.succes(
        data.message || "Utilisateur supprimé avec succès",
      );

      // Supprimer la ligne du tableau avec animation
      const ligne = document.querySelector(`tr[data-user-id="${id}"]`);
      if (ligne) {
        ligne.style.transition = "opacity 0.3s";
        ligne.style.opacity = "0";
        setTimeout(() => ligne.remove(), 300);
      }

      // Mettre à jour les compteurs
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

console.log("👥 Script gestion-utilisateurs.js chargé");

// Fonction pour mettre à jour la table des utilisateurs dynamiquement
function mettreAJourTableUtilisateurs(action, utilisateur) {
  const tbody = document.querySelector("#tableauUtilisateurs tbody");

  if (action === "create" && utilisateur) {
    // Ajouter une nouvelle ligne
    const nouvelleLigne = creerLigneUtilisateur(utilisateur);
    tbody.insertBefore(nouvelleLigne, tbody.firstChild);

    // Mettre à jour les compteurs
    mettreAJourCompteursUtilisateurs(1, utilisateur.role);
  } else if (action === "update" && utilisateur) {
    // Mettre à jour la ligne existante
    const ligneExistante = document.querySelector(
      `tr[data-user-id="${utilisateur.id}"]`,
    );
    if (ligneExistante) {
      const ancienneRole = ligneExistante.dataset.role;
      const nouvelleLigne = creerLigneUtilisateur(utilisateur);
      ligneExistante.replaceWith(nouvelleLigne);

      // Mettre à jour les compteurs si le rôle a changé
      if (ancienneRole !== utilisateur.role) {
        mettreAJourCompteursUtilisateurs(0, utilisateur.role, ancienneRole);
      }
    }
  }
}

// Fonction pour créer une ligne de tableau pour un utilisateur
function creerLigneUtilisateur(utilisateur) {
  const tr = document.createElement("tr");
  tr.setAttribute("data-user-id", utilisateur.id);
  tr.setAttribute("data-role", utilisateur.role);

  // Échapper les caractères spéciaux pour HTML
  const nom = utilisateur.nom
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
  const email = utilisateur.email
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
  const nomJs = utilisateur.nom.replace(/'/g, "\\'").replace(/"/g, '\\"');

  // Formater la date
  const date = utilisateur.date_creation
    ? new Date(utilisateur.date_creation).toLocaleDateString("fr-FR")
    : "N/A";

  // Déterminer la classe du badge de rôle
  const roleLower = utilisateur.role.toLowerCase();
  let roleBadgeClass = "role-utilisateur";
  let roleAffiche = utilisateur.role;

  if (roleLower === "admin") {
    roleBadgeClass = "role-admin";
    roleAffiche = "Admin";
  } else if (roleLower === "editeur" || roleLower === "éditeur") {
    roleBadgeClass = "role-editeur";
    roleAffiche = "Éditeur";
  } else if (roleLower === "utilisateur") {
    roleBadgeClass = "role-utilisateur";
    roleAffiche = "Utilisateur";
  } else {
    // Capitaliser la première lettre
    roleAffiche = utilisateur.role.charAt(0).toUpperCase() + utilisateur.role.slice(1);
  }

  // Générer l'avatar (2 premières lettres du nom)
  const avatar = nom.substring(0, 2).toUpperCase();

  tr.innerHTML = `
    <td class="user-info">
      <div class="user-avatar">${avatar}</div>
      <div class="user-details">
        <span class="user-name">${nom}</span>
        <span class="user-email">${email}</span>
      </div>
    </td>
    <td>${email}</td>
    <td>
      <span class="role-badge ${roleBadgeClass}">${roleAffiche}</span>
    </td>
    <td>${date}</td>
    <td>
      <div class="table-actions">
        <button class="btn btn-primary btn-small" onclick='voirUtilisateur(${JSON.stringify(utilisateur).replace(/'/g, "\\'")})' title="Voir">
          <i class="fas fa-eye"></i>
        </button>
        <button class="btn btn-warning btn-small" onclick='editerUtilisateur(${JSON.stringify(utilisateur).replace(/'/g, "\\'")})' title="Modifier">
          <i class="fas fa-edit"></i>
        </button>
        <button class="btn btn-danger btn-small" onclick="supprimerUtilisateur(${utilisateur.id}, '${nomJs}')" title="Supprimer">
          <i class="fas fa-trash"></i>
        </button>
      </div>
    </td>
  `;

  return tr;
}

// Fonction pour mettre à jour les compteurs d'utilisateurs
function mettreAJourCompteursUtilisateurs(
  delta,
  nouveauRole = null,
  ancienRole = null,
) {
  // Mettre à jour le compteur total
  const totalCompteur = document.querySelector('.stat-card-value');
  if (totalCompteur && delta !== 0) {
    const actuel = parseInt(totalCompteur.textContent) || 0;
    totalCompteur.textContent = Math.max(0, actuel + delta);
  }

  // Si changement de rôle, mettre à jour les compteurs spécifiques
  if (nouveauRole && ancienRole && nouveauRole !== ancienRole) {
    const statsCards = document.querySelectorAll('.stat-card');

    statsCards.forEach((card) => {
      const label = card.querySelector('.stat-card-label')?.textContent.toLowerCase();
      const valueElement = card.querySelector('.stat-card-value');

      if (!label || !valueElement) return;

      const actuel = parseInt(valueElement.textContent) || 0;

      // Décrémenter l'ancien rôle
      if (label.includes('administrateur') && ancienRole.toLowerCase() === 'admin') {
        valueElement.textContent = Math.max(0, actuel - 1);
      } else if (label.includes('éditeur') && (ancienRole.toLowerCase() === 'editeur' || ancienRole.toLowerCase() === 'éditeur')) {
        valueElement.textContent = Math.max(0, actuel - 1);
      } else if (label.includes('utilisateur') && ancienRole.toLowerCase() === 'utilisateur') {
        valueElement.textContent = Math.max(0, actuel - 1);
      }

      // Incrémenter le nouveau rôle
      if (label.includes('administrateur') && nouveauRole.toLowerCase() === 'admin') {
        valueElement.textContent = actuel + 1;
      } else if (label.includes('éditeur') && (nouveauRole.toLowerCase() === 'editeur' || nouveauRole.toLowerCase() === 'éditeur')) {
        valueElement.textContent = actuel + 1;
      } else if (label.includes('utilisateur') && nouveauRole.toLowerCase() === 'utilisateur') {
        valueElement.textContent = actuel + 1;
      }
    });
  }
  // Si c'est un ajout simple, incrémenter le bon compteur
  else if (delta > 0 && nouveauRole) {
    const statsCards = document.querySelectorAll('.stat-card');

    statsCards.forEach((card) => {
      const label = card.querySelector('.stat-card-label')?.textContent.toLowerCase();
      const valueElement = card.querySelector('.stat-card-value');

      if (!label || !valueElement) return;

      const actuel = parseInt(valueElement.textContent) || 0;

      if (label.includes('administrateur') && nouveauRole.toLowerCase() === 'admin') {
        valueElement.textContent = actuel + delta;
      } else if (label.includes('éditeur') && (nouveauRole.toLowerCase() === 'editeur' || nouveauRole.toLowerCase() === 'éditeur')) {
        valueElement.textContent = actuel + delta;
      } else if (label.includes('utilisateur') && nouveauRole.toLowerCase() === 'utilisateur') {
        valueElement.textContent = actuel + delta;
      }
    });
  }
}
