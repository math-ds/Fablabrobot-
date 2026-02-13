<?php
require_once __DIR__ . '/../modèles/AdminCommentairesModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';

class AdminCommentairesControleur
{
    private AdminCommentairesModele $modele;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['utilisateur_role']) || strtolower($_SESSION['utilisateur_role']) !== 'admin') {
            header('Location: ?page=login');
            exit;
        }

        $this->modele = new AdminCommentairesModele();
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

        $commentaires = $this->modele->tousLesElements($q) ?? [];
        $stats = $this->modele->obtenirStatistiques();

        require __DIR__ . '/../vues/admin/commentaires-admin.php';
    }

    private function supprimer(): void
    {
        // Vérification CSRF (POST traditionnel ou AJAX)
        $csrfValid = CsrfHelper::verifierJetonPost() || CsrfHelper::verifierJetonEntete();

        if (!$csrfValid) {
            if (JsonResponseHelper::estAjax()) {
                JsonResponseHelper::erreur("Token de sécurité invalide", 403);
            }
            $_SESSION['message'] = "Token de sécurité invalide.";
            $_SESSION['message_type'] = "danger";
            $this->redirect();
            return;
        }

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            if (JsonResponseHelper::estAjax()) {
                JsonResponseHelper::erreur("ID de commentaire invalide", 400);
            }
            $_SESSION['message'] = "ID de commentaire invalide.";
            $_SESSION['message_type'] = "danger";
            $this->redirect();
            return;
        }

        if ($this->modele->supprimer($id)) {
            if (JsonResponseHelper::estAjax()) {
                JsonResponseHelper::succes(['id' => $id], 'Commentaire supprimé avec succès');
            }
            $_SESSION['message'] = "Commentaire supprimé avec succès.";
            $_SESSION['message_type'] = "success";
        } else {
            if (JsonResponseHelper::estAjax()) {
                JsonResponseHelper::erreur("Erreur lors de la suppression", 500);
            }
            $_SESSION['message'] = "Erreur lors de la suppression.";
            $_SESSION['message_type'] = "danger";
        }

        $this->redirect();
    }

    private function redirect(): void
    {
        $query = !empty($_GET['q']) ? '&q=' . urlencode((string)$_GET['q']) : '';
        header('Location: ?page=admin-comments' . $query);
        exit;
    }
}