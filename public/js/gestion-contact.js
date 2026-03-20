function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = String(text ?? "");
  return div.innerHTML;
}

function lireJsonAttribut(value) {
  if (!value) {
    return null;
  }

  try {
    return JSON.parse(value);
  } catch (_error) {
    return null;
  }
}

function getSecurite() {
  return window.SecuriteHelper && typeof window.SecuriteHelper.echapperHtml === "function"
    ? window.SecuriteHelper
    : {
        echapperHtml: escapeHtml,
        echapperAttribut: escapeHtml,
      };
}

function estPageAdminContact() {
  if (document.querySelector("[data-admin-contact='1']")) {
    return true;
  }
  const params = new URLSearchParams(window.location.search);
  return params.get("page") === "admin-contact";
}

function filtreContactActif() {
  const bouton = document.querySelector(".filters .filter-btn[data-contact-filter].active");
  if (!(bouton instanceof HTMLElement)) {
    return "all";
  }
  const filtre = String(bouton.getAttribute("data-contact-filter") || "all")
    .trim()
    .toLowerCase();
  return filtre === "" ? "all" : filtre;
}

function construireUrlContact(statut = "all", q = "", page = 1) {
  const url = new URL(window.location.href);
  url.searchParams.set("page", "admin-contact");
  url.searchParams.delete("action");

  const filtre = String(statut || "all")
    .trim()
    .toLowerCase();
  if (filtre === "" || filtre === "all") {
    url.searchParams.delete("statut");
  } else {
    url.searchParams.set("statut", filtre);
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

async function chargerContactFiltre(statut = "all", q = "", page = 1, options = {}) {
  const url = construireUrlContact(statut, q, page);
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

async function rafraichirContactDepuisUI(resetPage = true) {
  if (!estPageAdminContact()) {
    return;
  }

  const champ = document.getElementById("champRecherche");
  const recherche = champ instanceof HTMLInputElement ? champ.value : "";
  const filtre = filtreContactActif();
  const pageCourante = Number.parseInt(
    String(new URLSearchParams(window.location.search).get("p") || "1"),
    10
  );
  await chargerContactFiltre(
    filtre,
    recherche,
    resetPage ? 1 : Number.isFinite(pageCourante) && pageCourante > 0 ? pageCourante : 1,
    {
      pushState: true,
      scrollToTop: false,
      preserveLocalState: false,
    }
  );
}

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("messageModal");

  if (modal) {
    window.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });
  }
});

document.addEventListener("click", (event) => {
  const target = event.target instanceof Element ? event.target : null;
  if (!target) {
    return;
  }

  const filterBtn = target.closest("[data-contact-filter]");
  if (filterBtn) {
    event.preventDefault();

    if (estPageAdminContact()) {
      document
        .querySelectorAll(".filters .filter-btn[data-contact-filter]")
        .forEach((btn) => btn.classList.remove("active"));
      filterBtn.classList.add("active");
      const statut = String(filterBtn.getAttribute("data-contact-filter") || "all");
      const recherche = String(document.getElementById("champRecherche")?.value || "");
      void chargerContactFiltre(statut, recherche, 1, {
        pushState: true,
        scrollToTop: false,
        preserveLocalState: false,
      });
    } else {
      document
        .querySelectorAll(".filters .filter-btn[data-contact-filter]")
        .forEach((btn) => btn.classList.remove("active"));
      filterBtn.classList.add("active");
    }
    return;
  }

  const contactViewBtn = target.closest("[data-contact-view]");
  if (contactViewBtn) {
    event.preventDefault();
    const payload = lireJsonAttribut(contactViewBtn.getAttribute("data-contact-view"));
    if (payload) {
      voirMessage(payload);
    }
    return;
  }

  const commentViewBtn = target.closest("[data-comment-view]");
  if (commentViewBtn) {
    event.preventDefault();
    const payload = lireJsonAttribut(commentViewBtn.getAttribute("data-comment-view"));
    if (payload) {
      voirCommentaire(payload);
    }
    return;
  }

  const contactStatusBtn = target.closest("[data-contact-set-statut]");
  if (contactStatusBtn) {
    event.preventDefault();
    const id = Number.parseInt(String(contactStatusBtn.getAttribute("data-contact-id") || "0"), 10);
    const statut = String(contactStatusBtn.getAttribute("data-contact-set-statut") || "");
    const nom = String(contactStatusBtn.getAttribute("data-contact-name") || "");
    if (id > 0 && statut && typeof window.changerStatut === "function") {
      window.changerStatut(id, statut, nom);
      closeModal();
    }
    return;
  }

  const deleteContactBtn = target.closest("[data-contact-delete-id]");
  if (deleteContactBtn) {
    event.preventDefault();
    const id = Number.parseInt(
      String(deleteContactBtn.getAttribute("data-contact-delete-id") || "0"),
      10
    );
    const nom = String(deleteContactBtn.getAttribute("data-contact-delete-name") || "contact");
    if (id > 0 && typeof window.supprimerMessage === "function") {
      window.supprimerMessage(id, nom);
      closeModal();
    }
    return;
  }

  const deleteCommentBtn = target.closest("[data-comment-delete-id]");
  if (deleteCommentBtn) {
    event.preventDefault();
    const id = Number.parseInt(
      String(deleteCommentBtn.getAttribute("data-comment-delete-id") || "0"),
      10
    );
    const auteur = String(deleteCommentBtn.getAttribute("data-comment-delete-name") || "Anonyme");
    if (id > 0 && typeof window.supprimerCommentaire === "function") {
      window.supprimerCommentaire(id, auteur);
      closeModal();
    }
    return;
  }

  const redirectBtn = target.closest("[data-comment-redirect]");
  if (redirectBtn) {
    event.preventDefault();
    const href = String(redirectBtn.getAttribute("data-comment-redirect") || "").trim();
    if (href) {
      window.location.href = href;
    }
    return;
  }

  const closeBtn = target.closest("[data-contact-close-modal], [data-comment-close-modal]");
  if (closeBtn) {
    event.preventDefault();
    closeModal();
  }
});

let contactSearchTimer = null;
document.addEventListener("input", (event) => {
  if (!estPageAdminContact()) {
    return;
  }

  const input = event.target instanceof HTMLInputElement ? event.target : null;
  if (!input || input.id !== "champRecherche") {
    return;
  }

  if (contactSearchTimer) {
    clearTimeout(contactSearchTimer);
  }

  contactSearchTimer = window.setTimeout(() => {
    void chargerContactFiltre(filtreContactActif(), input.value, 1, {
      pushState: true,
      scrollToTop: false,
      preserveLocalState: false,
    });
  }, 320);
});

window.addEventListener("popstate", () => {
  if (!estPageAdminContact()) {
    return;
  }

  const url = new URL(window.location.href);
  const statut = String(url.searchParams.get("statut") || "all")
    .trim()
    .toLowerCase();
  const q = String(url.searchParams.get("q") || "");
  const p = Number.parseInt(String(url.searchParams.get("p") || "1"), 10);

  void chargerContactFiltre(
    statut === "" ? "all" : statut,
    q,
    Number.isFinite(p) && p > 0 ? p : 1,
    {
      pushState: false,
      scrollToTop: false,
      preserveLocalState: false,
    }
  );
});

function voirMessage(messageJson) {
  const modal = document.getElementById("messageModal");
  const details = document.getElementById("messageDetails");
  const securite = getSecurite();

  if (!details) {
    return;
  }

  const id = Number.parseInt(String(messageJson.id || "0"), 10);
  const nom = securite.echapperAttribut(messageJson.nom || "");

  details.innerHTML = `
    <div class="contact-modal-details">
      <h3>${securite.echapperHtml(messageJson.sujet)}</h3>
      <p><strong>De:</strong> ${securite.echapperHtml(messageJson.nom)} (${securite.echapperHtml(messageJson.email)})</p>
      <p><strong>Date:</strong> ${securite.echapperHtml(messageJson.date_envoi)}</p>
      <p><strong>Statut:</strong> <span class="role-badge">${securite.echapperHtml(messageJson.statut)}</span></p>
      <hr>
      <p>${securite.echapperHtml(messageJson.message).replace(/\n/g, "<br>")}</p>
      <div class="contact-modal-actions">
        <button type="button" class="btn btn-secondary btn-small" data-contact-set-statut="non_lu" data-contact-id="${id}" data-contact-name="${nom}" title="Marquer comme non lu">Non lu</button>
        <button type="button" class="btn btn-secondary btn-small" data-contact-set-statut="lu" data-contact-id="${id}" data-contact-name="${nom}" title="Marquer comme lu">Lu</button>
        <button type="button" class="btn btn-primary btn-small" data-contact-set-statut="traite" data-contact-id="${id}" data-contact-name="${nom}" title="Marquer comme traite">Traite</button>
        <button type="button" class="btn btn-danger btn-small" data-contact-delete-id="${id}" data-contact-delete-name="${nom}">
          <i class="fas fa-trash"></i> Supprimer
        </button>
      </div>
    </div>
  `;

  if (modal) {
    modal.classList.add("active");
  }
}

function voirCommentaire(commentJson) {
  const modal = document.getElementById("messageModal");
  const details = document.getElementById("messageDetails");
  const securite = getSecurite();

  if (!details) {
    return;
  }

  const parentId = Number.parseInt(String(commentJson.parent_id || "0"), 10) || 0;
  const typeLabel = parentId > 0 ? "Reponse" : "Commentaire";
  const typeClass = parentId > 0 ? "reply" : "parent";
  const parentAuteur = String(commentJson.parent_auteur || "").trim();
  const parentTexte = String(commentJson.parent_texte || "").trim();
  const contexteParent =
    parentId > 0
      ? `
      <div class="comment-video-section">
        <p class="comment-video-section-label"><i class="fas fa-reply"></i> Contexte</p>
        <p class="comment-video-title-main">
          Reponse a ${securite.echapperHtml(parentAuteur || "un commentaire")}
        </p>
        ${
          parentTexte
            ? `<p class="comment-parent-preview-full">${securite
                .echapperHtml(parentTexte)
                .replace(/\n/g, "<br>")}</p>`
            : ""
        }
      </div>`
      : "";

  details.innerHTML = `
    <div class="comment-modal-layout">
      <div class="comment-header-info">
        <div class="comment-author-avatar" aria-hidden="true">
          ${securite.echapperHtml(
            String(commentJson.auteur || "A")
              .charAt(0)
              .toUpperCase()
          )}
        </div>
        <div class="comment-author-details">
          <h3 class="comment-author-main-name">${securite.echapperHtml(commentJson.auteur || "Anonyme")}</h3>
          <p class="comment-author-main-email">
            <i class="fas fa-envelope"></i>
            ${securite.echapperHtml(commentJson.user_email || "-")}
          </p>
          <p class="comment-posted-date">
            <i class="fas fa-clock"></i>
            ${securite.echapperHtml(commentJson.created_at || "-")}
          </p>
          <span class="comment-type-badge ${typeClass}">
            ${typeLabel}
          </span>
        </div>
      </div>

      <div class="comment-video-section">
        <p class="comment-video-section-label"><i class="fas fa-video"></i> Video associee</p>
        <p class="comment-video-title-main">${securite.echapperHtml(commentJson.video_titre || "-")}</p>
      </div>

      ${contexteParent}

      <div class="comment-body-section">
        <p class="comment-body-label"><i class="fas fa-comment"></i> Contenu</p>
        <div class="comment-body-text">
          ${securite.echapperHtml(commentJson.texte || "").replace(/\n/g, "<br>")}
        </div>
      </div>
    </div>
  `;

  if (modal) {
    modal.classList.add("active");
  }
}

function closeModal() {
  const modal = document.getElementById("messageModal");
  if (modal) {
    modal.classList.remove("active");
  }
}

function filtrerMessages(statut) {
  if (estPageAdminContact()) {
    const recherche = String(document.getElementById("champRecherche")?.value || "");
    void chargerContactFiltre(statut, recherche, 1);
  }
}

function rechercherMessages() {
  void rafraichirContactDepuisUI(true);
}

window.appliquerFiltrageContact = rechercherMessages;
