<?php
// ===============================
// VUE : app/vues/admin/dashboard-admin.php
// ===============================
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - AJC Admin</title>

    <!-- Meta CSRF pour AJAX -->
    <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirMetaJeton(); ?>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/admin-common.css">
    <link rel="stylesheet" href="css/admin-dashboard.css">
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
            <h1>
                <i class="fas fa-home"></i>
                Bienvenue sur le tableau de bord
            </h1>

            <div class="cards">
                <a href="?page=admin-articles" class="card card-articles">
                    <h3><i class="fas fa-newspaper"></i> Articles</h3>
                    <p>Gérez vos articles et actualités.</p>
                </a>
                <a href="?page=admin-projets" class="card card-projets">
                    <h3><i class="fas fa-project-diagram"></i> Projets</h3>
                    <p>Suivez l'avancement des projets.</p>
                </a>
                <a href="?page=utilisateurs-admin" class="card card-utilisateurs">
                    <h3><i class="fas fa-users"></i> Utilisateurs</h3>
                    <p>Gérez les comptes utilisateurs.</p>
                </a>
                <a href="?page=admin-contact" class="card card-contact">
                    <h3><i class="fas fa-envelope"></i> Contact Utilisateurs</h3>
                    <p>Gérez les messages reçus des utilisateurs.</p>
                </a>
                <a href="?page=admin-webtv" class="card card-webtv">
                    <h3><i class="fas fa-video"></i> WebTV</h3>
                    <p>Ajoutez ou gérez vos vidéos.</p>
                </a>
                <a href="?page=admin-comments" class="card card-commentaires">
                    <h3><i class="fas fa-comments"></i> Commentaires</h3>
                    <p>Gérez les commentaires des utilisateurs dans la partie WebTV.</p>
                </a>
                <a href="?page=admin-cache" class="card card-cache">
                    <h3><i class="fas fa-database"></i> Cache</h3>
                    <p>Gérez le cache du système et optimisez les performances.</p>
                </a>
                <a href="?page=admin-corbeille" class="card card-corbeille">
                    <h3><i class="fas fa-trash-alt"></i> Corbeille</h3>
                    <p>Gérez les éléments supprimés et la restauration.</p>
                </a>
            </div>
        </section>
    </main>
</div>

<!-- JavaScript menu mobile -->
<script src="js/admin-mobile-menu.js"></script>

</body>
</html>
