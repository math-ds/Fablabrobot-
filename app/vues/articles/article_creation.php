<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!in_array($_SESSION['utilisateur_role'] ?? '', ['Éditeur', 'Admin'])) {
    header('Location: ?page=articles');
    exit;
}

$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$titrePage = 'Créer un Article - FABLAB';
$pageCss = ['article.css'];

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
    margin-top: 15px;
    display: none;
  }
  .image-preview-container img {
    max-width: 100%;
    max-height: 250px;
    border-radius: 8px;
    border: 2px solid #00afa7;
  }
  .info-box {
    background: rgba(0, 175, 167, 0.1);
    border: 1px solid rgba(0, 175, 167, 0.3);
    padding: 12px;
    border-radius: 8px;
    margin-top: 10px;
    font-size: 0.9rem;
    color: rgba(245, 245, 245, 0.8);
  }
  .loading-spinner {
    display: none;
    text-align: center;
    padding: 10px;
    color: #00afa7;
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
    <h1><i class="fas fa-pen-nib"></i> Créer un nouvel article</h1>
    <p style="color: rgba(245, 245, 245, 0.7);">Remplissez le formulaire ci-dessous pour publier un nouveau article</p>
  </div>

  <?php if (!empty($_SESSION['message'])): ?>
    <div class="alert alert-<?= $_SESSION['message_type'] ?>">
      <?= htmlspecialchars($_SESSION['message']) ?>
    </div>
    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
  <?php endif; ?>

  <form action="?page=article_enregistrer" method="POST" enctype="multipart/form-data">
    <div class="form-group">
      <label>Titre *</label>
      <input type="text" name="titre" required placeholder="Titre de l'article...">
    </div>

    <div class="form-group">
      <label>Auteur *</label>
      <input type="text" name="auteur" value="<?= htmlspecialchars($_SESSION['utilisateur_nom'] ?? '') ?>" required placeholder="Votre nom...">
    </div>

    <div class="form-group">
      <label>Contenu *</label>
      <textarea name="contenu" rows="10" required placeholder="Contenu de l'article..."></textarea>
    </div>

    <div class="form-group">
      <label>URL de l'image (optionnel)</label>
      <input type="text"
             id="image_url"
             name="image_url"
             placeholder="https://exemple.com/image.jpg">

      <div class="info-box">
        <strong>💡 Astuce :</strong> Vous pouvez coller n'importe quelle URL d'image depuis Google, Discord, etc.
      </div>

      <div id="imagePreviewContainer" class="image-preview-container">
        <p><strong>Aperçu :</strong></p>
        <img id="imagePreview" alt="Aperçu de l'image">
        <div id="imageLoadingSpinner" class="loading-spinner">
          <i class="fas fa-spinner fa-spin"></i> Chargement...
        </div>
      </div>

      <div id="imagePreviewError" style="display: none; margin-top: 10px; padding: 10px; background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.3); border-radius: 8px; color: #ff6b6b;">
        <i class="fas fa-exclamation-triangle"></i> Impossible de charger l'image
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-paper-plane"></i> Publier
      </button>
      <a href="?page=articles" class="btn btn-secondary">
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
</script>

<?php include(__DIR__ . '/../parties/footer.php'); ?>
