<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';

$utilisateurConnecte = isset($_SESSION['utilisateur_id']);
$nomUtilisateur = $utilisateurConnecte ? $_SESSION['utilisateur_nom'] : '';
$roleUtilisateur = $utilisateurConnecte ? ($_SESSION['utilisateur_role'] ?? 'Utilisateur') : '';
$photoUtilisateur = $utilisateurConnecte ? ($_SESSION['utilisateur_photo'] ?? null) : null;

function genererInitiales($nom)
{
    if (empty($nom)) return 'US';
    $parties = preg_split('/\s+/', trim($nom));
    return (count($parties) >= 2)
        ? strtoupper(substr($parties[0], 0, 1) . substr($parties[count($parties) - 1], 0, 1))
        : strtoupper(substr($parties[0], 0, 2));
}

function genererCouleur($nom)
{
    $hash = abs(crc32($nom));
    $couleurs = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2'];
    return $couleurs[$hash % count($couleurs)];
}

$initiales = genererInitiales($nomUtilisateur);
$couleurAvatar = genererCouleur($nomUtilisateur);

$photoPath = __DIR__ . '/../../../public/uploads/profils/' . $photoUtilisateur;
$hasPhoto = !empty($photoUtilisateur) && file_exists($photoPath);

$titrePage = $titrePage ?? 'AJC FABLAB';
$pageCss = $pageCss ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($titrePage) ?></title>

  <!-- Import de polices -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Frameworks -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

  <!-- ⭐ SYSTÈME DE DESIGN CSS - TOUJOURS EN PREMIER -->
  <link rel="stylesheet" href="<?= $baseUrl ?>css/global.css">

  <!-- CSS compilé depuis SCSS -->
  <link rel="stylesheet" href="<?= $baseUrl ?>css/header.css">
  <link rel="stylesheet" href="<?= $baseUrl ?>css/footer.css">

  <!-- CSS spécifiques à la page -->
  <?php foreach ($pageCss as $css): ?>
    <link rel="stylesheet" href="<?= $baseUrl ?>css/<?= htmlspecialchars($css) ?>">
  <?php endforeach; ?>
</head>

<body>
  <header>
    <nav class="barre-navigation" role="navigation" aria-label="Navigation principale">
      <div class="conteneur-navigation">

        <button class="bouton-menu" id="boutonMenu" aria-label="Ouvrir le menu de navigation" aria-expanded="false">
          <i class="fas fa-bars"></i>
        </button>

        <a href="?page=accueil" class="logo-entete">
          <img src="<?= $baseUrl ?>images/global/AJC_FRW_bleu_simple.png" alt="Logo Fablab Robotique - Retour à l'accueil">
        </a>

        <div class="liens-navigation">
          <a href="?page=accueil" class="lien-nav">Accueil</a>
          <a href="?page=articles" class="lien-nav">Articles</a>
          <a href="?page=projets" class="lien-nav">Projets</a>
          <a href="?page=webtv" class="lien-nav">WebTV</a>
          <a href="?page=contact" class="lien-nav">Contact</a>
        </div>

        <div class="liens-authentification">
          <?php if ($utilisateurConnecte): ?>
            <div class="profil-container">
              <button class="profil-trigger" id="profilTrigger" aria-label="Ouvrir le menu profil" aria-expanded="false">
                <?php if ($hasPhoto): ?>
                  <img src="<?= $baseUrl ?>uploads/profils/<?= htmlspecialchars($photoUtilisateur); ?>" class="user-avatar" alt="Photo de profil de <?= htmlspecialchars($nomUtilisateur) ?>">
                <?php else: ?>
                  <div class="initials-avatar" style="background: <?= $couleurAvatar; ?>;">
                    <?= htmlspecialchars($initiales); ?>
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
                  <?php if (strtolower($roleUtilisateur) === 'admin'): ?>
                    <li><a href="?page=admin" class="profil-dropdown-item"><i class="fas fa-shield-alt"></i> Tableau de bord</a></li>
                  <?php endif; ?>
                  <li class="profil-dropdown-divider"></li>
                  <li><a href="?page=logout" class="profil-dropdown-item danger"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
                </ul>
              </div>
            </div>
          <?php else: ?>
            <a href="?page=login" class="lien-nav">Se connecter</a>
            <a href="?page=inscription" class="lien-nav btn-inscription">S'inscrire</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Menu mobile -->
      <div class="menu-mobile" id="menuMobile" role="menu">
        <a href="?page=accueil" class="lien-nav">Accueil</a>
        <a href="?page=articles" class="lien-nav">Articles</a>
        <a href="?page=projets" class="lien-nav">Projets</a>
        <a href="?page=webtv" class="lien-nav">WebTV</a>
        <a href="?page=contact" class="lien-nav">Contact</a>

        <div class="menu-mobile-auth">
          <?php if ($utilisateurConnecte): ?>
            <div class="profil-mobile">
              <?php if ($hasPhoto): ?>
                <img src="<?= $baseUrl ?>uploads/profils/<?= htmlspecialchars($photoUtilisateur); ?>" class="user-avatar-mobile" alt="Photo de profil">
              <?php else: ?>
                <div class="initials-avatar-mobile" style="background: <?= $couleurAvatar; ?>;">
                  <?= htmlspecialchars($initiales); ?>
                </div>
              <?php endif; ?>
              <div>
                <p class="profil-nom-mobile"><?= htmlspecialchars($nomUtilisateur); ?></p>
                <p class="profil-role-mobile"><?= htmlspecialchars(strtoupper($roleUtilisateur)); ?></p>
              </div>
            </div>
            <a href="?page=profil" class="lien-nav">Mon profil</a>
            <?php if (strtolower($roleUtilisateur) === 'admin'): ?>
              <a href="?page=admin" class="lien-nav">Tableau de bord</a>
            <?php endif; ?>
            <a href="?page=logout" class="lien-nav btn-inscription">Déconnexion</a>
          <?php else: ?>
            <a href="?page=login" class="lien-nav">Se connecter</a>
            <a href="?page=inscription" class="lien-nav btn-inscription">S'inscrire</a>
          <?php endif; ?>
        </div>
      </div>
    </nav>
  </header>

  <script>
    const boutonMenu = document.getElementById("boutonMenu");
    const menuMobile = document.getElementById("menuMobile");
    const profilTrigger = document.getElementById("profilTrigger");
    const profilDropdown = document.getElementById("profilDropdown");

    boutonMenu?.addEventListener("click", () => {
      const estOuvert = menuMobile.classList.toggle("active");
      boutonMenu.setAttribute("aria-expanded", estOuvert);
    });

    document.querySelectorAll(".menu-mobile .lien-nav").forEach(lien => {
      lien.addEventListener("click", () => {
        menuMobile.classList.remove("active");
        boutonMenu.setAttribute("aria-expanded", "false");
      });
    });

    if (profilTrigger) {
      profilTrigger.addEventListener("click", (e) => {
        e.stopPropagation();
        const estOuvert = profilDropdown.classList.toggle("active");
        profilTrigger.setAttribute("aria-expanded", estOuvert);
      });
      document.addEventListener("click", (e) => {
        if (!profilTrigger.contains(e.target) && !profilDropdown.contains(e.target)) {
          profilDropdown.classList.remove("active");
          profilTrigger.setAttribute("aria-expanded", "false");
        }
      });
    }
  </script>