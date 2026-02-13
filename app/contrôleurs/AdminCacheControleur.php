<?php

/**
 * Contrôleur d'Administration du Cache
 * Permet de gérer le système de cache depuis l'interface admin
 */
class AdminCacheControleur {
    
    private $cache;
    
    public function __construct() {
        require_once __DIR__ . '/../helpers/GestionnaireCache.php';
        require_once __DIR__ . '/../helpers/CacheFichier.php';
        require_once __DIR__ . '/../helpers/CacheMemoire.php';
        
        $this->cache = GestionnaireCache::obtenirInstance();
    }
    
    /**
     * Page principale de gestion du cache
     */
    public function index() {
        // Initialiser le token CSRF
        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        CsrfHelper::init();

        $stats = $this->cache->obtenirStatistiques();
        $actif = $this->cache->estActif();

        // Charger la config
        $config = require __DIR__ . '/../../config/cache.php';

        require_once __DIR__ . '/../vues/admin/cache-admin.php';
    }
    
    /**
     * Gère les différentes actions sur le cache
     */
    public function gererAction() {
        // Vérifier le token CSRF
        require_once __DIR__ . '/../helpers/CsrfHelper.php';

        if (!isset($_POST['csrf_token']) || !CsrfHelper::validerJeton($_POST['csrf_token'])) {
            require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
            JsonResponseHelper::erreur('Token CSRF invalide', 403);
            return;
        }
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'vider':
                $this->viderCache();
                break;

            case 'nettoyer':
                $this->nettoyerExpire();
                break;

            case 'basculer':
                $this->basculerEtat();
                break;

            case 'statistiques':
                $this->obtenirStatistiques();
                break;

            default:
                require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
                JsonResponseHelper::erreur('Action inconnue', 400);
        }
    }
    
    /**
     * Vide complètement le cache
     */
    private function viderCache() {
        require_once __DIR__ . '/../helpers/JsonResponseHelper.php';

        $succes = $this->cache->vider();

        if ($succes) {
            JsonResponseHelper::succes(null, 'Cache vidé avec succès !');
        } else {
            JsonResponseHelper::erreur('Erreur lors du vidage du cache');
        }
    }
    
    /**
     * Nettoie les entrées expirées
     */
    private function nettoyerExpire() {
        require_once __DIR__ . '/../helpers/JsonResponseHelper.php';

        $succes = $this->cache->nettoyerExpire();

        if ($succes) {
            JsonResponseHelper::succes(null, 'Entrées expirées nettoyées !');
        } else {
            JsonResponseHelper::erreur('Erreur lors du nettoyage');
        }
    }
    
    /**
     * Active ou désactive le cache
     */
    private function basculerEtat() {
        require_once __DIR__ . '/../helpers/JsonResponseHelper.php';

        if ($this->cache->estActif()) {
            $this->cache->desactiver();
            $message = 'Cache désactivé';
            $etat = false;
        } else {
            $this->cache->activer();
            $message = 'Cache activé';
            $etat = true;
        }

        JsonResponseHelper::succes(['actif' => $etat], $message);
    }

    /**
     * Retourne les statistiques du cache
     */
    private function obtenirStatistiques() {
        require_once __DIR__ . '/../helpers/JsonResponseHelper.php';

        $stats = $this->cache->obtenirStatistiques();

        JsonResponseHelper::succes($stats, 'Statistiques récupérées');
    }
}