<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des Messages de Contact - Admin FABLAB</title>
  <?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; echo CsrfHelper::obtenirMetaJeton(); ?>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/admin-common.css">
  <link rel="stylesheet" href="css/admin-contact.css">
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
        <input type="text" id="champRecherche" placeholder="Rechercher un message...">
      </div>
    </header>

    <section class="dashboard">
      <h1><i class="fas fa-envelope"></i> Gestion des Messages de Contact</h1>


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
          <h3>Total Messages</h3>
          <div class="value"><?= $stats['total'] ?></div>
        </div>
        <div class="stat-card">
          <h3>Non Lus</h3>
          <div class="value" style="color:#ff6b6b;"><?= $stats['non_lus'] ?></div>
        </div>
        <div class="stat-card">
          <h3>Lus</h3>
          <div class="value" style="color:#ffa500;"><?= $stats['lus'] ?></div>
        </div>
        <div class="stat-card">
          <h3>Traités</h3>
          <div class="value" style="color:#4ade80;"><?= $stats['traites'] ?></div>
        </div>
      </div>


      <!-- TABLEAU STANDARDISÉ -->
      <div class="table-container">
        <div class="table-header">
          <h3 class="table-title">
            <i class="fas fa-envelope"></i> Messages de contact
          </h3>
          <div class="stats-badge">
            <i class="fas fa-envelope"></i> <?= count($contacts) ?> message(s)
          </div>
        </div>

        <!-- Filtres -->
        <div class="filters">
          <button class="filter-btn active" onclick="filtrerMessages('all')">
            <i class="fas fa-inbox"></i> Tous
          </button>
          <button class="filter-btn" onclick="filtrerMessages('non_lu')">
            <i class="fas fa-envelope"></i> Non lus
          </button>
          <button class="filter-btn" onclick="filtrerMessages('lu')">
            <i class="fas fa-envelope-open"></i> Lus
          </button>
          <button class="filter-btn" onclick="filtrerMessages('traite')">
            <i class="fas fa-check-circle"></i> Traités
          </button>
        </div>

        <!-- Tableau -->
        <div class="users-table">
          <?php if (empty($contacts)): ?>
            <div class="empty-state">
              <i class="fas fa-inbox"></i>
              <h2>Aucun message trouvé</h2>
              <p>Vous n'avez reçu aucun message de contact</p>
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
                    <td>
                      <span class="contact-message-nom"><?= htmlspecialchars($msg['nom']) ?></span>
                      <span class="contact-message-email"><?= htmlspecialchars($msg['email']) ?></span>
                    </td>
                    <td>
                      <span class="contact-message-sujet"><?= htmlspecialchars($msg['sujet']) ?></span>
                    </td>
                    <td>
                      <p class="contact-message-excerpt"><?= htmlspecialchars(substr($msg['message'], 0, 50)) ?>...</p>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($msg['date_envoi'])) ?></td>
                    <td class="text-center">
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
                    <td class="text-center">
                      <button class="btn btn-sm btn-primary" onclick='voirMessage(<?= json_encode($msg, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Voir le message">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn btn-sm btn-danger" onclick="supprimerMessage(<?= $msg['id'] ?>, '<?= htmlspecialchars($msg['nom'], ENT_QUOTES) ?>')" title="Supprimer">
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


<div id="messageModal" class="contact-modal">
  <div class="contact-modal-content">
    <div class="contact-modal-header">
      <h2><i class="fas fa-envelope-open"></i> Détails du Message</h2>
      <button class="contact-close-modal" onclick="closeModal()">&times;</button>
    </div>
    <div id="messageDetails"></div>
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
    RechercheHelper.initialiser('champRecherche', '#contactsTable tbody tr');
  });
</script>

<script>
    // Fonction de suppression AJAX pour les messages de contact
    async function supprimerMessage(id, nom) {
        if (!confirm(`Êtes-vous sûr de vouloir supprimer le message de "${nom}" ?`)) {
            return;
        }

        try {
            const data = await AjaxHelper.post('?page=admin-contact', {
                action: 'delete',
                contact_id: id
            });

            if (data.success) {
                ToastNotification.succes(data.message || 'Message supprimé avec succès');

                // Supprimer la ligne du tableau avec animation
                const ligne = document.querySelector(`tr[data-contact-id="${id}"]`);
                if (ligne) {
                    ligne.style.transition = 'opacity 0.3s';
                    ligne.style.opacity = '0';
                    setTimeout(() => ligne.remove(), 300);
                }

                // Mettre à jour les compteurs
                const compteurs = document.querySelectorAll('.stat-card .value');
                compteurs.forEach(compteur => {
                    const match = compteur.textContent.match(/\d+/);
                    if (match) {
                        const actuel = parseInt(match[0]);
                        compteur.textContent = compteur.textContent.replace(/\d+/, actuel - 1);
                    }
                });
            }
        } catch (error) {
            // Mettre à jour le token CSRF si fourni dans l'erreur
            if (error.data?.new_token) {
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.content = error.data.new_token;
                }
            }

            ToastNotification.erreur(
                error.data?.message || 'Erreur lors de la suppression'
            );
        }
    }

    // Fonction pour changer le statut en AJAX
    async function changerStatut(id, statut, nom) {
        try {
            const data = await AjaxHelper.post('?page=admin-contact', {
                action: statut,
                contact_id: id,
                nom: nom
            });

            if (data.success) {
                ToastNotification.succes(data.message);

                // Mettre à jour la ligne du tableau
                const ligne = document.querySelector(`tr[data-contact-id="${id}"]`);
                if (ligne) {
                    ligne.setAttribute('data-statut', statut);
                    ligne.classList.remove('unread');

                    // Mettre à jour le badge de statut
                    const badge = ligne.querySelector('.role-badge');
                    if (badge) {
                        badge.className = 'role-badge';
                        if (statut === 'lu') {
                            badge.classList.add('role-editeur');
                            badge.textContent = 'Lu';
                        } else if (statut === 'traite') {
                            badge.classList.add('role-admin');
                            badge.textContent = 'Traité';
                        } else if (statut === 'non_lu') {
                            badge.classList.add('role-utilisateur');
                            badge.textContent = 'Non lu';
                        }
                    }
                }
            }
        } catch (error) {
            // Mettre à jour le token CSRF si fourni dans l'erreur
            if (error.data?.new_token) {
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.content = error.data.new_token;
                }
            }

            ToastNotification.erreur(
                error.data?.message || 'Erreur lors du changement de statut'
            );
        }
    }
</script>

<!-- JavaScript menu mobile -->
<script src="js/admin-mobile-menu.js"></script>

</body>
</html>