function parseJsonAttribut(value) {
  if (!value) {
    return null;
  }

  try {
    return JSON.parse(value);
  } catch (_error) {
    return null;
  }
}

function estPageAdminUtilisateurs() {
  if (document.querySelector("[data-admin-utilisateurs='1']")) {
    return true;
  }
  const params = new URLSearchParams(window.location.search);
  return params.get("page") === "admin-utilisateurs";
}

function filtreRoleActif() {
  const actif = document.querySelector(".filters .filter-btn[data-users-filter].active");
  if (!(actif instanceof HTMLElement)) {
    return "all";
  }
  const role = String(actif.getAttribute("data-users-filter") || "all")
    .trim()
    .toLowerCase();
  return role === "" ? "all" : role;
}

function construireUrlUtilisateurs(role = "all", q = "", page = 1) {
  const url = new URL(window.location.href);
  url.searchParams.set("page", "admin-utilisateurs");
  url.searchParams.delete("action");

  const roleNormalise = String(role || "all")
    .trim()
    .toLowerCase();
  if (roleNormalise === "" || roleNormalise === "all") {
    url.searchParams.delete("role");
  } else {
    url.searchParams.set("role", roleNormalise);
  }

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

async function chargerUtilisateurs(role = "all", q = "", page = 1, options = {}) {
  const url = construireUrlUtilisateurs(role, q, page);
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

function voirUtilisateur(utilisateur) {
  editerUtilisateur(utilisateur);
}

function editerUtilisateur(utilisateur) {
  const modal = document.getElementById("modaleUtilisateur");
  const role = String(utilisateur.role || "")
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "");

  document.getElementById("actionFormulaire").value = "update";
  document.getElementById("idUtilisateur").value = utilisateur.id;
  document.getElementById("nomUtilisateur").value = utilisateur.nom;
  document.getElementById("emailUtilisateur").value = utilisateur.email;
  document.getElementById("roleUtilisateur").value = role || "utilisateur";
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
  if (!estPageAdminUtilisateurs()) {
    return;
  }
  const q = String(document.getElementById("champRecherche")?.value || "");
  void chargerUtilisateurs(filtreRoleActif(), q, 1);
}

function filtrerUtilisateurs(role) {
  if (!estPageAdminUtilisateurs()) {
    return;
  }
  const q = String(document.getElementById("champRecherche")?.value || "");
  void chargerUtilisateurs(role, q, 1);
}

let utilisateurSearchTimer = null;

document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("modaleUtilisateur");
  const formulaire = document.getElementById("formulaireUtilisateur");

  if (modal) {
    window.addEventListener("click", (e) => {
      if (e.target === modal) {
        fermerModale();
      }
    });
  }

  if (formulaire) {
    formulaire.addEventListener("submit", async function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const action = String(formData.get("action") || "update");
      const boutonSoumettre = this.querySelector('button[type="submit"]');

      if (boutonSoumettre instanceof HTMLButtonElement) {
        boutonSoumettre.disabled = true;
        boutonSoumettre.textContent = "Envoi en cours...";
      }

      try {
        const data = await AjaxHelper.post("?page=admin-utilisateurs", formData);

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
        if (boutonSoumettre instanceof HTMLButtonElement) {
          boutonSoumettre.disabled = false;
          boutonSoumettre.textContent = action === "create" ? "Creer" : "Modifier";
        }
      }
    });
  }
});

document.addEventListener("click", (event) => {
  const target = event.target instanceof Element ? event.target : null;
  if (!target) {
    return;
  }

  const openCreateBtn = target.closest("[data-users-open-create]");
  if (openCreateBtn) {
    event.preventDefault();
    ouvrirModaleAjout();
    return;
  }

  const filterBtn = target.closest("[data-users-filter]");
  if (filterBtn) {
    event.preventDefault();
    const role = String(filterBtn.getAttribute("data-users-filter") || "all");

    if (estPageAdminUtilisateurs()) {
      document
        .querySelectorAll(".filters .filter-btn[data-users-filter]")
        .forEach((btn) => btn.classList.remove("active"));
      filterBtn.classList.add("active");
      void chargerUtilisateurs(
        role,
        String(document.getElementById("champRecherche")?.value || ""),
        1
      );
    }
    return;
  }

  const viewBtn = target.closest("[data-users-view]");
  if (viewBtn) {
    event.preventDefault();
    const utilisateur = parseJsonAttribut(viewBtn.getAttribute("data-users-view"));
    if (utilisateur) {
      voirUtilisateur(utilisateur);
    }
    return;
  }

  const editBtn = target.closest("[data-users-edit]");
  if (editBtn) {
    event.preventDefault();
    const utilisateur = parseJsonAttribut(editBtn.getAttribute("data-users-edit"));
    if (utilisateur) {
      editerUtilisateur(utilisateur);
    }
    return;
  }

  const deleteBtn = target.closest("[data-users-delete-id]");
  if (deleteBtn) {
    event.preventDefault();
    const id = Number.parseInt(String(deleteBtn.getAttribute("data-users-delete-id") || "0"), 10);
    const nom = String(deleteBtn.getAttribute("data-users-delete-name") || "Utilisateur");
    if (id > 0) {
      supprimerUtilisateur(id, nom);
    }
    return;
  }

  const closeBtn = target.closest("[data-users-close-modal]");
  if (closeBtn) {
    event.preventDefault();
    fermerModale();
  }
});

document.addEventListener("input", (event) => {
  if (!estPageAdminUtilisateurs()) {
    return;
  }

  const input = event.target instanceof HTMLInputElement ? event.target : null;
  if (!input || input.id !== "champRecherche") {
    return;
  }

  if (utilisateurSearchTimer) {
    clearTimeout(utilisateurSearchTimer);
  }

  utilisateurSearchTimer = window.setTimeout(() => {
    void chargerUtilisateurs(filtreRoleActif(), input.value, 1, {
      pushState: true,
      scrollToTop: false,
      preserveLocalState: false,
    });
  }, 320);
});

window.addEventListener("popstate", () => {
  if (!estPageAdminUtilisateurs()) {
    return;
  }

  const url = new URL(window.location.href);
  const role = String(url.searchParams.get("role") || "all")
    .trim()
    .toLowerCase();
  const q = String(url.searchParams.get("q") || "");
  const p = Number.parseInt(String(url.searchParams.get("p") || "1"), 10);

  void chargerUtilisateurs(role === "" ? "all" : role, q, Number.isFinite(p) && p > 0 ? p : 1, {
    pushState: false,
    scrollToTop: false,
    preserveLocalState: false,
  });
});

async function supprimerUtilisateur(id, nom) {
  if (!confirm(`Etes-vous sur de vouloir supprimer l'utilisateur "${nom}" ?`)) {
    return;
  }

  try {
    const data = await AjaxHelper.post("?page=admin-utilisateurs", {
      action: "delete",
      user_id: id,
    });

    if (data.success) {
      ToastNotification.succes(data.message || "Utilisateur supprime avec succes");

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
