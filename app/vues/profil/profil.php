<?php
require_once __DIR__ . '/../../helpers/AvatarHelper.php';
require_once __DIR__ . '/../../helpers/RoleHelper.php';
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$titrePage = 'Profil - FABLAB';
$pageCss = ['listes-partagees.css', 'profil.css'];
$avatarProfil = AvatarHelper::construireDonnees(
    (string)($_SESSION['utilisateur_nom'] ?? ($user['nom'] ?? '')),
    $_SESSION['utilisateur_photo'] ?? ($user['photo'] ?? null),
    $baseUrl
);
include __DIR__ . '/../parties/public-layout-start.php';
?>

<section class="hero-section profil-hero">
  <div class="hero-content">
    <h1 class="hero-title">Mon profil</h1>
    <p class="hero-subtitle">Gerez vos informations personnelles, votre mot de passe et votre photo dans un espace unique.</p>
  </div>
</section>

<main class="profil-main">
<div class="container profile-page">
    <a href="?page=accueil" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        <span>Retour a l'accueil</span>
    </a>

    <div class="profile-wrapper">
        <div class="profile-header">
            <div class="header-background"></div>
            <div class="header-content">
                <div class="profile-avatar-large">
                    <?php if (!empty($avatarProfil['has_photo']) && !empty($avatarProfil['photo_url'])): ?>
                        <img id="photoPreviewHeader" src="<?= htmlspecialchars($avatarProfil['photo_url']) ?>" alt="Photo de profil">
                    <?php else: ?>
                        <div class="avatar-placeholder <?= htmlspecialchars((string)($avatarProfil['classe_couleur'] ?? 'avatar-couleur-1'), ENT_QUOTES, 'UTF-8') ?>" id="photoPreviewHeader">
                            <?= htmlspecialchars($avatarProfil['initiales']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (RoleHelper::estAdmin((string)($user['role'] ?? ''))): ?>
                        <div class="avatar-badge" title="Administrateur">
                            <i class="fas fa-crown"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="header-info">
                    <h2 class="profile-name"><?= htmlspecialchars((string)$user['nom']) ?></h2>
                    <p class="profile-email">
                        <i class="fas fa-envelope"></i> <?= htmlspecialchars((string)$user['email']) ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="profile-content">
            <div class="content-grid">
                <div class="main-column">
                    <div class="card card-form">
                        <div class="card-header">
                            <div class="card-icon"><i class="fas fa-user-edit"></i></div>
                            <h2>Informations personnelles</h2>
                        </div>
                        <form method="POST" action="?page=profil" class="form-ajax" data-action="update-info">
                            <input type="hidden" name="action" value="update-info">
                            <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>
                            <div class="form-group">
                                <label for="nom"><i class="fas fa-user"></i> Nom complet</label>
                                <input type="text" name="nom" id="nom" class="form-control" value="<?= htmlspecialchars((string)$user['nom']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope"></i> Adresse email</label>
                                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars((string)$user['email']) ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Enregistrer
                            </button>
                        </form>
                    </div>

                    <div class="card card-password">
                        <div class="card-header">
                            <div class="card-icon"><i class="fas fa-lock"></i></div>
                            <h2>Changer le mot de passe</h2>
                        </div>
                        <form method="POST" action="?page=profil" class="form-ajax" data-action="update-password">
                            <input type="hidden" name="action" value="update-password">
                            <?php echo CsrfHelper::obtenirChampJeton(); ?>
                            <div class="form-group">
                                <label for="old_password"><i class="fas fa-key"></i> Ancien mot de passe</label>
                                <input type="password" name="old_password" id="old_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password"><i class="fas fa-lock"></i> Nouveau mot de passe</label>
                                <input type="password" name="new_password" id="new_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password"><i class="fas fa-check"></i> Confirmer</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-sync-alt"></i> Mettre a jour
                            </button>
                        </form>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div class="card-icon"><i class="fas fa-camera"></i></div>
                            <h2>Photo de profil</h2>
                        </div>
                        <div class="card-body">
                            <div class="photo-preview">
                                <?php if (!empty($avatarProfil['has_photo']) && !empty($avatarProfil['photo_url'])): ?>
                                    <img id="photoPreview" src="<?= htmlspecialchars($avatarProfil['photo_url']) ?>" alt="Photo de profil">
                                <?php else: ?>
                                    <div class="avatar-placeholder <?= htmlspecialchars((string)($avatarProfil['classe_couleur'] ?? 'avatar-couleur-1'), ENT_QUOTES, 'UTF-8') ?>" id="photoPreview">
                                        <?= htmlspecialchars($avatarProfil['initiales']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <form method="POST" enctype="multipart/form-data" action="?page=profil" class="form-ajax" data-action="upload-photo">
                                <?php echo CsrfHelper::obtenirChampJeton(); ?>
                                <label for="photo" class="visually-hidden">Choisir une photo de profil</label>
                                <input type="file" name="photo" id="photo" accept="image/*" required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> Mettre a jour
                                </button>
                            </form>

                            <?php if (!empty($user['photo'])): ?>
                                <form method="POST" action="?page=profil" class="form-ajax" data-action="delete" data-confirm-message="Voulez-vous vraiment supprimer votre photo de profil ?" id="deletePhotoForm">
                                    <input type="hidden" name="action" value="delete">
                                    <?php echo CsrfHelper::obtenirChampJeton(); ?>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash-alt"></i> Supprimer
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="?page=profil" class="form-ajax hidden" data-action="delete" data-confirm-message="Voulez-vous vraiment supprimer votre photo de profil ?" id="deletePhotoForm">
                                    <input type="hidden" name="action" value="delete">
                                    <?php echo CsrfHelper::obtenirChampJeton(); ?>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash-alt"></i> Supprimer
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="sidebar-column">
                    <div class="card card-stats">
                        <div class="card-header">
                            <div class="card-icon"><i class="fas fa-chart-line"></i></div>
                            <h3>Statistiques</h3>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                                <div class="stat-info">
                                    <span class="stat-label">Membre depuis</span>
                                    <span class="stat-value"><?= !empty($user['created_at']) ? date('d/m/Y', strtotime((string)$user['created_at'])) : 'Non renseignee' ?></span>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                                <div class="stat-info">
                                    <span class="stat-label">Derniere connexion</span>
                                    <span class="stat-value"><?= date('d/m/Y') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-actions">
                        <div class="card-header">
                            <div class="card-icon"><i class="fas fa-bolt"></i></div>
                            <h3>Actions rapides</h3>
                        </div>
                        <div class="quick-actions">
                            <a href="?page=accueil" class="action-link">
                                <i class="fas fa-home"></i> Accueil
                            </a>
                            <form method="POST" action="?page=logout" class="logout-profile-form m-0">
                                <?= CsrfHelper::obtenirChampJeton(); ?>
                                <a href="#" class="action-link action-logout" data-submit-parent-form="1" role="button" aria-label="Se déconnecter">
                                    <i class="fas fa-sign-out-alt"></i> Se deconnecter
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<?php $publicScripts = ['js/profil-page.js']; ?>

<?php include __DIR__ . '/../parties/public-layout-end.php'; ?>
