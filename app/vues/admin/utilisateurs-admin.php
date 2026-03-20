<?php
$adminTitle = 'Gestion des Utilisateurs - Admin FABLAB';
$adminCss = ['admin-utilisateurs.css'];

require __DIR__ . '/../parties/admin-layout-start.php';
?>

    <section class="dashboard" data-admin-utilisateurs="1">
      <h1>Gestion des Utilisateurs</h1>

    
      
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
          <h3>Total Utilisateurs</h3>
          <div class="value"><?= $stats['total_users'] ?></div>
        </div>
        <div class="stat-card">
          <h3>Administrateurs</h3>
          <div class="value admin-value-danger"><?= $stats['admins'] ?></div>
        </div>
        <div class="stat-card">
          <h3>&Eacute;diteurs</h3>
          <div class="value admin-value-warning"><?= $stats['editeurs'] ?></div>
        </div>
        <div class="stat-card">
          <h3>Utilisateurs</h3>
          <div class="value admin-value-success"><?= $stats['utilisateurs'] ?></div>
        </div>
      </div>


      <div class="table-container">
        <div class="table-header">
          <h3 class="table-title">
            <i class="fas fa-users"></i> Liste des utilisateurs
          </h3>
          <?php
            $filtreRechercheActif = trim((string)($recherche ?? '')) !== '';
            $filtreRoleActif = (string)($filtreRole ?? 'all') !== 'all';
            $filtrageActif = $filtreRechercheActif || $filtreRoleActif;
            $compteurEntete = $filtrageActif ? (int)($totalFiltres ?? 0) : (int)($stats['total_users'] ?? 0);
            $libelleEntete = $filtrageActif ? 'resultat(s)' : 'utilisateur(s)';
          ?>
          <div class="table-actions">
            <button type="button" class="btn btn-primary" data-users-open-create="1">
              <i class="fas fa-user-plus"></i> Nouvel Utilisateur
            </button>
            <span class="stats-badge">
              <i class="fas fa-users"></i> <?= $compteurEntete ?> <?= $libelleEntete ?>
            </span>
          </div>
        </div>
        <div class="table-search">
          <div class="search-bar">
            <input type="text" id="champRecherche" value="<?= htmlspecialchars((string)($recherche ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Rechercher un utilisateur...">
          </div>
        </div>

        <?php $filtreActif = (string)($filtreRole ?? 'all'); ?>
        <div class="filters" data-server-filtering="1">
          <button type="button" class="filter-btn <?= $filtreActif === 'all' ? 'active' : '' ?>" data-server-filter="1" data-users-filter="all">
            <i class="fas fa-users"></i> Tous (<?= (int)($stats['total_users'] ?? 0) ?>)
          </button>
          <button type="button" class="filter-btn <?= $filtreActif === 'admin' ? 'active' : '' ?>" data-server-filter="1" data-users-filter="admin">
            <i class="fas fa-user-shield"></i> Admins (<?= (int)($stats['admins'] ?? 0) ?>)
          </button>
          <button type="button" class="filter-btn <?= $filtreActif === 'editeur' ? 'active' : '' ?>" data-server-filter="1" data-users-filter="editeur">
            <i class="fas fa-user-edit"></i> &Eacute;diteurs (<?= (int)($stats['editeurs'] ?? 0) ?>)
          </button>
          <button type="button" class="filter-btn <?= $filtreActif === 'utilisateur' ? 'active' : '' ?>" data-server-filter="1" data-users-filter="utilisateur">
            <i class="fas fa-user"></i> Utilisateurs (<?= (int)($stats['utilisateurs'] ?? 0) ?>)
          </button>
        </div>

        <div class="users-table">
          <?php if ((int)($stats['total_users'] ?? 0) === 0): ?>
            <div class="empty-state">
              <i class="fas fa-user-slash"></i>
              <h2>Aucun utilisateur trouve</h2>
              <p>Commencez par creer votre premier utilisateur.</p>
            </div>
          <?php elseif ((int)($totalFiltres ?? 0) === 0): ?>
            <div class="empty-state">
              <i class="fas fa-search"></i>
              <h2>Aucun resultat</h2>
              <p>Aucun utilisateur ne correspond au filtre/recherche actuel.</p>
            </div>
          <?php else: ?>
            <table id="tableauUtilisateurs">
              <thead>
                <tr>
                  <th class="col-medium">Utilisateur</th>
                  <th class="col-medium">Email</th>
                  <th class="col-medium">Role</th>
                  <th class="col-date">Date d'inscription</th>
                  <th class="col-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                  <tr data-user-id="<?= $u['id'] ?>" data-role="<?= strtolower($u['role']) ?>">
                    <td class="user-info" data-label="Utilisateur" data-col="utilisateur">
                      <div class="user-avatar">
                        <?php if (!empty($u['avatar_has_photo']) && !empty($u['avatar_photo_url'])): ?>
                          <img src="<?= htmlspecialchars($u['avatar_photo_url']) ?>" alt="Photo de profil de <?= htmlspecialchars($u['nom']) ?>" loading="lazy">
                        <?php else: ?>
                          <span class="<?= htmlspecialchars((string)($u['avatar_classe_couleur'] ?? 'avatar-couleur-1'), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string)($u['avatar_initiales'] ?? strtoupper(substr((string)$u['nom'], 0, 2)))) ?>
                          </span>
                        <?php endif; ?>
                      </div>
                      <div class="user-details">
                        <span class="user-name"><?= htmlspecialchars($u['nom']) ?></span>
                      </div>
                    </td>
                    <td data-label="Email" data-col="email"><?= htmlspecialchars($u['email']) ?></td>
                    <td class="text-center" data-label="Role" data-col="role">
                      <span class="role-badge <?= match(strtolower($u['role'])) {
                        'admin' => 'role-admin',
                        'editeur' => 'role-editeur',
                        default => 'role-utilisateur'
                      } ?>">
                        <?= match(strtolower($u['role'])) {
                          'admin' => 'Admin',
                          'editeur' => 'Editeur',
                          default => 'Utilisateur'
                        } ?>
                      </span>
                    </td>
                    <td data-label="Date d'inscription" data-col="date"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td class="text-center" data-label="Actions" data-col="actions">
                      <button
                        type="button"
                        class="btn btn-sm btn-primary"
                        data-users-view="<?= htmlspecialchars(json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"
                        title="Voir">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button
                        type="button"
                        class="btn btn-sm btn-warning"
                        data-users-edit="<?= htmlspecialchars(json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"
                        title="Modifier">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button
                        type="button"
                        class="btn btn-sm btn-danger"
                        data-users-delete-id="<?= (int) $u['id'] ?>"
                        data-users-delete-name="<?= htmlspecialchars($u['nom'], ENT_QUOTES, 'UTF-8') ?>"
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
  <div id="modaleUtilisateur" class="modal" role="dialog" aria-modal="true" aria-hidden="true" aria-label="Gestion utilisateur">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="titreModale"><i class="fas fa-user"></i> Modifier l'Utilisateur</h2>
      <button type="button" class="close-modal" data-users-close-modal="1" aria-label="Fermer la modale">&times;</button>
    </div>
    <form id="formulaireUtilisateur" method="POST" action="?page=admin-utilisateurs">
      <input type="hidden" name="action" value="update" id="actionFormulaire">
      <input type="hidden" name="user_id" id="idUtilisateur">
      <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>

      <div class="form-group">
        <label for="nomUtilisateur">Nom complet *</label>
        <input type="text" name="nom" id="nomUtilisateur" required>
      </div>

      <div class="form-group">
        <label for="emailUtilisateur">Email *</label>
        <input type="email" name="email" id="emailUtilisateur" required>
      </div>

      <div class="form-group">
        <label for="roleUtilisateur">R&ocirc;le *</label>
        <select name="role" id="roleUtilisateur" required>
          <option value="admin">Admin</option>
          <option value="editeur">&Eacute;diteur</option>
          <option value="utilisateur">Utilisateur</option>
        </select>
      </div>

      <div class="form-group">
        <label for="motDePasseUtilisateur">Mot de passe *</label>
        <input type="password" name="password" id="motDePasseUtilisateur">
        <div class="password-info">
          <i class="fas fa-info-circle"></i> Laissez vide pour conserver le mot de passe actuel.
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-danger" data-users-close-modal="1">
          <i class="fas fa-times"></i> Annuler
        </button>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Enregistrer
        </button>
      </div>
    </form>
  </div>
</div>


<?php
$adminScripts = ['js/securite-helper.js', 'js/ajax-helper.js', 'js/toast-notification.js', 'js/recherche-helper.js', 'js/csrf_manager.js', 'js/gestion-utilisateurs.js'];
require __DIR__ . '/../parties/admin-layout-end.php';
?>

