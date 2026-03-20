<?php
require_once __DIR__ . '/RoleHelper.php';

class ValidationHelper
{
    
    public static function validerChaine(string $valeur, int $longueurMin, int $longueurMax, string $nomChamp): array
    {
        $valeur = trim($valeur);

        if (empty($valeur)) {
            return [
                'valid' => false,
                'error' => "Le champ $nomChamp est obligatoire.",
                'value' => ''
            ];
        }

        $longueur = mb_strlen($valeur, 'UTF-8');

        if ($longueur < $longueurMin) {
            return [
                'valid' => false,
                'error' => "Le champ $nomChamp doit contenir au moins $longueurMin caractères.",
                'value' => $valeur
            ];
        }

        if ($longueur > $longueurMax) {
            return [
                'valid' => false,
                'error' => "Le champ $nomChamp ne peut pas dépasser $longueurMax caractères.",
                'value' => $valeur
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'value' => $valeur
        ];
    }

    
    public static function validerEmail(string $email): array
    {
        $email = trim($email);

        if (empty($email)) {
            return [
                'valid' => false,
                'error' => "L'adresse email est obligatoire.",
                'value' => ''
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'error' => "L'adresse email n'est pas valide.",
                'value' => $email
            ];
        }

        
        if (mb_strlen($email, 'UTF-8') > 254) {
            return [
                'valid' => false,
                'error' => "L'adresse email est trop longue.",
                'value' => $email
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'value' => strtolower($email)
        ];
    }

    
    public static function validerUrl(string $url, bool $obligatoire = false): array
    {
        $url = trim($url);

        if (empty($url)) {
            if ($obligatoire) {
                return [
                    'valid' => false,
                    'error' => "L'URL est obligatoire.",
                    'value' => ''
                ];
            }
            return [
                'valid' => true,
                'error' => null,
                'value' => ''
            ];
        }

        
        if (preg_match('/^(images\/|uploads\/|[a-zA-Z0-9_\-\.]+\.(jpg|jpeg|png|gif|webp|svg))/', $url)) {
            return [
                'valid' => true,
                'error' => null,
                'value' => $url
            ];
        }

        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'valid' => false,
                'error' => "L'URL n'est pas valide.",
                'value' => $url
            ];
        }

        
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) {
            return [
                'valid' => false,
                'error' => "L'URL doit utiliser HTTP ou HTTPS.",
                'value' => $url
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'value' => $url
        ];
    }

    
    public static function validerFichierImage(array $fichier, int $tailleMaxKo = 5120): array
    {
        
        if (empty($fichier['tmp_name']) || $fichier['error'] !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => "Le fichier dépasse la taille maximale autorisée par le serveur.",
                UPLOAD_ERR_FORM_SIZE => "Le fichier dépasse la taille maximale autorisée.",
                UPLOAD_ERR_PARTIAL => "Le fichier n'a été que partiellement téléchargé.",
                UPLOAD_ERR_NO_FILE => "Aucun fichier n'a été téléchargé.",
                UPLOAD_ERR_NO_TMP_DIR => "Dossier temporaire manquant sur le serveur.",
                UPLOAD_ERR_CANT_WRITE => "Échec de l'écriture du fichier sur le disque.",
                UPLOAD_ERR_EXTENSION => "Une extension PHP a arrêté l'upload du fichier."
            ];

            $erreur = $messages[$fichier['error']] ?? "Erreur inconnue lors de l'upload.";

            return [
                'valid' => false,
                'error' => $erreur,
                'extension' => null
            ];
        }

        
        $tailleOctets = filesize($fichier['tmp_name']);
        $tailleMaxOctets = $tailleMaxKo * 1024;

        if ($tailleOctets > $tailleMaxOctets) {
            $tailleMaxMo = round($tailleMaxKo / 1024, 1);
            return [
                'valid' => false,
                'error' => "Le fichier ne doit pas dépasser $tailleMaxMo Mo.",
                'extension' => null
            ];
        }

        
        $infoImage = @getimagesize($fichier['tmp_name']);
        if ($infoImage === false) {
            return [
                'valid' => false,
                'error' => "Le fichier n'est pas une image valide.",
                'extension' => null
            ];
        }

        
        $mimeReel = mime_content_type($fichier['tmp_name']);
        $mimesAutorises = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp'
        ];

        if (!in_array($mimeReel, $mimesAutorises)) {
            return [
                'valid' => false,
                'error' => "Type de fichier non autorisé. Formats acceptés : JPEG, PNG, GIF, WebP.",
                'extension' => null
            ];
        }

        
        $extensionsParMime = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];

        $extension = $extensionsParMime[$mimeReel] ?? 'jpg';

        return [
            'valid' => true,
            'error' => null,
            'extension' => $extension
        ];
    }

    
    public static function assainirHtml(string $texte, bool $autoriserBalises = false): string
    {
        if ($autoriserBalises) {
            
            $balisesAutorisees = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3>';
            $texte = strip_tags($texte, $balisesAutorisees);
        } else {
            
            $texte = strip_tags($texte);
        }

        
        return htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
    }

    
    public static function validerRole(string $role): array
    {
        $rolesAutorises = [RoleHelper::ADMIN, RoleHelper::EDITEUR, RoleHelper::UTILISATEUR];
        $role = RoleHelper::normaliser($role);

        
        if ($role === 'éditeur') {
            $role = 'editeur';
        }

        if (empty($role)) {
            return [
                'valid' => false,
                'error' => "Le rôle est obligatoire.",
                'value' => ''
            ];
        }

        if (!in_array($role, $rolesAutorises)) {
            return [
                'valid' => false,
                'error' => "Le rôle spécifié n'est pas valide.",
                'value' => $role
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'value' => $role
        ];
    }

    
    public static function validerStatutContact(string $statut): array
    {
        $statutsAutorises = ['non_lu', 'lu', 'traite'];
        $statut = strtolower(trim($statut));

        if (empty($statut)) {
            return [
                'valid' => false,
                'error' => "Le statut est obligatoire.",
                'value' => ''
            ];
        }

        if (!in_array($statut, $statutsAutorises)) {
            return [
                'valid' => false,
                'error' => "Le statut spécifié n'est pas valide.",
                'value' => $statut
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'value' => $statut
        ];
    }

    
    public static function validerTypeVideo(string $type): array
    {
        $typesAutorises = ['local', 'youtube'];
        $type = strtolower(trim($type));

        if (empty($type)) {
            return [
                'valid' => false,
                'error' => "Le type de vidéo est obligatoire.",
                'value' => ''
            ];
        }

        if (!in_array($type, $typesAutorises)) {
            return [
                'valid' => false,
                'error' => "Le type de vidéo spécifié n'est pas valide.",
                'value' => $type
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'value' => $type
        ];
    }

    
    public static function validerIdYoutube(string $youtubeId, bool $obligatoire = false): array
    {
        $youtubeId = trim($youtubeId);

        if (empty($youtubeId)) {
            if ($obligatoire) {
                return [
                    'valid' => false,
                    'error' => "L'ID YouTube est obligatoire.",
                    'value' => ''
                ];
            }
            return [
                'valid' => true,
                'error' => null,
                'value' => ''
            ];
        }

        
        if (!preg_match('/^[a-zA-Z0-9_-]{11}$/', $youtubeId)) {
            return [
                'valid' => false,
                'error' => "L'ID YouTube n'est pas valide.",
                'value' => $youtubeId
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'value' => $youtubeId
        ];
    }

    
    public static function validerUrlYoutube(string $youtubeUrl, bool $obligatoire = false): array
    {
        $youtubeUrl = trim($youtubeUrl);

        if (empty($youtubeUrl)) {
            if ($obligatoire) {
                return [
                    'valid' => false,
                    'error' => "L'URL YouTube est obligatoire.",
                    'value' => '',
                    'id' => null
                ];
            }
            return [
                'valid' => true,
                'error' => null,
                'value' => '',
                'id' => null
            ];
        }

        
        if (!filter_var($youtubeUrl, FILTER_VALIDATE_URL)) {
            return [
                'valid' => false,
                'error' => "L'URL YouTube n'est pas valide.",
                'value' => $youtubeUrl,
                'id' => null
            ];
        }

        
        $videoId = null;

        
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $youtubeUrl, $matches)) {
            $videoId = $matches[1];
        }
        
        elseif (preg_match('/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/', $youtubeUrl, $matches)) {
            $videoId = $matches[1];
        }

        if (!$videoId) {
            return [
                'valid' => false,
                'error' => "L'URL YouTube n'est pas reconnue. Utilisez un format comme https://www.youtube.com/watch?v=VIDEO_ID ou https://youtu.be/VIDEO_ID.",
                'value' => $youtubeUrl,
                'id' => null
            ];
        }

        
        $idValidation = self::validerIdYoutube($videoId, true);
        if (!$idValidation['valid']) {
            return [
                'valid' => false,
                'error' => "ID YouTube extrait invalide : " . $idValidation['error'],
                'value' => $youtubeUrl,
                'id' => null
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'value' => $youtubeUrl,
            'id' => $videoId
        ];
    }
}
