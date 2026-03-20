<?php
require_once __DIR__ . '/../modèles/VideoModele.php';
require_once __DIR__ . '/../modèles/CommentairesVideoModele.php';
require_once __DIR__ . '/../modèles/FavorisModele.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/Pagination.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/AvatarHelper.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';
require_once __DIR__ . '/../../config/categories.php';

class VideoControleur
{
    private VideoModele $videoModele;
    private CommentairesVideoModele $commentaireModele;
    private $cache;

    public function __construct()
    {
        $this->videoModele = new VideoModele();
        $this->commentaireModele = new CommentairesVideoModele();
        $this->cache = GestionnaireCache::obtenirInstance();
    }

    private function marquerVideoFavori(?array $video): ?array
    {
        if (!$video) {
            return $video;
        }

        $utilisateurId = (int)($_SESSION['utilisateur_id'] ?? 0);
        if ($utilisateurId <= 0 || empty($video['id'])) {
            $video['is_favori'] = false;
            return $video;
        }

        $modeleFavoris = new FavorisModele();
        $video['is_favori'] = $modeleFavoris->estFavori($utilisateurId, 'video', (int)$video['id']);
        return $video;
    }

    private function marquerVideosFavoris(array $videos): array
    {
        $utilisateurId = (int)($_SESSION['utilisateur_id'] ?? 0);
        if ($utilisateurId <= 0 || empty($videos)) {
            foreach ($videos as &$video) {
                $video['is_favori'] = false;
            }
            unset($video);
            return $videos;
        }

        $modeleFavoris = new FavorisModele();
        $idsFavoris = $modeleFavoris->obtenirIdsFavorisUtilisateurEtType($utilisateurId, 'video');
        $lookup = array_fill_keys($idsFavoris, true);

        foreach ($videos as &$video) {
            $videoId = (int)($video['id'] ?? 0);
            $video['is_favori'] = $videoId > 0 && isset($lookup[$videoId]);
        }
        unset($video);

        return $videos;
    }

    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $cat = CategorieConfig::normaliserVideo((string)($_GET['categorie'] ?? '')) ?? '';
        $categories = $this->cache->memoriser('video_categories', function() {
            return CategorieConfig::videosDisponibles();
        }, 600);

        $current = $this->selectionnerVideoDemandee();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $current = $this->resoudreVideoDepuisPost($current);

            if ($action === 'add_comment') {
                $this->gererSoumissionCommentaire($current);
                return;
            }

            if ($action === 'reply_comment') {
                $parentIdRaw = $_POST['parent_id'] ?? null;
                $parentId = ctype_digit((string)$parentIdRaw) ? (int)$parentIdRaw : 0;
                $this->gererSoumissionCommentaire($current, $parentId);
                return;
            }
        }

        $mode = $current ? 'detail' : 'catalogue';

        if ($mode === 'catalogue') {
            
            
            if ($cat !== '') {
                $videosSource = $this->videoModele->tousLesVideos($q ?: null, null);
                $videosFiltrees = array_values(array_filter($videosSource, static function (array $video) use ($cat): bool {
                    $categorieVideo = CategorieConfig::normaliserVideo((string)($video['categorie'] ?? ''));
                    return $categorieVideo === $cat;
                }));
                $total = count($videosFiltrees);
                $pagination = new Pagination($total, 8);
                $videos = array_slice($videosFiltrees, $pagination->offset(), $pagination->limit());
            } else {
                $total = $this->videoModele->compterVideos($q ?: null, null);
                $pagination = new Pagination($total, 8);
                $videos = $this->videoModele->tousLesVideosPagines(
                    $pagination->limit(),
                    $pagination->offset(),
                    $q ?: null,
                    null
                );
            }
            foreach ($videos as &$video) {
                $categorieNormalisee = CategorieConfig::normaliserVideo((string)($video['categorie'] ?? ''));
                if ($categorieNormalisee !== null) {
                    $video['categorie'] = $categorieNormalisee;
                }
            }
            unset($video);
            $videos = $this->marquerVideosFavoris($videos);

            if ($this->estRequeteAjax()) {
                $this->repondreCatalogueAjax($videos, $categories, $q, $cat, $pagination);
                return;
            }

            $commentaires = [];
            $suggestions = [];
            $categorieCourante = $cat;
            $utilisateurConnecte = !empty($_SESSION['utilisateur_id']);
            $peutCreerVideo = $this->utilisateurPeutCreer();
            $auteurCreationVideo = trim((string)($_SESSION['utilisateur_nom'] ?? ''));
            if ($auteurCreationVideo === '') {
                $auteurCreationVideo = 'Utilisateur connecte';
            }
            if ($this->requeteVideoPresente()) {
                $_SESSION['message'] = "Video introuvable.";
                $_SESSION['message_type'] = "warning";
            }

            require __DIR__ . '/../vues/webtv/catalogue-video.php';
            return;
        }

        
        $current = $this->marquerVideoFavori($current);
        $this->videoModele->incrementerVues((int)$current['id']);

        $commentaires = $this->cache->memoriser('video_comments_' . $current['id'], function() use ($current) {
            return $this->commentaireModele->listerPourVideo((int)$current['id']);
        }, 600);

        $suggestionPool = $this->cache->memoriser('liste_videos_' . md5(''), function() {
            return $this->videoModele->tousLesVideos();
        }, 600);
        $suggestions = $this->obtenirSuggestions($suggestionPool, (int)$current['id'], 10);
        $suggestions = $this->marquerVideosFavoris($suggestions);

        $pagination = null;
        $videos = [];
        require __DIR__ . '/../vues/webtv/detail.php';
    }

    public function enregistrer(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Methode non autorisee';
            exit;
        }

        $estAjax = $this->estRequeteAjax();

        if (!$this->utilisateurPeutCreer()) {
            if ($estAjax) {
                $this->repondreErreurAjax('Acces refuse.', 403);
            }
            $_SESSION['message'] = 'Acces refuse.';
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=webtv');
            exit;
        }

        if (!CsrfHelper::validerJeton($_POST['csrf_token'] ?? '')) {
            if ($estAjax) {
                $this->repondreErreurAjax('Token de sécurité invalide. Veuillez réessayer.', 419);
            }
            $_SESSION['message'] = 'Token de sécurité invalide. Veuillez réessayer.';
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=webtv');
            exit;
        }

        $validationTitre = ValidationHelper::validerChaine((string)($_POST['titre'] ?? ''), 3, 200, 'Titre');
        $validationDescription = ValidationHelper::validerChaine((string)($_POST['description'] ?? ''), 5, 1500, 'Description');
        $validationYoutube = ValidationHelper::validerUrlYoutube((string)($_POST['youtube_url'] ?? ''), true);
        $categorie = CategorieConfig::normaliserVideo((string)($_POST['categorie'] ?? ''));

        foreach ([$validationTitre, $validationDescription, $validationYoutube] as $validation) {
            if (empty($validation['valid'])) {
                $message = (string)($validation['error'] ?? 'Donnees invalides.');
                if ($estAjax) {
                    $this->repondreErreurAjax($message, 422);
                }
                $_SESSION['message'] = $message;
                $_SESSION['message_type'] = 'danger';
                header('Location: ?page=webtv');
                exit;
            }
        }
        if ($categorie === null) {
            $message = 'Catégorie invalide. Veuillez choisir une catégorie de la liste.';
            if ($estAjax) {
                $this->repondreErreurAjax($message, 422);
            }
            $_SESSION['message'] = $message;
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=webtv');
            exit;
        }

        $youtubeId = (string)($validationYoutube['id'] ?? '');
        $vignetteUrl = $youtubeId !== '' ? "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg" : null;
        $auteur_id = isset($_SESSION['utilisateur_id']) ? (int)$_SESSION['utilisateur_id'] : null;

        try {
            $id = $this->videoModele->creer([
                'titre' => $validationTitre['value'],
                'description' => $validationDescription['value'],
                'categorie' => $categorie,
                'type' => 'youtube',
                'fichier' => '',
                'youtube_url' => $validationYoutube['value'],
                'vignette' => $vignetteUrl,
                'auteur_id' => $auteur_id,
            ]);

            $this->cache->supprimerParPrefixe('liste_videos_');
            $this->cache->supprimer('video_categories');

            $message = 'Video ajoutee avec succes.';
            if ($estAjax) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'data' => ['id' => $id],
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $_SESSION['message'] = $message;
            $_SESSION['message_type'] = 'success';
            header('Location: ?page=webtv');
            exit;
        } catch (Throwable $e) {
            if ($estAjax) {
                $this->repondreErreurAjax('Erreur lors de la creation de la video.', 500);
            }

            $_SESSION['message'] = 'Erreur lors de la creation de la video.';
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=webtv');
            exit;
        }
    }

    private function estRequeteAjax(): bool
    {
        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            return true;
        }

        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function repondreCatalogueAjax(array $videos, array $categories, string $q, string $cat, ?Pagination $pagination = null): void
    {
        $payloadVideos = array_map(static function (array $video): array {
            return [
                'id' => (int)($video['id'] ?? 0),
                'titre' => (string)($video['titre'] ?? ''),
                'description' => (string)($video['description'] ?? ''),
                'categorie' => (string)($video['categorie'] ?? ''),
                'type' => (string)($video['type'] ?? ''),
                'fichier' => (string)($video['fichier'] ?? ''),
                'youtube_url' => (string)($video['youtube_url'] ?? ''),
                'vignette' => (string)($video['vignette'] ?? ''),
                'vues' => (int)($video['vues'] ?? 0),
                'auteur_nom' => (string)($video['auteur_nom'] ?? ''),
                'created_at' => (string)($video['created_at'] ?? ''),
                'is_favori' => (bool)($video['is_favori'] ?? false),
            ];
        }, $videos);

        $paginationData = null;
        $paginationHtml = '';
        if ($pagination !== null) {
            $paginationData = [
                'page_courante' => $pagination->pageCourante(),
                'total_pages'   => $pagination->totalPages(),
                'total'         => $pagination->total(),
                'par_page'      => $pagination->limit(),
            ];
            $paginationHtml = $pagination->rendrePaginationComplete();
        }
        $totalCount = $paginationData !== null
            ? (int)($paginationData['total'] ?? count($payloadVideos))
            : count($payloadVideos);

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'data' => [
                'videos' => $payloadVideos,
                'categories' => array_values($categories),
                'q' => $q,
                'categorie' => $cat,
                'count' => count($payloadVideos),
                'total_count' => $totalCount,
                'vues_totales' => array_sum(array_map(static fn(array $video): int => (int)($video['vues'] ?? 0), $payloadVideos)),
                'can_favorite' => (int)($_SESSION['utilisateur_id'] ?? 0) > 0,
                'pagination' => $paginationData,
                'pagination_html' => $paginationHtml,
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function utilisateurPeutCreer(): bool
    {
        return RoleHelper::peutCreerContenu($_SESSION['utilisateur_role'] ?? '');
    }

    private function repondreErreurAjax(string $message, int $codeHttp = 400): void
    {
        http_response_code($codeHttp);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function repondreSuccesAjax(string $message, array $data = []): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function formaterDateCommentaire(string $rawDate): string
    {
        $timestamp = strtotime($rawDate);
        if ($timestamp === false) {
            return date('d/m/Y H:i');
        }

        return date('d/m/Y H:i', $timestamp);
    }

    private function longueurTexte(string $texte): int
    {
        if (function_exists('mb_strlen')) {
            return (int)mb_strlen($texte, 'UTF-8');
        }

        return strlen($texte);
    }

    private function selectionnerVideoDemandee(): ?array
    {
        if (isset($_GET['video']) && ctype_digit((string)$_GET['video'])) {
            $video = $this->videoModele->trouverParId((int)$_GET['video']);
            if ($video) {
                return $video;
            }
        }

        if (isset($_GET['video_url']) && trim((string)$_GET['video_url']) !== '') {
            $video = $this->videoModele->trouverParUrlYoutube(trim((string)$_GET['video_url']));
            if ($video) {
                return $video;
            }
        }

        return null;
    }

    private function gererSoumissionCommentaire(?array $current, ?int $parentId = null): void
    {
        $estAjax = $this->estRequeteAjax();

        if (!$current || empty($current['id'])) {
            if ($estAjax) {
                $this->repondreErreurAjax('Video introuvable.', 404);
            }
            $_SESSION['message'] = "Video introuvable.";
            $_SESSION['message_type'] = "danger";
            $this->rediriger($current, $this->extraFiltresRedirection());
            return;
        }

        if (!CsrfHelper::validerJeton($_POST['csrf_token'] ?? '')) {
            if ($estAjax) {
                $this->repondreErreurAjax('Token de sécurité invalide. Veuillez réessayer.', 419);
            }
            $_SESSION['message'] = "Token de sécurité invalide. Veuillez réessayer.";
            $_SESSION['message_type'] = "danger";
            $this->rediriger($current, $this->extraFiltresRedirection());
            return;
        }

        if (empty($_SESSION['utilisateur_id'])) {
            if ($estAjax) {
                $this->repondreErreurAjax('Vous devez etre connecte pour commenter.', 401);
            }
            $_SESSION['message'] = "Vous devez etre connecte pour commenter.";
            $_SESSION['message_type'] = "danger";
            $this->rediriger($current, $this->extraFiltresRedirection());
            return;
        }

        $texte = trim((string)($_POST['commentaire'] ?? ''));

        if ($texte === '') {
            if ($estAjax) {
                $this->repondreErreurAjax('Le commentaire ne peut pas etre vide.', 422);
            }
            $_SESSION['message'] = "Le commentaire ne peut pas etre vide.";
            $_SESSION['message_type'] = "warning";
            $this->rediriger($current, $this->extraFiltresRedirection());
            return;
        }

        if ($this->longueurTexte($texte) > 1500) {
            if ($estAjax) {
                $this->repondreErreurAjax('Le commentaire ne doit pas depasser 1500 caracteres.', 422);
            }
            $_SESSION['message'] = "Le commentaire ne doit pas depasser 1500 caracteres.";
            $_SESSION['message_type'] = "warning";
            $this->rediriger($current, $this->extraFiltresRedirection());
            return;
        }

        $parentAuteurReponse = null;

        if ($parentId !== null) {
            if ($parentId <= 0) {
                if ($estAjax) {
                    $this->repondreErreurAjax('Commentaire parent invalide.', 422);
                }
                $_SESSION['message'] = "Commentaire parent invalide.";
                $_SESSION['message_type'] = "warning";
                $this->rediriger($current, $this->extraFiltresRedirection());
                return;
            }

            $parent = $this->commentaireModele->trouverPourVideo($parentId, (int)$current['id']);
            if (!$parent) {
                if ($estAjax) {
                    $this->repondreErreurAjax('Commentaire parent introuvable.', 404);
                }
                $_SESSION['message'] = "Commentaire parent introuvable.";
                $_SESSION['message_type'] = "warning";
                $this->rediriger($current, $this->extraFiltresRedirection());
                return;
            }

            $parentAuteurReponse = (string)($parent['auteur'] ?? '');

            if (!empty($parent['parent_id'])) {
                if ($estAjax) {
                    $this->repondreErreurAjax('Vous ne pouvez pas répondre à une réponse.', 422);
                }
                $_SESSION['message'] = "Vous ne pouvez pas répondre à une réponse.";
                $_SESSION['message_type'] = "warning";
                $this->rediriger($current, $this->extraFiltresRedirection());
                return;
            }
        }

        $parentIdEffectif = ($parentId !== null && $parentId > 0) ? $parentId : null;

        $ok = $this->commentaireModele->creer(
            (int)$current['id'],
            (int)$_SESSION['utilisateur_id'],
            $texte,
            $parentIdEffectif
        );

        if ($ok) {
            $this->cache->supprimer('video_comments_' . $current['id']);
            if ($estAjax) {
                $nouvelId = $this->commentaireModele->dernierIdInsere();
                $dernier = $this->commentaireModele->trouverPourVideo($nouvelId, (int)$current['id']) ?? [];
                $dateCreation = (string)($dernier['created_at'] ?? date('Y-m-d H:i:s'));
                $baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
                $avatar = AvatarHelper::construireDonnees(
                    (string)($dernier['auteur'] ?? ($_SESSION['utilisateur_nom'] ?? 'Utilisateur')),
                    (string)($dernier['user_photo'] ?? ($_SESSION['utilisateur_photo'] ?? '')),
                    (string)$baseUrl
                );
                $commentaire = [
                    'id' => (int)($dernier['id'] ?? 0),
                    'auteur' => (string)($dernier['auteur'] ?? ($_SESSION['utilisateur_nom'] ?? 'Utilisateur')),
                    'texte' => (string)($dernier['texte'] ?? $texte),
                    'parent_id' => isset($dernier['parent_id']) && $dernier['parent_id'] !== null ? (int)$dernier['parent_id'] : null,
                    'parent_auteur' => $parentAuteurReponse,
                    'created_at' => $dateCreation,
                    'date_affichee' => $this->formaterDateCommentaire($dateCreation),
                    'avatar' => $avatar,
                ];
                $messageSucces = $parentIdEffectif === null ? 'Commentaire publie.' : 'Reponse publiee.';

                $this->repondreSuccesAjax($messageSucces, [
                    'comment' => $commentaire,
                    'total_comments' => $this->commentaireModele->compterPourVideo((int)$current['id']),
                ]);
            }
            $_SESSION['message'] = $parentIdEffectif === null ? "Commentaire publie." : "Reponse publiee.";
            $_SESSION['message_type'] = "success";
        } else {
            if ($estAjax) {
                $this->repondreErreurAjax('Erreur lors de la publication.', 500);
            }
            $_SESSION['message'] = "Erreur lors de la publication.";
            $_SESSION['message_type'] = "danger";
        }

        $this->rediriger($current, $this->extraFiltresRedirection());
    }

    private function gererSuppressionCommentaire($commentId, ?array $current): void
    {
        $estAjax = $this->estRequeteAjax();

        if (!CsrfHelper::validerJeton($_POST['csrf_token'] ?? '')) {
            if ($estAjax) {
                $this->repondreErreurAjax('Token de sécurité invalide. Veuillez réessayer.', 419);
            }
            $_SESSION['message'] = "Token de sécurité invalide. Veuillez réessayer.";
            $_SESSION['message_type'] = "danger";
            $this->rediriger($current, $this->extraFiltresRedirection());
            return;
        }

        if (!ctype_digit((string)$commentId)) {
            if ($estAjax) {
                $this->repondreErreurAjax('Commentaire invalide.', 422);
            }
            $this->rediriger($current, $this->extraFiltresRedirection());
            return;
        }

        $ok = $this->commentaireModele->supprimer((int)$commentId);
        if (!$ok) {
            if ($estAjax) {
                $this->repondreErreurAjax('Erreur lors de la suppression du commentaire.', 500);
            }
            $_SESSION['message'] = "Erreur lors de la suppression du commentaire.";
            $_SESSION['message_type'] = "danger";
            $this->rediriger($current, $this->extraFiltresRedirection());
            return;
        }

        $totalCommentaires = null;
        if ($current && !empty($current['id'])) {
            $this->cache->supprimer('video_comments_' . $current['id']);
            if ($estAjax) {
                $totalCommentaires = count($this->commentaireModele->listerPourVideo((int)$current['id']));
            }
        }

        if ($estAjax) {
            $payload = ['comment_id' => (int)$commentId];
            if ($totalCommentaires !== null) {
                $payload['total_comments'] = $totalCommentaires;
            }
            $this->repondreSuccesAjax('Commentaire supprime.', $payload);
        }

        $_SESSION['message'] = "Commentaire supprime.";
        $_SESSION['message_type'] = "success";
        $this->rediriger($current, $this->extraFiltresRedirection());
    }

    private function resoudreVideoDepuisPost(?array $current): ?array
    {
        $videoId = $_POST['video_id'] ?? null;
        if ($videoId !== null && ctype_digit((string)$videoId) && (int)$videoId > 0) {
            $video = $this->videoModele->trouverParId((int)$videoId);
            if ($video) {
                return $video;
            }
        }

        return $current;
    }

    private function obtenirSuggestions(array $videos, int $videoIdExclu, int $limite = 10): array
    {
        $suggerees = array_values(array_filter($videos, function($video) use ($videoIdExclu) {
            return (int)($video['id'] ?? 0) !== $videoIdExclu;
        }));

        return array_slice($suggerees, 0, max(1, $limite));
    }

    private function requeteVideoPresente(): bool
    {
        if (isset($_GET['video']) && trim((string)$_GET['video']) !== '') {
            return true;
        }

        if (isset($_GET['video_url']) && trim((string)$_GET['video_url']) !== '') {
            return true;
        }

        return false;
    }

    private function extraFiltresRedirection(): array
    {
        $q = trim((string)($_POST['return_q'] ?? ($_GET['q'] ?? '')));
        $categorie = trim((string)($_POST['return_categorie'] ?? ($_GET['categorie'] ?? '')));

        $params = [];
        if ($q !== '') {
            $params['q'] = $q;
        }
        if ($categorie !== '') {
            $params['categorie'] = $categorie;
        }

        return $params;
    }

    private function rediriger(?array $current, array $extra = []): void
    {
        $params = ['page' => 'webtv'];
        if ($current && !empty($current['id'])) {
            $params['video'] = (int)$current['id'];
        }

        foreach ($extra as $key => $value) {
            if ($value !== '' && $value !== null) {
                $params[$key] = $value;
            }
        }

        header('Location: ?' . http_build_query($params));
        exit;
    }
}
