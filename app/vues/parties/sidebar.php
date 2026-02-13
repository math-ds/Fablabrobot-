<?php
$current_page = $_GET['page'] ?? 'admin';

function isActive($page, $current_page) {
    return $page === $current_page ? 'active' : '';
}
?>

<nav class="sidebar-nav">
  <a href="?page=admin" class="<?= isActive('admin', $current_page) ?>">
    <i class="fas fa-home"></i> Dashboard
  </a>
  <a href="?page=admin-articles" class="<?= isActive('admin-articles', $current_page) ?>">
    <i class="fas fa-newspaper"></i> Articles
  </a>
  <a href="?page=admin-projets" class="<?= isActive('admin-projets', $current_page) ?>">
    <i class="fas fa-project-diagram"></i> Projets
  </a>
  <a href="?page=utilisateurs-admin" class="<?= isActive('utilisateurs-admin', $current_page) ?>">
    <i class="fas fa-users"></i> Utilisateurs
  </a>
  <a href="?page=admin-contact" class="<?= isActive('admin-contact', $current_page) ?>">
    <i class="fas fa-address-book"></i> Contact
  </a>
  <a href="?page=admin-webtv" class="<?= isActive('admin-webtv', $current_page) ?>">
    <i class="fas fa-video"></i> WebTV
  </a>
  <a href="?page=admin-comments" class="<?= isActive('admin-comments', $current_page) ?>">
    <i class="fas fa-comment"></i> Commentaires
  </a>
  <a href="?page=admin-corbeille" class="<?= isActive('admin-corbeille', $current_page) ?>">
    <i class="fas fa-trash-alt"></i> Corbeille
  </a>
  <a href="?page=admin-cache" class="<?= isActive('admin-cache', $current_page) ?>">
    <i class="fas fa-database"></i> Cache
  </a>
</nav>
