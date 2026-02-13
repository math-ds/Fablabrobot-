<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['utilisateur_role']) || strtolower($_SESSION['utilisateur_role']) !== 'admin') {
    header('Location: ?page=login');
    exit;
}

$GLOBALS['baseUrl'] = '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Commentaires WebTV</title>

    <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirMetaJeton(); ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $GLOBALS['baseUrl'] ?>css/global.css">
    <link rel="stylesheet" href="<?= $GLOBALS['baseUrl'] ?>css/admin-common.css">
    <link rel="stylesheet" href="<?= $GLOBALS['baseUrl'] ?>css/admin-commentaires.css">
    <link rel="stylesheet" href="<?= $GLOBALS['baseUrl'] ?>css/toast-notification.css">
</head>

<body>
<div class="admin-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-logo">
                <a href="?page=admin">
                    <img src="images/global/ajc_logo_blanc.png" alt="AJC Logo">
                </a>
            </div>
            <?php require __DIR__ . '/../parties/sidebar.php'; ?>
        </div>
        <div class="sidebar-footer">
            <a href="?page=logout" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </aside>

    <!-- CONTENU -->
    <main class="main-content">

        <!-- Header avec recherche -->
        <header class="admin-header">
            <div class="search-bar">
                <input type="text" id="champRecherche" placeholder="Rechercher dans les commentaires, auteurs, vidéos...">
            </div>
        </header>

        <!-- Dashboard wrapper -->
        <div class="dashboard">

            <!-- Header avec titre -->
            <div class="header-section">
                <h1>
                    <i class="fas fa-comments"></i>
                    Gestion des Commentaires WebTV
                </h1>
                <p class="subtitle">Surveillez et modérez les commentaires de vos vidéos WebTV</p>
            </div>

            <!-- Messages flash -->
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

            <!-- Grille de statistiques standardisée -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Commentaires totaux</h3>
                    <div class="value"><?= $stats['total'] ?? 0 ?></div>
                </div>

                <div class="stat-card">
                    <h3>Vidéos commentées</h3>
                    <div class="value"><?= count(array_unique(array_column($commentaires, 'video_id'))) ?></div>
                </div>
            </div>

            <!-- Table des commentaires -->
            <div class="table-container">
                <div class="table-header">
                    <h2>
                        <i class="fas fa-list"></i>
                        Liste des commentaires
                    </h2>
                </div>

                <?php if (!empty($commentaires)): ?>
                    <div class="users-table">
                        <table>
                            <thead>
                                <tr>
                                    <th class="col-id">#</th>
                                    <th class="col-medium">Auteur</th>
                                    <th class="col-medium">Vidéo associée</th>
                                    <th class="col-large">Contenu du commentaire</th>
                                    <th class="col-date">Date de publication</th>
                                    <th class="col-actions text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commentaires as $c): ?>
                                    <tr data-comment-id="<?= $c['id'] ?>" data-video-id="<?= $c['video_id'] ?? '' ?>" data-date="<?= strtotime($c['created_at'] ?? '') ?>">
                                        <td style="color: rgba(245, 245, 245, 0.6); font-weight: 600;">#<?= (int)$c['id'] ?></td>

                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <strong style="color: #00afa7;">
                                                    <?= htmlspecialchars($c['auteur'] ?? 'Anonyme') ?>
                                                </strong>
                                                <?php if (!empty($c['user_email'])): ?>
                                                    <small style="color: rgba(245, 245, 245, 0.6);">
                                                        <i class="fas fa-envelope"></i>
                                                        <?= htmlspecialchars($c['user_email']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <td>
                                            <?php if (!empty($c['video_titre'])): ?>
                                                <a href="?page=webtv&video=<?= (int)$c['video_id'] ?>"
                                                   target="_blank"
                                                   style="color: #00afa7; text-decoration: none; display: flex; align-items: center; gap: 8px;"
                                                   title="Voir la vidéo">
                                                    <i class="fas fa-video"></i>
                                                    <span style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                        <?= htmlspecialchars($c['video_titre']) ?>
                                                    </span>
                                                </a>
                                            <?php else: ?>
                                                <span style="color: rgba(245, 245, 245, 0.5); font-style: italic;">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div style="max-width: 400px; line-height: 1.4;">
                                                <div style="overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                                    <?= htmlspecialchars($c['texte'] ?? '') ?>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                                <small style="color: rgba(245, 245, 245, 0.8); font-weight: 600;">
                                                    <?= !empty($c['created_at']) ? date('d/m/Y', strtotime($c['created_at'])) : '—' ?>
                                                </small>
                                                <small style="color: rgba(245, 245, 245, 0.5);">
                                                    <?= !empty($c['created_at']) ? date('H:i', strtotime($c['created_at'])) : '' ?>
                                                </small>
                                            </div>
                                        </td>

                                        <td class="text-center">
                                            <button class="btn btn-primary btn-sm"
                                                    title="Voir le commentaire complet"
                                                    onclick='voirCommentaire(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm"
                                                    title="Supprimer ce commentaire"
                                                    onclick="supprimerCommentaire(<?= $c['id'] ?>, '<?= htmlspecialchars($c['auteur'] ?? 'Anonyme', ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h2>Aucun commentaire</h2>
                        <p>
                            <?php if (!empty($_GET['q'])): ?>
                                Aucun commentaire trouvé pour "<strong><?= htmlspecialchars($_GET['q']) ?></strong>"
                            <?php else: ?>
                                Aucun commentaire n'a encore été publié sur vos vidéos WebTV
                            <?php endif; ?>
                        </p>
                        <button class="btn btn-primary" onclick="window.location.href='?page=admin-webtv'" style="margin-top: 20px;">
                            <i class="fas fa-video"></i>
                            Gérer les vidéos
                        </button>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- Modal réutilisable pour afficher le commentaire complet -->
    <div id="messageModal" class="contact-modal">
        <div class="contact-modal-content">
            <div class="contact-modal-header">
                <h2><i class="fas fa-comment-alt"></i> Détail du commentaire</h2>
                <button class="contact-close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div id="messageDetails" class="message-details" style="color:var(--text-color-light);"></div>
        </div>
    </div>

    <script src="js/securite-helper.js"></script>
    <script src="js/ajax-helper.js"></script>
    <script src="js/toast-notification.js"></script>
    <script src="js/recherche-helper.js"></script>
    <script src="js/csrf_manager.js"></script>
    <script src="js/gestion-contact.js"></script>

    <script>
        // Initialiser la recherche améliorée
        document.addEventListener('DOMContentLoaded', function() {
            RechercheHelper.initialiser('champRecherche', 'table tbody tr');
        });
    </script>

    <script>
        // Fonction de suppression AJAX pour les commentaires
        async function supprimerCommentaire(id, auteur) {
            if (!confirm(`Êtes-vous sûr de vouloir supprimer le commentaire de "${auteur}" ?`)) {
                return;
            }

            try {
                const data = await AjaxHelper.post('?page=admin-comments', {
                    action: 'delete',
                    id: id
                });

                if (data.success) {
                    ToastNotification.succes(data.message || 'Commentaire supprimé avec succès');

                    // Supprimer la ligne du tableau avec animation
                    const ligne = document.querySelector(`tr[data-comment-id="${id}"]`);
                    if (ligne) {
                        ligne.style.transition = 'opacity 0.3s';
                        ligne.style.opacity = '0';
                        setTimeout(() => ligne.remove(), 300);
                    }

                    // Mettre à jour les compteurs
                    const compteurs = document.querySelectorAll('.card-value');
                    compteurs.forEach(compteur => {
                        const match = compteur.textContent.match(/\d+/);
                        if (match) {
                            const actuel = parseInt(match[0]);
                            compteur.textContent = compteur.textContent.replace(/\d+/, actuel - 1);
                        }
                    });
                }
            } catch (error) {
                ToastNotification.erreur(
                    error.data?.message || 'Erreur lors de la suppression'
                );
            }
        }
    </script>

    <!-- JavaScript menu mobile -->
    <script src="js/admin-mobile-menu.js"></script>

</body>
</html>