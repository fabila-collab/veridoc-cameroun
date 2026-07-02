<?php
/**
 * includes/rate_limit.php
 *
 * Protection anti-abus pour la vérification publique de documents.
 * Empêche qu'un script teste des milliers de codes au hasard
 * (énumération / brute force) en limitant le nombre de tentatives
 * par adresse IP, stockées dans la table `verification_attempts`.
 *
 * Ce fichier ne se connecte pas lui-même à la base : chaque fonction
 * reçoit le $pdo déjà ouvert (voir db.php à la racine du projet).
 *
 * Utilisation typique dans public/verify.php :
 *
 *   require_once __DIR__ . '/../db.php';
 *   require_once __DIR__ . '/../includes/rate_limit.php';
 *
 *   $ip = rl_client_ip();
 *   $etat = rl_check($pdo, $ip);      // état AVANT cette tentative
 *   rl_record($pdo, $ip, $code);      // toujours enregistrer, même bloqué :
 *                                      // sinon le blocage se vide tout seul
 *                                      // même si l'IP continue d'insister.
 *   if (!$etat['autorise']) {
 *       rl_deny_response($etat);
 *   }
 */

// ---- Réglages -------------------------------------------------------
// Ajustables selon le trafic réel observé une fois en production.

const RL_LIMITE_MINUTE   = 10;   // tentatives max sur 60 secondes
const RL_BLOCAGE_MINUTE  = 60;   // durée du blocage (secondes) si dépassé
const RL_LIMITE_HEURE    = 60;   // tentatives max sur 3600 secondes
const RL_BLOCAGE_HEURE   = 900;  // durée du blocage (secondes) si dépassé
const RL_PURGE_PROBA     = 0.02; // 2% de chances de purger les vieilles lignes à chaque appel

/**
 * Récupère l'adresse IP du visiteur de façon prudente.
 *
 * On ne fait PAS confiance à X-Forwarded-For par défaut : un client
 * malveillant peut envoyer n'importe quelle valeur dans cet en-tête
 * pour contourner la limitation par IP. Si le site est un jour derrière
 * un reverse proxy de confiance (Cloudflare, Nginx...), il faudra
 * whitelister l'IP du proxy avant d'activer ce comportement.
 */
function rl_client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Valide le format d'un code avant de toucher la base.
 * Rejette immédiatement le bruit / les injections évidentes et
 * évite une requête SQL inutile pour des entrées absurdes.
 */
function rl_code_valide(string $code): bool
{
    // Adapter le motif au format réel choisi par Personne 1/2
    // (ex: CM-2026-AB12CD). On reste permissif mais borné.
    return (bool) preg_match('/^[A-Z0-9\-]{6,50}$/', $code);
}

/**
 * Vérifie si l'IP est actuellement autorisée à tenter une vérification.
 *
 * @return array{autorise:bool, motif?:string, attendre:int, restantes:int}
 */
function rl_check(PDO $pdo, string $ip): array
{
    // Fenêtre courte : anti rafale (script qui boucle vite)
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS n, MIN(date_tentative) AS plus_ancienne
         FROM verification_attempts
         WHERE adresse_ip = :ip AND date_tentative >= (NOW() - INTERVAL 1 MINUTE)'
    );
    $stmt->execute(['ip' => $ip]);
    $minute = $stmt->fetch(PDO::FETCH_ASSOC);

    if ((int) $minute['n'] >= RL_LIMITE_MINUTE) {
        $ecoule = time() - strtotime($minute['plus_ancienne']);
        return [
            'autorise'  => false,
            'motif'     => 'trop_rapide',
            'attendre'  => max(1, RL_BLOCAGE_MINUTE - $ecoule),
            'restantes' => 0,
        ];
    }

    // Fenêtre longue : anti campagne d'énumération étalée dans le temps
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS n, MIN(date_tentative) AS plus_ancienne
         FROM verification_attempts
         WHERE adresse_ip = :ip AND date_tentative >= (NOW() - INTERVAL 1 HOUR)'
    );
    $stmt->execute(['ip' => $ip]);
    $heure = $stmt->fetch(PDO::FETCH_ASSOC);

    if ((int) $heure['n'] >= RL_LIMITE_HEURE) {
        $ecoule = time() - strtotime($heure['plus_ancienne']);
        return [
            'autorise'  => false,
            'motif'     => 'quota_horaire',
            'attendre'  => max(1, RL_BLOCAGE_HEURE - $ecoule),
            'restantes' => 0,
        ];
    }

    return [
        'autorise'  => true,
        'attendre'  => 0,
        'restantes' => RL_LIMITE_MINUTE - (int) $minute['n'],
    ];
}

/**
 * Enregistre une tentative de vérification (à appeler après rl_check,
 * que le code soit valide ou non — on compte TOUTES les tentatives).
 */
function rl_record(PDO $pdo, string $ip, ?string $code): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO verification_attempts (adresse_ip, code_tente, date_tentative)
         VALUES (:ip, :code, NOW())'
    );
    $stmt->execute([
        'ip'   => $ip,
        'code' => $code !== null ? substr($code, 0, 50) : null,
    ]);

    // Purge paresseuse : pas besoin de cron pour un petit projet étudiant,
    // on nettoie de temps en temps les lignes de plus de 24h.
    if (mt_rand() / mt_getrandmax() < RL_PURGE_PROBA) {
        $pdo->exec('DELETE FROM verification_attempts WHERE date_tentative < (NOW() - INTERVAL 1 DAY)');
    }
}

/**
 * Coupe court proprement quand une IP est bloquée : entête HTTP 429
 * correct + message clair, sans détail technique exploitable.
 */
function rl_deny_response(array $etat): void
{
    http_response_code(429);
    header('Retry-After: ' . $etat['attendre']);
    $minutes = max(1, (int) ceil($etat['attendre'] / 60));
    $message = $minutes > 1
        ? "Trop de tentatives depuis cette connexion. Réessayez dans environ {$minutes} minutes."
        : "Trop de tentatives depuis cette connexion. Réessayez dans quelques instants.";
    // La page appelante (public/verify.php) inclut ce fichier puis affiche
    // ce message dans le même gabarit visuel que le reste du site.
    define('RL_BLOQUE', true);
    define('RL_MESSAGE', $message);
}
