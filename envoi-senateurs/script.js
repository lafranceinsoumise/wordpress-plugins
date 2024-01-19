function postForm(e) {
  const $ = jQuery;
  if (e.target.id !== "envoi-senateurs") {
    return;
  }
  e.preventDefault();
  $("#success-message").css("display", "none");

  const form = $(e.target);
  const formData = form.serialize();

  const button = form.find('button[type="submit"]');
  button.prop("disabled", true);

  const res = $.ajax(configSenateurs.endpointURL, {
    method: "POST",
    mode: "same-origin",
    data: formData,
    success: function () {
      $("#success-message").css("display", "block");
    },
  });
}

jQuery(function () {
  document.addEventListener("submit", postForm);
});
