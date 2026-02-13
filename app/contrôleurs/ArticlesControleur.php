<?php


require_once __DIR__ . '/../modèles/ArticleModele.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';

class ArticlesControleur {

    private $modele;
    private $cache;

    public function __construct() {
        $this->modele = new ArticleModele();
        $this->cache = GestionnaireCache::obtenirInstance();
    }


    public function index() {
        // Cache : Liste des articles (15 minutes)
        $articles = $this->cache->memoriser('liste_articles', function() {
            return $this->modele->obtenirTousLesArticles();
        }, 900);

        require __DIR__ . '/../vues/articles/articles.php';
    }


    public function detail($id) {
        // Cache : Article individuel (1 heure)
        $article = $this->cache->memoriser('article_' . $id, function() use ($id) {
            return $this->modele->obtenirArticleParId($id);
        }, 3600);

        if ($article) {
            require __DIR__ . '/../vues/articles/article_detail.php';
        } else {
            echo "<h2>Article introuvable.</h2>";
        }
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

        // Détecter si c'est une requête AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';


        $role = $_SESSION['utilisateur_role'] ?? 'Visiteur';
        if (!in_array($role, ['Admin', 'Éditeur'], true)) {
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

        require_once __DIR__ . '/../modèles/AdminArticlesModele.php';
        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        require_once __DIR__ . '/../helpers/ValidationHelper.php';

        // Vérification CSRF
        if ($isAjax) {
            $csrfValid = CsrfHelper::verifierJetonPost() || CsrfHelper::verifierJetonEntete();
            if (!$csrfValid) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Token de sécurité invalide"]);
                exit;
            }
        }

        $modele = new AdminArticlesModele();

        $titre   = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $auteur  = trim($_POST['auteur'] ?? $_SESSION['utilisateur_nom'] ?? 'Inconnu');
        $image_url = trim($_POST['image_url'] ?? '');

        // Validation
        if (empty($titre) || strlen($titre) < 5 || strlen($titre) > 200) {
            $errorMsg = "Le titre doit contenir entre 5 et 200 caractères";
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

        if (empty($contenu) || strlen($contenu) < 10 || strlen($contenu) > 10000) {
            $errorMsg = "Le contenu doit contenir entre 10 et 10 000 caractères";
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


        if (empty($image_url)) {
            $image_url = null;
        }


        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../public/images/articles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = time() . '_' . basename($_FILES['image']['name']);
            $target = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_url = 'images/articles/' . $filename;
            }
        }


        try {
            $articleId = $modele->creer([
                'titre'     => $titre,
                'contenu'   => $contenu,
                'auteur'    => $auteur,
                'image_url' => $image_url
            ]);

            // Invalider le cache des articles
            $this->cache->supprimer('liste_articles');

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "Article publié avec succès !",
                    'article_id' => $articleId
                ]);
                exit;
            }

            $_SESSION['message'] = "✅ Article publié avec succès !";
            $_SESSION['message_type'] = 'success';
        } catch (Exception $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Erreur lors de la création : " . $e->getMessage()]);
                exit;
            }
            $_SESSION['message'] = "❌ Erreur lors de la création : " . $e->getMessage();
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

        $role = $_SESSION['utilisateur_role'] ?? 'Visiteur';
        if (!in_array($role, ['Admin', 'Éditeur'], true)) {
            $_SESSION['message'] = "❌ Vous n'avez pas les droits pour créer un article.";
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=articles');
            exit;
        }

        require __DIR__ . '/../vues/articles/article_creation.php';
    }
}