<?php

require_once __DIR__ . '/../modèles/AdminArticlesModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';
require_once __DIR__ . '/../helpers/Pagination.php';

class AdminArticlesControleur
{
    private const IMAGE_ERREUR = '__IMAGE_ERREUR__';

    private AdminArticlesModele $modele;
    private $cache;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $this->modele = new AdminArticlesModele();
        $this->cache = GestionnaireCache::obtenirInstance();
        CsrfHelper::init();
    }

    private function normaliserCategorieArticle(string $categorie): ?string
    {
        $value = trim($categorie);
        if ($value === '') {
            return null;
        }

        $key = function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
        $key = trim((string)(preg_replace('/\s+/', ' ', $key) ?? $key));

        $map = [
            'robotique' => 'Robotique',
            'electronique' => 'Electronique',
            'électronique' => 'Electronique',
            'programmation' => 'Programmation',
            'impression 3d' => 'Impression 3D',
            'mecanique' => 'Mecanique',
            'mécanique' => 'Mecanique',
            'conception' => 'Conception',
            'intelligence artificielle' => 'Intelligence Artificielle',
            'autre' => 'Autre',
        ];

        return $map[$key] ?? null;
    }

    private function estUrlExterne(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    private function construireNomFichierArticle(string $nomOriginal, string $extension): string
    {
        $base = pathinfo($nomOriginal, PATHINFO_FILENAME);
        $base = strtolower(trim((string)$base));
        $base = preg_replace('/[^a-z0-9_-]+/', '-', $base) ?? '';
        $base = trim($base, '-_');
        if ($base === '') {
            $base = 'image';
        }

        try {
            $suffixe = substr(bin2hex(random_bytes(4)), 0, 8);
        } catch (Throwable $e) {
            $suffixe = substr(md5((string)microtime(true)), 0, 8);
        }

        return 'article_' . $base . '_' . date('Ymd_His') . '_' . $suffixe . '.' . $extension;
    }

    private function cheminImageLocaleArticle(?string $imageUrl): ?string
    {
        $value = trim((string)$imageUrl);
        if ($value === '' || $this->estUrlExterne($value)) {
            return null;
        }

        if (str_starts_with($value, 'images/articles/')) {
            $value = substr($value, strlen('images/articles/'));
        }

        $value = ltrim($value, '/\\');
        if ($value === '') {
            return null;
        }

        return __DIR__ . '/../../public/images/articles/' . $value;
    }

    private function supprimerImageLocaleArticle(?string $imageUrl): void
    {
        $chemin = $this->cheminImageLocaleArticle($imageUrl);
        if ($chemin !== null && is_file($chemin)) {
            @unlink($chemin);
        }
    }

    private function gererImage(?array $articleExistant = null): ?string
    {
        $image_url = trim((string)($_POST['image_url'] ?? ''));
        $fileImage = $_FILES['image'] ?? null;
        $uploadDemande = is_array($fileImage)
            && (
                !empty($fileImage['name'])
                || (int)($fileImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
            );

        if ($uploadDemande) {
            $validation = ValidationHelper::validerFichierImage($fileImage, 5120);
            if (!$validation['valid']) {
                $_SESSION['message'] = $validation['error'];
                $_SESSION['message_type'] = 'danger';
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur((string)$validation['error'], 422);
                }
                return self::IMAGE_ERREUR;
            }

            $uploadDir = __DIR__ . '/../../public/images/articles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = (string)($validation['extension'] ?? 'jpg');
            $filename = $this->construireNomFichierArticle((string)($fileImage['name'] ?? ''), $extension);
            $target = $uploadDir . $filename;

            if (!move_uploaded_file((string)$fileImage['tmp_name'], $target)) {
                $_SESSION['message'] = "Erreur lors de l'enregistrement de l'image.";
                $_SESSION['message_type'] = 'danger';
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur("Erreur lors de l'enregistrement de l'image.", 500);
                }
                return self::IMAGE_ERREUR;
            }

            if ($articleExistant !== null) {
                $ancienneImage = (string)($articleExistant['image_url'] ?? '');
                if ($ancienneImage !== '') {
                    $this->supprimerImageLocaleArticle($ancienneImage);
                }
            }

            return 'images/articles/' . $filename;
        }

        if ($image_url !== '') {
            $validation = ValidationHelper::validerUrl($image_url, false);
            if (!$validation['valid']) {
                $_SESSION['message'] = $validation['error'];
                $_SESSION['message_type'] = 'danger';
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur((string)$validation['error'], 422);
                }
                return self::IMAGE_ERREUR;
            }

            if ($articleExistant !== null) {
                $ancienneImage = (string)($articleExistant['image_url'] ?? '');
                if ($ancienneImage !== '' && !$this->estUrlExterne($ancienneImage) && $ancienneImage !== $validation['value']) {
                    $this->supprimerImageLocaleArticle($ancienneImage);
                }
            }

            return $validation['value'];
        }

        return null;
    }

    public function gererRequete(?string $action = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            if (!CsrfHelper::verifierJetonPost() && !CsrfHelper::verifierJetonEntete()) {
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreurAvecDonnees("Token de securite invalide", 403, [
                        'new_token' => CsrfHelper::genererJeton()
                    ]);
                }
                $_SESSION['message'] = "Token de securite invalide.";
                $_SESSION['message_type'] = "danger";
                header("Location: ?page=admin-articles");
                exit;
            }

            $formAction = $_POST['action'] ?? null;

            try {
                if ($formAction === 'create') {
                    
                    $validations = [
                        'titre' => ValidationHelper::validerChaine($_POST['titre'] ?? '', 5, 200, 'Titre'),
                        'contenu' => ValidationHelper::validerChaine($_POST['contenu'] ?? '', 10, 10000, 'Contenu')
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

                    $categorie = $this->normaliserCategorieArticle((string)($_POST['categorie'] ?? ''));
                    $auteur_id = isset($_SESSION['utilisateur_id']) ? (int)$_SESSION['utilisateur_id'] : null;
                    $image_url = $this->gererImage();
                    if ($image_url === self::IMAGE_ERREUR) {
                        header("Location: ?page=admin-articles");
                        exit;
                    }

                    $id = $this->modele->creer([
                        'titre'     => $validations['titre']['value'],
                        'contenu'   => $validations['contenu']['value'],
                        'auteur_id' => $auteur_id,
                        'categorie' => $categorie,
                        'image_url' => $image_url
                    ]);

                    
                    $this->cache->supprimerParPrefixe('liste_articles_');
                    $this->cache->supprimer('liste_articles');

                    if (JsonResponseHelper::estAjax()) {
                        $article = $this->modele->trouver($id);
                        JsonResponseHelper::succes(['article' => $article], 'Article cree avec succes');
                    }

                    $_SESSION['message'] = "Article cree avec succes.";
                    $_SESSION['message_type'] = 'success';

                } elseif ($formAction === 'update') {
                    $id = (int)($_POST['article_id'] ?? 0);

                    
                    $validations = [
                        'titre' => ValidationHelper::validerChaine($_POST['titre'] ?? '', 5, 200, 'Titre'),
                        'contenu' => ValidationHelper::validerChaine($_POST['contenu'] ?? '', 10, 10000, 'Contenu')
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

                    $article = $this->modele->trouver($id);
                    $image_url = $this->gererImage($article);
                    if ($image_url === self::IMAGE_ERREUR) {
                        header("Location: ?page=admin-articles");
                        exit;
                    }
                    if ($image_url === null) {
                        $image_url = $article['image_url'] ?? null;
                    }

                    $categorie = $this->normaliserCategorieArticle((string)($_POST['categorie'] ?? ''));

                    $this->modele->mettreAJour($id, [
                        'titre'     => $validations['titre']['value'],
                        'contenu'   => $validations['contenu']['value'],
                        'categorie' => $categorie,
                        'image_url' => $image_url
                    ]);

                    
                    $this->cache->supprimer('article_' . $id);
                    $this->cache->supprimerParPrefixe('liste_articles_');
                    $this->cache->supprimer('liste_articles');

                    if (JsonResponseHelper::estAjax()) {
                        $article = $this->modele->trouver($id);
                        JsonResponseHelper::succes(['article' => $article], 'Article mis a jour avec succes');
                    }

                    $_SESSION['message'] = "Article mis a jour.";
                    $_SESSION['message_type'] = 'success';

                } elseif ($formAction === 'delete') {
                    $id = (int)($_POST['article_id'] ?? 0);
                    $this->modele->supprimer($id);

                    
                    $this->cache->supprimer('article_' . $id);
                    $this->cache->supprimerParPrefixe('liste_articles_');
                    $this->cache->supprimer('liste_articles');

                    if (JsonResponseHelper::estAjax()) {
                        JsonResponseHelper::succes(['id' => $id], 'Article supprime avec succes');
                    }

                    $_SESSION['message'] = "Article supprime.";
                    $_SESSION['message_type'] = 'success';
                }
            } catch (Throwable $e) {
                error_log('AdminArticlesControleur::gererRequete - ' . $e->getMessage());
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreurServeur('Une erreur est survenue lors du traitement de la demande.');
                }
                $_SESSION['message'] = "Une erreur est survenue lors du traitement de la demande.";
                $_SESSION['message_type'] = 'danger';
            }

            header('Location: ?page=admin-articles');
            exit;
        }

        $this->index();
    }

    public function index(): void
    {
        $articlesTous = $this->modele->tousLesElements() ?? [];
        $recherche = trim((string)($_GET['q'] ?? ''));
        $rechercheNormalisee = mb_strtolower($recherche, 'UTF-8');

        $articlesFiltres = array_values(array_filter(
            $articlesTous,
            function (array $article) use ($rechercheNormalisee): bool {
                if ($rechercheNormalisee === '') {
                    return true;
                }

                $index = mb_strtolower(implode(' ', [
                    (string)($article['titre'] ?? ''),
                    (string)($article['contenu'] ?? ''),
                    (string)($article['categorie'] ?? ''),
                    (string)($article['auteur_nom'] ?? ''),
                ]), 'UTF-8');

                return mb_strpos($index, $rechercheNormalisee, 0, 'UTF-8') !== false;
            }
        ));

        $total_articles = count($articlesTous);
        $totalFiltres = count($articlesFiltres);
        $pagination = new Pagination($totalFiltres, 10);
        $articles = array_slice($articlesFiltres, $pagination->offset(), $pagination->limit());
        include __DIR__ . '/../vues/admin/articles-admin.php';
    }
}
