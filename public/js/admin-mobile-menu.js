document.addEventListener("DOMContentLoaded", function () {
  if (!document.querySelector(".sidebar-overlay")) {
    const overlay = document.createElement("div");
    overlay.className = "sidebar-overlay";
    document.body.insertBefore(overlay, document.body.firstChild);
  }

  const menuToggle = document.querySelector(".admin-topbar-toggle");
  const sidebar = document.querySelector(".sidebar");
  const overlay = document.querySelector(".sidebar-overlay");

  if (!menuToggle || !sidebar || !overlay) {
    return;
  }

  function ouvrirMenu() {
    sidebar.classList.add("open");
    overlay.classList.add("active");
    menuToggle.innerHTML = '<i class="fas fa-times"></i>';
    document.body.style.overflow = "hidden";
  }

  function fermerMenu() {
    sidebar.classList.remove("open");
    overlay.classList.remove("active");
    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.style.overflow = "";
  }

  menuToggle.addEventListener("click", function (event) {
    event.stopPropagation();
    if (sidebar.classList.contains("open")) {
      fermerMenu();
    } else {
      ouvrirMenu();
    }
  });

  overlay.addEventListener("click", fermerMenu);

  sidebar.querySelectorAll(".sidebar-nav a").forEach((link) => {
    link.addEventListener("click", function () {
      setTimeout(fermerMenu, 150);
    });
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && sidebar.classList.contains("open")) {
      fermerMenu();
    }
  });

  let timerResize;
  window.addEventListener("resize", function () {
    clearTimeout(timerResize);
    timerResize = setTimeout(function () {
      if (window.innerWidth >= 1100 && sidebar.classList.contains("open")) {
        fermerMenu();
      }
    }, 250);
  });
});
