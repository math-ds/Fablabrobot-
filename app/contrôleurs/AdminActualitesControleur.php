<?php

require_once __DIR__ . '/../modèles/ActualitesModele.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
require_once __DIR__ . '/../helpers/RssHelper.php';
require_once __DIR__ . '/../helpers/Pagination.php';

class AdminActualitesControleur
{
    private $modele;
    private $cache;

    public function __construct()
    {
        $db = (new Database())->getConnection();
        $this->modele = new ActualitesModele($db);
        $this->cache = GestionnaireCache::obtenirInstance();
    }

    private function obtenirSourcesFiltres(): array
    {
        $sourcesDb = $this->modele->obtenirSources();
        $rssConfig = require __DIR__ . '/../../config/rss_feeds.php';
        $sourcesConfig = array_keys((array)($rssConfig['feeds'] ?? []));

        $liste = array_merge($sourcesConfig, $sourcesDb);
        $uniques = [];
        $vus = [];

        foreach ($liste as $source) {
            $sourceTexte = trim((string)$source);
            if ($sourceTexte === '') {
                continue;
            }
            $cle = function_exists('mb_strtolower')
                ? mb_strtolower($sourceTexte, 'UTF-8')
                : strtolower($sourceTexte);
            if (isset($vus[$cle])) {
                continue;
            }
            $vus[$cle] = true;
            $uniques[] = $sourceTexte;
        }

        natcasesort($uniques);
        return array_values($uniques);
    }

    private function verifierCsrfAction(): bool
    {
        return CsrfHelper::verifierJetonPost() || CsrfHelper::verifierJetonEntete();
    }

    
    public function index()
    {
        
        $recherche = trim((string)($_GET['q'] ?? ''));
        $source = trim((string)($_GET['source'] ?? ''));

        $totalGlobal = $this->modele->compterActualitesFiltrees('', '');
        $total = $this->modele->compterActualitesFiltrees($recherche, $source);
        $pagination = new Pagination($total, 20);
        $actualites = $this->modele->obtenirActualitesFiltrees(
            $recherche,
            $source,
            $pagination->limit(),
            $pagination->offset()
        );
        $sources = $this->obtenirSourcesFiltres();

        require __DIR__ . '/../vues/admin/actualites-admin.php';
    }

    
    public function gererRequete(?string $action = null)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $action ?? ($_POST['action'] ?? null);

            switch ($action) {
                case 'synchroniser':
                    $this->synchroniserFluxRss();
                    break;

                case 'supprimer':
                    $this->supprimerActualite();
                    break;

                case 'nettoyer':
                    $this->nettoyerAnciennesActualites();
                    break;

                default:
                    $this->index();
            }
        } else {
            $this->index();
        }
    }

    
    private function synchroniserFluxRss()
    {
        
        if (!$this->verifierCsrfAction()) {
            JsonResponseHelper::erreurAvecDonnees('Jeton CSRF invalide', 403, [
                'new_token' => CsrfHelper::genererJeton()
            ]);
            return;
        }

        
        $config = require __DIR__ . '/../../config/rss_feeds.php';
        $feeds = $config['feeds'] ?? [];
        $limitParFlux = $config['limit_par_flux'] ?? 5;
        $requireImage = $config['require_image'] ?? true; 

        if (empty($feeds)) {
            JsonResponseHelper::erreur('Aucun flux RSS configuré', 400);
            return;
        }

        
        $articles = RssHelper::recupererMultiplesFlux($feeds, $limitParFlux, $requireImage);

        if (empty($articles)) {
            JsonResponseHelper::erreur('Aucun article récupéré depuis les flux RSS', 500);
            return;
        }

        
        $compteurNouvelles = 0;
        $compteurExistantes = 0;

        foreach ($articles as $article) {
            
            if ($this->modele->actualiteExiste($article['url_source'])) {
                $compteurExistantes++;
                continue;
            }

            if ($this->modele->creerActualite($article)) {
                $compteurNouvelles++;
            }
        }

        $message = sprintf(
            '%d nouvelle(s) actualité(s) ajoutée(s), %d déjà existante(s)',
            $compteurNouvelles,
            $compteurExistantes
        );

        if ($compteurNouvelles > 0) {
            $this->invaliderCachesActualites();
        }

        JsonResponseHelper::succes([
            'nouvelles' => $compteurNouvelles,
            'existantes' => $compteurExistantes,
            'total' => count($articles)
        ], $message);
    }

    
    private function supprimerActualite()
    {
        if (!$this->verifierCsrfAction()) {
            JsonResponseHelper::erreurAvecDonnees('Jeton CSRF invalide', 403, [
                'new_token' => CsrfHelper::genererJeton()
            ]);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            JsonResponseHelper::erreur('ID invalide', 400);
            return;
        }

        if ($this->modele->supprimerActualite($id)) {
            $this->invaliderCachesActualites($id);
            JsonResponseHelper::succes(null, 'Actualité supprimée avec succès');
        } else {
            JsonResponseHelper::erreur('Erreur lors de la suppression', 500);
        }
    }

    
    private function nettoyerAnciennesActualites()
    {
        if (!$this->verifierCsrfAction()) {
            JsonResponseHelper::erreurAvecDonnees('Jeton CSRF invalide', 403, [
                'new_token' => CsrfHelper::genererJeton()
            ]);
            return;
        }

        $jours = (int)($_POST['jours'] ?? 30);
        $nombreSupprimees = $this->modele->nettoyerAnciennesActualites($jours);

        if ($nombreSupprimees > 0) {
            $this->invaliderCachesActualites();
        }

        JsonResponseHelper::succes(['nombre' => $nombreSupprimees], "$nombreSupprimees actualité(s) nettoyée(s)");
    }

    private function invaliderCachesActualites(?int $id = null): void
    {
        $this->cache->supprimerParPrefixe('liste_actualites_');
        $this->cache->supprimer('actualites_sources');

        if ($id !== null && $id > 0) {
            $this->cache->supprimer('actualite_' . $id);
            return;
        }

        $this->cache->supprimerParPrefixe('actualite_');
    }
}
