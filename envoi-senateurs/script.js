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

  const res = $.ajax(
    configSenateurs.endpointURL,
    {
      method: 'POST',
      mode: "same-origin",
      data: formData,
      success: function() {
        const messageBox = $('<div class="message"></div>');
        messageBox.text("Merci, votre email va être envoyé automatiquement. Vous en recevrez une copie sur l'adresse email que vous avez indiquée.");

        form.after(messageBox);
      }
    }
  );

}

jQuery(function () {
  document.addEventListener("submit", postForm);
});
