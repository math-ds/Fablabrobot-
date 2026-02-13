<?php
/**
 * Helper de réponse JSON pour AJAX
 *
 * Fournit des méthodes centralisées pour envoyer des réponses JSON
 * standardisées pour toutes les requêtes AJAX
 *
 * @author Fablabrobot
 * @version 1.0.0
 */
class JsonResponseHelper
{
    /**
     * Envoie une réponse JSON de succès
     *
     * @param mixed $donnees Données à retourner (optionnel)
     * @param string $message Message de succès (optionnel)
     * @return void
     */
    public static function succes($donnees = null, string $message = ''): void
    {
        self::send([
            'success' => true,
            'message' => $message,
            'data' => $donnees
        ]);
    }

    /**
     * Envoie une réponse JSON d'erreur
     *
     * @param string $message Message d'erreur
     * @param int $code Code HTTP (par défaut 400 Bad Request)
     * @param mixed $details Détails supplémentaires sur l'erreur (optionnel)
     * @return void
     */
    public static function erreur(string $message, int $code = 400, $details = null): void
    {
        http_response_code($code);
        self::send([
            'success' => false,
            'message' => $message,
            'error' => $details
        ]);
    }

    /**
     * Envoie une réponse JSON d'erreur de validation
     * Utilisé quand les données envoyées ne passent pas la validation
     *
     * @param array $erreurs Tableau associatif des erreurs de validation
     * @return void
     */
    public static function erreurValidation(array $erreurs): void
    {
        self::erreur('Erreur de validation', 422, ['validation' => $erreurs]);
    }

    /**
     * Vérifie si la requête actuelle est une requête AJAX
     * Vérifie le header X-Requested-With
     *
     * @return bool true si AJAX, false sinon
     */
    public static function estAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Envoie la réponse JSON et termine l'exécution du script
     *
     * @param array $donnees Données à encoder en JSON
     * @return void
     */
    private static function send(array $donnees): void
    {
        // Définir le header Content-Type
        header('Content-Type: application/json; charset=utf-8');

        // Encoder et envoyer les données
        // JSON_UNESCAPED_UNICODE pour gérer correctement les caractères accentués
        // JSON_UNESCAPED_SLASHES pour ne pas échapper les /
        echo json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Terminer l'exécution
        exit;
    }

    /**
     * Envoie une réponse 401 Unauthorized
     * Utilisé quand l'utilisateur n'est pas authentifié
     *
     * @param string $message Message d'erreur (optionnel)
     * @return void
     */
    public static function nonAutorise(string $message = 'Non authentifié'): void
    {
        self::erreur($message, 401);
    }

    /**
     * Envoie une réponse 403 Forbidden
     * Utilisé quand l'utilisateur n'a pas les permissions nécessaires
     *
     * @param string $message Message d'erreur (optionnel)
     * @return void
     */
    public static function interdit(string $message = 'Accès interdit'): void
    {
        self::erreur($message, 403);
    }

    /**
     * Envoie une réponse 404 Not Found
     * Utilisé quand une ressource n'existe pas
     *
     * @param string $message Message d'erreur (optionnel)
     * @return void
     */
    public static function nonTrouve(string $message = 'Ressource introuvable'): void
    {
        self::erreur($message, 404);
    }

    /**
     * Envoie une réponse 500 Internal Server Error
     * Utilisé pour les erreurs serveur inattendues
     *
     * @param string $message Message d'erreur (optionnel)
     * @param mixed $details Détails de l'erreur (optionnel, utile en dev)
     * @return void
     */
    public static function erreurServeur(string $message = 'Erreur serveur', $details = null): void
    {
        self::erreur($message, 500, $details);
    }
}
