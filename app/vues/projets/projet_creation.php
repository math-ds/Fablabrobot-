<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!in_array($_SESSION['utilisateur_role'] ?? '', ['Éditeur', 'Admin'], true)) {
    header('Location: ?page=projets');
    exit;
}

$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$titrePage = 'Créer un Projet - FABLAB';
$pageCss = ['projets.css'];

include(__DIR__ . '/../parties/header.php');
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
  .creation-container {
    max-width: 900px;
    margin: 40px auto;
    padding: 30px;
    background: rgba(20, 20, 25, 0.95);
    border-radius: 16px;
    border: 1px solid rgba(0, 175, 167, 0.3);
  }
  .creation-header {
    text-align: center;
    margin-bottom: 30px;
    color: #00afa7;
  }
  .creation-header h1 {
    font-size: 2rem;
    margin-bottom: 10px;
  }
  .section-separator {
    border: none;
    border-top: 1px solid rgba(0, 175, 167, 0.3);
    margin: 30px 0 10px;
  }
  .section-label {
    color: #00afa7;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 15px;
  }
  .section-label i {
    margin-right: 8px;
  }
  .form-group {
    margin-bottom: 20px;
  }
  .form-group label {
    display: block;
    margin-bottom: 8px;
    color: #f5f5f5;
    font-weight: 600;
  }
  .form-group input[type="text"],
  .form-group input[type="file"],
  .form-group textarea {
    width: 100%;
    padding: 12px;
    background: rgba(30, 30, 35, 0.9);
    border: 1px solid rgba(0, 175, 167, 0.3);
    border-radius: 8px;
    color: #f5f5f5;
    font-family: 'Inter', sans-serif;
  }
  .form-group input[type="text"]:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: #00afa7;
    box-shadow: 0 0 0 3px rgba(0, 175, 167, 0.1);
  }
  .image-preview-container {
    margin-top: 12px;
    display: none;
  }
  .image-preview-container img {
    max-width: 100%;
    max-height: 220px;
    border-radius: 8px;
    border: 2px solid #00afa7;
  }
  .info-box {
    background: rgba(0, 175, 167, 0.1);
    border: 1px solid rgba(0, 175, 167, 0.3);
    padding: 12px;
    border-radius: 8px;
    margin-top: 8px;
    font-size: 0.85rem;
    color: rgba(245, 245, 245, 0.7);
  }
  .form-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
  }
  .btn {
    padding: 12px 24px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }
  .btn-primary {
    background: #00afa7;
    color: white;
  }
  .btn-primary:hover {
    background: #008f88;
    transform: translateY(-2px);
  }
  .btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: #f5f5f5;
  }
  .btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
  }
  .alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
  }
  .alert-success {
    background: rgba(0, 175, 167, 0.1);
    border: 1px solid rgba(0, 175, 167, 0.3);
    color: #00afa7;
  }
  .alert-danger {
    background: rgba(255, 107, 107, 0.1);
    border: 1px solid rgba(255, 107, 107, 0.3);
    color: #ff6b6b;
  }
</style>

<div class="creation-container">
  <div class="creation-header">
    <h1><i class="fas fa-robot"></i> Créer un nouveau projet</h1>
    <p style="color: rgba(245, 245, 245, 0.7);">Remplissez le formulaire ci-dessous pour publier un nouveau projet</p>
  </div>

  <?php if (!empty($_SESSION['message'])): ?>
    <div class="alert alert-<?= $_SESSION['message_type'] ?>">
      <?= htmlspecialchars($_SESSION['message']) ?>
    </div>
    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
  <?php endif; ?>

  <form action="?page=projet_enregistrer" method="POST" enctype="multipart/form-data">

    <p class="section-label"><i class="fas fa-info-circle"></i> Informations principales</p>

    <div class="form-group">
      <label>Titre du projet *</label>
      <input type="text" name="titre" placeholder="Ex : Robot suiveur de ligne" required>
    </div>

    <div class="form-group">
      <label>Auteur *</label>
      <input type="text" name="auteur" value="<?= htmlspecialchars($_SESSION['utilisateur_nom'] ?? '') ?>" placeholder="Votre nom" required>
    </div>

    <div class="form-group">
      <label>Description courte *</label>
      <textarea name="description" rows="3" placeholder="Une courte description du projet (affichée sur la carte)" required></textarea>
    </div>

    <hr class="section-separator">
    <p class="section-label"><i class="fas fa-file-alt"></i> Détails du projet</p>

    <div class="form-group">
      <label>Description détaillée</label>
      <textarea name="description_detailed" rows="5" placeholder="Une description plus complète du projet..."></textarea>
    </div>

    <div class="form-group">
      <label>Technologies utilisées</label>
      <input type="text" name="technologies" placeholder="Ex : Arduino, Moteurs DC, Capteur ultrasonique, Impression 3D">
      <div class="info-box">💡 Séparez les technologies par des virgules. Elles apparaîtront comme des badges sur la carte du projet.</div>
    </div>

    <div class="form-group">
      <label>Fonctionnalités principales</label>
      <textarea name="features" rows="3" placeholder="Ex : Navigation autonome|Détection d'obstacles|Contrôle Bluetooth"></textarea>
      <div class="info-box">💡 Séparez les fonctionnalités par des <strong>|</strong> (barre verticale).</div>
    </div>

    <div class="form-group">
      <label>Défis rencontrés</label>
      <textarea name="challenges" rows="3" placeholder="Décrivez les difficultés techniques et comment vous les avez résolues..."></textarea>
    </div>

    <hr class="section-separator">
    <p class="section-label"><i class="fas fa-image"></i> Image du projet</p>

    <div class="form-group">
      <label>URL d'image (optionnel)</label>
      <input type="text" id="image_url" name="image_url" placeholder="https://exemple.com/image.jpg">
      <div class="info-box">💡 Coller une URL d'image depuis Google, Discord, Wikipedia, etc.</div>

      <div id="imagePreviewContainer" class="image-preview-container">
        <p style="color:#00afa7; font-weight:600; margin-bottom:8px;"><i class="fas fa-eye"></i> Aperçu :</p>
        <div style="position: relative;">
          <img id="imagePreview" alt="Aperçu">
          <div id="imageLoadingSpinner" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #00afa7;"></i>
          </div>
        </div>
      </div>

      <div id="imagePreviewError" style="display: none; color: #ff6b6b; margin-top: 8px;">
        ⚠️ Impossible de charger l'image
      </div>
    </div>

    <div class="form-group">
      <label>OU uploader une image</label>
      <input type="file" name="image" accept="image/*" onchange="previewLocalImage(this)">
      <div class="info-box">💡 Si vous uploadez un fichier, il aura priorité sur l'URL.</div>

      <div id="localPreviewContainer" style="margin-top: 15px; display: none;">
        <p style="color: #00afa7; font-weight: 600; margin-bottom: 10px;">
          <i class="fas fa-eye"></i> Aperçu du fichier :
        </p>
        <img id="localPreview" style="max-width: 100%; max-height: 200px; border-radius: 10px; border: 2px solid #00afa7;" alt="Aperçu local">
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-paper-plane"></i> Créer le projet
      </button>
      <a href="?page=projets" class="btn btn-secondary">
        <i class="fas fa-times"></i> Annuler
      </a>
    </div>
  </form>
</div>

<script src="js/image-preview-helper.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    ImagePreviewHelper.init({
      inputId: 'image_url',
      containerId: 'imagePreviewContainer',
      imgId: 'imagePreview',
      spinnerId: 'imageLoadingSpinner',
      errorId: 'imagePreviewError',
      useProxy: true
    });
  });

  function previewLocalImage(input) {
    ImagePreviewHelper.previewLocal(input, 'localPreview', 'localPreviewContainer');
  }
</script>

<?php include(__DIR__ . '/../parties/footer.php'); ?>
