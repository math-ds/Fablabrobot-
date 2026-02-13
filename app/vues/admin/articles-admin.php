<?php

$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Articles - Admin FABLAB</title>

    <!-- Meta CSRF pour AJAX -->
    <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirMetaJeton(); ?>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/admin-common.css">
    <link rel="stylesheet" href="css/admin-articles.css">
    <link rel="stylesheet" href="css/toast-notification.css">
</head>

<body>
<div class="admin-container">
    <aside class="sidebar">
        <div>
            <div class="sidebar-logo">
                <a href="?page=accueil">
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
                <input type="text" id="champRecherche" placeholder="Rechercher un article...">
            </div>
        </header>

        <section class="dashboard">
            <h1><i class="fas fa-newspaper"></i> Gestion des Articles</h1>

            <?php if (!empty($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type'] ?>">
                    <i class="fas fa-<?= $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($_SESSION['message']) ?>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <?php if (empty($articles)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Aucun article pour le moment. Créez-en un pour commencer !</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">
                            <i class="fas fa-newspaper"></i> Articles
                        </h3>
                        <div class="table-actions">
                            <button class="btn btn-primary" onclick="ouvrirModale('create')">
                                <i class="fas fa-plus"></i> Nouvel Article
                            </button>
                            <span class="stats-badge">
                                <i class="fas fa-file-alt"></i> <?= $total_articles ?? 0 ?> article(s)
                            </span>
                        </div>
                    </div>

                    <div class="users-table">
                        <table id="tableauArticles">
                        <thead>
                            <tr>
                                <th class="col-small">Image</th>
                                <th class="col-large">Titre</th>
                                <th class="col-medium">Extrait</th>
                                <th class="col-medium">Auteur</th>
                                <th class="col-date">Date</th>
                                <th class="col-actions text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($articles ?? [] as $article): ?>
                            <?php
                            $imageSrc = '';
                            if (!empty($article['image_url'])) {
                                if (str_starts_with($article['image_url'], 'http://') || str_starts_with($article['image_url'], 'https://')) {
                                    $imageSrc = $article['image_url'];
                                } else {
                                    $imageSrc = $article['image_url'];
                                }
                            }
                            ?>
                            <tr data-article-id="<?= $article['id'] ?>">
                                <td class="table-image-cell">
                                    <?php if (!empty($imageSrc)): ?>
                                        <div class="image-container" style="display: inline-block; position: relative;">
                                            <img src="<?= htmlspecialchars($imageSrc) ?>"
                                                 alt="<?= htmlspecialchars($article['titre']) ?>"
                                                 class="article-thumb"
                                                 onerror="essayerImageProxy(this, '<?= htmlspecialchars($imageSrc, ENT_QUOTES) ?>')">
                                            <div class="no-image-fallback" style="display: none; width: 160px; height: 160px; background: rgba(0, 175, 167, 0.1); align-items: center; justify-content: center; flex-direction: column; color: var(--text-muted); font-size: 0.7rem; border: 2px dashed var(--card-border); padding: 5px; text-align: center;">
                                                <i class="fas fa-link" style="font-size: 1.2rem; margin-bottom: 3px;"></i>
                                                <span>URL enregistrée</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="modern-placeholder no-image" style="display: inline-flex; width: 160px; height: 160px; background: rgba(0, 175, 167, 0.1); align-items: center; justify-content: center; color: var(--primary-color); font-size: 1.5rem; border: 2px dashed var(--card-border); transition: all 0.3s ease; border-radius: 10px;"
                                             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 15px rgba(0,175,167,0.2)';"
                                             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                            <i class="fas fa-newspaper"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong style="color: var(--primary-color);"><?= htmlspecialchars($article['titre']) ?></strong></td>
                                <td style="color: var(--text-muted);"><?= htmlspecialchars(substr($article['contenu'], 0, 80)) ?>...</td>
                                <td><?= htmlspecialchars($article['auteur']) ?></td>
                                <td><?= date('d/m/Y', strtotime($article['created_at'])) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-warning btn-sm" onclick='editerArticle(<?= $article["id"] ?>)' title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="supprimerArticle(<?= $article['id'] ?>, '<?= htmlspecialchars($article['titre'], ENT_QUOTES) ?>')" title="Supprimer">
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
        </section>
    </main>
</div>


<div id="modaleArticle" class="modal">
  <div class="modal-content modal-article">
    <div class="modal-header">
        <h2 id="titreModale">Nouvel Article</h2>
        <button class="close-modal" onclick="fermerModale()">&times;</button>
    </div>

    <form id="formulaireArticle" method="POST" action="?page=admin-articles">
        <input type="hidden" name="action" id="actionFormulaire" value="create">
        <input type="hidden" name="article_id" id="idArticle">
        <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>

        <div class="form-group">
            <label for="titre">Titre *</label>
            <input type="text" id="titre" name="titre" required>
        </div>

        <div class="form-group">
            <label for="contenu">Contenu *</label>
            <textarea id="contenu" name="contenu" rows="8" required></textarea>
        </div>

        <div class="form-group">
            <label for="auteur">Auteur *</label>
            <input type="text" id="auteur" name="auteur" required>
        </div>

        <div class="form-group">
            <label for="image_url">URL de l'image</label>
            <input type="text" id="image_url" name="image_url" placeholder="https://exemple.com/image.jpg ou coller l'URL depuis votre recherche">

            <div class="info-box">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Toutes les URLs d'images sont acceptées !</strong><br>
                    • Vous pouvez coller n'importe quelle URL (Google, Brave, etc.)<br>
                    • L'aperçu s'affiche grâce au système de proxy<br>
                    • L'image s'affichera également sur le site public
                </div>
            </div>

            <div id="conteneurApercuImage" class="image-preview-container">
                <p class="preview-label">
                    <i class="fas fa-eye"></i> Aperçu de l'image :
                </p>
                <div class="preview-wrapper">
                    <img id="apercuImage" alt="Aperçu de l'image sélectionnée">
                    <div id="spinnerChargementImage" class="image-loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </div>
            </div>

            <div id="erreurApercuImage" class="image-preview-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Impossible de charger l'aperçu</strong><br>
                    Vérifiez que l'URL est correcte. L'image sera quand même enregistrée et pourra s'afficher sur le site public.
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


<script id="donneesArticles" type="application/json">
<?= json_encode($articles ?? []) ?>
</script>

<!-- Scripts de sécurité et AJAX -->
<script src="js/securite-helper.js"></script>
<script src="js/ajax-helper.js"></script>
<script src="js/toast-notification.js"></script>
<script src="js/recherche-helper.js"></script>
<script src="js/csrf_manager.js"></script>
<script src="js/image-preview-helper.js"></script>
<script src="js/gestion-articles.js"></script>

<script>
  // Initialiser la recherche améliorée
  document.addEventListener('DOMContentLoaded', function() {
    RechercheHelper.initialiser('champRecherche', '#tableauArticles tbody tr');
  });
</script>

<!-- Convertir les messages flash en toast -->
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

<!-- JavaScript menu mobile -->
<script src="js/admin-mobile-menu.js"></script>

</body>
</html>