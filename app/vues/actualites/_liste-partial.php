<?php

$baseUrl = $baseUrl ?? ($GLOBALS['baseUrl'] ?? '/Fablabrobot/public/');
$actualitesAffichees = count($actualites ?? []);
$actualitesTotal = (isset($pagination) && $pagination instanceof Pagination)
    ? (int) $pagination->total()
    : $actualitesAffichees;
?>

<div class="actualites-shell" data-actualites-shell>
  <section class="catalog-header-row">
    <h2 class="section-title">Dernieres actualites</h2>
    <p id="actualitesResultsText" class="catalog-results-counter" aria-live="polite" aria-atomic="true">
      <?= $actualitesAffichees ?> résultat<?= $actualitesAffichees > 1 ? 's' : '' ?><?= $actualitesTotal > $actualitesAffichees ? ' sur ' . $actualitesTotal . ' résultats' : '' ?>
    </p>
  </section>

  <div class="projects-grid actualites-grid" id="actualitesGrid" data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (empty($actualites)): ?>
      <section class="catalog-empty-state">
        <div class="catalog-empty-state-icon" aria-hidden="true">
          <i class="fas fa-newspaper"></i>
        </div>
        <p class="catalog-empty-title">Aucune actualité trouvée.</p>
        <p>Essayez un autre mot-clé pour élargir les résultats.</p>
        <a href="?page=actualites" class="catalog-empty-state-action">Voir toutes les actualites</a>
      </section>
    <?php else: ?>
      <?php foreach ($actualites as $actualite): ?>
        <article class="project-card actualite-card">
          <div class="project-image actualite-card-media">
            <?php if (!empty($actualite['image_url'])): ?>
              <img src="<?= htmlspecialchars($actualite['image_url']); ?>"
                   alt="<?= htmlspecialchars($actualite['titre']); ?>"
                   loading="lazy"
                   class="js-fallback-next-image">
              <div class="actualite-image-fallback icone-fallback-cachee" aria-hidden="true">
                <i class="fas fa-newspaper"></i>
                <span>Image non disponible</span>
              </div>
            <?php else: ?>
              <div class="actualite-image-fallback" aria-hidden="true">
                <i class="fas fa-newspaper"></i>
                <span>Image non disponible</span>
              </div>
            <?php endif; ?>
          </div>

          <div class="project-content actualite-card-content">
            <h3 class="project-title actualite-card-title"><?= htmlspecialchars($actualite['titre']); ?></h3>
            <p class="project-description actualite-card-description">
              <?php
              $description = (string)($actualite['description'] ?? '');
              $descriptionNettoyee = strip_tags($description);
              if (function_exists('mb_substr')) {
                  $extrait = mb_substr($descriptionNettoyee, 0, 150, 'UTF-8');
                  $descriptionLongue = mb_strlen($descriptionNettoyee, 'UTF-8') > 150;
              } else {
                  $extrait = substr($descriptionNettoyee, 0, 150);
                  $descriptionLongue = strlen($descriptionNettoyee) > 150;
              }
              echo htmlspecialchars($extrait, ENT_QUOTES, 'UTF-8') . ($descriptionLongue ? '...' : '');
              ?>
            </p>
            <div class="project-tags actualite-card-tags">
              <?php if (!empty($actualite['source'])): ?>
                <span class="tag"><i class="fas fa-globe"></i> <?= htmlspecialchars($actualite['source']); ?></span>
              <?php endif; ?>
              <span class="tag"><i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($actualite['published_at'])); ?></span>
            </div>
            <div class="action-buttons actualite-card-actions">
              <a href="?page=actualite-detail&id=<?= (int)$actualite['id']; ?>" class="btn btn-primary">
                <i class="fas fa-book-open"></i> Lire l'article
              </a>
              <?php if (!empty($actualite['url_source'])): ?>
                <a href="<?= htmlspecialchars($actualite['url_source']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">
                  <i class="fas fa-external-link-alt"></i> Source
                </a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php require __DIR__ . '/../parties/pagination.php'; ?>
</div>
