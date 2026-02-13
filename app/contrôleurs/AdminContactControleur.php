<?php

require_once __DIR__ . '/../modèles/AdminContactModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';

class AdminContactControleur
{
    private AdminContactModele $modele;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['utilisateur_role']) || strtolower($_SESSION['utilisateur_role']) !== 'admin') {
            header('Location: ?page=login');
            exit;
        }

        $this->modele = new AdminContactModele();
        CsrfHelper::init();
    }

    public function gererRequete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérification CSRF (POST traditionnel ou AJAX)
            $csrfValid = CsrfHelper::verifierJetonPost() || CsrfHelper::verifierJetonEntete();

            if (!$csrfValid) {
                // Régénérer le token pour éviter les blocages
                $newToken = CsrfHelper::genererJeton();

                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur("Token de sécurité invalide", 403, ['new_token' => $newToken]);
                }
                $_SESSION['message'] = "Token de sécurité invalide.";
                $_SESSION['message_type'] = "danger";
                header("Location: ?page=admin-contact");
                exit;
            }

            $action = $_POST['action'] ?? '';
            $id = (int)($_POST['contact_id'] ?? 0);

            try {
                if (in_array($action, ['lu', 'traite', 'non_lu'])) {
                    $this->modele->mettreAJourStatut($id, $action);
                    $nom = htmlspecialchars($_POST['nom'] ?? '', ENT_QUOTES, 'UTF-8');
                    $message = "Le message de \"$nom\" a été marqué comme $action.";

                    if (JsonResponseHelper::estAjax()) {
                        JsonResponseHelper::succes(['id' => $id, 'statut' => $action], $message);
                    } else {
                        $_SESSION['message'] = $message;
                        $_SESSION['message_type'] = 'success';
                    }

                } elseif ($action === 'delete') {
                    $this->modele->supprimer($id);

                    if (JsonResponseHelper::estAjax()) {
                        JsonResponseHelper::succes(['id' => $id], 'Message supprimé avec succès');
                    } else {
                        $_SESSION['message'] = "Message supprimé avec succès.";
                        $_SESSION['message_type'] = 'success';
                    }
                }
            } catch (Throwable $e) {
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur($e->getMessage(), 500);
                }
                $_SESSION['message'] = "Erreur : " . $e->getMessage();
                $_SESSION['message_type'] = "danger";
            }

            header('Location: ?page=admin-contact');
            exit;
        }

        $this->index();
    }

    public function index(): void
    {
        $contacts = $this->modele->tousLesElements() ?? [];

        $total = count($contacts);
        $non_lus = count(array_filter($contacts, fn($c) => $c['statut'] === 'non_lu'));
        $lus = count(array_filter($contacts, fn($c) => $c['statut'] === 'lu'));
        $traites = count(array_filter($contacts, fn($c) => $c['statut'] === 'traite'));

        $stats = ['total' => $total, 'non_lus' => $non_lus, 'lus' => $lus, 'traites' => $traites];
        include __DIR__ . '/../vues/admin/contact-admin.php';
    }
}