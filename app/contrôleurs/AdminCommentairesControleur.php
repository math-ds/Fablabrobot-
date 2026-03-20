<?php
require_once __DIR__ . '/../modèles/AdminCommentairesModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';
require_once __DIR__ . '/../helpers/Pagination.php';

class AdminCommentairesControleur
{
    private AdminCommentairesModele $modele;
    private $cache;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->modele = new AdminCommentairesModele();
        $this->cache = GestionnaireCache::obtenirInstance();
        CsrfHelper::init();
    }

    public function gererRequete(?string $action = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $formAction = $_POST['action'] ?? '';

            if ($formAction === 'delete') {
                $this->supprimer();
                return;
            }
        }

        $this->index();
    }

    public function index(): void
    {
        $q = isset($_GET['q']) && trim((string)$_GET['q']) !== '' ? trim((string)$_GET['q']) : null;
        $type = $this->normaliserTypeFiltre((string)($_GET['type'] ?? 'all'));

        $commentaires = $this->modele->tousLesElements($q, $type) ?? [];
        $total_commentaires = count($commentaires);
        $videos_commentees = count(array_unique(array_filter(array_map(
            static fn(array $c): int => (int)($c['video_id'] ?? 0),
            $commentaires
        ))));
        $pagination = new Pagination($total_commentaires, 10);
        $commentaires = array_slice($commentaires, $pagination->offset(), $pagination->limit());
        $stats = $this->modele->obtenirStatistiques();
        $filtreType = $type;

        require __DIR__ . '/../vues/admin/commentaires-admin.php';
    }

    private function supprimer(): void
    {
        $csrfValid = CsrfHelper::verifierJetonPost() || CsrfHelper::verifierJetonEntete();

        if (!$csrfValid) {
            if (JsonResponseHelper::estAjax()) {
                JsonResponseHelper::erreurAvecDonnees('Token de sécurité invalide', 403, [
                    'new_token' => CsrfHelper::genererJeton()
                ]);
            }
            $_SESSION['message'] = 'Token de sécurité invalide.';
            $_SESSION['message_type'] = 'danger';
            $this->redirect();
            return;
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            if (JsonResponseHelper::estAjax()) {
                JsonResponseHelper::erreur('ID de commentaire invalide', 400);
            }
            $_SESSION['message'] = 'ID de commentaire invalide.';
            $_SESSION['message_type'] = 'danger';
            $this->redirect();
            return;
        }

        $commentaire = $this->modele->trouver($id);
        if (!$commentaire || empty($commentaire['video_id'])) {
            if (JsonResponseHelper::estAjax()) {
                JsonResponseHelper::erreur('Commentaire introuvable', 404);
            }
            $_SESSION['message'] = 'Commentaire introuvable.';
            $_SESSION['message_type'] = 'danger';
            $this->redirect();
            return;
        }

        $nbSupprimes = $this->modele->supprimerAvecDescendants($id);
        if ($nbSupprimes > 0) {
            $this->cache->supprimer('video_comments_' . (int)$commentaire['video_id']);
            if (JsonResponseHelper::estAjax()) {
                JsonResponseHelper::succes(
                    ['id' => $id, 'deleted_count' => $nbSupprimes],
                    'Commentaire supprime avec succes'
                );
            }
            $_SESSION['message'] = 'Commentaire supprime avec succes.';
            $_SESSION['message_type'] = 'success';
        } else {
            if (JsonResponseHelper::estAjax()) {
                JsonResponseHelper::erreur('Erreur lors de la suppression', 500);
            }
            $_SESSION['message'] = 'Erreur lors de la suppression.';
            $_SESSION['message_type'] = 'danger';
        }

        $this->redirect();
    }

    private function redirect(): void
    {
        $query = !empty($_GET['q']) ? '&q=' . urlencode((string)$_GET['q']) : '';
        $type = $this->normaliserTypeFiltre((string)($_GET['type'] ?? 'all'));
        if ($type !== 'all') {
            $query .= '&type=' . urlencode($type);
        }
        if (isset($_GET['p']) && (int)$_GET['p'] > 1) {
            $query .= '&p=' . (int)$_GET['p'];
        }
        header('Location: ?page=admin-comments' . $query);
        exit;
    }

    private function normaliserTypeFiltre(string $type): string
    {
        $valeur = strtolower(trim($type));

        if ($valeur === '' || $valeur === 'all' || $valeur === 'tous') {
            return 'all';
        }

        if (in_array($valeur, ['parent', 'commentaire', 'commentaires', 'racine'], true)) {
            return 'parent';
        }

        if (in_array($valeur, ['reponse', 'reponses', 'reply', 'replies'], true)) {
            return 'reponse';
        }

        return 'all';
    }
}
