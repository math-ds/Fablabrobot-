(() => {
  function escapeHtml(value) {
    return String(value ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function formatNumber(value) {
    return new Intl.NumberFormat("fr-FR").format(Number(value || 0));
  }

  function formatDate(dateValue) {
    if (!dateValue) {
      return "";
    }

    const date = new Date(dateValue);
    if (Number.isNaN(date.getTime())) {
      return "";
    }

    return new Intl.DateTimeFormat("fr-FR").format(date);
  }

  function extractYoutubeId(url) {
    const patterns = [
      /(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/,
      /(?:https?:\/\/)?(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]{11})/,
      /(?:https?:\/\/)?(?:www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/,
      /(?:https?:\/\/)?(?:www\.)?youtube\.com\/v\/([a-zA-Z0-9_-]{11})/,
    ];

    for (const pattern of patterns) {
      const match = String(url || "").match(pattern);
      if (match) {
        return match[1];
      }
    }

    return null;
  }

  function buildVideoUrl(videoId, q, categorie) {
    const params = new URLSearchParams({ page: "webtv", video: String(videoId) });

    if (q) {
      params.set("q", q);
    }
    if (categorie) {
      params.set("categorie", categorie);
    }

    return `?${params.toString()}`;
  }

  function buildThumbnailUrl(video, baseUrl) {
    if (video.type === "youtube" && video.youtube_url) {
      const youtubeId = extractYoutubeId(video.youtube_url);
      if (youtubeId) {
        return `https://img.youtube.com/vi/${encodeURIComponent(youtubeId)}/hqdefault.jpg`;
      }
    }

    if (video.vignette) {
      if (/^https?:\/\//i.test(video.vignette)) {
        return video.vignette;
      }
      return `${baseUrl}uploads/vignettes/${encodeURIComponent(video.vignette)}`;
    }

    return null;
  }

  function buildResultsHtml(videos, q, categorie, baseUrl, canFavorite) {
    if (!videos.length) {
      return `
        <section class="catalog-empty-state">
          <div class="catalog-empty-state-icon" aria-hidden="true">
            <i class="fas fa-video"></i>
          </div>
          <p class="catalog-empty-title">Aucune video trouvee.</p>
          <p>Essaie un autre mot-clé ou une autre catégorie pour élargir les résultats.</p>
          <a href="?page=webtv" class="catalog-empty-state-action">Voir catalogue</a>
        </section>
      `;
    }

    const cards = videos
      .map((video) => {
        const thumbnailUrl = buildThumbnailUrl(video, baseUrl);
        const description = String(video.description || "").trim();
        const title = escapeHtml(video.titre || "Sans titre");
        const metaDate = formatDate(video.created_at);
        const videoUrl = buildVideoUrl(video.id, q, categorie);
        const views = `${formatNumber(video.vues)} vues`;
        const isFavori = Boolean(video.is_favori);
        const author = video.auteur_nom ? `<span>${escapeHtml(video.auteur_nom)}</span>` : "";
        const categoryBadge = video.categorie
          ? `<span class="webtv-catalog-badge">${escapeHtml(video.categorie)}</span>`
          : "";
        const dateBadge = metaDate
          ? `<span class="webtv-catalog-date">${escapeHtml(metaDate)}</span>`
          : "";
        const favoriteButton = canFavorite
          ? `
              <button
                type="button"
                class="favori-toggle ${isFavori ? "is-active" : ""}"
                data-favori-toggle
                data-favori-type="video"
                data-favori-id="${Number(video.id) || 0}"
                aria-pressed="${isFavori ? "true" : "false"}"
                title="${isFavori ? "Retirer des favoris" : "Ajouter aux favoris"}">
                <i class="${isFavori ? "fas" : "far"} fa-heart" aria-hidden="true"></i>
              </button>
            `
          : "";

        return `
          <a href="${escapeHtml(videoUrl)}" class="webtv-catalog-card">
            <div class="webtv-catalog-thumb">
              ${favoriteButton}
              ${
                thumbnailUrl
                  ? `<img src="${escapeHtml(thumbnailUrl)}" alt="${title}" loading="lazy">`
                  : '<div class="webtv-catalog-fallback">VIDEO</div>'
              }
              ${categoryBadge}
              ${dateBadge}
              <span class="webtv-catalog-play" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                  <polygon points="7 4 20 12 7 20 7 4"></polygon>
                </svg>
              </span>
            </div>
            <div class="webtv-catalog-body">
              <p class="webtv-catalog-title">${title}</p>
              <p>${escapeHtml(description !== "" ? description : "Cliquez pour ouvrir la page detail de cette video.")}</p>
              <div class="webtv-catalog-meta">
                <span>${escapeHtml(views)}</span>
                ${author}
              </div>
            </div>
          </a>
        `;
      })
      .join("");

    return `<section class="webtv-catalog-grid" aria-label="Liste des videos">${cards}</section>`;
  }

  function updateTags(tagsContainer, activeCategory) {
    if (!tagsContainer) {
      return;
    }

    const normalized = String(activeCategory || "");
    tagsContainer.querySelectorAll("a[data-categorie]").forEach((tag) => {
      const category = tag.getAttribute("data-categorie") || "";
      if (category === normalized) {
        tag.classList.add("is-active");
      } else {
        tag.classList.remove("is-active");
      }
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    const filterForm = document.getElementById("webtvFilterForm");
    if (!filterForm) {
      return;
    }
    if (typeof AjaxHelper === "undefined") {
      console.error("AjaxHelper est indisponible pour le filtrage WebTV.");
      return;
    }

    const shell = document.getElementById("webtvCatalogShell");
    const searchInput = document.getElementById("webtvSearchInput");
    const categorySelect = document.getElementById("webtvCategorySelect");
    const tagsContainer = document.getElementById("webtvFilterTags");
    const resultsContainer = document.getElementById("webtvCatalogResults");
    const resultsText = document.getElementById("webtvResultsText");
    const heroCount = document.getElementById("webtvHeroCount");
    const heroViews = document.getElementById("webtvHeroViews");
    const resetLink = filterForm.querySelector(".webtv-btn-ghost");
    const baseUrl = shell?.dataset.baseUrl || "/Fablabrobot/public/";
    const catalogContainer = document.getElementById("webtvCatalogShell");
    const resultsSection =
      resultsContainer?.closest(".webtv-catalog-shell") || shell || resultsContainer;

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

    function mettreAJourCompteurRecherche(visible, total, q, categorie) {
      if (!resultsText) {
        return;
      }

      const hasFilters = q !== "" || categorie !== "";
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

    const requestAndRender = async (pageCourante = 1, options = {}) => {
      const { scrollToTop = false } = options;
      const q = String(searchInput?.value || "").trim();
      const categorie = String(categorySelect?.value || "").trim();
      const params = new URLSearchParams({ page: "webtv", ajax: "1" });

      if (q) {
        params.set("q", q);
      }
      if (categorie) {
        params.set("categorie", categorie);
      }
      if (Number.isFinite(pageCourante) && pageCourante > 1) {
        params.set("p", String(pageCourante));
      }

      try {
        const response = await AjaxHelper.get(`?${params.toString()}`);

        if (!response?.success || !response?.data) {
          throw new Error("Invalid response payload");
        }

        const payload = response.data;
        const videos = Array.isArray(payload.videos) ? payload.videos : [];
        const activeQ = String(payload.q || q);
        const activeCategory = String(payload.categorie || categorie);
        const canFavorite = Boolean(payload.can_favorite);
        const count = Number(payload.count || videos.length || 0);
        const payloadPagination =
          payload.pagination && typeof payload.pagination === "object" ? payload.pagination : null;
        const totalCount = Number(
          payload.total_count || payloadPagination?.total || videos.length || 0
        );
        const totalViews = Number(payload.vues_totales || 0);
        const pageActuelle = Number.parseInt(
          String(payloadPagination?.page_courante ?? pageCourante ?? 1),
          10
        );
        const totalFiltres = Number(payloadPagination?.total || videos.length || 0);

        if (resultsContainer) {
          resultsContainer.innerHTML = buildResultsHtml(
            videos,
            activeQ,
            activeCategory,
            baseUrl,
            canFavorite
          );
        }
        mettreAJourCompteurRecherche(count, totalFiltres, activeQ, activeCategory);

        if (heroCount) {
          heroCount.textContent = formatNumber(totalCount);
        }
        if (heroViews) {
          heroViews.textContent = formatNumber(totalViews);
        }

        if (categorySelect) {
          categorySelect.value = activeCategory;
        }
        updateTags(tagsContainer, activeCategory);

        if (typeof payload.pagination_html === "string") {
          const paginationHtml = payload.pagination_html.trim();
          const paginationNav = document.querySelector(".pagination-nav");

          if (paginationHtml === "") {
            if (paginationNav) {
              paginationNav.remove();
            }
          } else if (paginationNav) {
            paginationNav.outerHTML = paginationHtml;
          } else if (catalogContainer) {
            catalogContainer.insertAdjacentHTML("afterend", paginationHtml);
          }

          installerPaginationAjax();
        }

        const urlParams = new URLSearchParams({ page: "webtv" });
        if (activeQ) urlParams.set("q", activeQ);
        if (activeCategory) urlParams.set("categorie", activeCategory);
        if (Number.isFinite(pageActuelle) && pageActuelle > 1) {
          urlParams.set("p", String(pageActuelle));
        }
        window.history.replaceState({}, "", `?${urlParams.toString()}`);

        if (scrollToTop) {
          window.requestAnimationFrame(() => {
            remonterVersResultats();
          });
        }
      } catch (error) {
        console.error("Erreur de filtrage WebTV:", error);
        if (typeof PublicNotification !== "undefined") {
          PublicNotification.erreur("Erreur lors du filtrage des videos.");
        } else if (typeof ToastNotification !== "undefined") {
          ToastNotification.erreur("Erreur lors du filtrage des videos.");
        }
      }
    };

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

    filterForm.addEventListener("submit", (event) => {
      event.preventDefault();
      requestAndRender(1, { scrollToTop: true });
    });

    let debounceTimer = null;
    const debouncedFilter = () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => requestAndRender(1), 250);
    };

    if (searchInput) {
      searchInput.addEventListener("input", debouncedFilter);
    }

    if (categorySelect) {
      categorySelect.addEventListener("change", () => requestAndRender(1, { scrollToTop: true }));
    }

    if (resetLink) {
      resetLink.addEventListener("click", (event) => {
        event.preventDefault();
        if (searchInput) {
          searchInput.value = "";
        }
        if (categorySelect) {
          categorySelect.value = "";
        }
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
        const selectedCategory = target.getAttribute("data-categorie") || "";

        if (categorySelect) {
          categorySelect.value = selectedCategory;
        }

        requestAndRender(1, { scrollToTop: true });
      });
    }

    window.addEventListener("catalog:webtv-created", () => {
      requestAndRender(1);
    });

    installerPaginationAjax();
  });
})();
