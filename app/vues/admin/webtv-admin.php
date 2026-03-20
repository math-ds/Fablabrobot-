<?php
$auteurFormulaireVideo = trim((string)($_SESSION['utilisateur_nom'] ?? ''));
if ($auteurFormulaireVideo === '') {
    $auteurFormulaireVideo = 'Administrateur';
}

$adminTitle = 'Gestion WebTV - Admin FABLAB';
$adminCss = ['admin-webtv.css'];

require __DIR__ . '/../parties/admin-layout-start.php';
?>


    <section class="dashboard" data-admin-webtv="1">
      <h1>Gestion des vidéos YouTube</h1>

      
    <h2 class="sr-only">Sections principales</h2>
<?php if (!empty($_SESSION['message'])): ?>
        <div
          id="adminFlashData"
          hidden
          data-flash-type="<?= htmlspecialchars((string)($_SESSION['message_type'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>"
          data-flash-message="<?= htmlspecialchars((string)$_SESSION['message'], ENT_QUOTES, 'UTF-8') ?>"></div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
      <?php endif; ?>

      <div class="stats-grid">
        <div class="stat-card">
          <h3>Vidéos YouTube</h3>
          <div class="value"><?= $totalVideos ?? 0 ?></div>
        </div>
      </div>

      <?php if ((int)($totalVideos ?? 0) === 0): ?>
        <div class="empty-state">
          <i class="fab fa-youtube"></i>
          <p>Aucune vidéo YouTube enregistrée pour le moment. Créez-en une pour commencer !</p>
        </div>
      <?php else: ?>
        <div class="table-container">
          <div class="table-header">
            <h3 class="table-title">
              <i class="fab fa-youtube"></i> Vidéos YouTube
            </h3>
            <div class="table-actions">
              <button type="button" class="btn btn-primary" data-webtv-open-create="1">
                <i class="fas fa-plus"></i> Nouvelle Vidéo
              </button>
              <?php
                $filtrageActif = trim((string)($recherche ?? '')) !== '';
                $compteurEntete = $filtrageActif ? (int)($totalFiltres ?? 0) : (int)($totalVideos ?? 0);
                $libelleEntete = $filtrageActif ? 'résultat(s)' : 'video(s)';
              ?>
              <span class="stats-badge"><i class="fab fa-youtube"></i> <?= $compteurEntete ?> <?= $libelleEntete ?></span>
            </div>
          </div>
                    <div class="table-search">
                        <div class="search-bar">
                            <input type="text" id="champRecherche" value="<?= htmlspecialchars((string)($recherche ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Rechercher une vid&eacute;o...">
                        </div>
                    </div>


          <div class="users-table">
            <?php if ((int)($totalFiltres ?? 0) === 0): ?>
              <div class="empty-state">
                <i class="fas fa-search"></i>
                <h2>Aucun résultat</h2>
                <p>Aucune vidéo ne correspond à la recherche actuelle.</p>
              </div>
            <?php else: ?>
            <table id="tableauVideos">
              <thead>
                <tr>
                  <th class="col-small">Miniature</th>
                  <th class="col-large">Titre</th>
                  <th class="col-medium">Catégorie</th>
                  <th class="col-small">Plateforme</th>
                  <th class="col-date">Date</th>
                  <th class="col-actions text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($videos as $video): ?>
                <tr data-video-id="<?= $video['id'] ?>">
                  <td class="table-video-cell admin-text-center" data-label="Miniature" data-col="image">
                    <?php if (!empty($video['vignette'])): ?>
                      <div class="image-container">
                        <img
                          src="<?= htmlspecialchars((string)$video['vignette'], ENT_QUOTES, 'UTF-8') ?>"
                          alt="Miniature"
                          class="video-thumb"
                          data-proxy-src-on-error="<?= htmlspecialchars((string)$video['vignette'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="no-image-fallback admin-hidden">
                          <i class="fas fa-link"></i>
                          <span>Miniature indisponible</span>
                        </div>
                      </div>
                    <?php else: ?>
                      <div class="modern-placeholder no-image video-placeholder">
                        <i class="fas fa-video"></i>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td data-label="Titre" data-col="titre"><strong class="admin-text-primary"><?= htmlspecialchars($video['titre']) ?></strong></td>
                  <td class="admin-text-muted" data-label="Catégorie" data-col="categorie">
                    <span class="badge-categorie"><?= htmlspecialchars($video['categorie']) ?></span>
                  </td>
                  <td data-label="Plateforme" data-col="plateforme">
                    <span class="badge-categorie badge-platform-youtube"><i class="fab fa-youtube"></i> YouTube</span>
                  </td>
                  <td data-label="Date" data-col="date"><?= date('d/m/Y', strtotime($video['created_at'])) ?></td>
                  <td class="text-center" data-label="Actions" data-col="actions">
                    <button
                      type="button"
                      class="btn btn-warning btn-sm"
                      data-webtv-edit="<?= htmlspecialchars(json_encode($video, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"
                      title="Modifier">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button
                      type="button"
                      class="btn btn-danger btn-sm"
                      data-webtv-delete-id="<?= (int) $video['id'] ?>"
                      data-webtv-delete-title="<?= htmlspecialchars($video['titre'], ENT_QUOTES, 'UTF-8') ?>"
                      title="Supprimer">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <?php endif; ?>
          </div>
        </div>
        <?php if ((int)($totalFiltres ?? 0) > 0): ?>
          <div class="admin-pagination">
            <?php require __DIR__ . '/../parties/pagination.php'; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <script id="donneesVideos" type="application/json">
<?= json_encode($videos ?? []) ?>
</script>
    </section>
<div id="modaleVideo" class="modal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Gestion vidéo">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="titreModale">Nouvelle vidéo YouTube</h2>
      <button type="button" class="close-modal" data-webtv-close-modal="1" aria-label="Fermer la modale">&times;</button>
    </div>

    <form id="formulaireVideo" method="POST" action="?page=admin-webtv">
      <input type="hidden" name="action" id="actionFormulaire" value="create">
      <input type="hidden" name="id" id="idVideo">
      <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>

      <div class="form-group">
        <label for="titre">Titre *</label>
        <input type="text" id="titre" name="titre" required>
      </div>

      <div class="form-group">
        <label for="description">Description *</label>
        <textarea id="description" name="description" required></textarea>
      </div>

      <div class="form-group">
        <label for="auteur_affiche">Auteur</label>
        <input type="text"
               id="auteur_affiche"
               value="<?= htmlspecialchars($auteurFormulaireVideo, ENT_QUOTES, 'UTF-8') ?>"
               data-current-user="<?= htmlspecialchars($auteurFormulaireVideo, ENT_QUOTES, 'UTF-8') ?>"
               disabled>
      </div>

      <div class="form-group">
        <label for="categorie">Catégorie *</label>
        <select id="categorie" name="categorie" required>
          <option value="">Sélectionner une catégorie</option>
          <?php foreach (($categoriesVideo ?? []) as $categorieOption): ?>
            <option value="<?= htmlspecialchars($categorieOption, ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($categorieOption) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="youtube_url">URL YouTube *</label>
        <input type="url" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watchv=VIDEO_ID ou https://youtu.be/VIDEO_ID" data-webtv-youtube-input="1" required>
      </div>

      <div class="form-group">
        <label for="vignette">Miniature YouTube</label>
        <input type="hidden" id="vignette" name="vignette">

        <div class="info-box">
          <i class="fas fa-youtube"></i>
          <div>
            <strong>La miniature est automatiquement générée depuis YouTube</strong><br>
            €¢ Collez l'URL YouTube ci-dessus pour voir l'aperçu<br>
            €¢ L'image provient directement de YouTube (hqdefault.jpg)
          </div>
        </div>

        <div id="conteneurApercuImage" class="image-preview-container">
          <p class="preview-label"><i class="fas fa-eye"></i> Aperçu de la miniature YouTube :</p>
          <div class="preview-wrapper">
            <img id="apercuImage" class="admin-preview-image" alt="Miniature YouTube">
            <div id="spinnerChargementImage" class="image-loading-spinner">
              <i class="fas fa-spinner fa-spin"></i>
            </div>
            <div id="placeholderMiniature" class="youtube-placeholder">
              <i class="fab fa-youtube"></i>
              <p>Collez l'URL YouTube pour voir la miniature</p>
            </div>
          </div>
        </div>

        <div id="erreurApercuImage" class="image-preview-error">
          <i class="fas fa-exclamation-triangle"></i>
          <div>
            <strong>Impossible de charger la miniature</strong><br>
            Vérifiez que l'URL YouTube est correcte. La vidéo sera quand même enregistrée.
          </div>
        </div>
      </div>



      <div class="form-actions">
        <button type="button" class="btn btn-danger" data-webtv-close-modal="1">
          <i class="fas fa-times"></i> Annuler
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Enregistrer
        </button>
      </div>
    </form>
  </div>
</div>


<?php
$adminScripts = ['js/securite-helper.js', 'js/ajax-helper.js', 'js/toast-notification.js', 'js/recherche-helper.js', 'js/csrf_manager.js', 'js/image-preview-helper.js', 'js/gestion-webtv.js'];
require __DIR__ . '/../parties/admin-layout-end.php';
?>


