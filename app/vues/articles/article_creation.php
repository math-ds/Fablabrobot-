<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$titrePage = 'Créer un Article - FABLAB';
$pageCss = ['article.css', 'formulaires-creation.css'];

include(__DIR__ . '/../parties/public-layout-start.php');
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<main class="creation-main">
<div class="creation-container">
  <div class="creation-header">
    <h1><i class="fas fa-pen-nib"></i> Créer un nouvel article</h1>
    <p class="creation-sous-titre">Remplissez le formulaire ci-dessous pour publier un nouveau article</p>
  </div>

  <?php if (!empty($_SESSION['message'])): ?>
    <div class="creation-alerte creation-alerte-<?= $_SESSION['message_type'] ?>">
      <?= htmlspecialchars($_SESSION['message']) ?>
    </div>
    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
  <?php endif; ?>

  <form action="?page=article_enregistrer" method="POST" enctype="multipart/form-data">
    <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>
    <div class="form-group">
      <label for="article_creation_titre">Titre *</label>
      <input type="text" id="article_creation_titre" name="titre" required placeholder="Titre de l'article...">
    </div>

    <div class="form-group">
      <label for="article_creation_auteur_affichage">Auteur</label>
      <input type="text" id="article_creation_auteur_affichage" value="<?= htmlspecialchars($_SESSION['utilisateur_nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled>
    </div>

    <div class="form-group">
      <label for="article_creation_categorie">Catégorie</label>
      <select id="article_creation_categorie" name="categorie">
        <option value="">— Sans catégorie —</option>
        <?php foreach (($categories ?? []) as $categorieOption): ?>
          <option value="<?= htmlspecialchars($categorieOption, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($categorieOption) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="article_creation_contenu">Contenu *</label>
      <textarea id="article_creation_contenu" name="contenu" rows="10" required placeholder="Contenu de l'article..."></textarea>
    </div>

    <div class="form-group">
      <label for="image_url">URL de l'image (optionnel)</label>
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

      <div id="imagePreviewError" class="creation-erreur-apercu-boite">
        <i class="fas fa-exclamation-triangle"></i> Impossible de charger l'image
      </div>
    </div>

    <div class="form-group">
      <label for="image_file">OU uploader une image locale (optionnel)</label>
      <input type="file" id="image_file" name="image" accept="image/*" data-article-create-image-file-input="1">

      <div class="info-box">
        <strong><i class="fas fa-upload"></i> Upload local :</strong><br>
        • Formats acceptés: PNG, JPG, JPEG, GIF, WebP<br>
        • Taille max: 5 Mo<br>
        • Si un fichier est choisi, il sera prioritaire sur l'URL
      </div>

      <div id="localPreviewArticleCreateContainer" class="creation-apercu-local">
        <p class="creation-apercu-titre">Aperçu du fichier :</p>
        <img id="localPreviewArticleCreate" class="creation-apercu-image" alt="Aperçu du fichier local">
      </div>
    </div>

    <div class="creation-form-actions">
      <button type="submit" class="creation-bouton creation-bouton-primaire">
        <i class="fas fa-paper-plane"></i> Publier
      </button>
      <a href="?page=articles" class="creation-bouton creation-bouton-secondaire">
        <i class="fas fa-times"></i> Annuler
      </a>
    </div>
  </form>
</div>
</main>

<?php $publicScripts = ['js/image-preview-helper.js', 'js/article-creation-page.js']; ?>

<?php include(__DIR__ . '/../parties/public-layout-end.php'); ?>
