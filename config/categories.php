<?php

class CategorieConfig
{
    private static function normaliserTexte(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $lower = function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);

        $simple = strtr($lower, [
            '�' => 'a', '�' => 'a', '�' => 'a', '�' => 'a', '�' => 'a',
            '�' => 'c',
            '�' => 'e', '�' => 'e', '�' => 'e', '�' => 'e',
            '�' => 'i', '�' => 'i', '�' => 'i', '�' => 'i',
            '�' => 'o', '�' => 'o', '�' => 'o', '�' => 'o', '�' => 'o',
            '�' => 'u', '�' => 'u', '�' => 'u', '�' => 'u',
            '�' => 'y', '�' => 'y',
            '�' => 'oe', '�' => 'ae',
            "'" => ' ', '�' => ' ',
        ]);

        $simple = preg_replace('/\s+/', ' ', $simple ?? '');
        return trim((string)$simple);
    }

    
    private static function normaliserSelonMap(string $value, array $map, array $categories, bool $allAsNull = false): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if ($allAsNull) {
            $lower = function_exists('mb_strtolower')
                ? mb_strtolower($value, 'UTF-8')
                : strtolower($value);
            if ($lower === 'all') {
                return null;
            }
        }

        $key = self::normaliserTexte($value);
        if ($key === '') {
            return null;
        }

        if (isset($map[$key])) {
            return $map[$key];
        }

        foreach ($categories as $categorie) {
            if (self::normaliserTexte($categorie) === $key) {
                return $categorie;
            }
        }

        return null;
    }

    
    public static function articlesDisponibles(): array
    {
        return [
            'Robotique',
            '�lectronique',
            'Programmation',
            'Impression 3D',
            'M�canique',
            'Conception',
            'Intelligence Artificielle',
            'Autre',
        ];
    }

    public static function normaliserArticle(string $categorie): ?string
    {
        $electronique = '�lectronique';
        $mecanique = 'M�canique';
        $map = [
            'robotique' => 'Robotique',
            'electronique' => $electronique,
            'programmation' => 'Programmation',
            'impression 3d' => 'Impression 3D',
            'mecanique' => $mecanique,
            'conception' => 'Conception',
            'intelligence artificielle' => 'Intelligence Artificielle',
            'autre' => 'Autre',
        ];

        return self::normaliserSelonMap($categorie, $map, self::articlesDisponibles(), false);
    }

    
    public static function projetsDisponibles(): array
    {
        return [
            'Robotique',
            'Drone / FPV',
            'Impression 3D',
            '�lectronique',
            'Programmation',
            'M�canique',
            'Autre',
        ];
    }

    public static function normaliserProjet(string $categorie): ?string
    {
        $electronique = '�lectronique';
        $mecanique = 'M�canique';
        $map = [
            'robotique' => 'Robotique',
            'drone' => 'Drone / FPV',
            'drone / fpv' => 'Drone / FPV',
            'impression 3d' => 'Impression 3D',
            'electronique' => $electronique,
            'programmation' => 'Programmation',
            'mecanique' => $mecanique,
            'autre' => 'Autre',
            'autres' => 'Autre',
        ];

        return self::normaliserSelonMap($categorie, $map, self::projetsDisponibles(), true);
    }

    
    public static function videosDisponibles(): array
    {
        return [
            'Tutoriel',
            'Atelier',
            'Demonstration',
            'Projet',
            'Robotique',
            'Impression 3D',
            'Electronique',
            'Programmation',
            'Mecanique',
            'Interview',
            'Evenement',
            'Autre',
        ];
    }

    public static function normaliserVideo(string $categorie): ?string
    {
        $map = [
            'tutoriel' => 'Tutoriel',
            'tutoriels' => 'Tutoriel',
            'atelier' => 'Atelier',
            'ateliers' => 'Atelier',
            'demonstration' => 'Demonstration',
            'demonstrations' => 'Demonstration',
            'demo' => 'Demonstration',
            'demos' => 'Demonstration',
            'projet' => 'Projet',
            'projets' => 'Projet',
            'robotique' => 'Robotique',
            'impression 3d' => 'Impression 3D',
            'impression3d' => 'Impression 3D',
            'electronique' => 'Electronique',
            'electro' => 'Electronique',
            'programmation' => 'Programmation',
            'developpement' => 'Programmation',
            'dev' => 'Programmation',
            'mecanique' => 'Mecanique',
            'interview' => 'Interview',
            'interviews' => 'Interview',
            'evenement' => 'Evenement',
            'evenements' => 'Evenement',
            'event' => 'Evenement',
            'events' => 'Evenement',
            'autre' => 'Autre',
            'autres' => 'Autre',
        ];

        return self::normaliserSelonMap($categorie, $map, self::videosDisponibles(), false);
    }
}
