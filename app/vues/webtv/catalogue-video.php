<?php
$GLOBALS['baseUrl'] = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';

require_once __DIR__ . '/_helpers.php';

$titrePage = 'WebTV - Catalogue - FABLAB';
$pageCss = ['listes-partagees.css', 'filtres-catalogue.css', 'webtv.css', 'modale-creation-partagee.css', 'pagination.css'];

require __DIR__ . '/../parties/public-layout-start.php';

$videos = $videos ?? [];
$categories = $categories ?? [];
$baseUrl = $GLOBALS['baseUrl'];
$q = trim((string)($_GET['q'] ?? ''));
$categorieCourante = isset($categorieCourante)
    ? trim((string)$categorieCourante)
    : trim((string)($_GET['categorie'] ?? ''));
$vuesTotales = array_sum(array_map(static fn($video) => (int)($video['vues'] ?? 0), $videos));
$videosTotalHero = (isset($pagination) && $pagination instanceof Pagination)
    ? (int)$pagination->total()
    : count($videos);
$utilisateurConnecte = $utilisateurConnecte ?? !empty($_SESSION['utilisateur_id']);
$peutCreerVideo = $peutCreerVideo ?? false;
$auteurCreationVideo = $auteurCreationVideo ?? trim((string)($_SESSION['utilisateur_nom'] ?? ''));
if ($auteurCreationVideo === '') {
    $auteurCreationVideo = 'Utilisateur connecte';
}
?>

<main class="webtv-main">
<section class="hero-section webtv-hero webtv-hero-catalogue">
  <div class="hero-content">
    <h1 class="hero-title">WebTV</h1>
    <p class="hero-subtitle">
      Catalogue video du FabLab: ateliers, demos et retours d'experience.
    </p>
    <div class="webtv-hero-stats" aria-label="Statistiques WebTV">
      <div class="webtv-hero-stat">
        <span class="webtv-hero-number" id="webtvHeroCount"><?= $videosTotalHero ?></span>
        <span class="webtv-hero-label">Videos</span>
      </div>
      <div class="webtv-hero-divider" aria-hidden="true"></div>
      <div class="webtv-hero-stat">
        <span class="webtv-hero-number" id="webtvHeroViews"><?= number_format((int)$vuesTotales) ?></span>
        <span class="webtv-hero-label">Vues totales</span>
      </div>
    </div>
  </div>
</section>

<?php if ($peutCreerVideo): ?>
  <div class="webtv-action">
    <button type="button" class="btn-create" data-webtv-open-creation="1">
      <i class="fas fa-plus-circle"></i> Ajouter une video
    </button>
  </div>
<?php endif; ?>

<div class="webtv-catalog-shell" id="webtvCatalogShell" data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>">
  <section class="webtv-toolbar-card" aria-label="Filtres catalogue video">
    <form method="get" class="webtv-filter-form" id="webtvFilterForm">
      <input type="hidden" name="page" value="webtv">
      <label>
        <span>Recherche</span>
        <input type="search" id="webtvSearchInput" name="q" placeholder="Titre, description, auteur..." value="<?= htmlspecialchars($q) ?>">
      </label>
      <label>
        <span>Categorie</span>
        <select id="webtvCategorySelect" name="categorie">
          <option value="">Toutes les catégories</option>
          <?php foreach ($categories as $categorie): ?>
            <option value="<?= htmlspecialchars($categorie) ?>"<?= $categorieCourante === $categorie ? ' selected' : '' ?>>
              <?= htmlspecialchars($categorie) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="webtv-filter-actions">
        <button type="submit" class="webtv-btn webtv-btn-primary">Filtrer</button>
        <a href="<?= htmlspecialchars(webtvBuildUrl()) ?>" class="webtv-btn webtv-btn-ghost">Reinitialiser</a>
      </div>
    </form>

    <?php if (!empty($categories)): ?>
      <div class="webtv-filter-tags" id="webtvFilterTags">
        <a href="<?= htmlspecialchars(webtvBuildUrl(['q' => $q])) ?>" data-categorie="" class="webtv-filter-tag<?= $categorieCourante === '' ? ' is-active' : '' ?>">
          Tout
        </a>
        <?php foreach ($categories as $categorie): ?>
          <a
            href="<?= htmlspecialchars(webtvBuildUrl(['q' => $q, 'categorie' => $categorie])) ?>"
            data-categorie="<?= htmlspecialchars($categorie, ENT_QUOTES, 'UTF-8') ?>"
            class="webtv-filter-tag<?= $categorieCourante === $categorie ? ' is-active' : '' ?>">
            <?= htmlspecialchars($categorie) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="catalog-header-row">
    <h2 class="section-title">Catalogue videos</h2>
    <?php
      $videosAffichees = count($videos);
      $videosTotal = (isset($pagination) && $pagination instanceof Pagination)
          ? (int) $pagination->total()
          : $videosAffichees;
    ?>
    <p id="webtvResultsText" class="catalog-results-counter" aria-live="polite" aria-atomic="true">
      <?= $videosAffichees ?> résultat<?= $videosAffichees > 1 ? 's' : '' ?><?= $videosTotal > $videosAffichees ? ' sur ' . $videosTotal . ' résultats' : '' ?>
    </p>
  </section>

  <div id="webtvCatalogResults">
    <?php if (!empty($videos)): ?>
      <section class="webtv-catalog-grid" aria-label="Liste des videos">
        <?php foreach ($videos as $video): ?>
          <?php
            $params = ['video' => (int)$video['id']];
            if ($q !== '') {
                $params['q'] = $q;
            }
            if ($categorieCourante !== '') {
                $params['categorie'] = $categorieCourante;
            }
            $thumbnailUrl = webtvThumbnailUrl($video, $baseUrl);
            $description = trim((string)($video['description'] ?? ''));
            $metaDate = !empty($video['created_at']) ? date('d/m/Y', strtotime((string)$video['created_at'])) : null;
          ?>
          <a href="<?= htmlspecialchars(webtvBuildUrl($params)) ?>" class="webtv-catalog-card">
            <div class="webtv-catalog-thumb">
              <?php if ($utilisateurConnecte): ?>
                <button
                  type="button"
                  class="favori-toggle <?= !empty($video['is_favori']) ? 'is-active' : '' ?>"
                  data-favori-toggle
                  data-favori-type="video"
                  data-favori-id="<?= (int)($video['id'] ?? 0) ?>"
                  aria-pressed="<?= !empty($video['is_favori']) ? 'true' : 'false' ?>"
                  title="<?= !empty($video['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                  <i class="<?= !empty($video['is_favori']) ? 'fas' : 'far' ?> fa-heart" aria-hidden="true"></i>
                </button>
              <?php endif; ?>
              <?php if ($thumbnailUrl): ?>
                <img src="<?= htmlspecialchars($thumbnailUrl) ?>" alt="<?= htmlspecialchars($video['titre'] ?? 'Video') ?>" loading="lazy">
              <?php else: ?>
                <div class="webtv-catalog-fallback">VIDEO</div>
              <?php endif; ?>

              <?php if (!empty($video['categorie'])): ?>
                <span class="webtv-catalog-badge"><?= htmlspecialchars($video['categorie']) ?></span>
              <?php endif; ?>

              <?php if ($metaDate): ?>
                <span class="webtv-catalog-date"><?= htmlspecialchars($metaDate) ?></span>
              <?php endif; ?>

              <span class="webtv-catalog-play" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                  <polygon points="7 4 20 12 7 20 7 4"></polygon>
                </svg>
              </span>
            </div>

            <div class="webtv-catalog-body">
              <p class="webtv-catalog-title"><?= htmlspecialchars($video['titre'] ?? 'Sans titre') ?></p>
              <p><?= htmlspecialchars($description !== '' ? $description : 'Cliquez pour ouvrir la page detail de cette video.') ?></p>
              <div class="webtv-catalog-meta">
                <span><?= number_format((int)($video['vues'] ?? 0)) ?> vues</span>
                <?php if (!empty($video['auteur_nom'])): ?>
                  <span><?= htmlspecialchars($video['auteur_nom']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </section>
    <?php else: ?>
      <section class="catalog-empty-state">
        <div class="catalog-empty-state-icon" aria-hidden="true">
          <i class="fas fa-video"></i>
        </div>
        <p class="catalog-empty-title">Aucune video trouvee.</p>
        <p>Essaie un autre mot-clé ou une autre catégorie pour élargir les résultats.</p>
        <a href="<?= htmlspecialchars(webtvBuildUrl()) ?>" class="catalog-empty-state-action">Voir catalogue</a>
      </section>
    <?php endif; ?>
  </div>
</div>

<?php if ($peutCreerVideo): ?>
  <div id="modaleVideoCreation" class="modal-creation" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Création vidéo">
    <div class="modal-creation-content">
      <div class="modal-creation-header">
        <div>
          <h2><i class="fas fa-video"></i> Ajouter une video WebTV</h2>
          <p>Publiez rapidement une nouvelle video YouTube.</p>
        </div>
        <button class="close-modal-creation" type="button" data-webtv-close-creation="1" aria-label="Fermer la modale">&times;</button>
      </div>

      <div class="modal-creation-body">
        <form id="formVideoPublic">
          <?= CsrfHelper::obtenirChampJeton(); ?>

          <div class="form-group">
            <label for="video_titre">Titre *</label>
            <input type="text" id="video_titre" name="titre" required minlength="3" maxlength="200" placeholder="Titre de la video...">
            <small id="video_titre_error" class="champ-erreur"></small>
          </div>

          <div class="form-group">
            <label for="video_description">Description *</label>
            <textarea id="video_description" name="description" rows="4" required minlength="5" maxlength="1500" placeholder="Description de la video..."></textarea>
            <small id="video_description_error" class="champ-erreur"></small>
          </div>

          <div class="form-group">
            <label for="video_auteur_affiche">Auteur</label>
            <input type="text" id="video_auteur_affiche" value="<?= htmlspecialchars($auteurCreationVideo, ENT_QUOTES, 'UTF-8') ?>" disabled>
          </div>

          <div class="form-group">
            <label for="video_categorie">Catégorie *</label>
            <select id="video_categorie" name="categorie" required>
              <option value="">Sélectionner une catégorie</option>
              <?php foreach ($categories as $categorie): ?>
                <option value="<?= htmlspecialchars($categorie, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($categorie) ?></option>
              <?php endforeach; ?>
            </select>
            <small id="video_categorie_error" class="champ-erreur"></small>
          </div>

          <div class="form-group">
            <label for="video_youtube_url">URL YouTube *</label>
            <input type="url" id="video_youtube_url" name="youtube_url" required placeholder="https://www.youtube.com/watch?v=...">
            <small id="video_youtube_error" class="champ-erreur"></small>

            <div class="info-box">
              <i class="fab fa-youtube"></i>
              La miniature sera generee automatiquement depuis YouTube.
            </div>

            <div id="video_preview_container" class="image-preview-container webtv-youtube-preview">
              <p class="preview-label">
                <i class="fas fa-eye"></i> Apercu de la miniature YouTube
              </p>
              <div class="preview-wrapper">
                <img id="video_preview_image" alt="Miniature YouTube">
                <div id="video_preview_spinner" class="image-loading-spinner">
                  <i class="fas fa-spinner fa-spin"></i>
                </div>
                <div id="video_preview_placeholder" class="youtube-placeholder">
                  <i class="fab fa-youtube"></i>
                  <p>Collez une URL YouTube valide pour afficher la miniature.</p>
                </div>
              </div>
            </div>

            <div id="video_preview_error" class="image-preview-error">
              <i class="fas fa-exclamation-triangle"></i>
              Impossible de charger la miniature YouTube pour cette URL.
            </div>
          </div>

          <div class="form-actions">
            <button type="button" class="btn btn-secondary" data-webtv-close-creation="1">
              <i class="fas fa-times"></i> Annuler
            </button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-paper-plane"></i> Publier
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../parties/pagination.php'; ?>
</main>

<?php
$publicScripts = ['js/webtv-catalogue.js'];
if ($peutCreerVideo) {
    $publicScripts[] = 'js/webtv-public-form.js';
}
?>

<?php require __DIR__ . '/../parties/public-layout-end.php'; ?>
