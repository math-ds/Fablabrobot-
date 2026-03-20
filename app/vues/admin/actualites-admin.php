<?php
$adminTitle = 'Gestion des Actualités - Admin FABLAB';
$adminCss = ['admin-actualites.css', 'pagination.css'];
$adminScripts = ['js/ajax-helper.js', 'js/toast-notification.js', 'js/admin-actualites.js'];

require __DIR__ . '/../parties/admin-layout-start.php';
?>

<section class="dashboard actualites-admin" data-admin-actualites>
  <h1>Gestion des Actualités</h1>

  <div class="table-container">
    <div class="table-header">
      <h2 class="table-title">Actualités Technologiques</h2>
      <?php
        $filtrageActif = trim((string)($recherche ?? '')) !== '' || trim((string)($source ?? '')) !== '';
        $compteurEntete = $filtrageActif ? (int)($total ?? 0) : (int)($totalGlobal ?? 0);
        $libelleEntete = $filtrageActif ? 'résultat(s)' : 'actualite(s)';
      ?>
      <div class="table-actions actualites-table-actions">
        <button type="button" id="btnSynchroniser" class="btn btn-primary">
          <i class="fas fa-sync-alt"></i> Synchroniser Flux RSS
        </button>
        <button type="button" id="btnNettoyer" class="btn btn-warning">
          <i class="fas fa-broom"></i> Nettoyer (+30j)
        </button>
        <span class="stats-badge" data-actualites-compteur="<?= $compteurEntete ?>">
          <i class="fas fa-newspaper"></i>
          <span data-actualites-compteur-texte><?= $compteurEntete ?> <?= $libelleEntete ?></span>
        </span>
      </div>
    </div>

    <div class="table-search actualites-table-search">
      <div class="search-bar">
        <input
          type="text"
          id="champRecherche"
          value="<?= htmlspecialchars((string)($recherche ?? ''), ENT_QUOTES, 'UTF-8') ?>"
          placeholder="Rechercher une actualité..."
          autocomplete="off">
      </div>
      <div class="actualites-source-filter">
        <label for="filterSource">
          <i class="fas fa-globe"></i> Source
        </label>
        <select id="filterSource">
          <?php $sourceSelectionnee = (string)($source ?? ''); ?>
          <option value="">Toutes les sources</option>
          <?php foreach (($sources ?? []) as $itemSource): ?>
            <option
              value="<?= htmlspecialchars((string)$itemSource, ENT_QUOTES, 'UTF-8') ?>"
              <?= (string)$itemSource === $sourceSelectionnee ? 'selected' : '' ?>>
              <?= htmlspecialchars((string)$itemSource, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <?php if (empty($actualites)): ?>
      <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <?php if (!empty($recherche) || !empty($source)): ?>
          <p>Aucun résultat pour les filtres actuels.</p>
        <?php else: ?>
          <p>Aucune actualité. Utilisez la synchronisation RSS pour importer des elements.</p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="users-table">
        <table id="tableauActualites">
          <thead>
            <tr>
              <th class="col-small">Image</th>
              <th class="col-large">Titre</th>
              <th class="col-medium">Description</th>
              <th class="col-medium">Source</th>
              <th class="col-date">Date publication</th>
              <th class="col-actions text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($actualites as $actualite): ?>
              <tr data-actualite-id="<?= (int)$actualite['id'] ?>">
                <td class="table-image-cell" data-label="Image" data-col="image">
                  <?php if (!empty($actualite['image_url'])): ?>
                    <div class="image-container">
                      <img
                        src="<?= htmlspecialchars((string)$actualite['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars((string)$actualite['titre'], ENT_QUOTES, 'UTF-8') ?>"
                        class="article-thumb js-image-fallback">
                      <div class="no-image-fallback">
                        <i class="fas fa-link"></i>
                        <span>Image indisponible</span>
                      </div>
                    </div>
                  <?php else: ?>
                    <div class="modern-placeholder no-image">
                      <i class="fas fa-newspaper"></i>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="actualites-col-titre" data-label="Titre" data-col="titre">
                  <strong><?= htmlspecialchars((string)$actualite['titre'], ENT_QUOTES, 'UTF-8') ?></strong>
                </td>
                <td class="actualites-col-description" data-label="Description" data-col="description">
                  <?php
                  $description = (string)($actualite['description'] ?? '');
                  $extrait = function_exists('mb_substr')
                      ? mb_substr($description, 0, 80, 'UTF-8')
                      : substr($description, 0, 80);
                  echo htmlspecialchars($extrait, ENT_QUOTES, 'UTF-8');
                  echo '...';
                  ?>
                </td>
                <td class="actualites-col-source" data-label="Source" data-col="source">
                  <span class="badge-categorie"><?= htmlspecialchars((string)($actualite['source'] ?? 'Inconnu'), ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td data-label="Date publication" data-col="date">
                  <?= date('d/m/Y H:i', strtotime((string)$actualite['published_at'])) ?>
                </td>
                <td class="text-center" data-label="Actions" data-col="actions">
                  <div class="admin-action-inline">
                    <a
                      href="?page=actualite-detail&id=<?= (int)$actualite['id'] ?>"
                      target="_blank"
                      class="btn btn-primary btn-sm"
                      title="Voir">
                      <i class="fas fa-eye"></i>
                    </a>
                    <?php if (!empty($actualite['url_source'])): ?>
                      <a
                        href="<?= htmlspecialchars((string)$actualite['url_source'], ENT_QUOTES, 'UTF-8') ?>"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-primary btn-sm"
                        title="Source originale">
                        <i class="fas fa-external-link-alt"></i>
                      </a>
                    <?php endif; ?>
                    <button
                      type="button"
                      class="btn btn-danger btn-sm"
                      data-actualite-delete-id="<?= (int)$actualite['id'] ?>"
                      data-actualite-delete-title="<?= htmlspecialchars((string)$actualite['titre'], ENT_QUOTES, 'UTF-8') ?>"
                      title="Supprimer">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <?php if (($pagination ?? null) instanceof Pagination && $pagination->total() > 0): ?>
    <div class="admin-pagination actualites-pagination">
      <?php require __DIR__ . '/../parties/pagination.php'; ?>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../parties/admin-layout-end.php'; ?>


