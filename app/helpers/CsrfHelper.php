<?php
/**
 * Helper de protection CSRF (Cross-Site Request Forgery)
 *
 * Génère et valide des tokens CSRF pour protéger les formulaires
 * contre les attaques de falsification de requêtes intersites
 *
 * @author Fablabrobot
 * @version 2.0.0
 */
class CsrfHelper
{
    /**
     * Génère un nouveau token CSRF et le stocke en session
     * Le token expire après 1 heure
     *
     * @return string Le token généré (64 caractères hexadécimaux)
     */
    public static function genererJeton(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Générer un token cryptographiquement sécurisé
        $token = bin2hex(random_bytes(32));

        // Stocker en session avec timestamp
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();

        return $token;
    }

    /**
     * Récupère le token CSRF actuel sans en générer un nouveau
     * Génère un nouveau token si aucun n'existe
     *
     * @return string Le token CSRF actuel
     */
    public static function obtenirJeton(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Vérifier si le token existe
        if (!isset($_SESSION['csrf_token'])) {
            return self::genererJeton();
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Valide un token CSRF fourni
     * Vérifie l'existence, la correspondance et l'expiration du token
     *
     * @param string $token Le token à valider
     * @return bool True si le token est valide, false sinon
     */
    public static function validerJeton(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Vérifier que le token existe en session
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }

        // Vérifier l'expiration (24 heures)
        if (time() - $_SESSION['csrf_token_time'] > 86400) {
            return false;
        }

        // Comparaison sécurisée contre les attaques timing
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Génère un champ input hidden contenant le token CSRF
     * À utiliser directement dans les formulaires HTML
     *
     * @return string Le code HTML du champ input hidden
     */
    public static function obtenirChampJeton(): string
    {
        $token = self::obtenirJeton();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '" class="csrf-token-field">';
    }

    /**
     * Génère une balise meta contenant le token CSRF
     * À utiliser dans le header pour les requêtes AJAX
     *
     * @return string Le code HTML de la balise meta
     */
    public static function obtenirMetaJeton(): string
    {
        $token = self::obtenirJeton();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Vérifie le token CSRF depuis $_POST et affiche une erreur si invalide
     * Méthode utilitaire pour simplifier la validation dans les contrôleurs
     *
     * @param string $redirectUrl URL de redirection en cas d'erreur (optionnel)
     * @return bool True si valide, false si invalide (et redirection si URL fournie)
     */
    public static function verifierJetonPost(?string $redirectUrl = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_POST['csrf_token'] ?? '';

        if (!self::validerJeton($token)) {
            // Régénérer un nouveau token pour éviter les blocages
            self::genererJeton();

            $_SESSION['message'] = "Token de sécurité invalide. Veuillez réessayer.";
            $_SESSION['message_type'] = "danger";

            if ($redirectUrl !== null) {
                header("Location: $redirectUrl");
                exit;
            }

            return false;
        }

        return true;
    }

    /**
     * Vérifie le token CSRF depuis les headers HTTP (pour AJAX)
     *
     * @return bool True si valide, false sinon
     */
    public static function verifierJetonEntete(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        return self::validerJeton($token);
    }

    /**
     * Initialise automatiquement le token CSRF en session si nécessaire
     * À appeler au début de chaque page qui utilise des formulaires
     */
    public static function init(): void
    {
        self::obtenirJeton();
    }
}