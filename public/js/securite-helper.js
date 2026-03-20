const SecuriteHelper = {
  echapperHtml: function (texte) {
    if (texte === null || texte === undefined) {
      return "";
    }

    const div = document.createElement("div");
    div.textContent = String(texte);
    return div.innerHTML;
  },

  echapperAttribut: function (valeur) {
    if (valeur === null || valeur === undefined) {
      return "";
    }

    return String(valeur)
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#x27;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  },

  creerElementSecurise: function (balise, contenuTexte = "", classes = []) {
    const element = document.createElement(balise);

    if (contenuTexte) {
      element.textContent = contenuTexte;
    }

    if (classes.length > 0) {
      element.classList.add(...classes);
    }

    return element;
  },

  definirAttributSecurise: function (element, nomAttribut, valeur) {
    if (element && nomAttribut) {
      element.setAttribute(nomAttribut, this.echapperAttribut(valeur));
    }
  },

  echapperRegex: function (chaine) {
    return String(chaine).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  },

  estAlphaNumerique: function (chaine) {
    return /^[a-zA-Z0-9]+$/.test(String(chaine));
  },

  tronquer: function (texte, longueurMax, suffixe = "...") {
    const texteStr = String(texte || "");

    if (texteStr.length <= longueurMax) {
      return texteStr;
    }

    return texteStr.substring(0, longueurMax - suffixe.length) + suffixe;
  },
};
