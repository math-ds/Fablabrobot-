<?php

$GLOBALS['baseUrl'] = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';

function extractYoutubeId(string $url): ?string {
    $patterns = [
        '/(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
        '/(?:https?:\/\/)?(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]{11})/',
        '/(?:https?:\/\/)?(?:www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/'
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

require __DIR__ . '/../parties/header.php';


$current = $current ?? null;
$videos = $videos ?? [];
$commentaires = $commentaires ?? [];
$baseUrl = $GLOBALS['baseUrl'];
?>

<link rel="stylesheet" href="<?= $baseUrl ?>css/webtv.css" />

<?php if (!empty($_SESSION['message'])): ?>
  <div class="alert-message alert-<?= htmlspecialchars($_SESSION['message_type'] ?? 'info') ?>">
    <?= htmlspecialchars($_SESSION['message']) ?>
  </div>
  <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
<?php endif; ?>


<section class="hero-section">
  <div class="hero-overlay"></div>
  <div class="hero-particles"></div>
  <div class="container">
    <div class="hero-content text-center">
      <div class="hero-icon">
        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polygon points="5 3 19 12 5 21 5 3"></polygon>
        </svg>
      </div>
      <h1 class="hero-title">AJC FABLAB WebTV</h1>
      <p class="hero-subtitle">
        Découvrez nos tutoriels, projets et innovations en fabrication digitale
      </p>
      <div class="hero-stats">
        <div class="stat-item">
          <span class="stat-number"><?= count($videos) ?></span>
          <span class="stat-label">Vidéos</span>
        </div>
        <div class="stat-divider"></div>
        <div class="stat-item">
          <span class="stat-number"><?= number_format(array_sum(array_column($videos, 'vues'))) ?></span>
          <span class="stat-label">Vues totales</span>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="webtv-container">
  
  
  <div class="main-section">
    
    <div class="video-player-wrapper">
      <div class="video-player">
        <?php if ($current && !empty($current['type']) && $current['type'] === 'youtube' && !empty($current['youtube_url'])): ?>
          <?php $currentYoutubeId = extractYoutubeId($current['youtube_url']); ?>
          <iframe
            src="https://www.youtube.com/embed/<?= htmlspecialchars($currentYoutubeId) ?>?autoplay=1"
            title="<?= htmlspecialchars($current['titre'] ?? 'Vidéo') ?>"
            allowfullscreen
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
          </iframe>
        <?php elseif ($current && !empty($current['type']) && $current['type'] === 'local' && !empty($current['fichier'])): ?>
          <video controls>
            <source src="<?= $GLOBALS['baseUrl'] ?>uploads/videos/<?= htmlspecialchars($current['fichier']) ?>" type="video/mp4">
            Votre navigateur ne supporte pas la lecture vidéo.
          </video>
        <?php else: ?>
          <div class="no-video">
            <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="2" y="2" width="20" height="20" rx="2.18"></rect>
              <line x1="7" y1="2" x2="7" y2="22"></line>
              <line x1="17" y1="2" x2="17" y2="22"></line>
              <line x1="2" y1="12" x2="22" y2="12"></line>
              <line x1="2" y1="7" x2="7" y2="7"></line>
              <line x1="2" y1="17" x2="7" y2="17"></line>
              <line x1="17" y1="17" x2="22" y2="17"></line>
              <line x1="17" y1="7" x2="22" y2="7"></line>
            </svg>
            <p>Aucune vidéo disponible</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="video-info-card">
      <div class="video-info">
        <h1><?= htmlspecialchars($current['titre'] ?? 'Titre indisponible') ?></h1>
        <div class="video-meta">
          <div class="meta-item">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
            <span><?= number_format((int)($current['vues'] ?? 0)) ?> vues</span>
          </div>
          <?php if (!empty($current['auteur'])): ?>
            <div class="meta-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              <span><?= htmlspecialchars($current['auteur']) ?></span>
            </div>
          <?php endif; ?>
          <?php if (!empty($current['created_at'])): ?>
            <div class="meta-item">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
              <span><?= date('d/m/Y', strtotime($current['created_at'])) ?></span>
            </div>
          <?php endif; ?>
        </div>
        <?php if (!empty($current['description'])): ?>
          <div class="video-description">
            <p><?= nl2br(htmlspecialchars($current['description'])) ?></p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    
    <div class="comments-section">
      <div class="comments-header">
        <h2>
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
          <?= count($commentaires) ?> Commentaire<?= count($commentaires) > 1 ? 's' : '' ?>
        </h2>
      </div>

      <?php if (!empty($_SESSION['utilisateur_nom'])): ?>
        <form method="post" class="comment-form">
          <input type="hidden" name="action" value="add_comment">
          <div class="comment-input-wrapper">
            <div class="user-avatar">
              <?= strtoupper(substr($_SESSION['utilisateur_nom'], 0, 1)) ?>
            </div>
            <textarea 
              name="commentaire" 
              placeholder="Partagez votre avis sur cette vidéo..." 
              rows="3" 
              required
            ></textarea>
          </div>
          <div class="comment-actions">
            <button type="submit" class="btn-submit">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
              </svg>
              Publier
            </button>
          </div>
        </form>
      <?php else: ?>
        <div class="login-prompt">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
            <polyline points="10 17 15 12 10 7"></polyline>
            <line x1="15" y1="12" x2="3" y2="12"></line>
          </svg>
          <p>
            <a href="?page=login">Connectez-vous</a> ou 
            <a href="?page=inscription">inscrivez-vous</a> pour commenter
          </p>
        </div>
      <?php endif; ?>

      <div class="comments-list">
        <?php if (!empty($commentaires)): ?>
          <?php foreach ($commentaires as $c): ?>
            <div class="comment-item">
              <div class="comment-avatar">
                <?= strtoupper(substr($c['auteur'] ?? 'U', 0, 1)) ?>
              </div>
              <div class="comment-content">
                <div class="comment-header">
                  <span class="comment-author"><?= htmlspecialchars($c['auteur'] ?? 'Utilisateur') ?></span>
                  <?php if (!empty($c['created_at'])): ?>
                    <span class="comment-date"><?= date('d/m/Y à H:i', strtotime($c['created_at'])) ?></span>
                  <?php endif; ?>
                </div>
                <p class="comment-text"><?= nl2br(htmlspecialchars($c['texte'] ?? '')) ?></p>
                
                <?php if (!empty($_SESSION['utilisateur_role']) && strtolower($_SESSION['utilisateur_role']) === 'admin'): ?>
                  <a 
                    href="?page=webtv&video=<?= (int)($current['id'] ?? 0) ?>&del=<?= (int)($c['id'] ?? 0) ?>"
                    class="btn-delete"
                    onclick="return confirm('Supprimer ce commentaire ?')"
                  >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    Supprimer
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-comments">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <p>Aucun commentaire pour le moment</p>
            <span>Soyez le premier à partager votre avis !</span>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  
  <aside class="sidebar">
    <div class="sidebar-header">
      <h3>Vidéos suggérées</h3>
      <div class="header-accent"></div>
    </div>
    
    <div class="videos-grid">
      <?php if (!empty($videos)): ?>
        <?php foreach ($videos as $video): ?>
          <a href="?page=webtv&video=<?= (int)$video['id'] ?>" class="video-card">
            <div class="video-thumbnail">
              <?php if (!empty($video['type']) && $video['type'] === 'youtube' && !empty($video['youtube_url'])): ?>
                <?php $youtubeId = extractYoutubeId($video['youtube_url']); ?>
                <img src="https://img.youtube.com/vi/<?= htmlspecialchars($youtubeId) ?>/hqdefault.jpg"
                     alt="<?= htmlspecialchars($video['titre'] ?? 'Vidéo') ?>">
              <?php elseif (!empty($video['vignette'])): ?>
                <img src="<?= $GLOBALS['baseUrl'] ?>uploads/vignettes/<?= htmlspecialchars($video['vignette']) ?>" 
                     alt="<?= htmlspecialchars($video['titre'] ?? 'Vidéo') ?>">
              <?php else: ?>
                <img src="https://via.placeholder.com/320x180/232E59/FFFFFF?text=Vidéo" 
                     alt="<?= htmlspecialchars($video['titre'] ?? 'Vidéo') ?>">
              <?php endif; ?>
              <div class="play-overlay">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                  <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
              </div>
              <div class="video-duration">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14" stroke="white" stroke-width="2" fill="none"></polyline>
                </svg>
              </div>
            </div>
            
            <div class="video-card-info">
              <h4><?= htmlspecialchars($video['titre'] ?? 'Sans titre') ?></h4>
              <div class="video-card-meta">
                <span class="views">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                  <?= number_format((int)($video['vues'] ?? 0)) ?>
                </span>
                <?php if (!empty($video['auteur'])): ?>
                  <span class="author">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                      <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <?= htmlspecialchars($video['auteur']) ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-videos">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <polygon points="23 7 16 12 23 17 23 7"></polygon>
            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
          </svg>
          <p>Aucune vidéo disponible</p>
        </div>
      <?php endif; ?>
    </div>
  </aside>

</div>

<?php require __DIR__ . '/../parties/footer.php'; ?>
</body>
</html>