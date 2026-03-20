<?php

require_once __DIR__ . '/../modèles/ArticleModele.php';
require_once __DIR__ . '/../modèles/FavorisModele.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';
require_once __DIR__ . '/../helpers/Pagination.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../../config/categories.php';

class ArticlesControleur
{
    private ArticleModele $modele;
    private $cache;

    public function __construct()
    {
        $this->modele = new ArticleModele();
        $this->cache = GestionnaireCache::obtenirInstance();
    }
    private function utilisateurPeutCreer(): bool
    {
        return RoleHelper::peutCreerContenu($_SESSION['utilisateur_role'] ?? '');
    }

    private function normaliserCategorieArticle(string $categorie): ?string
    {
        return CategorieConfig::normaliserArticle($categorie);
    }

    
    private function categoriesArticleDisponibles(): array
    {
        return CategorieConfig::articlesDisponibles();
    }

    private function marquerArticleFavori(array $article): array
    {
        $utilisateurId = (int)($_SESSION['utilisateur_id'] ?? 0);
        if ($utilisateurId <= 0 || empty($article['id'])) {
            $article['is_favori'] = false;
            return $article;
        }

        $modeleFavoris = new FavorisModele();
        $article['is_favori'] = $modeleFavoris->estFavori($utilisateurId, 'article', (int)$article['id']);
        return $article;
    }

    private function marquerArticlesFavoris(array $articles): array
    {
        $utilisateurId = (int)($_SESSION['utilisateur_id'] ?? 0);
        if ($utilisateurId <= 0 || empty($articles)) {
            foreach ($articles as &$article) {
                $article['is_favori'] = false;
            }
            unset($article);
            return $articles;
        }

        $modeleFavoris = new FavorisModele();
        $idsFavoris = $modeleFavoris->obtenirIdsFavorisUtilisateurEtType($utilisateurId, 'article');
        $lookup = array_fill_keys($idsFavoris, true);

        foreach ($articles as &$article) {
            $articleId = (int)($article['id'] ?? 0);
            $article['is_favori'] = $articleId > 0 && isset($lookup[$articleId]);
        }
        unset($article);

        return $articles;
    }

    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $categorie = trim((string)($_GET['categorie'] ?? ''));
        if ($categorie !== '' && $categorie !== 'all') {
            $categorie = $this->normaliserCategorieArticle($categorie) ?? $categorie;
        }

        $cacheSignature = md5($q . '|' . $categorie);
        $total = (int)$this->cache->memoriser('liste_articles_total_' . $cacheSignature, function() use ($q, $categorie) {
            return $this->modele->compterArticles($q, $categorie);
        }, 900);
        $pagination = new Pagination($total, 9);
        $pageKey = 'liste_articles_page_' . $cacheSignature
            . '_p' . $pagination->pageCourante()
            . '_l' . $pagination->limit();
        $articles = $this->cache->memoriser($pageKey, function() use ($q, $categorie, $pagination) {
            return $this->modele->obtenirArticlesPagines(
                $pagination->limit(),
                $pagination->offset(),
                $q,
                $categorie
            );
        }, 900);
        $articles = $this->marquerArticlesFavoris($articles);

        $categories = $this->categoriesArticleDisponibles();
        $utilisateurConnecte = !empty($_SESSION['utilisateur_id']);
        $peutCreerArticle = $this->utilisateurPeutCreer();

        if ($this->estRequeteAjax()) {
            $this->repondreIndexAjax($articles, $categories, $q, $categorie, $pagination);
            return;
        }

        require __DIR__ . '/../vues/articles/articles.php';
    }

    public function detail($id): void
    {
        $article = $this->cache->memoriser('article_' . $id, function() use ($id) {
            return $this->modele->obtenirArticleParId($id);
        }, 3600);
        if ($article) {
            $article = $this->marquerArticleFavori($article);
        }

        if ($article) {
            require __DIR__ . '/../vues/articles/article_detail.php';
            return;
        }

        echo '<h2>Article introuvable.</h2>';
    }

    public function enregistrer(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=articles');
            exit;
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if (!$this->utilisateurPeutCreer()) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas les droits pour créer un article."]);
                exit;
            }
            $_SESSION['message'] = "Vous n'avez pas les droits pour créer un article.";
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=articles');
            exit;
        }

        require_once __DIR__ . '/../helpers/CsrfHelper.php';

        $csrfValid = CsrfHelper::verifierJetonPost() || CsrfHelper::verifierJetonEntete();
        if (!$csrfValid) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
                exit;
            }
            $_SESSION['message'] = 'Token de sécurité invalide.';
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=articles');
            exit;
        }

        $titre = trim((string)($_POST['titre'] ?? ''));
        $contenu = trim((string)($_POST['contenu'] ?? ''));
        $auteur_id = isset($_SESSION['utilisateur_id']) ? (int)$_SESSION['utilisateur_id'] : null;
        $categorie = $this->normaliserCategorieArticle((string)($_POST['categorie'] ?? ''));
        $image_url = trim((string)($_POST['image_url'] ?? ''));

        if ($titre === '' || strlen($titre) < 5 || strlen($titre) > 200) {
            $errorMsg = 'Le titre doit contenir entre 5 et 200 caractères';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $_SESSION['message'] = $errorMsg;
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=articles');
            exit;
        }

        if ($contenu === '' || strlen($contenu) < 10 || strlen($contenu) > 10000) {
            $errorMsg = 'Le contenu doit contenir entre 10 et 10 000 caractères';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $_SESSION['message'] = $errorMsg;
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=articles');
            exit;
        }

        if ($image_url === '') {
            $image_url = null;
        }

        $fileImage = $_FILES['image'] ?? null;
        $uploadDemande = is_array($fileImage)
            && (
                !empty($fileImage['name'])
                || (int)($fileImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
            );

        if ($uploadDemande) {
            $validation = ValidationHelper::validerFichierImage($fileImage, 5120);
            if (!$validation['valid']) {
                $errorMsg = (string)$validation['error'];
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $errorMsg]);
                    exit;
                }
                $_SESSION['message'] = $errorMsg;
                $_SESSION['message_type'] = 'danger';
                header('Location: ?page=articles');
                exit;
            }

            $uploadDir = __DIR__ . '/../../public/images/articles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = (string)($validation['extension'] ?? 'jpg');
            $filename = uniqid('article_', true) . '.' . $extension;
            $target = $uploadDir . $filename;

            if (!move_uploaded_file((string)$fileImage['tmp_name'], $target)) {
                $errorMsg = "Erreur lors de l'enregistrement de l'image.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $errorMsg]);
                    exit;
                }
                $_SESSION['message'] = $errorMsg;
                $_SESSION['message_type'] = 'danger';
                header('Location: ?page=articles');
                exit;
            }

            $image_url = 'images/articles/' . $filename;
        }

        try {
            $articleId = $this->modele->creer([
                'titre' => $titre,
                'contenu' => $contenu,
                'auteur_id' => $auteur_id,
                'categorie' => $categorie,
                'image_url' => $image_url,
            ]);

            $this->cache->supprimerParPrefixe('liste_articles_');
            $this->cache->supprimer('liste_articles');

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Article publié avec succès !',
                    'article_id' => $articleId,
                ]);
                exit;
            }

            $_SESSION['message'] = 'Article publié avec succès !';
            $_SESSION['message_type'] = 'success';
        } catch (Exception $e) {
            error_log('ArticlesControleur::enregistrer - ' . $e->getMessage());
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la creation de l\'article.']);
                exit;
            }
            $_SESSION['message'] = 'Une erreur est survenue lors de la creation de l\'article.';
            $_SESSION['message_type'] = 'danger';
        }

        header('Location: ?page=articles');
        exit;
    }

    public function creation(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!$this->utilisateurPeutCreer()) {
            $_SESSION['message'] = "Vous n'avez pas les droits pour créer un article.";
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=articles');
            exit;
        }

        $categories = $this->categoriesArticleDisponibles();
        require __DIR__ . '/../vues/articles/article_creation.php';
    }

    private function estRequeteAjax(): bool
    {
        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            return true;
        }

        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function extraireCategories(array $articles): array
    {
        $categories = [];
        foreach ($articles as $article) {
            $categorieBrute = trim((string)($article['categorie'] ?? ''));
            $categorie = $this->normaliserCategorieArticle($categorieBrute) ?? $categorieBrute;
            if ($categorie !== '') {
                $categories[$categorie] = true;
            }
        }

        $resultat = array_keys($categories);
        sort($resultat, SORT_NATURAL | SORT_FLAG_CASE);
        return $resultat;
    }

    private function repondreIndexAjax(array $articles, array $categories, string $q, string $categorie, ?Pagination $pagination = null): void
    {
        $payloadArticles = array_map(static function(array $article): array {
            return [
                'id' => (int)($article['id'] ?? 0),
                'titre' => (string)($article['titre'] ?? ''),
                'contenu' => (string)($article['contenu'] ?? ''),
                'auteur_nom' => (string)($article['auteur_nom'] ?? ''),
                'categorie' => (string)($article['categorie'] ?? ''),
                'image_url' => (string)($article['image_url'] ?? ''),
                'created_at' => (string)($article['created_at'] ?? ''),
                'is_favori' => (bool)($article['is_favori'] ?? false),
            ];
        }, $articles);

        $paginationData = null;
        $paginationHtml = '';
        if ($pagination !== null) {
            $paginationData = [
                'page_courante' => $pagination->pageCourante(),
                'total_pages' => $pagination->totalPages(),
                'total' => $pagination->total(),
                'par_page' => $pagination->limit(),
            ];
            $paginationHtml = $pagination->rendrePaginationComplete();
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'data' => [
                'articles' => $payloadArticles,
                'categories' => $categories,
                'q' => $q,
                'categorie' => $categorie,
                'count' => count($payloadArticles),
                'can_favorite' => (int)($_SESSION['utilisateur_id'] ?? 0) > 0,
                'pagination' => $paginationData,
                'pagination_html' => $paginationHtml,
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

