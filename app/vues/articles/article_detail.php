<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$titrePage = isset($article['titre']) ? 'Article: ' . $article['titre'] : 'Détail article';
$pageCss = ['details-partages.css'];
$utilisateurConnecte = !empty($_SESSION['utilisateur_id']);

require __DIR__ . '/../parties/public-layout-start.php';
?>

<main class="detail-main">
<div class="container">
  <a href="?page=articles" class="back-btn">
    <i class="fas fa-arrow-left"></i>
    Retour aux articles
  </a>

  <?php if (!empty($article)): ?>
    <?php
      $imageSrc = '';
      if (!empty($article['image_url'])) {
          if (str_starts_with($article['image_url'], 'http://') || str_starts_with($article['image_url'], 'https://')) {
              $imageSrc = $article['image_url'];
          } elseif (str_starts_with($article['image_url'], 'images/')) {
              $imageSrc = $article['image_url'];
          } else {
              $imageSrc = 'images/articles/' . $article['image_url'];
          }
      }
    ?>

    <div class="project-detail">
      <div class="project-header">
        <div class="project-image">
          <?php if ($imageSrc !== ''): ?>
            <img src="<?= htmlspecialchars($imageSrc); ?>"
                 alt="<?= htmlspecialchars($article['titre']); ?>"
                 class="js-fallback-next-image">
            <i class="fas fa-newspaper" aria-hidden="true"></i>
          <?php else: ?>
            <i class="fas fa-newspaper" aria-hidden="true"></i>
          <?php endif; ?>
        </div>

        <div class="project-header-content">
          <h1 class="project-title"><?= htmlspecialchars($article['titre']); ?></h1>
          <div class="project-meta">
            <?php $displayAuteur = $article['auteur_nom'] ?? 'Inconnu'; ?>
            <div class="meta-item">
              <span><?= htmlspecialchars($displayAuteur); ?></span>
            </div>
            <div class="meta-item">
              <i class="fas fa-calendar"></i>
              <span><?= !empty($article['created_at']) ? date('d/m/Y', strtotime($article['created_at'])) : 'Date inconnue'; ?></span>
            </div>
            <?php if ($utilisateurConnecte): ?>
              <button
                type="button"
                class="favori-toggle favori-toggle-detail <?= !empty($article['is_favori']) ? 'is-active' : '' ?>"
                data-favori-toggle
                data-favori-type="article"
                data-favori-id="<?= (int)($article['id'] ?? 0); ?>"
                aria-pressed="<?= !empty($article['is_favori']) ? 'true' : 'false' ?>"
                title="<?= !empty($article['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                <i class="<?= !empty($article['is_favori']) ? 'fas' : 'far' ?> fa-heart" aria-hidden="true"></i>
                <span data-favori-label><?= !empty($article['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?></span>
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="project-content">
        <div class="content-grid">
          <div class="main-content">
            <div class="section-block">
              <h2><i class="fas fa-book-open"></i> Contenu de l'article</h2>
              <div class="description-text">
                <?= nl2br(htmlspecialchars($article['contenu'] ?? '')) ?>
              </div>
            </div>

            <?php if (!empty($article['resume'])): ?>
              <div class="section-block">
                <h3><i class="fas fa-align-left"></i> Résumé</h3>
                <div class="detailed-description">
                  <?= nl2br(htmlspecialchars($article['resume'])); ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($article['tags'])): ?>
              <div class="section-block">
                <h3><i class="fas fa-tags"></i> Tags</h3>
                <div class="tech-stack">
                  <?php foreach (explode(',', $article['tags']) as $tag): ?>
                    <span class="tech-tag"><?= htmlspecialchars(trim($tag)); ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <aside class="sidebar">
            <h3><i class="fas fa-info-circle"></i> Informations de l'article</h3>

            <div class="info-item">
              <div class="info-label">Statut</div>
              <div class="info-value"><span class="status-badge">Publié</span></div>
            </div>

            <div class="info-item">
              <div class="info-label">Auteur</div>
              <div class="info-value"><?= htmlspecialchars($article['auteur_nom'] ?? 'Inconnu'); ?></div>
            </div>

            <?php if (!empty($article['created_at'])): ?>
              <div class="info-item">
                <div class="info-label">Date de création</div>
                <div class="info-value"><?= date('d/m/Y', strtotime($article['created_at'])); ?></div>
              </div>
            <?php endif; ?>

            <?php if (!empty($article['updated_at']) && $article['updated_at'] !== ($article['created_at'] ?? '')): ?>
              <div class="info-item">
                <div class="info-label">Dernière mise à jour</div>
                <div class="info-value"><?= date('d/m/Y', strtotime($article['updated_at'])); ?></div>
              </div>
            <?php endif; ?>

            <?php if (!empty($article['categorie'])): ?>
              <div class="info-item">
                <div class="info-label">Catégorie</div>
                <div class="info-value"><?= htmlspecialchars($article['categorie']); ?></div>
              </div>
            <?php endif; ?>

            <div class="sidebar-action">
              <a href="?page=articles" class="project-link">
                <i class="fas fa-arrow-left"></i>
                Tous les articles
              </a>
            </div>
          </aside>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="error-page">
      <div class="error-icon"><i class="fas fa-exclamation-triangle"></i></div>
      <h1 class="error-title">Article introuvable</h1>
      <p class="error-text">Désolé, l'article que vous recherchez n'existe pas ou a été supprimé.</p>
      <a href="?page=articles" class="project-link">
        <i class="fas fa-arrow-left"></i>
        Retour aux articles
      </a>
    </div>
  <?php endif; ?>
</div>
</main>

<?php require __DIR__ . '/../parties/public-layout-end.php'; ?>
