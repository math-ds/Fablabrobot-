<?php
require_once __DIR__ . '/../modèles/AdminUtilisateursModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';

class AdminUtilisateursControleur
{
    private AdminUtilisateursModele $modele;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['utilisateur_role']) || strtolower($_SESSION['utilisateur_role']) !== 'admin') {
            header('Location: ?page=login');
            exit;
        }
        
        $this->modele = new AdminUtilisateursModele();
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
                header("Location: ?page=admin-utilisateurs");
                exit;
            }

            $formAction = $_POST['action'] ?? null;

            try {
                if ($formAction === 'create') {
                    // Validation des données
                    $validations = [
                        'nom' => ValidationHelper::validerChaine($_POST['nom'] ?? '', 2, 100, 'Nom'),
                        'email' => ValidationHelper::validerEmail($_POST['email'] ?? ''),
                        'role' => ValidationHelper::validerRole($_POST['role'] ?? 'Utilisateur')
                    ];

                    foreach ($validations as $field => $result) {
                        if (!$result['valid']) {
                            if (JsonResponseHelper::estAjax()) {
                                JsonResponseHelper::erreurValidation([$field => $result['error']]);
                            }
                            $_SESSION['message'] = $result['error'];
                            $_SESSION['message_type'] = 'danger';
                            header("Location: ?page=admin-utilisateurs");
                            exit;
                        }
                    }

                    // Validation du mot de passe si fourni
                    $mdp = trim($_POST['mot_de_passe'] ?? '');
                    if (!empty($mdp) && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $mdp)) {
                        $error = "Le mot de passe doit contenir au moins 8 caractères, dont 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.";
                        if (JsonResponseHelper::estAjax()) {
                            JsonResponseHelper::erreurValidation(['mot_de_passe' => $error]);
                        }
                        $_SESSION['message'] = $error;
                        $_SESSION['message_type'] = 'danger';
                        header("Location: ?page=admin-utilisateurs");
                        exit;
                    }

                    $id = $this->modele->creer([
                        'nom' => $validations['nom']['value'],
                        'email' => $validations['email']['value'],
                        'role' => $validations['role']['value'],
                        'mot_de_passe' => $mdp
                    ]);

                    if (JsonResponseHelper::estAjax()) {
                        $utilisateur = $this->modele->trouver($id);
                        JsonResponseHelper::succes(['utilisateur' => $utilisateur], 'Utilisateur créé avec succès');
                    }

                    $_SESSION['message'] = "Utilisateur ajouté avec succès.";
                    $_SESSION['message_type'] = "success";

                } elseif ($formAction === 'update') {
                    $id = (int)($_POST['user_id'] ?? 0);

                    // Validation des données
                    $validations = [
                        'nom' => ValidationHelper::validerChaine($_POST['nom'] ?? '', 2, 100, 'Nom'),
                        'email' => ValidationHelper::validerEmail($_POST['email'] ?? ''),
                        'role' => ValidationHelper::validerRole($_POST['role'] ?? 'Utilisateur')
                    ];

                    foreach ($validations as $field => $result) {
                        if (!$result['valid']) {
                            if (JsonResponseHelper::estAjax()) {
                                JsonResponseHelper::erreurValidation([$field => $result['error']]);
                            }
                            $_SESSION['message'] = $result['error'];
                            $_SESSION['message_type'] = 'danger';
                            header("Location: ?page=admin-utilisateurs");
                            exit;
                        }
                    }

                    // Validation du mot de passe si fourni
                    $mdp = trim($_POST['mot_de_passe'] ?? '');
                    if (!empty($mdp) && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $mdp)) {
                        $error = "Le mot de passe doit contenir au moins 8 caractères, dont 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.";
                        if (JsonResponseHelper::estAjax()) {
                            JsonResponseHelper::erreurValidation(['mot_de_passe' => $error]);
                        }
                        $_SESSION['message'] = $error;
                        $_SESSION['message_type'] = 'danger';
                        header("Location: ?page=admin-utilisateurs");
                        exit;
                    }

                    $this->modele->mettreAJour($id, [
                        'nom' => $validations['nom']['value'],
                        'email' => $validations['email']['value'],
                        'role' => $validations['role']['value'],
                        'mot_de_passe' => $mdp
                    ]);

                    if (JsonResponseHelper::estAjax()) {
                        $utilisateur = $this->modele->trouver($id);
                        JsonResponseHelper::succes(['utilisateur' => $utilisateur], 'Utilisateur mis à jour avec succès');
                    }

                    $_SESSION['message'] = "Utilisateur mis à jour.";
                    $_SESSION['message_type'] = "success";

                } elseif ($formAction === 'delete') {
                    $id = (int)($_POST['user_id'] ?? 0);
                    $this->modele->supprimer($id);

                    if (JsonResponseHelper::estAjax()) {
                        JsonResponseHelper::succes(['id' => $id], 'Utilisateur supprimé avec succès');
                    }

                    $_SESSION['message'] = "Utilisateur supprimé.";
                    $_SESSION['message_type'] = "success";
                }
            } catch (Throwable $e) {
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur($e->getMessage(), 500);
                }
                $_SESSION['message'] = "Erreur : " . $e->getMessage();
                $_SESSION['message_type'] = "danger";
            }

            header("Location: ?page=admin-utilisateurs");
            exit;
        }

        $this->index();
    }

    public function index(): void
    {
        $users = $this->modele->tousLesElements() ?? [];

        $total_users = count($users);
        $admins = count(array_filter($users, fn($u) => strtolower($u['role']) === 'admin'));
        $editeurs = count(array_filter($users, fn($u) => in_array(strtolower($u['role']), ['editeur', 'éditeur'])));
        $utilisateurs = count(array_filter($users, fn($u) => in_array(strtolower($u['role']), ['user', 'utilisateur'])));

        $stats = compact('total_users', 'admins', 'editeurs', 'utilisateurs');

        include __DIR__ . '/../vues/admin/utilisateurs-admin.php';
    }
}