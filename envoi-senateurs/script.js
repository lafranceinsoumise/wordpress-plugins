function postForm(e) {
  console.log("Dans le gestionnaire");
  if (!e.target.classList.contains("envoi-senateurs")) {
    return;
  }
  e.preventDefault();
  console.log("et actif");

  const $ = jQuery;

  const form = $(e.target);
  const formData = form.serialize();

  const button = form.find('button[type="submit"]');
  button.prop("disabled", true);

  const res = $.ajax(configSenateurs.endpointURL, {
    method: "POST",
    mode: "same-origin",
    data: formData,
    success: function () {
      const messageBox = $(".envoi-succes");
      messageBox.css("display", "block");
    },
  });
}

jQuery(function () {
  document.addEventListener("submit", postForm);
});
