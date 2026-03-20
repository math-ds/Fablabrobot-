<?php
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
require_once __DIR__ . '/../../helpers/AvatarHelper.php';

$adminTitle = $adminTitle ?? 'Administration FABLAB';
$adminCss = isset($adminCss) && is_array($adminCss) ? $adminCss : [];
$adminAssetBase = $adminAssetBase ?? '';
$adminTopbarTitle = $adminTopbarTitle ?? preg_replace('/\s*-\s*Admin.*$/iu', '', (string)$adminTitle);
$adminTopbarTitle = trim((string)$adminTopbarTitle) !== '' ? (string)$adminTopbarTitle : 'Administration';
$adminTopbarSubtitle = $adminTopbarSubtitle ?? 'Espace de gestion';
$adminTopbarUser = trim((string)($_SESSION['utilisateur_nom'] ?? 'Administrateur'));
$adminTopbarRole = strtoupper(trim((string)($_SESSION['utilisateur_role'] ?? 'ADMIN')));
$adminBaseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$adminAvatar = AvatarHelper::construireDonnees(
    $adminTopbarUser,
    $_SESSION['utilisateur_photo'] ?? null,
    $adminBaseUrl
);

$baseCss = [
    'css/typographie.css',
    'css/global.css',
    'css/admin-commun.css',
    'css/toast-notification.css',
    'css/pagination.css',
];
$normalizedCss = [];

foreach (array_merge($baseCss, $adminCss) as $cssPath) {
    $path = trim((string)$cssPath);
    if ($path === '') {
        continue;
    }

    if (!str_starts_with($path, 'css/')) {
        $path = 'css/' . ltrim($path, '/');
    }

    if (!in_array($path, $normalizedCss, true)) {
        $normalizedCss[] = $path;
    }
}

function admin_css_version(string $cssPath): string
{
    if (preg_match('#^(https?:)?//#', $cssPath)) {
        return '';
    }

    $relativePath = ltrim($cssPath, '/');
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
    <title><?= htmlspecialchars((string)$adminTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <?= CsrfHelper::obtenirMetaJeton(); ?>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($adminBaseUrl . 'images/global/AJC_FRW_bleu_simple.png', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= htmlspecialchars($adminBaseUrl . 'images/global/AJC_FRW_bleu_simple.png', ENT_QUOTES, 'UTF-8') ?>">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <?php foreach ($normalizedCss as $cssPath): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($adminAssetBase . $cssPath . admin_css_version($cssPath), ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</head>
<body>
<div class="admin-container">
    <aside class="sidebar">
        <div>
            <?php include __DIR__ . '/sidebar.php'; ?>
        </div>
    </aside>

    <main class="main-content">
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <button class="menu-toggle admin-topbar-toggle" type="button" aria-label="Ouvrir le menu admin">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="admin-topbar-heading">
                    <p class="admin-topbar-kicker">Administration</p>
                    <p class="admin-topbar-title"><?= htmlspecialchars((string)$adminTopbarTitle, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="admin-topbar-subtitle"><?= htmlspecialchars((string)$adminTopbarSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <div class="admin-topbar-right">
                <div class="admin-topbar-user">
                    <span class="admin-topbar-avatar" aria-hidden="true">
                        <?php if (!empty($adminAvatar['has_photo']) && !empty($adminAvatar['photo_url'])): ?>
                            <img
                                src="<?= htmlspecialchars((string)$adminAvatar['photo_url'], ENT_QUOTES, 'UTF-8') ?>"
                                alt=""
                                loading="lazy">
                        <?php else: ?>
                            <span class="admin-topbar-avatar-fallback <?= htmlspecialchars((string)($adminAvatar['classe_couleur'] ?? 'avatar-couleur-1'), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string)($adminAvatar['initiales'] ?? 'AD'), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </span>
                    <span class="admin-topbar-user-meta">
                        <span class="admin-topbar-user-name"><?= htmlspecialchars($adminTopbarUser, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="admin-topbar-user-role"><?= htmlspecialchars($adminTopbarRole, ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </div>
                <a href="?page=accueil" class="admin-topbar-link">
                    <i class="fas fa-home"></i>
                    <span>Voir le site</span>
                </a>
                <a href="?page=profil" class="admin-topbar-link">
                    <i class="fas fa-user"></i>
                    <span>Mon profil</span>
                </a>
                <form method="POST" action="?page=logout" class="admin-topbar-logout-form">
                    <?= CsrfHelper::obtenirChampJeton(); ?>
                    <button type="submit" class="admin-topbar-link admin-topbar-link-logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Déconnexion</span>
                    </button>
                </form>
            </div>
        </header>
