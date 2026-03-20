<?php

require_once __DIR__ . '/../modèles/AuthentificationModele.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';

class AuthentificationControleur
{
    private $modele;

    public function __construct()
    {
        $db = (new Database())->getConnection();
        $this->modele = new AuthentificationModele($db);
    }

    
    public function connexion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validerJeton($_POST['csrf_token'] ?? '')) {
                $error = "Token de sécurité invalide. Veuillez réessayer.";
                require __DIR__ . '/../vues/utilisateurs/login.php';
                return;
            }

            $email = trim($_POST['email'] ?? '');
            $motDePasse = $_POST['password'] ?? '';

            if (empty($email) || empty($motDePasse)) {
                $error = "Tous les champs sont requis.";
                require __DIR__ . '/../vues/utilisateurs/login.php';
                return;
            }

            $utilisateur = $this->modele->verifierConnexion($email, $motDePasse);

            if ($utilisateur) {
                
                session_regenerate_id(true);

                
                $_SESSION['utilisateur_id'] = $utilisateur['id'];
                $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
                $_SESSION['utilisateur_email'] = $utilisateur['email'];
                $_SESSION['utilisateur_role'] = RoleHelper::normaliser($utilisateur['role'] ?? '');
                $_SESSION['utilisateur_photo'] = !empty($utilisateur['photo']) ? $utilisateur['photo'] : null;
                unset($_SESSION['utilisateur_avatar']);


                
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
                $_SESSION['last_activity'] = time();

                header("Location: ?page=accueil");
                exit;
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        }

        require __DIR__ . '/../vues/utilisateurs/login.php';
    }

    
    public function inscription()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!CsrfHelper::validerJeton($_POST['csrf_token'] ?? '')) {
                $error = "Token de sécurité invalide. Veuillez réessayer.";
                require __DIR__ . '/../vues/utilisateurs/inscription.php';
                return;
            }

            $nom = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $motDePasse = $_POST['password'] ?? '';
            $confirmation = $_POST['confirm-password'] ?? '';

            
            if (empty($nom) || empty($email) || empty($motDePasse) || empty($confirmation)) {
                $error = "Tous les champs sont requis.";

            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "L'adresse email n'est pas valide.";

            } elseif (strlen($nom) < 2) {
                $error = "Le nom doit contenir au moins 2 caractères.";

            } elseif ($motDePasse !== $confirmation) {
                $error = "Les mots de passe ne correspondent pas.";

            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $motDePasse)) {
                $error = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";

            } elseif ($this->modele->emailExiste($email)) {
                $error = "Cet email est déjà utilisé.";

            } else {
                $hash = password_hash($motDePasse, PASSWORD_DEFAULT);
                $this->modele->creerUtilisateur($nom, $email, $hash);

                $_SESSION['success'] = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
                header("Location: ?page=login");
                exit;
            }
        }

        require __DIR__ . '/../vues/utilisateurs/inscription.php';
    }

    
    public function deconnexion()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Méthode non autorisée";
            exit;
        }

        if (!CsrfHelper::validerJeton($_POST['csrf_token'] ?? '')) {
            $_SESSION['message'] = "Token de sécurité invalide. Veuillez réessayer.";
            $_SESSION['message_type'] = 'danger';
            header("Location: ?page=accueil");
            exit;
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        header("Location: ?page=accueil");
        exit;
    }

    
    public function mdpOublie()
    {
        require __DIR__ . '/../vues/utilisateurs/motdepasseoublie.php';
    }
}
