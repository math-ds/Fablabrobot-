<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
require_once __DIR__ . '/../../helpers/CsrfHelper.php';

$authTitle = $authTitle ?? 'Espace utilisateur - FABLAB';
$authCss = isset($authCss) && is_array($authCss) ? $authCss : ['utilisateurs.css', 'header-auth.css'];

$styles = [
    'css/typographie.css',
    'css/scss-build/main.css',
];
foreach ($authCss as $stylePath) {
    $path = trim((string)$stylePath);
    if ($path === '') {
        continue;
    }

    if (!str_starts_with($path, 'css/')) {
        $path = 'css/' . ltrim($path, '/');
    }

    if (!in_array($path, $styles, true)) {
        $styles[] = $path;
    }
}

function public_auth_style_version(string $stylePath): string
{
    if (preg_match('#^(https?:)?//#', $stylePath)) {
        return '';
    }

    $relativePath = ltrim($stylePath, '/');
    if (!str_starts_with($relativePath, 'css/')) {
        $relativePath = 'css/' . $relativePath;
    }

    $absolutePath = dirname(__DIR__, 3) . '/public/' . $relativePath;
    if (!is_file($absolutePath)) {
        return '';
    }

    $mtime = @filemtime($absolutePath);
    if ($mtime === false) {
        return '';
    }

    return '?v=' . (string)$mtime;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string)$authTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <?= CsrfHelper::obtenirMetaJeton(); ?>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($baseUrl . 'images/global/AJC_FRW_bleu_simple.png', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($baseUrl . 'images/global/AJC_FRW_bleu_simple.png', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php foreach ($styles as $stylePath): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl . $stylePath . public_auth_style_version($stylePath), ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</head>
<body>
<?php require __DIR__ . '/header-auth.php'; ?>
