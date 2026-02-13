<?php
/* ============================================================
   VUE : articles.php
   Les données ($articles) sont transmises par ArticlesControleur->index()
   Plus aucune connexion BDD ni requête SQL ici.
   ============================================================ */

$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';

// CSS et titre pour le header
$titrePage = 'Articles - FABLAB';
$pageCss = ['article.css'];

include(__DIR__ . '/../parties/header.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$roleUtilisateur = $_SESSION['utilisateur_role'] ?? 'Visiteur';

// $articles est fourni par le contrôleur — pas besoin de requête ici
if (!isset($articles)) {
    $articles = [];
}
?>

<section class="hero-section">
  <div class="hero-content">
    <h1 class="hero-title">Nos Articles</h1>
    <p class="hero-subtitle">Découvrez nos articles récents sur la technologie, l'innovation et le bien-être.</p>
  </div>
</section>

<section class="section-recherche">
  <div class="search-filter">
    <input type="text" id="searchInput" placeholder="Rechercher un article...">
    <select id="categoryFilter">
      <option value="all">Toutes les catégories</option>
    </select>
  </div>
</section>

<?php if (in_array($roleUtilisateur, ['Éditeur', 'Editeur', 'Admin'])): ?>
  <div class="article-action">
    <button onclick="ouvrirModaleArticle()" class="btn btn-primary">
      <i class="fas fa-plus-circle"></i> Créer un article
    </button>
  </div>
<?php endif; ?>

<main class="featured-section">
  <h2 class="section-title">Articles Récents</h2>
  <div class="projects-grid">
    <?php if (empty($articles)): ?>
      <div class="no-articles">
        <div class="no-articles-icon">
          <i class="fas fa-newspaper"></i>
        </div>
        <h3>Aucun article disponible</h3>
        <p>Il n'y a pas encore d'articles publiés. Revenez bientôt !</p>
      </div>
    <?php else: ?>
      <?php foreach ($articles as $article): ?>
        <div class="project-card">
          <div class="project-image">
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
                   onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
              <div class="image-fallback" style="display:none; width:100%; height:100%; background:linear-gradient(135deg,#00afa7,#008f88); align-items:center; justify-content:center;">
                <i class="fas fa-newspaper" style="font-size:3rem; color:white; opacity:0.7;"></i>
              </div>
            <?php else: ?>
              <div style="width:100%; height:100%; background:linear-gradient(135deg,#00afa7,#008f88); display:flex; align-items:center; justify-content:center;">
                <i class="fas fa-newspaper" style="font-size:3rem; color:white; opacity:0.7;"></i>
              </div>
            <?php endif; ?>
          </div>

          <div class="project-content">
            <h3 class="project-title"><?= htmlspecialchars($article['titre']); ?></h3>
            <p class="project-description">
              <?php
              $extrait = substr(strip_tags($article['contenu']), 0, 120);
              echo htmlspecialchars($extrait) . (strlen($article['contenu']) > 120 ? '...' : '');
              ?>
            </p>
            <div class="project-tags">
              <span class="tag">✍️ <?= htmlspecialchars($article['auteur']); ?></span>
              <span class="tag">📅 <?= date('d/m/Y', strtotime($article['created_at'])); ?></span>
            </div>
            <div class="action-buttons">
              <a href="?page=article-detail&id=<?= $article['id']; ?>" class="btn btn-primary">
                <i class="fas fa-book-open"></i> Lire l'article
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<!-- MODALE CRÉATION ARTICLE -->
<div id="modaleArticleCreation" class="modal-creation">
  <div class="modal-creation-content">
    <div class="modal-creation-header">
      <div>
        <h2><i class="fas fa-pen-nib"></i> Créer un nouvel article</h2>
        <p>Remplissez le formulaire ci-dessous pour publier un nouveau article</p>
      </div>
      <button class="close-modal-creation" onclick="fermerModaleArticle()">&times;</button>
    </div>

    <div class="modal-creation-body">
      <div id="alertArticle"></div>

      <form id="formArticle">
        <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>

        <div class="form-group">
          <label>Titre *</label>
          <input type="text" name="titre" id="article_titre" required minlength="5" maxlength="200" placeholder="Titre de l'article...">
          <small id="titre_error" style="color: #ff6b6b; display: none;"></small>
        </div>

        <div class="form-group">
          <label>Auteur *</label>
          <input type="text" name="auteur" id="article_auteur" value="<?= htmlspecialchars($_SESSION['utilisateur_nom'] ?? '') ?>" required minlength="2" maxlength="100" placeholder="Votre nom...">
          <small id="auteur_error" style="color: #ff6b6b; display: none;"></small>
        </div>

        <div class="form-group">
          <label>Contenu *</label>
          <textarea name="contenu" id="article_contenu" rows="10" required minlength="10" maxlength="10000" placeholder="Contenu de l'article..."></textarea>
          <small id="contenu_error" style="color: #ff6b6b; display: none;"></small>
        </div>

        <div class="form-group">
          <label>URL de l'image (optionnel)</label>
          <input type="text" name="image_url" id="article_image_url" placeholder="https://exemple.com/image.jpg" oninput="previewArticleImage(this.value)">

          <div class="info-box">
            <strong>💡 Astuce :</strong> Vous pouvez coller n'importe quelle URL d'image depuis Google, Discord, etc.
          </div>

          <div id="articleImagePreviewContainer" class="image-preview-container">
            <p><strong>Aperçu :</strong></p>
            <img id="articleImagePreview" alt="Aperçu de l'image">
            <div id="articleLoadingSpinner" class="loading-spinner">
              <i class="fas fa-spinner fa-spin"></i> Chargement...
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" class="btn btn-secondary" onclick="fermerModaleArticle()">
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

<script>
// Recherche et filtrage
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById("searchInput");
  const categoryFilter = document.getElementById("categoryFilter");
  const cards = document.querySelectorAll(".project-card");

  function filtrer() {
    const searchText = searchInput.value.toLowerCase();
    const selectedCategory = categoryFilter.value.toLowerCase();

    cards.forEach(card => {
      const title = card.querySelector(".project-title").textContent.toLowerCase();
      const description = card.querySelector(".project-description").textContent.toLowerCase();
      const category = card.dataset.categorie?.toLowerCase() || "autre";

      const matchTexte = title.includes(searchText) || description.includes(searchText);
      const matchCategorie = selectedCategory === "all" || category === selectedCategory;

      card.style.display = (matchTexte && matchCategorie) ? "block" : "none";
    });
  }

  searchInput.addEventListener("input", filtrer);
  categoryFilter.addEventListener("change", filtrer);
});

// Gestion modale article
window.ouvrirModaleArticle = function() {
  const modal = document.getElementById('modaleArticleCreation');
  const form = document.getElementById('formArticle');
  const alert = document.getElementById('alertArticle');
  const preview = document.getElementById('articleImagePreviewContainer');

  if (!modal) {
    console.error('Modale article non trouvée');
    return;
  }

  modal.classList.add('active');
  if (form) form.reset();
  if (alert) alert.innerHTML = '';
  if (preview) preview.classList.remove('active');
};

window.fermerModaleArticle = function() {
  const modal = document.getElementById('modaleArticleCreation');
  if (modal) {
    modal.classList.remove('active');
  }
};

// Fermer avec Escape
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const modal = document.getElementById('modaleArticleCreation');
    if (modal && modal.classList.contains('active')) {
      window.fermerModaleArticle();
    }
  }
});

// Fermer en cliquant à l'extérieur
document.addEventListener('click', function(event) {
  const modal = document.getElementById('modaleArticleCreation');
  if (event.target === modal) {
    window.fermerModaleArticle();
  }
});

// Preview image
let articleImageTimeout = null;
function previewArticleImage(url) {
  const preview = document.getElementById('articleImagePreview');
  const container = document.getElementById('articleImagePreviewContainer');
  const spinner = document.getElementById('articleLoadingSpinner');

  if (articleImageTimeout) clearTimeout(articleImageTimeout);

  container.classList.remove('active');
  spinner.classList.remove('active');

  if (url && url.trim() !== '') {
    container.classList.add('active');
    spinner.classList.add('active');
    preview.style.opacity = '0.3';

    articleImageTimeout = setTimeout(() => {
      spinner.classList.remove('active');
      preview.style.opacity = '1';
    }, 5000);

    preview.src = url;
    preview.onload = () => {
      clearTimeout(articleImageTimeout);
      spinner.classList.remove('active');
      preview.style.opacity = '1';
    };
    preview.onerror = () => {
      clearTimeout(articleImageTimeout);
      spinner.classList.remove('active');
      container.classList.remove('active');
    };
  }
}

// Validation côté client
function validerFormArticle() {
  const titre = document.getElementById('article_titre');
  const auteur = document.getElementById('article_auteur');
  const contenu = document.getElementById('article_contenu');
  let isValid = true;

  // Reset erreurs
  document.querySelectorAll('small[id$="_error"]').forEach(el => el.style.display = 'none');

  // Validation titre
  if (titre.value.trim().length < 5 || titre.value.trim().length > 200) {
    document.getElementById('titre_error').textContent = 'Le titre doit contenir entre 5 et 200 caractères';
    document.getElementById('titre_error').style.display = 'block';
    isValid = false;
  }

  // Validation auteur
  if (auteur.value.trim().length < 2 || auteur.value.trim().length > 100) {
    document.getElementById('auteur_error').textContent = "L'auteur doit contenir entre 2 et 100 caractères";
    document.getElementById('auteur_error').style.display = 'block';
    isValid = false;
  }

  // Validation contenu
  if (contenu.value.trim().length < 10 || contenu.value.trim().length > 10000) {
    document.getElementById('contenu_error').textContent = 'Le contenu doit contenir entre 10 et 10 000 caractères';
    document.getElementById('contenu_error').style.display = 'block';
    isValid = false;
  }

  return isValid;
}

// Soumission formulaire AJAX avec sécurité
let articleSubmitting = false;
const formArticle = document.getElementById('formArticle');
if (formArticle) {
  formArticle.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Empêcher la double soumission - vérifier IMMÉDIATEMENT
    if (articleSubmitting) {
      console.warn('Soumission déjà en cours, ignorée');
      return;
    }

    // Désactiver IMMÉDIATEMENT le bouton avant toute validation
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const alertDiv = document.getElementById('alertArticle');

    articleSubmitting = true;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publication...';

    // Validation côté client
    if (!validerFormArticle()) {
      // Réactiver le bouton si la validation échoue
      articleSubmitting = false;
      submitBtn.disabled = false;
      submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Publier';
      return;
    }

    const formData = new FormData(e.target);

  try {
    // Récupérer le token CSRF du meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch('?page=article_enregistrer', {
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
        fermerModaleArticle();
        location.reload();
      }, 1500);
    } else {
      alertDiv.innerHTML = `<div class="alert-modal alert-danger">${result.message || 'Une erreur est survenue'}</div>`;
    }
  } catch (error) {
    console.error('Erreur:', error);
    alertDiv.innerHTML = '<div class="alert-modal alert-danger">Erreur lors de la publication de l\'article. Veuillez réessayer.</div>';
  } finally {
    articleSubmitting = false;
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Publier';
  }
  });
} else {
  console.error('Formulaire article non trouvé');
}

console.log('Script modale article chargé');
console.log('Modale article:', document.getElementById('modaleArticleCreation'));
console.log('Formulaire article:', document.getElementById('formArticle'));
</script>

<?php include(__DIR__ . '/../parties/footer.php'); ?>