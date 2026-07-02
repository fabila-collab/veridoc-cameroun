<?php
session_start();
require_once '../db.php';
require_once '../includes/qr.php';

if (!isset($_SESSION['etablissement_id'])) {
    header("Location: ../index.php");
    exit();
}

$etablissement_id = $_SESSION['etablissement_id'];

$stmt = $pdo->prepare("SELECT * FROM documents WHERE etablissement_id = ? ORDER BY date_creation DESC");
$stmt->execute([$etablissement_id]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Documents - Veridoc</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .admin-nav { margin-bottom: 20px; background: #eee; padding: 10px; border-radius: 5px; }
        .admin-nav a { margin-right: 15px; text-decoration: none; color: #007bff; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .statut-valide { color: #28a745; font-weight: bold; }
        .statut-revoque { color: #dc3545; font-weight: bold; }
        .actions a { margin-right: 10px; text-decoration: none; color: #007bff; }
        .actions a:hover { text-decoration: underline; }
        .btn-revoquer { color: #dc3545 !important; }
    </style>
</head>
<body>
    <h1>Liste de vos Documents</h1>
    <div class="admin-nav">
        <a href="index.php">Accueil Admin</a>
        <a href="ajouter.php">Ajouter un Document</a>
        <a href="liste.php">Liste des Documents</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Code Unique</th>
                <th>Nom du Titulaire</th>
                <th>Type de Document</th>
                <th>Date d'Émission</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($documents) > 0): ?>
                <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($doc['code_unique']) ?></strong></td>
                        <td><?= htmlspecialchars($doc['nom_titulaire']) ?></td>
                        <td><?= htmlspecialchars($doc['type_document']) ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($doc['date_emission']))) ?></td>
                        <td class="<?= $doc['statut'] === 'valide' ? 'statut-valide' : 'statut-revoque' ?>">
                            <?= htmlspecialchars(ucfirst($doc['statut'])) ?>
                        </td>
                        <td class="actions">
                            <a href="modifier.php?id=<?= $doc['id'] ?>">Modifier</a>
                            <?php if ($doc['statut'] === 'valide'): ?>
                                <a href="revoquer.php?id=<?= $doc['id'] ?>" class="btn-revoquer" onclick="return confirm('Êtes-vous sûr de vouloir révoquer ce document ? Cette action est irréversible.');">Révoquer</a>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars(generateQRCodeUrl($doc['code_unique'])) ?>" target="_blank" title="Voir le QR Code">QR Code</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Aucun document n'a été généré pour le moment.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
