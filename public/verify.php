<?php
/**
 * public/verify.php
 *
 * Cœur de la vérification publique :
 *  1. Applique la limitation de débit par IP (includes/rate_limit.php)
 *  2. Valide le format du code avant toute requête SQL
 *  3. Cherche le document (requête préparée, jointure établissement)
 *  4. Affiche un résultat "tampon" clair : valide / révoqué / introuvable
 *
 * Volontairement : le message affiché quand un code n'existe pas et
 * quand un code a un format invalide est IDENTIQUE. Ça évite de donner
 * à un script d'énumération un signal lui permettant d'affiner ses
 * essais (oracle de format).
 */
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../db.php';            // fournit $pdo
require_once __DIR__ . '/../includes/rate_limit.php';

$ip = rl_client_ip();
$codeBrut = trim((string) ($_GET['code'] ?? ''));
$code = strtoupper($codeBrut);
$estRobot = trim((string) ($_GET['site_web'] ?? '')) !== ''; // piège à robots (index.php)

$etatDebit = rl_check($pdo, $ip);

$resultat = null;      // null | 'valide' | 'revoque' | 'introuvable' | 'bloque'
$document = null;

if ($codeBrut === '') {
    // Arrivée sur la page sans code : on ne consomme pas le quota,
    // on invite juste à revenir au formulaire.
    $resultat = null;
} else {
    // On enregistre TOUTE tentative avec un code non vide — y compris
    // celles faites alors que l'IP est déjà bloquée. C'est ce qui
    // permet au blocage de se prolonger tant que l'abus continue,
    // au lieu de se vider tout seul après 60s peu importe l'activité.
    rl_record($pdo, $ip, $code);

    if (!$etatDebit['autorise']) {
        rl_deny_response($etatDebit);
        $resultat = 'bloque';
    } elseif ($estRobot || !rl_code_valide($code)) {
        $resultat = 'introuvable';
    } else {
        $stmt = $pdo->prepare(
            'SELECT d.nom_titulaire, d.type_document, d.date_emission,
                    d.statut, d.date_creation, e.nom AS etablissement_nom
             FROM documents d
             JOIN etablissements e ON e.id = d.etablissement_id
             WHERE d.code_unique = :code
             LIMIT 1'
        );
        $stmt->execute(['code' => $code]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            $resultat = 'introuvable';
        } else {
            $resultat = $document['statut'] === 'valide' ? 'valide' : 'revoque';
        }
    }
}

$titresParEtat = [
    'valide'      => 'Document authentique',
    'revoque'     => 'Document révoqué',
    'introuvable' => 'Code introuvable',
    'bloque'      => 'Trop de tentatives',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $resultat ? htmlspecialchars($titresParEtat[$resultat]) . ' — ' : '' ?>VeriDoc Cameroun</title>
<meta name="robots" content="noindex">
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

<main class="result-hero">
  <div class="watermark" aria-hidden="true"></div>

<?php if ($resultat === null): ?>

  <div class="result-card result-empty">
    <h1>Aucun code fourni</h1>
    <p class="lede">Retournez à l'accueil pour saisir ou scanner un code.</p>
    <a class="btn btn-primary" href="index.php">Retour à la vérification</a>
  </div>

<?php elseif ($resultat === 'bloque'): ?>

  <div class="result-card result-bloque">
    <div class="stamp stamp-neutral" aria-hidden="true">
      <span class="stamp-icon">⏳</span>
    </div>
    <h1><?= htmlspecialchars($titresParEtat['bloque']) ?></h1>
    <p class="lede"><?= htmlspecialchars(RL_MESSAGE) ?></p>
    <a class="btn btn-ghost" href="index.php">Retour à l'accueil</a>
  </div>

<?php elseif ($resultat === 'introuvable'): ?>

  <div class="result-card result-introuvable">
    <div class="stamp stamp-neutral" aria-hidden="true">
      <span class="stamp-icon">?</span>
    </div>
    <h1><?= htmlspecialchars($titresParEtat['introuvable']) ?></h1>
    <p class="lede">
      Aucun document ne correspond à ce code. Vérifiez qu'il est saisi
      exactement comme imprimé, ou contactez l'établissement émetteur.
    </p>
    <a class="btn btn-primary" href="index.php">Vérifier un autre code</a>
  </div>

<?php elseif ($resultat === 'revoque'): ?>

  <div class="result-card result-revoque">
    <div class="stamp stamp-revoque" aria-hidden="true">
      <span class="stamp-icon">✕</span>
    </div>
    <h1><?= htmlspecialchars($titresParEtat['revoque']) ?></h1>
    <p class="lede">Ce document a été révoqué par son établissement émetteur et n'est plus valable.</p>

    <dl class="doc-details">
      <div><dt>Titulaire</dt><dd><?= htmlspecialchars($document['nom_titulaire']) ?></dd></div>
      <div><dt>Type de document</dt><dd><?= htmlspecialchars($document['type_document']) ?></dd></div>
      <div><dt>Établissement émetteur</dt><dd><?= htmlspecialchars($document['etablissement_nom']) ?></dd></div>
      <div><dt>Date d'émission</dt><dd><?= htmlspecialchars($document['date_emission']) ?></dd></div>
    </dl>

    <a class="btn btn-ghost" href="index.php">Vérifier un autre code</a>
  </div>

<?php else: /* valide */ ?>

  <div class="result-card result-valide">
    <div class="stamp stamp-valide" aria-hidden="true">
      <span class="stamp-icon">✓</span>
    </div>
    <h1><?= htmlspecialchars($titresParEtat['valide']) ?></h1>
    <p class="lede">Ce document a été délivré par un établissement partenaire et n'a pas été révoqué.</p>

    <dl class="doc-details">
      <div><dt>Titulaire</dt><dd><?= htmlspecialchars($document['nom_titulaire']) ?></dd></div>
      <div><dt>Type de document</dt><dd><?= htmlspecialchars($document['type_document']) ?></dd></div>
      <div><dt>Établissement émetteur</dt><dd><?= htmlspecialchars($document['etablissement_nom']) ?></dd></div>
      <div><dt>Date d'émission</dt><dd><?= htmlspecialchars($document['date_emission']) ?></dd></div>
    </dl>

    <a class="btn btn-ghost" href="index.php">Vérifier un autre code</a>
  </div>

<?php endif; ?>

</main>

<footer class="site-footer">
  <p>VeriDoc Cameroun — Service public de vérification documentaire</p>
</footer>

</body>
</html>
