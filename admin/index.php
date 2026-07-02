<?php
session_start();
require_once '../db.php';

// Verification de la connexion de l'etablissement
if (!isset($_SESSION['etablissement_id'])) {
    header("Location: ../index.php");
    exit();
}

$etablissement_id = $_SESSION['etablissement_id'];

// Recuperation des statistiques
$stmt = $pdo->prepare("SELECT COUNT(*) as total_docs FROM documents WHERE etablissement_id = ?");
$stmt->execute([$etablissement_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Admin - Veridoc</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .admin-nav { margin-bottom: 20px; background: #eee; padding: 10px; border-radius: 5px; }
        .admin-nav a { margin-right: 15px; text-decoration: none; color: #007bff; font-weight: bold; }
        .dashboard-stats { padding: 20px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; display: inline-block; }
    </style>
</head>
<body>
    <h1>Tableau de Bord - Établissement</h1>
    <div class="admin-nav">
        <a href="index.php">Accueil Admin</a>
        <a href="ajouter.php">Ajouter un Document</a>
        <a href="liste.php">Liste des Documents</a>
        <!-- Assuming logout logic is handled elsewhere, e.g. ../logout.php -->
        <a href="../logout.php" style="color: red;">Déconnexion</a>
    </div>

    <div class="dashboard-stats">
        <h2>Statistiques</h2>
        <p>Total des documents générés : <span style="font-size: 1.5em; font-weight: bold; color: #28a745;"><?= htmlspecialchars($stats['total_docs']) ?></span></p>
    </div>
</body>
</html>
