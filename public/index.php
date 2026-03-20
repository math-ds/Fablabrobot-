<?php

session_start();


if (isset($_SESSION['utilisateur_id'])) {
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        header('Location: ?page=login');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

$GLOBALS['baseUrl'] = '/Fablabrobot/public/';

function load_controller_by_basename(string $filename) {
    $safeFilename = basename($filename);
    if ($safeFilename !== $filename || !preg_match('/^[A-Za-z0-9_]+\.php$/', $safeFilename)) {
        throw new Exception('Nom de contrôleur invalide : ' . $filename);
    }

    $fullPath = __DIR__ . '/../app/contrôleurs/' . $safeFilename;
    if (!file_exists($fullPath)) {
        throw new Exception('Contrôleur introuvable : ' . $filename);
    }

    require_once $fullPath;
}

function render_error_view(int $statusCode, string $errorTitle, string $errorMessage): void {
    http_response_code($statusCode);
    $viewPath = __DIR__ . '/../app/vues/erreurs/' . $statusCode . '.php';
    if (!file_exists($viewPath)) {
        $viewPath = __DIR__ . '/../app/vues/erreurs/500.php';
    }
    require $viewPath;
}

function new_if_exists(array $classNames) {
    foreach ($classNames as $cn) {
        if (class_exists($cn)) return new $cn();
    }
    return null;
}


function verifierAccesAdmin() {
    require_once __DIR__ . '/../app/middlewares/RoleMiddleware.php';
    RoleMiddleware::exigerAdmin();
}


function verifierConnexionUtilisateur() {
    require_once __DIR__ . '/../app/middlewares/AuthMiddleware.php';
    AuthMiddleware::exigerConnexion();
}

$page = $_GET['page'] ?? 'accueil';

try {
    switch ($page) {


        case 'accueil':
            load_controller_by_basename('AccueilControleur.php');
            (new AccueilControleur())->index();
            break;

        case 'articles':
            load_controller_by_basename('ArticlesControleur.php');
            $ctrl = new_if_exists(['ArticlesControleur','ArticleControleur']);
            if (!$ctrl) throw new Exception("Classe ArticlesControleur (ou ArticleControleur) introuvable. Vérifie le nom exact dans app/contrôleurs/ArticlesControleur.php");
            $ctrl->index();
            break;

        case 'article-detail':
            load_controller_by_basename('ArticlesControleur.php');
            $ctrl = new_if_exists(['ArticlesControleur','ArticleControleur']);
            if (!$ctrl) throw new Exception("Classe ArticlesControleur (ou ArticleControleur) introuvable. Vérifie le nom exact.");
            if (!isset($_GET['id'])) {
                render_error_view(404, 'Article introuvable', "L'article demandé est introuvable.");
                break;
            }
            $ctrl->detail($_GET['id']);
            break;
                    case 'article_creation':
            load_controller_by_basename('ArticlesControleur.php');
            $ctrl = new_if_exists(['ArticlesControleur','ArticleControleur']);
            if (!$ctrl) throw new Exception("Classe ArticlesControleur (ou ArticleControleur) introuvable.");
            $ctrl->creation();
            break;
                    case 'article_enregistrer':
            load_controller_by_basename('ArticlesControleur.php');
            $ctrl = new_if_exists(['ArticlesControleur','ArticleControleur']);
            if (!$ctrl) throw new Exception("Classe ArticlesControleur (ou ArticleControleur) introuvable.");
            $ctrl->enregistrer();
            break;



        case 'actualites':
            load_controller_by_basename('ActualitesControleur.php');
            (new ActualitesControleur())->index();
            break;

        case 'actualite-detail':
            load_controller_by_basename('ActualitesControleur.php');
            if (!isset($_GET['id'])) {
                render_error_view(404, 'Actualité introuvable', "L'actualité demandée est introuvable.");
                break;
            }
            (new ActualitesControleur())->detail((int)$_GET['id']);
            break;

        case 'projets':
            load_controller_by_basename('ProjetsControleur.php');
            (new ProjetsControleur())->index();
            break;

        case 'projet':
            load_controller_by_basename('ProjetControleur.php');
            if (!isset($_GET['id'])) {
                render_error_view(404, 'Projet introuvable', 'Le projet demandé est introuvable.');
                break;
            }
            (new ProjetControleur())->detail($_GET['id']);
            break;
case 'projet_creation':
    load_controller_by_basename('ProjetsControleur.php');
    (new ProjetsControleur())->creation();
    break;

case 'projet_enregistrer':
    load_controller_by_basename('ProjetsControleur.php');
    (new ProjetsControleur())->enregistrer();
    break;

        case 'webtv':
            load_controller_by_basename('VideoControleur.php');
            (new VideoControleur())->index();
            break;
        
        case 'webtv_enregistrer':
            load_controller_by_basename('VideoControleur.php');
            (new VideoControleur())->enregistrer();
            break;

        case 'contact':
            load_controller_by_basename('ContactControleur.php');
            (new ContactControleur())->index();
            break;
            
   case 'profil':
            verifierConnexionUtilisateur();
            load_controller_by_basename('ProfilControleur.php');
            $ctrl = new ProfilControleur();
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $ctrl->mettreAJourPhoto();
            } else {
                $ctrl->index();
            }
            break;
        
        case 'favoris':
            load_controller_by_basename('FavorisControleur.php');
            (new FavorisControleur())->index();
            break;

        case 'favoris-toggle':
            load_controller_by_basename('FavorisControleur.php');
            (new FavorisControleur())->toggle();
            break;

        case 'favoris-clear':
            load_controller_by_basename('FavorisControleur.php');
            (new FavorisControleur())->supprimerTous();
            break;
        case 'login':
            load_controller_by_basename('AuthentificationControleur.php');
            (new AuthentificationControleur())->connexion();
            break;

        case 'inscription':
            load_controller_by_basename('AuthentificationControleur.php');
            (new AuthentificationControleur())->inscription();
            break;

        case 'logout':
            load_controller_by_basename('AuthentificationControleur.php');
            (new AuthentificationControleur())->deconnexion();
            break;

        case 'mdp-oublie':
            load_controller_by_basename('AuthentificationControleur.php');
            (new AuthentificationControleur())->mdpOublie();
            break;


        case 'admin':
            verifierAccesAdmin();
            load_controller_by_basename('AdminDashboardControleur.php');
            (new AdminDashboardControleur())->index();
            break;

        case 'admin-projets':
            verifierAccesAdmin();
            load_controller_by_basename('AdminProjetsControleur.php');
            (new AdminProjetsControleur())->gererRequete($_POST['action'] ?? null);
            break;

        case 'admin-articles':
            verifierAccesAdmin();
            load_controller_by_basename('AdminArticlesControleur.php');
            (new AdminArticlesControleur())->gererRequete($_POST['action'] ?? null);
            break;

        case 'admin-actualites':
            verifierAccesAdmin();
            load_controller_by_basename('AdminActualitesControleur.php');
            (new AdminActualitesControleur())->gererRequete($_POST['action'] ?? null);
            break;

        case 'admin-webtv':
            verifierAccesAdmin();
            load_controller_by_basename('AdminVideoControleur.php');
            (new AdminVideoControleur())->gererRequete($_POST['action'] ?? null);
            break;

        case 'admin-comments':
            verifierAccesAdmin();
            load_controller_by_basename('AdminCommentairesControleur.php');
            (new AdminCommentairesControleur())->gererRequete($_POST['action'] ?? null);
            break;


        case 'admin-utilisateurs':
            verifierAccesAdmin();
            load_controller_by_basename('AdminUtilisateursControleur.php');
            (new AdminUtilisateursControleur())->gererRequete($_POST['action'] ?? null);
            break;


        case 'utilisateurs-admin':
            header('Location: ?page=admin-utilisateurs');
            exit;

        case 'admin-contact':
            verifierAccesAdmin();
            load_controller_by_basename('AdminContactControleur.php');
            (new AdminContactControleur())->gererRequete($_POST['action'] ?? null);
            break;

        case 'admin-corbeille':
            verifierAccesAdmin();
            load_controller_by_basename('AdminCorbeilleControleur.php');
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
            load_controller_by_basename('AdminCacheControleur.php');
            $ctrl = new AdminCacheControleur();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $ctrl->gererAction();
            } else {
                $ctrl->index();
            }
            break;


        default:
            render_error_view(
                404,
                'Page introuvable',
                'La page demandée est introuvable.'
            );
            break;
    }

} catch (Throwable $e) {
    error_log('Router error: ' . $e->getMessage());
    error_log($e->getTraceAsString());
    render_error_view(
        500,
        'Erreur serveur',
        'Une erreur interne est survenue. Veuillez réessayer plus tard.'
    );
}


