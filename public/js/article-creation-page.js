(() => {
  document.addEventListener("DOMContentLoaded", () => {
    if (typeof ImagePreviewHelper === "undefined") {
      return;
    }

    ImagePreviewHelper.init({
      inputId: "image_url",
      containerId: "imagePreviewContainer",
      imgId: "imagePreview",
      spinnerId: "imageLoadingSpinner",
      errorId: "imagePreviewError",
      useProxy: true,
    });

    const fileInput = document.querySelector("[data-article-create-image-file-input]");
    if (fileInput instanceof HTMLInputElement) {
      fileInput.addEventListener("change", () => {
        ImagePreviewHelper.previewLocal(
          fileInput,
          "localPreviewArticleCreate",
          "localPreviewArticleCreateContainer"
        );
      });
    }
  });
})();
