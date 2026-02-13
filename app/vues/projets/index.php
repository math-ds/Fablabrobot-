<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
require __DIR__ . '/../parties/header.php';
?>

<link rel="stylesheet" href="<?= $baseUrl ?>css/projets.css">

<div class="particles" id="particles"></div>

<section class="hero-section">
  <div class="hero-content">
    <h1 class="hero-title">Les Projets</h1>
    <p class="hero-subtitle">
      Découvrez les projets réalisés au sein du Fablab d'AJC Formation.
      Cette section met en avant des réalisations développées autour de la robotique, de l'électronique et des technologies numériques.
    </p>
  </div>
</section>
<section class="section-recherche">
  <div class="search-filter">
  <input type="text" id="searchInput" placeholder=" Rechercher un projet...">
  <select id="categoryFilter">
    <option value="all">Toutes les catégories</option>
    <option value="robotique">Robotique</option>
    <option value="drone">Drone</option>
    <option value="autres">Autres</option>
  </select>
</div>
</section>


<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['utilisateur_role'] ?? '';
?>

<?php if (in_array($role, ['Admin', 'Éditeur'], true)): ?>
  <div class="projet-action">
    <button onclick="ouvrirModaleProjet()" class="btn-create">
      <i class="fas fa-plus-circle"></i> Créer un projet
    </button>
  </div>
<?php endif; ?>



<section class="featured-section">
    <h2 class="section-title">Projets récents</h2>

   <div class="projects-grid">
<?php foreach ($projects as $project): ?>

    <?php
   
    $txt = strtolower($project['title'] . ' ' . $project['description'] . ' ' . ($project['technologies'] ?? ''));

    if (str_contains($txt, "drone") || str_contains($txt, "fpv") || str_contains($txt, "quad")) {
        $categorie = "drone";
    } elseif (str_contains($txt, "robot") || str_contains($txt, "moteur") || str_contains($txt, "arduino") || str_contains($txt, "servo")) {
        $categorie = "robotique";
    } else {
        $categorie = "autres";
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
         onclick="openModal(<?= $project['id']; ?>)">

        <div class="project-image">
            <?php if (!empty($imageSrc)): ?>
                <img src="<?= htmlspecialchars($imageSrc) ?>"
                     alt="<?= htmlspecialchars($project['title']) ?>"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <i class="fas fa-code" style="display:none;"></i>
            <?php else: ?>
                <i class="fas fa-code"></i>
            <?php endif; ?>
        </div>

        <div class="project-content">
            <h3 class="project-title"><?= htmlspecialchars($project['title']) ?></h3>
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
</div>


</section>


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
<div class="modal" id="modal-<?= $project['id']; ?>">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><?= htmlspecialchars($project['title']); ?></h2>
            <button class="close-btn" onclick="closeModal(<?= $project['id']; ?>)">&times;</button>
        </div>

        <div class="modal-body">
            <div class="modal-layout">
                <div class="modal-image-section">
                    <div class="modal-image">
                        <?php if (!empty($imageSrc)): ?>
                            <img src="<?= htmlspecialchars($imageSrc) ?>" 
                                 alt="<?= htmlspecialchars($project['title']); ?>"
                                 onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <i class="fas fa-code" style="display:none;"></i>
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

<!-- MODALE CRÉATION PROJET -->
<div id="modaleProjetCreation" class="modal-creation">
  <div class="modal-creation-content">
    <div class="modal-creation-header">
      <div>
        <h2><i class="fas fa-robot"></i> Créer un nouveau projet</h2>
        <p>Remplissez le formulaire ci-dessous pour publier un nouveau projet</p>
      </div>
      <button class="close-modal-creation" onclick="fermerModaleProjet()">&times;</button>
    </div>

    <div class="modal-creation-body">
      <div id="alertProjet"></div>

      <form id="formProjet" enctype="multipart/form-data">
        <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>

        <p class="section-label"><i class="fas fa-info-circle"></i> Informations principales</p>

        <div class="form-group">
          <label>Titre du projet *</label>
          <input type="text" name="titre" id="projet_titre" required minlength="3" maxlength="200" placeholder="Ex : Robot suiveur de ligne">
          <small id="titre_error_projet" style="color: #ff6b6b; display: none;"></small>
        </div>

        <div class="form-group">
          <label>Auteur *</label>
          <input type="text" name="auteur" id="projet_auteur" value="<?= htmlspecialchars($_SESSION['utilisateur_nom'] ?? '') ?>" required minlength="2" maxlength="100" placeholder="Votre nom">
          <small id="auteur_error_projet" style="color: #ff6b6b; display: none;"></small>
        </div>

        <div class="form-group">
          <label>Description courte *</label>
          <textarea name="description" id="projet_description" rows="3" required minlength="10" maxlength="500" placeholder="Une courte description du projet (affichée sur la carte)"></textarea>
          <small id="description_error_projet" style="color: #ff6b6b; display: none;"></small>
        </div>

        <p class="section-label"><i class="fas fa-file-alt"></i> Détails du projet</p>

        <div class="form-group">
          <label>Description détaillée</label>
          <textarea name="description_detailed" id="projet_description_detailed" rows="5" placeholder="Une description plus complète du projet..."></textarea>
        </div>

        <div class="form-group">
          <label>Technologies utilisées</label>
          <input type="text" name="technologies" id="projet_technologies" placeholder="Ex : Arduino, Moteurs DC, Capteur ultrasonique, Impression 3D">
          <div class="info-box">💡 Séparez les technologies par des virgules. Elles apparaîtront comme des badges sur la carte du projet.</div>
        </div>

        <div class="form-group">
          <label>Fonctionnalités principales</label>
          <textarea name="features" id="projet_features" rows="3" placeholder="Ex : Navigation autonome, Détection d'obstacles, Contrôle Bluetooth"></textarea>
          <div class="info-box">💡 Séparez les fonctionnalités par des virgules, comme pour les technologies.</div>
        </div>

        <div class="form-group">
          <label>Défis rencontrés</label>
          <textarea name="challenges" id="projet_challenges" rows="3" placeholder="Décrivez les difficultés techniques et comment vous les avez résolues..."></textarea>
        </div>

        <p class="section-label"><i class="fas fa-image"></i> Image du projet</p>

        <div class="form-group">
          <label>URL d'image (optionnel)</label>
          <input type="text" name="image_url" id="projet_image_url" placeholder="https://exemple.com/image.jpg" oninput="previewProjetImage(this.value)">
          <div class="info-box">💡 Coller une URL d'image depuis Google, Discord, Wikipedia, etc.</div>

          <div id="projetImagePreviewContainer" class="image-preview-container">
            <p style="color:#00afa7; font-weight:600; margin-bottom:8px;"><i class="fas fa-eye"></i> Aperçu :</p>
            <img id="projetImagePreview" alt="Aperçu">
          </div>
        </div>

        <div class="form-group">
          <label>OU uploader une image</label>
          <input type="file" name="image" id="projet_image_file" accept="image/*" onchange="previewProjetLocalImage(this)">
          <div class="info-box">💡 Si vous uploadez un fichier, il aura priorité sur l'URL.</div>

          <div id="projetLocalPreviewContainer" class="image-preview-container">
            <p style="color: #00afa7; font-weight: 600; margin-bottom: 10px;">
              <i class="fas fa-eye"></i> Aperçu du fichier :
            </p>
            <img id="projetLocalPreview" style="max-width: 100%; max-height: 200px; border-radius: 10px; border: 2px solid #00afa7;" alt="Aperçu local">
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="fermerModaleProjet()">
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

<script>
// Gestion modale projet
window.ouvrirModaleProjet = function() {
  const modal = document.getElementById('modaleProjetCreation');
  const form = document.getElementById('formProjet');
  const alert = document.getElementById('alertProjet');
  const previewUrl = document.getElementById('projetImagePreviewContainer');
  const previewLocal = document.getElementById('projetLocalPreviewContainer');

  if (!modal) {
    console.error('Modale projet non trouvée');
    return;
  }

  modal.classList.add('active');
  if (form) form.reset();
  if (alert) alert.innerHTML = '';
  if (previewUrl) previewUrl.classList.remove('active');
  if (previewLocal) previewLocal.classList.remove('active');
};

window.fermerModaleProjet = function() {
  const modal = document.getElementById('modaleProjetCreation');
  if (modal) {
    modal.classList.remove('active');
  }
};

// Fermer avec Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const modal = document.getElementById('modaleProjetCreation');
    if (modal && modal.classList.contains('active')) {
      window.fermerModaleProjet();
    }
  }
});

// Fermer en cliquant à l'extérieur
document.addEventListener('click', function(event) {
  const modal = document.getElementById('modaleProjetCreation');
  if (event.target === modal) {
    window.fermerModaleProjet();
  }
});

// Preview image URL
let projetImageTimeout = null;
function previewProjetImage(url) {
  const preview = document.getElementById('projetImagePreview');
  const container = document.getElementById('projetImagePreviewContainer');

  if (projetImageTimeout) clearTimeout(projetImageTimeout);
  container.classList.remove('active');

  if (url && url.trim() !== '') {
    preview.style.opacity = '0.3';
    container.classList.add('active');

    projetImageTimeout = setTimeout(() => {
      preview.style.opacity = '1';
    }, 5000);

    preview.src = url;
    preview.onload = () => {
      clearTimeout(projetImageTimeout);
      preview.style.opacity = '1';
    };
    preview.onerror = () => {
      clearTimeout(projetImageTimeout);
      container.classList.remove('active');
    };
  }
}

// Preview image locale avec validation
function previewProjetLocalImage(input) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const alertDiv = document.getElementById('alertProjet');

    // Validation du type de fichier
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
      alertDiv.innerHTML = '<div class="alert-modal alert-danger">Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.</div>';
      input.value = '';
      return;
    }

    // Validation de la taille (5 Mo max)
    const maxSize = 5 * 1024 * 1024; // 5 Mo
    if (file.size > maxSize) {
      alertDiv.innerHTML = '<div class="alert-modal alert-danger">Le fichier est trop volumineux. Taille maximale : 5 Mo.</div>';
      input.value = '';
      return;
    }

    // Preview
    const reader = new FileReader();
    const container = document.getElementById('projetLocalPreviewContainer');

    reader.onload = (e) => {
      document.getElementById('projetLocalPreview').src = e.target.result;
      container.classList.add('active');
      alertDiv.innerHTML = '';
    };

    reader.onerror = () => {
      alertDiv.innerHTML = '<div class="alert-modal alert-danger">Erreur lors de la lecture du fichier.</div>';
      input.value = '';
    };

    reader.readAsDataURL(file);
  }
}

// Validation côté client
function validerFormProjet() {
  const titre = document.getElementById('projet_titre');
  const auteur = document.getElementById('projet_auteur');
  const description = document.getElementById('projet_description');
  let isValid = true;

  // Reset erreurs
  document.querySelectorAll('small[id$="_error_projet"]').forEach(el => el.style.display = 'none');

  // Validation titre
  if (titre.value.trim().length < 3 || titre.value.trim().length > 200) {
    document.getElementById('titre_error_projet').textContent = 'Le titre doit contenir entre 3 et 200 caractères';
    document.getElementById('titre_error_projet').style.display = 'block';
    isValid = false;
  }

  // Validation auteur
  if (auteur.value.trim().length < 2 || auteur.value.trim().length > 100) {
    document.getElementById('auteur_error_projet').textContent = "L'auteur doit contenir entre 2 et 100 caractères";
    document.getElementById('auteur_error_projet').style.display = 'block';
    isValid = false;
  }

  // Validation description
  if (description.value.trim().length < 10 || description.value.trim().length > 500) {
    document.getElementById('description_error_projet').textContent = 'La description doit contenir entre 10 et 500 caractères';
    document.getElementById('description_error_projet').style.display = 'block';
    isValid = false;
  }

  return isValid;
}

// Soumission formulaire AJAX avec sécurité
let projetSubmitting = false;
const formProjet = document.getElementById('formProjet');
if (formProjet) {
  formProjet.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Empêcher la double soumission - vérifier IMMÉDIATEMENT
    if (projetSubmitting) {
      console.warn('Soumission déjà en cours, ignorée');
      return;
    }

    // Désactiver IMMÉDIATEMENT le bouton avant toute validation
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const alertDiv = document.getElementById('alertProjet');

    projetSubmitting = true;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création...';

    // Validation côté client
    if (!validerFormProjet()) {
      // Réactiver le bouton si la validation échoue
      projetSubmitting = false;
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Créer le projet';
      return;
    }

    const formData = new FormData(e.target);

  try {
    // Récupérer le token CSRF du meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch('?page=projet_enregistrer', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrfToken && { 'X-CSRF-Token': csrfToken })
      },
      credentials: 'same-origin'
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();

    if (result.success) {
      alertDiv.innerHTML = `<div class="alert-modal alert-success">${result.message}</div>`;
      e.target.reset();
      setTimeout(() => {
        fermerModaleProjet();
        location.reload();
      }, 1500);
    } else {
      alertDiv.innerHTML = `<div class="alert-modal alert-danger">${result.message || 'Une erreur est survenue'}</div>`;
    }
  } catch (error) {
    console.error('Erreur:', error);
    alertDiv.innerHTML = '<div class="alert-modal alert-danger">Erreur lors de la création du projet. Veuillez réessayer.</div>';
  } finally {
    projetSubmitting = false;
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Créer le projet';
  }
  });
} else {
  console.error('Formulaire projet non trouvé');
}

console.log('Script modale projet chargé');
</script>

<script>
function createParticles() {
    const particles = document.getElementById('particles');
    const particleCount = 45;
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 6 + 's';
        particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
        particles.appendChild(particle);
    }
}

function openModal(id) {
    document.getElementById('modal-' + id).style.display = 'block';
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById('modal-' + id).style.display = 'none';
    document.body.style.overflow = 'auto';
}
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}
document.addEventListener('DOMContentLoaded', createParticles);
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
        document.body.style.overflow = 'auto';
    }
});
document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("searchInput");
  const categoryFilter = document.getElementById("categoryFilter");
  const cards = document.querySelectorAll(".project-card");

  function filtrer() {
    const searchText = searchInput.value.toLowerCase();
    const selectedCategory = categoryFilter.value.toLowerCase();

    cards.forEach(card => {
      const title = card.querySelector("h3, .project-titre").textContent.toLowerCase();
      const category = card.dataset.categorie?.toLowerCase() || "autre";

      const matchTexte = title.includes(searchText);
      const matchCategorie = selectedCategory === "all" || category === selectedCategory;

      card.style.display = (matchTexte && matchCategorie) ? "block" : "none";
      
    });
  }

  searchInput.addEventListener("input", filtrer);
  categoryFilter.addEventListener("change", filtrer);
});
</script>

<?php require __DIR__ . '/../parties/footer.php'; ?>