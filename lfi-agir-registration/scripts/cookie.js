(function () {

  if ( typeof window.CustomEvent === "function" ) return false;

  function CustomEvent ( event, params ) {
    params = params || { bubbles: false, cancelable: false, detail: null };
    var evt = document.createEvent( 'CustomEvent' );
    evt.initCustomEvent( event, params.bubbles, params.cancelable, params.detail );
    return evt;
  }

  window.CustomEvent = CustomEvent;
})();

window.addEventListener('DOMContentLoaded', function () {
  var i, pair;

  var hashParams = new URLSearchParams(location.hash.replace("#", "?"));

  for (pair of hashParams.entries()) {
    if (pair[0].startsWith("agir_")) {
      Cookies.set(pair[0], pair[1], {sameSite: 'strict'});
    }
  }

  var queryParams = new URLSearchParams(location.search);

  var readParams = [];
  for (pair of queryParams.entries()) {
    if (pair[0].startsWith("agir_")) {
      Cookies.set(pair[0], pair[1], {sameSite: 'strict'});
      readParams.push(pair[0]);
    }
  }

  for (i = 0; i < readParams.length; i++) {
    queryParams.delete(readParams[i]);
  }

  var paramString = queryParams.toString() !== "" ? ("?" + queryParams.toString()) : '';
  window.history.replaceState(null, null, window.location.pathname + paramString);

  window.addEventListener('agirCookiesLoaded', function() {
    var cookies = Cookies.get();
    var elements;
    for (var name in cookies) {
      if(!cookies.hasOwnProperty( name ) || !name.startsWith("agir_")) {
        continue;
      }

      var selector = '[data-agir-cookie="' + name.replace("agir_", '') + '"]';
      elements = document.querySelectorAll(selector);
      for (i = 0; i < elements.length; i++) {
        elements[i].innerText= cookies[name];
      }

      var inputSelector = 'input[name="form_fields[' + name + ']"]';
      elements = document.querySelectorAll(inputSelector);
      for (i = 0; i < elements.length; i++) {
        elements[i].value = cookies[name];
      }
    }
  })

  var event = new CustomEvent('agirCookiesLoaded');

  if (!queryParams.get("_p") || !queryParams.get("code")) {
    window.dispatchEvent(event);
  } else {
    var getparams = new URLSearchParams({
      "p": queryParams.get("_p"),
      "code": queryParams.get("code"),
      "id": queryParams.get("_p"),
      "no_session": 1
    });
    var url1 = "https://actionpopulaire.fr/api/people/retrieve/?" + getparams;
    var r1 = new XMLHttpRequest();
    r1.open("GET", url1, true);
    r1.withCredentials = false;
    r1.onreadystatechange = function () {
      if (r1.readyState !== 4 || r1.status !== 200) return;
      var person = JSON.parse(r1.responseText);
      Cookies.set("agir_first_name", person.firstName);
      Cookies.set("agir_last_name", person.lastName);
      Cookies.set("agir_id", person.id);
      Cookies.set("agir_email", person.email);
      Cookies.set("agir_referrer_id", person.referrerId);
      Cookies.set("agir_newsletters", person.newsletters.join(","));

      window.dispatchEvent(event);
    }
    r1.send();
  }
});
