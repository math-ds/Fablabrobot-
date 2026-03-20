<?php

require_once __DIR__ . '/../modèles/ActualitesModele.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';
require_once __DIR__ . '/../helpers/Pagination.php';

class ActualitesControleur
{
    private $modele;
    private $cache;
    private $cacheConfig = [];

    public function __construct()
    {
        $db = (new Database())->getConnection();
        $this->modele = new ActualitesModele($db);
        $this->cache = GestionnaireCache::obtenirInstance();
        $this->cacheConfig = require __DIR__ . '/../../config/cache.php';
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

    
    public function index()
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $source = trim((string)($_GET['source'] ?? ''));
        $cacheSignature = md5($q . '|' . $source);
        $limit = 8;

        $total = (int)$this->cache->memoriser(
            'liste_actualites_total_' . $cacheSignature,
            function () use ($q, $source) {
                return $this->modele->compterActualitesFiltrees($q, $source);
            },
            $this->ttl('liste_actualites', 900)
        );

        $pagination = new Pagination($total, $limit);
        $pageKey = 'liste_actualites_page_' . $cacheSignature
            . '_p' . $pagination->pageCourante()
            . '_l' . $pagination->limit();
        $actualites = $this->cache->memoriser(
            $pageKey,
            function () use ($q, $source, $pagination) {
                return $this->modele->obtenirActualitesFiltrees($q, $source, $pagination->limit(), $pagination->offset());
            },
            $this->ttl('liste_actualites', 900)
        );

        $sources = $this->cache->memoriser(
            'actualites_sources',
            function () {
                return $this->obtenirSourcesFiltres();
            },
            $this->ttl('actualites_sources', 1800)
        );

        if ($this->estRequeteAjax()) {
            require __DIR__ . '/../vues/actualites/_liste-partial.php';
            return;
        }

        require __DIR__ . '/../vues/actualites/index.php';
    }

    
    public function detail(int $id)
    {
        $actualite = $this->cache->memoriser(
            'actualite_' . $id,
            function () use ($id) {
                return $this->modele->obtenirActualiteParId($id);
            },
            $this->ttl('actualite', 3600)
        );

        if (!$actualite) {
            $_SESSION['message'] = 'Actualite introuvable.';
            $_SESSION['message_type'] = 'error';
            header('Location: ?page=actualites');
            exit;
        }

        require __DIR__ . '/../vues/actualites/detail.php';
    }

    
    private function estRequeteAjax(): bool
    {
        if (isset($_GET['ajax']) && (string)$_GET['ajax'] === '1') {
            return true;
        }

        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function ttl(string $key, int $default): int
    {
        $dureesVie = $this->cacheConfig['durees_vie'] ?? [];
        $ttl = (int)($dureesVie[$key] ?? $default);
        return $ttl > 0 ? $ttl : $default;
    }
}
