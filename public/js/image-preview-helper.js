/**
 * Helper d'aperçu d'image réutilisable
 *
 * Module centralisé pour gérer tous les aperçus d'images dans l'application
 * avec support du proxy CORS et gestion cohérente de l'UI
 *
 * @author Fablabrobot
 * @version 1.0.0
 */

const ImagePreviewHelper = {

  timeouts: {}, // Stockage des timeouts pour chaque instance

  /**
   * Initialise un aperçu d'image pour un champ input
   *
   * @param {Object} config Configuration de l'aperçu
   * @param {string} config.inputId - ID du champ input URL
   * @param {string} config.containerId - ID du conteneur de l'aperçu
   * @param {string} config.imgId - ID de l'élément img
   * @param {string} config.spinnerId - ID du spinner de chargement (optionnel)
   * @param {string} config.errorId - ID du conteneur d'erreur (optionnel)
   * @param {boolean} config.useProxy - Utiliser le proxy en cas d'erreur (défaut: true)
   * @param {number} config.maxHeight - Hauteur max de l'image (défaut: 200px)
   */
  init(config) {
    const input = document.getElementById(config.inputId);
    if (!input) return;

    // Écouter les changements sur le champ
    input.addEventListener('input', (e) => {
      this.preview(e.target.value, config);
    });
  },

  /**
   * Affiche l'aperçu d'une image depuis une URL
   *
   * @param {string} url - URL de l'image
   * @param {Object} config - Configuration de l'aperçu
   */
  preview(url, config) {
    const container = document.getElementById(config.containerId);
    const img = document.getElementById(config.imgId);
    const spinner = config.spinnerId ? document.getElementById(config.spinnerId) : null;
    const error = config.errorId ? document.getElementById(config.errorId) : null;

    if (!container || !img) return;

    // Clear previous timeout
    if (this.timeouts[config.imgId]) {
      clearTimeout(this.timeouts[config.imgId]);
    }

    // Si l'URL est vide, masquer l'aperçu
    if (!url || url.trim() === '') {
      container.style.display = 'none';
      if (error) error.style.display = 'none';
      img.style.display = 'none';
      img.src = '';
      return;
    }

    // Afficher le conteneur et le spinner
    container.style.display = 'block';
    if (error) error.style.display = 'none';
    if (spinner) spinner.style.display = 'block';
    img.style.display = 'block';
    img.style.opacity = '0.3';

    // Timeout de sécurité (5 secondes)
    this.timeouts[config.imgId] = setTimeout(() => {
      if (spinner) spinner.style.display = 'none';
      img.style.opacity = '1';
    }, 5000);

    // Créer une nouvelle image pour tester le chargement
    const testImg = new Image();

    testImg.onload = () => {
      clearTimeout(this.timeouts[config.imgId]);
      img.src = url;
      img.style.display = 'block';
      img.style.opacity = '1';
      if (spinner) spinner.style.display = 'none';
    };

    testImg.onerror = () => {

      // Essayer avec le proxy si activé
      if (config.useProxy !== false) {
        this.tryWithProxy(url, config, testImg);
      } else {
        this.showError(config);
      }
    };

    testImg.src = url;
  },

  /**
   * Essaie de charger l'image via le proxy
   */
  tryWithProxy(originalUrl, config, testImg) {
    const proxyUrl = `proxy-image.php?url=${encodeURIComponent(originalUrl)}`;
    const img = document.getElementById(config.imgId);
    const spinner = config.spinnerId ? document.getElementById(config.spinnerId) : null;

    const proxyImg = new Image();

    proxyImg.onload = () => {
      clearTimeout(this.timeouts[config.imgId]);
      img.src = proxyUrl;
      img.style.display = 'block';
      img.style.opacity = '1';
      if (spinner) spinner.style.display = 'none';
    };

    proxyImg.onerror = () => {
      this.showError(config);
    };

    proxyImg.src = proxyUrl;
  },

  /**
   * Affiche le message d'erreur
   */
  showError(config) {
    clearTimeout(this.timeouts[config.imgId]);
    const spinner = config.spinnerId ? document.getElementById(config.spinnerId) : null;
    const error = config.errorId ? document.getElementById(config.errorId) : null;
    const img = document.getElementById(config.imgId);

    if (spinner) spinner.style.display = 'none';
    img.style.display = 'none';
    if (error) error.style.display = 'block';
  },

  /**
   * Affiche l'aperçu d'un fichier local
   *
   * @param {HTMLInputElement} input - Input file
   * @param {string} previewImgId - ID de l'élément img pour l'aperçu
   * @param {string} containerId - ID du conteneur de l'aperçu
   */
  previewLocal(input, previewImgId, containerId) {
    if (!input.files || !input.files[0]) return;

    const container = document.getElementById(containerId);
    const img = document.getElementById(previewImgId);
    if (!img) return;

    const reader = new FileReader();

    reader.onload = (e) => {
      img.src = e.target.result;
      img.style.display = 'block';
      if (container) container.style.display = 'block';
    };

    reader.readAsDataURL(input.files[0]);
  },

  /**
   * Réinitialise un aperçu
   *
   * @param {Object} config - Configuration de l'aperçu
   */
  reset(config) {
    const container = document.getElementById(config.containerId);
    const img = document.getElementById(config.imgId);
    const error = config.errorId ? document.getElementById(config.errorId) : null;

    if (this.timeouts[config.imgId]) {
      clearTimeout(this.timeouts[config.imgId]);
    }

    if (container) container.style.display = 'none';
    if (img) img.src = '';
    if (error) error.style.display = 'none';
  }
};

// Rendre le module disponible globalement
window.ImagePreviewHelper = ImagePreviewHelper;
