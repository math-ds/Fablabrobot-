<?php

class AuthMiddleware
{
    public static function utilisateurConnecte(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['utilisateur_id']) && (int)$_SESSION['utilisateur_id'] > 0;
    }

    public static function exigerConnexion(string $redirect = '?page=login'): void
    {
        if (self::utilisateurConnecte()) {
            return;
        }

        header('Location: ' . $redirect);
        exit;
    }
}

