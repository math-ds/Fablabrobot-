<?php

require_once __DIR__ . '/../modèles/AdminContactModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
require_once __DIR__ . '/../helpers/Pagination.php';

class AdminContactControleur
{
    private AdminContactModele $modele;
    private array $statutsAutorises = ['all', 'non_lu', 'lu', 'traite'];

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $this->modele = new AdminContactModele();
        CsrfHelper::init();
    }

    public function gererRequete(): void
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
                error_log('AdminContactControleur::gererRequete - ' . $e->getMessage());
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreurServeur('Une erreur est survenue lors du traitement de la demande.');
                }
                $_SESSION['message'] = "Une erreur est survenue lors du traitement de la demande.";
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
        $recherche = trim((string)($_GET['q'] ?? ''));
        $statut = strtolower(trim((string)($_GET['statut'] ?? 'all')));
        if (!in_array($statut, $this->statutsAutorises, true)) {
            $statut = 'all';
        }

        $total = count($contacts);
        $non_lus = count(array_filter($contacts, fn($c) => $c['statut'] === 'non_lu'));
        $lus = count(array_filter($contacts, fn($c) => $c['statut'] === 'lu'));
        $traites = count(array_filter($contacts, fn($c) => $c['statut'] === 'traite'));

        $rechercheNormalisee = mb_strtolower($recherche, 'UTF-8');
        $contactsFiltres = array_values(array_filter(
            $contacts,
            function (array $contact) use ($statut, $rechercheNormalisee): bool {
                $statutContact = strtolower((string)($contact['statut'] ?? ''));
                if ($statut !== 'all' && $statutContact !== $statut) {
                    return false;
                }

                if ($rechercheNormalisee === '') {
                    return true;
                }

                $index = mb_strtolower(implode(' ', [
                    (string)($contact['nom'] ?? ''),
                    (string)($contact['email'] ?? ''),
                    (string)($contact['sujet'] ?? ''),
                    (string)($contact['message'] ?? ''),
                ]), 'UTF-8');

                return mb_strpos($index, $rechercheNormalisee, 0, 'UTF-8') !== false;
            }
        ));

        $stats = ['total' => $total, 'non_lus' => $non_lus, 'lus' => $lus, 'traites' => $traites];
        $totalFiltres = count($contactsFiltres);
        $pagination = new Pagination($totalFiltres, 10);
        $contacts = array_slice($contactsFiltres, $pagination->offset(), $pagination->limit());
        $filtreStatut = $statut;
        include __DIR__ . '/../vues/admin/contact-admin.php';
    }
}
