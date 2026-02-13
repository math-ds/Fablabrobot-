/**
 * Gestion des Messages de Contact - Admin
 */

document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("messageModal");
  const closeBtn = document.querySelector(".contact-close-modal");

  if (closeBtn) {
    closeBtn.addEventListener("click", closeModal);
  }

  if (modal) {
    window.addEventListener("click", (e) => e.target === modal && closeModal());
  }
});

function voirMessage(messageJson) {
  const modal = document.getElementById("messageModal");
  const details = document.getElementById("messageDetails");

  if (!details) return;

  // Sécurité : utiliser SecuriteHelper pour échapper correctement
  details.innerHTML = `
    <div style="padding: 20px;">
      <h3>${SecuriteHelper.echapperHtml(messageJson.sujet)}</h3>
      <p><strong>De:</strong> ${SecuriteHelper.echapperHtml(messageJson.nom)} (${SecuriteHelper.echapperHtml(messageJson.email)})</p>
      <p><strong>Date:</strong> ${SecuriteHelper.echapperHtml(messageJson.date_envoi)}</p>
      <p><strong>Statut:</strong> <span class="role-badge">${SecuriteHelper.echapperHtml(messageJson.statut)}</span></p>
      <hr>
      <p>${SecuriteHelper.echapperHtml(messageJson.message).replace(/\n/g, "<br>")}</p>
      <div style="margin-top: 20px; display: flex; gap: 8px; flex-wrap: wrap;">
        <button class="btn btn-secondary btn-small" onclick="changerStatut(${messageJson.id}, 'non_lu', '${SecuriteHelper.echapperAttribut(messageJson.nom)}'); closeModal();" title="Marquer comme non lu">Non lu</button>
        <button class="btn btn-secondary btn-small" onclick="changerStatut(${messageJson.id}, 'lu', '${SecuriteHelper.echapperAttribut(messageJson.nom)}'); closeModal();" title="Marquer comme lu">Lu</button>
        <button class="btn btn-primary btn-small" onclick="changerStatut(${messageJson.id}, 'traite', '${SecuriteHelper.echapperAttribut(messageJson.nom)}'); closeModal();" title="Marquer comme traité">Traité</button>
        <button class="btn btn-danger btn-small" onclick="supprimerMessage(${messageJson.id}, '${SecuriteHelper.echapperAttribut(messageJson.nom)}'); closeModal();">
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
  if (!details) return;

  // Sécurité : utiliser SecuriteHelper pour échapper correctement
  details.innerHTML = `
    <div style="padding:12px 6px;">
      <h3 style="margin-bottom:6px;">${SecuriteHelper.echapperHtml(commentJson.auteur || "Anonyme")}</h3>
      <p style="margin:6px 0;"><strong>Vidéo:</strong> ${SecuriteHelper.echapperHtml(commentJson.video_titre || "—")}</p>
      <p style="margin:6px 0;"><strong>Email:</strong> ${SecuriteHelper.echapperHtml(commentJson.user_email || "—")}</p>
      <p style="margin:6px 0;"><strong>Date:</strong> ${SecuriteHelper.echapperHtml(commentJson.created_at || "—")}</p>
      <hr style="border-color: rgba(255,255,255,0.06);">
      <div class="comment-body" style="white-space:pre-wrap; line-height:1.6; color: var(--text-color-light);">
        ${SecuriteHelper.echapperHtml(commentJson.texte || "").replace(/\n/g, "<br>")}
      </div>
    </div>
  `;

  if (modal) {
    modal.classList.add("active");
  }
}

function closeModal() {
  const modal = document.getElementById("messageModal");
  if (modal) modal.classList.remove("active");
}

function filtrerMessages(statut) {
  const lignes = document.querySelectorAll("#contactsTable tbody tr");
  lignes.forEach((ligne) => {
    ligne.style.display =
      statut === "all" || ligne.dataset.statut === statut ? "" : "none";
  });
}

function rechercherMessages() {
  const valeur = document.getElementById("champRecherche").value.toLowerCase();
  document.querySelectorAll("#contactsTable tbody tr").forEach((ligne) => {
    ligne.style.display = ligne.textContent.toLowerCase().includes(valeur)
      ? ""
      : "none";
  });
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

console.log("📧 Contact ready");
