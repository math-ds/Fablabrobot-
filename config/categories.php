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
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
            'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y',
            'œ' => 'oe', 'æ' => 'ae',
            "'" => ' ', '’' => ' ',
            'ï¿½' => 'e',
            'ã©' => 'e', 'ã¨' => 'e', 'ãª' => 'e', 'ã«' => 'e',
            'ã‰' => 'e', 'ã´' => 'o', 'ã®' => 'i', 'ã¯' => 'i', 'ã ' => 'a',
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
            'Electronique',
            'Programmation',
            'Impression 3D',
            'Mecanique',
            'Conception',
            'Intelligence Artificielle',
            'Autre',
        ];
    }

    public static function normaliserArticle(string $categorie): ?string
    {
        $map = [
            'robotique' => 'Robotique',
            'electronique' => 'Electronique',
            'programmation' => 'Programmation',
            'impression 3d' => 'Impression 3D',
            'mecanique' => 'Mecanique',
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
            'Electronique',
            'Programmation',
            'Mecanique',
            'Autre',
        ];
    }

    public static function normaliserProjet(string $categorie): ?string
    {
        $map = [
            'robotique' => 'Robotique',
            'drone' => 'Drone / FPV',
            'drone / fpv' => 'Drone / FPV',
            'impression 3d' => 'Impression 3D',
            'electronique' => 'Electronique',
            'programmation' => 'Programmation',
            'mecanique' => 'Mecanique',
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
