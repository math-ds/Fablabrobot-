<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$pageCss = ['projets.css', 'listes-partagees.css', 'filtres-catalogue.css', 'cartes-partagees.css', 'modale-cartes.css', 'modale-creation-partagee.css', 'pagination.css'];
$q = trim((string)($_GET['q'] ?? ''));
$categorieCourante = isset($categorieCourante) ? trim((string)$categorieCourante) : trim((string)($_GET['categorie'] ?? ''));
if ($categorieCourante === '') {
    $categorieCourante = 'all';
}
$categoriesProjet = $categoriesProjet ?? ['Robotique', 'Drone / FPV', 'Impression 3D', 'Electronique', 'Programmation', 'Mecanique', 'Autre'];

require __DIR__ . '/../parties/public-layout-start.php';
$utilisateurConnecte = $utilisateurConnecte ?? !empty($_SESSION['utilisateur_id']);
$peutCreerProjet = $peutCreerProjet ?? false;
?>

<div class="particles" id="particles"></div>

<main class="projets-main">
<section class="hero-section">
  <div class="hero-content">
    <h1 class="hero-title">Les Projets</h1>
    <p class="hero-subtitle">
      Découvrez les projets réalisés au sein du Fablab d'AJC Formation.
      Cette section met en avant des réalisations développées autour de la robotique, de l'électronique et des technologies numériques.
    </p>
  </div>
</section>
<?php if ($peutCreerProjet): ?>
  <div class="projet-action">
    <button type="button" class="btn-create" data-projet-open-creation="1">
      <i class="fas fa-plus-circle"></i> Creer un projet
    </button>
  </div>
<?php endif; ?>
<div class="webtv-catalog-shell" id="projetsFilterShell">
  <section class="webtv-toolbar-card" aria-label="Filtres catalogue projets">
      <form method="get" class="webtv-filter-form" id="projetsFilterForm">
        <input type="hidden" name="page" value="projets">
        <label>
          <span>Recherche</span>
          <input type="search" id="searchInput" name="q" placeholder="Titre, description, technologies..." value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label>
          <span>Categorie</span>
          <select id="categoryFilter" name="categorie">
            <option value="all"<?= $categorieCourante === 'all' ? ' selected' : '' ?>>Toutes les catégories</option>
            <?php foreach ($categoriesProjet as $categorieOption): ?>
              <option value="<?= htmlspecialchars($categorieOption, ENT_QUOTES, 'UTF-8') ?>"<?= $categorieCourante === $categorieOption ? ' selected' : '' ?>>
                <?= htmlspecialchars($categorieOption) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <div class="webtv-filter-actions">
          <button type="submit" class="webtv-btn webtv-btn-primary">Filtrer</button>
          <button type="button" id="resetFilters" class="webtv-btn webtv-btn-ghost">Reinitialiser</button>
        </div>
      </form>

      <?php if (!empty($categoriesProjet)): ?>
        <?php
          $paramsFiltresBase = ['page' => 'projets'];
          if ($q !== '') {
              $paramsFiltresBase['q'] = $q;
          }
        ?>
        <div class="webtv-filter-tags" id="projetsFilterTags">
          <a
            href="?<?= htmlspecialchars(http_build_query($paramsFiltresBase), ENT_QUOTES, 'UTF-8') ?>"
            data-categorie="all"
            class="webtv-filter-tag<?= $categorieCourante === 'all' ? ' is-active' : '' ?>">
            Tout
          </a>
          <?php foreach ($categoriesProjet as $categorieOption): ?>
            <?php $paramsCategorie = $paramsFiltresBase; $paramsCategorie['categorie'] = (string)$categorieOption; ?>
            <a
              href="?<?= htmlspecialchars(http_build_query($paramsCategorie), ENT_QUOTES, 'UTF-8') ?>"
              data-categorie="<?= htmlspecialchars($categorieOption, ENT_QUOTES, 'UTF-8') ?>"
              class="webtv-filter-tag<?= $categorieCourante === $categorieOption ? ' is-active' : '' ?>">
              <?= htmlspecialchars($categorieOption) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
  </section>
</div>



<section class="featured-section">
    <?php
      $projetsAffiches = count($projects ?? []);
      $projetsTotal = (isset($pagination) && $pagination instanceof Pagination)
          ? (int) $pagination->total()
          : $projetsAffiches;
    ?>
    <section class="catalog-header-row">
      <h2 class="section-title">Projets recents</h2>
      <p id="projetsResultsText" class="catalog-results-counter" aria-live="polite" aria-atomic="true">
        <?= $projetsAffiches ?> résultat<?= $projetsAffiches > 1 ? 's' : '' ?><?= $projetsTotal > $projetsAffiches ? ' sur ' . $projetsTotal . ' résultats' : '' ?>
      </p>
    </section>
    <div class="projects-grid" id="projetsGrid" data-base-url="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>">
<?php if (empty($projects)): ?>
    <section class="catalog-empty-state">
      <div class="catalog-empty-state-icon" aria-hidden="true">
        <i class="fas fa-code"></i>
      </div>
      <p class="catalog-empty-title">Aucun projet trouve.</p>
      <p>Essaie un autre mot-clé ou une autre catégorie pour élargir les résultats.</p>
      <a href="?page=projets" class="catalog-empty-state-action">Voir catalogue</a>
    </section>
<?php else: ?>
<?php foreach ($projects as $project): ?>

    <?php
    $txt = strtolower($project['title'] . ' ' . $project['description'] . ' ' . ($project['technologies'] ?? ''));

    if (str_contains($txt, "drone") || str_contains($txt, "fpv") || str_contains($txt, "quad")) {
        $categorie = "Drone / FPV";
    } elseif (str_contains($txt, "robot") || str_contains($txt, "moteur") || str_contains($txt, "arduino") || str_contains($txt, "servo")) {
        $categorie = "Robotique";
    } else {
        $categorie = "Autre";
    }

    $imageSrc = '';
    if (!empty($project['image_url'])) {
        if (str_starts_with($project['image_url'], 'http://') || str_starts_with($project['image_url'], 'https://')) {
            $imageSrc = $project['image_url'];
        } else {
            $imageSrc = '../public/images/projets/' . $project['image_url'];
        }
    }
    ?>

    <div class="project-card"
         data-categorie="<?= $categorie ?>"
         data-projet-open-modal="<?= (int) $project['id']; ?>"
         role="button"
         tabindex="0"
         aria-haspopup="dialog"
         aria-controls="modal-projet-<?= (int) $project['id']; ?>"
         aria-label="Ouvrir les informations du projet <?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?>">

        <div class="project-image">
            <?php if ($utilisateurConnecte): ?>
                <button
                    type="button"
                    class="favori-toggle <?= !empty($project['is_favori']) ? 'is-active' : '' ?>"
                    data-favori-toggle
                    data-favori-type="projet"
                    data-favori-id="<?= (int)$project['id']; ?>"
                    aria-pressed="<?= !empty($project['is_favori']) ? 'true' : 'false' ?>"
                    title="<?= !empty($project['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                    <i class="<?= !empty($project['is_favori']) ? 'fas' : 'far' ?> fa-heart" aria-hidden="true"></i>
                </button>
            <?php endif; ?>
            <?php if (!empty($imageSrc)): ?>
                <img src="<?= htmlspecialchars($imageSrc) ?>"
                     alt="<?= htmlspecialchars($project['title']) ?>"
                     class="js-fallback-next-image">
                <i class="fas fa-code icone-fallback-cachee"></i>
            <?php else: ?>
                <i class="fas fa-code"></i>
            <?php endif; ?>
        </div>

        <div class="project-content">
            <p class="project-title"><?= htmlspecialchars($project['title']) ?></p>
            <p class="project-description"><?= htmlspecialchars($project['description']) ?></p>

            <?php if (!empty($project['technologies'])): ?>
            <div class="project-tags">
                <?php foreach (explode(',', $project['technologies']) as $tech): ?>
                    <span class="tag"><?= htmlspecialchars(trim($tech)) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

<?php endforeach; ?>
<?php endif; ?>
</div>


</section>

<?php require __DIR__ . '/../parties/pagination.php'; ?>

<?php foreach ($projects as $project): ?>
<?php
    $imageSrc = '';
    if (!empty($project['image_url'])) {
        if (str_starts_with($project['image_url'], 'http://') || str_starts_with($project['image_url'], 'https://')) {
            $imageSrc = $project['image_url'];
        } else {
            $imageSrc = '../public/images/projets/' . $project['image_url'];
        }
    }
?>
<div class="modal modal-detail" id="modal-projet-<?= $project['id']; ?>" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Détail projet">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><?= htmlspecialchars($project['title']); ?></h2>
            <div class="modal-header-actions">
                <?php if ($utilisateurConnecte): ?>
                    <button
                        type="button"
                        class="favori-toggle favori-toggle-detail <?= !empty($project['is_favori']) ? 'is-active' : '' ?>"
                        data-favori-toggle
                        data-favori-type="projet"
                        data-favori-id="<?= (int)$project['id']; ?>"
                        aria-pressed="<?= !empty($project['is_favori']) ? 'true' : 'false' ?>"
                        title="<?= !empty($project['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                        <i class="<?= !empty($project['is_favori']) ? 'fas' : 'far' ?> fa-heart" aria-hidden="true"></i>
                        <span data-favori-label><?= !empty($project['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?></span>
                    </button>
                <?php endif; ?>
                <button type="button" class="close-btn" data-projet-close-modal="<?= (int) $project['id']; ?>" aria-label="Fermer la modale">&times;</button>
            </div>
        </div>

        <div class="modal-body">
            <div class="modal-layout">
                <div class="modal-image-section">
                    <div class="modal-image">
                        <?php if (!empty($imageSrc)): ?>
                            <img src="<?= htmlspecialchars($imageSrc) ?>"
                                 alt="<?= htmlspecialchars($project['title']); ?>"
                                 class="js-fallback-next-image">
                            <i class="fas fa-code icone-fallback-cachee"></i>
                        <?php else: ?>
                            <i class="fas fa-code"></i>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="modal-content-section">
                    <div class="modal-description">
                        <?= nl2br(htmlspecialchars($project['description'])); ?>
                    </div>

                    <?php if (isset($project['technologies']) && trim($project['technologies']) !== ''): ?>
                        <div class="modal-section-title">
                            <i class="fas fa-microchip"></i> Technologies utilisées
                        </div>
                        <div class="modal-tags">
                            <?php
                            $techs = explode(',', $project['technologies']);
                            foreach ($techs as $tech): ?>
                                <span class="tag"><?= htmlspecialchars(trim($tech)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="action-buttons">
                        <a href="?page=projet&id=<?= $project['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Voir plus de détails
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<div id="modaleProjetCreation" class="modal-creation" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Création projet">
  <div class="modal-creation-content">
    <div class="modal-creation-header">
      <div>
        <h2><i class="fas fa-robot"></i> Créer un nouveau projet</h2>
        <p>Remplissez le formulaire ci-dessous pour publier un nouveau projet</p>
      </div>
      <button type="button" class="close-modal-creation" data-projet-close-creation="1" aria-label="Fermer la modale">&times;</button>
    </div>

    <div class="modal-creation-body">
      <form id="formProjet" enctype="multipart/form-data">
        <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>

        <p class="section-label"><i class="fas fa-info-circle"></i> Informations principales</p>

        <div class="form-group">
          <label for="projet_titre">Titre du projet *</label>
          <input type="text" name="titre" id="projet_titre" required minlength="3" maxlength="200" placeholder="Ex : Robot suiveur de ligne">
          <small id="titre_error_projet" class="champ-erreur"></small>
        </div>

        <div class="form-group">
          <label for="projet_auteur_affichage">Auteur</label>
          <input type="text" id="projet_auteur_affichage" value="<?= htmlspecialchars($_SESSION['utilisateur_nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled>
        </div>

        <div class="form-group">
          <label for="projet_categorie">Categorie</label>
          <select name="categorie" id="projet_categorie">
            <option value="">-- Sans catégorie --</option>
            <?php foreach (($categoriesProjet ?? []) as $categorieOption): ?>
              <option value="<?= htmlspecialchars($categorieOption, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($categorieOption) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="projet_description">Description courte *</label>
          <textarea name="description" id="projet_description" rows="3" required minlength="10" maxlength="500" placeholder="Une courte description du projet (affichée sur la carte)"></textarea>
          <small id="description_error_projet" class="champ-erreur"></small>
        </div>

        <p class="section-label"><i class="fas fa-file-alt"></i> Détails du projet</p>

        <div class="form-group">
          <label for="projet_description_detailed">Description détaillée</label>
          <textarea name="description_detailed" id="projet_description_detailed" rows="5" placeholder="Une description plus complète du projet..."></textarea>
        </div>

        <div class="form-group">
          <label for="projet_technologies">Technologies utilisées</label>
          <input type="text" name="technologies" id="projet_technologies" placeholder="Ex : Arduino, Moteurs DC, Capteur ultrasonique, Impression 3D">
          <div class="info-box">💡 Séparez les technologies par des virgules. Elles apparaîtront comme des badges sur la carte du projet.</div>
        </div>

        <div class="form-group">
          <label for="projet_features">Fonctionnalités principales</label>
          <textarea name="features" id="projet_features" rows="3" placeholder="Ex : Navigation autonome, Détection d'obstacles, Contrôle Bluetooth"></textarea>
          <div class="info-box">💡 Séparez les fonctionnalités par des virgules, comme pour les technologies.</div>
        </div>

        <div class="form-group">
          <label for="projet_challenges">Défis rencontrés</label>
          <textarea name="challenges" id="projet_challenges" rows="3" placeholder="Décrivez les difficultés techniques et comment vous les avez résolues..."></textarea>
        </div>

        <p class="section-label"><i class="fas fa-image"></i> Image du projet</p>

        <div class="form-group">
          <label for="projet_image_url">URL d'image (optionnel)</label>
          <input type="text" name="image_url" id="projet_image_url" placeholder="https://exemple.com/image.jpg">
          <div class="info-box">💡 Coller une URL d'image depuis Google, Discord, Wikipedia, etc.</div>

          <div id="projetImagePreviewContainer" class="image-preview-container">
            <p class="apercu-titre"><i class="fas fa-eye"></i> Aperçu :</p>
            <img id="projetImagePreview" alt="Aperçu">
          </div>
        </div>

        <div class="form-group">
          <label for="projet_image_file">OU uploader une image</label>
          <input type="file" name="image" id="projet_image_file" accept="image/*">
          <div class="info-box">💡 Si vous uploadez un fichier, il aura priorité sur l'URL.</div>

          <div id="projetLocalPreviewContainer" class="image-preview-container">
            <p class="apercu-titre">
              <i class="fas fa-eye"></i> Aperçu du fichier :
            </p>
            <img id="projetLocalPreview" class="apercu-image-locale" alt="Aperçu local">
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" data-projet-close-creation="1">
            <i class="fas fa-times"></i> Annuler
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Créer le projet
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
</main>

<?php $publicScripts = ['js/projets-page.js', 'js/projets-catalogue.js']; ?>

<?php require __DIR__ . '/../parties/public-layout-end.php'; ?>
