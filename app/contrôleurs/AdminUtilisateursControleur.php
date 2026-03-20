<?php
require_once __DIR__ . '/../modèles/AdminUtilisateursModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
require_once __DIR__ . '/../helpers/AvatarHelper.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';
require_once __DIR__ . '/../helpers/Pagination.php';

class AdminUtilisateursControleur
{
    private AdminUtilisateursModele $modele;
    private array $rolesAutorises = ['all', 'admin', 'editeur', 'utilisateur'];

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $this->modele = new AdminUtilisateursModele();
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
                header("Location: ?page=admin-utilisateurs");
                exit;
            }

            $formAction = $_POST['action'] ?? null;

            try {
                if ($formAction === 'create') {
                    
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

                    
                    $password = trim((string)($_POST['password'] ?? ''));
                    if (!empty($password) && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
                        $error = "Le mot de passe doit contenir au moins 8 caractères, dont 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.";
                        if (JsonResponseHelper::estAjax()) {
                            JsonResponseHelper::erreurValidation(['password' => $error]);
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
                        'password' => $password
                    ]);

                    if (JsonResponseHelper::estAjax()) {
                        $utilisateur = $this->modele->trouver($id);
                        $utilisateur = $this->enrichirUtilisateurAvatar($utilisateur);
                        JsonResponseHelper::succes(['utilisateur' => $utilisateur], 'Utilisateur créé avec succès');
                    }

                    $_SESSION['message'] = "Utilisateur ajouté avec succès.";
                    $_SESSION['message_type'] = "success";

                } elseif ($formAction === 'update') {
                    $id = (int)($_POST['user_id'] ?? 0);

                    
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

                    
                    $password = trim((string)($_POST['password'] ?? ''));
                    if (!empty($password) && !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
                        $error = "Le mot de passe doit contenir au moins 8 caractères, dont 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.";
                        if (JsonResponseHelper::estAjax()) {
                            JsonResponseHelper::erreurValidation(['password' => $error]);
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
                        'password' => $password
                    ]);

                    if (JsonResponseHelper::estAjax()) {
                        $utilisateur = $this->modele->trouver($id);
                        $utilisateur = $this->enrichirUtilisateurAvatar($utilisateur);
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
                error_log('AdminUtilisateursControleur::gererRequete - ' . $e->getMessage());
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreurServeur('Une erreur est survenue lors du traitement de la demande.');
                }
                $_SESSION['message'] = "Une erreur est survenue lors du traitement de la demande.";
                $_SESSION['message_type'] = "danger";
            }

            header("Location: ?page=admin-utilisateurs");
            exit;
        }

        $this->index();
    }

    private function enrichirUtilisateurAvatar(?array $utilisateur): ?array
    {
        if (!$utilisateur) {
            return null;
        }

        $baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
        $avatar = AvatarHelper::construireDonnees(
            (string)($utilisateur['nom'] ?? ''),
            $utilisateur['photo'] ?? null,
            $baseUrl
        );

        $utilisateur['avatar_has_photo'] = $avatar['has_photo'];
        $utilisateur['avatar_photo_url'] = $avatar['photo_url'];
        $utilisateur['avatar_initiales'] = $avatar['initiales'];
        $utilisateur['avatar_couleur'] = $avatar['couleur'];
        $utilisateur['avatar_classe_couleur'] = $avatar['classe_couleur'];

        return $utilisateur;
    }

    private function enrichirListeUtilisateursAvatar(array $utilisateurs): array
    {
        $resultat = [];
        foreach ($utilisateurs as $utilisateur) {
            $resultat[] = $this->enrichirUtilisateurAvatar($utilisateur);
        }
        return $resultat;
    }

    public function index(): void
    {
        $users = $this->modele->tousLesElements() ?? [];
        $users = $this->enrichirListeUtilisateursAvatar($users);
        $recherche = trim((string)($_GET['q'] ?? ''));
        $role = strtolower(trim((string)($_GET['role'] ?? 'all')));
        if (!in_array($role, $this->rolesAutorises, true)) {
            $role = 'all';
        }

        $total_users = count($users);
        $admins = count(array_filter($users, fn($u) => RoleHelper::normaliser((string)($u['role'] ?? '')) === RoleHelper::ADMIN));
        $editeurs = count(array_filter($users, fn($u) => RoleHelper::normaliser((string)($u['role'] ?? '')) === RoleHelper::EDITEUR));
        $utilisateurs = count(array_filter($users, fn($u) => RoleHelper::normaliser((string)($u['role'] ?? '')) === RoleHelper::UTILISATEUR));

        $rechercheNormalisee = mb_strtolower($recherche, 'UTF-8');
        $usersFiltres = array_values(array_filter(
            $users,
            function (array $user) use ($role, $rechercheNormalisee): bool {
                $roleUtilisateur = RoleHelper::normaliser((string)($user['role'] ?? ''));
                if ($role !== 'all' && $roleUtilisateur !== $role) {
                    return false;
                }

                if ($rechercheNormalisee === '') {
                    return true;
                }

                $index = mb_strtolower(implode(' ', [
                    (string)($user['nom'] ?? ''),
                    (string)($user['email'] ?? ''),
                    (string)($user['role'] ?? ''),
                ]), 'UTF-8');

                return mb_strpos($index, $rechercheNormalisee, 0, 'UTF-8') !== false;
            }
        ));

        $stats = compact('total_users', 'admins', 'editeurs', 'utilisateurs');
        $totalFiltres = count($usersFiltres);
        $pagination = new Pagination($totalFiltres, 10);
        $users = array_slice($usersFiltres, $pagination->offset(), $pagination->limit());
        $filtreRole = $role;

        include __DIR__ . '/../vues/admin/utilisateurs-admin.php';
    }
}
