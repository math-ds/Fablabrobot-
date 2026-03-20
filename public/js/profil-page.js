(() => {
  const classesCouleurAvatar = [
    "avatar-couleur-1",
    "avatar-couleur-2",
    "avatar-couleur-3",
    "avatar-couleur-4",
    "avatar-couleur-5",
    "avatar-couleur-6",
    "avatar-couleur-7",
    "avatar-couleur-8",
  ];

  const correspondanceCouleursAvatar = {
    "#ff6b6b": "avatar-couleur-1",
    "#4ecdc4": "avatar-couleur-2",
    "#45b7d1": "avatar-couleur-3",
    "#ffa07a": "avatar-couleur-4",
    "#98d8c8": "avatar-couleur-5",
    "#f7dc6f": "avatar-couleur-6",
    "#bb8fce": "avatar-couleur-7",
    "#85c1e2": "avatar-couleur-8",
  };

  function determinerClasseCouleurAvatar(classeCouleur, couleur) {
    const classe = String(classeCouleur || "").trim();
    if (/^avatar-couleur-[1-8]$/.test(classe)) {
      return classe;
    }

    const cleCouleur = String(couleur || "")
      .trim()
      .toLowerCase();
    return correspondanceCouleursAvatar[cleCouleur] || "avatar-couleur-2";
  }

  function appliquerClasseCouleurAvatar(element, classeCouleur, couleur) {
    if (!(element instanceof HTMLElement)) {
      return;
    }

    classesCouleurAvatar.forEach((classe) => element.classList.remove(classe));
    element.classList.add(determinerClasseCouleurAvatar(classeCouleur, couleur));
  }

  function remplacerPreview(element, id, alt, source) {
    if (!element) {
      return;
    }

    if (element.tagName === "IMG") {
      element.src = source;
      return;
    }

    const image = document.createElement("img");
    image.src = source;
    image.id = id;
    image.alt = alt;
    element.replaceWith(image);
  }

  function notifier(type, message) {
    const msg = String(message || "").trim();
    if (msg === "") {
      return;
    }

    if (typeof PublicNotification !== "undefined") {
      if (type === "success") {
        PublicNotification.succes(msg);
      } else {
        PublicNotification.erreur(msg);
      }
      return;
    }

    if (typeof ToastNotification !== "undefined") {
      if (type === "success") {
        ToastNotification.succes(msg);
      } else {
        ToastNotification.erreur(msg);
      }
      return;
    }

    if (type === "success") {
      console.log(msg);
    } else {
      console.error(msg);
    }
  }

  function obtenirTokenCsrf() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? String(meta.getAttribute("content") || "") : "";
  }

  function construireUrlProfilAjax() {
    const current = new URL(window.location.href);
    let path = current.pathname || "/";

    if (!/\.php$/i.test(path)) {
      path = path.replace(/\/?$/, "/") + "index.php";
    }

    const url = new URL(path, current.origin);
    url.searchParams.set("page", "profil");
    url.searchParams.set("ajax", "1");
    return url.toString();
  }

  function renderAvatarPlaceholder(id, initiales, classeCouleur, couleur) {
    const placeholder = document.createElement("div");
    placeholder.id = id;
    placeholder.className = "avatar-placeholder";
    appliquerClasseCouleurAvatar(placeholder, classeCouleur, couleur);
    placeholder.textContent = String(initiales || "US");
    return placeholder;
  }

  function obtenirNomUtilisateurActuel() {
    const profileName = document.querySelector(".profile-name");
    if (profileName) {
      const nom = String(profileName.textContent || "").trim();
      if (nom !== "") {
        return nom;
      }
    }

    const dropdownName = document.querySelector(".profil-dropdown-header .profil-nom");
    if (dropdownName) {
      const nom = String(dropdownName.textContent || "").trim();
      if (nom !== "") {
        return nom;
      }
    }

    return "Utilisateur";
  }

  function remplacerAvatarEnteteDesktop(hasPhoto, photoUrl, initiales, classeCouleur, couleur) {
    const trigger = document.getElementById("profilTrigger");
    if (!trigger) {
      return;
    }

    const zoneInfo = trigger.querySelector(".profil-info");
    const avatarImage = trigger.querySelector("img.user-avatar");
    const avatarPlaceholder = trigger.querySelector(".initials-avatar");

    if (hasPhoto) {
      if (avatarImage) {
        avatarImage.src = photoUrl;
      } else {
        const image = document.createElement("img");
        image.className = "user-avatar";
        image.alt = "Photo de profil";
        image.src = photoUrl;
        if (avatarPlaceholder) {
          avatarPlaceholder.replaceWith(image);
        } else {
          trigger.insertBefore(image, zoneInfo || trigger.firstChild);
        }
      }
      return;
    }

    if (avatarPlaceholder) {
      appliquerClasseCouleurAvatar(avatarPlaceholder, classeCouleur, couleur);
      avatarPlaceholder.textContent = initiales;
      return;
    }

    const placeholder = document.createElement("div");
    placeholder.className = "initials-avatar";
    appliquerClasseCouleurAvatar(placeholder, classeCouleur, couleur);
    placeholder.textContent = initiales;
    if (avatarImage) {
      avatarImage.replaceWith(placeholder);
    } else {
      trigger.insertBefore(placeholder, zoneInfo || trigger.firstChild);
    }
  }

  function remplacerAvatarEnteteMobile(hasPhoto, photoUrl, initiales, classeCouleur, couleur) {
    const profilMobile = document.querySelector(".profil-mobile");
    if (!profilMobile) {
      return;
    }

    const avatarImage = profilMobile.querySelector("img.user-avatar-mobile");
    const avatarPlaceholder = profilMobile.querySelector(".initials-avatar-mobile");
    const insertionReference = profilMobile.firstChild;

    if (hasPhoto) {
      if (avatarImage) {
        avatarImage.src = photoUrl;
      } else {
        const image = document.createElement("img");
        image.className = "user-avatar-mobile";
        image.alt = "Photo de profil";
        image.src = photoUrl;
        if (avatarPlaceholder) {
          avatarPlaceholder.replaceWith(image);
        } else {
          profilMobile.insertBefore(image, insertionReference);
        }
      }
      return;
    }

    if (avatarPlaceholder) {
      appliquerClasseCouleurAvatar(avatarPlaceholder, classeCouleur, couleur);
      avatarPlaceholder.textContent = initiales;
      return;
    }

    const placeholder = document.createElement("div");
    placeholder.className = "initials-avatar-mobile";
    appliquerClasseCouleurAvatar(placeholder, classeCouleur, couleur);
    placeholder.textContent = initiales;
    if (avatarImage) {
      avatarImage.replaceWith(placeholder);
    } else {
      profilMobile.insertBefore(placeholder, insertionReference);
    }
  }

  function mettreAJourNomEntete(nomComplet) {
    const nom = String(nomComplet || "").trim();
    if (nom === "") {
      return;
    }

    const prenom = nom.split(/\s+/)[0] || nom;

    const nomCourt = document.querySelector(".profil-info .profil-nom");
    if (nomCourt) {
      nomCourt.textContent = prenom;
    }

    const nomDropdown = document.querySelector(".profil-dropdown-header .profil-nom");
    if (nomDropdown) {
      nomDropdown.textContent = nom;
    }

    const nomMobile = document.querySelector(".profil-nom-mobile");
    if (nomMobile) {
      nomMobile.textContent = nom;
    }
  }

  function mettreAJourAvatarEntete(payloadAvatar) {
    if (!payloadAvatar || typeof payloadAvatar !== "object") {
      return;
    }

    const hasPhoto = Boolean(payloadAvatar.has_photo && payloadAvatar.photo_url);
    const photoUrl = String(payloadAvatar.photo_url || "");
    const initiales = String(payloadAvatar.initiales || "US");
    const classeCouleur = String(payloadAvatar.classe_couleur || "");
    const couleur = String(payloadAvatar.couleur || "#4ECDC4");

    remplacerAvatarEnteteDesktop(hasPhoto, photoUrl, initiales, classeCouleur, couleur);
    remplacerAvatarEnteteMobile(hasPhoto, photoUrl, initiales, classeCouleur, couleur);
  }

  function mettreAJourAvatar(payloadAvatar) {
    if (!payloadAvatar || typeof payloadAvatar !== "object") {
      return;
    }

    const hasPhoto = Boolean(payloadAvatar.has_photo && payloadAvatar.photo_url);
    const photoUrl = String(payloadAvatar.photo_url || "");
    const initiales = String(payloadAvatar.initiales || "US");
    const classeCouleur = String(payloadAvatar.classe_couleur || "");
    const couleur = String(payloadAvatar.couleur || "#4ECDC4");

    const preview = document.getElementById("photoPreview");
    const previewHeader = document.getElementById("photoPreviewHeader");

    if (hasPhoto) {
      if (preview) {
        remplacerPreview(preview, "photoPreview", "Photo de profil", photoUrl);
      }
      if (previewHeader) {
        remplacerPreview(previewHeader, "photoPreviewHeader", "Photo de profil", photoUrl);
      }
    } else {
      if (preview) {
        const replacement = renderAvatarPlaceholder(
          "photoPreview",
          initiales,
          classeCouleur,
          couleur
        );
        preview.replaceWith(replacement);
      }
      if (previewHeader) {
        const replacementHeader = renderAvatarPlaceholder(
          "photoPreviewHeader",
          initiales,
          classeCouleur,
          couleur
        );
        previewHeader.replaceWith(replacementHeader);
      }
    }

    const deleteForms = document.querySelectorAll('form.form-ajax[data-action="delete"]');
    deleteForms.forEach((form) => {
      if (!(form instanceof HTMLElement)) {
        return;
      }
      form.classList.toggle("hidden", !hasPhoto);
    });

    mettreAJourAvatarEntete(payloadAvatar);
  }

  function mettreAJourInfosUtilisateur(payloadUser) {
    if (!payloadUser || typeof payloadUser !== "object") {
      return;
    }

    const nom = String(payloadUser.nom || "");
    const email = String(payloadUser.email || "");

    const nomInput = document.getElementById("nom");
    const emailInput = document.getElementById("email");
    if (nomInput instanceof HTMLInputElement && nom !== "") {
      nomInput.value = nom;
    }
    if (emailInput instanceof HTMLInputElement && email !== "") {
      emailInput.value = email;
    }

    const profileName = document.querySelector(".profile-name");
    if (profileName) {
      profileName.textContent = nom !== "" ? nom : profileName.textContent;
    }

    const profileEmail = document.querySelector(".profile-email");
    if (profileEmail && email !== "") {
      const icon = profileEmail.querySelector("i");
      profileEmail.textContent = "";
      if (icon) {
        profileEmail.appendChild(icon);
        profileEmail.append(` ${email}`);
      } else {
        profileEmail.textContent = email;
      }
    }

    if (nom !== "") {
      mettreAJourNomEntete(nom);
    }
  }

  async function soumettreFormulaireAjax(form) {
    const action = String(form.dataset.action || "");
    const confirmMessage = String(form.dataset.confirmMessage || "").trim();

    if (confirmMessage !== "" && !window.confirm(confirmMessage)) {
      return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton instanceof HTMLButtonElement) {
      submitButton.disabled = true;
    }

    const formData = new FormData(form);
    if (!formData.has("action") && action !== "") {
      formData.set("action", action);
    }

    const headers = {
      "X-Requested-With": "XMLHttpRequest",
    };
    const csrfToken = obtenirTokenCsrf();
    if (csrfToken !== "") {
      headers["X-CSRF-Token"] = csrfToken;
    }

    try {
      const requestUrl = construireUrlProfilAjax();

      const response = await fetch(requestUrl, {
        method: "POST",
        body: formData,
        headers,
        credentials: "same-origin",
      });

      if (response.redirected && response.url) {
        window.location.href = response.url;
        return;
      }

      let payload = null;
      try {
        payload = await response.clone().json();
      } catch (_jsonError) {
        payload = null;
      }

      if (payload && payload.redirect_url) {
        window.location.href = String(payload.redirect_url);
        return;
      }

      if (!payload) {
        if (!response.ok) {
          notifier("error", `Erreur serveur (${response.status}).`);
          return;
        }
        notifier("error", "Reponse serveur inattendue.");
        return;
      }

      if (!response.ok || payload.success === false) {
        notifier("error", payload.message || "Erreur lors de la mise a jour.");
        return;
      }

      notifier("success", payload.message || "Mise a jour effectuee.");
      mettreAJourInfosUtilisateur(payload.user);
      mettreAJourNomEntete(
        payload.user && payload.user.nom ? payload.user.nom : obtenirNomUtilisateurActuel()
      );
      mettreAJourAvatar(payload.avatar);

      if (action === "update-password") {
        form.reset();
      }
      if (action === "upload-photo") {
        const fileInput = document.getElementById("photo");
        if (fileInput instanceof HTMLInputElement) {
          fileInput.value = "";
        }
      }
    } catch (error) {
      notifier("error", "Erreur réseau ou session expirée. Veuillez réessayer.");
    } finally {
      if (submitButton instanceof HTMLButtonElement) {
        submitButton.disabled = false;
      }
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    const photoInput = document.getElementById("photo");
    if (photoInput) {
      photoInput.addEventListener("change", (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || !input.files || !input.files[0]) {
          return;
        }

        const reader = new FileReader();
        reader.onload = (readerEvent) => {
          const source = String(readerEvent.target?.result || "");
          if (source === "") {
            return;
          }

          const preview = document.getElementById("photoPreview");
          const previewHeader = document.getElementById("photoPreviewHeader");

          remplacerPreview(preview, "photoPreview", "Photo de profil", source);
          remplacerPreview(previewHeader, "photoPreviewHeader", "Photo de profil", source);
        };

        reader.readAsDataURL(input.files[0]);
      });
    }

    document.querySelectorAll("form.form-ajax").forEach((form) => {
      form.addEventListener("submit", (event) => {
        event.preventDefault();
        soumettreFormulaireAjax(form);
      });
    });
  });
})();
