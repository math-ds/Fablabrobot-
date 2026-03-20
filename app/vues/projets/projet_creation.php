<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$titrePage = 'Créer un Projet - FABLAB';
$pageCss = ['projets.css', 'formulaires-creation.css'];

include(__DIR__ . '/../parties/public-layout-start.php');
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<main class="creation-main">
<div class="creation-container">
  <div class="creation-header">
    <h1><i class="fas fa-robot"></i> Créer un nouveau projet</h1>
    <p class="creation-sous-titre">Remplissez le formulaire ci-dessous pour publier un nouveau projet</p>
  </div>

  <?php if (!empty($_SESSION['message'])): ?>
    <div class="creation-alerte creation-alerte-<?= $_SESSION['message_type'] ?>">
      <?= htmlspecialchars($_SESSION['message']) ?>
    </div>
    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
  <?php endif; ?>

  <form action="?page=projet_enregistrer" method="POST" enctype="multipart/form-data">
    <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>

    <p class="section-label"><i class="fas fa-info-circle"></i> Informations principales</p>

    <div class="form-group">
      <label for="projet_creation_titre">Titre du projet *</label>
      <input type="text" id="projet_creation_titre" name="titre" placeholder="Ex : Robot suiveur de ligne" required>
    </div>

    <div class="form-group">
      <label for="projet_creation_auteur_affichage">Auteur</label>
      <input type="text" id="projet_creation_auteur_affichage" value="<?= htmlspecialchars($_SESSION['utilisateur_nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled>
    </div>

    <div class="form-group">
      <label for="projet_creation_categorie">Catégorie</label>
      <select id="projet_creation_categorie" name="categorie" class="form-select">
        <option value="">— Sélectionner une catégorie —</option>
        <?php foreach (($categoriesProjet ?? []) as $categorieOption): ?>
          <option value="<?= htmlspecialchars($categorieOption, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($categorieOption) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="projet_creation_description">Description courte *</label>
      <textarea id="projet_creation_description" name="description" rows="3" placeholder="Une courte description du projet (affichée sur la carte)" required></textarea>
    </div>

    <hr class="section-separator">
    <p class="section-label"><i class="fas fa-file-alt"></i> Détails du projet</p>

    <div class="form-group">
      <label for="projet_creation_description_detailed">Description détaillée</label>
      <textarea id="projet_creation_description_detailed" name="description_detailed" rows="5" placeholder="Une description plus complète du projet..."></textarea>
    </div>

    <div class="form-group">
      <label for="projet_creation_technologies">Technologies utilisées</label>
      <input type="text" id="projet_creation_technologies" name="technologies" placeholder="Ex : Arduino, Moteurs DC, Capteur ultrasonique, Impression 3D">
      <div class="info-box">💡 Séparez les technologies par des virgules. Elles apparaîtront comme des badges sur la carte du projet.</div>
    </div>

    <div class="form-group">
      <label for="projet_creation_features">Fonctionnalités principales</label>
      <textarea id="projet_creation_features" name="features" rows="3" placeholder="Ex : Navigation autonome|Détection d'obstacles|Contrôle Bluetooth"></textarea>
      <div class="info-box">💡 Séparez les fonctionnalités par des <strong>|</strong> (barre verticale).</div>
    </div>

    <div class="form-group">
      <label for="projet_creation_challenges">Défis rencontrés</label>
      <textarea id="projet_creation_challenges" name="challenges" rows="3" placeholder="Décrivez les difficultés techniques et comment vous les avez résolues..."></textarea>
    </div>

    <hr class="section-separator">
    <p class="section-label"><i class="fas fa-image"></i> Image du projet</p>

    <div class="form-group">
      <label for="image_url">URL d'image (optionnel)</label>
      <input type="text" id="image_url" name="image_url" placeholder="https://exemple.com/image.jpg">
      <div class="info-box">💡 Coller une URL d'image depuis Google, Discord, Wikipedia, etc.</div>

      <div id="imagePreviewContainer" class="image-preview-container">
        <p class="creation-apercu-titre"><i class="fas fa-eye"></i> Aperçu :</p>
        <div class="creation-image-wrapper">
          <img id="imagePreview" alt="Aperçu">
          <div id="imageLoadingSpinner" class="creation-spinner-centre">
            <i class="fas fa-spinner fa-spin creation-spinner-icone"></i>
          </div>
        </div>
      </div>

      <div id="imagePreviewError" class="creation-erreur-apercu">
        Attention: Impossible de charger l'image
      </div>
    </div>

    <div class="form-group">
      <label for="creation_project_image_file">OU uploader une image</label>
      <input type="file" name="image" id="creation_project_image_file" accept="image/*">
      <div class="info-box">💡 Si vous uploadez un fichier, il aura priorité sur l'URL.</div>

      <div id="localPreviewContainer" class="creation-apercu-local">
        <p class="creation-apercu-titre">
          <i class="fas fa-eye"></i> Aperçu du fichier :
        </p>
        <img id="localPreview" class="creation-apercu-image" alt="Aperçu local">
      </div>
    </div>

    <div class="creation-form-actions">
      <button type="submit" class="creation-bouton creation-bouton-primaire">
        <i class="fas fa-paper-plane"></i> Créer le projet
      </button>
      <a href="?page=projets" class="creation-bouton creation-bouton-secondaire">
        <i class="fas fa-times"></i> Annuler
      </a>
    </div>
  </form>
</div>
</main>

<?php $publicScripts = ['js/image-preview-helper.js', 'js/projet-creation-page.js']; ?>

<?php include(__DIR__ . '/../parties/public-layout-end.php'); ?>
