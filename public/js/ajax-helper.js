/**
 * Helper AJAX unifié avec gestion CSRF automatique
 *
 * Fournit des méthodes pour effectuer des requêtes AJAX de manière sécurisée
 * avec gestion automatique du token CSRF et des erreurs
 *
 * @author Fablabrobot
 * @version 1.0.0
 */
const AjaxHelper = {
  /**
   * Récupère le token CSRF depuis la page
   * Cherche d'abord dans une balise meta, puis dans un input hidden
   *
   * @returns {string} Le token CSRF
   */
  obtenirTokenCsrf: function () {
    // Chercher d'abord dans une balise meta (recommandé pour AJAX)
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
      return metaTag.content;
    }

    // Sinon chercher dans un input hidden
    const inputField = document.querySelector('input[name="csrf_token"]');
    if (inputField) {
      return inputField.value;
    }

    console.error("❌ Token CSRF introuvable");
    return "";
  },

  /**
   * Effectue une requête AJAX
   *
   * @param {string} url - L'URL de destination
   * @param {Object} options - Options de configuration
   * @param {string} options.method - Méthode HTTP (GET, POST, etc.)
   * @param {Object|FormData} options.data - Données à envoyer
   * @param {Object} options.headers - Headers HTTP supplémentaires
   * @returns {Promise<Object>} Promise qui résout avec les données JSON
   */
  requete: async function (url, options = {}) {
    const config = {
      method: options.method || "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-Token": this.obtenirTokenCsrf(),
        ...options.headers,
      },
    };

    // Préparation du body pour POST/PUT
    if (options.data) {
      if (options.data instanceof FormData) {
        // Si FormData, laisser le navigateur gérer le Content-Type
        config.body = options.data;
      } else {
        // Sinon, encoder en application/x-www-form-urlencoded
        config.headers["Content-Type"] = "application/x-www-form-urlencoded";
        config.body = new URLSearchParams(options.data).toString();
      }
    }

    try {
      const response = await fetch(url, config);
      const data = await response.json();

      if (!response.ok) {
        throw { response, data };
      }

      return data;
    } catch (error) {
      // Log l'erreur pour le débogage
      console.error("❌ Erreur AJAX:", error);

      // Gestion des erreurs réseau
      if (error instanceof TypeError && error.message === "Failed to fetch") {
        throw {
          data: {
            success: false,
            message: "Erreur de connexion. Vérifiez votre réseau.",
          },
        };
      }

      // Gestion des erreurs timeout
      if (error.message && error.message.includes("Timeout")) {
        throw {
          data: {
            success: false,
            message: "La requête a expiré. Réessayez.",
          },
        };
      }

      // Retourner l'erreur pour que le code appelant puisse la gérer
      throw error;
    }
  },

  /**
   * Effectue une requête GET
   *
   * @param {string} url - L'URL de destination
   * @returns {Promise<Object>} Promise qui résout avec les données JSON
   */
  get: function (url) {
    return this.requete(url, { method: "GET" });
  },

  /**
   * Effectue une requête POST
   *
   * @param {string} url - L'URL de destination
   * @param {Object|FormData} data - Données à envoyer
   * @returns {Promise<Object>} Promise qui résout avec les données JSON
   */
  post: function (url, data) {
    // Ajouter le token CSRF aux données POST pour compatibilité
    let dataWithToken = data;
    if (data && typeof data === "object" && !(data instanceof FormData)) {
      dataWithToken = { ...data, csrf_token: this.obtenirTokenCsrf() };
    } else if (data instanceof FormData) {
      data.append("csrf_token", this.obtenirTokenCsrf());
    }
    return this.requete(url, { method: "POST", data: dataWithToken });
  },

  /**
   * Effectue une requête PUT
   *
   * @param {string} url - L'URL de destination
   * @param {Object|FormData} data - Données à envoyer
   * @returns {Promise<Object>} Promise qui résout avec les données JSON
   */
  put: function (url, data) {
    return this.requete(url, { method: "PUT", data });
  },

  /**
   * Effectue une requête DELETE
   * Simule via POST avec _method=DELETE pour compatibilité serveur
   *
   * @param {string} url - L'URL de destination
   * @param {Object} data - Données supplémentaires (optionnel)
   * @returns {Promise<Object>} Promise qui résout avec les données JSON
   */
  delete: function (url, data = {}) {
    return this.requete(url, {
      method: "POST",
      data: { ...data, _method: "DELETE" },
    });
  },
};

console.log("🌐 AjaxHelper chargé");
