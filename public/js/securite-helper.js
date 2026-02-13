/**
 * Helper de Sécurité JavaScript
 *
 * Fournit des fonctions pour échapper et sécuriser les données
 * avant insertion dans le DOM pour prévenir les attaques XSS
 *
 * @author Fablabrobot
 * @version 1.0.0
 */
const SecuriteHelper = {
  /**
   * Échappe les caractères HTML pour prévenir les attaques XSS
   * Utilise la méthode textContent pour échapper automatiquement
   *
   * @param {*} texte - Le texte à échapper
   * @returns {string} Le texte échappé
   *
   * @example
   * SecuriteHelper.echapperHtml('<script>alert("XSS")</script>')
   * // Retourne: '&lt;script&gt;alert("XSS")&lt;/script&gt;'
   */
  echapperHtml: function(texte) {
    if (texte === null || texte === undefined) {
      return '';
    }

    const div = document.createElement('div');
    div.textContent = String(texte);
    return div.innerHTML;
  },

  /**
   * Échappe un attribut HTML pour prévenir les injections
   * Remplace tous les caractères dangereux par leurs entités HTML
   *
   * @param {*} valeur - La valeur de l'attribut à échapper
   * @returns {string} La valeur échappée
   *
   * @example
   * SecuriteHelper.echapperAttribut('"><script>alert("XSS")</script>')
   * // Retourne: '&quot;&gt;&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;'
   */
  echapperAttribut: function(valeur) {
    if (valeur === null || valeur === undefined) {
      return '';
    }

    return String(valeur)
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#x27;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
  },

  /**
   * Crée un élément DOM de manière sécurisée
   * Évite l'utilisation de innerHTML qui peut être dangereuse
   *
   * @param {string} balise - Le nom de la balise (ex: 'div', 'p', 'span')
   * @param {string} [contenuTexte=''] - Le contenu textuel (sera échappé automatiquement)
   * @param {string[]} [classes=[]] - Tableau de classes CSS à ajouter
   * @returns {HTMLElement} L'élément créé
   *
   * @example
   * const div = SecuriteHelper.creerElementSecurise('div', 'Bonjour <script>', ['card', 'mb-3'])
   * // Crée: <div class="card mb-3">Bonjour &lt;script&gt;</div>
   */
  creerElementSecurise: function(balise, contenuTexte = '', classes = []) {
    const element = document.createElement(balise);

    if (contenuTexte) {
      element.textContent = contenuTexte;
    }

    if (classes.length > 0) {
      element.classList.add(...classes);
    }

    return element;
  },

  /**
   * Définit un attribut de manière sécurisée
   *
   * @param {HTMLElement} element - L'élément HTML
   * @param {string} nomAttribut - Le nom de l'attribut
   * @param {*} valeur - La valeur de l'attribut
   *
   * @example
   * const input = document.createElement('input')
   * SecuriteHelper.definirAttributSecurise(input, 'value', userInput)
   */
  definirAttributSecurise: function(element, nomAttribut, valeur) {
    if (element && nomAttribut) {
      element.setAttribute(nomAttribut, this.echapperAttribut(valeur));
    }
  },

  /**
   * Nettoie une chaîne pour l'utiliser dans une regex
   * Échappe les caractères spéciaux des expressions régulières
   *
   * @param {string} chaine - La chaîne à nettoyer
   * @returns {string} La chaîne nettoyée
   */
  echapperRegex: function(chaine) {
    return String(chaine).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  },

  /**
   * Valide si une chaîne contient uniquement des caractères alphanumériques
   *
   * @param {string} chaine - La chaîne à valider
   * @returns {boolean} true si valide, false sinon
   */
  estAlphaNumerique: function(chaine) {
    return /^[a-zA-Z0-9]+$/.test(String(chaine));
  },

  /**
   * Tronque une chaîne à une longueur maximale
   *
   * @param {string} texte - Le texte à tronquer
   * @param {number} longueurMax - La longueur maximale
   * @param {string} [suffixe='...'] - Le suffixe à ajouter si tronqué
   * @returns {string} Le texte tronqué
   */
  tronquer: function(texte, longueurMax, suffixe = '...') {
    const texteStr = String(texte || '');

    if (texteStr.length <= longueurMax) {
      return texteStr;
    }

    return texteStr.substring(0, longueurMax - suffixe.length) + suffixe;
  }
};

console.log('🔒 SecuriteHelper chargé');
