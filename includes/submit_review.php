<?php
session_start();
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

// Force l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Accès direct interdit.");
}

// Vérif connexion
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté.";
    header("Location: ../pages/connexion.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

// Validation
if ($product_id <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
    $_SESSION['error'] = "Veuillez remplir tous les champs correctement.";
    header("Location: ../pages/produit.php?id=$product_id");
    exit();
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérif doublon
   
        $stmt = $pdo->prepare("
            INSERT INTO avis 
            (id_utilisateur, id_produit, note, commentaire, date_avis, statut) 
            VALUES (?, ?, ?, ?, NOW(), 'en_attente')
        ");
        $stmt->execute([$user_id, $product_id, $rating, $comment]);

        $_SESSION['success'] = "Votre avis a été soumis ! Il sera visible après modération.";
    }
 catch (PDOException $e) {
    $_SESSION['error'] = "Erreur base de données : " . $e->getMessage();
    error_log("Erreur SQL avis : " . $e->getMessage());
}

header("Location: ../pages/produit.php?id=$product_id");
exit();
?>