<?php

require_once __DIR__ . '/../modèles/AdminArticlesModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';

class AdminArticlesControleur
{
    private AdminArticlesModele $modele;
    private $cache;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['utilisateur_role']) || strtolower($_SESSION['utilisateur_role']) !== 'admin') {
            header('Location: ?page=login');
            exit;
        }

        $this->modele = new AdminArticlesModele();
        $this->cache = GestionnaireCache::obtenirInstance();
        CsrfHelper::init();
    }

    public function gererRequete(?string $action = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérification CSRF (POST traditionnel ou AJAX)
            if (!CsrfHelper::verifierJetonPost() && !CsrfHelper::verifierJetonEntete()) {
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur("Token de sécurité invalide", 403);
                }
                $_SESSION['message'] = "Token de sécurité invalide.";
                $_SESSION['message_type'] = "danger";
                header("Location: ?page=admin-articles");
                exit;
            }

            $formAction = $_POST['action'] ?? null;

            try {
                if ($formAction === 'create') {
                    // Validation des données
                    $validations = [
                        'titre' => ValidationHelper::validerChaine($_POST['titre'] ?? '', 5, 200, 'Titre'),
                        'contenu' => ValidationHelper::validerChaine($_POST['contenu'] ?? '', 10, 10000, 'Contenu'),
                        'auteur' => ValidationHelper::validerChaine($_POST['auteur'] ?? '', 2, 100, 'Auteur'),
                        'image_url' => ValidationHelper::validerUrl($_POST['image_url'] ?? '', false)
                    ];

                    foreach ($validations as $field => $result) {
                        if (!$result['valid']) {
                            if (JsonResponseHelper::estAjax()) {
                                JsonResponseHelper::erreurValidation([$field => $result['error']]);
                            }
                            $_SESSION['message'] = $result['error'];
                            $_SESSION['message_type'] = 'danger';
                            header("Location: ?page=admin-articles");
                            exit;
                        }
                    }

                    $id = $this->modele->creer([
                        'titre'     => $validations['titre']['value'],
                        'contenu'   => $validations['contenu']['value'],
                        'auteur'    => $validations['auteur']['value'],
                        'image_url' => $validations['image_url']['value']
                    ]);

                    // Invalider le cache des articles
                    $this->cache->supprimer('liste_articles');

                    if (JsonResponseHelper::estAjax()) {
                        $article = $this->modele->trouver($id);
                        JsonResponseHelper::succes(['article' => $article], 'Article créé avec succès');
                    }

                    $_SESSION['message'] = "Article créé avec succès.";
                    $_SESSION['message_type'] = 'success';

                } elseif ($formAction === 'update') {
                    $id = (int)($_POST['article_id'] ?? 0);

                    // Validation des données
                    $validations = [
                        'titre' => ValidationHelper::validerChaine($_POST['titre'] ?? '', 5, 200, 'Titre'),
                        'contenu' => ValidationHelper::validerChaine($_POST['contenu'] ?? '', 10, 10000, 'Contenu'),
                        'auteur' => ValidationHelper::validerChaine($_POST['auteur'] ?? '', 2, 100, 'Auteur'),
                        'image_url' => ValidationHelper::validerUrl($_POST['image_url'] ?? '', false)
                    ];

                    foreach ($validations as $field => $result) {
                        if (!$result['valid']) {
                            if (JsonResponseHelper::estAjax()) {
                                JsonResponseHelper::erreurValidation([$field => $result['error']]);
                            }
                            $_SESSION['message'] = $result['error'];
                            $_SESSION['message_type'] = 'danger';
                            header("Location: ?page=admin-articles");
                            exit;
                        }
                    }

                    $this->modele->mettreAJour($id, [
                        'titre'     => $validations['titre']['value'],
                        'contenu'   => $validations['contenu']['value'],
                        'auteur'    => $validations['auteur']['value'],
                        'image_url' => $validations['image_url']['value']
                    ]);

                    // Invalider le cache de cet article et de la liste
                    $this->cache->supprimer('article_' . $id);
                    $this->cache->supprimer('liste_articles');

                    if (JsonResponseHelper::estAjax()) {
                        $article = $this->modele->trouver($id);
                        JsonResponseHelper::succes(['article' => $article], 'Article mis à jour avec succès');
                    }

                    $_SESSION['message'] = "Article mis à jour.";
                    $_SESSION['message_type'] = 'success';

                } elseif ($formAction === 'delete') {
                    $id = (int)($_POST['article_id'] ?? 0);
                    $this->modele->supprimer($id);

                    // Invalider le cache de cet article et de la liste
                    $this->cache->supprimer('article_' . $id);
                    $this->cache->supprimer('liste_articles');

                    if (JsonResponseHelper::estAjax()) {
                        JsonResponseHelper::succes(['id' => $id], 'Article supprimé avec succès');
                    }

                    $_SESSION['message'] = "Article supprimé.";
                    $_SESSION['message_type'] = 'success';
                }
            } catch (Throwable $e) {
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur($e->getMessage(), 500);
                }
                $_SESSION['message'] = "Erreur: " . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
            }

            header('Location: ?page=admin-articles');
            exit;
        }

        $this->index();
    }

    public function index(): void
    {
        $articles = $this->modele->tousLesElements() ?? [];
        $total_articles = count($articles);
        include __DIR__ . '/../vues/admin/articles-admin.php';
    }
}