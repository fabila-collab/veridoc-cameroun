<?php
session_start();
require_once 'db.php';

// Si l'utilisateur est déjà connecté, on le redirige vers l'admin
if (isset($_SESSION['etablissement_id'])) {
    header("Location: admin/index.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];

    if (!empty($email) && !empty($mot_de_passe)) {
        // Recherche de l'établissement par email
        $stmt = $pdo->prepare("SELECT * FROM etablissements WHERE email = ?");
        $stmt->execute([$email]);
        $etablissement = $stmt->fetch(PDO::FETCH_ASSOC);

        // Vérification du mot de passe (utiliser password_verify en production si le mdp est haché)
        // Pour les tests ou si le mdp est en texte clair dans la BD (ce qui est déconseillé) :
        if ($etablissement && ($etablissement['mot_de_passe'] === $mot_de_passe || password_verify($mot_de_passe, $etablissement['mot_de_passe']))) {
            // Connexion réussie
            $_SESSION['etablissement_id'] = $etablissement['id'];
            header("Location: admin/index.php");
            exit();
        } else {
            $message = "Email ou mot de passe incorrect.";
        }
    } else {
        $message = "Veuillez remplir tous les champs.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Veridoc</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: #fff; padding: 30px; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { text-align: center; color: #333; }
        form div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="email"], input[type="password"] { width: calc(100% - 20px); padding: 10px; border: 1px solid #ccc; border-radius: 3px; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; font-size: 16px; border-radius: 3px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .message { color: #dc3545; background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 3px; margin-bottom: 15px; }
    </style>
</head>
<body>
     <div class="login-container">
        <h1>Connexion Établissement</h1>
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div>
                <label for="email">Adresse Email :</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="mot_de_passe">Mot de passe :</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body> 
</html>
