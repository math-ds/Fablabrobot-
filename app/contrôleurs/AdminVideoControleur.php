<?php

require_once __DIR__ . '/../modèles/AdminVideoModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';
require_once __DIR__ . '/../helpers/Pagination.php';
require_once __DIR__ . '/../../config/categories.php';

class AdminVideoControleur
{
    private AdminVideoModele $modele;
    private $cache;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $this->modele = new AdminVideoModele();
        $this->cache = GestionnaireCache::obtenirInstance();
        CsrfHelper::init();
    }

    public function gererRequete(?string $action = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $csrfValid = CsrfHelper::verifierJetonPost() || CsrfHelper::verifierJetonEntete();

            if (!$csrfValid) {
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreurAvecDonnees("Token de sécurité invalide", 403, [
                        'new_token' => CsrfHelper::genererJeton()
                    ]);
                }
                $_SESSION['message'] = "Token de sécurité invalide.";
                $_SESSION['message_type'] = "danger";
                header("Location: ?page=admin-webtv");
                exit;
            }

            $formAction = $_POST['action'] ?? null;

            try {
                if ($formAction === 'create') {
                    
                    $validations = [
                        'titre' => ValidationHelper::validerChaine($_POST['titre'] ?? '', 3, 200, 'Titre'),
                        'description' => ValidationHelper::validerChaine($_POST['description'] ?? '', 5, 1000, 'Description'),
                        'youtube_url' => ValidationHelper::validerUrlYoutube($_POST['youtube_url'] ?? '', true)
                    ];
                    $categorie = CategorieConfig::normaliserVideo((string)($_POST['categorie'] ?? ''));

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
                    if ($categorie === null) {
                        if (JsonResponseHelper::estAjax()) {
                            JsonResponseHelper::erreurValidation(['categorie' => 'Catégorie invalide.']);
                        }
                        $_SESSION['message'] = 'Catégorie invalide.';
                        $_SESSION['message_type'] = 'danger';
                        header("Location: ?page=admin-webtv");
                        exit;
                    }

                    
                    $youtubeId = $validations['youtube_url']['id'];
                    $vignetteUrl = $youtubeId ? "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg" : null;
                    $auteur_id = isset($_SESSION['utilisateur_id']) ? (int)$_SESSION['utilisateur_id'] : null;

                    $id = $this->modele->creer([
                        'titre'       => $validations['titre']['value'],
                        'description' => $validations['description']['value'],
                        'categorie'   => $categorie,
                        'type'        => 'youtube',
                        'fichier'     => '',
                        'youtube_url' => $validations['youtube_url']['value'],
                        'vignette'    => $vignetteUrl,
                        'auteur_id'   => $auteur_id
                    ]);

                    
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

                    
                    $validations = [
                        'titre' => ValidationHelper::validerChaine($_POST['titre'] ?? '', 3, 200, 'Titre'),
                        'description' => ValidationHelper::validerChaine($_POST['description'] ?? '', 5, 1000, 'Description'),
                        'youtube_url' => ValidationHelper::validerUrlYoutube($_POST['youtube_url'] ?? '', true)
                    ];
                    $categorie = CategorieConfig::normaliserVideo((string)($_POST['categorie'] ?? ''));

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
                    if ($categorie === null) {
                        if (JsonResponseHelper::estAjax()) {
                            JsonResponseHelper::erreurValidation(['categorie' => 'Catégorie invalide.']);
                        }
                        $_SESSION['message'] = 'Catégorie invalide.';
                        $_SESSION['message_type'] = 'danger';
                        header("Location: ?page=admin-webtv");
                        exit;
                    }

                    
                    $youtubeId = $validations['youtube_url']['id'];
                    $vignetteUrl = $youtubeId ? "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg" : null;
                    $this->modele->mettreAJour($id, [
                        'titre'       => $validations['titre']['value'],
                        'description' => $validations['description']['value'],
                        'categorie'   => $categorie,
                        'type'        => 'youtube',
                        'fichier'     => '',
                        'youtube_url' => $validations['youtube_url']['value'],
                        'vignette'    => $vignetteUrl
                    ]);

                    
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
                error_log('AdminVideoControleur::gererRequete - ' . $e->getMessage());
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreurServeur('Une erreur est survenue lors du traitement de la demande.');
                }
                $_SESSION['message'] = "Une erreur est survenue lors du traitement de la demande.";
                $_SESSION['message_type'] = 'danger';
            }

            header('Location: ?page=admin-webtv');
            exit;
        }

        $this->index();
    }

    public function index(): void
    {
        $videosTous = $this->modele->tousLesElements() ?? [];
        foreach ($videosTous as &$video) {
            $categorieNormalisee = CategorieConfig::normaliserVideo((string)($video['categorie'] ?? ''));
            if ($categorieNormalisee !== null) {
                $video['categorie'] = $categorieNormalisee;
            }
        }
        unset($video);

        $recherche = trim((string)($_GET['q'] ?? ''));
        $rechercheNormalisee = mb_strtolower($recherche, 'UTF-8');
        $videosFiltrees = array_values(array_filter(
            $videosTous,
            function (array $video) use ($rechercheNormalisee): bool {
                if ($rechercheNormalisee === '') {
                    return true;
                }

                $index = mb_strtolower(implode(' ', [
                    (string)($video['titre'] ?? ''),
                    (string)($video['description'] ?? ''),
                    (string)($video['categorie'] ?? ''),
                    (string)($video['auteur_nom'] ?? ''),
                ]), 'UTF-8');

                return mb_strpos($index, $rechercheNormalisee, 0, 'UTF-8') !== false;
            }
        ));

        $categoriesVideo = CategorieConfig::videosDisponibles();
        $totalVideos = count($videosTous);
        $totalFiltres = count($videosFiltrees);
        $pagination = new Pagination($totalFiltres, 10);
        $videos = array_slice($videosFiltrees, $pagination->offset(), $pagination->limit());
        include __DIR__ . '/../vues/admin/webtv-admin.php';
    }
}
