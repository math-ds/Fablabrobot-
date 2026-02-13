<?php

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Projets - Admin FABLAB</title>

    <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirMetaJeton(); ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/admin-common.css">
    <link rel="stylesheet" href="css/admin-projets.css">
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
                <input type="text" id="champRecherche" placeholder="Rechercher un projet...">
            </div>
        </header>

        <section class="dashboard">
            <h1><i class="fas fa-project-diagram"></i> Gestion des Projets</h1>

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

            <?php if (empty($projects)): ?>
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
                        <div class="table-actions">
                            <button class="btn btn-primary" onclick="ouvrirModale('create')">
                                <i class="fas fa-plus"></i> Nouveau Projet
                            </button>
                            <span class="stats-badge">
                                <i class="fas fa-folder"></i> <?= $total_projects ?> projet(s)
                            </span>
                        </div>
                    </div>

                    <div class="users-table">
                        <table id="tableauProjets">
                        <thead>
                            <tr>
                                <th class="col-small">Image</th>
                                <th class="col-large">Titre</th>
                                <th class="col-medium">Description</th>
                                <th class="col-medium">Auteur</th>
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
                                    $imageSrc = 'images/projets/' . $project['image_url'];
                                }
                            }
                            ?>
                            <tr data-projet-id="<?= $project['id'] ?>">
                                <td>
                                    <?php if (!empty($imageSrc)): ?>
                                        <div class="image-container" style="display: inline-block; position: relative;">
                                            <img src="<?= htmlspecialchars($imageSrc) ?>"
                                                 alt="<?= htmlspecialchars($project['title']) ?>"
                                                 class="project-thumb article-thumb"
                                                 onerror="essayerImageProxy(this, '<?= htmlspecialchars($imageSrc, ENT_QUOTES) ?>')">
                                            <div class="no-image-fallback" style="display: none; width: 160px; height: 160px; background: rgba(0, 175, 167, 0.1); align-items: center; justify-content: center; flex-direction: column; color: var(--text-muted); font-size: 0.7rem; border: 2px dashed var(--card-border); padding: 5px; text-align: center;">
                                                <i class="fas fa-link" style="font-size: 1.2rem; margin-bottom: 3px;"></i>
                                                <span>URL enregistrée</span>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-image" style="display: inline-flex; width: 140px; height: 90px; background: rgba(0, 175, 167, 0.1); align-items: center; justify-content: center; color: var(--primary-color); font-size: 1.2rem; border: 2px dashed var(--card-border); transition: all 0.3s ease; border-radius: 10px;"
                                             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 15px rgba(0,175,167,0.2)';"
                                             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                            <i class="fas fa-project-diagram"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong style="color: var(--primary-color);"><?= htmlspecialchars($project['title']) ?></strong></td>
                                <td style="color: var(--text-muted);"><?= htmlspecialchars(substr($project['description'], 0, 60)) ?>...</td>
                                <td><?= htmlspecialchars($project['auteur'] ?? 'N/A') ?></td>
                                <td style="color: var(--text-muted); font-size: 0.85rem;"><?= htmlspecialchars(substr($project['technologies'] ?? 'N/A', 0, 40)) ?></td>
                                <td><?= date('d/m/Y', strtotime($project['created_at'])) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-warning btn-sm" onclick='editerProjet(<?= $project["id"] ?>)' title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="supprimerProjet(<?= $project['id'] ?>, '<?= addslashes($project['title']) ?>')" title="Supprimer">
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


<div id="modaleProjet" class="modal">
  <div class="modal-content" style="max-width: 800px;">
    <div class="modal-header">
        <h2 id="titreModale">Nouveau Projet</h2>
        <button class="close-modal" onclick="fermerModale()">&times;</button>
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
            <textarea id="description" name="description" rows="3" required placeholder="Résumé du projet en quelques lignes..."></textarea>
        </div>

        <div class="form-group">
            <label for="auteur">Auteur *</label>
            <input type="text" id="auteur" name="auteur" placeholder="Nom de l'auteur du projet">
        </div>

        <div class="form-group">
            <label for="description_detailed">Description détaillée</label>
            <textarea id="description_detailed" name="description_detailed" rows="6" placeholder="Description complète du projet..."></textarea>
        </div>

        <div class="form-group">
            <label for="technologies">Technologies utilisées</label>
            <input type="text" id="technologies" name="technologies" placeholder="Ex: Python, Arduino, TensorFlow...">
        </div>

        <div class="form-group">
            <label for="features">Fonctionnalités principales</label>
            <textarea id="features" name="features" rows="3" placeholder="Séparez par des virgules, ex: Navigation autonome, Détection d'obstacles, Contrôle Bluetooth"></textarea>
        </div>

        <div class="form-group">
            <label for="challenges">Défis rencontrés</label>
            <textarea id="challenges" name="challenges" rows="3" placeholder="Défis techniques et solutions..."></textarea>
        </div>

       
        <div class="form-group">
            <label for="image_url">URL d'image (externe)</label>
            <input type="text" id="image_url" name="image_url" placeholder="https://exemple.com/image.jpg">
            
            <div style="background: rgba(0, 175, 167, 0.1); border: 1px solid rgba(0, 175, 167, 0.3); padding: 12px; border-radius: 8px; margin-top: 10px; font-size: 0.9rem; color: var(--primary-color);">
                <strong><i class="fas fa-check-circle"></i> Toutes les URLs d'images sont acceptées !</strong><br>
                • Vous pouvez coller n'importe quelle URL (Google, Discord, Wikipedia, etc.)<br>
                • L'image s'affichera sur le site public
            </div>

            <div id="imagePreviewContainer" style="margin-top: 15px; display: none;">
                <p style="color: var(--primary-color); font-weight: 600; margin-bottom: 10px;">
                    <i class="fas fa-eye"></i> Aperçu de l'image :
                </p>
                <div style="position: relative; display: inline-block;">
                    <img id="imagePreview" style="max-width: 100%; max-height: 200px; border-radius: 10px; border: 2px solid var(--primary-color);" alt="Aperçu">
                    <div id="imageLoadingSpinner" style="display: none; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.7); padding: 20px; border-radius: 10px;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                    </div>
                </div>
            </div>
            
            <div id="imagePreviewError" style="margin-top: 15px; display: none; background: rgba(255, 107, 107, 0.1); border: 1px solid rgba(255, 107, 107, 0.3); padding: 12px; border-radius: 8px; color: #ff6b6b;">
                <i class="fas fa-exclamation-triangle"></i> <strong>Impossible de charger l'aperçu</strong><br>
                Vérifiez que l'URL est correcte.
            </div>
        </div>

       
        <div class="form-group">
            <label>OU uploader une image locale (PNG, JPG...)</label>
            <input type="file" id="imageFile" name="image" accept="image/*" onchange="previewLocalImage(this)">
            <div style="background: rgba(0, 175, 167, 0.1); border: 1px solid rgba(0, 175, 167, 0.3); padding: 12px; border-radius: 8px; margin-top: 10px; font-size: 0.9rem; color: var(--primary-color);">
                <strong><i class="fas fa-upload"></i> Upload local</strong><br>
                • Accepte PNG, JPG, JPEG, GIF, WebP<br>
                • Si vous uploadez un fichier, il a priorité sur l'URL ci-dessus<br>
                • Le fichier sera sauvegardé dans <code>public/images/projets/</code>
            </div>

            <div id="localPreviewContainer" style="margin-top: 15px; display: none;">
                <p style="color: var(--primary-color); font-weight: 600; margin-bottom: 10px;">
                    <i class="fas fa-eye"></i> Aperçu du fichier :
                </p>
                <img id="localPreview" style="max-width: 100%; max-height: 200px; border-radius: 10px; border: 2px solid var(--primary-color);" alt="Aperçu local">
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


<script id="donneesProjets" type="application/json">
<?= json_encode($projects ?? []) ?>
</script>

<script src="js/securite-helper.js"></script>
<script src="js/ajax-helper.js"></script>
<script src="js/toast-notification.js"></script>
<script src="js/recherche-helper.js"></script>
<script src="js/csrf_manager.js"></script>
<script src="js/image-preview-helper.js"></script>
<script src="js/gestion-projets.js"></script>

<script>
  // Initialiser la recherche améliorée
  document.addEventListener('DOMContentLoaded', function() {
    RechercheHelper.initialiser('champRecherche', '#tableauProjets tbody tr');
  });
</script>

<!-- JavaScript menu mobile -->
<script src="js/admin-mobile-menu.js"></script>

</body>
</html>