/**
 * assets/js/scanner.js
 *
 * Scanner de QR code par webcam, pour les personnes qui ont le code
 * sous les yeux (papier, écran) mais préfèrent scanner plutôt que
 * taper. S'appuie sur jsQR (chargé en CDN dans public/index.php).
 *
 * Comportement : dès qu'un QR est décodé, on en extrait le code puis
 * on redirige vers verify.php?code=... — exactement la même page que
 * si le code avait été tapé à la main. Toute la logique de sécurité
 * (rate limiting, validation) reste donc centralisée côté serveur.
 */
(function () {
  'use strict';

  var toggleBtn = document.getElementById('scan-toggle');
  var closeBtn = document.getElementById('scan-close');
  var panel = document.getElementById('scan-panel');
  var video = document.getElementById('scan-video');
  var canvas = document.getElementById('scan-canvas');
  var statusEl = document.getElementById('scan-status');
  var codeInput = document.getElementById('code');
  var form = codeInput ? codeInput.closest('form') : null;

  if (!toggleBtn || !panel || !video || !canvas) {
    return; // Cette page n'a pas de scanner (sécurité si le HTML change)
  }

  var stream = null;
  var rafId = null;
  var ctx = canvas.getContext('2d', { willReadFrequently: true });
  var scanning = false;

  function setStatus(message) {
    if (statusEl) statusEl.textContent = message;
  }

  function supportsCamera() {
    return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
  }

  /**
   * Un QR de document peut contenir soit le code brut ("CM-2026-AB12CD"),
   * soit une URL complète pointant vers verify.php?code=CM-2026-AB12CD
   * (cas d'un QR imprimé sur le document, scannable par n'importe quel
   * appareil photo, pas seulement notre scanner). On gère les deux.
   */
  function extraireCode(texteDecode) {
    var texte = (texteDecode || '').trim();
    try {
      var url = new URL(texte);
      var parametre = url.searchParams.get('code');
      if (parametre) return parametre;
    } catch (erreur) {
      // Ce n'est pas une URL, on continue avec le texte brut
    }
    return texte;
  }

  function arreterCamera() {
    scanning = false;
    if (rafId) {
      cancelAnimationFrame(rafId);
      rafId = null;
    }
    if (stream) {
      stream.getTracks().forEach(function (track) { track.stop(); });
      stream = null;
    }
    video.srcObject = null;
  }

  function fermerPanneau() {
    arreterCamera();
    panel.hidden = true;
    toggleBtn.setAttribute('aria-expanded', 'false');
  }

  function boucleAnalyse() {
    if (!scanning) return;

    if (video.readyState === video.HAVE_ENOUGH_DATA && typeof jsQR === 'function') {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

      var image = ctx.getImageData(0, 0, canvas.width, canvas.height);
      var resultat = jsQR(image.data, image.width, image.height, {
        inversionAttempts: 'dontInvert',
      });

      if (resultat && resultat.data) {
        var code = extraireCode(resultat.data);
        if (code) {
          setStatus('Code détecté, vérification en cours…');
          arreterCamera();
          if (codeInput && form) {
            codeInput.value = code;
            form.submit();
          }
          return;
        }
      }
    }

    rafId = requestAnimationFrame(boucleAnalyse);
  }

  function demarrerCamera() {
    if (typeof jsQR !== 'function') {
      setStatus('Le scanner est indisponible pour le moment. Merci de saisir le code manuellement.');
      return;
    }
    if (!supportsCamera()) {
      setStatus("Votre navigateur ne permet pas d'accéder à la caméra. Saisissez le code manuellement.");
      return;
    }

    setStatus('Ouverture de la caméra…');

    navigator.mediaDevices
      .getUserMedia({ video: { facingMode: 'environment' } })
      .then(function (flux) {
        stream = flux;
        video.srcObject = flux;
        return video.play();
      })
      .then(function () {
        setStatus('Placez le QR code du document dans le cadre.');
        scanning = true;
        rafId = requestAnimationFrame(boucleAnalyse);
      })
      .catch(function () {
        setStatus("Impossible d'accéder à la caméra. Vérifiez les autorisations ou saisissez le code manuellement.");
      });
  }

  toggleBtn.addEventListener('click', function () {
    var estOuvert = !panel.hidden;
    if (estOuvert) {
      fermerPanneau();
    } else {
      panel.hidden = false;
      toggleBtn.setAttribute('aria-expanded', 'true');
      demarrerCamera();
    }
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', fermerPanneau);
  }

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) arreterCamera();
  });
  window.addEventListener('pagehide', arreterCamera);
})();
