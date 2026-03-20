function getCsrfToken() {
  const metaToken = document.querySelector('meta[name="csrf-token"]');
  if (!metaToken) {
    console.error("Token CSRF non trouve dans les meta tags.");
    return "";
  }
  const token = metaToken.getAttribute("content");
  return token;
}

function setCsrfToken(token) {
  const valeur = String(token || "").trim();
  if (valeur === "") {
    return;
  }

  const metaToken = document.querySelector('meta[name="csrf-token"]');
  if (metaToken) {
    metaToken.setAttribute("content", valeur);
  }

  document.querySelectorAll('input[name="csrf_token"]').forEach((field) => {
    if (field instanceof HTMLInputElement) {
      field.value = valeur;
    }
  });
}

function extractNewCsrfToken(payload) {
  if (!payload || typeof payload !== "object") {
    return "";
  }

  const token = payload?.data?.new_token ?? payload?.new_token ?? "";
  return String(token || "").trim();
}

async function envoyerAction(action, donnees = {}, canRetry = true) {
  try {
    const formData = new FormData();
    formData.append("action", action);
    formData.append("csrf_token", getCsrfToken());

    for (let [cle, valeur] of Object.entries(donnees)) {
      formData.append(cle, valeur);
    }

    const response = await fetch("?page=admin-cache", {
      method: "POST",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-Token": getCsrfToken(),
      },
      body: formData,
    });

    const result = await response.json();
    const nouveauToken = extractNewCsrfToken(result);
    if (nouveauToken !== "") {
      setCsrfToken(nouveauToken);
    }

    if ((response.status === 403 || response.status === 419) && canRetry && nouveauToken !== "") {
      return envoyerAction(action, donnees, false);
    }

    if (!response.ok) {
      throw new Error(result?.message || `HTTP error! status: ${response.status}`);
    }

    return result;
  } catch (error) {
    console.error("Erreur:", error);

    ToastNotification.erreur("Erreur de connexion au serveur");
    return {
      success: false,
      message: "Erreur de connexion au serveur",
    };
  }
}

async function rafraichirStats() {
  const result = await envoyerAction("statistiques");

  if (result.success) {
    ToastNotification.succes("Statistiques actualisées !");

    if (result.data) {
      mettreAJourAffichageStats(result.data);
    }
  } else {
    ToastNotification.erreur(result.message || "Erreur lors du rafraîchissement");
  }
}

function mettreAJourAffichageStats(stats) {
  const statsCards = document.querySelectorAll(".stat-card .value");
  if (statsCards[0]) statsCards[0].textContent = stats.total_entrees || 0;
  if (statsCards[1]) statsCards[1].textContent = stats.entrees_valides || 0;
  if (statsCards[2]) statsCards[2].textContent = stats.entrees_expirees || 0;
  if (statsCards[3]) statsCards[3].textContent = stats.taille_formatee || "0 Ko";
}

async function nettoyerCache() {
  if (!confirm("Voulez-vous nettoyer les entrées expirées ?")) {
    return;
  }

  const result = await envoyerAction("nettoyer");

  if (result.success) {
    ToastNotification.succes(result.message || "Cache nettoyé avec succès !");

    rafraichirStats();
  } else {
    ToastNotification.erreur(result.message || "Erreur lors du nettoyage");
  }
}

async function viderCache() {
  if (!confirm("Attention : cette action supprimera toutes les entrees du cache. Continuer ?")) {
    return;
  }

  const result = await envoyerAction("vider");

  if (result.success) {
    ToastNotification.succes(result.message || "Cache vidé avec succès !");

    rafraichirStats();
  } else {
    ToastNotification.erreur(result.message || "Erreur lors du vidage du cache");
  }
}

async function basculerCache() {
  const result = await envoyerAction("basculer");

  if (result.success) {
    ToastNotification.succes(result.message || "État du cache modifié");

    const statusIndicator = document.getElementById("status-indicator");
    const cacheStatus = document.querySelector(".cache-status");
    const btnBasculer = document.getElementById("btn-basculer");

    if (result.data && result.data.actif !== undefined) {
      const estActif = result.data.actif;

      if (statusIndicator) {
        statusIndicator.textContent = estActif ? "Actif" : "Inactif";
      }

      if (cacheStatus) {
        cacheStatus.classList.remove("actif", "inactif");
        cacheStatus.classList.add(estActif ? "actif" : "inactif");
      }

      if (btnBasculer) {
        btnBasculer.textContent = estActif ? "Désactiver" : "Activer";
      }
    }
  } else {
    ToastNotification.erreur(result.message || "Erreur lors du changement d'état");
  }
}

document.addEventListener("DOMContentLoaded", function () {
  document.addEventListener("click", function (event) {
    const target =
      event.target instanceof Element ? event.target.closest("[data-cache-action]") : null;
    if (!target) {
      return;
    }

    event.preventDefault();
    const action = String(target.getAttribute("data-cache-action") || "");

    if (action === "refresh") {
      rafraichirStats();
    } else if (action === "clean") {
      nettoyerCache();
    } else if (action === "clear") {
      viderCache();
    } else if (action === "toggle") {
      basculerCache();
    }
  });
});
