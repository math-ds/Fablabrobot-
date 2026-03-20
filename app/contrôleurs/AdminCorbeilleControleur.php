<?php
require_once __DIR__ . '/../modèles/AdminArticlesModele.php';
require_once __DIR__ . '/../modèles/AdminProjetsModele.php';
require_once __DIR__ . '/../modèles/AdminVideoModele.php';
require_once __DIR__ . '/../modèles/AdminUtilisateursModele.php';
require_once __DIR__ . '/../modèles/AdminContactModele.php';
require_once __DIR__ . '/../modèles/ActualitesModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';
require_once __DIR__ . '/../helpers/GestionnaireCache.php';
require_once __DIR__ . '/../helpers/Pagination.php';
require_once __DIR__ . '/../../config/database.php';

class AdminCorbeilleControleur
{
    private AdminArticlesModele $articlesModele;
    private AdminProjetsModele $projetsModele;
    private AdminVideoModele $videoModele;
    private AdminUtilisateursModele $utilisateursModele;
    private AdminContactModele $contactModele;
    private ActualitesModele $actualitesModele;
    private $cache;
    private array $typesCorbeilleAutorises = ['tous', 'article', 'actualite', 'projet', 'video', 'utilisateur', 'message'];

    public function __construct()
    {
        $this->articlesModele = new AdminArticlesModele();
        $this->projetsModele = new AdminProjetsModele();
        $this->videoModele = new AdminVideoModele();
        $this->utilisateursModele = new AdminUtilisateursModele();
        $this->contactModele = new AdminContactModele();
        $this->actualitesModele = new ActualitesModele((new Database())->getConnection());
        $this->cache = GestionnaireCache::obtenirInstance();
    }

    private function verifierCsrfAction(): bool
    {
        $tokenPost = $_POST['csrf_token'] ?? '';
        $tokenHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        return CsrfHelper::validerJeton($tokenPost) || CsrfHelper::validerJeton($tokenHeader);
    }

    private function invaliderCacheContenu(string $type, int $id): void
    {
        if ($id <= 0) {
            return;
        }

        switch ($type) {
            case 'article':
                $this->cache->supprimerParPrefixe('liste_articles_');
                $this->cache->supprimer('liste_articles');
                $this->cache->supprimer('article_' . $id);
                break;

            case 'actualite':
                $this->cache->supprimerParPrefixe('liste_actualites_');
                $this->cache->supprimer('actualites_sources');
                $this->cache->supprimer('actualite_' . $id);
                break;

            case 'projet':
                $this->cache->supprimer('liste_projets');
                $this->cache->supprimer('projet_' . $id);
                break;

            case 'video':
                $this->cache->supprimerParPrefixe('liste_videos_');
                $this->cache->supprimer('video_categories');
                $this->cache->supprimer('video_comments_' . $id);
                break;
        }
    }

    private function estUrlExterne(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    private function supprimerFichierImageLocal(string $type, ?string $imageUrl): void
    {
        $value = trim((string)$imageUrl);
        if ($value === '' || $this->estUrlExterne($value)) {
            return;
        }

        $prefixe = $type === 'article' ? 'images/articles/' : 'images/projets/';
        if (str_starts_with($value, $prefixe)) {
            $value = substr($value, strlen($prefixe));
        }

        $value = ltrim($value, '/\\');
        if ($value === '') {
            return;
        }

        $baseDir = $type === 'article'
            ? __DIR__ . '/../../public/images/articles/'
            : __DIR__ . '/../../public/images/projets/';

        $chemin = $baseDir . $value;
        if (is_file($chemin)) {
            @unlink($chemin);
        }
    }

    private function trouverElementSupprime(string $type, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $elements = match ($type) {
            'article' => $this->articlesModele->elementsSupprimes(),
            'actualite' => $this->actualitesModele->obtenirActualitesSupprimees(),
            'projet' => $this->projetsModele->elementsSupprimes(),
            default => [],
        };

        foreach ($elements as $element) {
            if ((int)($element['id'] ?? 0) === $id) {
                return $element;
            }
        }

        return null;
    }

    private function normaliserFiltreTypeCorbeille(?string $type): string
    {
        $normalise = strtolower(trim((string)$type));
        if ($normalise === '') {
            return 'tous';
        }

        return in_array($normalise, $this->typesCorbeilleAutorises, true) ? $normalise : 'tous';
    }

    private function normaliserTexteRecherche(string $texte): string
    {
        return mb_strtolower(trim($texte), 'UTF-8');
    }

    private function elementCorrespondRecherche(array $element, string $recherche): bool
    {
        if ($recherche === '') {
            return true;
        }

        $type = (string)($element['type'] ?? '');
        $champs = [(string)$type];

        switch ($type) {
            case 'article':
            case 'actualite':
            case 'projet':
            case 'video':
                $champs[] = (string)($element['titre'] ?? $element['title'] ?? '');
                $champs[] = (string)($element['description'] ?? '');
                $champs[] = (string)($element['categorie'] ?? '');
                $champs[] = (string)($element['source'] ?? '');
                break;
            case 'utilisateur':
                $champs[] = (string)($element['nom'] ?? '');
                $champs[] = (string)($element['email'] ?? '');
                $champs[] = (string)($element['role'] ?? '');
                break;
            case 'message':
                $champs[] = (string)($element['nom'] ?? '');
                $champs[] = (string)($element['sujet'] ?? '');
                $champs[] = (string)($element['email'] ?? '');
                $champs[] = (string)($element['message'] ?? '');
                break;
        }

        $indexRecherche = $this->normaliserTexteRecherche(implode(' ', $champs));
        return mb_strpos($indexRecherche, $recherche, 0, 'UTF-8') !== false;
    }

    
    public function afficherCorbeille(): void
    {
        
        $articles = $this->articlesModele->elementsSupprimes();
        $actualites = $this->actualitesModele->obtenirActualitesSupprimees();
        $projets = $this->projetsModele->elementsSupprimes();
        $videos = $this->videoModele->elementsSupprimes();
        $utilisateurs = $this->utilisateursModele->elementsSupprimes();
        $messages = $this->contactModele->elementsSupprimes();

        
        foreach ($articles as &$article) {
            $article['type'] = 'article';
        }
        foreach ($actualites as &$actualite) {
            $actualite['type'] = 'actualite';
        }
        foreach ($projets as &$projet) {
            $projet['type'] = 'projet';
        }
        foreach ($videos as &$video) {
            $video['type'] = 'video';
        }
        foreach ($utilisateurs as &$utilisateur) {
            $utilisateur['type'] = 'utilisateur';
        }
        foreach ($messages as &$message) {
            $message['type'] = 'message';
        }

        
        $elementsCorbeille = array_merge($articles, $actualites, $projets, $videos, $utilisateurs, $messages);

        
        usort($elementsCorbeille, function($a, $b) {
            return strtotime($b['deleted_at']) - strtotime($a['deleted_at']);
        });

        $totalCorbeille = count($elementsCorbeille);
        $compteursCorbeille = [
            'tous' => $totalCorbeille,
            'article' => count(array_filter($elementsCorbeille, fn($e) => ($e['type'] ?? '') === 'article')),
            'actualite' => count(array_filter($elementsCorbeille, fn($e) => ($e['type'] ?? '') === 'actualite')),
            'projet' => count(array_filter($elementsCorbeille, fn($e) => ($e['type'] ?? '') === 'projet')),
            'video' => count(array_filter($elementsCorbeille, fn($e) => ($e['type'] ?? '') === 'video')),
            'utilisateur' => count(array_filter($elementsCorbeille, fn($e) => ($e['type'] ?? '') === 'utilisateur')),
            'message' => count(array_filter($elementsCorbeille, fn($e) => ($e['type'] ?? '') === 'message')),
        ];

        $filtreCorbeilleActif = $this->normaliserFiltreTypeCorbeille($_GET['type'] ?? 'tous');
        $rechercheCorbeille = trim((string)($_GET['q'] ?? ''));
        $rechercheNormalisee = $this->normaliserTexteRecherche($rechercheCorbeille);

        $elementsApresRecherche = $rechercheNormalisee === ''
            ? $elementsCorbeille
            : array_values(array_filter(
                $elementsCorbeille,
                fn(array $element): bool => $this->elementCorrespondRecherche($element, $rechercheNormalisee)
            ));

        $compteursCorbeilleAffiches = [
            'tous' => count($elementsApresRecherche),
            'article' => count(array_filter($elementsApresRecherche, fn($e) => ($e['type'] ?? '') === 'article')),
            'actualite' => count(array_filter($elementsApresRecherche, fn($e) => ($e['type'] ?? '') === 'actualite')),
            'projet' => count(array_filter($elementsApresRecherche, fn($e) => ($e['type'] ?? '') === 'projet')),
            'video' => count(array_filter($elementsApresRecherche, fn($e) => ($e['type'] ?? '') === 'video')),
            'utilisateur' => count(array_filter($elementsApresRecherche, fn($e) => ($e['type'] ?? '') === 'utilisateur')),
            'message' => count(array_filter($elementsApresRecherche, fn($e) => ($e['type'] ?? '') === 'message')),
        ];

        $elementsFiltres = array_values(array_filter(
            $elementsCorbeille,
            function (array $element) use ($filtreCorbeilleActif, $rechercheNormalisee): bool {
                $typeElement = (string)($element['type'] ?? '');
                if ($filtreCorbeilleActif !== 'tous' && $typeElement !== $filtreCorbeilleActif) {
                    return false;
                }

                return $this->elementCorrespondRecherche($element, $rechercheNormalisee);
            }
        ));

        $totalFiltresCorbeille = count($elementsFiltres);
        $pagination = new Pagination($totalFiltresCorbeille, 10);
        $tousLesElements = array_slice(
            $elementsFiltres,
            $pagination->offset(),
            $pagination->limit()
        );

        require_once __DIR__ . '/../vues/admin/corbeille-admin.php';
    }

    
    public function restaurerElement(): void
    {
        if (!$this->verifierCsrfAction()) {
            JsonResponseHelper::erreurAvecDonnees('Token CSRF invalide', 403, [
                'new_token' => CsrfHelper::genererJeton()
            ]);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? '';

        if ($id <= 0 || empty($type)) {
            JsonResponseHelper::erreur('ID ou type invalide');
            return;
        }

        try {
            $succes = false;
            $nomType = '';

            switch ($type) {
                case 'article':
                    $succes = $this->articlesModele->restaurer($id);
                    $nomType = 'Article';
                    break;
                case 'actualite':
                    $succes = $this->actualitesModele->restaurerActualite($id);
                    $nomType = 'Actualité';
                    break;
                case 'projet':
                    $succes = $this->projetsModele->restaurer($id);
                    $nomType = 'Projet';
                    break;
                case 'video':
                    $succes = $this->videoModele->restaurer($id);
                    $nomType = 'Vidéo';
                    break;
                case 'utilisateur':
                    $succes = $this->utilisateursModele->restaurer($id);
                    $nomType = 'Utilisateur';
                    break;
                case 'message':
                    $succes = $this->contactModele->restaurer($id);
                    $nomType = 'Message';
                    break;
                default:
                    JsonResponseHelper::erreur('Type inconnu');
                    return;
            }

            if ($succes) {
                $this->invaliderCacheContenu($type, $id);
                JsonResponseHelper::succes(null, "$nomType restauré avec succès.");
            } else {
                JsonResponseHelper::erreurServeur("Erreur lors de la restauration de $nomType.");
            }
        } catch (Exception $e) {
            JsonResponseHelper::erreurServeur('Erreur lors de la restauration', $e);
        }
    }

    
    public function supprimerDefinitivement(): void
    {
        if (!$this->verifierCsrfAction()) {
            JsonResponseHelper::erreurAvecDonnees('Token CSRF invalide', 403, [
                'new_token' => CsrfHelper::genererJeton()
            ]);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $type = $_POST['type'] ?? '';

        if ($id <= 0 || empty($type)) {
            JsonResponseHelper::erreur('ID ou type invalide');
            return;
        }

        try {
            $succes = false;
            $nomType = '';

            switch ($type) {
                case 'article':
                    $article = $this->trouverElementSupprime('article', $id);
                    if (is_array($article)) {
                        $this->supprimerFichierImageLocal('article', (string)($article['image_url'] ?? ''));
                    }
                    $succes = $this->articlesModele->supprimerDefinitivement($id);
                    $nomType = 'Article';
                    break;
                case 'actualite':
                    $succes = $this->actualitesModele->supprimerDefinitivement($id);
                    $nomType = 'Actualité';
                    break;
                case 'projet':
                    $projet = $this->trouverElementSupprime('projet', $id);
                    if (is_array($projet)) {
                        $this->supprimerFichierImageLocal('projet', (string)($projet['image_url'] ?? ''));
                    }
                    $succes = $this->projetsModele->supprimerDefinitivement($id);
                    $nomType = 'Projet';
                    break;
                case 'video':
                    $succes = $this->videoModele->supprimerDefinitivement($id);
                    $nomType = 'Vidéo';
                    break;
                case 'utilisateur':
                    $succes = $this->utilisateursModele->supprimerDefinitivement($id);
                    $nomType = 'Utilisateur';
                    break;
                case 'message':
                    $succes = $this->contactModele->supprimerDefinitivement($id);
                    $nomType = 'Message';
                    break;
                default:
                    JsonResponseHelper::erreur('Type inconnu');
                    return;
            }

            if ($succes) {
                $this->invaliderCacheContenu($type, $id);
                if (!JsonResponseHelper::estAjax()) {
                    $_SESSION['message'] = "$nomType supprimé définitivement avec succès.";
                    $_SESSION['message_type'] = 'success';
                    header('Location: ?page=admin-corbeille');
                    exit;
                }
                JsonResponseHelper::succes(null, "$nomType supprimé définitivement avec succès.");
            } else {
                if (!JsonResponseHelper::estAjax()) {
                    $_SESSION['erreur'] = "Erreur lors de la suppression définitive de $nomType.";
                    header('Location: ?page=admin-corbeille');
                    exit;
                }
                JsonResponseHelper::erreurServeur("Erreur lors de la suppression définitive de $nomType.");
            }
        } catch (Exception $e) {
            if (!JsonResponseHelper::estAjax()) {
                error_log('AdminCorbeilleControleur::supprimerDefinitivement - ' . $e->getMessage());
                $_SESSION['erreur'] = 'Erreur lors de la suppression.';
                header('Location: ?page=admin-corbeille');
                exit;
            }
            JsonResponseHelper::erreurServeur('Erreur lors de la suppression', $e);
        }
    }

    
    public function restaurerTous(): void
    {
        if (!$this->verifierCsrfAction()) {
            JsonResponseHelper::erreurAvecDonnees('Token CSRF invalide', 403, [
                'new_token' => CsrfHelper::genererJeton()
            ]);
            return;
        }

        try {
            
            $articles = $this->articlesModele->elementsSupprimes();
            $actualites = $this->actualitesModele->obtenirActualitesSupprimees();
            $projets = $this->projetsModele->elementsSupprimes();
            $videos = $this->videoModele->elementsSupprimes();
            $utilisateurs = $this->utilisateursModele->elementsSupprimes();
            $messages = $this->contactModele->elementsSupprimes();

            $compteur = 0;

            
            foreach ($articles as $article) {
                if ($this->articlesModele->restaurer($article['id'])) {
                    $this->invaliderCacheContenu('article', (int)$article['id']);
                    $compteur++;
                }
            }

            
            foreach ($actualites as $actualite) {
                if ($this->actualitesModele->restaurerActualite((int)$actualite['id'])) {
                    $this->invaliderCacheContenu('actualite', (int)$actualite['id']);
                    $compteur++;
                }
            }

            
            foreach ($projets as $projet) {
                if ($this->projetsModele->restaurer($projet['id'])) {
                    $this->invaliderCacheContenu('projet', (int)$projet['id']);
                    $compteur++;
                }
            }

            
            foreach ($videos as $video) {
                if ($this->videoModele->restaurer($video['id'])) {
                    $this->invaliderCacheContenu('video', (int)$video['id']);
                    $compteur++;
                }
            }

            
            foreach ($utilisateurs as $utilisateur) {
                if ($this->utilisateursModele->restaurer($utilisateur['id'])) {
                    $compteur++;
                }
            }

            
            foreach ($messages as $message) {
                if ($this->contactModele->restaurer($message['id'])) {
                    $compteur++;
                }
            }

            if (!JsonResponseHelper::estAjax()) {
                $_SESSION['message'] = "$compteur élément(s) restauré(s) avec succès.";
                $_SESSION['message_type'] = 'success';
                header('Location: ?page=admin-corbeille');
                exit;
            }
            JsonResponseHelper::succes(
                ['compteur' => $compteur],
                "$compteur élément(s) restauré(s) avec succès."
            );
        } catch (Exception $e) {
            if (!JsonResponseHelper::estAjax()) {
                error_log('AdminCorbeilleControleur::restaurerTous - ' . $e->getMessage());
                $_SESSION['erreur'] = 'Erreur lors de la restauration de tous les elements.';
                header('Location: ?page=admin-corbeille');
                exit;
            }
            JsonResponseHelper::erreurServeur('Erreur lors de la restauration de tous les elements', $e);
        }
    }

    
    public function viderCorbeille(): void
    {
        if (!$this->verifierCsrfAction()) {
            JsonResponseHelper::erreurAvecDonnees('Token CSRF invalide', 403, [
                'new_token' => CsrfHelper::genererJeton()
            ]);
            return;
        }

        try {
            
            $articles = $this->articlesModele->elementsSupprimes();
            $actualites = $this->actualitesModele->obtenirActualitesSupprimees();
            $projets = $this->projetsModele->elementsSupprimes();
            $videos = $this->videoModele->elementsSupprimes();
            $utilisateurs = $this->utilisateursModele->elementsSupprimes();
            $messages = $this->contactModele->elementsSupprimes();

            $compteur = 0;

            
            foreach ($articles as $article) {
                $this->supprimerFichierImageLocal('article', (string)($article['image_url'] ?? ''));
                if ($this->articlesModele->supprimerDefinitivement($article['id'])) {
                    $this->invaliderCacheContenu('article', (int)$article['id']);
                    $compteur++;
                }
            }

            
            foreach ($actualites as $actualite) {
                if ($this->actualitesModele->supprimerDefinitivement((int)$actualite['id'])) {
                    $this->invaliderCacheContenu('actualite', (int)$actualite['id']);
                    $compteur++;
                }
            }

            
            foreach ($projets as $projet) {
                $this->supprimerFichierImageLocal('projet', (string)($projet['image_url'] ?? ''));
                if ($this->projetsModele->supprimerDefinitivement($projet['id'])) {
                    $this->invaliderCacheContenu('projet', (int)$projet['id']);
                    $compteur++;
                }
            }

            
            foreach ($videos as $video) {
                if ($this->videoModele->supprimerDefinitivement($video['id'])) {
                    $this->invaliderCacheContenu('video', (int)$video['id']);
                    $compteur++;
                }
            }

            
            foreach ($utilisateurs as $utilisateur) {
                if ($this->utilisateursModele->supprimerDefinitivement($utilisateur['id'])) {
                    $compteur++;
                }
            }

            
            foreach ($messages as $message) {
                if ($this->contactModele->supprimerDefinitivement($message['id'])) {
                    $compteur++;
                }
            }

            if (!JsonResponseHelper::estAjax()) {
                $_SESSION['message'] = "Corbeille vidée: $compteur élément(s) supprimé(s) définitivement.";
                $_SESSION['message_type'] = 'success';
                header('Location: ?page=admin-corbeille');
                exit;
            }
            JsonResponseHelper::succes(
                ['compteur' => $compteur],
                "Corbeille vidée: $compteur élément(s) supprimé(s) définitivement."
            );
        } catch (Exception $e) {
            if (!JsonResponseHelper::estAjax()) {
                error_log('AdminCorbeilleControleur::viderCorbeille - ' . $e->getMessage());
                $_SESSION['erreur'] = 'Erreur lors du vidage de la corbeille.';
                header('Location: ?page=admin-corbeille');
                exit;
            }
            JsonResponseHelper::erreurServeur('Erreur lors du vidage de la corbeille', $e);
        }
    }
}
