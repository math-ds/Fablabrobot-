/**
 * Helper de recherche amélioré avec debouncing
 *
 * Fournit une recherche côté client avec :
 * - Debouncing pour éviter trop de filtres
 * - Compteur de résultats
 * - Animation fluide
 *
 * @author Fablabrobot
 * @version 1.0.0
 */
const RechercheHelper = {
  /**
   * Initialise la recherche sur un tableau
   *
   * @param {string} inputId - ID de l'input de recherche
   * @param {string} tableauSelector - Sélecteur CSS du tableau (ex: "#tableauArticles tbody tr")
   * @param {number} delai - Délai de debouncing en ms (défaut: 300)
   */
  initialiser: function(inputId, tableauSelector, delai = 300) {
    const champRecherche = document.getElementById(inputId);
    if (!champRecherche) return;

    let timeoutId = null;

    champRecherche.addEventListener('input', function() {
      // Annuler le timeout précédent
      if (timeoutId) {
        clearTimeout(timeoutId);
      }

      // Attendre le délai avant de filtrer
      timeoutId = setTimeout(() => {
        RechercheHelper.filtrer(champRecherche.value, tableauSelector);
      }, delai);
    });
  },

  /**
   * Filtre les lignes d'un tableau
   *
   * @param {string} valeur - Valeur de recherche
   * @param {string} tableauSelector - Sélecteur CSS des lignes
   */
  filtrer: function(valeur, tableauSelector) {
    const recherche = valeur.toLowerCase().trim();
    const lignes = document.querySelectorAll(tableauSelector);
    let compteur = 0;

    lignes.forEach((ligne) => {
      const texte = ligne.textContent.toLowerCase();
      const correspond = texte.includes(recherche);

      // Animation de disparition/apparition
      if (correspond) {
        ligne.style.display = '';
        ligne.style.opacity = '0';
        setTimeout(() => {
          ligne.style.transition = 'opacity 0.2s';
          ligne.style.opacity = '1';
        }, 10);
        compteur++;
      } else {
        ligne.style.transition = 'opacity 0.2s';
        ligne.style.opacity = '0';
        setTimeout(() => {
          ligne.style.display = 'none';
        }, 200);
      }
    });

    // Afficher le compteur de résultats
    RechercheHelper.afficherCompteur(compteur, lignes.length, recherche);
  },

  /**
   * Affiche un compteur de résultats
   *
   * @param {number} trouve - Nombre de résultats trouvés
   * @param {number} total - Nombre total d'éléments
   * @param {string} recherche - Terme recherché
   */
  afficherCompteur: function(trouve, total, recherche) {
    // Chercher ou créer le compteur
    let compteur = document.getElementById('compteur-recherche');

    if (!compteur) {
      compteur = document.createElement('div');
      compteur.id = 'compteur-recherche';
      compteur.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: rgba(0, 175, 167, 0.95);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        z-index: 9998;
        transition: all 0.3s;
      `;
      document.body.appendChild(compteur);
    }

    if (recherche === '') {
      // Masquer le compteur si pas de recherche
      compteur.style.opacity = '0';
      compteur.style.transform = 'translateY(20px)';
      setTimeout(() => {
        if (compteur.parentNode) {
          compteur.remove();
        }
      }, 300);
    } else {
      // Afficher le compteur
      compteur.style.opacity = '1';
      compteur.style.transform = 'translateY(0)';

      if (trouve === 0) {
        compteur.style.background = 'rgba(255, 107, 107, 0.95)';
        compteur.innerHTML = `<i class="fas fa-search"></i> Aucun résultat pour "${recherche}"`;
      } else {
        compteur.style.background = 'rgba(0, 175, 167, 0.95)';
        compteur.innerHTML = `<i class="fas fa-check-circle"></i> ${trouve} résultat${trouve > 1 ? 's' : ''} sur ${total}`;
      }
    }
  }
};

console.log('🔍 RechercheHelper chargé');
