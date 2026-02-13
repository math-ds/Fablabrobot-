<?php
// ===============================
// VUE : app/vues/admin/corbeille-admin.php
// ===============================
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corbeille - Admin</title>

    <!-- Meta CSRF pour AJAX -->
    <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirMetaJeton(); ?>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/admin-common.css">
    <link rel="stylesheet" href="css/admin-corbeille.css">
    <link rel="stylesheet" href="css/admin-articles.css">
    <link rel="stylesheet" href="css/toast-notification.css">
</head>
<body>

<div class="admin-container">
    <!-- Sidebar -->
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
            <a href="?page=logout" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </aside>

    <!-- Contenu principal -->
    <main class="main-content">
        <header class="admin-header">
            <div class="search-bar">
                <input type="text" id="champRecherche" placeholder="Rechercher dans la corbeille...">
            </div>
        </header>

        <section class="dashboard">
            <h1><i class="fas fa-trash-alt"></i> Corbeille</h1>

            <?php if (!empty($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type'] ?? 'success' ?>">
                    <i class="fas fa-<?= $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($_SESSION['message']) ?>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['erreur'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($_SESSION['erreur']) ?>
                </div>
                <?php unset($_SESSION['erreur']); ?>
            <?php endif; ?>

            <?php if (empty($tousLesElements)): ?>
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
                            <span class="stats-badge">
                                <i class="fas fa-trash-alt"></i> <?= count($tousLesElements) ?> élément(s)
                            </span>
                        </div>
                    </div>

                    <!-- Filtres -->
                    <div class="filters">
                        <button class="filter-btn active" data-filter="tous">
                            <i class="fas fa-list"></i> Tous (<?= count($tousLesElements) ?>)
                        </button>
                        <button class="filter-btn" data-filter="article">
                            <i class="fas fa-newspaper"></i> Articles (<?= count(array_filter($tousLesElements, fn($e) => $e['type'] === 'article')) ?>)
                        </button>
                        <button class="filter-btn" data-filter="projet">
                            <i class="fas fa-project-diagram"></i> Projets (<?= count(array_filter($tousLesElements, fn($e) => $e['type'] === 'projet')) ?>)
                        </button>
                        <button class="filter-btn" data-filter="video">
                            <i class="fas fa-video"></i> Vidéos (<?= count(array_filter($tousLesElements, fn($e) => $e['type'] === 'video')) ?>)
                        </button>
                        <button class="filter-btn" data-filter="utilisateur">
                            <i class="fas fa-user"></i> Utilisateurs (<?= count(array_filter($tousLesElements, fn($e) => $e['type'] === 'utilisateur')) ?>)
                        </button>
                        <button class="filter-btn" data-filter="message">
                            <i class="fas fa-envelope"></i> Messages (<?= count(array_filter($tousLesElements, fn($e) => $e['type'] === 'message')) ?>)
                        </button>
                    </div>

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
                                    <!-- Type avec badge coloré -->
                                    <td>
                                        <?php
                                        $badges = [
                                            'article' => ['color' => 'blue', 'icon' => 'fa-newspaper', 'label' => 'Article'],
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

                                    <!-- Nom / Titre -->
                                    <td class="item-name">
                                        <?php
                                        $nom = '';
                                        switch ($element['type']) {
                                            case 'article':
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
                                        echo htmlspecialchars($nom);
                                        ?>
                                    </td>

                                    <!-- Description -->
                                    <td class="item-description">
                                        <?php
                                        $description = '';
                                        switch ($element['type']) {
                                            case 'article':
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
                                        // Limiter à 80 caractères
                                        $description = mb_strlen($description) > 80 ? mb_substr($description, 0, 80) . '...' : $description;
                                        echo htmlspecialchars($description);
                                        ?>
                                    </td>

                                    <!-- Date de suppression -->
                                    <td>
                                        <?php
                                        $date = new DateTime($element['deleted_at']);
                                        echo $date->format('d/m/Y à H:i');
                                        ?>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-center">
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
                </div>
            <?php endif; ?>
        </section>
    </main>
</div>

<!-- Toast Notification -->
<div id="toastNotification" class="toast-notification"></div>

<!-- Modale de restauration -->
<div id="restoreModal" class="corbeille-modal">
    <div class="corbeille-modal-content">
        <div class="corbeille-modal-header">
            <h2><i class="fas fa-undo"></i> Restaurer l'élément</h2>
            <button class="corbeille-modal-close" onclick="fermerModale('restoreModal')">
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
            <p style="color: rgba(245, 245, 245, 0.8); margin-bottom: 20px;">
                <i class="fas fa-info-circle" style="color: #00afa7;"></i>
                Êtes-vous sûr de vouloir restaurer cet élément ? Il sera remis à sa place d'origine.
            </p>
        </div>
        <div class="corbeille-modal-actions">
            <button class="btn btn-secondary" onclick="fermerModale('restoreModal')">Annuler</button>
            <button class="btn btn-warning" id="confirmRestoreBtn">
                <i class="fas fa-undo"></i> Restaurer
            </button>
        </div>
    </div>
</div>

<!-- Modale de suppression définitive -->
<div id="deleteModal" class="corbeille-modal">
    <div class="corbeille-modal-content">
        <div class="corbeille-modal-header">
            <h2><i class="fas fa-trash-alt"></i> Supprimer définitivement</h2>
            <button class="corbeille-modal-close" onclick="fermerModale('deleteModal')">
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
            <div style="background: rgba(255, 107, 107, 0.1); border: 1px solid rgba(255, 107, 107, 0.3); border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                <p style="color: #ff6b6b; margin: 0; font-weight: 600;">
                    <i class="fas fa-exclamation-triangle"></i>
                    ATTENTION : Cette action est IRRÉVERSIBLE !
                </p>
                <p style="color: rgba(245, 245, 245, 0.8); margin: 10px 0 0 0;">
                    Une fois supprimé définitivement, cet élément ne pourra plus être récupéré.
                </p>
            </div>
        </div>
        <div class="corbeille-modal-actions">
            <button class="btn btn-secondary" onclick="fermerModale('deleteModal')">Annuler</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">
                <i class="fas fa-trash"></i> Supprimer définitivement
            </button>
        </div>
    </div>
</div>

<script id="donneesCorbeille" type="application/json">
<?= json_encode($tousLesElements ?? []) ?>
</script>

<!-- Scripts de sécurité et AJAX -->
<script src="js/securite-helper.js"></script>
<script src="js/ajax-helper.js"></script>
<script src="js/toast-notification.js"></script>
<script src="js/recherche-helper.js"></script>
<script src="js/csrf_manager.js"></script>
<script src="js/corbeille-admin.js"></script>

<script>
  // Initialiser la recherche améliorée
  document.addEventListener('DOMContentLoaded', function() {
    RechercheHelper.initialiser('champRecherche', '#tableauCorbeille tbody tr');
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
