<?php
/**
 * public/index.php
 *
 * Point d'entrée public de VeriDoc Cameroun.
 * Ne touche PAS à la base de données : c'est une simple page d'accueil
 * avec le formulaire de saisie de code et le scanner QR. Toute la
 * logique de vérification (et la protection anti-abus) vit dans
 * public/verify.php.
 */
declare(strict_types=1);
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VeriDoc Cameroun — Vérifier un document</title>
<meta name="description" content="Vérifiez en quelques secondes l'authenticité d'un document délivré par un établissement partenaire.">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header class="site-header">
  <a class="brand" href="index.php">
    <span class="brand-mark" aria-hidden="true"></span>
    <span class="brand-text">VeriDoc<span class="brand-text-accent">Cameroun</span></span>
  </a>
  <p class="site-tagline">Authentification publique de documents</p>
</header>

<main class="verify-hero">
  <div class="watermark" aria-hidden="true"></div>

  <div class="verify-card">
    <p class="eyebrow">Vérification en 2 secondes</p>
    <h1>Ce document est-il authentique&nbsp;?</h1>
    <p class="lede">
      Entrez le code imprimé au bas du document, ou scannez son QR code.
      Aucune inscription requise.
    </p>

    <form class="code-form" action="verify.php" method="get" autocomplete="off" novalidate>
      <label for="code" class="code-label">Code du document</label>
      <div class="code-input-row">
        <input
          type="text"
          id="code"
          name="code"
          class="code-input"
          placeholder="EX : CM-2026-AB12CD"
          maxlength="50"
          pattern="[A-Za-z0-9\-]{6,50}"
          title="6 à 50 caractères : lettres, chiffres et tirets uniquement"
          required
          autofocus
        >
        <button type="submit" class="btn btn-primary">Vérifier</button>
      </div>

      <!-- Piège à robots : champ invisible pour un humain, tentant pour un bot.
           Si rempli, verify.php ignore silencieusement la requête. -->
      <div class="hp-field" aria-hidden="true">
        <label for="site_web">Laissez ce champ vide</label>
        <input type="text" id="site_web" name="site_web" tabindex="-1" autocomplete="off">
      </div>
    </form>

    <div class="divider"><span>ou</span></div>

    <button type="button" id="scan-toggle" class="btn btn-ghost">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M4 8V5a1 1 0 0 1 1-1h3M20 8V5a1 1 0 0 1-1-1h-3M4 16v3a1 1 0 0 0 1 1h3M20 16v3a1 1 0 0 1-1 1h-3M4 12h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
      </svg>
      Scanner le QR code avec la caméra
    </button>

    <div id="scan-panel" class="scan-panel" hidden>
      <video id="scan-video" playsinline muted></video>
      <canvas id="scan-canvas" hidden></canvas>
      <div class="scan-frame" aria-hidden="true"></div>
      <p id="scan-status" class="scan-status">Autorisez l'accès à la caméra pour scanner.</p>
      <button type="button" id="scan-close" class="btn btn-ghost btn-small">Fermer le scanner</button>
    </div>
  </div>

  <p class="trust-note">
    Chaque code correspond à un document unique enregistré par son établissement émetteur.
    En cas de doute, contactez directement l'établissement concerné.
  </p>
</main>

<footer class="site-footer">
  <p>VeriDoc Cameroun — Service public de vérification documentaire</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script src="../assets/js/scanner.js"></script>
</body>
</html>
