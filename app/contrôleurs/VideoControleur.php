<?php
require_once __DIR__ . '/../modèles/VideoModele.php';
require_once __DIR__ . '/../modèles/CommentairesVideoModele.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';

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

    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Filtres
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $cat = isset($_GET['categorie']) ? trim((string)$_GET['categorie']) : '';

        // Cache : Liste des vidéos avec filtres (clé unique par combinaison de filtres)
        $cacheKey = 'liste_videos_' . md5($q . $cat);
        $videos = $this->cache->memoriser($cacheKey, function() use ($q, $cat) {
            return $this->videoModele->tousLesVideos($q ?: null, $cat ?: null);
        }, 600); // 10 minutes

        // Cache : Catégories (peu changeantes, durée plus longue)
        $categories = $this->cache->memoriser('video_categories', function() {
            return $this->videoModele->obtenirCategories();
        }, 1800); // 30 minutes

        $current = $this->selectionnerVideoActuelle($videos);

        // Ajout commentaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_comment') {
            $this->gererSoumissionCommentaire($current);
            return;
        }

        // Suppression (admin)
        if (isset($_GET['del']) && !empty($_SESSION['utilisateur_role']) && strtolower($_SESSION['utilisateur_role']) === 'admin') {
            $this->gererSuppressionCommentaire($_GET['del'], $current);
            return;
        }

        // Incrément vues
        if ($current && !empty($current['id'])) {
            $this->videoModele->incrementerVues((int)$current['id']);
        }

        // Cache : Commentaires de la vidéo actuelle
        if ($current && !empty($current['id'])) {
            $commentaires = $this->cache->memoriser('video_comments_' . $current['id'],
                function() use ($current) {
                    return $this->commentaireModele->listerPourVideo((int)$current['id']);
                },
                600 // 10 minutes
            );
        } else {
            $commentaires = [];
        }

        require __DIR__ . '/../vues/webtv/webtv.php';
    }

    private function selectionnerVideoActuelle(array $videos): ?array
    {
        if (isset($_GET['video']) && ctype_digit((string)$_GET['video'])) {
            $video = $this->videoModele->trouverParId((int)$_GET['video']);
            if ($video) return $video;
        }

        if (isset($_GET['video_url']) && trim((string)$_GET['video_url']) !== '') {
            $video = $this->videoModele->trouverParUrlYoutube(trim((string)$_GET['video_url']));
            if ($video) return $video;
        }

        return !empty($videos) ? $videos[0] : null;
    }

    private function gererSoumissionCommentaire(?array $current): void
    {
        if (!$current || empty($current['id'])) {
            $_SESSION['message'] = "Vidéo introuvable.";
            $_SESSION['message_type'] = "danger";
            $this->rediriger($current);
            return;
        }

        if (empty($_SESSION['utilisateur_id'])) {
            $_SESSION['message'] = "Vous devez être connecté pour commenter.";
            $_SESSION['message_type'] = "danger";
            $this->rediriger($current);
            return;
        }

        $texte = trim((string)($_POST['commentaire'] ?? ''));

        if ($texte === '') {
            $_SESSION['message'] = "Le commentaire ne peut pas être vide.";
            $_SESSION['message_type'] = "warning";
            $this->rediriger($current);
            return;
        }

        $ok = $this->commentaireModele->creer(
            (int)$current['id'],
            (int)$_SESSION['utilisateur_id'],
            $texte
        );

        if ($ok) {
            // Invalider le cache des commentaires de cette vidéo
            $this->cache->supprimer('video_comments_' . $current['id']);

            $_SESSION['message'] = "Commentaire publié ✅";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Erreur lors de la publication du commentaire.";
            $_SESSION['message_type'] = "danger";
        }

        $this->rediriger($current);
    }

    private function gererSuppressionCommentaire($commentId, ?array $current): void
    {
        if (!ctype_digit((string)$commentId)) {
            $this->rediriger($current);
            return;
        }

        $this->commentaireModele->supprimer((int)$commentId);

        // Invalider le cache des commentaires de cette vidéo
        if ($current && !empty($current['id'])) {
            $this->cache->supprimer('video_comments_' . $current['id']);
        }

        $_SESSION['message'] = "Commentaire supprimé.";
        $_SESSION['message_type'] = "success";
        $this->rediriger($current);
    }

    private function rediriger(?array $current): void
    {
        $videoParam = $current
            ? (isset($current['youtube_url']) && !empty($current['youtube_url']) ? 'video_url=' . urlencode($current['youtube_url']) : 'video=' . (int)$current['id'])
            : '';

        header("Location: ?page=webtv" . ($videoParam ? "&$videoParam" : ""));
        exit;
    }
}
