const CsrfManager = {
  getToken: function () {
    const field = document.querySelector('input[name="csrf_token"]');
    return field ? field.value : "";
  },
  updateAllTokenFields: function () {},
  addTokenToForm: function (form) {},
  refreshToken: function () {
    window.location.reload();
  },
};
