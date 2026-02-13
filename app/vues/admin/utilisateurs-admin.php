<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des Utilisateurs - Admin FABLAB</title>
  <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirMetaJeton(); ?>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/admin-common.css">
  <link rel="stylesheet" href="css/admin-utilisateurs.css">
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
        <input type="text" id="champRecherche" placeholder="Rechercher un utilisateur...">
      </div>
    </header>

    <section class="dashboard">
      <h1><i class="fas fa-users"></i> Gestion des Utilisateurs</h1>

    
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

      <!-- Cartes de statistiques standardisées -->
      <div class="stats-grid">
        <div class="stat-card">
          <h3>Total Utilisateurs</h3>
          <div class="value"><?= $stats['total_users'] ?></div>
        </div>
        <div class="stat-card">
          <h3>Administrateurs</h3>
          <div class="value" style="color:#ff6b6b;"><?= $stats['admins'] ?></div>
        </div>
        <div class="stat-card">
          <h3>Éditeurs</h3>
          <div class="value" style="color:#ffa500;"><?= $stats['editeurs'] ?></div>
        </div>
        <div class="stat-card">
          <h3>Utilisateurs</h3>
          <div class="value" style="color:#4ade80;"><?= $stats['utilisateurs'] ?></div>
        </div>
      </div>


      <!-- TABLEAU STANDARDISÉ -->
      <div class="table-container">
        <div class="table-header">
          <h3 class="table-title">
            <i class="fas fa-users"></i> Liste des utilisateurs
          </h3>
          <div class="table-actions">
            <button class="btn btn-primary" onclick="ouvrirModaleAjout()">
              <i class="fas fa-user-plus"></i> Nouvel Utilisateur
            </button>
          </div>
        </div>

        <!-- Filtres -->
        <div class="filters">
          <button class="filter-btn active" onclick="filtrerUtilisateurs('all')">
            <i class="fas fa-users"></i> Tous
          </button>
          <button class="filter-btn" onclick="filtrerUtilisateurs('admin')">
            <i class="fas fa-user-shield"></i> Admins
          </button>
          <button class="filter-btn" onclick="filtrerUtilisateurs('editeur')">
            <i class="fas fa-user-edit"></i> Éditeurs
          </button>
          <button class="filter-btn" onclick="filtrerUtilisateurs('utilisateur')">
            <i class="fas fa-user"></i> Utilisateurs
          </button>
        </div>

        <!-- Tableau -->
        <div class="users-table">
          <?php if (empty($users)): ?>
            <div class="empty-state">
              <i class="fas fa-user-slash"></i>
              <h2>Aucun utilisateur trouvé</h2>
              <p>Commencez par créer votre premier utilisateur</p>
            </div>
          <?php else: ?>
            <table id="tableauUtilisateurs">
              <thead>
                <tr>
                  <th class="col-medium">Utilisateur</th>
                  <th class="col-medium">Email</th>
                  <th class="col-small">Rôle</th>
                  <th class="col-date">Date d'inscription</th>
                  <th class="col-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                  <tr data-user-id="<?= $u['id'] ?>" data-role="<?= strtolower($u['role']) ?>">
                    <td class="user-info">
                      <div class="user-avatar"><?= strtoupper(substr($u['nom'], 0, 2)) ?></div>
                      <div class="user-details">
                        <span class="user-name"><?= htmlspecialchars($u['nom']) ?></span>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td class="text-center">
                      <span class="role-badge <?= match(strtolower($u['role'])) {
                        'admin' => 'role-admin',
                        'editeur', 'éditeur' => 'role-editeur',
                        default => 'role-utilisateur'
                      } ?>">
                        <?= ucfirst($u['role']) ?>
                      </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($u['date_creation'])) ?></td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-primary" onclick='voirUtilisateur(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Voir">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn btn-sm btn-warning" onclick='editerUtilisateur(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Modifier">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-sm btn-danger" onclick="supprimerUtilisateur(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nom'], ENT_QUOTES) ?>')" title="Supprimer">
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
    </section>
  </main>
</div>


<div id="modaleUtilisateur" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2 id="titreModale"><i class="fas fa-user"></i> Modifier l'Utilisateur</h2>
      <button class="close-modal" onclick="fermerModale()">&times;</button>
    </div>
    <form id="formulaireUtilisateur" method="POST" action="?page=admin-utilisateurs">
      <input type="hidden" name="action" value="update" id="actionFormulaire">
      <input type="hidden" name="user_id" id="idUtilisateur">
      <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirChampJeton(); ?>

      <div class="form-group">
        <label>Nom complet *</label>
        <input type="text" name="nom" id="nomUtilisateur" required>
      </div>

      <div class="form-group">
        <label>Email *</label>
        <input type="email" name="email" id="emailUtilisateur" required>
      </div>

      <div class="form-group">
        <label>Rôle *</label>
        <select name="role" id="roleUtilisateur" required>
          <option value="admin">Admin</option>
          <option value="editeur">Éditeur</option>
          <option value="utilisateur">Utilisateur</option>
        </select>
      </div>

      <div class="form-group">
        <label>Mot de passe *</label>
        <input type="password" name="mot_de_passe" id="motDePasseUtilisateur">
        <div class="password-info">
          <i class="fas fa-info-circle"></i> Laissez vide pour conserver le mot de passe actuel.
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-danger" onclick="fermerModale()">✖ Annuler</button>
        <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<script src="js/securite-helper.js"></script>
<script src="js/ajax-helper.js"></script>
<script src="js/toast-notification.js"></script>
<script src="js/recherche-helper.js"></script>
<script src="js/csrf_manager.js"></script>
<script src="js/gestion-utilisateurs.js"></script>

<script>
  // Initialiser la recherche améliorée
  document.addEventListener('DOMContentLoaded', function() {
    RechercheHelper.initialiser('champRecherche', '#tableauUtilisateurs tbody tr');
  });
</script>

<!-- JavaScript menu mobile -->
<script src="js/admin-mobile-menu.js"></script>

</body>
</html>