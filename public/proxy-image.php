<?php

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');


if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    die('URL manquante');
}

$imageUrl = $_GET['url'];


if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    die('URL invalide');
}


$ch = curl_init($imageUrl);


curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    CURLOPT_HTTPHEADER => [
        'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
        'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
        'Referer: https://www.google.com/',
    ],
]);


$imageData = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);


if ($httpCode !== 200 || !$imageData) {
    http_response_code(404);
    die('Impossible de charger l\'image');
}


if ($contentType) {
    header('Content-Type: ' . $contentType);
} else {
    header('Content-Type: image/jpeg'); 
}


echo $imageData;
