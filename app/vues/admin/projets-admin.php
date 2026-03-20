<?php
$adminTitle = 'Gestion des Projets - Admin FABLAB';
$adminCss = ['admin-projets.css'];

require __DIR__ . '/../parties/admin-layout-start.php';
?>

        <section class="dashboard" data-admin-projets="1">
            <h1>Gestion des Projets</h1>

            
    <h2 class="sr-only">Sections principales</h2>
<?php if (!empty($_SESSION['message'])): ?>
              <div
                id="adminFlashData"
                hidden
                data-flash-type="<?= htmlspecialchars((string)($_SESSION['message_type'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>"
                data-flash-message="<?= htmlspecialchars((string)$_SESSION['message'], ENT_QUOTES, 'UTF-8') ?>"></div>
              <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <?php if ((int)($total_projects ?? 0) === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>Aucun projet pour le moment. Créez-en un pour commencer !</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">
                            <i class="fas fa-project-diagram"></i> Projets
                        </h3>
                        <?php
                            $filtrageActif = trim((string)($recherche ?? '')) !== '';
                            $compteurEntete = $filtrageActif ? (int)($totalFiltres ?? 0) : (int)($total_projects ?? 0);
                            $libelleEntete = $filtrageActif ? 'résultat(s)' : 'projet(s)';
                        ?>
                        <div class="table-actions">
                            <button type="button" class="btn btn-primary" data-projets-open-create="1">
                                <i class="fas fa-plus"></i> Nouveau Projet
                            </button>
                            <span class="stats-badge">
                                <i class="fas fa-folder"></i> <?= $compteurEntete ?> <?= $libelleEntete ?>
                            </span>
                        </div>
                    </div>
                    <div class="table-search">
                        <div class="search-bar">
                            <input type="text" id="champRecherche" value="<?= htmlspecialchars((string)($recherche ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Rechercher un projet...">
                        </div>
                    </div>

                    <div class="users-table">
                        <?php if ((int)($totalFiltres ?? 0) === 0): ?>
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h2>Aucun résultat</h2>
                                <p>Aucun projet ne correspond a la recherche actuelle.</p>
                            </div>
                        <?php else: ?>
                        <table id="tableauProjets">
                        <thead>
                            <tr>
                                <th class="col-small">Image</th>
                                <th class="col-large">Titre</th>
                                <th class="col-medium">Description</th>
                                <th class="col-medium">Auteur</th>
                                <th class="col-medium">Catégorie</th>
                                <th class="col-medium">Technologies</th>
                                <th class="col-date">Date</th>
                                <th class="col-actions text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($projects as $project): ?>
                            <?php
                           
                            $imageSrc = '';
                            if (!empty($project['image_url'])) {
                                if (str_starts_with($project['image_url'], 'http://') || str_starts_with($project['image_url'], 'https://')) {
                                    $imageSrc = $project['image_url'];
                                } else {
                                    $imageBrute = ltrim((string)$project['image_url'], '/');
                                    $imageSrc = str_starts_with($imageBrute, 'images/')
                                        ? $imageBrute
                                        : 'images/projets/' . $imageBrute;
                                }
                            }
                            ?>
                            <tr data-projet-id="<?= $project['id'] ?>">
                                <td class="table-image-cell" data-label="Image" data-col="image">
                                    <?php if (!empty($imageSrc)): ?>
                                        <div class="image-container">
                                            <img src="<?= htmlspecialchars($imageSrc) ?>"
                                                 alt="<?= htmlspecialchars($project['title']) ?>"
                                                 class="project-thumb"
                                                 data-proxy-src-on-error="<?= htmlspecialchars($imageSrc, ENT_QUOTES, 'UTF-8') ?>">
                                            <div class="no-image-fallback admin-hidden">
                                                <i class="fas fa-link"></i>
                                                <span>URL enregistrée</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="modern-placeholder no-image">
                                            <i class="fas fa-project-diagram"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Titre" data-col="titre"><strong class="admin-text-primary"><?= htmlspecialchars($project['title']) ?></strong></td>
                                <td class="admin-text-muted" data-label="Description" data-col="description">
                                    <span class="project-description-text"><?= htmlspecialchars(substr($project['description'], 0, 60)) ?>...</span>
                                </td>
                                <td data-label="Auteur" data-col="utilisateur"><?= htmlspecialchars($project['auteur_nom'] ?? 'N/A') ?></td>
                                <td data-label="Catégorie" data-col="categorie">
                                    <?php if (!empty($project['categorie'])): ?>
                                        <span class="badge-categorie"><?= htmlspecialchars($project['categorie']) ?></span>
                                    <?php else: ?>
                                        <span class="admin-text-muted-sm">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="admin-text-muted-compact" data-label="Technologies" data-col="technologies">
                                    <span class="project-tech-text"><?= htmlspecialchars(substr((string)($project['technologies'] ?? 'N/A'), 0, 40)) ?></span>
                                </td>
                                <td data-label="Date" data-col="date"><?= date('d/m/Y', strtotime($project['created_at'])) ?></td>
                                <td class="text-center" data-label="Actions" data-col="actions">
                                    <button type="button" class="btn btn-warning btn-sm" data-projet-edit-id="<?= (int) $project['id'] ?>" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" data-projet-delete-id="<?= (int) $project['id'] ?>" data-projet-delete-title="<?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?>" title="Supprimer">
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
    <div id="modaleProjet" class="modal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Gestion projet">
  <div class="modal-content modal-article">
    <div class="modal-header">
        <h2 id="titreModale">Nouveau Projet</h2>
        <button type="button" class="close-modal" data-projet-close-modal="1" aria-label="Fermer la modale">&times;</button>
    </div>

    <form id="formulaireProjet" method="POST" action="?page=admin-projets" enctype="multipart/form-data">
        <input type="hidden" name="action" id="actionFormulaire" value="create">
        <input type="hidden" name="project_id" id="idProjet">
        <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>

        <div class="form-group">
            <label for="title">Titre du projet *</label>
            <input type="text" id="title" name="title" required>
        </div>

        <div class="form-group">
            <label for="description">Description courte *</label>
            <textarea id="description" name="description" rows="3" required placeholder="Resume du projet en quelques lignes..."></textarea>
        </div>

        <div class="form-group">
            <label for="projetAdminAuteurAffichage">Auteur</label>
            <input type="text" id="projetAdminAuteurAffichage" value="<?= htmlspecialchars($_SESSION['utilisateur_nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" disabled>
        </div>

        <div class="form-group">
            <label for="categorieProjet">Catégorie</label>
            <select id="categorieProjet" name="categorie" class="form-select">
                <option value="">- Sélectionner une catégorie -</option>
                <option value="Robotique">Robotique</option>
                <option value="Drone / FPV">Drone / FPV</option>
                <option value="Impression 3D">Impression 3D</option>
                <option value="Electronique">Electronique</option>
                <option value="Programmation">Programmation</option>
                <option value="Mecanique">Mecanique</option>
                <option value="Autre">Autre</option>
            </select>
        </div>

        <div class="form-group">
            <label for="description_detailed">Description detaillee</label>
            <textarea id="description_detailed" name="description_detailed" rows="6" placeholder="Description complete du projet..."></textarea>
        </div>

        <div class="form-group">
            <label for="technologies">Technologies utilisees</label>
            <input type="text" id="technologies" name="technologies" placeholder="Ex: Python, Arduino, TensorFlow...">
        </div>

        <div class="form-group">
            <label for="features">Fonctionnalites principales</label>
            <textarea id="features" name="features" rows="3" placeholder="Separez par des virgules, ex: Navigation autonome, Detection d'obstacles, Controle Bluetooth"></textarea>
        </div>

        <div class="form-group">
            <label for="challenges">Defis rencontres</label>
            <textarea id="challenges" name="challenges" rows="3" placeholder="Defis techniques et solutions..."></textarea>
        </div>

       
        <div class="form-group">
            <label for="image_url">URL d'image (externe)</label>
            <input type="text" id="image_url" name="image_url" placeholder="https://exemple.com/image.jpg">
            
            <div class="info-box">
                <strong><i class="fas fa-check-circle"></i> Toutes les URLs d'images sont acceptées !</strong><br>
                - Vous pouvez coller n'importe quelle URL (Google, Discord, Wikipedia, etc.)<br>
                - L'image s'affichera sur le site public
            </div>

            <div id="imagePreviewContainer" class="image-preview-container">
                <p class="preview-label"><i class="fas fa-eye"></i> Aperçu de l'image :</p>
                <div class="preview-wrapper">
                    <img id="imagePreview" alt="Aperçu">
                    <div id="imageLoadingSpinner" class="image-loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </div>
            </div>

            <div id="imagePreviewError" class="image-preview-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Impossible de charger l'apercu</strong><br>
                    Verifiez que l'URL est correcte.
                </div>
            </div>
        </div>

       
        <div class="form-group">
            <label for="imageFile">OU uploader une image locale (PNG, JPG...)</label>
            <input type="file" id="imageFile" name="image" accept="image/*" data-projet-image-file-input="1">
            <div class="info-box">
                <strong><i class="fas fa-upload"></i> Upload local</strong><br>
                - Accepte PNG, JPG, JPEG, GIF, WebP<br>
                - Si vous uploadez un fichier, il a priorite sur l'URL ci-dessus<br>
                - Le fichier sera sauvegarde dans <code>public/images/projets/</code>
            </div>

            <div id="localPreviewContainer" class="image-preview-container">
                <p class="preview-label"><i class="fas fa-eye"></i> Aperçu du fichier :</p>
                <div class="preview-wrapper">
                    <img id="localPreview" alt="Aperçu local">
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-danger" data-projet-close-modal="1">
                <i class="fas fa-times"></i> Annuler
             </button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Enregistrer
            </button>
        </div>
    </form>
  </div>
</div>


<script id="donneesProjets" type="application/json">
<?= json_encode($projects ?? []) ?>
</script>


<?php
$adminScripts = ['js/securite-helper.js', 'js/ajax-helper.js', 'js/toast-notification.js', 'js/recherche-helper.js', 'js/csrf_manager.js', 'js/image-preview-helper.js', 'js/gestion-projets.js'];
require __DIR__ . '/../parties/admin-layout-end.php';
?>

