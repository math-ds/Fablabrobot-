<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$titrePage = isset($actualite['titre']) ? 'Actualité: ' . $actualite['titre'] : 'Détail actualité';
$pageCss = ['details-partages.css'];

require __DIR__ . '/../parties/public-layout-start.php';
?>

<main class="detail-main">
<div class="container">
  <a href="?page=actualites" class="back-btn">
    <i class="fas fa-arrow-left"></i>
    Retour aux actualités
  </a>

  <?php if (!empty($actualite)): ?>
    <div class="project-detail">
      <div class="project-header">
        <div class="project-image">
          <?php if (!empty($actualite['image_url'])): ?>
            <img src="<?= htmlspecialchars($actualite['image_url']); ?>"
                 alt="<?= htmlspecialchars($actualite['titre']); ?>"
                 class="js-fallback-next-image">
            <i class="fas fa-newspaper" aria-hidden="true"></i>
          <?php else: ?>
            <i class="fas fa-newspaper" aria-hidden="true"></i>
          <?php endif; ?>
        </div>

        <div class="project-header-content">
          <h1 class="project-title"><?= htmlspecialchars($actualite['titre']); ?></h1>
          <div class="project-meta">
            <?php if (!empty($actualite['source'])): ?>
              <div class="meta-item">
                <i class="fas fa-globe"></i>
                <span><?= htmlspecialchars($actualite['source']); ?></span>
              </div>
            <?php endif; ?>
            <?php if (!empty($actualite['auteur'])): ?>
              <div class="meta-item">
                <i class="fas fa-user"></i>
                <span><?= htmlspecialchars($actualite['auteur']); ?></span>
              </div>
            <?php endif; ?>
            <div class="meta-item">
              <i class="fas fa-calendar"></i>
              <span><?= !empty($actualite['published_at']) ? date('d/m/Y à H:i', strtotime($actualite['published_at'])) : 'Date inconnue'; ?></span>
            </div>
          </div>
        </div>
      </div>

      <div class="project-content">
        <div class="content-grid">
          <div class="main-content">
            <?php if (!empty($actualite['description'])): ?>
              <div class="section-block">
                <h2><i class="fas fa-align-left"></i> Résumé</h2>
                <div class="description-text">
                  <?= nl2br(htmlspecialchars($actualite['description'])) ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($actualite['contenu'])): ?>
              <div class="section-block">
                <h2><i class="fas fa-book-open"></i> Contenu de l'article</h2>
                <div class="detailed-description">
                  <?= nl2br(htmlspecialchars($actualite['contenu'])) ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($actualite['url_source'])): ?>
              <div class="section-block">
                <a href="<?= htmlspecialchars($actualite['url_source']); ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="project-link">
                  <i class="fas fa-external-link-alt"></i>
                  Lire l'article complet sur <?= htmlspecialchars($actualite['source'] ?? 'le site source'); ?>
                </a>
              </div>
            <?php endif; ?>
          </div>

          <aside class="sidebar">
            <h3><i class="fas fa-info-circle"></i> Informations</h3>

            <?php if (!empty($actualite['source'])): ?>
              <div class="info-item">
                <div class="info-label">Source</div>
                <div class="info-value"><?= htmlspecialchars($actualite['source']); ?></div>
              </div>
            <?php endif; ?>

            <?php if (!empty($actualite['auteur'])): ?>
              <div class="info-item">
                <div class="info-label">Auteur</div>
                <div class="info-value"><?= htmlspecialchars($actualite['auteur']); ?></div>
              </div>
            <?php endif; ?>

            <?php if (!empty($actualite['published_at'])): ?>
              <div class="info-item">
                <div class="info-label">Date de publication</div>
                <div class="info-value"><?= date('d/m/Y à H:i', strtotime($actualite['published_at'])); ?></div>
              </div>
            <?php endif; ?>

            <?php if (!empty($actualite['created_at'])): ?>
              <div class="info-item">
                <div class="info-label">Ajouté le</div>
                <div class="info-value"><?= date('d/m/Y', strtotime($actualite['created_at'])); ?></div>
              </div>
            <?php endif; ?>

            <div class="sidebar-action">
              <a href="?page=actualites" class="project-link">
                <i class="fas fa-arrow-left"></i>
                Toutes les actualités
              </a>
            </div>
          </aside>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="error-page">
      <div class="error-icon"><i class="fas fa-exclamation-triangle"></i></div>
      <h1 class="error-title">Actualité introuvable</h1>
      <p class="error-text">Désolé, l'actualité que vous recherchez n'existe pas ou a été supprimée.</p>
      <a href="?page=actualites" class="project-link">
        <i class="fas fa-arrow-left"></i>
        Retour aux actualités
      </a>
    </div>
  <?php endif; ?>
</div>
</main>

<?php require __DIR__ . '/../parties/public-layout-end.php'; ?>
