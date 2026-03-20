<?php


class AdminCacheControleur
{
    private $cache;

    public function __construct()
    {
        require_once __DIR__ . '/../helpers/GestionnaireCache.php';
        require_once __DIR__ . '/../helpers/CacheFichier.php';
        require_once __DIR__ . '/../helpers/CacheMemoire.php';

        $this->cache = GestionnaireCache::obtenirInstance();
    }

    
    public function index()
    {
        
        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        CsrfHelper::init();

        $stats = $this->cache->obtenirStatistiques();
        $actif = $this->cache->estActif();

        
        $config = require __DIR__ . '/../../config/cache.php';

        require_once __DIR__ . '/../vues/admin/cache-admin.php';
    }

    
    public function gererAction()
    {
        
        require_once __DIR__ . '/../helpers/CsrfHelper.php';

        $tokenPost = (string)($_POST['csrf_token'] ?? '');
        $tokenHeader = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $csrfValide = CsrfHelper::validerJeton($tokenPost) || CsrfHelper::validerJeton($tokenHeader);

        if (!$csrfValide) {
            require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
            JsonResponseHelper::erreurAvecDonnees('Token CSRF invalide', 403, [
                'new_token' => CsrfHelper::genererJeton()
            ]);
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

    
    private function viderCache()
    {
        require_once __DIR__ . '/../helpers/JsonResponseHelper.php';

        $succes = $this->cache->vider();

        if ($succes) {
            JsonResponseHelper::succes(null, 'Cache vidé avec succès !');
        } else {
            JsonResponseHelper::erreur('Erreur lors du vidage du cache');
        }
    }

    
    private function nettoyerExpire()
    {
        require_once __DIR__ . '/../helpers/JsonResponseHelper.php';

        $succes = $this->cache->nettoyerExpire();

        if ($succes) {
            JsonResponseHelper::succes(null, 'Entrées expirées nettoyées !');
        } else {
            JsonResponseHelper::erreur('Erreur lors du nettoyage');
        }
    }

    
    private function basculerEtat()
    {
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

    
    private function obtenirStatistiques()
    {
        require_once __DIR__ . '/../helpers/JsonResponseHelper.php';

        $stats = $this->cache->obtenirStatistiques();

        JsonResponseHelper::succes($stats, 'Statistiques récupérées');
    }
}
