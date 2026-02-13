<?php

session_start();

// ✅ PROTECTION SESSION HIJACKING - Vérifier validité de la session
if (isset($_SESSION['utilisateur_id'])) {
    // Vérifier timeout (30 minutes d'inactivité)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        header('Location: ?page=login');
        exit;
    }
    $_SESSION['last_activity'] = time();

    // Vérifier user agent (protection basique contre vol de session)
    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
        session_unset();
        session_destroy();
        header('Location: ?page=login');
        exit;
    }
}

$GLOBALS['baseUrl'] = '/Fablabrobot/public/';

function load_controller($relativePath) {
    $full = __DIR__ . '/../' . $relativePath;
    if (!file_exists($full)) {
        throw new Exception("Contrôleur introuvable : $relativePath");
    }
    require_once $full;
}

function new_if_exists(array $classNames) {
    foreach ($classNames as $cn) {
        if (class_exists($cn)) return new $cn();
    }
    return null;
}

/**
 * Vérifie que l'utilisateur est authentifié et possède le rôle Admin
 * Redirige vers login si non authentifié, affiche erreur 403 si non admin
 */
function verifierAccesAdmin() {
    if (!isset($_SESSION['utilisateur_id'])) {
        header('Location: ?page=login');
        exit;
    }
    if (!isset($_SESSION['utilisateur_role']) || strtolower($_SESSION['utilisateur_role']) !== 'admin') {
        http_response_code(403);
        echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Accès refusé - FABLAB</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #191a28; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .error-container { text-align: center; padding: 40px; }
        .error-container h1 { color: #ff6b6b; font-size: 3rem; margin: 0 0 1rem; }
        .error-container p { color: #8b92b0; font-size: 1.2rem; margin-bottom: 2rem; }
        .btn { display: inline-block; padding: 12px 30px; background: #00afa7; color: #fff; text-decoration: none; border-radius: 8px; transition: all 0.3s; }
        .btn:hover { background: #008f8c; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class='error-container'>
        <h1>⚠️ Accès refusé</h1>
        <p>Vous devez être administrateur pour accéder à cette page.</p>
        <a href='?page=accueil' class='btn'>Retour à l'accueil</a>
    </div>
</body>
</html>";
        exit;
    }
}

$page = $_GET['page'] ?? 'accueil';

try {
    switch ($page) {


        case 'accueil':
            load_controller('app/contrôleurs/AccueilControleur.php');
            (new AccueilControleur())->index();
            break;

        case 'articles':
            load_controller('app/contrôleurs/ArticlesControleur.php');
            $ctrl = new_if_exists(['ArticlesControleur','ArticleControleur']);
            if (!$ctrl) throw new Exception("Classe ArticlesControleur (ou ArticleControleur) introuvable. Vérifie le nom exact dans app/contrôleurs/ArticlesControleur.php");
            $ctrl->index();
            break;

        case 'article-detail':
            load_controller('app/contrôleurs/ArticlesControleur.php');
            $ctrl = new_if_exists(['ArticlesControleur','ArticleControleur']);
            if (!$ctrl) throw new Exception("Classe ArticlesControleur (ou ArticleControleur) introuvable. Vérifie le nom exact.");
            if (!isset($_GET['id'])) { echo "<h2>Article introuvable.</h2>"; break; }
            $ctrl->detail($_GET['id']);
            break;
                    case 'article_creation':
            load_controller('app/contrôleurs/ArticlesControleur.php');
            $ctrl = new_if_exists(['ArticlesControleur','ArticleControleur']);
            if (!$ctrl) throw new Exception("Classe ArticlesControleur (ou ArticleControleur) introuvable.");
            $ctrl->creation();
            break;
                    case 'article_enregistrer':
            load_controller('app/contrôleurs/ArticlesControleur.php');
            $ctrl = new_if_exists(['ArticlesControleur','ArticleControleur']);
            if (!$ctrl) throw new Exception("Classe ArticlesControleur (ou ArticleControleur) introuvable.");
            $ctrl->enregistrer();
            break;



        case 'projets':
            load_controller('app/contrôleurs/ProjetsControleur.php');
            (new ProjetsControleur())->index();
            break;

        case 'projet':
            load_controller('app/contrôleurs/ProjetControleur.php');
            if (!isset($_GET['id'])) { echo "<h2>Projet introuvable.</h2>"; break; }
            (new ProjetControleur())->detail($_GET['id']);
            break;
case 'projet_creation':
    load_controller('app/contrôleurs/ProjetsControleur.php');
    (new ProjetsControleur())->creation();
    break;

case 'projet_enregistrer':
    load_controller('app/contrôleurs/ProjetsControleur.php');
    (new ProjetsControleur())->enregistrer();
    break;

        case 'webtv':
            load_controller('app/contrôleurs/VideoControleur.php');
            (new VideoControleur())->index();
            break;

        case 'contact':
            load_controller('app/contrôleurs/ContactControleur.php');
            (new ContactControleur())->index();
            break;
            
   case 'profil':
            load_controller('app/contrôleurs/ProfilControleur.php');
            $ctrl = new ProfilControleur();
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $ctrl->mettreAJourPhoto();
            } else {
                $ctrl->index();
            }
            break;
        
        case 'login':
            load_controller('app/contrôleurs/AuthentificationControleur.php');
            (new AuthentificationControleur())->connexion();
            break;

        case 'inscription':
            load_controller('app/contrôleurs/AuthentificationControleur.php');
            (new AuthentificationControleur())->inscription();
            break;

        case 'logout':
            load_controller('app/contrôleurs/AuthentificationControleur.php');
            (new AuthentificationControleur())->deconnexion();
            break;

        case 'mdp-oublie':
            load_controller('app/contrôleurs/AuthentificationControleur.php');
            (new AuthentificationControleur())->mdpOublie();
            break;


        case 'admin':
            verifierAccesAdmin();
            load_controller('app/contrôleurs/AdminDashboardControleur.php');
            (new AdminDashboardControleur())->index();
            break;

        case 'admin-projets':
            verifierAccesAdmin();
            load_controller('app/contrôleurs/AdminProjetsControleur.php');
            (new AdminProjetsControleur())->gererRequete($_POST['action'] ?? null);
            break;

        case 'admin-articles':
            verifierAccesAdmin();
            load_controller('app/contrôleurs/AdminArticlesControleur.php');
            (new AdminArticlesControleur())->gererRequete($_POST['action'] ?? null);
            break;

        case 'admin-webtv':
            verifierAccesAdmin();
            load_controller('app/contrôleurs/AdminVideoControleur.php');
            (new AdminVideoControleur())->gererRequete($_POST['action'] ?? null);
            break;

        case 'admin-comments':
            verifierAccesAdmin();
            load_controller('app/contrôleurs/AdminCommentairesControleur.php');
            (new AdminCommentairesControleur())->gererRequete($_POST['action'] ?? null);
            break;


        case 'admin-utilisateurs':
            verifierAccesAdmin();
            load_controller('app/contrôleurs/AdminUtilisateursControleur.php');
            (new AdminUtilisateursControleur())->gererRequete($_POST['action'] ?? null);
            break;


        case 'utilisateurs-admin':
            verifierAccesAdmin();
            load_controller('app/contrôleurs/AdminUtilisateursControleur.php');
            (new AdminUtilisateursControleur())->gererRequete($_POST['action'] ?? null);
            break;

        case 'admin-contact':
            verifierAccesAdmin();
            load_controller('app/contrôleurs/AdminContactControleur.php');
            (new AdminContactControleur())->gererRequete($_POST['action'] ?? null);
            break;

        case 'admin-corbeille':
            verifierAccesAdmin();
            load_controller('app/contrôleurs/AdminCorbeilleControleur.php');
            $ctrl = new AdminCorbeilleControleur();
            $action = $_GET['action'] ?? null;
            if ($action === 'restaurer') {
                $ctrl->restaurerElement();
            } elseif ($action === 'supprimer-definitivement') {
                $ctrl->supprimerDefinitivement();
            } elseif ($action === 'restaurer-tous') {
                $ctrl->restaurerTous();
            } elseif ($action === 'vider-corbeille') {
                $ctrl->viderCorbeille();
            } else {
                $ctrl->afficherCorbeille();
            }
            break;

        case 'admin-cache':
            verifierAccesAdmin();
            load_controller('app/contrôleurs/AdminCacheControleur.php');
            $ctrl = new AdminCacheControleur();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $ctrl->gererAction();
            } else {
                $ctrl->index();
            }
            break;


        default:
            load_controller('app/contrôleurs/AccueilControleur.php');
            (new AccueilControleur())->index();
            break;
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo "<pre style='padding:16px;background:#111;color:#f55;border:1px solid #400;border-radius:8px;'>
Erreur fatale dans le routeur :
" . htmlspecialchars($e->getMessage()) . "
</pre>";
}
