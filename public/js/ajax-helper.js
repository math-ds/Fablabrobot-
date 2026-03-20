const AjaxHelper = {
  obtenirTokenCsrf: function () {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
      return metaTag.content;
    }

    const inputField = document.querySelector('input[name="csrf_token"]');
    if (inputField) {
      return inputField.value;
    }

    console.error("Token CSRF introuvable");
    return "";
  },

  mettreAJourTokenCsrf: function (token) {
    const nouveauToken = String(token || "").trim();
    if (nouveauToken === "") {
      return;
    }

    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
      metaTag.content = nouveauToken;
    }

    document.querySelectorAll('input[name="csrf_token"]').forEach((inputField) => {
      if (inputField instanceof HTMLInputElement) {
        inputField.value = nouveauToken;
      }
    });
  },

  extraireTokenDepuisReponse: function (data) {
    if (!data || typeof data !== "object") {
      return "";
    }

    const token = data?.data?.new_token ?? data?.new_token ?? "";
    return String(token).trim();
  },

  messageStatutHttp: function (status) {
    const map = {
      400: "Requete invalide.",
      401: "Session invalide. Veuillez vous reconnecter.",
      403: "Action non autorisee ou jeton de securite invalide.",
      404: "Ressource introuvable.",
      409: "Conflit de donnees.",
      419: "Session expirée. Rechargez la page et reessayez.",
      422: "Des donnees sont invalides.",
      429: "Trop de requetes. Reessayez dans quelques secondes.",
      500: "Erreur interne du serveur.",
      502: "Serveur indisponible (502).",
      503: "Service temporairement indisponible (503).",
      504: "Serveur trop lent (504).",
    };
    return map[Number(status)] || "Une erreur est survenue.";
  },

  extraireMessageErreur: function (error, fallback = "Une erreur est survenue.") {
    const fallbackText = String(fallback || "Une erreur est survenue.").trim();
    if (!error) {
      return fallbackText;
    }

    if (typeof error === "string") {
      const msg = error.trim();
      return msg !== "" ? msg : fallbackText;
    }

    const direct = String(error.message || "").trim();
    if (direct !== "") {
      return direct;
    }

    const nested = String(error?.data?.message || "").trim();
    if (nested !== "") {
      return nested;
    }

    const nestedError = String(error?.data?.error?.message || "").trim();
    if (nestedError !== "") {
      return nestedError;
    }

    if (error?.response?.status) {
      return this.messageStatutHttp(error.response.status);
    }

    return fallbackText;
  },

  requete: async function (url, options = {}) {
    const timeoutMsRaw = Number(options.timeoutMs);
    const timeoutMs = Number.isFinite(timeoutMsRaw) && timeoutMsRaw > 0 ? timeoutMsRaw : 15000;
    const abortController = new AbortController();
    const timeoutId = window.setTimeout(() => abortController.abort(), timeoutMs);

    const config = {
      method: options.method || "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-Token": this.obtenirTokenCsrf(),
        ...options.headers,
      },
      signal: abortController.signal,
    };

    if (options.data) {
      if (options.data instanceof FormData) {
        config.body = options.data;
      } else {
        config.headers["Content-Type"] = "application/x-www-form-urlencoded";
        config.body = new URLSearchParams(options.data).toString();
      }
    }

    try {
      const response = await fetch(url, config);
      const responseText = await response.text();
      let data = {};
      if (responseText.trim() !== "") {
        try {
          data = JSON.parse(responseText);
        } catch (_parseError) {
          data = {
            success: false,
            message: this.messageStatutHttp(response.status),
            raw_response: responseText,
          };
        }
      }

      const nouveauToken = this.extraireTokenDepuisReponse(data);

      if (nouveauToken !== "") {
        this.mettreAJourTokenCsrf(nouveauToken);
        if (data && typeof data === "object") {
          data.new_token = nouveauToken;
        }
      }

      if (!response.ok) {
        const csrfInvalide = response.status === 403 || response.status === 419;
        const peutRetenter = csrfInvalide && nouveauToken !== "" && options.__csrfRetry !== true;

        if (peutRetenter) {
          const retryOptions = { ...options, __csrfRetry: true };

          if (retryOptions.data instanceof FormData) {
            retryOptions.data.set("csrf_token", nouveauToken);
          } else if (retryOptions.data && typeof retryOptions.data === "object") {
            retryOptions.data = { ...retryOptions.data, csrf_token: nouveauToken };
          }

          retryOptions.headers = {
            ...(retryOptions.headers || {}),
            "X-CSRF-Token": nouveauToken,
          };

          return this.requete(url, retryOptions);
        }

        const messageApi = String(data?.message || data?.error?.message || "").trim();
        if (messageApi === "") {
          data.message = this.messageStatutHttp(response.status);
        }
        data.success = false;

        throw { response, data };
      }

      return data;
    } catch (error) {
      console.error("Erreur AJAX:", error);

      if (error && error.name === "AbortError") {
        throw {
          data: {
            success: false,
            message: "La requête a expiré. Réessayez.",
          },
        };
      }

      if (error instanceof TypeError && error.message === "Failed to fetch") {
        throw {
          data: {
            success: false,
            message: "Erreur de connexion. Vérifiez votre réseau.",
          },
        };
      }

      if (error.message && error.message.includes("Timeout")) {
        throw {
          data: {
            success: false,
            message: "La requête a expiré. Réessayez.",
          },
        };
      }

      if (error && typeof error === "object" && error.response) {
        const status = Number(error.response.status || 0);
        const message = this.extraireMessageErreur(error, this.messageStatutHttp(status));
        throw {
          ...error,
          data: {
            ...(error.data || {}),
            success: false,
            message,
          },
        };
      }

      const message = this.extraireMessageErreur(error, "Une erreur est survenue.");
      throw {
        data: {
          success: false,
          message,
        },
      };
    } finally {
      window.clearTimeout(timeoutId);
    }
  },

  get: function (url) {
    return this.requete(url, { method: "GET" });
  },

  post: function (url, data) {
    const tokenMeta = this.obtenirTokenCsrf();
    let dataWithToken = data;

    if (data && typeof data === "object" && !(data instanceof FormData)) {
      const payload = { ...data };
      if (!Object.prototype.hasOwnProperty.call(payload, "csrf_token")) {
        payload.csrf_token = tokenMeta;
      }
      dataWithToken = payload;
    } else if (data instanceof FormData) {
      const tokenForm = String(data.get("csrf_token") || "").trim();
      const tokenFinal = tokenForm !== "" ? tokenForm : tokenMeta;

      if (tokenForm === "" && tokenFinal !== "") {
        data.set("csrf_token", tokenFinal);
      }

      return this.requete(url, {
        method: "POST",
        data,
        headers: tokenFinal !== "" ? { "X-CSRF-Token": tokenFinal } : {},
      });
    }

    return this.requete(url, {
      method: "POST",
      data: dataWithToken,
      headers: tokenMeta !== "" ? { "X-CSRF-Token": tokenMeta } : {},
    });
  },

  put: function (url, data) {
    return this.requete(url, { method: "PUT", data });
  },

  delete: function (url, data = {}) {
    return this.requete(url, {
      method: "POST",
      data: { ...data, _method: "DELETE" },
    });
  },

  notifierErreur: function (error, fallback = "Une erreur est survenue.") {
    const message = this.extraireMessageErreur(error, fallback);
    if (
      typeof ToastNotification !== "undefined" &&
      typeof ToastNotification.erreur === "function"
    ) {
      ToastNotification.erreur(message);
      return;
    }
    console.error(message);
  },

  notifierSucces: function (message, fallback = "Action reussie.") {
    const texte = String(message || fallback || "").trim();
    if (texte === "") {
      return;
    }
    if (
      typeof ToastNotification !== "undefined" &&
      typeof ToastNotification.succes === "function"
    ) {
      ToastNotification.succes(texte);
      return;
    }
    console.log(texte);
  },
};
