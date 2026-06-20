(function () {
  var gate = document.getElementById("tos-gate");

  if (!gate) {
    return;
  }

  var accept = document.getElementById("tos-gate-accept");
  var decline = document.getElementById("tos-gate-decline");
  var remember = document.getElementById("tos-gate-remember");
  var gated = document.documentElement.classList.contains("tos-gated");

  if (gated) {
    gate.hidden = false;
  }

  function setCookie(days) {
    var secure = location.protocol === "https:" ? "; Secure" : "";
    var age = days ? "; max-age=" + days * 24 * 60 * 60 : "";

    document.cookie = "simms_tos_accepted=1; path=/; SameSite=Lax" + age + secure;
  }

  if (accept) {
    accept.addEventListener("click", function () {
      setCookie(remember && remember.checked ? 30 : 0);

      document.documentElement.classList.add("tos-gate-exiting");
      document.documentElement.classList.remove("tos-gated");

      window.setTimeout(function () {
        gate.hidden = true;
        document.documentElement.classList.remove("tos-gate-exiting");
      }, 850);
    });
  }

  if (decline) {
    decline.addEventListener("click", function () {
      window.location.replace("https://www.google.com");
    });
  }
})();
