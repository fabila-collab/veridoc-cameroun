<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['etablissement_id'])) {
    header("Location: ../index.php");
    exit();
}

$etablissement_id = $_SESSION['etablissement_id'];

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // On verifie que le document appartient bien à l'etablissement
    // et on change le statut à 'revoque'
    $stmt = $pdo->prepare("UPDATE documents SET statut = 'revoque' WHERE id = ? AND etablissement_id = ?");
    $stmt->execute([$id, $etablissement_id]);
}

header("Location: liste.php");
exit();
?>
