/**
 * Système de notifications toast modernes
 *
 * Affiche des notifications élégantes en haut à droite de l'écran
 * avec fermeture automatique et animations fluides
 *
 * @author Fablabrobot
 * @version 1.0.0
 */
const ToastNotification = {
  conteneur: null,

  /**
   * Initialise le conteneur de toasts
   * Crée un div qui contiendra toutes les notifications
   */
  initialiser: function() {
    if (this.conteneur) return;

    this.conteneur = document.createElement('div');
    this.conteneur.id = 'toast-conteneur';
    this.conteneur.className = 'toast-conteneur';
    document.body.appendChild(this.conteneur);
  },

  /**
   * Affiche un toast
   *
   * @param {string} message - Le message à afficher
   * @param {string} type - Le type de toast (info, success, danger, warning)
   * @param {number} duree - Durée d'affichage en millisecondes (par défaut 4000)
   */
  afficher: function(message, type = 'info', duree = 4000) {
    this.initialiser();

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const icone = this.obtenirIcone(type);
    // Échapper le HTML pour éviter les injections XSS
    const messageSecurise = String(message)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');

    toast.innerHTML = `
      <div class="toast-icone">${icone}</div>
      <div class="toast-message">${messageSecurise}</div>
      <button class="toast-fermer" aria-label="Fermer">&times;</button>
    `;

    // Animation d'entrée
    this.conteneur.appendChild(toast);
    setTimeout(() => toast.classList.add('toast-visible'), 10);

    // Fermeture automatique
    const fermerToast = () => {
      toast.classList.remove('toast-visible');
      setTimeout(() => {
        if (toast.parentNode) {
          toast.remove();
        }
      }, 300);
    };

    const timeoutId = setTimeout(fermerToast, duree);

    // Fermeture manuelle
    toast.querySelector('.toast-fermer').addEventListener('click', () => {
      clearTimeout(timeoutId);
      fermerToast();
    });

    // Pause sur hover
    toast.addEventListener('mouseenter', () => clearTimeout(timeoutId));
    toast.addEventListener('mouseleave', () => {
      setTimeout(fermerToast, 1000);
    });
  },

  /**
   * Affiche un toast de succès
   *
   * @param {string} message - Le message à afficher
   * @param {number} duree - Durée d'affichage (optionnel)
   */
  succes: function(message, duree) {
    this.afficher(message, 'success', duree);
  },

  /**
   * Affiche un toast d'erreur
   *
   * @param {string} message - Le message à afficher
   * @param {number} duree - Durée d'affichage (optionnel)
   */
  erreur: function(message, duree) {
    this.afficher(message, 'danger', duree);
  },

  /**
   * Affiche un toast d'avertissement
   *
   * @param {string} message - Le message à afficher
   * @param {number} duree - Durée d'affichage (optionnel)
   */
  avertissement: function(message, duree) {
    this.afficher(message, 'warning', duree);
  },

  /**
   * Affiche un toast d'information
   *
   * @param {string} message - Le message à afficher
   * @param {number} duree - Durée d'affichage (optionnel)
   */
  info: function(message, duree) {
    this.afficher(message, 'info', duree);
  },

  /**
   * Retourne l'icône FontAwesome correspondant au type
   *
   * @param {string} type - Le type de toast
   * @returns {string} Le HTML de l'icône
   */
  obtenirIcone: function(type) {
    const icones = {
      success: '<i class="fas fa-check-circle"></i>',
      danger: '<i class="fas fa-exclamation-circle"></i>',
      warning: '<i class="fas fa-exclamation-triangle"></i>',
      info: '<i class="fas fa-info-circle"></i>'
    };
    return icones[type] || icones.info;
  }
};

// Auto-initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
  ToastNotification.initialiser();
});

console.log('🍞 ToastNotification chargé');
