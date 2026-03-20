<?php
$adminTitle = 'Admin - Commentaires WebTV';
$adminCss = ['admin-commentaires.css'];

$valeurRecherche = trim((string)($_GET['q'] ?? ''));
$filtreType = trim((string)($_GET['type'] ?? 'all'));

require __DIR__ . '/../parties/admin-layout-start.php';
?>

<div class="dashboard">
    <div class="header-section">
        <h1>Gestion des commentaires WebTV</h1>
        <h2 class="sr-only">Sections principales</h2>
        <p class="subtitle">Modérez les commentaires et leurs réponses.</p>
    </div>

    <?php if (!empty($_SESSION['message'])): ?>
      <div
        id="adminFlashData"
        hidden
        data-flash-type="<?= htmlspecialchars((string)($_SESSION['message_type'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>"
        data-flash-message="<?= htmlspecialchars((string)$_SESSION['message'], ENT_QUOTES, 'UTF-8') ?>"></div>
      <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>

    <div class="stats-grid comments-stats">
        <div class="stat-card">
            <h3>Total commentaires</h3>
            <div class="value" data-stat-key="total"><?= (int)($stats['total'] ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <h3>Nouveaux (7 jours)</h3>
            <div class="value" data-stat-key="recent"><?= (int)($stats['recent'] ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <h3>Commentaires racine</h3>
            <div class="value" data-stat-key="parents"><?= (int)($stats['parents'] ?? 0) ?></div>
        </div>
        <div class="stat-card">
            <h3>Réponses</h3>
            <div class="value" data-stat-key="reponses"><?= (int)($stats['reponses'] ?? 0) ?></div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header">
            <h2>
                <i class="fas fa-list"></i>
                Liste des commentaires
            </h2>
            <?php
                $filtreRechercheActif = trim((string)$valeurRecherche) !== '';
                $filtreTypeActif = (string)$filtreType !== 'all';
                $filtrageActif = $filtreRechercheActif || $filtreTypeActif;
            ?>
            <span class="header-count-pill">
                <i class="fas fa-video"></i>
                <?= (int)$videos_commentees ?> video(s) commentee(s)
            </span>
            <?php if ($filtrageActif): ?>
                <span class="header-count-pill">
                    <i class="fas fa-filter"></i>
                    <?= (int)($total_commentaires ?? 0) ?> résultat(s)
                </span>
            <?php endif; ?>
        </div>

        <form method="GET" class="table-search comments-filters-form">
            <input type="hidden" name="page" value="admin-comments">

            <div class="search-bar">
                <input
                    type="text"
                    id="champRecherche"
                    name="q"
                    value="<?= htmlspecialchars($valeurRecherche, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Rechercher dans commentaires, auteurs, videos..."
                    autocomplete="off">
            </div>

            <div class="comments-type-filter">
                <label for="typeFilter" class="comments-type-label">Type</label>
                <select id="typeFilter" name="type">
                    <option value="all" <?= $filtreType === 'all' ? 'selected' : '' ?>>Tous</option>
                    <option value="parent" <?= $filtreType === 'parent' ? 'selected' : '' ?>>Commentaires</option>
                    <option value="reponse" <?= $filtreType === 'reponse' ? 'selected' : '' ?>>Réponses</option>
                </select>
            </div>

            <div class="comments-filters-actions">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
                <a href="?page=admin-comments" class="btn btn-secondary btn-sm">
                    <i class="fas fa-rotate-left"></i> Reset
                </a>
            </div>
        </form>

        <?php if (!empty($commentaires)): ?>
            <div class="users-table">
                <table id="tableauCommentaires">
                    <thead>
                        <tr>
                            <th class="col-id">#</th>
                            <th class="col-small">Type</th>
                            <th class="col-medium">Auteur</th>
                            <th class="col-medium">Video</th>
                            <th class="col-large">Contenu</th>
                            <th class="col-date">Date</th>
                            <th class="col-actions text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commentaires as $c): ?>
                            <?php
                            $commentaireId = (int)($c['id'] ?? 0);
                            $videoId = (int)($c['video_id'] ?? 0);
                            $parentId = !empty($c['parent_id']) ? (int)$c['parent_id'] : 0;
                            $isReply = $parentId > 0;
                            $auteur = (string)($c['auteur'] ?? 'Anonyme');
                            $parentAuteur = trim((string)($c['parent_auteur'] ?? ''));
                            $parentTexte = trim((string)($c['parent_texte'] ?? ''));
                            $couper = static function (string $texte, int $max): string {
                                if (function_exists('mb_substr')) {
                                    return mb_substr($texte, 0, $max);
                                }
                                return substr($texte, 0, $max);
                            };
                            $longueur = static function (string $texte): int {
                                if (function_exists('mb_strlen')) {
                                    return mb_strlen($texte);
                                }
                                return strlen($texte);
                            };

                            $parentTexteCourt = $parentTexte !== '' ? $couper($parentTexte, 120) : '';
                            if ($parentTexteCourt !== '' && $longueur($parentTexte) > 120) {
                                $parentTexteCourt .= '...';
                            }

                            $payload = [
                                'id' => $commentaireId,
                                'video_id' => $videoId,
                                'parent_id' => $parentId > 0 ? $parentId : null,
                                'auteur' => $auteur,
                                'user_email' => (string)($c['user_email'] ?? ''),
                                'video_titre' => (string)($c['video_titre'] ?? ''),
                                'created_at' => (string)($c['created_at'] ?? ''),
                                'texte' => (string)($c['texte'] ?? ''),
                                'parent_auteur' => $parentAuteur,
                                'parent_texte' => $parentTexte,
                            ];
                            ?>
                            <tr
                                class="comment-row <?= $isReply ? 'is-reply' : 'is-parent' ?>"
                                data-comment-id="<?= $commentaireId ?>"
                                data-parent-id="<?= $parentId ?>"
                                data-video-id="<?= $videoId ?>"
                                data-date="<?= strtotime((string)($c['created_at'] ?? '')) ?>">

                                <td data-label="#" data-col="id" class="comment-id-cell">#<?= $commentaireId ?></td>

                                <td data-label="Type" data-col="type" class="comment-type-cell">
                                    <span class="comment-type-badge <?= $isReply ? 'reply' : 'parent' ?>">
                                        <?= $isReply ? 'Réponse' : 'Commentaire' ?>
                                    </span>
                                </td>

                                <td data-label="Auteur" data-col="utilisateur">
                                    <div class="comment-author-cell">
                                        <strong class="comment-author-name"><?= htmlspecialchars($auteur, ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php if (!empty($c['user_email'])): ?>
                                            <small class="comment-author-email">
                                                <i class="fas fa-envelope"></i>
                                                <?= htmlspecialchars((string)$c['user_email'], ENT_QUOTES, 'UTF-8') ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td data-label="Video" data-col="titre">
                                    <?php if (!empty($c['video_titre']) && $videoId > 0): ?>
                                        <a href="?page=webtv&video=<?= $videoId ?>" target="_blank" class="comment-video-link" title="Voir la video">
                                            <i class="fas fa-video"></i>
                                            <span class="comment-video-title"><?= htmlspecialchars((string)$c['video_titre'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </a>
                                    <?php else: ?>
                                        <span class="comment-muted">-</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Contenu" data-col="description">
                                    <div class="comment-content-cell">
                                        <?php if ($isReply): ?>
                                            <div class="comment-reply-block">
                                                <p class="comment-reply-label">
                                                    <i class="fas fa-reply"></i> Réponse à <?= htmlspecialchars($parentAuteur !== '' ? $parentAuteur : 'un commentaire', ENT_QUOTES, 'UTF-8') ?>
                                                </p>
                                                <?php if ($parentTexteCourt !== ''): ?>
                                                    <p class="comment-parent-preview">
                                                        <?= htmlspecialchars($parentTexteCourt, ENT_QUOTES, 'UTF-8') ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div class="comment-reply-text">
                                                    <?= htmlspecialchars((string)($c['texte'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="comment-text"><?= htmlspecialchars((string)($c['texte'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td data-label="Date" data-col="date">
                                    <div class="comment-date-cell">
                                        <small class="comment-date"><?= !empty($c['created_at']) ? date('d/m/Y', strtotime((string)$c['created_at'])) : '-' ?>
                                        </small>
                                        <small class="comment-time">
                                            <?= !empty($c['created_at']) ? date('H:i', strtotime((string)$c['created_at'])) : '' ?>
                                        </small>
                                    </div>
                                </td>

                                <td class="text-center" data-label="Actions" data-col="actions">
                                    <button
                                        class="btn btn-primary btn-sm"
                                        type="button"
                                        title="Voir le commentaire complet"
                                        data-comment-view="<?= htmlspecialchars((string)json_encode($payload, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button
                                        class="btn btn-danger btn-sm"
                                        type="button"
                                        title="Supprimer ce commentaire"
                                        data-comment-delete-id="<?= $commentaireId ?>"
                                        data-comment-delete-name="<?= htmlspecialchars($auteur, ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php require __DIR__ . '/../parties/pagination.php'; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <h2>Aucun commentaire</h2>
                <p>
                    <?php if ($valeurRecherche !== ''): ?>
                        Aucun résultat pour "<strong><?= htmlspecialchars($valeurRecherche, ENT_QUOTES, 'UTF-8') ?></strong>".
                    <?php elseif ($filtreType === 'parent'): ?>
                        Aucun commentaire racine trouvé.
                    <?php elseif ($filtreType === 'reponse'): ?>
                        Aucune réponse trouvée.
                    <?php else: ?>
                        Aucun commentaire n'a encore été publié.
                    <?php endif; ?>
                </p>
                <button class="btn btn-primary" type="button" data-comment-redirect="?page=admin-webtv">
                    <i class="fas fa-video"></i>
                    Gérer les vidéos
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="messageModal" class="contact-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Détail commentaire">
    <div class="contact-modal-content">
        <div class="contact-modal-header">
            <h2><i class="fas fa-comment-alt"></i> Détail du commentaire</h2>
            <button class="contact-close-modal" type="button" data-comment-close-modal="1" aria-label="Fermer la modale">&times;</button>
        </div>
        <div id="messageDetails" class="message-details"></div>
    </div>
</div>

<?php
$adminScripts = ['js/securite-helper.js', 'js/ajax-helper.js', 'js/toast-notification.js', 'js/csrf_manager.js', 'js/gestion-contact.js', 'js/admin-commentaires-actions.js'];
require __DIR__ . '/../parties/admin-layout-end.php';
?>


