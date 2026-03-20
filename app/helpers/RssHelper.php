<?php


class RssHelper
{
    private const HTTP_TIMEOUT = 15;
    private const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0 Safari/537.36';

    
    public static function recupererFlux(string $url, int $limit = 20, bool $requireImage = false, ?string $sourceName = null): array
    {
        $articles = [];

        try {
            
            libxml_use_internal_errors(true);

            
            $xml = self::recupererContenuFlux($url);

            if ($xml === false) {
                error_log("Erreur lors de la récupération du flux RSS: $url");
                return [];
            }

            $xml = trim((string)$xml);
            if (strncmp($xml, "\xEF\xBB\xBF", 3) === 0) {
                $xml = substr($xml, 3);
            }

            
            $feed = simplexml_load_string($xml);

            if ($feed === false) {
                error_log("Erreur lors du parsing du flux RSS: $url");
                return [];
            }

            
            $items = isset($feed->channel->item) ? $feed->channel->item : $feed->entry;

            $count = 0;
            foreach ($items as $item) {
                if ($count >= $limit) {
                    break;
                }

                $article = self::parserItem($item, $feed, $sourceName);

                
                if ($article) {
                    if ($requireImage && empty($article['image_url'])) {
                        continue; 
                    }
                    $articles[] = $article;
                    $count++;
                }
            }

            libxml_clear_errors();

        } catch (Exception $e) {
            error_log("Exception lors de la récupération du flux RSS: " . $e->getMessage());
        }

        return $articles;
    }

    
    private static function parserItem($item, $feed, ?string $sourceName = null): ?array
    {
        $namespaces = $item->getNamespaces(true);

        
        $titre = (string)($item->title ?? '');
        if (empty($titre)) {
            return null;
        }

        
        $description = (string)($item->description ?? $item->summary ?? '');
        $descriptionBrute = $description; 
        $description = strip_tags($description);
        $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        
        $contenu = '';
        if (isset($namespaces['content'])) {
            $content = $item->children($namespaces['content']);
            $contenu = (string)($content->encoded ?? '');
        }
        if (empty($contenu)) {
            $contenu = $description;
        }

        
        $contenuBrut = $contenu;
        $contenu = strip_tags($contenu);
        $contenu = html_entity_decode($contenu, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        
        $url = (string)($item->link ?? '');
        if (isset($item->link['href'])) {
            $url = (string)$item->link['href'];
        }

        
        $imageUrl = '';

        
        if (isset($namespaces['media'])) {
            $media = $item->children($namespaces['media']);
            if (isset($media->thumbnail)) {
                $imageUrl = (string)$media->thumbnail['url'];
            } elseif (isset($media->content)) {
                $imageUrl = (string)$media->content['url'];
            }
        }

        
        if (empty($imageUrl) && isset($item->enclosure['url'])) {
            $type = (string)($item->enclosure['type'] ?? '');
            if (str_starts_with($type, 'image/')) {
                $imageUrl = (string)$item->enclosure['url'];
            }
        }

        
        if (empty($imageUrl) && isset($item->image)) {
            $imageUrl = (string)$item->image;
        }

        
        if (empty($imageUrl)) {
            
            if (isset($namespaces['media'])) {
                $media = $item->children($namespaces['media']);
                if (isset($media->group->content)) {
                    $imageUrl = (string)$media->group->content['url'];
                }
            }
        }

        
        if (empty($imageUrl) && !empty($contenuBrut)) {
            $imageUrl = self::extrairePremiereImage($contenuBrut);
        }

        
        if (empty($imageUrl) && !empty($descriptionBrute)) {
            $imageUrl = self::extrairePremiereImage($descriptionBrute);
        }

        
        $publishedAt = null;
        $dateStr = (string)($item->pubDate ?? $item->published ?? $item->updated ?? '');
        if (!empty($dateStr)) {
            $timestamp = strtotime($dateStr);
            if ($timestamp !== false) {
                $publishedAt = date('Y-m-d H:i:s', $timestamp);
            }
        }

        
        $auteur = (string)($item->author ?? $item->creator ?? '');
        if (isset($namespaces['dc'])) {
            $dc = $item->children($namespaces['dc']);
            $auteur = (string)($dc->creator ?? $auteur);
        }

        
        $source = trim((string)$sourceName);
        if ($source === '') {
            $source = (string)($feed->channel->title ?? $feed->title ?? 'Inconnu');
        }

        
        
        $texteDescription = $description;
        if (strlen($texteDescription) < 200 && strlen($contenu) > strlen($description)) {
            $texteDescription = $contenu;
        }

        return [
            'titre' => trim($titre),
            'description' => trim(substr($texteDescription, 0, 800)), 
            'contenu' => trim(substr($contenu, 0, 3000)), 
            'source' => trim($source),
            'url_source' => self::normaliserUrl(trim($url)), 
            'image_url' => trim($imageUrl),
            'auteur' => trim($auteur),
            'published_at' => $publishedAt ?? date('Y-m-d H:i:s')
        ];
    }

    
    private static function extrairePremiereImage(string $html): string
    {
        
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            $imgSrc = $matches[1];

            
            if (self::estImageValide($imgSrc)) {
                return $imgSrc;
            }
        }

        
        if (preg_match('/<figure[^>]*>.*?<img[^>]+src=["\']([^"\']+)["\'][^>]*>.*?<\/figure>/is', $html, $matches)) {
            if (isset($matches[1]) && self::estImageValide($matches[1])) {
                return $matches[1];
            }
        }

        return '';
    }

    
    private static function estImageValide(string $url): bool
    {
        
        if (empty($url)) {
            return false;
        }

        
        if (str_starts_with($url, 'data:')) {
            return false;
        }

        
        $trackersIgnores = ['feedburner', 'doubleclick', 'pixel', 'tracker', 'analytics', '1x1'];
        foreach ($trackersIgnores as $tracker) {
            if (stripos($url, $tracker) !== false) {
                return false;
            }
        }

        
        $extensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg'];
        foreach ($extensions as $ext) {
            if (stripos($url, $ext) !== false) {
                return true;
            }
        }

        
        return str_starts_with($url, 'http');
    }

    
    private static function normaliserUrl(string $url): string
    {
        
        $url = preg_replace('/[?&](utm_[^&]+|fbclid=[^&]+|xtor=[^&]+)/i', '', $url);

        
        $url = rtrim($url, '?&');

        
        $url = str_replace('http://', 'https://', $url);

        return $url;
    }

    
    public static function recupererMultiplesFlux(array $feeds, int $limitParFlux = 10, bool $requireImage = false): array
    {
        $tousLesArticles = [];

        foreach ($feeds as $nom => $url) {
            $articles = self::recupererFlux($url, $limitParFlux, $requireImage, (string)$nom);
            $tousLesArticles = array_merge($tousLesArticles, $articles);
        }

        
        usort($tousLesArticles, function($a, $b) {
            return strtotime($b['published_at']) - strtotime($a['published_at']);
        });

        return $tousLesArticles;
    }

    
    private static function recupererContenuFlux(string $url)
    {
        $essais = [
            ['stream', false],
            ['stream', true],
            ['curl', false],
            ['curl', true],
        ];

        foreach ($essais as [$methode, $sslAssoupli]) {
            $contenu = $methode === 'stream'
                ? self::recupererViaStream($url, $sslAssoupli)
                : self::recupererViaCurl($url, $sslAssoupli);

            if (!is_string($contenu) || trim($contenu) === '') {
                continue;
            }

            if (self::ressembleAFluxXml($contenu)) {
                return $contenu;
            }
        }

        return false;
    }

    
    private static function recupererViaStream(string $url, bool $sslAssoupli)
    {
        $httpHeaders = [
            'User-Agent: ' . self::DEFAULT_USER_AGENT,
            'Accept: application/rss+xml, application/xml;q=0.9, */*;q=0.8',
            'Accept-Encoding: gzip, deflate',
            'Connection: close',
        ];

        $contextOptions = [
            'http' => [
                'method' => 'GET',
                'timeout' => self::HTTP_TIMEOUT,
                'follow_location' => 1,
                'max_redirects' => 5,
                'ignore_errors' => true,
                'header' => implode("\r\n", $httpHeaders),
            ],
        ];

        if (str_starts_with(strtolower($url), 'https://')) {
            $contextOptions['ssl'] = [
                'verify_peer' => !$sslAssoupli,
                'verify_peer_name' => !$sslAssoupli,
                'allow_self_signed' => $sslAssoupli,
            ];
        }

        $context = stream_context_create($contextOptions);
        return @file_get_contents($url, false, $context);
    }

    
    private static function recupererViaCurl(string $url, bool $sslAssoupli)
    {
        if (!function_exists('curl_init')) {
            return false;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => self::HTTP_TIMEOUT,
            CURLOPT_USERAGENT => self::DEFAULT_USER_AGENT,
            CURLOPT_HTTPHEADER => [
                'Accept: application/rss+xml, application/xml;q=0.9, */*;q=0.8',
            ],
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => !$sslAssoupli,
            CURLOPT_SSL_VERIFYHOST => $sslAssoupli ? 0 : 2,
        ];

        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!is_string($body) || $body === '' || $httpCode >= 400 || $curlError !== '') {
            return false;
        }

        return $body;
    }

    
    private static function ressembleAFluxXml(string $content): bool
    {
        $sample = strtolower(ltrim(substr($content, 0, 2048)));
        if ($sample === '') {
            return false;
        }

        if (str_contains($sample, '<html') && !str_contains($sample, '<rss') && !str_contains($sample, '<feed')) {
            return false;
        }

        return str_contains($sample, '<rss')
            || str_contains($sample, '<feed')
            || str_contains($sample, '<?xml');
    }
}
