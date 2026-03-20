<?php

class JsonResponseHelper
{
    private static function estModeDebug(): bool
    {
        $value = getenv('APP_DEBUG');
        if ($value === false || $value === '') {
            return false;
        }

        $normalized = strtolower(trim((string)$value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private static function journaliserErreur(string $message, $details = null): void
    {
        $logMessage = $message;
        if ($details instanceof Throwable) {
            $logMessage .= ' | ' . $details->getMessage();
        } elseif (is_string($details) && $details !== '') {
            $logMessage .= ' | ' . $details;
        } elseif ($details !== null) {
            $logMessage .= ' | ' . json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        error_log($logMessage);
    }

    
    public static function succes($donnees = null, string $message = ''): void
    {
        self::send([
            'success' => true,
            'message' => $message,
            'data' => $donnees
        ]);
    }

    
    public static function erreur(string $message, int $code = 400, $details = null): void
    {
        http_response_code($code);
        $payload = [
            'success' => false,
            'message' => $message
        ];

        if (self::estModeDebug() && $details !== null) {
            $payload['error'] = $details instanceof Throwable ? $details->getMessage() : $details;
        }

        self::send($payload);
    }

    
    public static function erreurAvecDonnees(string $message, int $code = 400, array $donnees = []): void
    {
        http_response_code($code);
        self::send([
            'success' => false,
            'message' => $message,
            'data' => $donnees
        ]);
    }

    
    public static function erreurValidation(array $erreurs): void
    {
        self::erreur('Erreur de validation', 422, ['validation' => $erreurs]);
    }

    
    public static function estAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    
    private static function send(array $donnees): void
    {
        
        header('Content-Type: application/json; charset=utf-8');

        
        
        
        echo json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        
        exit;
    }

    
    public static function nonAutorise(string $message = 'Non authentifié'): void
    {
        self::erreur($message, 401);
    }

    
    public static function interdit(string $message = 'Accès interdit'): void
    {
        self::erreur($message, 403);
    }

    
    public static function nonTrouve(string $message = 'Ressource introuvable'): void
    {
        self::erreur($message, 404);
    }

    
    public static function erreurServeur(string $message = 'Erreur serveur', $details = null): void
    {
        self::journaliserErreur($message, $details);
        self::erreur($message, 500, $details);
    }
}
