<?php

if (!function_exists('webtvExtractYoutubeId')) {
    function webtvExtractYoutubeId(string $url): ?string
    {
        $patterns = [
            '/(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
            '/(?:https?:\/\/)?(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]{11})/',
            '/(?:https?:\/\/)?(?:www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
            '/(?:https?:\/\/)?(?:www\.)?youtube\.com\/v\/([a-zA-Z0-9_-]{11})/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}

if (!function_exists('webtvThumbnailUrl')) {
    function webtvThumbnailUrl(array $video, string $baseUrl): ?string
    {
        if (!empty($video['type']) && $video['type'] === 'youtube' && !empty($video['youtube_url'])) {
            $youtubeId = webtvExtractYoutubeId((string)$video['youtube_url']);
            if ($youtubeId) {
                return 'https://img.youtube.com/vi/' . rawurlencode($youtubeId) . '/hqdefault.jpg';
            }
        }

        if (!empty($video['vignette'])) {
            $vignette = (string)$video['vignette'];
            if (preg_match('#^https?://#i', $vignette)) {
                return $vignette;
            }
            return $baseUrl . 'uploads/vignettes/' . rawurlencode($vignette);
        }

        return null;
    }
}

if (!function_exists('webtvBuildUrl')) {
    function webtvBuildUrl(array $params = []): string
    {
        $query = ['page' => 'webtv'];

        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null) {
                $query[$key] = $value;
            }
        }

        return '?' . http_build_query($query);
    }
}
