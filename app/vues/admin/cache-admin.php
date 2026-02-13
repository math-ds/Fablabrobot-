<?php
// Protection : vérifier que l'utilisateur est admin
if (!isset($_SESSION['utilisateur_role']) || strtolower($_SESSION['utilisateur_role']) !== 'admin') {
    header('Location: ?page=login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du Cache - Admin FABLAB</title>

    <!-- Meta CSRF pour AJAX -->
    <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirMetaJeton(); ?>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/admin-common.css">
    <link rel="stylesheet" href="css/admin-cache.css">
    <link rel="stylesheet" href="css/toast-notification.css">
</head>
<body>

<div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-logo">
                <a href="?page=admin">
                    <img src="images/global/ajc_logo_blanc.png" alt="AJC Logo">
                </a>
            </div>
            <?php include __DIR__ . '/../parties/sidebar.php'; ?>
        </div>
        <div class="sidebar-footer">
            <a href="?page=logout" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </aside>

    <!-- Contenu principal -->
    <main class="main-content">
        <section class="dashboard">
            <div class="cache-header">
                <h1><i class="fas fa-database"></i> Gestion du Cache</h1>
                <div class="cache-status <?= $actif ? 'actif' : 'inactif' ?>">
                    <span id="status-indicator"><?= $actif ? '✓ Actif' : '✗ Inactif' ?></span>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Entrées</h3>
                    <p class="value"><?= $stats['total_entrees'] ?? 0 ?></p>
                    <p class="subtext">Fichiers de cache</p>
                </div>

                <div class="stat-card">
                    <h3>Entrées Valides</h3>
                    <p class="value"><?= $stats['entrees_valides'] ?? 0 ?></p>
                    <p class="subtext">Non expirées</p>
                </div>

                <div class="stat-card">
                    <h3>Entrées Expirées</h3>
                    <p class="value"><?= $stats['entrees_expirees'] ?? 0 ?></p>
                    <p class="subtext">À nettoyer</p>
                </div>

                <div class="stat-card">
                    <h3>Taille Totale</h3>
                    <p class="value"><?= $stats['taille_formatee'] ?? '0 Ko' ?></p>
                    <p class="subtext">Espace utilisé</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="actions-section">
                <h2>Actions Rapides</h2>
                <div class="actions-grid">
                    <div class="action-card">
                        <h3><i class="fas fa-sync-alt"></i> Rafraîchir les Stats</h3>
                        <p>Actualiser les statistiques du cache en temps réel</p>
                        <button type="button" class="btn btn-primary" onclick="rafraichirStats()">
                            Rafraîchir
                        </button>
                    </div>

                    <div class="action-card">
                        <h3><i class="fas fa-broom"></i> Nettoyer le Cache</h3>
                        <p>Supprime uniquement les entrées expirées</p>
                        <button type="button" class="btn btn-primary" onclick="nettoyerCache()">
                            Nettoyer
                        </button>
                    </div>

                    <div class="action-card">
                        <h3><i class="fas fa-trash"></i> Vider le Cache</h3>
                        <p>Supprime toutes les entrées du cache (irréversible)</p>
                        <button type="button" class="btn btn-primary" onclick="viderCache()">
                            Vider Tout
                        </button>
                    </div>

                    <div class="action-card">
                        <h3><i class="fas fa-power-off"></i> Basculer l'État</h3>
                        <p>Active ou désactive le système de cache</p>
                        <button type="button" class="btn btn-secondary" onclick="basculerCache()" id="btn-basculer">
                            <?= $actif ? 'Désactiver' : 'Activer' ?>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- JavaScript -->
<script src="js/toast-notification.js"></script>
<script src="js/admin-cache.js"></script>
<script src="js/admin-mobile-menu.js"></script>

</body>
</html>
