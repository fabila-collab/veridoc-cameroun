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
$document = null;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Verifier que le document appartient bien a cet etablissement
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ? AND etablissement_id = ?");
    $stmt->execute([$id, $etablissement_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        die("Document introuvable ou vous n'avez pas la permission de le modifier.");
    }
} else {
    header("Location: liste.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_titulaire = trim($_POST['nom_titulaire']);
    $type_document = trim($_POST['type_document']);
    $date_emission = trim($_POST['date_emission']);

    if (empty($nom_titulaire) || empty($type_document) || empty($date_emission)) {
        $message = "Veuillez remplir tous les champs obligatoires.";
        $messageType = "error";
    } else {
        $stmt = $pdo->prepare("UPDATE documents SET nom_titulaire = ?, type_document = ?, date_emission = ? WHERE id = ? AND etablissement_id = ?");
        try {
            $stmt->execute([$nom_titulaire, $type_document, $date_emission, $id, $etablissement_id]);
            $message = "Document mis à jour avec succès.";
            $messageType = "success";
            
            // Mise a jour des donnees affichees
            $document['nom_titulaire'] = $nom_titulaire;
            $document['type_document'] = $type_document;
            $document['date_emission'] = $date_emission;
        } catch (PDOException $e) {
            $message = "Erreur lors de la modification : " . $e->getMessage();
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
    <title>Modifier un Document - Veridoc</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .admin-nav { margin-bottom: 20px; background: #eee; padding: 10px; border-radius: 5px; }
        .admin-nav a { margin-right: 15px; text-decoration: none; color: #007bff; font-weight: bold; }
        form { background: #f8f9fa; padding: 20px; border: 1px solid #ddd; border-radius: 5px; max-width: 500px; display: inline-block; vertical-align: top;}
        form div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="date"] { width: calc(100% - 20px); padding: 10px; border: 1px solid #ccc; border-radius: 3px; }
        input[disabled] { background: #e9ecef; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; font-size: 16px; border-radius: 3px; }
        button:hover { background: #0056b3; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 3px; max-width: 500px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .qr-preview { margin-left: 20px; padding: 20px; border: 1px solid #ddd; background: #fff; display: inline-block; border-radius: 5px; text-align: center; }
    </style>
</head>
<body>
    <h1>Modifier le Document</h1>
    <div class="admin-nav">
        <a href="index.php">Accueil Admin</a>
        <a href="liste.php">Liste des Documents</a>
    </div>

    <?php if ($message): ?>
        <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div>
        <form method="post" action="">
            <div>
                <label for="code_unique">Code Unique (non modifiable) :</label>
                <input type="text" id="code_unique" value="<?= htmlspecialchars($document['code_unique']) ?>" disabled>
            </div>
            <div>
                <label for="nom_titulaire">Nom du Titulaire :</label>
                <input type="text" id="nom_titulaire" name="nom_titulaire" value="<?= htmlspecialchars($document['nom_titulaire']) ?>" required>
            </div>
            <div>
                <label for="type_document">Type de Document :</label>
                <input type="text" id="type_document" name="type_document" value="<?= htmlspecialchars($document['type_document']) ?>" required>
            </div>
            <div>
                <label for="date_emission">Date d'Émission :</label>
                <input type="date" id="date_emission" name="date_emission" value="<?= htmlspecialchars($document['date_emission']) ?>" required>
            </div>
            <button type="submit">Enregistrer les Modifications</button>
        </form>
        
        <div class="qr-preview">
            <h4 style="margin-top: 0;">QR Code associé :</h4>
            <img src="<?= htmlspecialchars(generateQRCodeUrl($document['code_unique'])) ?>" alt="QR Code" style="border: 1px solid #ccc; padding: 5px;">
        </div>
    </div>
</body>
</html>
