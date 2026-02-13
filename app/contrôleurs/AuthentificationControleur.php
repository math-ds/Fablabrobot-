<?php

require_once __DIR__ . '/../modèles/AuthentificationModele.php';
require_once __DIR__ . '/../../config/database.php';

class AuthentificationControleur
{
    private $modele;

    public function __construct()
    {
        $db = (new Database())->getConnection();
        $this->modele = new AuthentificationModele($db);
    }

    /**
     * Connexion d'un utilisateur
     */
    public function connexion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $motDePasse = $_POST['password'] ?? '';

            if (empty($email) || empty($motDePasse)) {
                $error = "Tous les champs sont requis.";
                require __DIR__ . '/../vues/utilisateurs/login.php';
                return;
            }

            $utilisateur = $this->modele->verifierConnexion($email, $motDePasse);

            if ($utilisateur) {
                // ✅ PROTECTION SESSION FIXATION - Régénérer l'ID de session
                session_regenerate_id(true);

                // Données utilisateur
                $_SESSION['utilisateur_id'] = $utilisateur['id'];
                $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
                $_SESSION['utilisateur_email'] = $utilisateur['email'];
                $_SESSION['utilisateur_role'] = !empty($utilisateur['role']) ? $utilisateur['role'] : 'Utilisateur';

                // Générer les initiales pour l'avatar
                $initiales = $this->genererInitiales($utilisateur['nom']);
                $_SESSION['utilisateur_avatar'] = "https://via.placeholder.com/40/232e59/ffffff?text=" . $initiales;

                // ✅ PROTECTION SESSION HIJACKING - Stocker empreinte sécurité
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
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

    /**
     * Inscription d'un nouvel utilisateur
     */
    public function inscription()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $motDePasse = $_POST['password'] ?? '';
            $confirmation = $_POST['confirm-password'] ?? '';

            // Validations
            if (empty($nom) || empty($email) || empty($motDePasse) || empty($confirmation)) {
                $error = "Tous les champs sont requis.";

            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "L'adresse email n'est pas valide.";

            } elseif (strlen($nom) < 2) {
                $error = "Le nom doit contenir au moins 2 caractères.";

            } elseif ($motDePasse !== $confirmation) {
                $error = "Les mots de passe ne correspondent pas.";

            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $motDePasse)) {
                // ✅ REGEX : validation de la complexité du mot de passe
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

    /**
     * Déconnexion de l'utilisateur
     */
    public function deconnexion()
    {
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

    /**
     * Page mot de passe oublié
     */
    public function mdpOublie()
    {
        require __DIR__ . '/../vues/utilisateurs/motdepasseoublie.php';
    }

    /**
     * Générer les initiales à partir du nom
     */
    private function genererInitiales(string $nom): string
    {
        if (empty($nom)) return 'US';
        $parties = preg_split('/\s+/', trim($nom));
        return (count($parties) >= 2)
            ? strtoupper(substr($parties[0], 0, 1) . substr(end($parties), 0, 1))
            : strtoupper(substr($parties[0], 0, 2));
    }
}
