<?php
require_once __DIR__ . '/../modèles/AdminArticlesModele.php';
require_once __DIR__ . '/../modèles/AdminProjetsModele.php';
require_once __DIR__ . '/../modèles/AdminVideoModele.php';
require_once __DIR__ . '/../modèles/AdminUtilisateursModele.php';
require_once __DIR__ . '/../modèles/AdminContactModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';
require_once __DIR__ . '/../helpers/JsonResponseHelper.php';

class AdminCorbeilleControleur
{
    private AdminArticlesModele $articlesModele;
    private AdminProjetsModele $projetsModele;
    private AdminVideoModele $videoModele;
    private AdminUtilisateursModele $utilisateursModele;
    private AdminContactModele $contactModele;

    public function __construct()
    {
        $this->articlesModele = new AdminArticlesModele();
        $this->projetsModele = new AdminProjetsModele();
        $this->videoModele = new AdminVideoModele();
        $this->utilisateursModele = new AdminUtilisateursModele();
        $this->contactModele = new AdminContactModele();
    }

    /**
     * Affiche la page de la corbeille
     */
    public function afficherCorbeille(): void
    {
        // Récupérer tous les éléments supprimés
        $articles = $this->articlesModele->elementsSupprimes();
        $projets = $this->projetsModele->elementsSupprimes();
        $videos = $this->videoModele->elementsSupprimes();
        $utilisateurs = $this->utilisateursModele->elementsSupprimes();
        $messages = $this->contactModele->elementsSupprimes();

        // Ajouter un type à chaque élément pour l'identifier
        foreach ($articles as &$article) {
            $article['type'] = 'article';
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

        // Fusionner tous les éléments
        $tousLesElements = array_merge($articles, $projets, $videos, $utilisateurs, $messages);

        // Trier par date de suppression (le plus récent en premier)
        usort($tousLesElements, function($a, $b) {
            return strtotime($b['deleted_at']) - strtotime($a['deleted_at']);
        });

        require_once __DIR__ . '/../vues/admin/corbeille-admin.php';
    }

    /**
     * Restaure un élément supprimé
     */
    public function restaurerElement(): void
    {
        // Vérifier CSRF pour AJAX
        if (JsonResponseHelper::estAjax()) {
            if (!CsrfHelper::validerJeton($_POST['csrf_token'] ?? '')) {
                JsonResponseHelper::interdit('Token CSRF invalide');
                return;
            }
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
                JsonResponseHelper::succes(null, "$nomType restauré avec succès");
            } else {
                JsonResponseHelper::erreurServeur("Erreur lors de la restauration du $nomType");
            }
        } catch (Exception $e) {
            JsonResponseHelper::erreurServeur('Erreur lors de la restauration', $e->getMessage());
        }
    }

    /**
     * Supprime définitivement un élément
     */
    public function supprimerDefinitivement(): void
    {
        // Vérifier CSRF pour AJAX
        if (JsonResponseHelper::estAjax()) {
            if (!CsrfHelper::validerJeton($_POST['csrf_token'] ?? '')) {
                JsonResponseHelper::interdit('Token CSRF invalide');
                return;
            }
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
                    $succes = $this->articlesModele->supprimerDefinitivement($id);
                    $nomType = 'Article';
                    break;
                case 'projet':
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
                if (!JsonResponseHelper::estAjax()) {
                    $_SESSION['message'] = "$nomType supprimé définitivement";
                    header('Location: ?page=admin-corbeille');
                    exit;
                }
                JsonResponseHelper::succes(null, "$nomType supprimé définitivement");
            } else {
                if (!JsonResponseHelper::estAjax()) {
                    $_SESSION['erreur'] = "Erreur lors de la suppression définitive du $nomType";
                    header('Location: ?page=admin-corbeille');
                    exit;
                }
                JsonResponseHelper::erreurServeur("Erreur lors de la suppression définitive du $nomType");
            }
        } catch (Exception $e) {
            if (!JsonResponseHelper::estAjax()) {
                $_SESSION['erreur'] = 'Erreur lors de la suppression: ' . $e->getMessage();
                header('Location: ?page=admin-corbeille');
                exit;
            }
            JsonResponseHelper::erreurServeur('Erreur lors de la suppression', $e->getMessage());
        }
    }

    /**
     * Restaure tous les éléments supprimés
     */
    public function restaurerTous(): void
    {
        // Vérifier CSRF pour AJAX
        if (JsonResponseHelper::estAjax()) {
            if (!CsrfHelper::validerJeton($_POST['csrf_token'] ?? '')) {
                JsonResponseHelper::interdit('Token CSRF invalide');
                return;
            }
        }

        try {
            // Récupérer tous les éléments supprimés
            $articles = $this->articlesModele->elementsSupprimes();
            $projets = $this->projetsModele->elementsSupprimes();
            $videos = $this->videoModele->elementsSupprimes();
            $utilisateurs = $this->utilisateursModele->elementsSupprimes();
            $messages = $this->contactModele->elementsSupprimes();

            $compteur = 0;

            // Restaurer tous les articles
            foreach ($articles as $article) {
                if ($this->articlesModele->restaurer($article['id'])) {
                    $compteur++;
                }
            }

            // Restaurer tous les projets
            foreach ($projets as $projet) {
                if ($this->projetsModele->restaurer($projet['id'])) {
                    $compteur++;
                }
            }

            // Restaurer toutes les vidéos
            foreach ($videos as $video) {
                if ($this->videoModele->restaurer($video['id'])) {
                    $compteur++;
                }
            }

            // Restaurer tous les utilisateurs
            foreach ($utilisateurs as $utilisateur) {
                if ($this->utilisateursModele->restaurer($utilisateur['id'])) {
                    $compteur++;
                }
            }

            // Restaurer tous les messages
            foreach ($messages as $message) {
                if ($this->contactModele->restaurer($message['id'])) {
                    $compteur++;
                }
            }

            if (!JsonResponseHelper::estAjax()) {
                $_SESSION['message'] = "Tous les éléments restaurés : $compteur élément(s) restauré(s)";
                header('Location: ?page=admin-corbeille');
                exit;
            }
            JsonResponseHelper::succes(
                ['compteur' => $compteur],
                "Tous les éléments restaurés : $compteur élément(s) restauré(s)"
            );
        } catch (Exception $e) {
            if (!JsonResponseHelper::estAjax()) {
                $_SESSION['erreur'] = 'Erreur lors de la restauration de tous les éléments: ' . $e->getMessage();
                header('Location: ?page=admin-corbeille');
                exit;
            }
            JsonResponseHelper::erreurServeur('Erreur lors de la restauration de tous les éléments', $e->getMessage());
        }
    }

    /**
     * Vide toute la corbeille (supprime définitivement tous les éléments)
     */
    public function viderCorbeille(): void
    {
        // Vérifier CSRF pour AJAX
        if (JsonResponseHelper::estAjax()) {
            if (!CsrfHelper::validerJeton($_POST['csrf_token'] ?? '')) {
                JsonResponseHelper::interdit('Token CSRF invalide');
                return;
            }
        }

        try {
            // Récupérer tous les éléments supprimés
            $articles = $this->articlesModele->elementsSupprimes();
            $projets = $this->projetsModele->elementsSupprimes();
            $videos = $this->videoModele->elementsSupprimes();
            $utilisateurs = $this->utilisateursModele->elementsSupprimes();
            $messages = $this->contactModele->elementsSupprimes();

            $compteur = 0;

            // Supprimer définitivement tous les articles
            foreach ($articles as $article) {
                if ($this->articlesModele->supprimerDefinitivement($article['id'])) {
                    $compteur++;
                }
            }

            // Supprimer définitivement tous les projets
            foreach ($projets as $projet) {
                if ($this->projetsModele->supprimerDefinitivement($projet['id'])) {
                    $compteur++;
                }
            }

            // Supprimer définitivement toutes les vidéos
            foreach ($videos as $video) {
                if ($this->videoModele->supprimerDefinitivement($video['id'])) {
                    $compteur++;
                }
            }

            // Supprimer définitivement tous les utilisateurs
            foreach ($utilisateurs as $utilisateur) {
                if ($this->utilisateursModele->supprimerDefinitivement($utilisateur['id'])) {
                    $compteur++;
                }
            }

            // Supprimer définitivement tous les messages
            foreach ($messages as $message) {
                if ($this->contactModele->supprimerDefinitivement($message['id'])) {
                    $compteur++;
                }
            }

            if (!JsonResponseHelper::estAjax()) {
                $_SESSION['message'] = "Corbeille vidée : $compteur élément(s) supprimé(s) définitivement";
                header('Location: ?page=admin-corbeille');
                exit;
            }
            JsonResponseHelper::succes(
                ['compteur' => $compteur],
                "Corbeille vidée : $compteur élément(s) supprimé(s) définitivement"
            );
        } catch (Exception $e) {
            if (!JsonResponseHelper::estAjax()) {
                $_SESSION['erreur'] = 'Erreur lors du vidage de la corbeille: ' . $e->getMessage();
                header('Location: ?page=admin-corbeille');
                exit;
            }
            JsonResponseHelper::erreurServeur('Erreur lors du vidage de la corbeille', $e->getMessage());
        }
    }
}
