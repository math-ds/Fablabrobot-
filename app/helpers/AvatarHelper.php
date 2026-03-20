<?php

class AvatarHelper
{
    private const PALETTE_COULEURS = [
        '#FF6B6B',
        '#4ECDC4',
        '#45B7D1',
        '#FFA07A',
        '#98D8C8',
        '#F7DC6F',
        '#BB8FCE',
        '#85C1E2',
    ];

    
    public static function construireDonnees(?string $nom, ?string $photoFichier, string $baseUrl): array
    {
        $nomNormalise = trim((string) $nom);
        if ($nomNormalise === '') {
            $nomNormalise = 'Utilisateur';
        }

        $photoUrl = self::obtenirUrlPhoto($photoFichier, $baseUrl);

        return [
            'has_photo' => $photoUrl !== null,
            'photo_url' => $photoUrl,
            'initiales' => self::genererInitiales($nomNormalise),
            'couleur' => self::genererCouleur($nomNormalise),
            'classe_couleur' => self::genererClasseCouleur($nomNormalise),
        ];
    }

    public static function genererInitiales(string $nom): string
    {
        $nom = trim($nom);
        if ($nom === '') {
            return 'US';
        }

        $parties = preg_split('/\s+/', $nom) ?: [];
        $parties = array_values(array_filter($parties, static fn($partie) => $partie !== ''));

        if (count($parties) >= 2) {
            $premiere = self::extraire($parties[0], 1);
            $derniere = self::extraire($parties[count($parties) - 1], 1);
            return self::majuscules($premiere . $derniere);
        }

        return self::majuscules(self::extraire($parties[0] ?? $nom, 2));
    }

    public static function genererCouleur(string $seed): string
    {
        return self::PALETTE_COULEURS[self::obtenirIndexCouleur($seed)];
    }

    public static function genererClasseCouleur(string $seed): string
    {
        return 'avatar-couleur-' . (self::obtenirIndexCouleur($seed) + 1);
    }

    public static function obtenirUrlPhoto(?string $photoFichier, string $baseUrl): ?string
    {
        $photoNormalisee = self::normaliserNomFichier($photoFichier);
        if ($photoNormalisee === null) {
            return null;
        }

        $cheminAbsolu = self::obtenirCheminPhotoAbsolu($photoNormalisee);
        if (!is_file($cheminAbsolu)) {
            return null;
        }

        return rtrim($baseUrl, '/') . '/uploads/profils/' . rawurlencode($photoNormalisee);
    }

    private static function obtenirCheminPhotoAbsolu(string $photoFichier): string
    {
        return __DIR__ . '/../../public/uploads/profils/' . $photoFichier;
    }

    private static function normaliserNomFichier(?string $photoFichier): ?string
    {
        $photoFichier = trim((string) $photoFichier);
        if ($photoFichier === '') {
            return null;
        }

        $base = basename($photoFichier);
        return $base !== '' ? $base : null;
    }

    private static function extraire(string $valeur, int $longueur): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($valeur, 0, $longueur, 'UTF-8');
        }

        return substr($valeur, 0, $longueur);
    }

    private static function majuscules(string $valeur): string
    {
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($valeur, 'UTF-8');
        }

        return strtoupper($valeur);
    }

    private static function obtenirIndexCouleur(string $seed): int
    {
        $seed = trim($seed);
        if ($seed === '') {
            $seed = 'US';
        }

        $hash = abs(crc32($seed));
        return $hash % count(self::PALETTE_COULEURS);
    }
}
