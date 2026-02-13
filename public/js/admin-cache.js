/**
 * Gestion du Cache - JavaScript
 * Gère les actions AJAX pour l'administration du cache
 */

// Log de démarrage
console.log('🗄️ Chargement de admin-cache.js...');

// Récupérer le token CSRF depuis la balise meta
function getCsrfToken() {
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (!metaToken) {
        console.error('❌ Token CSRF non trouvé dans les meta tags !');
        return '';
    }
    const token = metaToken.getAttribute('content');
    console.log('🔐 Token CSRF trouvé:', token ? 'OK' : 'VIDE');
    return token;
}

/**
 * Envoyer une requête AJAX au serveur
 * @param {string} action - L'action à exécuter
 * @param {object} donnees - Données supplémentaires à envoyer
 * @returns {Promise} - Promesse avec la réponse JSON
 */
async function envoyerAction(action, donnees = {}) {
    try {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('csrf_token', getCsrfToken());

        // Ajouter les données supplémentaires
        for (let [cle, valeur] of Object.entries(donnees)) {
            formData.append(cle, valeur);
        }

        const response = await fetch('?page=admin-cache', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        // Logger les réponses pour diagnostic
        console.log(`Action ${action}:`, result);

        return result;
    } catch (error) {
        console.error('Erreur:', error);
        // Afficher l'erreur à l'utilisateur
        ToastNotification.erreur('Erreur de connexion au serveur');
        return {
            success: false,
            message: 'Erreur de connexion au serveur'
        };
    }
}

/**
 * Rafraîchir les statistiques du cache
 */
async function rafraichirStats() {
    const result = await envoyerAction('statistiques');

    if (result.success) {
        ToastNotification.succes('Statistiques actualisées !');
        // Mettre à jour les statistiques affichées
        if (result.data) {
            mettreAJourAffichageStats(result.data);
        }
    } else {
        ToastNotification.erreur(result.message || 'Erreur lors du rafraîchissement');
    }
}

/**
 * Met à jour l'affichage des statistiques sans recharger la page
 */
function mettreAJourAffichageStats(stats) {
    // Mettre à jour les valeurs des cartes de statistiques
    const statsCards = document.querySelectorAll('.stat-card .value');
    if (statsCards[0]) statsCards[0].textContent = stats.total_entrees || 0;
    if (statsCards[1]) statsCards[1].textContent = stats.entrees_valides || 0;
    if (statsCards[2]) statsCards[2].textContent = stats.entrees_expirees || 0;
    if (statsCards[3]) statsCards[3].textContent = stats.taille_formatee || '0 Ko';
}

/**
 * Nettoyer les entrées expirées du cache
 */
async function nettoyerCache() {
    if (!confirm('Voulez-vous nettoyer les entrées expirées ?')) {
        return;
    }

    const result = await envoyerAction('nettoyer');

    if (result.success) {
        ToastNotification.succes(result.message || 'Cache nettoyé avec succès !');
        // Rafraîchir les stats après le nettoyage
        rafraichirStats();
    } else {
        ToastNotification.erreur(result.message || 'Erreur lors du nettoyage');
    }
}

/**
 * Vider complètement le cache
 */
async function viderCache() {
    if (!confirm('⚠️ ATTENTION : Cette action supprimera TOUTES les entrées du cache.\n\nContinuer ?')) {
        return;
    }

    const result = await envoyerAction('vider');

    if (result.success) {
        ToastNotification.succes(result.message || 'Cache vidé avec succès !');
        // Rafraîchir les stats après le vidage
        rafraichirStats();
    } else {
        ToastNotification.erreur(result.message || 'Erreur lors du vidage du cache');
    }
}

/**
 * Basculer l'état du cache (activer/désactiver)
 */
async function basculerCache() {
    const result = await envoyerAction('basculer');

    if (result.success) {
        ToastNotification.succes(result.message || 'État du cache modifié');

        // Mettre à jour l'affichage de l'état sans recharger
        const statusIndicator = document.getElementById('status-indicator');
        const cacheStatus = document.querySelector('.cache-status');
        const btnBasculer = document.getElementById('btn-basculer');

        if (result.data && result.data.actif !== undefined) {
            const estActif = result.data.actif;

            // Mettre à jour l'indicateur d'état
            if (statusIndicator) {
                statusIndicator.textContent = estActif ? '✓ Actif' : '✗ Inactif';
            }

            // Mettre à jour la classe CSS du statut
            if (cacheStatus) {
                cacheStatus.classList.remove('actif', 'inactif');
                cacheStatus.classList.add(estActif ? 'actif' : 'inactif');
            }

            // Mettre à jour le texte du bouton
            if (btnBasculer) {
                btnBasculer.textContent = estActif ? 'Désactiver' : 'Activer';
            }
        }
    } else {
        ToastNotification.erreur(result.message || 'Erreur lors du changement d\'état');
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Page de cache initialisée');
});

// Log de chargement pour diagnostic
console.log('✅ admin-cache.js chargé et initialisé');
