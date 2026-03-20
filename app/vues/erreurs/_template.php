<?php
$pageErrorCode = (int)($pageErrorCode ?? 500);
$pageErrorTitle = (string)($pageErrorTitle ?? 'Erreur');
$pageErrorMessage = (string)($pageErrorMessage ?? 'Une erreur est survenue.');

$errorMainCss = '/Fablabrobot/public/css/scss-build/main.css';
$errorMainCssFile = dirname(__DIR__, 3) . '/public/css/scss-build/main.css';
$errorMainCssVersion = is_file($errorMainCssFile) ? '?v=' . (string)@filemtime($errorMainCssFile) : '';
$errorTypoCss = '/Fablabrobot/public/css/typographie.css';
$errorTypoCssFile = dirname(__DIR__, 3) . '/public/css/typographie.css';
$errorTypoCssVersion = is_file($errorTypoCssFile) ? '?v=' . (string)@filemtime($errorTypoCssFile) : '';
$errorPageCss = '/Fablabrobot/public/css/error-pages.css';
$errorPageCssFile = dirname(__DIR__, 3) . '/public/css/error-pages.css';
$errorPageCssVersion = is_file($errorPageCssFile) ? '?v=' . (string)@filemtime($errorPageCssFile) : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageErrorTitle, ENT_QUOTES, 'UTF-8') ?> - FABLAB</title>
    <link rel="icon" type="image/png" href="/Fablabrobot/public/images/global/AJC_FRW_bleu_simple.png">
    <link rel="shortcut icon" type="image/png" href="/Fablabrobot/public/images/global/AJC_FRW_bleu_simple.png">
    <link rel="stylesheet" href="<?= htmlspecialchars($errorTypoCss . $errorTypoCssVersion, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($errorMainCss . $errorMainCssVersion, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($errorPageCss . $errorPageCssVersion, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="error-page error-page-<?= (int)$pageErrorCode ?>">
    <main class="error-container" role="main" aria-labelledby="error-title">
        <h1 id="error-title" class="error-code"><?= $pageErrorCode ?> - <?= htmlspecialchars($pageErrorTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="error-message"><?= htmlspecialchars($pageErrorMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <a class="error-button" href="?page=accueil">Retour a l'accueil</a>
    </main>
</body>
</html>
