(() => {
  function notifier(type, message) {
    const texte = String(message || "").trim();
    if (texte === "") {
      return;
    }

    if (typeof PublicNotification !== "undefined") {
      if (type === "success") {
        PublicNotification.succes(texte);
      } else {
        PublicNotification.erreur(texte);
      }
      return;
    }

    if (typeof ToastNotification !== "undefined") {
      if (type === "success") {
        ToastNotification.succes(texte);
      } else {
        ToastNotification.erreur(texte);
      }
      return;
    }

    if (type === "success") {
      console.log(texte);
    } else {
      console.error(texte);
    }
  }

  function echapperHtml(valeur) {
    return String(valeur || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function obtenirTokenCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
      return String(meta.getAttribute("content") || "");
    }

    const input = document.querySelector('input[name="csrf_token"]');
    return input ? String(input.value || "") : "";
  }

  function construireUrlWebtvAjax() {
    const current = new URL(window.location.href);
    let path = current.pathname || "/";

    if (!/\.php$/i.test(path)) {
      path = path.replace(/\/?$/, "/") + "index.php";
    }

    const url = new URL(path, current.origin);
    url.searchParams.set("page", "webtv");
    url.searchParams.set("ajax", "1");

    const videoId = current.searchParams.get("video");
    if (videoId) {
      url.searchParams.set("video", videoId);
    }

    const videoUrl = current.searchParams.get("video_url");
    if (!videoId && videoUrl) {
      url.searchParams.set("video_url", videoUrl);
    }

    const q = current.searchParams.get("q");
    if (q) {
      url.searchParams.set("q", q);
    }

    const categorie = current.searchParams.get("categorie");
    if (categorie) {
      url.searchParams.set("categorie", categorie);
    }

    return url.toString();
  }

  function extraireDebut(valeur, longueur) {
    return Array.from(String(valeur || ""))
      .slice(0, Math.max(0, longueur))
      .join("");
  }

  function calculerInitiales(nom) {
    const morceaux = String(nom || "")
      .trim()
      .split(/\s+/)
      .filter((part) => part !== "");

    if (morceaux.length === 0) {
      return "US";
    }

    if (morceaux.length >= 2) {
      const premiere = extraireDebut(morceaux[0], 1);
      const derniere = extraireDebut(morceaux[morceaux.length - 1], 1);
      return String(`${premiere}${derniere}`).toLocaleUpperCase("fr-FR");
    }

    return extraireDebut(morceaux[0], 2).toLocaleUpperCase("fr-FR");
  }

  function formaterDateCommentaire(dateAffichee, dateRaw) {
    const bruteAffichee = String(dateAffichee || "").trim();
    if (bruteAffichee !== "") {
      return bruteAffichee;
    }

    const brute = String(dateRaw || "").trim();
    if (brute === "") {
      return "";
    }

    const date = new Date(brute.replace(" ", "T"));
    if (Number.isNaN(date.getTime())) {
      return brute;
    }

    return date.toLocaleString("fr-FR", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function mettreAJourCompteurCommentaires(total) {
    const titre = document.getElementById("webtv-comments-count");
    if (!titre) {
      return;
    }

    const nombre = Number.isFinite(total) ? Math.max(0, Number(total)) : 0;
    titre.textContent = `${nombre} commentaire${nombre > 1 ? "s" : ""}`;
  }

  function lireContexteCommentaires(commentsList) {
    return {
      videoId: Number.parseInt(String(commentsList.dataset.videoId || "0"), 10) || 0,
      returnQ: String(commentsList.dataset.returnQ || ""),
      returnCategorie: String(commentsList.dataset.returnCategorie || ""),
      canReply: String(commentsList.dataset.canReply || "0") === "1",
      csrfToken: String(commentsList.dataset.csrfToken || obtenirTokenCsrf() || ""),
    };
  }

  function construireAvatarHtml(commentaire, auteur) {
    const avatar =
      commentaire && typeof commentaire.avatar === "object" ? commentaire.avatar : null;
    const hasPhoto = Boolean(avatar?.has_photo && avatar?.photo_url);
    const photoUrl = String(avatar?.photo_url || "");
    const initiales = String(avatar?.initiales || calculerInitiales(auteur));
    const classeCouleur = String(avatar?.classe_couleur || "avatar-couleur-2");

    if (hasPhoto) {
      return `<img class="webtv-avatar-image" src="${echapperHtml(photoUrl)}" alt="Photo de profil de ${echapperHtml(auteur)}" loading="lazy">`;
    }

    return `<span class="webtv-avatar-fallback ${echapperHtml(classeCouleur)}">${echapperHtml(initiales)}</span>`;
  }

  function construireFormulaireReponse(commentId, contexte) {
    return `
      <form method="post" class="reply-form" data-reply-form="1" data-parent-id="${commentId}" hidden>
        <input type="hidden" name="action" value="reply_comment">
        <input type="hidden" name="parent_id" value="${commentId}">
        <input type="hidden" name="video_id" value="${contexte.videoId}">
        <input type="hidden" name="return_q" value="${echapperHtml(contexte.returnQ)}">
        <input type="hidden" name="return_categorie" value="${echapperHtml(contexte.returnCategorie)}">
        <input type="hidden" name="csrf_token" value="${echapperHtml(contexte.csrfToken)}">
        <textarea name="commentaire" rows="2" maxlength="1500" placeholder="Ecrivez votre reponse..." required></textarea>
        <div class="comment-actions">
          <button type="submit" class="btn-submit">Repondre</button>
          <button type="button" class="btn-cancel-reply" data-reply-cancel="1">Annuler</button>
        </div>
      </form>
    `;
  }

  function extraireAuteurParent(commentsList, parentId) {
    if (!Number.isFinite(parentId) || parentId <= 0) {
      return "";
    }

    const parentAuteurElement = commentsList.querySelector(
      `.comment-item[data-comment-id="${parentId}"] .comment-author`
    );
    if (!(parentAuteurElement instanceof HTMLElement)) {
      return "";
    }

    return String(parentAuteurElement.textContent || "").trim();
  }

  function creerElementCommentaire(commentaire, contexte, isReply = false) {
    const id = Number.parseInt(String(commentaire?.id || "0"), 10) || 0;
    const parentId = Number.parseInt(String(commentaire?.parent_id || "0"), 10) || 0;
    const auteur = String(commentaire?.auteur || "Utilisateur");
    const texte = String(commentaire?.texte || "");
    const dateAffichee = formaterDateCommentaire(
      commentaire?.date_affichee,
      commentaire?.created_at
    );
    const avatarHtml = construireAvatarHtml(commentaire, auteur);
    const parentAuteur = String(commentaire?.parent_auteur || "Utilisateur");
    const contexteReponseHtml = isReply
      ? `<div class="comment-reply-context">Reponse a ${echapperHtml(parentAuteur)}</div>`
      : "";

    const element = document.createElement("div");
    element.className = isReply ? "comment-item comment-reply-item" : "comment-item";
    element.setAttribute("data-comment-id", String(id));
    if (isReply && parentId > 0) {
      element.setAttribute("data-parent-id", String(parentId));
    }

    let outilsHtml = "";
    if (!isReply && contexte.canReply) {
      outilsHtml = `
        <div class="comment-tools">
          <button type="button" class="comment-reply-toggle" data-reply-toggle="1" data-comment-id="${id}">
            Repondre
          </button>
        </div>
        ${construireFormulaireReponse(id, contexte)}
        <div class="comment-replies" data-replies-for="${id}"></div>
      `;
    } else if (!isReply) {
      outilsHtml = `<div class="comment-replies" data-replies-for="${id}"></div>`;
    }

    element.innerHTML = `
      <div class="webtv-comment-avatar">
        ${avatarHtml}
      </div>
      <div class="comment-content">
        <div class="comment-header">
          <span class="comment-author">${echapperHtml(auteur)}</span>
          ${dateAffichee ? `<span class="comment-date">${echapperHtml(dateAffichee)}</span>` : ""}
        </div>
        ${contexteReponseHtml}
        <p class="comment-text">${echapperHtml(texte).replace(/\n/g, "<br>")}</p>
        ${outilsHtml}
      </div>
    `;

    return element;
  }

  function fermerTousFormulairesReponse(commentsList, saufFormulaire = null) {
    const formulaires = commentsList.querySelectorAll('[data-reply-form="1"]');
    formulaires.forEach((formulaire) => {
      if (!(formulaire instanceof HTMLFormElement)) {
        return;
      }
      if (saufFormulaire && formulaire === saufFormulaire) {
        return;
      }
      formulaire.hidden = true;
    });
  }

  function insererCommentaireDansListe(commentsList, commentaire, contexte) {
    const parentId = Number.parseInt(String(commentaire?.parent_id || "0"), 10) || 0;

    if (parentId > 0) {
      if (!commentaire.parent_auteur) {
        commentaire.parent_auteur = extraireAuteurParent(commentsList, parentId) || "Utilisateur";
      }

      let conteneurReponses = commentsList.querySelector(
        `.comment-replies[data-replies-for="${parentId}"]`
      );
      if (!conteneurReponses) {
        const parent = commentsList.querySelector(
          `.comment-item[data-comment-id="${parentId}"] .comment-content`
        );
        if (parent) {
          const nouveauConteneur = document.createElement("div");
          nouveauConteneur.className = "comment-replies";
          nouveauConteneur.setAttribute("data-replies-for", String(parentId));
          parent.appendChild(nouveauConteneur);
          conteneurReponses = nouveauConteneur;
        }
      }

      if (conteneurReponses) {
        conteneurReponses.appendChild(creerElementCommentaire(commentaire, contexte, true));
        return;
      }
    }

    const element = creerElementCommentaire(commentaire, contexte, false);
    const premierCommentaire = commentsList.querySelector(".comment-item:not(.comment-reply-item)");
    if (premierCommentaire) {
      commentsList.insertBefore(element, premierCommentaire);
    } else {
      commentsList.appendChild(element);
    }
  }

  function initialiserContexteReponsesExistantes(commentsList) {
    const reponses = commentsList.querySelectorAll(".comment-reply-item");
    reponses.forEach((reponse) => {
      if (!(reponse instanceof HTMLElement)) {
        return;
      }

      const parentId = Number.parseInt(String(reponse.dataset.parentId || "0"), 10) || 0;
      const auteurParent = extraireAuteurParent(commentsList, parentId) || "Utilisateur";
      const contenu = reponse.querySelector(".comment-content");
      if (!(contenu instanceof HTMLElement)) {
        return;
      }

      if (contenu.querySelector(".comment-reply-context")) {
        return;
      }

      const blocContexte = document.createElement("div");
      blocContexte.className = "comment-reply-context";
      blocContexte.textContent = `Reponse a ${auteurParent}`;

      const texte = contenu.querySelector(".comment-text");
      if (texte) {
        contenu.insertBefore(blocContexte, texte);
      } else {
        contenu.appendChild(blocContexte);
      }
    });
  }

  async function envoyerFormulaire(formulaire) {
    const formData = new FormData(formulaire);
    const token = obtenirTokenCsrf();
    if (token !== "" && !formData.has("csrf_token")) {
      formData.set("csrf_token", token);
    }

    const headers = { "X-Requested-With": "XMLHttpRequest" };
    if (token !== "") {
      headers["X-CSRF-Token"] = token;
    }

    const response = await fetch(construireUrlWebtvAjax(), {
      method: "POST",
      body: formData,
      headers,
      credentials: "same-origin",
    });

    let payload = null;
    try {
      payload = await response.clone().json();
    } catch (_error) {
      payload = null;
    }

    if (!payload) {
      throw new Error(`Reponse serveur invalide (${response.status}).`);
    }

    if (!response.ok || payload.success === false) {
      throw new Error(String(payload.message || "Erreur serveur."));
    }

    return payload;
  }

  async function traiterSoumissionCommentaire(formulaire, commentsList, contexte) {
    const bouton = formulaire.querySelector('button[type="submit"]');
    if (bouton instanceof HTMLButtonElement) {
      if (bouton.disabled) {
        return;
      }
      bouton.disabled = true;
    }

    try {
      const payload = await envoyerFormulaire(formulaire);
      const commentaire = payload?.data?.comment || null;
      if (!commentaire) {
        throw new Error("Commentaire recu invalide.");
      }

      const blocVide = commentsList.querySelector(".no-comments");
      if (blocVide) {
        blocVide.remove();
      }

      insererCommentaireDansListe(commentsList, commentaire, contexte);

      const textarea = formulaire.querySelector('textarea[name="commentaire"]');
      if (textarea instanceof HTMLTextAreaElement) {
        textarea.value = "";
      }

      if (String(formulaire.dataset.replyForm || "") === "1") {
        formulaire.hidden = true;
      }

      const total = Number(payload?.data?.total_comments);
      if (Number.isFinite(total)) {
        mettreAJourCompteurCommentaires(total);
      } else {
        mettreAJourCompteurCommentaires(commentsList.querySelectorAll(".comment-item").length);
      }

      notifier("success", payload.message || "Publication réussie.");
    } catch (error) {
      notifier("error", error instanceof Error ? error.message : "Erreur lors de la publication.");
    } finally {
      if (bouton instanceof HTMLButtonElement) {
        bouton.disabled = false;
      }
    }
  }

  function initialiserFormulaireAjoutPrincipal(formulaire, commentsList, contexte) {
    formulaire.addEventListener("submit", (event) => {
      event.preventDefault();
      traiterSoumissionCommentaire(formulaire, commentsList, contexte);
    });
  }

  function initialiserActionsReponses(commentsList, contexte) {
    commentsList.addEventListener("click", (event) => {
      const cible = event.target;
      if (!(cible instanceof Element)) {
        return;
      }

      const boutonToggle = cible.closest('[data-reply-toggle="1"]');
      if (boutonToggle instanceof HTMLElement) {
        const commentId = Number.parseInt(String(boutonToggle.dataset.commentId || "0"), 10) || 0;
        if (commentId <= 0) {
          return;
        }

        const formulaire = commentsList.querySelector(
          `[data-reply-form="1"][data-parent-id="${commentId}"]`
        );
        if (!(formulaire instanceof HTMLFormElement)) {
          return;
        }

        const doitAfficher = formulaire.hidden;
        fermerTousFormulairesReponse(commentsList, formulaire);
        formulaire.hidden = !doitAfficher;
        if (doitAfficher) {
          const textarea = formulaire.querySelector('textarea[name="commentaire"]');
          if (textarea instanceof HTMLTextAreaElement) {
            textarea.focus();
          }
        }
        return;
      }

      const boutonAnnuler = cible.closest('[data-reply-cancel="1"]');
      if (boutonAnnuler instanceof HTMLElement) {
        const formulaire = boutonAnnuler.closest('[data-reply-form="1"]');
        if (formulaire instanceof HTMLFormElement) {
          formulaire.hidden = true;
          const textarea = formulaire.querySelector('textarea[name="commentaire"]');
          if (textarea instanceof HTMLTextAreaElement) {
            textarea.value = "";
          }
        }
      }
    });

    commentsList.addEventListener("submit", (event) => {
      const cible = event.target;
      if (!(cible instanceof HTMLFormElement)) {
        return;
      }

      if (String(cible.dataset.replyForm || "") !== "1") {
        return;
      }

      event.preventDefault();
      traiterSoumissionCommentaire(cible, commentsList, contexte);
    });
  }

  function initialiser() {
    const commentsList = document.querySelector(".comments-list");
    if (!commentsList) {
      return;
    }

    const contexte = lireContexteCommentaires(commentsList);
    initialiserContexteReponsesExistantes(commentsList);

    const formAjout = document.querySelector(".comment-form");
    if (formAjout instanceof HTMLFormElement) {
      initialiserFormulaireAjoutPrincipal(formAjout, commentsList, contexte);
    }

    initialiserActionsReponses(commentsList, contexte);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initialiser);
  } else {
    initialiser();
  }
})();
