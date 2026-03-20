<?php

require_once __DIR__ . '/../app/helpers/ImageProxyHelper.php';

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('X-Content-Type-Options: nosniff');

$imageUrl = (string)($_GET['url'] ?? '');
$resultat = ImageProxyHelper::recupererImage($imageUrl);

if (!$resultat['success']) {
    http_response_code((int)$resultat['status']);
    echo (string)$resultat['message'];
    exit;
}

header('Content-Type: ' . (string)$resultat['mime']);
echo (string)$resultat['data'];
