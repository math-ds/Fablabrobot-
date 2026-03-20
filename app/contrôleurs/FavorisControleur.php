<?php
require_once __DIR__ . '/../modèles/FavorisModele.php';
require_once __DIR__ . '/../helpers/CsrfHelper.php';

class FavorisControleur
{
    private FavorisModele $modele;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->modele = new FavorisModele();
    }

    public function index(): void
    {
        $utilisateurId = $this->obtenirUtilisateurSessionValide();
        if ($utilisateurId <= 0) {
            header('Location: ?page=login');
            exit;
        }

        $typeCourant = $this->normaliserTypeFiltre((string)($_GET['type'] ?? 'all'));
        $favoris = $this->modele->obtenirFavorisUtilisateur($utilisateurId, $typeCourant);
        $compteurs = $this->modele->compterFavorisValidesUtilisateur($utilisateurId);

        require __DIR__ . '/../vues/favoris/index.php';
    }

    public function toggle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->repondreErreur('Methode non autorisee.', 405);
        }

        $utilisateurId = $this->obtenirUtilisateurSessionValide();
        if ($utilisateurId <= 0) {
            $this->repondreErreur('Vous devez etre connecte pour gerer vos favoris.', 401);
        }

        $csrfValide = CsrfHelper::verifierJetonPost() || CsrfHelper::verifierJetonEntete();
        if (!$csrfValide) {
            $this->repondreErreur('Token de sécurité invalide.', 419);
        }

        $type = $this->modele->normaliserType((string)($_POST['type_contenu'] ?? ''));
        $contenuId = (int)($_POST['contenu_id'] ?? 0);

        if ($type === null || $contenuId <= 0) {
            $this->repondreErreur('Parametres favoris invalides.', 422);
        }

        if (!$this->modele->contenuExiste($type, $contenuId)) {
            $this->repondreErreur('Contenu introuvable ou supprime.', 404);
        }

        try {
            $isFavori = $this->modele->toggleFavori($utilisateurId, $type, $contenuId);
        } catch (Throwable $e) {
            error_log("Erreur favori: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $messageErreur = 'Erreur lors de la mise a jour du favori.';
            if ($e instanceof InvalidArgumentException || $e instanceof RuntimeException) {
                $messageErreur = $e->getMessage();
            }
            $this->repondreErreur($messageErreur, 500);
        }

        $message = $isFavori
            ? 'Ajoute aux favoris.'
            : 'Retire des favoris.';

        $this->repondreSucces([
            'type_contenu' => $type,
            'contenu_id' => $contenuId,
            'is_favori' => $isFavori,
        ], $message);
    }

    public function supprimerTous(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->repondreErreur('Methode non autorisee.', 405);
        }

        $utilisateurId = $this->obtenirUtilisateurSessionValide();
        if ($utilisateurId <= 0) {
            $this->repondreErreur('Vous devez etre connecte pour gerer vos favoris.', 401);
        }

        $csrfValide = CsrfHelper::verifierJetonPost() || CsrfHelper::verifierJetonEntete();
        if (!$csrfValide) {
            $this->repondreErreur('Token de sécurité invalide.', 419);
        }

        try {
            $supprimes = $this->modele->supprimerTousFavorisUtilisateur($utilisateurId);
        } catch (Throwable $e) {
            $this->repondreErreur('Erreur lors de la suppression des favoris.', 500);
        }

        $message = $supprimes > 0
            ? 'Tous les favoris ont ete supprimes.'
            : 'Aucun favori a supprimer.';

        $this->repondreSucces([
            'deleted' => $supprimes,
        ], $message);
    }

    private function normaliserTypeFiltre(string $type): string
    {
        $typeNormalise = strtolower(trim($type));
        if ($typeNormalise === 'all') {
            return 'all';
        }

        $typeModele = $this->modele->normaliserType($typeNormalise);
        return $typeModele ?? 'all';
    }

    private function estRequeteAjax(): bool
    {
        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            return true;
        }

        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function repondreSucces(array $data, string $message): void
    {
        if ($this->estRequeteAjax()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => true,
                'message' => $message,
                'data' => $data,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = 'success';
        $this->redirigerRetour();
    }

    private function repondreErreur(string $message, int $codeHttp = 400): void
    {
        if ($this->estRequeteAjax()) {
            http_response_code($codeHttp);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'message' => $message,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = 'danger';
        $this->redirigerRetour();
    }

    private function redirigerRetour(): void
    {
        $retour = '?page=favoris';
        $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer !== '') {
            $parsed = parse_url($referer);
            if (is_array($parsed)) {
                $sameHost = true;
                if (isset($parsed['host']) && isset($_SERVER['HTTP_HOST'])) {
                    $sameHost = strcasecmp((string)$parsed['host'], (string)$_SERVER['HTTP_HOST']) === 0;
                }

                if ($sameHost && !empty($parsed['query'])) {
                    $retour = '?' . (string)$parsed['query'];
                }
            }
        }

        header('Location: ' . $retour);
        exit;
    }

    private function obtenirUtilisateurSessionValide(): int
    {
        $utilisateurId = (int)($_SESSION['utilisateur_id'] ?? 0);
        if ($utilisateurId <= 0) {
            return 0;
        }

        if ($this->modele->utilisateurActifExiste($utilisateurId)) {
            return $utilisateurId;
        }

        $emailSession = trim((string)($_SESSION['utilisateur_email'] ?? ''));
        if ($emailSession !== '') {
            $utilisateur = $this->modele->obtenirUtilisateurActifParEmail($emailSession);
            if (is_array($utilisateur) && (int)($utilisateur['id'] ?? 0) > 0) {
                $_SESSION['utilisateur_id'] = (int)$utilisateur['id'];
                $_SESSION['utilisateur_nom'] = (string)($utilisateur['nom'] ?? '');
                $_SESSION['utilisateur_email'] = (string)($utilisateur['email'] ?? $emailSession);
                $_SESSION['utilisateur_role'] = (string)($utilisateur['role'] ?? ($_SESSION['utilisateur_role'] ?? ''));
                $_SESSION['utilisateur_photo'] = !empty($utilisateur['photo']) ? (string)$utilisateur['photo'] : null;
                return (int)$utilisateur['id'];
            }
        }

        $this->invaliderSessionUtilisateur();
        return 0;
    }

    private function invaliderSessionUtilisateur(): void
    {
        unset($_SESSION['utilisateur_id']);
        unset($_SESSION['utilisateur_nom']);
        unset($_SESSION['utilisateur_email']);
        unset($_SESSION['utilisateur_role']);
        unset($_SESSION['utilisateur_photo']);
        unset($_SESSION['utilisateur_avatar']);
    }
}
