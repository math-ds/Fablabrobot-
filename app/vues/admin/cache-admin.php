<?php
$adminTitle = 'Gestion du Cache - Admin FABLAB';
$adminCss = ['admin-cache.css'];

require __DIR__ . '/../parties/admin-layout-start.php';
?>

<section class="dashboard">
    <div class="cache-header">
        <h1>Gestion du Cache</h1>
        <h2 class="sr-only">Sections principales</h2>
        <div class="cache-status <?= $actif ? 'actif' : 'inactif' ?>">
            <span id="status-indicator"><?= $actif ? 'Actif' : 'Inactif' ?></span>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Entrees</h3>
            <p class="value"><?= $stats['total_entrees'] ?? 0 ?></p>
            <p class="subtext">Fichiers de cache</p>
        </div>

        <div class="stat-card">
            <h3>Entrees Valides</h3>
            <p class="value"><?= $stats['entrees_valides'] ?? 0 ?></p>
            <p class="subtext">Non expirees</p>
        </div>

        <div class="stat-card">
            <h3>Entrees Expirees</h3>
            <p class="value"><?= $stats['entrees_expirees'] ?? 0 ?></p>
            <p class="subtext">A nettoyer</p>
        </div>

        <div class="stat-card">
            <h3>Taille Totale</h3>
            <p class="value"><?= $stats['taille_formatee'] ?? '0 Ko' ?></p>
            <p class="subtext">Espace utilise</p>
        </div>
    </div>

    <div class="actions-section">
        <h2>Actions Rapides</h2>
        <div class="actions-grid">
            <div class="action-card">
                <h3><i class="fas fa-sync-alt"></i> Rafraichir les Stats</h3>
                <p>Actualiser les statistiques du cache en temps reel</p>
                <button type="button" class="btn btn-primary" data-cache-action="refresh">
                    Rafraichir
                </button>
            </div>

            <div class="action-card">
                <h3><i class="fas fa-broom"></i> Nettoyer le Cache</h3>
                <p>Supprime uniquement les entrees expirees</p>
                <button type="button" class="btn btn-primary" data-cache-action="clean">
                    Nettoyer
                </button>
            </div>

            <div class="action-card">
                <h3><i class="fas fa-trash"></i> Vider le Cache</h3>
                <p>Supprime toutes les entrees du cache (irreversible)</p>
                <button type="button" class="btn btn-primary" data-cache-action="clear">
                    Vider Tout
                </button>
            </div>

            <div class="action-card">
                <h3><i class="fas fa-power-off"></i> Basculer l'etat</h3>
                <p>Active ou desactive le systeme de cache</p>
                <button type="button" class="btn btn-secondary" data-cache-action="toggle" id="btn-basculer">
                    <?= $actif ? 'Desactiver' : 'Activer' ?>
                </button>
            </div>
        </div>
    </div>
</section>

<?php
$adminScripts = [
    'js/toast-notification.js',
    'js/admin-cache.js',
];
require __DIR__ . '/../parties/admin-layout-end.php';
?>
