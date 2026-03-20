<?php

require_once __DIR__ . '/AuthMiddleware.php';
require_once __DIR__ . '/../helpers/RoleHelper.php';

class RoleMiddleware
{
    public static function exigerAdmin(): void
    {
        self::exigerUnDesRoles([RoleHelper::ADMIN]);
    }

    public static function exigerUnDesRoles(array $roles): void
    {
        AuthMiddleware::exigerConnexion();

        $rolesAutorises = array_values(array_unique(array_map(
            static fn($role) => RoleHelper::normaliser((string)$role),
            $roles
        )));

        $roleUtilisateur = RoleHelper::normaliser((string)($_SESSION['utilisateur_role'] ?? ''));
        if (in_array($roleUtilisateur, $rolesAutorises, true)) {
            return;
        }

        self::refuserAcces();
    }

    private static function refuserAcces(): void
    {
        http_response_code(403);

        if (self::estRequeteAjax()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'message' => "Accès refusé",
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $errorTitle = 'Accès refusé';
        $errorMessage = "Vous devez avoir les droits nécessaires pour accéder à cette page.";
        require __DIR__ . '/../vues/erreurs/403.php';
        exit;
    }

    private static function estRequeteAjax(): bool
    {
        if (isset($_GET['ajax']) && (string)$_GET['ajax'] === '1') {
            return true;
        }

        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
