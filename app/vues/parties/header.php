<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
require_once __DIR__ . '/../../helpers/AvatarHelper.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';

$utilisateurConnecte = isset($_SESSION['utilisateur_id']);
$nomUtilisateur = $utilisateurConnecte ? $_SESSION['utilisateur_nom'] : '';
$roleUtilisateur = $utilisateurConnecte ? ($_SESSION['utilisateur_role'] ?? 'Utilisateur') : '';
$photoUtilisateur = $utilisateurConnecte ? ($_SESSION['utilisateur_photo'] ?? null) : null;

$avatarUtilisateur = AvatarHelper::construireDonnees($nomUtilisateur, $photoUtilisateur, $baseUrl);

?>
  <header>
    <nav class="barre-navigation" role="navigation" aria-label="Navigation principale">
      <div class="conteneur-navigation">

        <button class="bouton-menu" id="boutonMenu" aria-label="Ouvrir le menu de navigation" aria-expanded="false" aria-controls="menuMobile">
          <i class="fas fa-bars"></i>
        </button>

        <a href="?page=accueil" class="logo-entete">
          <img src="<?= $baseUrl ?>images/global/AJC_FRW_bleu_simple.png" alt="Logo Fablab Robotique - Retour à l'accueil">
        </a>

        <div class="liens-navigation">
          <a href="?page=accueil" class="lien-nav">Accueil</a>
          <a href="?page=articles" class="lien-nav">Articles</a>
          <a href="?page=actualites" class="lien-nav">Actualités</a>
          <a href="?page=projets" class="lien-nav">Projets</a>
          <a href="?page=webtv" class="lien-nav">WebTV</a>
          <a href="?page=contact" class="lien-nav">Contact</a>
        </div>

        <div class="liens-authentification">
          <?php if ($utilisateurConnecte): ?>
            <div class="profil-container">
              <button class="profil-trigger" id="profilTrigger" aria-label="Ouvrir le menu profil" aria-expanded="false">
                <?php if ($avatarUtilisateur['has_photo']): ?>
                  <img src="<?= htmlspecialchars($avatarUtilisateur['photo_url']) ?>" class="user-avatar" alt="Photo de profil de <?= htmlspecialchars($nomUtilisateur) ?>">
                <?php else: ?>
                  <div class="initials-avatar <?= htmlspecialchars((string)($avatarUtilisateur['classe_couleur'] ?? 'avatar-couleur-1'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($avatarUtilisateur['initiales']); ?>
                  </div>
                <?php endif; ?>
                <div class="profil-info">
                  <p class="profil-nom"><?= htmlspecialchars(explode(' ', $nomUtilisateur)[0]); ?></p>
                  <p class="profil-role"><?= htmlspecialchars(strtoupper($roleUtilisateur)); ?></p>
                </div>
              </button>

              <div class="profil-dropdown" id="profilDropdown">
                <div class="profil-dropdown-header">
                  <p class="profil-nom"><?= htmlspecialchars($nomUtilisateur); ?></p>
                  <p class="profil-role"><?= htmlspecialchars(strtoupper($roleUtilisateur)); ?></p>
                </div>
                <ul class="profil-dropdown-menu">
                  <li><a href="?page=profil" class="profil-dropdown-item"><i class="fas fa-user"></i> Mon profil</a></li>
                  <li><a href="?page=favoris" class="profil-dropdown-item"><i class="fas fa-heart"></i> Mes favoris</a></li>
                  <?php if (RoleHelper::estAdmin($roleUtilisateur)): ?>
                    <li><a href="?page=admin" class="profil-dropdown-item"><i class="fas fa-shield-alt"></i> Tableau de bord</a></li>
                  <?php endif; ?>
                  <li class="profil-dropdown-divider"></li>
                  <li>
                    <form method="POST" action="?page=logout" class="m-0">
                      <?= CsrfHelper::obtenirChampJeton(); ?>
                      <a href="#" class="profil-dropdown-item danger" data-submit-parent-form="1" role="button" aria-label="Se déconnecter"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    </form>
                  </li>
                </ul>
              </div>
            </div>
          <?php else: ?>
            <a href="?page=login" class="lien-nav">Se connecter</a>
            <a href="?page=inscription" class="lien-nav btn-inscription">S'inscrire</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="menu-mobile" id="menuMobile" role="navigation" aria-label="Navigation mobile" aria-hidden="true" inert>
        <a href="?page=accueil" class="lien-nav">Accueil</a>
        <a href="?page=articles" class="lien-nav">Articles</a>
        <a href="?page=actualites" class="lien-nav">Actualités</a>
        <a href="?page=projets" class="lien-nav">Projets</a>
        <a href="?page=webtv" class="lien-nav">WebTV</a>
        <a href="?page=contact" class="lien-nav">Contact</a>

        <div class="menu-mobile-auth">
          <?php if ($utilisateurConnecte): ?>
            <div class="profil-mobile">
              <?php if ($avatarUtilisateur['has_photo']): ?>
                <img src="<?= htmlspecialchars($avatarUtilisateur['photo_url']) ?>" class="user-avatar-mobile" alt="Photo de profil">
              <?php else: ?>
                <div class="initials-avatar-mobile <?= htmlspecialchars((string)($avatarUtilisateur['classe_couleur'] ?? 'avatar-couleur-1'), ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars($avatarUtilisateur['initiales']); ?>
                </div>
              <?php endif; ?>
              <div>
                <p class="profil-nom-mobile"><?= htmlspecialchars($nomUtilisateur); ?></p>
                <p class="profil-role-mobile"><?= htmlspecialchars(strtoupper($roleUtilisateur)); ?></p>
              </div>
            </div>
            <a href="?page=profil" class="lien-nav">Mon profil</a>
            <a href="?page=favoris" class="lien-nav">Mes favoris</a>
            <?php if (RoleHelper::estAdmin($roleUtilisateur)): ?>
              <a href="?page=admin" class="lien-nav">Tableau de bord</a>
            <?php endif; ?>
            <form method="POST" action="?page=logout" class="m-0 w-full">
              <?= CsrfHelper::obtenirChampJeton(); ?>
              <a href="#" class="lien-nav btn-inscription" data-submit-parent-form="1" role="button" aria-label="Se déconnecter">Déconnexion</a>
            </form>
          <?php else: ?>
            <a href="?page=login" class="lien-nav">Se connecter</a>
            <a href="?page=inscription" class="lien-nav btn-inscription">S'inscrire</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="menu-mobile-backdrop" id="menuMobileBackdrop" aria-hidden="true"></div>
    </nav>
  </header>
