<?php
$adminTitle = 'Corbeille - Admin';
$adminCss = ['admin-corbeille.css', 'admin-articles.css'];

require __DIR__ . '/../parties/admin-layout-start.php';
?>

        <section class="dashboard" data-admin-corbeille="1">
            <h1>Corbeille</h1>

            <?php if (!empty($_SESSION['erreur'])): ?>
                <div
                    id="adminFlashData"
                    hidden
                    data-flash-type="danger"
                    data-flash-message="<?= htmlspecialchars((string)$_SESSION['erreur'], ENT_QUOTES, 'UTF-8') ?>"></div>
                <?php unset($_SESSION['erreur']); ?>
            <?php elseif (!empty($_SESSION['message'])): ?>
                <div
                    id="adminFlashData"
                    hidden
                    data-flash-type="<?= htmlspecialchars((string)($_SESSION['message_type'] ?? 'success'), ENT_QUOTES, 'UTF-8') ?>"
                    data-flash-message="<?= htmlspecialchars((string)$_SESSION['message'], ENT_QUOTES, 'UTF-8') ?>"></div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <?php
                $filtreActif = (string)($filtreCorbeilleActif ?? 'tous');
                $rechercheActuelle = (string)($rechercheCorbeille ?? '');
                $totalFiltreActuel = (int)($totalFiltresCorbeille ?? count($tousLesElements));
                $filtrageCorbeilleActif = $filtreActif !== 'tous' || trim($rechercheActuelle) !== '';
                $compteursGlobaux = is_array($compteursCorbeille ?? null) ? $compteursCorbeille : [];
                $compteursAffiches = is_array($compteursCorbeilleAffiches ?? null) ? $compteursCorbeilleAffiches : $compteursGlobaux;
                $totalGlobalCorbeille = (int)($compteursGlobaux['tous'] ?? 0);
            ?>

            <?php if ((int)($totalCorbeille ?? 0) === 0): ?>
                <div class="empty-state">
                    <i class="fas fa-trash-alt"></i>
                    <h2>La corbeille est vide</h2>
                    <p>Aucun élément supprimé</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="table-title">
                            <i class="fas fa-trash-alt"></i> Éléments supprimés
                        </h3>
                        <div class="table-actions">
                            <button class="btn btn-warning" id="restaurerTousBtn">
                                <i class="fas fa-undo"></i> Restaurer tout
                            </button>
                            <button class="btn btn-danger" id="viderCorbeilleBtn">
                                <i class="fas fa-trash"></i> Vider la corbeille
                            </button>
                            <span class="stats-badge" data-corbeille-total="<?= $totalGlobalCorbeille ?>">
                                <i class="fas fa-trash-alt"></i> <?= $totalGlobalCorbeille ?> élément(s)
                            </span>
                            <?php if ($filtrageCorbeilleActif): ?>
                                <span class="stats-badge">
                                    <i class="fas fa-filter"></i> <?= $totalFiltreActuel ?> résultat(s)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="table-search">
                        <div class="search-bar">
                            <input type="text" id="champRecherche" value="<?= htmlspecialchars($rechercheActuelle, ENT_QUOTES, 'UTF-8') ?>" placeholder="Rechercher dans la corbeille...">
                        </div>
                    </div>

                    <div class="filters" data-server-filtering="1">
                        <button class="filter-btn <?= $filtreActif === 'tous' ? 'active' : '' ?>" data-server-filter="1" data-filter="tous">
                            <i class="fas fa-list"></i> Tous (<?= (int)($compteursAffiches['tous'] ?? 0) ?>)
                        </button>
                        <button class="filter-btn <?= $filtreActif === 'article' ? 'active' : '' ?>" data-server-filter="1" data-filter="article">
                            <i class="fas fa-newspaper"></i> Articles (<?= (int)($compteursAffiches['article'] ?? 0) ?>)
                        </button>
                        <button class="filter-btn <?= $filtreActif === 'actualite' ? 'active' : '' ?>" data-server-filter="1" data-filter="actualite">
                            <i class="fas fa-rss"></i> Actualités (<?= (int)($compteursAffiches['actualite'] ?? 0) ?>)
                        </button>
                        <button class="filter-btn <?= $filtreActif === 'projet' ? 'active' : '' ?>" data-server-filter="1" data-filter="projet">
                            <i class="fas fa-project-diagram"></i> Projets (<?= (int)($compteursAffiches['projet'] ?? 0) ?>)
                        </button>
                        <button class="filter-btn <?= $filtreActif === 'video' ? 'active' : '' ?>" data-server-filter="1" data-filter="video">
                            <i class="fas fa-video"></i> Vidéos (<?= (int)($compteursAffiches['video'] ?? 0) ?>)
                        </button>
                        <button class="filter-btn <?= $filtreActif === 'utilisateur' ? 'active' : '' ?>" data-server-filter="1" data-filter="utilisateur">
                            <i class="fas fa-user"></i> Utilisateurs (<?= (int)($compteursAffiches['utilisateur'] ?? 0) ?>)
                        </button>
                        <button class="filter-btn <?= $filtreActif === 'message' ? 'active' : '' ?>" data-server-filter="1" data-filter="message">
                            <i class="fas fa-envelope"></i> Messages (<?= (int)($compteursAffiches['message'] ?? 0) ?>)
                        </button>
                    </div>

                    <?php if ($totalFiltreActuel === 0): ?>
                        <div class="empty-state empty-state-filtered">
                            <i class="fas fa-search"></i>
                            <h2>Aucun résultat</h2>
                            <p>Aucun élément ne correspond au filtre/recherche actuel.</p>
                        </div>
                    <?php endif; ?>

                    <?php if ($totalFiltreActuel > 0): ?>
                        <div class="users-table">
                            <table id="tableauCorbeille">
                                <thead>
                                    <tr>
                                        <th class="col-medium">Type</th>
                                        <th class="col-large">Nom / Titre</th>
                                        <th class="col-large">Description</th>
                                        <th class="col-date">Supprimé le</th>
                                        <th class="col-actions text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($tousLesElements as $element): ?>
                                <tr data-type="<?= htmlspecialchars($element['type']) ?>" data-id="<?= htmlspecialchars($element['id']) ?>">
                                    <td data-label="Type" data-col="type">
                                        <?php
                                        $badges = [
                                            'article' => ['color' => 'blue', 'icon' => 'fa-newspaper', 'label' => 'Article'],
                                            'actualite' => ['color' => 'blue', 'icon' => 'fa-rss', 'label' => 'Actualité'],
                                            'projet' => ['color' => 'green', 'icon' => 'fa-project-diagram', 'label' => 'Projet'],
                                            'video' => ['color' => 'red', 'icon' => 'fa-video', 'label' => 'Vidéo'],
                                            'utilisateur' => ['color' => 'purple', 'icon' => 'fa-user', 'label' => 'Utilisateur'],
                                            'message' => ['color' => 'orange', 'icon' => 'fa-envelope', 'label' => 'Message']
                                        ];
                                        $badge = $badges[$element['type']] ?? ['color' => 'gray', 'icon' => 'fa-question', 'label' => 'Inconnu'];
                                        ?>
                                        <span class="badge badge-<?= $badge['color'] ?>">
                                            <i class="fas <?= $badge['icon'] ?>"></i>
                                            <?= $badge['label'] ?>
                                        </span>
                                    </td>

                                    <td class="item-name" data-label="Nom / Titre" data-col="nom-titre">
                                        <?php
                                        $nom = '';
                                        switch ($element['type']) {
                                            case 'article':
                                            case 'actualite':
                                            case 'projet':
                                            case 'video':
                                                $nom = $element['titre'] ?? $element['title'] ?? 'Sans titre';
                                                break;
                                            case 'utilisateur':
                                                $nom = $element['nom'] ?? 'Sans nom';
                                                break;
                                            case 'message':
                                                $nom = $element['nom'] . ' - ' . $element['sujet'];
                                                break;
                                        }
                                        ?>
                                        <span class="cell-text cell-text-title"><?= htmlspecialchars($nom) ?></span>
                                    </td>

                                    <td class="item-description" data-label="Description" data-col="description">
                                        <?php
                                        $description = '';
                                        switch ($element['type']) {
                                            case 'article':
                                            case 'actualite':
                                            case 'projet':
                                            case 'video':
                                                $description = $element['description'] ?? '';
                                                break;
                                            case 'utilisateur':
                                                $description = $element['email'];
                                                break;
                                            case 'message':
                                                $description = $element['message'] ?? '';
                                                break;
                                        }
                                        
                                        $description = mb_strlen($description) > 80 ? mb_substr($description, 0, 80) . '...' : $description;
                                        ?>
                                        <span class="cell-text cell-text-description"><?= htmlspecialchars($description) ?></span>
                                    </td>

                                    <td data-label="Supprime le" data-col="supprime-le">
                                        <?php
                                        $date = new DateTime($element['deleted_at']);
                                        echo $date->format('d/m/Y à H:i');
                                        ?>
                                    </td>

                                    <td class="text-center" data-label="Actions" data-col="actions">
                                        <button class="btn btn-warning btn-sm btn-restore" title="Restaurer"
                                                data-id="<?= htmlspecialchars($element['id']) ?>"
                                                data-type="<?= htmlspecialchars($element['type']) ?>">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm btn-delete-permanent" title="Supprimer définitivement"
                                                data-id="<?= htmlspecialchars($element['id']) ?>"
                                                data-type="<?= htmlspecialchars($element['type']) ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($totalFiltreActuel > 0): ?>
                    <div class="admin-pagination">
                        <?php require __DIR__ . '/../parties/pagination.php'; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
<div id="restoreModal" class="corbeille-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Restauration d'élément">
    <div class="corbeille-modal-content">
        <div class="corbeille-modal-header">
            <h2><i class="fas fa-undo"></i> Restaurer l'élément</h2>
            <button type="button" class="corbeille-modal-close" data-corbeille-close="restoreModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="corbeille-modal-body">
            <div class="element-details">
                <span id="restoreElementType" class="element-type"></span>
                <div id="restoreElementTitle" class="element-title"></div>
                <div id="restoreElementDescription" class="element-description"></div>
                <div class="element-meta">
                    <div class="element-meta-item">
                        <i class="fas fa-calendar-times"></i>
                        <span id="restoreElementDate"></span>
                    </div>
                </div>
            </div>
            <p class="corbeille-modal-note">
                <i class="fas fa-info-circle corbeille-modal-note-icon"></i>
                Êtes-vous sûr de vouloir restaurer cet élément ? Il sera remis à sa place d'origine.
            </p>
        </div>
        <div class="corbeille-modal-actions">
            <button type="button" class="btn btn-secondary" data-corbeille-close="restoreModal">Annuler</button>
            <button class="btn btn-warning" id="confirmRestoreBtn">
                <i class="fas fa-undo"></i> Restaurer
            </button>
        </div>
    </div>
</div>

<div id="deleteModal" class="corbeille-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Suppression définitive">
    <div class="corbeille-modal-content">
        <div class="corbeille-modal-header">
            <h2><i class="fas fa-trash-alt"></i> Supprimer définitivement</h2>
            <button type="button" class="corbeille-modal-close" data-corbeille-close="deleteModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="corbeille-modal-body">
            <div class="element-details">
                <span id="deleteElementType" class="element-type"></span>
                <div id="deleteElementTitle" class="element-title"></div>
                <div id="deleteElementDescription" class="element-description"></div>
                <div class="element-meta">
                    <div class="element-meta-item">
                        <i class="fas fa-calendar-times"></i>
                        <span id="deleteElementDate"></span>
                    </div>
                </div>
            </div>
            <div class="corbeille-warning-box">
                <p class="corbeille-warning-title">
                    <i class="fas fa-exclamation-triangle"></i> ATTENTION : Cette action est IRRÉVERSIBLE !
                </p>
                <p class="corbeille-warning-text">
                    Une fois supprimé définitivement, cet élément ne pourra plus être récupéré.
                </p>
            </div>
        </div>
        <div class="corbeille-modal-actions">
            <button type="button" class="btn btn-secondary" data-corbeille-close="deleteModal">Annuler</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">
                <i class="fas fa-trash"></i> Supprimer définitivement
            </button>
        </div>
    </div>
</div>

<script id="donneesCorbeille" type="application/json">
<?= json_encode($tousLesElements ?? []) ?>
</script>


<?php
$adminScripts = ['js/securite-helper.js', 'js/ajax-helper.js', 'js/toast-notification.js', 'js/recherche-helper.js', 'js/csrf_manager.js', 'js/corbeille-admin.js'];
require __DIR__ . '/../parties/admin-layout-end.php';
?>
