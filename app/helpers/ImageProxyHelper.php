<?php

class ImageProxyHelper
{
    private const ALLOWED_SCHEMES = ['http', 'https'];
    private const MAX_IMAGE_BYTES = 5242880; 

    public static function recupererImage(string $imageUrl): array
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return self::erreur(400, 'URL manquante');
        }

        $validation = self::validerUrl($imageUrl);
        if ($validation !== null) {
            return $validation;
        }

        $host = (string)parse_url($imageUrl, PHP_URL_HOST);
        if (!self::hostAutorise($host)) {
            return self::erreur(403, 'Host interdit');
        }

        $ch = curl_init($imageUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'Fablabrobot-ImageProxy/1.0',
            CURLOPT_HTTPHEADER => [
                'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
            ],
        ]);

        $imageData = curl_exec($ch);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($ch);
        curl_close($ch);

        if ($curlError !== 0 || $httpCode !== 200 || $imageData === false || $imageData === '') {
            return self::erreur(404, 'Impossible de charger l\'image');
        }

        if (strlen($imageData) > self::MAX_IMAGE_BYTES) {
            return self::erreur(413, 'Image trop volumineuse');
        }

        $mime = self::normaliserMime($contentType);
        if ($mime === '' || strpos($mime, 'image/') !== 0) {
            return self::erreur(415, 'Type de contenu non supporte');
        }

        return [
            'success' => true,
            'status' => 200,
            'mime' => $mime,
            'data' => $imageData,
            'message' => '',
        ];
    }

    private static function validerUrl(string $imageUrl): ?array
    {
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return self::erreur(400, 'URL invalide');
        }

        $parsed = parse_url($imageUrl);
        $scheme = strtolower((string)($parsed['scheme'] ?? ''));
        $host = (string)($parsed['host'] ?? '');

        if (!in_array($scheme, self::ALLOWED_SCHEMES, true) || $host === '') {
            return self::erreur(400, 'URL invalide');
        }

        return null;
    }

    private static function hostAutorise(string $host): bool
    {
        $host = strtolower(trim($host));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local')) {
            return false;
        }

        $ips = @gethostbynamel($host);
        if (!is_array($ips) || $ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    private static function normaliserMime(string $contentType): string
    {
        $parts = explode(';', $contentType);
        return strtolower(trim((string)($parts[0] ?? '')));
    }

    private static function erreur(int $status, string $message): array
    {
        return [
            'success' => false,
            'status' => $status,
            'mime' => '',
            'data' => '',
            'message' => $message,
        ];
    }
}

