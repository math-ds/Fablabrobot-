<?php
$GLOBALS['baseUrl'] = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';

require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/../../helpers/AvatarHelper.php';
require_once __DIR__ . '/../../helpers/CsrfHelper.php';

$titrePage = 'WebTV - Lecture - FABLAB';
$pageCss = ['listes-partagees.css', 'webtv.css'];

require __DIR__ . '/../parties/public-layout-start.php';

$current = $current ?? null;
$videos = $videos ?? [];
$suggestions = $suggestions ?? [];
$commentaires = $commentaires ?? [];
$baseUrl = $GLOBALS['baseUrl'];
$q = trim((string)($_GET['q'] ?? ''));
$categorieCourante = trim((string)($_GET['categorie'] ?? ''));
$retourCatalogueUrl = webtvBuildUrl(['q' => $q, 'categorie' => $categorieCourante]);
$utilisateurConnecte = !empty($_SESSION['utilisateur_id']);

$avatarUtilisateurCourant = AvatarHelper::construireDonnees(
    $_SESSION['utilisateur_nom'] ?? '',
    $_SESSION['utilisateur_photo'] ?? null,
    $baseUrl
);

$commentairesPrincipaux = [];
$reponsesParParent = [];
foreach ($commentaires as $commentaireItem) {
    $parentId = isset($commentaireItem['parent_id']) && $commentaireItem['parent_id'] !== null
        ? (int)$commentaireItem['parent_id']
        : 0;
    if ($parentId > 0) {
        $reponsesParParent[$parentId][] = $commentaireItem;
    } else {
        $commentairesPrincipaux[] = $commentaireItem;
    }
}
?>

<section class="hero-section webtv-hero webtv-hero-detail">
  <div class="hero-content">
    <a href="<?= htmlspecialchars($retourCatalogueUrl) ?>" class="webtv-back-link">
      <span aria-hidden="true">&larr;</span>
      Retour au catalogue
    </a>
    <h1 class="hero-title"><?= htmlspecialchars($current['titre'] ?? 'Video') ?></h1>
    <p class="hero-subtitle">Page detail avec lecture et interactions.</p>
  </div>
</section>

<div class="webtv-detail-layout">
  <main class="webtv-detail-main">
    <section class="webtv-player-card">
      <div class="video-player">
        <?php if ($current && !empty($current['type']) && $current['type'] === 'youtube' && !empty($current['youtube_url'])): ?>
          <?php $currentYoutubeId = webtvExtractYoutubeId((string)$current['youtube_url']); ?>
          <?php if ($currentYoutubeId): ?>
            <iframe
              src="https://www.youtube.com/embed/<?= htmlspecialchars($currentYoutubeId) ?>"
              title="<?= htmlspecialchars($current['titre'] ?? 'Video') ?>"
              allowfullscreen
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
            </iframe>
          <?php else: ?>
            <div class="no-video">
              <p>URL YouTube invalide.</p>
            </div>
          <?php endif; ?>
        <?php elseif ($current && !empty($current['type']) && $current['type'] === 'local' && !empty($current['fichier'])): ?>
          <video controls>
            <source src="<?= htmlspecialchars($GLOBALS['baseUrl'] . 'uploads/videos/' . (string)$current['fichier']) ?>" type="video/mp4">
            Votre navigateur ne supporte pas la lecture video.
          </video>
        <?php else: ?>
          <div class="no-video">
            <p>Video indisponible.</p>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="video-info-card">
      <div class="video-info">
        <h2><?= htmlspecialchars($current['titre'] ?? 'Titre indisponible') ?></h2>
        <div class="video-meta">
          <div class="meta-item">
            <span><?= number_format((int)($current['vues'] ?? 0)) ?> vues</span>
          </div>
          <?php
            $displayAuteurVideo = $current['auteur_nom'] ?? null;
          ?>
          <?php if ($displayAuteurVideo): ?>
            <div class="meta-item">
              <span><?= htmlspecialchars($displayAuteurVideo) ?></span>
            </div>
          <?php endif; ?>
          <?php if (!empty($current['created_at'])): ?>
            <div class="meta-item">
              <span><?= date('d/m/Y', strtotime((string)$current['created_at'])) ?></span>
            </div>
          <?php endif; ?>
          <?php if (!empty($current['categorie'])): ?>
            <div class="meta-item">
              <span><?= htmlspecialchars((string)$current['categorie']) ?></span>
            </div>
          <?php endif; ?>
          <?php if ($utilisateurConnecte): ?>
            <button
              type="button"
              class="favori-toggle favori-toggle-detail <?= !empty($current['is_favori']) ? 'is-active' : '' ?>"
              data-favori-toggle
              data-favori-type="video"
              data-favori-id="<?= (int)($current['id'] ?? 0); ?>"
              aria-pressed="<?= !empty($current['is_favori']) ? 'true' : 'false' ?>"
              title="<?= !empty($current['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
              <i class="<?= !empty($current['is_favori']) ? 'fas' : 'far' ?> fa-heart" aria-hidden="true"></i>
              <span data-favori-label><?= !empty($current['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?></span>
            </button>
          <?php endif; ?>
        </div>
        <?php if (!empty($current['description'])): ?>
          <div class="video-description">
            <p><?= nl2br(htmlspecialchars((string)$current['description'])) ?></p>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="comments-section">
      <div class="comments-header">
        <h2 id="webtv-comments-count"><?= count($commentaires) ?> commentaire<?= count($commentaires) > 1 ? 's' : '' ?></h2>
      </div>

      <?php if (!empty($_SESSION['utilisateur_nom'])): ?>
        <form method="post" class="comment-form">
          <input type="hidden" name="action" value="add_comment">
          <input type="hidden" name="video_id" value="<?= (int)($current['id'] ?? 0) ?>">
          <input type="hidden" name="return_q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="return_categorie" value="<?= htmlspecialchars($categorieCourante, ENT_QUOTES, 'UTF-8') ?>">
          <?= CsrfHelper::obtenirChampJeton(); ?>
          <div class="comment-input-wrapper">
            <div class="webtv-user-avatar">
              <?php if ($avatarUtilisateurCourant['has_photo']): ?>
                <img class="webtv-avatar-image" src="<?= htmlspecialchars($avatarUtilisateurCourant['photo_url']) ?>" alt="Votre photo de profil" loading="lazy">
              <?php else: ?>
                <span class="webtv-avatar-fallback <?= htmlspecialchars((string)($avatarUtilisateurCourant['classe_couleur'] ?? 'avatar-couleur-1'), ENT_QUOTES, 'UTF-8') ?>">
                  <?= htmlspecialchars($avatarUtilisateurCourant['initiales']) ?>
                </span>
              <?php endif; ?>
            </div>
            <textarea
              name="commentaire"
              placeholder="Partagez votre avis sur cette video..."
              rows="3"
              required
            ></textarea>
          </div>
          <div class="comment-actions">
            <button type="submit" class="btn-submit">Publier</button>
          </div>
        </form>
      <?php else: ?>
        <div class="login-prompt">
          <p>
            <a href="?page=login">Connectez-vous</a> ou
            <a href="?page=inscription">inscrivez-vous</a> pour commenter.
          </p>
        </div>
      <?php endif; ?>

      <div
        class="comments-list"
        data-video-id="<?= (int)($current['id'] ?? 0) ?>"
        data-return-q="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"
        data-return-categorie="<?= htmlspecialchars($categorieCourante, ENT_QUOTES, 'UTF-8') ?>"
        data-can-reply="<?= $utilisateurConnecte ? '1' : '0' ?>"
        data-csrf-token="<?= htmlspecialchars(CsrfHelper::obtenirJeton(), ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($commentairesPrincipaux)): ?>
          <?php foreach ($commentairesPrincipaux as $c): ?>
            <?php
              $commentaireId = (int)($c['id'] ?? 0);
              $avatarCommentaire = AvatarHelper::construireDonnees(
                  $c['auteur'] ?? 'Utilisateur',
                  $c['user_photo'] ?? null,
                  $baseUrl
              );
              $reponses = $reponsesParParent[$commentaireId] ?? [];
            ?>
            <div class="comment-item" data-comment-id="<?= $commentaireId ?>">
              <div class="webtv-comment-avatar">
                <?php if ($avatarCommentaire['has_photo']): ?>
                  <img class="webtv-avatar-image" src="<?= htmlspecialchars($avatarCommentaire['photo_url']) ?>" alt="Photo de profil de <?= htmlspecialchars($c['auteur'] ?? 'Utilisateur') ?>" loading="lazy">
                <?php else: ?>
                  <span class="webtv-avatar-fallback <?= htmlspecialchars((string)($avatarCommentaire['classe_couleur'] ?? 'avatar-couleur-1'), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($avatarCommentaire['initiales']) ?>
                  </span>
                <?php endif; ?>
              </div>
              <div class="comment-content">
                <div class="comment-header">
                  <span class="comment-author"><?= htmlspecialchars($c['auteur'] ?? 'Utilisateur') ?></span>
                  <?php if (!empty($c['created_at'])): ?>
                    <span class="comment-date"><?= date('d/m/Y H:i', strtotime((string)$c['created_at'])) ?></span>
                  <?php endif; ?>
                </div>
                <p class="comment-text"><?= nl2br(htmlspecialchars($c['texte'] ?? '')) ?></p>

                <?php if ($utilisateurConnecte): ?>
                  <div class="comment-tools">
                    <button
                      type="button"
                      class="comment-reply-toggle"
                      data-reply-toggle="1"
                      data-comment-id="<?= $commentaireId ?>">
                      Repondre
                    </button>
                  </div>

                  <form method="post" class="reply-form" data-reply-form="1" data-parent-id="<?= $commentaireId ?>" hidden>
                    <input type="hidden" name="action" value="reply_comment">
                    <input type="hidden" name="parent_id" value="<?= $commentaireId ?>">
                    <input type="hidden" name="video_id" value="<?= (int)($current['id'] ?? 0) ?>">
                    <input type="hidden" name="return_q" value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="return_categorie" value="<?= htmlspecialchars($categorieCourante, ENT_QUOTES, 'UTF-8') ?>">
                    <?= CsrfHelper::obtenirChampJeton(); ?>
                    <textarea
                      name="commentaire"
                      rows="2"
                      maxlength="1500"
                      placeholder="Écrivez votre réponse..."
                      required></textarea>
                    <div class="comment-actions">
                      <button type="submit" class="btn-submit">Repondre</button>
                      <button type="button" class="btn-cancel-reply" data-reply-cancel="1">Annuler</button>
                    </div>
                  </form>
                <?php endif; ?>

                <div class="comment-replies" data-replies-for="<?= $commentaireId ?>">
                  <?php foreach ($reponses as $reponse): ?>
                    <?php
                      $avatarReponse = AvatarHelper::construireDonnees(
                          $reponse['auteur'] ?? 'Utilisateur',
                          $reponse['user_photo'] ?? null,
                          $baseUrl
                      );
                    ?>
                    <div class="comment-item comment-reply-item" data-comment-id="<?= (int)($reponse['id'] ?? 0) ?>" data-parent-id="<?= $commentaireId ?>">
                      <div class="webtv-comment-avatar">
                        <?php if ($avatarReponse['has_photo']): ?>
                          <img class="webtv-avatar-image" src="<?= htmlspecialchars($avatarReponse['photo_url']) ?>" alt="Photo de profil de <?= htmlspecialchars($reponse['auteur'] ?? 'Utilisateur') ?>" loading="lazy">
                        <?php else: ?>
                          <span class="webtv-avatar-fallback <?= htmlspecialchars((string)($avatarReponse['classe_couleur'] ?? 'avatar-couleur-1'), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($avatarReponse['initiales']) ?>
                          </span>
                        <?php endif; ?>
                      </div>
                      <div class="comment-content">
                        <div class="comment-header">
                          <span class="comment-author"><?= htmlspecialchars($reponse['auteur'] ?? 'Utilisateur') ?></span>
                          <?php if (!empty($reponse['created_at'])): ?>
                            <span class="comment-date"><?= date('d/m/Y H:i', strtotime((string)$reponse['created_at'])) ?></span>
                          <?php endif; ?>
                        </div>
                        <p class="comment-text"><?= nl2br(htmlspecialchars($reponse['texte'] ?? '')) ?></p>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-comments">
            <p>Aucun commentaire pour le moment.</p>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <aside class="webtv-detail-sidebar">
    <div class="sidebar-header">
      <h3>Videos suggerees</h3>
      <div class="header-accent"></div>
    </div>
    <div class="webtv-suggestion-list">
      <?php if (!empty($suggestions)): ?>
        <?php foreach ($suggestions as $video): ?>
          <?php
            $params = ['video' => (int)$video['id']];
            if ($q !== '') {
                $params['q'] = $q;
            }
            if ($categorieCourante !== '') {
                $params['categorie'] = $categorieCourante;
            }
            $thumbnailUrl = webtvThumbnailUrl($video, $baseUrl);
          ?>
          <a href="<?= htmlspecialchars(webtvBuildUrl($params)) ?>" class="webtv-suggestion-card">
            <div class="webtv-suggestion-thumb">
              <?php if ($thumbnailUrl): ?>
                <img src="<?= htmlspecialchars($thumbnailUrl) ?>" alt="<?= htmlspecialchars($video['titre'] ?? 'Video') ?>" loading="lazy">
              <?php else: ?>
                <div class="video-thumbnail-fallback">VIDEO</div>
              <?php endif; ?>
            </div>
            <div class="webtv-suggestion-info">
              <div class="webtv-suggestion-top">
                <h4><?= htmlspecialchars($video['titre'] ?? 'Sans titre') ?></h4>
                <?php if ($utilisateurConnecte): ?>
                  <button
                    type="button"
                    class="favori-toggle webtv-suggestion-favori <?= !empty($video['is_favori']) ? 'is-active' : '' ?>"
                    data-favori-toggle
                    data-favori-type="video"
                    data-favori-id="<?= (int)($video['id'] ?? 0); ?>"
                    aria-pressed="<?= !empty($video['is_favori']) ? 'true' : 'false' ?>"
                    title="<?= !empty($video['is_favori']) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                    <i class="<?= !empty($video['is_favori']) ? 'fas' : 'far' ?> fa-heart" aria-hidden="true"></i>
                  </button>
                <?php endif; ?>
              </div>
              <p><?= number_format((int)($video['vues'] ?? 0)) ?> vues</p>
            </div>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-videos">
          <p>Pas encore de suggestion.</p>
        </div>
      <?php endif; ?>
    </div>
  </aside>
</div>

<?php $publicScripts = ['js/webtv-detail.js']; ?>
<?php require __DIR__ . '/../parties/public-layout-end.php'; ?>
