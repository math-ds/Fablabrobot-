<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion WebTV - Admin FABLAB</title>

  <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirMetaJeton(); ?>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/admin-common.css">
  <link rel="stylesheet" href="css/admin-webtv.css">
  <link rel="stylesheet" href="css/toast-notification.css">
</head>

<body>
<div class="admin-container">
  <aside class="sidebar">
    <div>
            <div class="sidebar-logo">
                <a href="?page=admin">
                    <img src="images/global/ajc_logo_blanc.png" alt="AJC Logo">
                </a>
            </div>
      <?php include __DIR__ . '/../parties/sidebar.php'; ?>
    </div>
    <div class="sidebar-footer">
      <a href="?page=logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
  </aside>

  <main class="main-content">
    <header class="admin-header">
      <div class="search-bar">
        <input type="text" id="champRecherche" placeholder="Rechercher une vidéo...">
      </div>
    </header>

    <section class="dashboard">
      <h1><i class="fab fa-youtube"></i> Gestion des vidéos YouTube</h1>

      <?php if (!empty($_SESSION['message'])): ?>
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            ToastNotification.<?= $_SESSION['message_type'] === 'success' ? 'succes' : 'erreur' ?>(
              <?= json_encode($_SESSION['message'], JSON_UNESCAPED_UNICODE) ?>
            );
          });
        </script>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
      <?php endif; ?>

      <!-- Cartes de statistiques standardisées -->
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Vidéos YouTube</h3>
          <div class="value"><?= count($videos ?? []) ?></div>
        </div>
      </div>

      <?php if (empty($videos)): ?>
        <div class="empty-state">
          <i class="fab fa-youtube"></i>
          <p>Aucune vidéo YouTube enregistrée pour le moment. Créez-en une pour commencer !</p>
        </div>
      <?php else: ?>
        <!-- Table vidéos -->
        <div class="table-container">
          <div class="table-header">
            <h3 class="table-title">
              <i class="fab fa-youtube"></i> Vidéos YouTube
            </h3>
            <div class="table-actions">
              <button class="btn btn-primary" onclick="ouvrirModale('create')">
                <i class="fas fa-plus"></i> Nouvelle Vidéo
              </button>
              <span class="stats-badge"><i class="fab fa-youtube"></i> <?= $totalVideos ?? 0 ?> vidéo(s)</span>
            </div>
          </div>

          <div class="users-table">
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
                  <td class="table-video-cell" style="text-align: center;">
                    <?php if (!empty($video['vignette'])): ?>
                      <img src="<?= htmlspecialchars($video['vignette']) ?>" alt="Miniature" class="video-thumb">
                    <?php else: ?>
                      <div class="modern-placeholder no-image video-placeholder">
                        <i class="fas fa-video"></i>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td><strong style="color: var(--primary-color);"><?= htmlspecialchars($video['titre']) ?></strong></td>
                  <td style="color: var(--text-muted);"><?= htmlspecialchars($video['categorie']) ?></td>
                  <td>
                    <span style="color:#ff4d4d;"><i class="fab fa-youtube"></i> YouTube</span>
                  </td>
                  <td><?= date('d/m/Y', strtotime($video['created_at'])) ?></td>
                  <td class="text-center">
                    <button class="btn btn-warning btn-sm" onclick='ouvrirModale("update", <?= json_encode($video, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Modifier">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="supprimerVideo(<?= $video['id'] ?>, '<?= htmlspecialchars($video['titre'], ENT_QUOTES) ?>')" title="Supprimer">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

      <script id="donneesVideos" type="application/json">
<?= json_encode($videos ?? []) ?>
</script>
    </section>
  </main>
</div>

<!-- Modal vidéo -->
<div id="modaleVideo" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="titreModale">Nouvelle vidéo YouTube</h2>
      <button class="close-modal" onclick="fermerModale()">&times;</button>
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
        <label for="categorie">Catégorie *</label>
        <input type="text" id="categorie" name="categorie" required>
      </div>

      <div class="form-group">
        <label for="youtube_url">URL YouTube *</label>
        <input type="url" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=VIDEO_ID ou https://youtu.be/VIDEO_ID" oninput="traiterUrlYoutube(this.value)" required>
      </div>

      <div class="form-group">
        <label for="vignette">Miniature YouTube</label>
        <input type="hidden" id="vignette" name="vignette">

        <div class="info-box">
          <i class="fas fa-youtube"></i>
          <div>
            <strong>La miniature est automatiquement générée depuis YouTube</strong><br>
            • Collez l'URL YouTube ci-dessus pour voir l'aperçu<br>
            • L'image provient directement de YouTube (hqdefault.jpg)
          </div>
        </div>

        <div id="conteneurApercuImage" class="image-preview-container">
          <p class="preview-label">
            <i class="fas fa-eye"></i> Aperçu de la miniature YouTube :
          </p>
          <div class="preview-wrapper">
            <img id="apercuImage" alt="Miniature YouTube" style="display:none; width: 200px; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid var(--card-border);">
            <div id="spinnerChargementImage" class="image-loading-spinner" style="display:none;">
              <i class="fas fa-spinner fa-spin"></i>
            </div>
            <div id="placeholderMiniature" class="youtube-placeholder">
              <i class="fab fa-youtube"></i>
              <p>Collez l'URL YouTube pour voir la miniature</p>
            </div>
          </div>
        </div>

        <div id="erreurApercuImage" class="image-preview-error" style="display:none;">
          <i class="fas fa-exclamation-triangle"></i>
          <div>
            <strong>Impossible de charger la miniature</strong><br>
            Vérifiez que l'URL YouTube est correcte. La vidéo sera quand même enregistrée.
          </div>
        </div>
      </div>



      <div class="form-actions">
        <button type="button" class="btn btn-danger" onclick="fermerModale()">
          <i class="fas fa-times"></i> Annuler
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Enregistrer
        </button>
      </div>
    </form>
  </div>
</div>

<script src="js/securite-helper.js"></script>
<script src="js/ajax-helper.js"></script>
<script src="js/toast-notification.js"></script>
<script src="js/recherche-helper.js"></script>
<script src="js/csrf_manager.js"></script>
<script src="js/image-preview-helper.js"></script>
<script src="js/gestion-webtv.js"></script>

<script>
  // Initialiser la recherche améliorée
  document.addEventListener('DOMContentLoaded', function() {
    RechercheHelper.initialiser('champRecherche', '#tableauVideos tbody tr');
  });
</script>

<!-- JavaScript menu mobile -->
<script src="js/admin-mobile-menu.js"></script>

</body>
</html>