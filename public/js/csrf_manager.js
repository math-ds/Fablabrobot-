/**
 * GESTION DES TOKENS CSRF
 */

const CsrfManager = {
  getToken: function () {
    const field = document.querySelector('input[name="csrf_token"]');
    return field ? field.value : "";
  },
  updateAllTokenFields: function () {
    // Les tokens sont déjà générés côté PHP, rien à faire
  },
  addTokenToForm: function (form) {
    // Les tokens sont déjà dans le formulaire, rien à faire
  },
  refreshToken: function () {
    // Forcer le rechargement de la page pour obtenir un nouveau token
    window.location.reload();
  },
};
console.log("✓ CSRF ready");
