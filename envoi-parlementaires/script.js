function postForm(e) {
  if (!e.target.classList.contains("envoi-parlementaires-comission-lois")) {
    return;
  }
  e.preventDefault();

  const $ = jQuery;

  const form = $(e.target);
  const formData = form.serialize();

  const button = form.find('button[type="submit"]');
  button.prop("disabled", true);

  const res = $.ajax(
    configParlementaires.endpointURL,
    {
      method: 'POST',
      mode: "same-origin",
      data: formData,
      success: function() {
        const messageBox = $('.envoi-succeeded');
        messageBox.css('display', 'block');

        const formVerifierLettre = $('#form-verifier-lettre');
        formVerifierLettre.css('display', 'none')
      }
    }
  );
}

function mailto() {
    const $ = jQuery;
    $('#ouvrir_popup_client_mail').click()
}

function sendAgain() {
    location.reload();
}

jQuery(function () {
  document.addEventListener("submit", postForm);
  setInterval(() => {
      //only way to listen on modal button to refresh the page.
      const sendAgainButton = document.getElementById("send-again");
      if (sendAgainButton) {
          sendAgainButton.removeEventListener("click", sendAgain);
          sendAgainButton.addEventListener("click", sendAgain);
      }
  }, 1000);
});
