<?php

$GLOBALS['baseUrl'] = '/Fablabrobot/public/';


$host = 'localhost';
$dbname = 'fablab';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$article) $error = "Article introuvable.";
    } else {
        $error = "Article invalide.";
    }

} catch (PDOException $e) {
    $error = "Erreur de connexion : " . $e->getMessage();
}

include(__DIR__ . '/../parties/header.php');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($article) ? htmlspecialchars($article['titre']) : "Article" ?></title>

  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $GLOBALS['baseUrl'] ?>css/header.css">
  <link rel="stylesheet" href="../public/css/article.css">

  
</head>

<body>
<div class="container">

  <a href="?page=articles" class="back-btn">
    <i class="fas fa-arrow-left"></i> Retour aux articles
  </a>

  <?php if (isset($error)): ?>
      <div class="error-page">
        <div class="error-icon">⚠️</div>
        <h1 class="error-title">Erreur</h1>
        <p class="error-text"><?= htmlspecialchars($error) ?></p>
        <a href="?page=articles" class="back-btn">
          <i class="fas fa-arrow-left"></i> Retour aux articles
        </a>
      </div>

  <?php else: ?>
      
      <div class="project-detail">

        <!-- Image en pleine largeur -->
        <?php if (!empty($article['image_url'])): ?>
          <div style="margin-bottom: 2rem; width: 100%; overflow: hidden; border-radius: 12px;">
            <?php
            // Construire le bon chemin d'image
            $imageSrc = '';
            if (str_starts_with($article['image_url'], 'http://') || str_starts_with($article['image_url'], 'https://')) {
                // URL externe
                $imageSrc = $article['image_url'];
            } elseif (str_starts_with($article['image_url'], 'images/')) {
                // Chemin complet (images/articles/...)
                $imageSrc = $article['image_url'];
            } else {
                // Juste le nom du fichier - ajouter le préfixe
                $imageSrc = 'images/articles/' . $article['image_url'];
            }
            ?>
            <img src="<?= htmlspecialchars($imageSrc); ?>"
                 alt="<?= htmlspecialchars($article['titre']); ?>"
                 style="width: 100%; height: auto; max-height: 500px; object-fit: cover; display: block;">
          </div>
        <?php endif; ?>

        <!-- Titre principal -->
        <h1 class="project-title" style="margin-bottom: 2rem;"><?= htmlspecialchars($article['titre']) ?></h1>

        <div class="project-content">
          <div class="content-grid">


            <div class="main-content">
              <div class="description-section">
                <h2>📖 Contenu de l'article</h2>
                <div class="description-text">
                  <?= nl2br(htmlspecialchars($article['contenu'])) ?>
                </div>
              </div>

              <?php if (!empty($article['resume']) || !empty($article['tags'])): ?>
              <div class="features-section">
                <h2>ℹ️ Informations complémentaires</h2>
                <div class="features-grid">
                  
                  <?php if (!empty($article['resume'])): ?>
                  <div class="feature-card">
                    <h4>📝 Résumé</h4>
                    <p><?= htmlspecialchars($article['resume']) ?></p>
                  </div>
                  <?php endif; ?>

                  <?php if (!empty($article['tags'])): ?>
                  <div class="feature-card">
                    <h4>🏷️ Tags</h4>
                    <div class="tech-stack">
                      <?php 
                      $tags = explode(',', $article['tags']);
                      foreach ($tags as $tag): 
                      ?>
                        <span class="tech-tag"><?= trim(htmlspecialchars($tag)) ?></span>
                      <?php endforeach; ?>
                    </div>
                  </div>
                  <?php endif; ?>

                </div>
              </div>
              <?php endif; ?>
            </div>


            <div class="sidebar" style="background: rgba(0, 175, 167, 0.05); border: 1px solid rgba(0, 175, 167, 0.3);">
              <h3 style="color: #00afa7; font-size: 1.3rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid rgba(0, 175, 167, 0.3);">
                <i class="fas fa-info-circle"></i> Informations
              </h3>

              <div style="margin-bottom: 1.2rem;">
                <div style="display: flex; align-items: center; gap: 10px; color: rgba(245, 245, 245, 0.6); font-size: 0.85rem; margin-bottom: 5px;">
                  <i class="fas fa-check-circle" style="color: #00afa7;"></i>
                  <span>Statut</span>
                </div>
                <div style="padding-left: 24px;">
                  <span style="background: rgba(0, 175, 167, 0.2); color: #00afa7; padding: 4px 12px; border-radius: 15px; font-size: 0.9rem; font-weight: 600;">
                    Publié
                  </span>
                </div>
              </div>

              <div style="margin-bottom: 1.2rem;">
                <div style="display: flex; align-items: center; gap: 10px; color: rgba(245, 245, 245, 0.6); font-size: 0.85rem; margin-bottom: 5px;">
                  <i class="fas fa-user" style="color: #00afa7;"></i>
                  <span>Auteur</span>
                </div>
                <div style="padding-left: 24px; color: #f5f5f5; font-weight: 500;">
                  <?= htmlspecialchars($article['auteur']) ?>
                </div>
              </div>

              <div style="margin-bottom: 1.2rem;">
                <div style="display: flex; align-items: center; gap: 10px; color: rgba(245, 245, 245, 0.6); font-size: 0.85rem; margin-bottom: 5px;">
                  <i class="fas fa-calendar-plus" style="color: #00afa7;"></i>
                  <span>Date de création</span>
                </div>
                <div style="padding-left: 24px; color: #f5f5f5; font-weight: 500;">
                  <?= date('d/m/Y', strtotime($article['created_at'])) ?>
                </div>
              </div>

              <?php if (!empty($article['updated_at']) && $article['updated_at'] != $article['created_at']): ?>
              <div style="margin-bottom: 1.2rem;">
                <div style="display: flex; align-items: center; gap: 10px; color: rgba(245, 245, 245, 0.6); font-size: 0.85rem; margin-bottom: 5px;">
                  <i class="fas fa-sync-alt" style="color: #00afa7;"></i>
                  <span>Dernière mise à jour</span>
                </div>
                <div style="padding-left: 24px; color: #f5f5f5; font-weight: 500;">
                  <?= date('d/m/Y', strtotime($article['updated_at'])) ?>
                </div>
              </div>
              <?php endif; ?>

              <?php if (!empty($article['categorie'])): ?>
              <div style="margin-bottom: 1.2rem;">
                <div style="display: flex; align-items: center; gap: 10px; color: rgba(245, 245, 245, 0.6); font-size: 0.85rem; margin-bottom: 5px;">
                  <i class="fas fa-tag" style="color: #00afa7;"></i>
                  <span>Catégorie</span>
                </div>
                <div style="padding-left: 24px; color: #f5f5f5; font-weight: 500;">
                  <?= htmlspecialchars($article['categorie']) ?>
                </div>
              </div>
              <?php endif; ?>

              <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(0, 175, 167, 0.2);">
                <a href="?page=articles" style="display: flex; align-items: center; justify-content: center; gap: 10px; background: #00afa7; color: white; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;">
                  <i class="fas fa-arrow-left"></i>
                  Tous les articles
                </a>
              </div>

            </div>
          </div>
        </div>
      </div>
  <?php endif; ?>
</div>

<?php include(__DIR__ . '/../parties/footer.php'); ?>
</body>
</html>
