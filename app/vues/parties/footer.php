<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
?>
  <footer class="site-footer">
    <div class="site-footer-container">

      <div class="footer-social-icons">
        <a href="https://www.ajc-formation.fr" target="_blank" rel="noopener" aria-label="Site AJC Formation">
          <img src="<?= $GLOBALS['baseUrl'] ?>images/assets/AJC.png" alt="AJC Formation" class="footer-social-icon">
        </a>
        <a href="https://www.facebook.com/AjcFormation/?locale=fr_FR" target="_blank" rel="noopener" aria-label="Facebook AJC">
          <img src="<?= $GLOBALS['baseUrl'] ?>images/assets/fablab_facebook.png" alt="Page Facebook" class="footer-social-icon">
        </a>
        <a href="https://fr.linkedin.com/company/ajc-formation" target="_blank" rel="noopener" aria-label="LinkedIn AJC">
          <img src="<?= $GLOBALS['baseUrl'] ?>images/assets/LinkedinAJC.png" alt="Page LinkedIn" class="footer-social-icon">
        </a>
      </div>

      <nav class="footer-nav-links" aria-label="Navigation secondaire">
        <ul>
          <li><a href="?page=accueil">Accueil</a></li>
          <li><a href="?page=articles">Articles</a></li>
          <li><a href="?page=actualites">Actualités</a></li>
          <li><a href="?page=projets">Projets</a></li>
          <li><a href="?page=webtv">WebTV</a></li>
          <li><a href="?page=contact">Contact</a></li>
        </ul>
      </nav>
    </div>

    <div class="site-footer-bottom">
      <p>&copy; <?= date('Y') ?> AJC Formation — Tous droits réservés.</p>
    </div>
  </footer>
