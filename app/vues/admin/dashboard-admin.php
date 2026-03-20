<?php
$adminTitle = 'Tableau de bord - AJC Admin';
$adminCss = ['admin-dashboard.css'];

require __DIR__ . '/../parties/admin-layout-start.php';
?>

<section class="dashboard">
    <h1>Bienvenue sur le tableau de bord</h1>

    
    <h2 class="sr-only">Sections principales</h2>
<div class="cards">
        <a href="?page=admin-articles" class="card card-articles">
            <h3><i class="fas fa-newspaper"></i> Articles</h3>
            <p>Gérez vos articles et actualités.</p>
        </a>
        <a href="?page=admin-projets" class="card card-projets">
            <h3><i class="fas fa-project-diagram"></i> Projets</h3>
            <p>Suivez l'avancement des projets.</p>
        </a>
        <a href="?page=admin-utilisateurs" class="card card-utilisateurs">
            <h3><i class="fas fa-users"></i> Utilisateurs</h3>
            <p>Gérez les comptes utilisateurs.</p>
        </a>
        <a href="?page=admin-contact" class="card card-contact">
            <h3><i class="fas fa-envelope"></i> Contact Utilisateurs</h3>
            <p>Gérez les messages reçus des utilisateurs.</p>
        </a>
        <a href="?page=admin-actualites" class="card card-actualites">
            <h3><i class="fas fa-rss"></i> Actualités</h3>
            <p>Gérez les flux RSS et les actualités technologiques.</p>
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

<?php
$adminScripts = [];
require __DIR__ . '/../parties/admin-layout-end.php';
?>
