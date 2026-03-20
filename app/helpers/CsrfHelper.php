<?php

class CsrfHelper
{
    public static function genererJeton(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();

        return $token;
    }

    public static function obtenirJeton(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            !isset($_SESSION['csrf_token'])
            || !is_string($_SESSION['csrf_token'])
            || $_SESSION['csrf_token'] === ''
            || !isset($_SESSION['csrf_token_time'])
            || !is_numeric($_SESSION['csrf_token_time'])
        ) {
            return self::genererJeton();
        }

        if (time() - (int)$_SESSION['csrf_token_time'] > 86400) {
            return self::genererJeton();
        }

        return $_SESSION['csrf_token'];
    }

    public static function validerJeton(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }

        if (time() - (int)$_SESSION['csrf_token_time'] > 86400) {
            return false;
        }

        return hash_equals((string)$_SESSION['csrf_token'], $token);
    }

    public static function obtenirChampJeton(): string
    {
        $token = self::obtenirJeton();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '" class="csrf-token-field">';
    }

    public static function obtenirMetaJeton(): string
    {
        $token = self::obtenirJeton();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verifierJetonPost(string $redirectUrl = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = (string)($_POST['csrf_token'] ?? '');
        if (self::validerJeton($token)) {
            return true;
        }

        
        
        if ($redirectUrl !== null) {
            self::genererJeton();
            $_SESSION['message'] = 'Token de securite invalide. Veuillez reessayer.';
            $_SESSION['message_type'] = 'danger';
            header("Location: $redirectUrl");
            exit;
        }

        return false;
    }

    public static function verifierJetonEntete(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return self::validerJeton($token);
    }

    public static function init(): void
    {
        self::obtenirJeton();
    }
}
