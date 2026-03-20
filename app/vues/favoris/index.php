<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$titrePage = 'Mes favoris - FABLAB';
$pageCss = ['favoris.css', 'listes-partagees.css', 'cartes-partagees.css'];

require __DIR__ . '/../parties/public-layout-start.php';

$favoris = $favoris ?? [];
$typeCourant = $typeCourant ?? 'all';
$compteurs = $compteurs ?? ['all' => 0, 'article' => 0, 'projet' => 0, 'video' => 0];
$totalFavoris = (int)($compteurs['all'] ?? 0);

function favorisImageSrc(array $favori, string $baseUrl): string
{
    $image = trim((string)($favori['image_url'] ?? ''));

    $type = (string)($favori['type_contenu'] ?? '');
    if ($type === 'video') {
        $youtubeUrl = trim((string)($favori['youtube_url'] ?? ''));
        if ($youtubeUrl !== '' && preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{11})/i', $youtubeUrl, $matches)) {
            return 'https://img.youtube.com/vi/' . rawurlencode($matches[1]) . '/mqdefault.jpg';
        }

        if ($image !== '' && preg_match('/img\.youtube\.com\/vi\/([A-Za-z0-9_-]{11})\//i', $image, $matches)) {
            return 'https://img.youtube.com/vi/' . rawurlencode($matches[1]) . '/mqdefault.jpg';
        }

        if ($image !== '' && preg_match('/^[A-Za-z0-9_-]{11}$/', $image)) {
            return 'https://img.youtube.com/vi/' . rawurlencode($image) . '/mqdefault.jpg';
        }

        if ($image === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $image)) {
            return $image;
        }

        return $baseUrl . 'uploads/vignettes/' . ltrim($image, '/');
    }

    if ($image === '') {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $image)) {
        return $image;
    }

    if ($type === 'projet') {
        if (str_starts_with($image, 'images/')) {
            return $baseUrl . ltrim($image, '/');
        }
        return $baseUrl . 'images/projets/' . ltrim($image, '/');
    }

    return $baseUrl . ltrim($image, '/');
}
?>

<section class="hero-section favoris-hero">
  <div class="hero-content">
    <h1 class="hero-title">Mes favoris</h1>
    <p class="hero-subtitle">Retrouve rapidement tous les articles, projets et vidéos que tu as enregistrés.</p>
  </div>
</section>

<section class="favoris-shell" data-favoris-shell data-favoris-page="1">
  <section class="webtv-toolbar-card favoris-toolbar">
    <div class="favoris-toolbar-head">
      <h2>Filtres</h2>
      <p><?= (int)($compteurs['all'] ?? 0) ?> favoris au total</p>
    </div>
    <div class="favoris-toolbar-controls">
      <div class="webtv-filter-tags">
        <a href="?page=favoris&type=all" class="webtv-filter-tag <?= $typeCourant === 'all' ? 'is-active' : '' ?>" data-favoris-filter>
          Tout <span class="favoris-filter-count"><?= (int)($compteurs['all'] ?? 0) ?></span>
        </a>
        <a href="?page=favoris&type=article" class="webtv-filter-tag <?= $typeCourant === 'article' ? 'is-active' : '' ?>" data-favoris-filter>
          Articles <span class="favoris-filter-count"><?= (int)($compteurs['article'] ?? 0) ?></span>
        </a>
        <a href="?page=favoris&type=projet" class="webtv-filter-tag <?= $typeCourant === 'projet' ? 'is-active' : '' ?>" data-favoris-filter>
          Projets <span class="favoris-filter-count"><?= (int)($compteurs['projet'] ?? 0) ?></span>
        </a>
        <a href="?page=favoris&type=video" class="webtv-filter-tag <?= $typeCourant === 'video' ? 'is-active' : '' ?>" data-favoris-filter>
          Vidéos <span class="favoris-filter-count"><?= (int)($compteurs['video'] ?? 0) ?></span>
        </a>
      </div>
      <div class="favoris-toolbar-actions">
        <a href="?page=favoris&type=all" class="favoris-toolbar-btn favoris-toolbar-btn-ghost" data-favoris-filter>
          Réinitialiser le filtre
        </a>
        <button
          type="button"
          class="favoris-toolbar-btn favoris-toolbar-btn-primary"
          data-favoris-clear
          data-favoris-total="<?= $totalFavoris ?>"
          <?= $totalFavoris <= 0 ? 'disabled' : '' ?>>
          Supprimer tous les favoris
        </button>
      </div>
    </div>
  </section>
</section>

<main class="featured-section favoris-section" data-favoris-content>
  <?php if (empty($favoris)): ?>
    <section class="catalog-empty-state">
      <div class="catalog-empty-state-icon" aria-hidden="true">
        <i class="fas fa-heart-broken"></i>
      </div>
      <h3>Aucun favori pour le moment.</h3>
      <p>Ajoute des contenus avec le coeur pour les retrouver ici.</p>
      <a href="?page=articles" class="catalog-empty-state-action">Voir les articles</a>
    </section>
  <?php else: ?>
    <div class="projects-grid">
      <?php foreach ($favoris as $favori): ?>
        <?php
          $type = (string)($favori['type_contenu'] ?? '');
          $title = (string)($favori['titre'] ?? '');
          $descriptionSource = strip_tags((string)($favori['description'] ?? ''));
          if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            $description = mb_strlen($descriptionSource, 'UTF-8') > 150
              ? mb_substr($descriptionSource, 0, 150, 'UTF-8') . '...'
              : $descriptionSource;
          } else {
            $description = strlen($descriptionSource) > 150
              ? substr($descriptionSource, 0, 150) . '...'
              : $descriptionSource;
          }
          $imageSrc = favorisImageSrc($favori, $baseUrl);
          $url = (string)($favori['url'] ?? '#');
          $labelType = match ($type) {
            'article' => 'Article',
            'projet' => 'Projet',
            'video' => 'Vidéo',
            default => 'Contenu',
          };
          $typeBadgeClass = match ($type) {
            'article' => 'tag-type--article',
            'projet' => 'tag-type--projet',
            'video' => 'tag-type--video',
            default => 'tag-type--default',
          };
        ?>
        <article class="project-card" data-favori-type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
          <div class="project-image">
            <button
              type="button"
              class="favori-toggle is-active"
              data-favori-toggle
              data-favori-type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"
              data-favori-id="<?= (int)($favori['id'] ?? 0) ?>"
              aria-pressed="true"
              title="Retirer des favoris">
              <i class="fas fa-heart" aria-hidden="true"></i>
            </button>
            <?php if ($imageSrc !== ''): ?>
              <img src="<?= htmlspecialchars($imageSrc, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>" loading="lazy" class="js-fallback-next-image">
              <i class="fas fa-star icone-fallback-cachee"></i>
            <?php else: ?>
              <i class="fas fa-star"></i>
            <?php endif; ?>
          </div>
          <div class="project-content">
            <h3 class="project-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="project-description"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="project-tags">
              <span class="tag tag-type <?= htmlspecialchars($typeBadgeClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($labelType, ENT_QUOTES, 'UTF-8') ?></span>
              <?php if (!empty($favori['auteur_nom'])): ?>
                <span class="tag">Auteur: <?= htmlspecialchars((string)$favori['auteur_nom'], ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
              <?php if (!empty($favori['favori_created_at'])): ?>
                <span class="tag">Ajoute le <?= date('d/m/Y', strtotime((string)$favori['favori_created_at'])) ?></span>
              <?php endif; ?>
            </div>
            <div class="action-buttons">
              <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
                Voir le détail
              </a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php require __DIR__ . '/../parties/public-layout-end.php'; ?>
