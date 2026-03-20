<?php
$adminTitle = 'Gestion des Messages de Contact - Admin FABLAB';
$adminCss = ['admin-contact.css'];

require __DIR__ . '/../parties/admin-layout-start.php';
?>
    <section class="dashboard" data-admin-contact="1">
      <h1>Gestion des Messages de Contact</h1>


      
    <h2 class="sr-only">Sections principales</h2>
<?php if (!empty($_SESSION['message'])): ?>
        <div
          id="adminFlashData"
          hidden
          data-flash-type="<?= htmlspecialchars((string)($_SESSION['message_type'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>"
          data-flash-message="<?= htmlspecialchars((string)$_SESSION['message'], ENT_QUOTES, 'UTF-8') ?>"></div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
      <?php endif; ?>

      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Messages</h3>
          <div class="value"><?= $stats['total'] ?></div>
        </div>
        <div class="stat-card">
          <h3>Non Lus</h3>
          <div class="value admin-value-danger"><?= $stats['non_lus'] ?></div>
        </div>
        <div class="stat-card">
          <h3>Lus</h3>
          <div class="value admin-value-warning"><?= $stats['lus'] ?></div>
        </div>
        <div class="stat-card">
          <h3>Traités</h3>
          <div class="value admin-value-success"><?= $stats['traites'] ?></div>
        </div>
      </div>


      <div class="table-container">
        <div class="table-header">
          <h3 class="table-title">
            <i class="fas fa-envelope"></i> Messages de contact
          </h3>
          <?php
            $filtreRechercheActif = trim((string)($recherche ?? '')) !== '';
            $filtreStatutActif = (string)($filtreStatut ?? 'all') !== 'all';
            $filtrageActif = $filtreRechercheActif || $filtreStatutActif;
            $compteurEntete = $filtrageActif ? (int)($totalFiltres ?? 0) : (int)($stats['total'] ?? 0);
            $libelleEntete = $filtrageActif ? 'resultat(s)' : 'message(s)';
          ?>
          <div class="stats-badge">
            <i class="fas fa-envelope"></i> <?= $compteurEntete ?> <?= $libelleEntete ?>
          </div>
        </div>
        <div class="table-search">
          <div class="search-bar">
            <input type="text" id="champRecherche" value="<?= htmlspecialchars((string)($recherche ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Rechercher un message...">
          </div>
        </div>

        <?php $filtreActif = (string)($filtreStatut ?? 'all'); ?>
        <div class="filters" data-server-filtering="1">
          <button type="button" class="filter-btn <?= $filtreActif === 'all' ? 'active' : '' ?>" data-server-filter="1" data-contact-filter="all">
            <i class="fas fa-inbox"></i> Tous (<?= (int)($stats['total'] ?? 0) ?>)
          </button>
          <button type="button" class="filter-btn <?= $filtreActif === 'non_lu' ? 'active' : '' ?>" data-server-filter="1" data-contact-filter="non_lu">
            <i class="fas fa-envelope"></i> Non lus (<?= (int)($stats['non_lus'] ?? 0) ?>)
          </button>
          <button type="button" class="filter-btn <?= $filtreActif === 'lu' ? 'active' : '' ?>" data-server-filter="1" data-contact-filter="lu">
            <i class="fas fa-envelope-open"></i> Lus (<?= (int)($stats['lus'] ?? 0) ?>)
          </button>
          <button type="button" class="filter-btn <?= $filtreActif === 'traite' ? 'active' : '' ?>" data-server-filter="1" data-contact-filter="traite">
            <i class="fas fa-check-circle"></i> Traités (<?= (int)($stats['traites'] ?? 0) ?>)
          </button>
        </div>

        <div class="users-table">
          <?php if ((int)($stats['total'] ?? 0) === 0): ?>
            <div class="empty-state">
              <i class="fas fa-inbox"></i>
              <h2>Aucun message trouvé</h2>
              <p>Vous n'avez reçu aucun message de contact</p>
            </div>
          <?php elseif ((int)($totalFiltres ?? 0) === 0): ?>
            <div class="empty-state">
              <i class="fas fa-search"></i>
              <h2>Aucun résultat</h2>
              <p>Aucun message ne correspond au filtre/recherche actuel.</p>
            </div>
          <?php else: ?>
            <table id="contactsTable">
              <thead>
                <tr>
                  <th class="col-medium">Contact</th>
                  <th class="col-medium">Sujet</th>
                  <th class="col-large">Message</th>
                  <th class="col-date">Date</th>
                  <th class="col-small">Statut</th>
                  <th class="col-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($contacts as $msg): ?>
                  <tr data-contact-id="<?= $msg['id'] ?>" data-statut="<?= htmlspecialchars($msg['statut']) ?>" class="<?= $msg['statut'] === 'non_lu' ? 'unread' : '' ?>">
                    <td data-label="Contact" data-col="contact">
                      <span class="contact-message-nom"><?= htmlspecialchars($msg['nom']) ?></span>
                      <span class="contact-message-email"><?= htmlspecialchars($msg['email']) ?></span>
                    </td>
                    <td data-label="Sujet" data-col="sujet">
                      <span class="contact-message-sujet"><?= htmlspecialchars($msg['sujet']) ?></span>
                    </td>
                    <td data-label="Message" data-col="message">
                      <?php
                        $messageContact = (string)($msg['message'] ?? '');
                        $extraitContact = function_exists('mb_substr')
                          ? mb_substr($messageContact, 0, 50, 'UTF-8')
                          : substr($messageContact, 0, 50);
                      ?>
                      <p class="contact-message-excerpt"><?= htmlspecialchars($extraitContact, ENT_QUOTES, 'UTF-8') ?>...</p>
                    </td>
                    <td data-label="Date" data-col="date"><?= date('d/m/Y H:i', strtotime($msg['date_envoi'])) ?></td>
                    <td class="text-center" data-label="Statut" data-col="statut">
                      <span class="role-badge <?= match($msg['statut']) {
                        'lu' => 'role-editeur',
                        'traite' => 'role-admin',
                        default => 'role-utilisateur'
                      } ?>">
                        <?php
                          if ($msg['statut'] === 'non_lu') {
                            echo 'Non lu';
                          } elseif ($msg['statut'] === 'lu') {
                            echo 'Lu';
                          } elseif ($msg['statut'] === 'traite') {
                            echo 'Traité';
                          } else {
                            echo htmlspecialchars($msg['statut']);
                          }
                        ?>
                      </span>
                    </td>
                    <td class="text-center" data-label="Actions" data-col="actions">
                      <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        data-contact-view="<?= htmlspecialchars(json_encode($msg, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"
                        title="Voir le message">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        data-contact-delete-id="<?= (int) $msg['id'] ?>"
                        data-contact-delete-name="<?= htmlspecialchars($msg['nom'], ENT_QUOTES, 'UTF-8') ?>"
                        title="Supprimer">
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
        <?php require __DIR__ . '/../parties/pagination.php'; ?>
      <?php endif; ?>
    </section>
  <div id="messageModal" class="contact-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Détail message">
  <div class="contact-modal-content">
    <div class="contact-modal-header">
      <h2><i class="fas fa-envelope-open"></i> Détails du Message</h2>
      <button type="button" class="contact-close-modal" data-contact-close-modal="1" aria-label="Fermer la modale">&times;</button>
    </div>
    <div id="messageDetails"></div>
  </div>
</div>


<?php
$adminScripts = ['js/securite-helper.js', 'js/ajax-helper.js', 'js/toast-notification.js', 'js/recherche-helper.js', 'js/csrf_manager.js', 'js/gestion-contact.js', 'js/admin-contact-actions.js'];
require __DIR__ . '/../parties/admin-layout-end.php';
?>
