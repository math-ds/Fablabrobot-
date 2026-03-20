<?php

require_once __DIR__ . '/../modèles/AdminProjetsModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';
require_once __DIR__ . '/../helpers/Pagination.php';

class AdminProjetsControleur
{
    private const IMAGE_ERREUR = '__IMAGE_ERREUR__';

    private AdminProjetsModele $modele;
    private $cache;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $this->modele = new AdminProjetsModele();
        $this->cache = GestionnaireCache::obtenirInstance();
        CsrfHelper::init();
    }

    private function normaliserCategorieProjet(string $categorie): ?string
    {
        $value = trim($categorie);
        if ($value === '') {
            return null;
        }

        $key = function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
        $key = trim((string)(preg_replace('/\s+/', ' ', $key) ?? $key));

        $map = [
            'robotique' => 'Robotique',
            'drone' => 'Drone / FPV',
            'drone / fpv' => 'Drone / FPV',
            'impression 3d' => 'Impression 3D',
            'electronique' => 'Electronique',
            'électronique' => 'Electronique',
            'programmation' => 'Programmation',
            'mecanique' => 'Mecanique',
            'mécanique' => 'Mecanique',
            'autre' => 'Autre',
            'autres' => 'Autre',
        ];

        return $map[$key] ?? null;
    }
    private function estUrlExterne(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    private function construireNomFichierProjet(string $nomOriginal, string $extension): string
    {
        $base = pathinfo($nomOriginal, PATHINFO_FILENAME);
        $base = strtolower(trim((string)$base));
        $base = preg_replace('/[^a-z0-9_-]+/', '-', $base) ?? '';
        $base = trim($base, '-_');
        if ($base === '') {
            $base = 'image';
        }

        try {
            $suffixe = substr(bin2hex(random_bytes(4)), 0, 8);
        } catch (Throwable $e) {
            $suffixe = substr(md5((string)microtime(true)), 0, 8);
        }

        return 'projet_' . $base . '_' . date('Ymd_His') . '_' . $suffixe . '.' . $extension;
    }

    private function cheminImageLocaleProjet(?string $imageUrl): ?string
    {
        $value = trim((string)$imageUrl);
        if ($value === '' || $this->estUrlExterne($value)) {
            return null;
        }

        if (str_starts_with($value, 'images/projets/')) {
            $value = substr($value, strlen('images/projets/'));
        }

        $value = ltrim($value, '/\\');
        if ($value === '') {
            return null;
        }

        return __DIR__ . '/../../public/images/projets/' . $value;
    }

    private function supprimerImageLocaleProjet(?string $imageUrl): void
    {
        $chemin = $this->cheminImageLocaleProjet($imageUrl);
        if ($chemin !== null && is_file($chemin)) {
            @unlink($chemin);
        }
    }

    private function gererImage(?array $projetExistant = null): ?string
    {
        $image_url = trim($_POST['image_url'] ?? '');
        $fileImage = $_FILES['image'] ?? null;
        $uploadDemande = is_array($fileImage)
            && (
                !empty($fileImage['name'])
                || (int)($fileImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
            );

        
        if ($uploadDemande) {
            
            $validation = ValidationHelper::validerFichierImage($fileImage, 5120); 

            if (!$validation['valid']) {
                $_SESSION['message'] = $validation['error'];
                $_SESSION['message_type'] = 'danger';
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur((string)$validation['error'], 422);
                }
                return self::IMAGE_ERREUR;
            }

            $uploadDir = __DIR__ . '/../../public/images/projets/';

            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            
            $extension = $validation['extension'];
            $filename = $this->construireNomFichierProjet((string)($fileImage['name'] ?? ''), (string)$extension);
            $target = $uploadDir . $filename;

            if (move_uploaded_file($fileImage['tmp_name'], $target)) {
                if ($projetExistant !== null) {
                    $ancienneImage = (string)($projetExistant['image_url'] ?? '');
                    if ($ancienneImage !== '' && $ancienneImage !== $filename) {
                        $this->supprimerImageLocaleProjet($ancienneImage);
                    }
                }
                return $filename;
            } else {
                $_SESSION['message'] = "Erreur lors de l'enregistrement de l'image.";
                $_SESSION['message_type'] = 'danger';
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur("Erreur lors de l'enregistrement de l'image.", 500);
                }
                return self::IMAGE_ERREUR;
            }
        }

        
        if (!empty($image_url)) {
            $validation = ValidationHelper::validerUrl($image_url, false);
            if (!$validation['valid']) {
                $_SESSION['message'] = $validation['error'];
                $_SESSION['message_type'] = 'danger';
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreur((string)$validation['error'], 422);
                }
                return self::IMAGE_ERREUR;
            }

            if ($projetExistant !== null) {
                $ancienneImage = (string)($projetExistant['image_url'] ?? '');
                if ($ancienneImage !== '' && !$this->estUrlExterne($ancienneImage) && $ancienneImage !== $validation['value']) {
                    $this->supprimerImageLocaleProjet($ancienneImage);
                }
            }
            return $validation['value'];
        }

        return null;
    }

    public function gererRequete(?string $action = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $csrfValid = CsrfHelper::verifierJetonPost() || CsrfHelper::verifierJetonEntete();

            if (!$csrfValid) {
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreurAvecDonnees("Token de securite invalide", 403, [
                        'new_token' => CsrfHelper::genererJeton()
                    ]);
                }
                $_SESSION['message'] = "Token de securite invalide.";
                $_SESSION['message_type'] = "danger";
                header("Location: ?page=admin-projets");
                exit;
            }

            $formAction = $_POST['action'] ?? null;

            try {
                if ($formAction === 'create') {
                    
                    $validations = [
                        'title' => ValidationHelper::validerChaine($_POST['title'] ?? '', 3, 200, 'Titre'),
                        'description' => ValidationHelper::validerChaine($_POST['description'] ?? '', 10, 500, 'Description')
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
                    if ($image_url === self::IMAGE_ERREUR) {
                        header("Location: ?page=admin-projets");
                        exit;
                    }

                    $categorie = $this->normaliserCategorieProjet((string)($_POST['categorie'] ?? ''));
                    $auteur_id = isset($_SESSION['utilisateur_id']) ? (int)$_SESSION['utilisateur_id'] : null;

                    $id = $this->modele->creer([
                        'title'                => $validations['title']['value'],
                        'description'          => $validations['description']['value'],
                        'auteur_id'            => $auteur_id,
                        'description_detailed' => trim($_POST['description_detailed'] ?? ''),
                        'technologies'         => trim($_POST['technologies'] ?? ''),
                        'categorie'            => $categorie,
                        'image_url'            => $image_url,
                        'features'             => trim($_POST['features'] ?? ''),
                        'challenges'           => trim($_POST['challenges'] ?? '')
                    ]);

                    
                    $this->cache->supprimer('liste_projets');

                    if (JsonResponseHelper::estAjax()) {
                        $projet = $this->modele->trouver($id);
                        JsonResponseHelper::succes(['project' => $projet], 'Projet cree avec succes');
                    }

                    $_SESSION['message'] = "Projet cree avec succes.";
                    $_SESSION['message_type'] = 'success';

                } elseif ($formAction === 'update') {
                    $id = (int)($_POST['project_id'] ?? 0);

                    
                    $validations = [
                        'title' => ValidationHelper::validerChaine($_POST['title'] ?? '', 3, 200, 'Titre'),
                        'description' => ValidationHelper::validerChaine($_POST['description'] ?? '', 10, 500, 'Description')
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

                    $projet = $this->modele->trouver($id);
                    $image_url = $this->gererImage($projet);
                    if ($image_url === self::IMAGE_ERREUR) {
                        header("Location: ?page=admin-projets");
                        exit;
                    }

                    if ($image_url === null) {
                        $image_url = $projet['image_url'] ?? null;
                    }

                    $categorie = $this->normaliserCategorieProjet((string)($_POST['categorie'] ?? ''));

                    $this->modele->mettreAJour($id, [
                        'title'                => $validations['title']['value'],
                        'description'          => $validations['description']['value'],
                        'description_detailed' => trim($_POST['description_detailed'] ?? ''),
                        'technologies'         => trim($_POST['technologies'] ?? ''),
                        'categorie'            => $categorie,
                        'image_url'            => $image_url,
                        'features'             => trim($_POST['features'] ?? ''),
                        'challenges'           => trim($_POST['challenges'] ?? '')
                    ]);

                    
                    $this->cache->supprimer('projet_' . $id);
                    $this->cache->supprimer('liste_projets');

                    if (JsonResponseHelper::estAjax()) {
                        $projet = $this->modele->trouver($id);
                        JsonResponseHelper::succes(['project' => $projet], 'Projet mis a jour avec succes');
                    }

                    $_SESSION['message'] = "Projet mis a jour.";
                    $_SESSION['message_type'] = 'success';

                } elseif ($formAction === 'delete') {
                    $id = (int)($_POST['project_id'] ?? 0);
                    $this->modele->supprimer($id);

                    
                    $this->cache->supprimer('projet_' . $id);
                    $this->cache->supprimer('liste_projets');

                    if (JsonResponseHelper::estAjax()) {
                        JsonResponseHelper::succes(['id' => $id], 'Projet supprime avec succes');
                    }

                    $_SESSION['message'] = "Projet supprime.";
                    $_SESSION['message_type'] = 'success';
                }
            } catch (Throwable $e) {
                error_log('AdminProjetsControleur::gererRequete - ' . $e->getMessage());
                if (JsonResponseHelper::estAjax()) {
                    JsonResponseHelper::erreurServeur('Une erreur est survenue lors du traitement de la demande.');
                }
                $_SESSION['message'] = "Une erreur est survenue lors du traitement de la demande.";
                $_SESSION['message_type'] = 'danger';
            }

            header('Location: ?page=admin-projets');
            exit;
        }

        $this->index();
    }

    public function index(): void
    {
        $projectsTous = $this->modele->tousLesElements() ?? [];
        $recherche = trim((string)($_GET['q'] ?? ''));
        $rechercheNormalisee = mb_strtolower($recherche, 'UTF-8');

        $projectsFiltres = array_values(array_filter(
            $projectsTous,
            function (array $project) use ($rechercheNormalisee): bool {
                if ($rechercheNormalisee === '') {
                    return true;
                }

                $index = mb_strtolower(implode(' ', [
                    (string)($project['title'] ?? ''),
                    (string)($project['description'] ?? ''),
                    (string)($project['categorie'] ?? ''),
                    (string)($project['technologies'] ?? ''),
                    (string)($project['auteur_nom'] ?? ''),
                ]), 'UTF-8');

                return mb_strpos($index, $rechercheNormalisee, 0, 'UTF-8') !== false;
            }
        ));

        $total_projects = count($projectsTous);
        $totalFiltres = count($projectsFiltres);
        $pagination = new Pagination($totalFiltres, 10);
        $projects = array_slice($projectsFiltres, $pagination->offset(), $pagination->limit());
        include __DIR__ . '/../vues/admin/projets-admin.php';
    }
}
