<?php

class RoleHelper
{
    public const ADMIN = 'admin';
    public const EDITEUR = 'editeur';
    public const UTILISATEUR = 'utilisateur';

    public static function normaliser(?string $role): string
    {
        $value = trim((string)$role);
        if ($value === '') {
            return self::UTILISATEUR;
        }

        $value = function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);

        $value = strtr($value, [
            "\u{00E9}" => 'e',
            "\u{00E8}" => 'e',
            "\u{00EA}" => 'e',
            "\u{00EB}" => 'e',
            "\u{00E0}" => 'a',
            "\u{00E2}" => 'a',
            "\u{00EE}" => 'i',
            "\u{00EF}" => 'i',
            "\u{00F4}" => 'o',
            "\u{00F6}" => 'o',
            "\u{00F9}" => 'u',
            "\u{00FB}" => 'u',
            "\u{00FC}" => 'u',
            "\u{00E7}" => 'c',
        ]);

        if ($value === self::ADMIN || str_contains($value, 'administr')) {
            return self::ADMIN;
        }

        if ($value === self::EDITEUR || str_contains($value, 'diteur') || $value === 'editor') {
            return self::EDITEUR;
        }

        if ($value === 'user' || $value === self::UTILISATEUR || str_contains($value, 'utilisat')) {
            return self::UTILISATEUR;
        }

        return self::UTILISATEUR;
    }

    public static function estAdmin(?string $role): bool
    {
        return self::normaliser($role) === self::ADMIN;
    }

    public static function peutCreerContenu(?string $role): bool
    {
        $roleNormalise = self::normaliser($role);
        return $roleNormalise === self::ADMIN || $roleNormalise === self::EDITEUR;
    }
}
