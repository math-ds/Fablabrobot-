<?php

require_once __DIR__ . '/../modèles/AdminVideoModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';

class AdminVideoControleur
{
    private AdminVideoModele $modele;
    private $cache;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['utilisateur_role']) || strtolower($_SESSION['utilisateur_role']) !== 'admin') {
            header('Location: ?page=login');
            exit;
        }

        $this->modele = new AdminVideoModele();
        $this->cache = GestionnaireCache::obtenirInstance();
        CsrfHelper::init();
    }

    public function gererRequete(?string $action = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérification CSRF (POST traditionnel ou AJAX)
            $csrfValid = CsrfHelper::verifierJetonPost() || CsrfHelper::verifierJetonEntete();

            if (!$csrfValid) {
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur("Token de sécurité invalide", 403);
                }
                $_SESSION['message'] = "Token de sécurité invalide.";
                $_SESSION['message_type'] = "danger";
                header("Location: ?page=admin-webtv");
                exit;
            }

            $formAction = $_POST['action'] ?? null;

            try {
                if ($formAction === 'create') {
                    // Validation des données
                    $validations = [
                        'titre' => ValidationHelper::validerChaine($_POST['titre'] ?? '', 3, 200, 'Titre'),
                        'description' => ValidationHelper::validerChaine($_POST['description'] ?? '', 5, 1000, 'Description'),
                        'categorie' => ValidationHelper::validerChaine($_POST['categorie'] ?? '', 2, 100, 'Catégorie'),
                        'youtube_url' => ValidationHelper::validerUrlYoutube($_POST['youtube_url'] ?? '', true)
                    ];

                    foreach ($validations as $field => $result) {
                        if (!$result['valid']) {
                            if (JsonResponseHelper::estAjax()) {
                                JsonResponseHelper::erreurValidation([$field => $result['error']]);
                            }
                            $_SESSION['message'] = $result['error'];
                            $_SESSION['message_type'] = 'danger';
                            header("Location: ?page=admin-webtv");
                            exit;
                        }
                    }

                    // Générer automatiquement l'URL de la vignette YouTube
                    $youtubeId = $validations['youtube_url']['id'];
                    $vignetteUrl = $youtubeId ? "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg" : null;

                    $id = $this->modele->creer([
                        'titre'       => $validations['titre']['value'],
                        'description' => $validations['description']['value'],
                        'categorie'   => $validations['categorie']['value'],
                        'type'        => 'youtube',
                        'fichier'     => '',
                        'youtube_url' => $validations['youtube_url']['value'],
                        'vignette'    => $vignetteUrl
                    ]);

                    // Invalider le cache des vidéos
                    $this->cache->supprimerParPrefixe('liste_videos_');
                    $this->cache->supprimer('video_categories');

                    if (JsonResponseHelper::estAjax()) {
                        $video = $this->modele->trouver($id);
                        JsonResponseHelper::succes(['video' => $video], 'Vidéo ajoutée avec succès');
                    }

                    $_SESSION['message'] = "Vidéo ajoutée.";
                    $_SESSION['message_type'] = 'success';

                } elseif ($formAction === 'update') {
                    $id = (int)($_POST['id'] ?? 0);

                    // Validation des données
                    $validations = [
                        'titre' => ValidationHelper::validerChaine($_POST['titre'] ?? '', 3, 200, 'Titre'),
                        'description' => ValidationHelper::validerChaine($_POST['description'] ?? '', 5, 1000, 'Description'),
                        'categorie' => ValidationHelper::validerChaine($_POST['categorie'] ?? '', 2, 100, 'Catégorie'),
                        'youtube_url' => ValidationHelper::validerUrlYoutube($_POST['youtube_url'] ?? '', true)
                    ];

                    foreach ($validations as $field => $result) {
                        if (!$result['valid']) {
                            if (JsonResponseHelper::estAjax()) {
                                JsonResponseHelper::erreurValidation([$field => $result['error']]);
                            }
                            $_SESSION['message'] = $result['error'];
                            $_SESSION['message_type'] = 'danger';
                            header("Location: ?page=admin-webtv");
                            exit;
                        }
                    }

                    // Générer automatiquement l'URL de la vignette YouTube
                    $youtubeId = $validations['youtube_url']['id'];
                    $vignetteUrl = $youtubeId ? "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg" : null;

                    $this->modele->mettreAJour($id, [
                        'titre'       => $validations['titre']['value'],
                        'description' => $validations['description']['value'],
                        'categorie'   => $validations['categorie']['value'],
                        'type'        => 'youtube',
                        'fichier'     => '',
                        'youtube_url' => $validations['youtube_url']['value'],
                        'vignette'    => $vignetteUrl
                    ]);

                    // Invalider le cache de cette vidéo et des listes
                    $this->cache->supprimerParPrefixe('liste_videos_');
                    $this->cache->supprimer('video_categories');
                    $this->cache->supprimer('video_comments_' . $id);

                    if (JsonResponseHelper::estAjax()) {
                        $video = $this->modele->trouver($id);
                        JsonResponseHelper::succes(['video' => $video], 'Vidéo mise à jour avec succès');
                    }

                    $_SESSION['message'] = "Vidéo mise à jour.";
                    $_SESSION['message_type'] = 'success';

                } elseif ($formAction === 'delete') {
                    $id = (int)($_POST['id'] ?? 0);
                    $this->modele->supprimer($id);

                    // Invalider le cache de cette vidéo et des listes
                    $this->cache->supprimerParPrefixe('liste_videos_');
                    $this->cache->supprimer('video_categories');
                    $this->cache->supprimer('video_comments_' . $id);

                    if (JsonResponseHelper::estAjax()) {
                        JsonResponseHelper::succes(['id' => $id], 'Vidéo supprimée avec succès');
                    }

                    $_SESSION['message'] = "Vidéo supprimée.";
                    $_SESSION['message_type'] = 'success';
                }
            } catch (Throwable $e) {
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur($e->getMessage(), 500);
                }
                $_SESSION['message'] = "Erreur: " . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
            }

            header('Location: ?page=admin-webtv');
            exit;
        }

        $this->index();
    }

    public function index(): void
    {
        $videos = $this->modele->tousLesElements() ?? [];
        $totalVideos = count($videos);
        include __DIR__ . '/../vues/admin/webtv-admin.php';
    }
}