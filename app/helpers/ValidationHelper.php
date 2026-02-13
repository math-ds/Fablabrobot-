<?php
/**
 * Helper de validation et sanitisation
 *
 * Fournit des méthodes centralisées pour valider et nettoyer
 * toutes les entrées utilisateur de manière sécurisée
 *
 * @author Fablabrobot
 * @version 1.0.0
 */
class ValidationHelper
{
    /**
     * Valide une chaîne de caractères avec longueur min/max
     *
     * @param string $valeur La valeur à valider
     * @param int $longueurMin Longueur minimale requise
     * @param int $longueurMax Longueur maximale autorisée
     * @param string $nomChamp Nom du champ pour les messages d'erreur
     * @return array ['valid' => bool, 'error' => string|null, 'value' => string]
     */
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

    /**
     * Valide une adresse email
     *
     * @param string $email L'email à valider
     * @return array ['valid' => bool, 'error' => string|null, 'value' => string]
     */
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

        // Vérifier la longueur maximale (254 caractères selon RFC 5321)
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

    /**
     * Valide une URL ou un chemin de fichier local
     *
     * @param string $url L'URL ou le chemin de fichier à valider
     * @param bool $obligatoire Si l'URL est obligatoire
     * @return array ['valid' => bool, 'error' => string|null, 'value' => string]
     */
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

        // Vérifier si c'est un chemin de fichier local (commence par images/ ou contient juste un nom de fichier)
        if (preg_match('/^(images\/|uploads\/|[a-zA-Z0-9_\-\.]+\.(jpg|jpeg|png|gif|webp|svg))/', $url)) {
            return [
                'valid' => true,
                'error' => null,
                'value' => $url
            ];
        }

        // Sinon, valider comme une URL externe
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'valid' => false,
                'error' => "L'URL n'est pas valide.",
                'value' => $url
            ];
        }

        // Vérifier que l'URL utilise HTTP ou HTTPS
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

    /**
     * Valide un fichier image de manière robuste
     * Vérifie le type MIME réel, le contenu, l'extension et la taille
     *
     * @param array $fichier Le tableau $_FILES['nom_champ']
     * @param int $tailleMaxKo Taille maximale en Ko (par défaut 5 Mo = 5120 Ko)
     * @return array ['valid' => bool, 'error' => string|null, 'extension' => string|null]
     */
    public static function validerFichierImage(array $fichier, int $tailleMaxKo = 5120): array
    {
        // Vérifier qu'un fichier a été uploadé
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

        // Vérifier la taille du fichier
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

        // Vérifier que le fichier est une image via getimagesize()
        $infoImage = @getimagesize($fichier['tmp_name']);
        if ($infoImage === false) {
            return [
                'valid' => false,
                'error' => "Le fichier n'est pas une image valide.",
                'extension' => null
            ];
        }

        // Vérifier le type MIME réel avec mime_content_type()
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

        // Extraire l'extension à partir du type MIME (plus sûr que le nom de fichier)
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

    /**
     * Sanitise du contenu HTML
     * Supprime les balises HTML et échappe les caractères spéciaux
     *
     * @param string $texte Le texte à sanitiser
     * @param bool $autoriserBalises Si true, autorise les balises HTML basiques
     * @return string Le texte sanitisé
     */
    public static function assainirHtml(string $texte, bool $autoriserBalises = false): string
    {
        if ($autoriserBalises) {
            // Autoriser seulement les balises sûres
            $balisesAutorisees = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3>';
            $texte = strip_tags($texte, $balisesAutorisees);
        } else {
            // Supprimer toutes les balises HTML
            $texte = strip_tags($texte);
        }

        // Échapper les caractères spéciaux
        return htmlspecialchars($texte, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Valide un rôle utilisateur
     * Vérifie que le rôle fait partie de la whitelist
     *
     * @param string $role Le rôle à valider
     * @return array ['valid' => bool, 'error' => string|null, 'value' => string]
     */
    public static function validerRole(string $role): array
    {
        $rolesAutorises = ['admin', 'editeur', 'utilisateur'];
        $role = strtolower(trim($role));

        // Normaliser "éditeur" (avec accent) en "editeur" (sans accent)
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

    /**
     * Valide un statut de message contact
     * Vérifie que le statut fait partie de la whitelist
     *
     * @param string $statut Le statut à valider
     * @return array ['valid' => bool, 'error' => string|null, 'value' => string]
     */
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

    /**
     * Valide un type de vidéo WebTV
     *
     * @param string $type Le type à valider
     * @return array ['valid' => bool, 'error' => string|null, 'value' => string]
     */
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

    /**
     * Valide un ID YouTube
     *
     * @param string $youtubeId L'ID YouTube à valider
     * @param bool $obligatoire Si l'ID est obligatoire
     * @return array ['valid' => bool, 'error' => string|null, 'value' => string]
     */
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

        // Un ID YouTube fait généralement 11 caractères et contient seulement des lettres, chiffres, - et _
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

    /**
     * Valide une URL YouTube et extrait l'ID de la vidéo
     *
     * @param string $youtubeUrl L'URL YouTube à valider
     * @param bool $obligatoire Si l'URL est obligatoire
     * @return array ['valid' => bool, 'error' => string|null, 'value' => string, 'id' => string|null]
     */
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

        // Vérifier que c'est une URL valide
        if (!filter_var($youtubeUrl, FILTER_VALIDATE_URL)) {
            return [
                'valid' => false,
                'error' => "L'URL YouTube n'est pas valide.",
                'value' => $youtubeUrl,
                'id' => null
            ];
        }

        // Extraire l'ID YouTube de différentes formes d'URL
        $videoId = null;

        // Format: https://www.youtube.com/watch?v=VIDEO_ID
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $youtubeUrl, $matches)) {
            $videoId = $matches[1];
        }
        // Format: https://youtube.com/v/VIDEO_ID
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

        // Valider l'ID extrait
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
