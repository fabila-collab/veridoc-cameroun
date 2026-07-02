<?php
session_start();
require_once '../db.php';
require_once '../includes/qr.php';

if (!isset($_SESSION['etablissement_id'])) {
    header("Location: ../index.php");
    exit();
}

$etablissement_id = $_SESSION['etablissement_id'];
$message = '';
$messageType = '';
$qrCodeUrl = '';
$code_unique = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_titulaire = trim($_POST['nom_titulaire']);
    $type_document = trim($_POST['type_document']);
    $date_emission = trim($_POST['date_emission']);

    if (empty($nom_titulaire) || empty($type_document) || empty($date_emission)) {
        $message = "Veuillez remplir tous les champs obligatoires.";
        $messageType = "error";
    } else {
        // Generation du code unique
        $code_unique = strtoupper(uniqid('DOC-') . '-' . bin2hex(random_bytes(2)));

        $stmt = $pdo->prepare("INSERT INTO documents (code_unique, nom_titulaire, type_document, etablissement_id, date_emission, statut) VALUES (?, ?, ?, ?, ?, 'valide')");
        
        try {
            $stmt->execute([$code_unique, $nom_titulaire, $type_document, $etablissement_id, $date_emission]);
            $message = "Document ajouté avec succès.";
            $messageType = "success";
            $qrCodeUrl = generateQRCodeUrl($code_unique);
        } catch (PDOException $e) {
            $message = "Erreur lors de l'ajout du document : " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Document - Veridoc</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .admin-nav { margin-bottom: 20px; background: #eee; padding: 10px; border-radius: 5px; }
        .admin-nav a { margin-right: 15px; text-decoration: none; color: #007bff; font-weight: bold; }
        form { background: #f8f9fa; padding: 20px; border: 1px solid #ddd; border-radius: 5px; max-width: 500px; }
        form div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="date"] { width: calc(100% - 20px); padding: 10px; border: 1px solid #ccc; border-radius: 3px; }
        button { padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 3px; }
        button:hover { background: #218838; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 3px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .qr-result { margin-top: 20px; padding: 20px; border: 1px solid #ddd; background: #fff; text-align: center; border-radius: 5px; max-width: 500px; }
        .qr-result img { border: 1px solid #ccc; padding: 5px; background: #fff; }
    </style>
</head>
<body>
    <h1>Ajouter un Document</h1>
    <div class="admin-nav">
        <a href="index.php">Accueil Admin</a>
        <a href="ajouter.php">Ajouter un Document</a>
        <a href="liste.php">Liste des Documents</a>
    </div>

    <?php if ($message): ?>
        <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div>
            <label for="nom_titulaire">Nom du Titulaire :</label>
            <input type="text" id="nom_titulaire" name="nom_titulaire" required placeholder="Ex: Jean Dupont">
        </div>
        <div>
            <label for="type_document">Type de Document :</label>
            <input type="text" id="type_document" name="type_document" required placeholder="Ex: Attestation de Réussite">
        </div>
        <div>
            <label for="date_emission">Date d'Émission :</label>
            <input type="date" id="date_emission" name="date_emission" required>
        </div>
        <button type="submit">Générer le Document et le QR Code</button>
    </form>

    <?php if ($qrCodeUrl): ?>
        <div class="qr-result">
            <h3 style="color: #28a745;">Document Généré avec Succès !</h3>
            <p>Code Unique : <strong><?= htmlspecialchars($code_unique) ?></strong></p>
            <p>QR Code à imprimer sur le document :</p>
            <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="QR Code du document">
        </div>
    <?php endif; ?>
</body>
</html>
