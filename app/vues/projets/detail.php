<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$titrePage = isset($projet['title']) ? 'Projet: ' . $projet['title'] : 'Détail projet';
$pageCss = ['details-partages.css'];
$utilisateurConnecte = !empty($_SESSION['utilisateur_id']);

require __DIR__ . '/../parties/public-layout-start.php';
?>

<main class="detail-main">
<div class="container">
  <a href="?page=projets" class="back-btn">
    <i class="fas fa-arrow-left"></i>
    Retour aux projets
  </a>

  <?php if (!empty($projet)): ?>
    <?php
      $imageSrc = '';
      if (isset($projet['image_url']) && trim((string) $projet['image_url']) !== '') {
          if (str_starts_with($projet['image_url'], 'http://') || str_starts_with($projet['image_url'], 'https://')) {
              $imageSrc = $projet['image_url'];
          } elseif (str_starts_with($projet['image_url'], 'images/')) {
              $imageSrc = $projet['image_url'];
          } else {
              $imageSrc = 'images/projets/' . $projet['image_url'];
          }
      }
    ?>

    <div class="project-detail">
      <div class="project-header">
        <div class="project-image">
          <?php if ($imageSrc !== ''): ?>
            <img src="<?= htmlspecialchars($imageSrc); ?>"
                 alt="<?= htmlspecialchars($projet['title']); ?>"
                 class="js-fallback-next-image">
            <i class="fas fa-code" aria-hidden="true"></i>
          <?php else: ?>
            <i class="fas fa-code" aria-hidden="true"></i>
          <?php endif; ?>
        </div>

        <div class="project-header-content">
          <h1 class="project-title"><?= htmlspecialchars($projet['title']); ?></h1>
          <?php $displayAuteurProjet = $projet['auteur_nom'] ?? null; ?>
          <div class="project-meta">
            <?php if ($displayAuteurProjet): ?>
              <div class="meta-item">
                <span><?= htmlspecialchars($displayAuteurProjet); ?></span>
              </div>
            <?php endif; ?>
            <div class="meta-item">
              <i class="fas fa-calendar"></i>
              <span><?= (new DateTime($projet['created_at']))->format('d/m/Y'); ?></span>
            </div>
            <?php if ($utilisateurConnecte): ?>
              <button
                type="button"
                class="favori-toggle favori-toggle-detail <?= !empty($projet['is_favori']) ? 'is-active' : '' ?>"
                data-favori-toggle
                data-favori-type="projet"
                data-favori-id="<?= (int)($projet['id'] ?? 0); ?>"
                aria-pressed="<?= !empty($projet['is_favori']) ? 'true' : 'false' ?>"
                title="<?= !empty($projet['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                <i class="<?= !empty($projet['is_favori']) ? 'fas' : 'far' ?> fa-heart" aria-hidden="true"></i>
                <span data-favori-label><?= !empty($projet['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?></span>
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="project-content">
        <div class="content-grid">
          <div class="main-content">
            <div class="section-block">
              <h2><i class="fas fa-info-circle"></i> Description du projet</h2>
              <p class="description-text"><?= nl2br(htmlspecialchars($projet['description'])); ?></p>
            </div>

            <?php if (isset($projet['description_detailed']) && trim((string) $projet['description_detailed']) !== '' && $projet['description_detailed'] !== $projet['description']): ?>
              <div class="section-block">
                <h3><i class="fas fa-align-left"></i> Description détaillée</h3>
                <div class="detailed-description">
                  <?= nl2br(htmlspecialchars($projet['description_detailed'])); ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (isset($projet['technologies']) && trim((string) $projet['technologies']) !== ''): ?>
              <div class="section-block">
                <h3><i class="fas fa-microchip"></i> Technologies utilisées</h3>
                <div class="tech-stack">
                  <?php foreach (explode(',', $projet['technologies']) as $tech): ?>
                    <span class="tech-tag"><?= htmlspecialchars(trim($tech)); ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <aside class="sidebar">
            <h3><i class="fas fa-chart-bar"></i> Informations du projet</h3>
            <div class="info-item">
              <div class="info-label">Statut</div>
              <div class="info-value"><span class="status-badge">Publié</span></div>
            </div>
            <div class="info-item">
              <div class="info-label">Date de création</div>
              <div class="info-value"><?= (new DateTime($projet['created_at']))->format('d/m/Y'); ?></div>
            </div>
            <?php if (isset($projet['updated_at']) && $projet['updated_at'] !== $projet['created_at']): ?>
              <div class="info-item">
                <div class="info-label">Dernière mise à jour</div>
                <div class="info-value"><?= (new DateTime($projet['updated_at']))->format('d/m/Y'); ?></div>
              </div>
            <?php endif; ?>

            <div class="sidebar-action">
              <a href="?page=projets" class="project-link">
                <i class="fas fa-arrow-left"></i>
                Tous les projets
              </a>
            </div>
          </aside>
        </div>

        <?php if (isset($projet['features']) && trim((string) $projet['features']) !== ''): ?>
          <div class="features-section">
            <h2><i class="fas fa-cogs"></i> Fonctionnalités principales</h2>
            <div class="features-grid">
              <?php foreach (explode(',', $projet['features']) as $feature): ?>
                <?php $feature = trim($feature); ?>
                <?php if ($feature !== ''): ?>
                  <div class="feature-card">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($feature); ?>
                  </div>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if (isset($projet['challenges']) && trim((string) $projet['challenges']) !== ''): ?>
          <div class="challenges-section">
            <h2><i class="fas fa-wrench"></i> Défis techniques rencontrés</h2>
            <div class="challenges-content">
              <?= nl2br(htmlspecialchars($projet['challenges'])); ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <div class="error-page">
      <div class="error-icon"><i class="fas fa-exclamation-triangle"></i></div>
      <h1 class="error-title">Projet introuvable</h1>
      <p class="error-text">Désolé, le projet que vous recherchez n'existe pas ou a été supprimé.</p>
      <a href="?page=projets" class="project-link">
        <i class="fas fa-arrow-left"></i>
        Retour aux projets
      </a>
    </div>
  <?php endif; ?>
</div>
</main>

<?php require __DIR__ . '/../parties/public-layout-end.php'; ?>
