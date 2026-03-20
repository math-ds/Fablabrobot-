(() => {
  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function stripTags(value) {
    return String(value ?? "").replace(/<[^>]*>/g, "");
  }

  function excerpt(value, max = 120) {
    const clean = stripTags(value).trim();
    if (clean.length <= max) {
      return clean;
    }
    return `${clean.slice(0, max)}...`;
  }

  function formatDate(value) {
    if (!value) {
      return "";
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return "";
    }
    return new Intl.DateTimeFormat("fr-FR").format(date);
  }

  function buildImageUrl(imageUrl, baseUrl) {
    const source = String(imageUrl ?? "").trim();
    if (source === "") {
      return null;
    }
    if (/^https?:\/\//i.test(source)) {
      return source;
    }
    return `${baseUrl}${source.replace(/^\/+/, "")}`;
  }

  function renderArticleCard(article, baseUrl, canFavorite) {
    const articleId = Number(article.id) || 0;
    const image = buildImageUrl(article.image_url, baseUrl);
    const title = escapeHtml(article.titre || "Sans titre");
    const description = escapeHtml(excerpt(article.contenu || ""));
    const author = escapeHtml(article.auteur_nom || "Inconnu");
    const date = escapeHtml(formatDate(article.created_at));
    const href = `?page=article-detail&id=${encodeURIComponent(articleId)}`;
    const isFavori = Boolean(article.is_favori);
    const favoriteButton = canFavorite
      ? `
          <button
            type="button"
            class="favori-toggle ${isFavori ? "is-active" : ""}"
            data-favori-toggle
            data-favori-type="article"
            data-favori-id="${articleId}"
            aria-pressed="${isFavori ? "true" : "false"}"
            title="${isFavori ? "Retirer des favoris" : "Ajouter aux favoris"}">
            <i class="${isFavori ? "fas" : "far"} fa-heart" aria-hidden="true"></i>
          </button>
        `
      : "";

    return `
      <div class="project-card"
           data-categorie="${escapeHtml(article.categorie || "")}"
           data-article-open-modal="${articleId}"
           role="button"
           tabindex="0"
           aria-haspopup="dialog"
           aria-controls="modal-article-${articleId}"
           aria-label="Ouvrir les informations de l'article ${title}">
        <div class="project-image">
          ${favoriteButton}
          ${
            image
              ? `<img src="${escapeHtml(image)}"
                     alt="${title}"
                     loading="lazy"
                     class="js-fallback-next-image">
                 <i class="fas fa-code icone-fallback-cachee"></i>`
              : '<i class="fas fa-code"></i>'
          }
        </div>
        <div class="project-content">
          <p class="project-title">${title}</p>
          <p class="project-description">${description}</p>
          <div class="project-tags">
            <span class="tag">Auteur: ${author}</span>
            <span class="tag">${date}</span>
          </div>
          <div class="action-buttons">
            <a href="${href}" class="btn btn-primary js-stop-propagation">
              <i class="fas fa-book-open"></i> Lire l'article
            </a>
          </div>
        </div>
      </div>
    `;
  }

  function renderArticleModal(article, baseUrl, canFavorite) {
    const articleId = Number(article.id) || 0;
    const image = buildImageUrl(article.image_url, baseUrl);
    const title = escapeHtml(article.titre || "Sans titre");
    const author = escapeHtml(article.auteur_nom || "Inconnu");
    const date = escapeHtml(formatDate(article.created_at));
    const href = `?page=article-detail&id=${encodeURIComponent(articleId)}`;
    const isFavori = Boolean(article.is_favori);
    const favoriteButton = canFavorite
      ? `
          <button
            type="button"
            class="favori-toggle favori-toggle-detail ${isFavori ? "is-active" : ""}"
            data-favori-toggle
            data-favori-type="article"
            data-favori-id="${articleId}"
            aria-pressed="${isFavori ? "true" : "false"}"
            title="${isFavori ? "Retirer des favoris" : "Ajouter aux favoris"}">
            <i class="${isFavori ? "fas" : "far"} fa-heart" aria-hidden="true"></i>
            <span data-favori-label>${isFavori ? "Retirer des favoris" : "Ajouter aux favoris"}</span>
          </button>
        `
      : "";

    return `
      <div class="modal modal-detail modal-article modal-article-dynamic"
           id="modal-article-${articleId}"
           role="dialog"
           aria-modal="true"
           aria-hidden="true"
           aria-label="Détail article">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title">${title}</h2>
            <div class="modal-header-actions">
              ${favoriteButton}
              <button type="button" class="close-btn" data-article-close-modal="${articleId}" aria-label="Fermer la modale">&times;</button>
            </div>
          </div>

          <div class="modal-body">
            <div class="modal-layout">
              <div class="modal-image-section">
                <div class="modal-image">
                  ${
                    image
                      ? `<img src="${escapeHtml(image)}"
                             alt="${title}"
                             loading="lazy"
                             class="js-fallback-next-image">
                         <i class="fas fa-code icone-fallback-cachee"></i>`
                      : '<i class="fas fa-code"></i>'
                  }
                </div>
              </div>

              <div class="modal-content-section">
                <div class="modal-meta">
                  <div class="modal-meta-item">
                    <i class="fas fa-user"></i>
                    <span>${author}</span>
                  </div>
                  <div class="modal-meta-item">
                    <i class="fas fa-calendar"></i>
                    <span>${date}</span>
                  </div>
                </div>

                <div class="action-buttons">
                  <a href="${href}" class="btn btn-primary">
                    <i class="fas fa-eye"></i> Voir l'article en detail
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  function ensureArticleModals(articles, baseUrl, canFavorite) {
    document.querySelectorAll(".modal-article-dynamic").forEach((modal) => modal.remove());

    articles.forEach((article) => {
      const articleId = Number(article.id) || 0;
      if (!articleId) {
        return;
      }
      if (document.getElementById(`modal-article-${articleId}`)) {
        return;
      }

      document.body.insertAdjacentHTML(
        "beforeend",
        renderArticleModal(article, baseUrl, canFavorite)
      );
      const modal = document.getElementById(`modal-article-${articleId}`);
      if (modal) {
        initialiserFallbackImages(modal);
      }
    });
  }

  function renderArticles(articles, baseUrl, canFavorite) {
    if (!articles.length) {
      return `
        <section class="catalog-empty-state">
          <div class="catalog-empty-state-icon" aria-hidden="true">
            <i class="fas fa-newspaper"></i>
          </div>
          <p class="catalog-empty-title">Aucun article trouve.</p>
          <p>Essaie un autre mot-clé ou une autre catégorie pour élargir les résultats.</p>
          <a href="?page=articles" class="catalog-empty-state-action">Voir catalogue</a>
        </section>
      `;
    }

    return articles.map((article) => renderArticleCard(article, baseUrl, canFavorite)).join("");
  }

  function initialiserFallbackImages(root = document) {
    root.querySelectorAll("img.js-fallback-next-image").forEach((image) => {
      if (image.dataset.fallbackBound === "1") {
        return;
      }
      image.dataset.fallbackBound = "1";
      image.addEventListener("error", () => {
        image.style.display = "none";
        const next = image.nextElementSibling;
        if (next) {
          next.style.display = "flex";
        }
      });
    });
  }

  function renderCategories(select, categories, current) {
    if (!select) {
      return;
    }

    const options = ['<option value="all">Toutes les catégories</option>'];
    categories.forEach((categorie) => {
      const selected = categorie === current ? " selected" : "";
      options.push(
        `<option value="${escapeHtml(categorie)}"${selected}>${escapeHtml(categorie)}</option>`
      );
    });

    select.innerHTML = options.join("");
  }

  function buildFilterUrl(q, categorie) {
    const params = new URLSearchParams({ page: "articles" });
    if (String(q || "").trim() !== "") {
      params.set("q", String(q).trim());
    }
    if (String(categorie || "").trim() !== "" && categorie !== "all") {
      params.set("categorie", String(categorie).trim());
    }
    return `?${params.toString()}`;
  }

  function updateTags(tagsContainer, activeCategory) {
    if (!tagsContainer) {
      return;
    }

    const normalized = String(activeCategory || "all") || "all";
    tagsContainer.querySelectorAll("a[data-categorie]").forEach((tag) => {
      const category = tag.getAttribute("data-categorie") || "all";
      tag.classList.toggle("is-active", category === normalized);
    });
  }

  function renderCategoryTags(tagsContainer, categories, activeCategory, q) {
    if (!tagsContainer) {
      return;
    }

    const normalized = String(activeCategory || "all") || "all";
    const tags = [
      `<a href="${escapeHtml(buildFilterUrl(q, "all"))}" data-categorie="all" class="webtv-filter-tag${normalized === "all" ? " is-active" : ""}">Tout</a>`,
    ];

    categories.forEach((categorie) => {
      const value = String(categorie || "").trim();
      if (!value) {
        return;
      }
      tags.push(
        `<a href="${escapeHtml(buildFilterUrl(q, value))}" data-categorie="${escapeHtml(value)}" class="webtv-filter-tag${normalized === value ? " is-active" : ""}">${escapeHtml(value)}</a>`
      );
    });

    tagsContainer.innerHTML = tags.join("");
  }

  document.addEventListener("DOMContentLoaded", () => {
    const filterForm = document.getElementById("articlesFilterForm");
    const input = document.getElementById("searchInput");
    const select = document.getElementById("categoryFilter");
    const resetButton = document.getElementById("resetFilters");
    const tagsContainer = document.getElementById("articlesFilterTags");
    const grid = document.getElementById("articlesGrid");
    const resultsText = document.getElementById("articlesResultsText");

    if (!input || !select || !grid) {
      return;
    }
    if (typeof AjaxHelper === "undefined") {
      console.error("AjaxHelper indisponible pour les articles.");
      return;
    }

    const baseUrl = grid.dataset.baseUrl || "/Fablabrobot/public/";
    const resultsSection = grid.closest(".featured-section") || grid;
    initialiserFallbackImages(grid);

    function remonterVersResultats() {
      if (!resultsSection) {
        return;
      }
      const top = Math.max(
        Math.round(resultsSection.getBoundingClientRect().top + window.scrollY - 16),
        0
      );
      window.scrollTo({ top, behavior: "smooth" });
    }

    function lirePageDepuisLien(href) {
      try {
        const url = new URL(String(href || ""), window.location.href);
        const page = Number.parseInt(url.searchParams.get("p") || "1", 10);
        return Number.isFinite(page) && page > 0 ? page : 1;
      } catch (error) {
        return 1;
      }
    }

    function mettreAJourUrl(filtres, pageCourante = 1) {
      const p = new URLSearchParams({ page: "articles" });
      Object.entries(filtres).forEach(([key, value]) => {
        const v = String(value ?? "").trim();
        if (v !== "" && v !== "all") p.set(key, v);
      });
      if (Number.isFinite(pageCourante) && pageCourante > 1) {
        p.set("p", String(pageCourante));
      }
      window.history.replaceState({}, "", `?${p.toString()}`);
    }

    function mettreAJourCompteurRecherche(visible, total, q, categorie) {
      if (!resultsText) {
        return;
      }

      const hasFilters = q !== "" || (categorie !== "" && categorie !== "all");
      if (
        typeof RechercheHelper !== "undefined" &&
        typeof RechercheHelper.afficherCompteurInline === "function"
      ) {
        RechercheHelper.afficherCompteurInline(resultsText, visible, total, {
          unite: "résultat",
          hasFilters,
        });
        return;
      }

      const suffixVisible = visible > 1 ? "s" : "";
      const suffixTotal = total > 1 ? "s" : "";
      if (visible <= 0 || total <= 0) {
        resultsText.textContent = "Aucun résultat";
      } else if (hasFilters || total > visible) {
        resultsText.textContent = `${visible} résultat${suffixVisible} sur ${total} résultat${suffixTotal}`;
      } else {
        resultsText.textContent = `${visible} résultat${suffixVisible}`;
      }
    }

    async function requestAndRender(pageCourante = 1, options = {}) {
      const { scrollToTop = false } = options;
      const q = input.value.trim();
      const categorie = select.value;
      const params = new URLSearchParams({ page: "articles", ajax: "1" });

      if (q !== "") {
        params.set("q", q);
      }
      if (categorie !== "" && categorie !== "all") {
        params.set("categorie", categorie);
      }
      if (Number.isFinite(pageCourante) && pageCourante > 1) {
        params.set("p", String(pageCourante));
      }

      try {
        const response = await AjaxHelper.get(`?${params.toString()}`);
        if (!response?.success || !response?.data) {
          throw new Error("Reponse AJAX invalide");
        }

        const payload = response.data;
        const articles = Array.isArray(payload.articles) ? payload.articles : [];
        const categories = Array.isArray(payload.categories) ? payload.categories : [];
        const activeQ = String(payload.q || "");
        const activeCat = String(payload.categorie || "all") || "all";
        const canFavorite = Boolean(payload.can_favorite);
        const payloadPagination =
          payload.pagination && typeof payload.pagination === "object" ? payload.pagination : null;
        const pageActuelle = Number.parseInt(
          String(payloadPagination?.page_courante ?? pageCourante ?? 1),
          10
        );
        const totalFiltres = Number(payloadPagination?.total || articles.length || 0);

        grid.innerHTML = renderArticles(articles, baseUrl, canFavorite);
        initialiserFallbackImages(grid);
        ensureArticleModals(articles, baseUrl, canFavorite);
        renderCategories(select, categories, activeCat);
        renderCategoryTags(tagsContainer, categories, activeCat, activeQ);
        mettreAJourCompteurRecherche(articles.length, totalFiltres, activeQ, activeCat);

        if (typeof payload.pagination_html === "string") {
          const paginationHtml = payload.pagination_html.trim();
          const paginationNav = document.querySelector(".pagination-nav");

          if (paginationHtml === "") {
            if (paginationNav) {
              paginationNav.remove();
            }
          } else if (paginationNav) {
            paginationNav.outerHTML = paginationHtml;
          } else {
            const featuredSection = grid.closest(".featured-section");
            if (featuredSection) {
              featuredSection.insertAdjacentHTML("afterend", paginationHtml);
            }
          }

          installerPaginationAjax();
        }

        mettreAJourUrl(
          { q: activeQ, categorie: activeCat },
          Number.isFinite(pageActuelle) && pageActuelle > 0 ? pageActuelle : 1
        );

        if (scrollToTop) {
          window.requestAnimationFrame(() => {
            remonterVersResultats();
          });
        }
      } catch (error) {
        console.error("Erreur filtrage articles:", error);
        if (typeof PublicNotification !== "undefined") {
          PublicNotification.erreur("Erreur lors du filtrage des articles.");
        } else if (typeof ToastNotification !== "undefined") {
          ToastNotification.erreur("Erreur lors du filtrage des articles.");
        }
      }
    }

    let paginationDelegationInstallee = false;
    function installerPaginationAjax() {
      if (paginationDelegationInstallee) {
        return;
      }
      paginationDelegationInstallee = true;

      document.addEventListener("click", (event) => {
        if (
          event.button !== 0 ||
          event.metaKey ||
          event.ctrlKey ||
          event.shiftKey ||
          event.altKey
        ) {
          return;
        }

        const link =
          event.target instanceof Element
            ? event.target.closest(".pagination-nav a.pagination-btn")
            : null;
        if (!(link instanceof HTMLAnchorElement)) {
          return;
        }

        event.preventDefault();
        requestAndRender(lirePageDepuisLien(link.getAttribute("href")), { scrollToTop: true });
      });
    }

    if (filterForm) {
      filterForm.addEventListener("submit", (event) => {
        event.preventDefault();
        requestAndRender(1, { scrollToTop: true });
      });
    }

    let debounceTimer = null;
    const debouncedFilter = () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => requestAndRender(1), 250);
    };

    input.addEventListener("input", debouncedFilter);
    select.addEventListener("change", () => requestAndRender(1));
    if (resetButton) {
      resetButton.addEventListener("click", () => {
        input.value = "";
        select.value = "all";
        requestAndRender(1, { scrollToTop: true });
      });
    }

    if (tagsContainer) {
      tagsContainer.addEventListener("click", (event) => {
        const target =
          event.target instanceof Element ? event.target.closest("a[data-categorie]") : null;

        if (!target) {
          return;
        }

        event.preventDefault();
        const selectedCategory = target.getAttribute("data-categorie") || "all";
        select.value = selectedCategory;
        updateTags(tagsContainer, selectedCategory);
        requestAndRender(1, { scrollToTop: true });
      });
    }

    updateTags(tagsContainer, select.value || "all");

    window.addEventListener("catalog:article-created", () => {
      requestAndRender(1);
    });

    installerPaginationAjax();
  });
})();
