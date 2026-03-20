<?php

require_once __DIR__ . '/../modèles/ProjetModele.php';
require_once __DIR__ . '/../modèles/FavorisModele.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';
require_once __DIR__ . '/../helpers/Pagination.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';
require_once __DIR__ . '/../../config/categories.php';

class ProjetsControleur
{
    private function utilisateurPeutCreer(): bool
    {
        return RoleHelper::peutCreerContenu($_SESSION['utilisateur_role'] ?? '');
    }

    
    private function categoriesProjetDisponibles(): array
    {
        return CategorieConfig::projetsDisponibles();
    }

    private function normaliserCategorieProjet(string $categorie): ?string
    {
        return CategorieConfig::normaliserProjet($categorie);
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

    private function marquerProjetsFavoris(array $projects): array
    {
        $utilisateurId = (int)($_SESSION['utilisateur_id'] ?? 0);
        if ($utilisateurId <= 0 || empty($projects)) {
            foreach ($projects as &$project) {
                $project['is_favori'] = false;
            }
            unset($project);
            return $projects;
        }

        $modeleFavoris = new FavorisModele();
        $idsFavoris = $modeleFavoris->obtenirIdsFavorisUtilisateurEtType($utilisateurId, 'projet');
        $lookup = array_fill_keys($idsFavoris, true);

        foreach ($projects as &$project) {
            $projectId = (int)($project['id'] ?? 0);
            $project['is_favori'] = $projectId > 0 && isset($lookup[$projectId]);
        }
        unset($project);

        return $projects;
    }

    public function index(): void
    {
        $modele = new ProjetModele();
        $cache = GestionnaireCache::obtenirInstance();
        $q = trim((string)($_GET['q'] ?? ''));
        $categorieDemandee = trim((string)($_GET['categorie'] ?? ''));
        $categorieFiltre = $this->normaliserCategorieProjet($categorieDemandee);

        if ($categorieFiltre !== null) {
            $listeProjets = $cache->memoriser('liste_projets', function() use ($modele) {
                return $modele->obtenirTousLesProjets();
            }, 900);

            $projetsFiltres = $this->filtrerProjets($listeProjets, $q, $categorieFiltre);
            $total = count($projetsFiltres);
            $pagination = new Pagination($total, 9);
            $projects = array_slice($projetsFiltres, $pagination->offset(), $pagination->limit());
        } else {
            $total = $modele->compterProjets($q);
            $pagination = new Pagination($total, 9);
            $projects = $modele->obtenirProjetsPagines($pagination->limit(), $pagination->offset(), $q);
        }
        $projects = $this->marquerProjetsFavoris($projects);

        if ($this->estRequeteAjax()) {
            $this->repondreIndexAjax($projects, $q, $categorieFiltre ?? 'all', $pagination);
            return;
        }

        $categoriesProjet = $this->categoriesProjetDisponibles();
        $categorieCourante = $categorieFiltre ?? 'all';
        $utilisateurConnecte = !empty($_SESSION['utilisateur_id']);
        $peutCreerProjet = $this->utilisateurPeutCreer();
        require __DIR__ . '/../vues/projets/index.php';
    }

    public function creation(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!$this->utilisateurPeutCreer()) {
            header('Location: ?page=projets');
            exit;
        }

        $categoriesProjet = $this->categoriesProjetDisponibles();
        include __DIR__ . '/../vues/projets/projet_creation.php';
    }

    public function enregistrer(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=projets');
            exit;
        }

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if (!$this->utilisateurPeutCreer()) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Acces refuse']);
                exit;
            }
            $_SESSION['message'] = 'Acces refuse.';
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=projets');
            exit;
        }

        require_once __DIR__ . '/../helpers/CsrfHelper.php';
        require_once __DIR__ . '/../helpers/ValidationHelper.php';

        $csrfValid = CsrfHelper::verifierJetonPost() || CsrfHelper::verifierJetonEntete();
        if (!$csrfValid) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide']);
                exit;
            }
            $_SESSION['message'] = 'Token de sécurité invalide.';
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=projets');
            exit;
        }

        $modele = new ProjetModele();

        $title = trim((string)($_POST['titre'] ?? ''));
        $auteur_id = isset($_SESSION['utilisateur_id']) ? (int)$_SESSION['utilisateur_id'] : null;
        $description = trim((string)($_POST['description'] ?? ''));
        $descriptionDetailed = trim((string)($_POST['description_detailed'] ?? ''));
        $technologies = trim((string)($_POST['technologies'] ?? ''));
        $features = trim((string)($_POST['features'] ?? ''));
        $challenges = trim((string)($_POST['challenges'] ?? ''));
        $imageUrl = trim((string)($_POST['image_url'] ?? ''));
        $categorie = $this->normaliserCategorieProjet((string)($_POST['categorie'] ?? ''));

        if ($title === '' || strlen($title) < 3 || strlen($title) > 200) {
            $errorMsg = 'Le titre doit contenir entre 3 et 200 caracteres';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $_SESSION['message'] = $errorMsg;
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=projets');
            exit;
        }

        if ($description === '' || strlen($description) < 10 || strlen($description) > 500) {
            $errorMsg = 'La description doit contenir entre 10 et 500 caracteres';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                exit;
            }
            $_SESSION['message'] = $errorMsg;
            $_SESSION['message_type'] = 'danger';
            header('Location: ?page=projets');
            exit;
        }

        if ($imageUrl === '') {
            $imageUrl = null;
        }

        $fileImage = $_FILES['image'] ?? null;
        $uploadDemande = is_array($fileImage)
            && (
                !empty($fileImage['name'])
                || (int)($fileImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
            );

        if ($uploadDemande) {
            $validation = ValidationHelper::validerFichierImage($fileImage, 5120);
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
            $filename = $this->construireNomFichierProjet((string)($fileImage['name'] ?? ''), (string)$extension);
            $target = $uploadDir . $filename;

            if (!move_uploaded_file((string)$fileImage['tmp_name'], $target)) {
                $errorMsg = "Erreur lors de l'enregistrement de l'image.";
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $errorMsg]);
                    exit;
                }
                $_SESSION['message'] = $errorMsg;
                $_SESSION['message_type'] = 'danger';
                header('Location: ?page=projets');
                exit;
            }

            $imageUrl = $filename;
        }

        try {
            $projetId = $modele->creerProjet([
                'title' => $title,
                'auteur_id' => $auteur_id,
                'description' => $description,
                'description_detailed' => $descriptionDetailed !== '' ? $descriptionDetailed : null,
                'technologies' => $technologies !== '' ? $technologies : null,
                'categorie' => $categorie,
                'image_url' => $imageUrl,
                'features' => $features !== '' ? $features : null,
                'challenges' => $challenges !== '' ? $challenges : null,
            ]);
            $cache = GestionnaireCache::obtenirInstance();
            $cache->supprimer('liste_projets');

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Projet cree avec succes !',
                    'project_id' => $projetId,
                ]);
                exit;
            }

            $_SESSION['message'] = 'Projet ajoute avec succes !';
            $_SESSION['message_type'] = 'success';
        } catch (Exception $e) {
            error_log('ProjetsControleur::enregistrer - ' . $e->getMessage());
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de la création du projet.']);
                exit;
            }
            $_SESSION['message'] = 'Une erreur est survenue lors de la création du projet.';
            $_SESSION['message_type'] = 'danger';
        }

        header('Location: ?page=projets');
        exit;
    }

    private function estRequeteAjax(): bool
    {
        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            return true;
        }

        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function determinerCategorieProjet(array $project): string
    {
        $categorieBd = $this->normaliserCategorieProjet((string)($project['categorie'] ?? ''));
        if ($categorieBd !== null) {
            return $categorieBd;
        }

        $txt = strtolower(
            trim((string)($project['title'] ?? '')) . ' ' .
            trim((string)($project['description'] ?? '')) . ' ' .
            trim((string)($project['technologies'] ?? ''))
        );

        if (str_contains($txt, 'drone') || str_contains($txt, 'fpv') || str_contains($txt, 'quad')) {
            return 'Drone / FPV';
        }
        if (str_contains($txt, 'impression') || str_contains($txt, '3d') || str_contains($txt, 'print')) {
            return 'Impression 3D';
        }
        if (str_contains($txt, 'electro') || str_contains($txt, 'electronique') || str_contains($txt, 'capteur') || str_contains($txt, 'circuit')) {
            return 'Electronique';
        }
        if (str_contains($txt, 'code') || str_contains($txt, 'dev') || str_contains($txt, 'python') || str_contains($txt, 'javascript') || str_contains($txt, 'programm')) {
            return 'Programmation';
        }
        if (str_contains($txt, 'mecanique') || str_contains($txt, 'chassis') || str_contains($txt, 'engrenage')) {
            return 'Mecanique';
        }
        if (str_contains($txt, 'robot') || str_contains($txt, 'moteur') || str_contains($txt, 'arduino') || str_contains($txt, 'servo')) {
            return 'Robotique';
        }

        return 'Autre';
    }

    private function filtrerProjets(array $projects, string $q, string $categorie): array
    {
        $qLower = function_exists('mb_strtolower')
            ? mb_strtolower($q, 'UTF-8')
            : strtolower($q);
        $categorieFiltre = $this->normaliserCategorieProjet($categorie);

        if ($qLower === '' && $categorieFiltre === null) {
            return $projects;
        }

        return array_values(array_filter($projects, function(array $project) use ($qLower, $categorieFiltre): bool {
            $categorieProjet = $this->determinerCategorieProjet($project);
            $stack = trim((string)($project['title'] ?? '')) . ' '
                . trim((string)($project['description'] ?? '')) . ' '
                . trim((string)($project['technologies'] ?? '')) . ' '
                . trim((string)($project['auteur_nom'] ?? ''));
            $stackLower = function_exists('mb_strtolower')
                ? mb_strtolower($stack, 'UTF-8')
                : strtolower($stack);

            $matchTexte = $qLower === '' || str_contains($stackLower, $qLower);
            $matchCategorie = $categorieFiltre === null || $categorieFiltre === $categorieProjet;
            return $matchTexte && $matchCategorie;
        }));
    }

    private function repondreIndexAjax(array $projects, string $q, string $categorie, ?Pagination $pagination = null): void
    {
        $payloadProjects = array_map(function(array $project): array {
            return [
                'id' => (int)($project['id'] ?? 0),
                'title' => (string)($project['title'] ?? ''),
                'description' => (string)($project['description'] ?? ''),
                'technologies' => (string)($project['technologies'] ?? ''),
                'image_url' => (string)($project['image_url'] ?? ''),
                'categorie' => $this->determinerCategorieProjet($project),
                'is_favori' => (bool)($project['is_favori'] ?? false),
            ];
        }, $projects);

        $paginationData = null;
        $paginationHtml = '';
        if ($pagination !== null) {
            $paginationData = [
                'page_courante' => $pagination->pageCourante(),
                'total_pages' => $pagination->totalPages(),
                'total' => $pagination->total(),
                'par_page' => $pagination->limit(),
            ];
            $paginationHtml = $pagination->rendrePaginationComplete();
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'data' => [
                'projects' => $payloadProjects,
                'q' => $q,
                'categorie' => $categorie,
                'count' => count($payloadProjects),
                'categories' => $this->categoriesProjetDisponibles(),
                'can_favorite' => (int)($_SESSION['utilisateur_id'] ?? 0) > 0,
                'pagination' => $paginationData,
                'pagination_html' => $paginationHtml,
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
