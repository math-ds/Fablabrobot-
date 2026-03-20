(() => {
  function previewLocalImage(input) {
    if (typeof ImagePreviewHelper === "undefined") {
      return;
    }

    ImagePreviewHelper.previewLocal(input, "localPreview", "localPreviewContainer");
  }

  window.previewLocalImage = previewLocalImage;

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

    const inputFile = document.getElementById("creation_project_image_file");
    if (inputFile) {
      inputFile.addEventListener("change", () => {
        previewLocalImage(inputFile);
      });
    }
  });
})();
