const ImagePreviewHelper = {
  PROXY_ENDPOINT: "proxy-image.php",

  timeouts: {}, // Stockage des timeouts pour chaque instance

  buildProxyUrl(originalUrl) {
    return `${this.PROXY_ENDPOINT}?url=${encodeURIComponent(String(originalUrl || ""))}`;
  },

  init(config) {
    const input = document.getElementById(config.inputId);
    if (!input) return;

    input.addEventListener("input", (e) => {
      this.preview(e.target.value, config);
    });
  },

  preview(url, config) {
    const container = document.getElementById(config.containerId);
    const img = document.getElementById(config.imgId);
    const spinner = config.spinnerId ? document.getElementById(config.spinnerId) : null;
    const error = config.errorId ? document.getElementById(config.errorId) : null;

    if (!container || !img) return;

    if (this.timeouts[config.imgId]) {
      clearTimeout(this.timeouts[config.imgId]);
    }

    if (!url || url.trim() === "") {
      container.style.display = "none";
      if (error) error.style.display = "none";
      img.style.display = "none";
      img.src = "";
      return;
    }

    container.style.display = "block";
    if (error) error.style.display = "none";
    if (spinner) spinner.style.display = "block";
    img.style.display = "block";
    img.style.opacity = "0.3";

    this.timeouts[config.imgId] = setTimeout(() => {
      if (spinner) spinner.style.display = "none";
      img.style.opacity = "1";
    }, 5000);

    const testImg = new Image();

    testImg.onload = () => {
      clearTimeout(this.timeouts[config.imgId]);
      img.src = url;
      img.style.display = "block";
      img.style.opacity = "1";
      if (spinner) spinner.style.display = "none";
    };

    testImg.onerror = () => {
      if (config.useProxy !== false) {
        this.tryWithProxy(url, config, testImg);
      } else {
        this.showError(config);
      }
    };

    testImg.src = url;
  },

  tryWithProxy(originalUrl, config, testImg) {
    const proxyUrl = this.buildProxyUrl(originalUrl);
    const img = document.getElementById(config.imgId);
    const spinner = config.spinnerId ? document.getElementById(config.spinnerId) : null;

    const proxyImg = new Image();

    proxyImg.onload = () => {
      clearTimeout(this.timeouts[config.imgId]);
      img.src = proxyUrl;
      img.style.display = "block";
      img.style.opacity = "1";
      if (spinner) spinner.style.display = "none";
    };

    proxyImg.onerror = () => {
      this.showError(config);
    };

    proxyImg.src = proxyUrl;
  },

  tryLoadViaProxyForElement(imgElement, originalUrl, options = {}) {
    if (!(imgElement instanceof HTMLImageElement)) {
      return;
    }

    const fallbackSelector = String(options.fallbackSelector || ".no-image-fallback");
    const fallback = imgElement.nextElementSibling;
    if (fallback && fallback.matches && fallback.matches(fallbackSelector)) {
      imgElement.style.display = "none";
      fallback.style.display = "flex";
    }

    const proxyUrl = this.buildProxyUrl(originalUrl);
    const proxyImg = new Image();

    proxyImg.onload = () => {
      imgElement.src = proxyUrl;
      imgElement.style.display = "block";
      if (fallback && fallback.matches && fallback.matches(fallbackSelector)) {
        fallback.style.display = "none";
      }
    };

    proxyImg.onerror = () => {};

    proxyImg.src = proxyUrl;
  },

  showError(config) {
    clearTimeout(this.timeouts[config.imgId]);
    const spinner = config.spinnerId ? document.getElementById(config.spinnerId) : null;
    const error = config.errorId ? document.getElementById(config.errorId) : null;
    const img = document.getElementById(config.imgId);

    if (spinner) spinner.style.display = "none";
    img.style.display = "none";
    if (error) error.style.display = "block";
  },

  previewLocal(input, previewImgId, containerId) {
    if (!input.files || !input.files[0]) return;

    const container = document.getElementById(containerId);
    const img = document.getElementById(previewImgId);
    if (!img) return;

    const reader = new FileReader();

    reader.onload = (e) => {
      img.src = e.target.result;
      img.style.display = "block";
      if (container) container.style.display = "block";
    };

    reader.readAsDataURL(input.files[0]);
  },

  reset(config) {
    const container = document.getElementById(config.containerId);
    const img = document.getElementById(config.imgId);
    const error = config.errorId ? document.getElementById(config.errorId) : null;

    if (this.timeouts[config.imgId]) {
      clearTimeout(this.timeouts[config.imgId]);
    }

    if (container) container.style.display = "none";
    if (img) img.src = "";
    if (error) error.style.display = "none";
  },
};

window.ImagePreviewHelper = ImagePreviewHelper;
