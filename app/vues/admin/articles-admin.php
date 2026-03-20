<?php
$adminTitle = 'Gestion des Articles - Admin FABLAB';
$adminCss = ['admin-articles.css'];

require __DIR__ . '/../parties/admin-layout-start.php';
?>

        <section class="dashboard" data-admin-articles="1">
            <h1>Gestion des Articles</h1>

            
    <h2 class="sr-only">Sections principales</h2>
<?php if ((int)($total_articles ?? 0) === 0): ?>
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
                        <?php
                            $filtrageActif = trim((string)($recherche ?? '')) !== '';
                            $compteurEntete = $filtrageActif ? (int)($totalFiltres ?? 0) : (int)($total_articles ?? 0);
                            $libelleEntete = $filtrageActif ? 'résultat(s)' : 'article(s)';
                        ?>
                        <div class="table-actions">
                            <button type="button" class="btn btn-primary" data-articles-open-create="1">
                                <i class="fas fa-plus"></i> Nouvel Article
                            </button>
                            <span class="stats-badge">
                                <i class="fas fa-file-alt"></i> <?= $compteurEntete ?> <?= $libelleEntete ?>
                            </span>
                        </div>
                    </div>
                    <div class="table-search">
                        <div class="search-bar">
                            <input type="text" id="champRecherche" value="<?= htmlspecialchars((string)($recherche ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Rechercher un article...">
                        </div>
                    </div>

                    <div class="users-table">
                        <?php if ((int)($totalFiltres ?? 0) === 0): ?>
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h2>Aucun résultat</h2>
                                <p>Aucun article ne correspond a la recherche actuelle.</p>
                            </div>
                        <?php else: ?>
                        <table id="tableauArticles">
                        <thead>
                            <tr>
                                <th class="col-small">Image</th>
                                <th class="col-large">Titre</th>
                                <th class="col-medium">Extrait</th>
                                <th class="col-medium">Auteur</th>
                                <th class="col-medium">Catégorie</th>
                                <th class="col-date">Date</th>
                                <th class="col-actions text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach (($articles ?? []) as $article): ?>
                            <?php
                            $imageSrc = '';
                            if (!empty($article['image_url'])) {
                                if (str_starts_with($article['image_url'], 'http://') || str_starts_with($article['image_url'], 'https://')) {
                                    $imageSrc = $article['image_url'];
                                } else {
                                    $imageSrc = str_starts_with($article['image_url'], 'images/')
                                        ? $article['image_url']
                                        : 'images/articles/' . ltrim((string)$article['image_url'], '/');
                                }
                            }
                            ?>
                            <tr data-article-id="<?= $article['id'] ?>">
                                <td class="table-image-cell" data-label="Image" data-col="image">
                                    <?php if (!empty($imageSrc)): ?>
                                        <div class="image-container">
                                            <img src="<?= htmlspecialchars($imageSrc) ?>"
                                                 alt="<?= htmlspecialchars($article['titre']) ?>"
                                                 class="article-thumb"
                                                 data-proxy-src-on-error="<?= htmlspecialchars($imageSrc, ENT_QUOTES, 'UTF-8') ?>">
                                            <div class="no-image-fallback admin-hidden">
                                                <i class="fas fa-link"></i>
                                                <span>URL enregistrée</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="modern-placeholder no-image">
                                            <i class="fas fa-newspaper"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Titre" data-col="titre"><strong class="admin-text-primary"><?= htmlspecialchars($article['titre']) ?></strong></td>
                                <td class="admin-text-muted" data-label="Extrait" data-col="description"><?= htmlspecialchars(substr($article['contenu'], 0, 80)) ?>...</td>
                                <td data-label="Auteur" data-col="utilisateur"><?= htmlspecialchars($article['auteur_nom'] ?? '') ?></td>
                                <td data-label="Catégorie" data-col="categorie">
                                  <?php if (!empty($article['categorie'])): ?>
                                    <span class="badge-categorie"><?= htmlspecialchars($article['categorie']) ?></span>
                                  <?php else: ?>
                                    <span class="admin-text-muted-sm">-</span>
                                  <?php endif; ?>
                                </td>
                                <td data-label="Date" data-col="date"><?= date('d/m/Y', strtotime($article['created_at'])) ?></td>
                                <td class="text-center" data-label="Actions" data-col="actions">
                                    <button type="button" class="btn btn-warning btn-sm" data-article-edit-id="<?= (int) $article['id'] ?>" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" data-article-delete-id="<?= (int) $article['id'] ?>" data-article-delete-title="<?= htmlspecialchars($article['titre'], ENT_QUOTES, 'UTF-8') ?>" title="Supprimer">
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
        </section>
    <div id="modaleArticle" class="modal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Gestion article">
  <div class="modal-content modal-article">
    <div class="modal-header">
        <h2 id="titreModale">Nouvel Article</h2>
        <button type="button" class="close-modal" data-article-close-modal="1" aria-label="Fermer la modale">&times;</button>
    </div>

    <form id="formulaireArticle" method="POST" action="?page=admin-articles" enctype="multipart/form-data">
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
            <label for="articleAdminAuteurAffichage">Auteur</label>
            <input type="text" id="articleAdminAuteurAffichage" value="<?= htmlspecialchars($_SESSION['utilisateur_nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled>
        </div>

        <div class="form-group">
            <label for="categorieAdmin">Catégorie</label>
            <select id="categorieAdmin" name="categorie">
                <option value="">- Sans catégorie -</option>
                <option value="Robotique">Robotique</option>
                <option value="Electronique">Electronique</option>
                <option value="Programmation">Programmation</option>
                <option value="Impression 3D">Impression 3D</option>
                <option value="Mecanique">Mecanique</option>
                <option value="Conception">Conception</option>
                <option value="Intelligence Artificielle">Intelligence Artificielle</option>
                <option value="Autre">Autre</option>
            </select>
        </div>

        <div class="form-group">
            <label for="image_url">URL de l'image</label>
            <input type="text" id="image_url" name="image_url" placeholder="https://exemple.com/image.jpg ou coller l'URL depuis votre recherche">

            <div class="info-box">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Toutes les URLs d'images sont acceptées !</strong><br>
                    - Vous pouvez coller n'importe quelle URL (Google, Brave, etc.)<br>
                    - L'apercu s'affiche grace au systeme de proxy<br>
                    - L'image s'affichera egalement sur le site public
                </div>
            </div>

            <div id="conteneurApercuImage" class="image-preview-container">
                <p class="preview-label"><i class="fas fa-eye"></i> Aperçu de l'image : 
                </p>
                <div class="preview-wrapper">
                    <img id="apercuImage" alt="Aperçu de l'image selectionnee">
                    <div id="spinnerChargementImage" class="image-loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </div>
            </div>

            <div id="erreurApercuImage" class="image-preview-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Impossible de charger l'apercu</strong><br>
                    Verifiez que l'URL est correcte. L'image sera quand meme enregistrée et pourra s'afficher sur le site public.
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="imageFileArticle">OU uploader une image locale (PNG, JPG...)</label>
            <input type="file" id="imageFileArticle" name="image" accept="image/*" data-article-image-file-input="1">

            <div class="info-box">
                <i class="fas fa-upload"></i>
                <div>
                    <strong>Upload local</strong><br>
                    - Formats acceptes: PNG, JPG, JPEG, GIF, WebP<br>
                    - Si un fichier est selectionne, il est prioritaire sur l'URL<br>
                    - Le fichier est sauvegarde dans <code>public/images/articles/</code>
                </div>
            </div>

            <div id="localPreviewArticleContainer" class="image-preview-container">
                <p class="preview-label"><i class="fas fa-eye"></i> Aperçu du fichier :</p>
                <div class="preview-wrapper">
                    <img id="localPreviewArticle" alt="Aperçu local de l'image">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-danger" data-article-close-modal="1">
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


<?php
$adminScripts = [
    'js/securite-helper.js',
    'js/ajax-helper.js',
    'js/toast-notification.js',
    'js/recherche-helper.js',
    'js/csrf_manager.js',
    'js/image-preview-helper.js',
    'js/gestion-articles.js',
];
require __DIR__ . '/../parties/admin-layout-end.php';
?>


