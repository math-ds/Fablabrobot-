<?php

require_once __DIR__ . '/../modèles/AdminProjetsModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';

class AdminProjetsControleur
{
    private AdminProjetsModele $modele;
    private $cache;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['utilisateur_role']) || strtolower($_SESSION['utilisateur_role']) !== 'admin') {
            header('Location: ?page=login');
            exit;
        }

        $this->modele = new AdminProjetsModele();
        $this->cache = GestionnaireCache::obtenirInstance();
        CsrfHelper::init();
    }

    private function gererImage(): ?string
    {
        $image_url = trim($_POST['image_url'] ?? '');

        // 1. Gestion de l'upload de fichier
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            // Validation robuste avec ValidationHelper (MIME + contenu + extension + taille)
            $validation = ValidationHelper::validerFichierImage($_FILES['image'], 5120); // 5 Mo max

            if (!$validation['valid']) {
                $_SESSION['message'] = $validation['error'];
                $_SESSION['message_type'] = 'danger';
                return null;
            }

            $uploadDir = __DIR__ . '/../../public/images/projets/';

            // Créer le dossier avec permissions sécurisées (0755 au lieu de 0777)
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Nom de fichier sécurisé avec extension validée
            $extension = $validation['extension'];
            $filename = uniqid('projet_', true) . '.' . $extension;
            $target = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                return $filename;
            } else {
                $_SESSION['message'] = "Erreur lors de l'enregistrement de l'image.";
                $_SESSION['message_type'] = 'danger';
                return null;
            }
        }

        // 2. Gestion de l'URL externe
        if (!empty($image_url)) {
            $validation = ValidationHelper::validerUrl($image_url, false);
            if (!$validation['valid']) {
                $_SESSION['message'] = $validation['error'];
                $_SESSION['message_type'] = 'danger';
                return null;
            }
            return $validation['value'];
        }

        return null;
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
                header("Location: ?page=admin-projets");
                exit;
            }

            $formAction = $_POST['action'] ?? null;

            try {
                if ($formAction === 'create') {
                    // Validation des données
                    $validations = [
                        'title' => ValidationHelper::validerChaine($_POST['title'] ?? '', 3, 200, 'Titre'),
                        'description' => ValidationHelper::validerChaine($_POST['description'] ?? '', 10, 500, 'Description'),
                        'auteur' => ValidationHelper::validerChaine($_POST['auteur'] ?? '', 2, 100, 'Auteur')
                    ];

                    foreach ($validations as $field => $result) {
                        if (!$result['valid']) {
                            if (JsonResponseHelper::estAjax()) {
                                JsonResponseHelper::erreurValidation([$field => $result['error']]);
                            }
                            $_SESSION['message'] = $result['error'];
                            $_SESSION['message_type'] = 'danger';
                            header("Location: ?page=admin-projets");
                            exit;
                        }
                    }

                    $image_url = $this->gererImage();

                    $id = $this->modele->creer([
                        'title'                => $validations['title']['value'],
                        'description'          => $validations['description']['value'],
                        'auteur'               => $validations['auteur']['value'],
                        'description_detailed' => trim($_POST['description_detailed'] ?? ''),
                        'technologies'         => trim($_POST['technologies'] ?? ''),
                        'image_url'            => $image_url,
                        'features'             => trim($_POST['features'] ?? ''),
                        'challenges'           => trim($_POST['challenges'] ?? '')
                    ]);

                    // Invalider le cache des projets
                    $this->cache->supprimer('liste_projets');

                    if (JsonResponseHelper::estAjax()) {
                        $projet = $this->modele->trouver($id);
                        JsonResponseHelper::succes(['project' => $projet], 'Projet créé avec succès');
                    }

                    $_SESSION['message'] = "Projet créé avec succès.";
                    $_SESSION['message_type'] = 'success';

                } elseif ($formAction === 'update') {
                    $id = (int)($_POST['project_id'] ?? 0);

                    // Validation des données
                    $validations = [
                        'title' => ValidationHelper::validerChaine($_POST['title'] ?? '', 3, 200, 'Titre'),
                        'description' => ValidationHelper::validerChaine($_POST['description'] ?? '', 10, 500, 'Description'),
                        'auteur' => ValidationHelper::validerChaine($_POST['auteur'] ?? '', 2, 100, 'Auteur')
                    ];

                    foreach ($validations as $field => $result) {
                        if (!$result['valid']) {
                            if (JsonResponseHelper::estAjax()) {
                                JsonResponseHelper::erreurValidation([$field => $result['error']]);
                            }
                            $_SESSION['message'] = $result['error'];
                            $_SESSION['message_type'] = 'danger';
                            header("Location: ?page=admin-projets");
                            exit;
                        }
                    }

                    $image_url = $this->gererImage();

                    if ($image_url === null) {
                        $projet = $this->modele->trouver($id);
                        $image_url = $projet['image_url'] ?? null;
                    }

                    $this->modele->mettreAJour($id, [
                        'title'                => $validations['title']['value'],
                        'description'          => $validations['description']['value'],
                        'auteur'               => $validations['auteur']['value'],
                        'description_detailed' => trim($_POST['description_detailed'] ?? ''),
                        'technologies'         => trim($_POST['technologies'] ?? ''),
                        'image_url'            => $image_url,
                        'features'             => trim($_POST['features'] ?? ''),
                        'challenges'           => trim($_POST['challenges'] ?? '')
                    ]);

                    // Invalider le cache de ce projet et de la liste
                    $this->cache->supprimer('projet_' . $id);
                    $this->cache->supprimer('liste_projets');

                    if (JsonResponseHelper::estAjax()) {
                        $projet = $this->modele->trouver($id);
                        JsonResponseHelper::succes(['project' => $projet], 'Projet mis à jour avec succès');
                    }

                    $_SESSION['message'] = "Projet mis à jour.";
                    $_SESSION['message_type'] = 'success';

                } elseif ($formAction === 'delete') {
                    $id = (int)($_POST['project_id'] ?? 0);
                    $this->modele->supprimer($id);

                    // Invalider le cache de ce projet et de la liste
                    $this->cache->supprimer('projet_' . $id);
                    $this->cache->supprimer('liste_projets');

                    if (JsonResponseHelper::estAjax()) {
                        JsonResponseHelper::succes(['id' => $id], 'Projet supprimé avec succès');
                    }

                    $_SESSION['message'] = "Projet supprimé.";
                    $_SESSION['message_type'] = 'success';
                }
            } catch (Throwable $e) {
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur($e->getMessage(), 500);
                }
                $_SESSION['message'] = "Erreur: " . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
            }

            header('Location: ?page=admin-projets');
            exit;
        }

        $this->index();
    }

    public function index(): void
    {
        $projects = $this->modele->tousLesElements() ?? [];
        $total_projects = count($projects);
        include __DIR__ . '/../vues/admin/projets-admin.php';
    }
}