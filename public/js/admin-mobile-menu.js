/**
 * ============================================
 * ADMIN MOBILE MENU - admin-mobile-menu.js
 * Gestion du menu hamburger mobile pour l'admin
 * ============================================
 */

document.addEventListener('DOMContentLoaded', function() {
  // Créer le bouton menu hamburger s'il n'existe pas
  if (!document.querySelector('.menu-toggle')) {
    const menuToggle = document.createElement('button');
    menuToggle.className = 'menu-toggle';
    menuToggle.setAttribute('aria-label', 'Toggle menu');
    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.insertBefore(menuToggle, document.body.firstChild);
  }

  // Créer l'overlay s'il n'existe pas
  if (!document.querySelector('.sidebar-overlay')) {
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.insertBefore(overlay, document.body.firstChild);
  }

  const menuToggle = document.querySelector('.menu-toggle');
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');

  if (!menuToggle || !sidebar || !overlay) {
    console.warn('Elements du menu mobile non trouvés');
    return;
  }

  // Fonction pour ouvrir le menu
  function ouvrirMenu() {
    sidebar.classList.add('open');
    overlay.classList.add('active');
    menuToggle.innerHTML = '<i class="fas fa-times"></i>';
    document.body.style.overflow = 'hidden'; // Empêcher le scroll
  }

  // Fonction pour fermer le menu
  function fermerMenu() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.style.overflow = ''; // Restaurer le scroll
  }

  // Toggle au clic sur le bouton hamburger
  menuToggle.addEventListener('click', function(e) {
    e.stopPropagation();
    if (sidebar.classList.contains('open')) {
      fermerMenu();
    } else {
      ouvrirMenu();
    }
  });

  // Fermer au clic sur l'overlay
  overlay.addEventListener('click', fermerMenu);

  // Fermer au clic sur un lien de la sidebar (navigation)
  const sidebarLinks = sidebar.querySelectorAll('.sidebar-nav a');
  sidebarLinks.forEach(link => {
    link.addEventListener('click', function() {
      // Petit délai pour permettre la navigation
      setTimeout(fermerMenu, 150);
    });
  });

  // Fermer avec la touche Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && sidebar.classList.contains('open')) {
      fermerMenu();
    }
  });

  // Gérer le redimensionnement de la fenêtre
  let resizeTimer;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
      // Si on passe en mode desktop (>768px), fermer le menu
      if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
        fermerMenu();
      }
    }, 250);
  });
});
