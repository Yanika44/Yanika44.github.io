/**
 * Google signin Script
 *
 * @filesource js/google.js
 * @link https://www.kotchasan.com/
 * @copyright 2018 Goragod.com
 * @license https://www.kotchasan.com/license/
 */
function initGooleSignin(google_client_id) {
  window.setTimeout(function() {
    loadJavascript(
      "apis-google",
      "https://accounts.google.com/gsi/client",
      googleSigninLoad
    );
  }, 100);
  window.google_client_id = google_client_id;
}

var googleSigninLoad = function() {
  var handleCredentialResponse = function(response) {
    let responsePayload = jwt_decode(response.credential),
      q = [];
    if ($E("token")) {
      q.push("token=" + $E("token").value);
    }
    q.push("id=" + encodeURIComponent(responsePayload.sub));
    q.push("name=" + encodeURIComponent(responsePayload.name));
    q.push("image=" + encodeURIComponent(responsePayload.picture));
    q.push("email=" + encodeURIComponent(responsePayload.email));
    send(WEB_URL + "index.php/" + ($E("google_action") ? $E("google_action").value : "index/model/gglogin/chklogin"), q.join("&"), socialLoginSubmit);
  };
  google.accounts.id.initialize({
    client_id: window.google_client_id + ".apps.googleusercontent.com",
    callback: handleCredentialResponse
  });
  if ($E("google_login")) {
    var o = {
      theme: "outline",
      size: "large"
    };
    var datas = $E("google_login").dataset;
    for (var prop in datas) {
      o[prop] = datas[prop];
    }
    google.accounts.id.renderButton($E("google_login"), o);
  }
};
