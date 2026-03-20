<?php


$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';


$titrePage = 'Articles - FABLAB';
$pageCss = ['article.css', 'listes-partagees.css', 'filtres-catalogue.css', 'cartes-partagees.css', 'modale-cartes.css', 'modale-creation-partagee.css', 'pagination.css'];

include(__DIR__ . '/../parties/public-layout-start.php');
$utilisateurConnecte = $utilisateurConnecte ?? !empty($_SESSION['utilisateur_id']);
$peutCreerArticle = $peutCreerArticle ?? false;


if (!isset($articles)) {
    $articles = [];
}
$categories = $categories ?? [];
$q = trim((string)($_GET['q'] ?? ''));
$categorieCourante = trim((string)($_GET['categorie'] ?? ''));
$categorieActive = $categorieCourante === '' ? 'all' : $categorieCourante;
?>

<section class="hero-section">
  <div class="hero-content">
    <h1 class="hero-title">Nos Articles</h1>
    <p class="hero-subtitle">Découvrez nos articles récents sur la technologie, l'innovation et le bien-être.</p>
  </div>
</section>

<?php if ($peutCreerArticle): ?>
  <div class="article-action">
    <button type="button" class="btn btn-primary" data-article-open-creation="1">
      <i class="fas fa-plus-circle"></i> Créer un article
    </button>
  </div>
<?php endif; ?>
<div class="webtv-catalog-shell" id="articlesFilterShell">
  <section class="webtv-toolbar-card" aria-label="Filtres catalogue articles">
      <form method="get" class="webtv-filter-form" id="articlesFilterForm">
        <input type="hidden" name="page" value="articles">
        <label>
          <span>Recherche</span>
          <input type="search" id="searchInput" name="q" placeholder="Titre, contenu, auteur..." value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label>
          <span>Catégorie</span>
          <select id="categoryFilter" name="categorie">
            <option value="all"<?= $categorieActive === 'all' ? ' selected' : '' ?>>Toutes les catégories</option>
            <?php foreach ($categories as $categorie): ?>
              <option value="<?= htmlspecialchars($categorie, ENT_QUOTES, 'UTF-8') ?>"<?= $categorieActive === $categorie ? ' selected' : '' ?>>
                <?= htmlspecialchars($categorie) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <div class="webtv-filter-actions">
          <button type="submit" class="webtv-btn webtv-btn-primary">Filtrer</button>
          <button type="button" id="resetFilters" class="webtv-btn webtv-btn-ghost">Réinitialiser</button>
        </div>
      </form>

      <?php if (!empty($categories)): ?>
        <?php
          $paramsFiltresBase = ['page' => 'articles'];
          if ($q !== '') {
              $paramsFiltresBase['q'] = $q;
          }
        ?>
        <div class="webtv-filter-tags" id="articlesFilterTags">
          <a
            href="?<?= htmlspecialchars(http_build_query($paramsFiltresBase), ENT_QUOTES, 'UTF-8') ?>"
            data-categorie="all"
            class="webtv-filter-tag<?= $categorieActive === 'all' ? ' is-active' : '' ?>">
            Tout
          </a>
          <?php foreach ($categories as $categorie): ?>
            <?php $paramsCategorie = $paramsFiltresBase; $paramsCategorie['categorie'] = (string)$categorie; ?>
            <a
              href="?<?= htmlspecialchars(http_build_query($paramsCategorie), ENT_QUOTES, 'UTF-8') ?>"
              data-categorie="<?= htmlspecialchars($categorie, ENT_QUOTES, 'UTF-8') ?>"
              class="webtv-filter-tag<?= $categorieActive === $categorie ? ' is-active' : '' ?>">
              <?= htmlspecialchars($categorie) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
  </section>
</div>

<main class="featured-section">
  <?php
    $articlesAffiches = count($articles);
    $articlesTotal = (isset($pagination) && $pagination instanceof Pagination)
        ? (int) $pagination->total()
        : $articlesAffiches;
  ?>
  <section class="catalog-header-row">
    <h2 class="section-title">Articles récents</h2>
    <p id="articlesResultsText" class="catalog-results-counter" aria-live="polite" aria-atomic="true">
      <?= $articlesAffiches ?> résultat<?= $articlesAffiches > 1 ? 's' : '' ?><?= $articlesTotal > $articlesAffiches ? ' sur ' . $articlesTotal . ' résultats' : '' ?>
    </p>
  </section>
  <div class="projects-grid" id="articlesGrid" data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (empty($articles)): ?>
      <section class="catalog-empty-state">
        <div class="catalog-empty-state-icon" aria-hidden="true">
          <i class="fas fa-newspaper"></i>
        </div>
        <p class="catalog-empty-title">Aucun article trouvé.</p>
        <p>Essaie un autre mot-clé ou une autre catégorie pour élargir les résultats.</p>
        <a href="?page=articles" class="catalog-empty-state-action">Voir catalogue</a>
      </section>
    <?php else: ?>
      <?php foreach ($articles as $article): ?>
        <?php $articleCategorie = trim((string)($article['categorie'] ?? '')); ?>
        <div class="project-card"
             data-categorie="<?= htmlspecialchars($articleCategorie, ENT_QUOTES, 'UTF-8') ?>"
             data-article-open-modal="<?= (int) $article['id']; ?>"
             role="button"
             tabindex="0"
             aria-haspopup="dialog"
             aria-controls="modal-article-<?= (int) $article['id']; ?>"
             aria-label="Ouvrir les informations de l'article <?= htmlspecialchars($article['titre'], ENT_QUOTES, 'UTF-8') ?>">
          <div class="project-image">
            <?php if ($utilisateurConnecte): ?>
              <button
                type="button"
                class="favori-toggle <?= !empty($article['is_favori']) ? 'is-active' : '' ?>"
                data-favori-toggle
                data-favori-type="article"
                data-favori-id="<?= (int)$article['id']; ?>"
                aria-pressed="<?= !empty($article['is_favori']) ? 'true' : 'false' ?>"
                title="<?= !empty($article['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                <i class="<?= !empty($article['is_favori']) ? 'fas' : 'far' ?> fa-heart" aria-hidden="true"></i>
              </button>
            <?php endif; ?>
            <?php if (!empty($article['image_url'])): ?>
              <?php
              $imageUrl = $article['image_url'];
              if (!preg_match('/^https?:\/\//i', $imageUrl)) {
                  $imageUrl = $baseUrl . $imageUrl;
              }
              ?>
              <img src="<?= htmlspecialchars($imageUrl); ?>"
                   alt="<?= htmlspecialchars($article['titre']); ?>"
                   loading="lazy"
                   class="js-fallback-next-image">
              <i class="fas fa-code icone-fallback-cachee"></i>
            <?php else: ?>
              <i class="fas fa-code"></i>
            <?php endif; ?>
          </div>

          <div class="project-content">
            <p class="project-title"><?= htmlspecialchars($article['titre']); ?></p>
            <p class="project-description">
              <?php
              $extrait = substr(strip_tags($article['contenu']), 0, 120);
              echo htmlspecialchars($extrait) . (strlen($article['contenu']) > 120 ? '...' : '');
              ?>
            </p>
            <div class="project-tags">
              <span class="tag">Auteur : <?= htmlspecialchars($article['auteur_nom'] ?? ''); ?></span>
              <span class="tag"><?= date('d/m/Y', strtotime($article['created_at'])); ?></span>
            </div>
            <div class="action-buttons">
              <a href="?page=article-detail&id=<?= $article['id']; ?>" class="btn btn-primary js-stop-propagation">
                <i class="fas fa-book-open"></i> Lire l'article
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<?php require __DIR__ . '/../parties/pagination.php'; ?>

<?php foreach ($articles as $article): ?>
<?php
  $imageUrl = '';

  if (!empty($article['image_url'])) {
      if (preg_match('/^https?:\/\//i', $article['image_url'])) {
          $imageUrl = $article['image_url'];
      } else {
          $imageUrl = $baseUrl . ltrim($article['image_url'], '/');
      }
  }
?>
<div class="modal modal-detail modal-article" id="modal-article-<?= (int) $article['id']; ?>" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Détail article">
  <div class="modal-content">
    <div class="modal-header">
      <h2 class="modal-title"><?= htmlspecialchars($article['titre']); ?></h2>
      <div class="modal-header-actions">
        <?php if ($utilisateurConnecte): ?>
          <button
            type="button"
            class="favori-toggle favori-toggle-detail <?= !empty($article['is_favori']) ? 'is-active' : '' ?>"
            data-favori-toggle
            data-favori-type="article"
            data-favori-id="<?= (int)$article['id']; ?>"
            aria-pressed="<?= !empty($article['is_favori']) ? 'true' : 'false' ?>"
            title="<?= !empty($article['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
            <i class="<?= !empty($article['is_favori']) ? 'fas' : 'far' ?> fa-heart" aria-hidden="true"></i>
            <span data-favori-label><?= !empty($article['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?></span>
          </button>
        <?php endif; ?>
        <button type="button" class="close-btn" data-article-close-modal="<?= (int) $article['id']; ?>" aria-label="Fermer la modale">&times;</button>
      </div>
    </div>

    <div class="modal-body">
      <div class="modal-layout">
        <div class="modal-image-section">
          <div class="modal-image">
            <?php if ($imageUrl !== ''): ?>
              <img src="<?= htmlspecialchars($imageUrl); ?>"
                   alt="<?= htmlspecialchars($article['titre']); ?>"
                   class="js-fallback-next-image">
              <i class="fas fa-code icone-fallback-cachee"></i>
            <?php else: ?>
              <i class="fas fa-code"></i>
            <?php endif; ?>
          </div>
        </div>

        <div class="modal-content-section">
          <div class="modal-meta">
            <div class="modal-meta-item">
              <i class="fas fa-user"></i>
              <span><?= htmlspecialchars($article['auteur_nom'] ?? ''); ?></span>
            </div>
            <div class="modal-meta-item">
              <i class="fas fa-calendar"></i>
              <span><?= date('d/m/Y', strtotime($article['created_at'])); ?></span>
            </div>
          </div>

          <div class="action-buttons">
            <a href="?page=article-detail&id=<?= (int) $article['id']; ?>" class="btn btn-primary">
              <i class="fas fa-eye"></i> Voir l'article en detail
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<div id="modaleArticleCreation" class="modal-creation" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Création article">
  <div class="modal-creation-content">
    <div class="modal-creation-header">
      <div>
        <h2><i class="fas fa-pen-nib"></i> Créer un nouvel article</h2>
        <p>Remplissez le formulaire ci-dessous pour publier un nouveau article</p>
      </div>
      <button type="button" class="close-modal-creation" data-article-close-creation="1" aria-label="Fermer la modale">&times;</button>
    </div>

    <div class="modal-creation-body">
      <form id="formArticle" enctype="multipart/form-data">
        <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>

        <div class="form-group">
          <label for="article_titre">Titre *</label>
          <input type="text" name="titre" id="article_titre" required minlength="5" maxlength="200" placeholder="Titre de l'article...">
          <small id="titre_error" class="champ-erreur"></small>
        </div>

        <div class="form-group">
          <label for="article_auteur_affichage">Auteur</label>
          <input type="text" id="article_auteur_affichage" value="<?= htmlspecialchars($_SESSION['utilisateur_nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled>
        </div>

        <div class="form-group">
          <label for="article_categorie">Categorie</label>
          <select name="categorie" id="article_categorie">
            <option value="">-- Sans categorie --</option>
            <?php foreach (($categories ?? []) as $categorieOption): ?>
              <option value="<?= htmlspecialchars($categorieOption, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($categorieOption) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="article_contenu">Contenu *</label>
          <textarea name="contenu" id="article_contenu" rows="10" required minlength="10" maxlength="10000" placeholder="Contenu de l'article..."></textarea>
          <small id="contenu_error" class="champ-erreur"></small>
        </div>

        <div class="form-group">
          <label for="article_image_url">URL de l'image (optionnel)</label>
          <input type="text" name="image_url" id="article_image_url" placeholder="https://exemple.com/image.jpg">

          <div class="info-box">
            <strong><i class="fas fa-lightbulb"></i> Astuce :</strong> Vous pouvez coller n'importe quelle URL d'image depuis Google, Discord, etc.
          </div>

          <div id="articleImagePreviewContainer" class="image-preview-container">
            <p><strong>Aperçu :</strong></p>
            <img id="articleImagePreview" alt="Aperçu de l'image">
            <div id="articleLoadingSpinner" class="loading-spinner">
              <i class="fas fa-spinner fa-spin"></i> Chargement...
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="article_image_file">OU uploader une image (optionnel)</label>
          <input type="file" name="image" id="article_image_file" accept="image/*">

          <div class="info-box">
            <strong><i class="fas fa-upload"></i> Upload local :</strong> Si vous uploadez un fichier,
            il sera prioritaire sur l'URL.
          </div>

          <div id="articleLocalPreviewContainer" class="image-preview-container">
            <p class="apercu-titre">
              <i class="fas fa-eye"></i> Aperçu du fichier :
            </p>
            <img id="articleLocalPreview" class="apercu-image-locale" alt="Aperçu local">
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" data-article-close-creation="1">
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

<?php $publicScripts = ['js/articles-page.js', 'js/articles-catalogue.js']; ?>

<?php include(__DIR__ . '/../parties/public-layout-end.php'); ?>




