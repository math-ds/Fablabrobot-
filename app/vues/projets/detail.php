<?php require __DIR__ . '/../parties/header.php'; ?>
<link rel="stylesheet" href="../public/css/details-projets.css">

<body>
<div class="container">
    <a href="?page=projets" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        Retour aux projets
    </a>

    <?php if ($projet): ?>
        <div class="project-detail">
            <div class="project-header">
                <div class="project-image">
                    <?php if (isset($projet['image_url']) && trim($projet['image_url']) !== ''): ?>
                        <?php
                        // Construire le bon chemin d'image
                        $imageSrc = '';
                        if (str_starts_with($projet['image_url'], 'http://') || str_starts_with($projet['image_url'], 'https://')) {
                            // URL externe
                            $imageSrc = $projet['image_url'];
                        } elseif (str_starts_with($projet['image_url'], 'images/')) {
                            // Chemin complet (images/projets/...)
                            $imageSrc = $projet['image_url'];
                        } else {
                            // Juste le nom du fichier - ajouter le préfixe
                            $imageSrc = 'images/projets/' . $projet['image_url'];
                        }
                        ?>
                        <img src="<?= htmlspecialchars($imageSrc); ?>"
                             alt="<?= htmlspecialchars($projet['title']); ?>"
                             onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <i class="fas fa-code" style="display:none;"></i>
                    <?php else: ?>
                        <i class="fas fa-code"></i>
                    <?php endif; ?>
                </div>

                <div class="project-header-content">
                    <h1 class="project-title"><?= htmlspecialchars($projet['title']); ?></h1>
                    <div class="project-meta">
                        <div class="meta-item">
                            <i class="fas fa-calendar"></i>
                            <span><?= (new DateTime($projet['created_at']))->format('d/m/Y'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="project-content">
                <div class="content-grid">
                    <div class="description-section">
                        <div class="section-block">
                            <h2><i class="fas fa-info-circle"></i> Description du projet</h2>
                            <p class="description-text"><?= nl2br(htmlspecialchars($projet['description'])); ?></p>
                        </div>

                        <?php if (isset($projet['description_detailed']) && trim($projet['description_detailed']) !== '' && $projet['description_detailed'] != $projet['description']): ?>
                            <div class="section-block">
                                <h3><i class="fas fa-align-left"></i> Description détaillée</h3>
                                <div class="detailed-description">
                                    <?= nl2br(htmlspecialchars($projet['description_detailed'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($projet['technologies']) && trim($projet['technologies']) !== ''): ?>
                            <div class="section-block">
                                <h3><i class="fas fa-microchip"></i> Technologies utilisées</h3>
                                <div class="tech-stack">
                                    <?php 
                                    $techs = explode(',', $projet['technologies']);
                                    foreach ($techs as $tech): ?>
                                        <span class="tech-tag"><?= htmlspecialchars(trim($tech)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="sidebar">
                        <h3><i class="fas fa-chart-bar"></i> Informations du projet</h3>
                        <div class="info-item">
                            <div class="info-label">Date de création</div>
                            <div class="info-value"><?= (new DateTime($projet['created_at']))->format('d/m/Y'); ?></div>
                        </div>

                        <?php if (isset($projet['updated_at']) && $projet['updated_at'] != $projet['created_at']): ?>
                            <div class="info-item">
                                <div class="info-label">Dernière mise à jour</div>
                                <div class="info-value"><?= (new DateTime($projet['updated_at']))->format('d/m/Y'); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (isset($projet['features']) && trim($projet['features']) !== ''): ?>
                    <div class="features-section">
                        <h2><i class="fas fa-cogs"></i> Fonctionnalités principales</h2>
                        <div class="features-grid">
                            <?php
                            $features = explode(',', $projet['features']);
                            foreach ($features as $feature):
                                $feature = trim($feature);
                                if ($feature !== ''): ?>
                                    <div class="feature-card">
                                        <i class="fas fa-check-circle"></i>
                                        <?= htmlspecialchars($feature); ?>
                                    </div>
                            <?php endif; endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($projet['challenges']) && trim($projet['challenges']) !== ''): ?>
                    <div class="challenges-section">
                        <h2><i class="fas fa-wrench"></i> Défis techniques rencontrés</h2>
                        <div class="challenges-content">
                            <?= nl2br(htmlspecialchars($projet['challenges'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="error-page">
            <div class="error-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h1 class="error-title">Projet introuvable</h1>
            <p class="error-text">Désolé, le projet que vous recherchez n'existe pas ou a été supprimé.</p>
            <a href="?page=projets" class="project-link">
                <i class="fas fa-arrow-left"></i> Retour aux projets
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../parties/footer.php'; ?>
</body>
</html>
