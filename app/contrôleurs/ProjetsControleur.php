<?php

require_once __DIR__ . '/../modèles/ProjetModele.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';

class ProjetsControleur {

    public function index() {
        $modele = new ProjetModele();
        $cache = GestionnaireCache::obtenirInstance();

        // Cache : Liste des projets (15 minutes)
        $projects = $cache->memoriser('liste_projets', function() use ($modele) {
            return $modele->obtenirTousLesProjets();
        }, 900);

        require __DIR__ . '/../vues/projets/index.php';
    }

    public function creation(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $role = $_SESSION['utilisateur_role'] ?? '';
        if (!in_array($role, ['Admin', 'Éditeur'], true)) {
            header('Location: ?page=projets');
            exit;
        }

        include __DIR__ . '/../vues/projets/projet_creation.php';
    }

    public function enregistrer(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=projets');
            exit;
        }

        // Détecter si c'est une requête AJAX
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';


        $role = $_SESSION['utilisateur_role'] ?? '';
        if (!in_array($role, ['Admin', 'Éditeur'], true)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Accès refusé"]);
                exit;
            }
            $_SESSION['message'] = "❌ Accès refusé.";
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=projets');
            exit;
        }

        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        require_once __DIR__ . '/../helpers/ValidationHelper.php';

        // Vérification CSRF
        if ($isAjax) {
            $csrfValid = CsrfHelper::verifierJetonPost() || CsrfHelper::verifierJetonEntete();
            if (!$csrfValid) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Token de sécurité invalide"]);
                exit;
            }
        }


        require_once __DIR__ . '/../../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();


        $title                = trim($_POST['titre'] ?? '');
        $auteur               = trim($_POST['auteur'] ?? $_SESSION['utilisateur_nom'] ?? 'Inconnu');
        $description          = trim($_POST['description'] ?? '');
        $description_detailed = trim($_POST['description_detailed'] ?? '');
        $technologies         = trim($_POST['technologies'] ?? '');
        $features             = trim($_POST['features'] ?? '');
        $challenges           = trim($_POST['challenges'] ?? '');
        $image_url            = trim($_POST['image_url'] ?? '');

        // Validation du titre
        if (empty($title) || strlen($title) < 3 || strlen($title) > 200) {
            $errorMsg = "Le titre doit contenir entre 3 et 200 caractères";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $_SESSION['message'] = "❌ " . $errorMsg;
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=projets');
            exit;
        }

        // Validation de la description
        if (empty($description) || strlen($description) < 10 || strlen($description) > 500) {
            $errorMsg = "La description doit contenir entre 10 et 500 caractères";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $_SESSION['message'] = "❌ " . $errorMsg;
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=projets');
            exit;
        }

        // Validation de l'auteur
        if (empty($auteur) || strlen($auteur) < 2 || strlen($auteur) > 100) {
            $errorMsg = "L'auteur doit contenir entre 2 et 100 caractères";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $_SESSION['message'] = "❌ " . $errorMsg;
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=projets');
            exit;
        }


        if (empty($image_url)) {
            $image_url = null;
        }


        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Validation du fichier
            $validation = ValidationHelper::validerFichierImage($_FILES['image'], 5120); // 5 Mo max

            if (!$validation['valid']) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $validation['error']]);
                    exit;
                }
                $_SESSION['message'] = $validation['error'];
                $_SESSION['message_type'] = 'danger';
                header('Location: ?page=projets');
                exit;
            }

            $uploadDir = __DIR__ . '/../../public/images/projets/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $extension = $validation['extension'];
            $filename = uniqid('projet_', true) . '.' . $extension;
            $target = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_url = $filename;
            }
        }


        try {
            $stmt = $conn->prepare("
                INSERT INTO projects (title, auteur, description, description_detailed, technologies, image_url, features, challenges)
                VALUES (:title, :auteur, :description, :description_detailed, :technologies, :image_url, :features, :challenges)
            ");

            $stmt->execute([
                ':title'                => $title,
                ':auteur'               => $auteur,
                ':description'          => $description,
                ':description_detailed' => $description_detailed ?: null,
                ':technologies'         => $technologies ?: null,
                ':image_url'            => $image_url,
                ':features'             => $features ?: null,
                ':challenges'           => $challenges ?: null,
            ]);

            $projetId = $conn->lastInsertId();

            // Invalider le cache des projets
            $cache = GestionnaireCache::obtenirInstance();
            $cache->supprimer('liste_projets');

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => "Projet créé avec succès !",
                    'project_id' => $projetId
                ]);
                exit;
            }

            $_SESSION['message'] = "✅ Projet ajouté avec succès !";
            $_SESSION['message_type'] = 'success';
        } catch (Exception $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Erreur : " . $e->getMessage()]);
                exit;
            }
            $_SESSION['message'] = "❌ Erreur : " . $e->getMessage();
            $_SESSION['message_type'] = 'danger';
        }

        header('Location: ?page=projets');
        exit;
    }
}