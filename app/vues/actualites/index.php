<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';

$titrePage = 'Actualites Tech - FABLAB';
$pageCss = ['listes-partagees.css', 'cartes-partagees.css', 'filtres-catalogue.css', 'pagination.css', 'actualites.css'];
$publicScripts = ['js/actualites-page.js'];

include(__DIR__ . '/../parties/public-layout-start.php');

$q = trim((string)($_GET['q'] ?? ''));
$sourceCourante = trim((string)($_GET['source'] ?? ''));
$actualites = $actualites ?? [];
?>

<section class="hero-section">
  <div class="hero-content">
    <h1 class="hero-title">Actualites Technologiques</h1>
    <p class="hero-subtitle">Decouvrez les dernieres actualites en technologie, robotique et innovation</p>
  </div>
</section>

<div class="webtv-catalog-shell" id="actualitesFilterShell">
  <section class="webtv-toolbar-card" aria-label="Filtres catalogue actualites">
    <form method="get" class="webtv-filter-form actualites-filter-form" id="actualitesFilterForm" data-actualites-filter-form>
      <input type="hidden" name="page" value="actualites">
      <label>
        <span>Recherche</span>
        <input type="search" id="searchInput" name="q" placeholder="Titre, source, description..." value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
      </label>
      <label>
        <span>Source</span>
        <select name="source" id="actualitesSource" data-actualites-source>
          <option value="">Toutes les sources</option>
          <?php foreach (($sources ?? []) as $source): ?>
            <?php $sourceTexte = trim((string)$source); ?>
            <?php if ($sourceTexte === '') { continue; } ?>
            <option value="<?= htmlspecialchars($sourceTexte, ENT_QUOTES, 'UTF-8') ?>" <?= $sourceTexte === $sourceCourante ? 'selected' : '' ?>>
              <?= htmlspecialchars($sourceTexte, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="webtv-filter-actions">
        <button type="submit" class="webtv-btn webtv-btn-primary">Filtrer</button>
        <button type="button" class="webtv-btn webtv-btn-ghost" data-actualites-reset>Reinitialiser</button>
      </div>
    </form>
  </section>
</div>

<main class="featured-section">
  <?php require __DIR__ . '/_liste-partial.php'; ?>
</main>

<?php include(__DIR__ . '/../parties/public-layout-end.php'); ?>
