<?php
// ============================================
// includes/submit_review.php
// ============================================
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header('Location: ../pages/connexion.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/catalogue.php');
    exit();
}

$id_produit     = isset($_POST['id_produit'])  ? (int) $_POST['id_produit']  : 0;
$note           = isset($_POST['note'])        ? (int) $_POST['note']        : 0;
$commentaire    = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';
$id_utilisateur = (int) $_SESSION['user_id'];

if ($id_produit <= 0 || $note < 1 || $note > 5) {
    header("Location: ../pages/produit.php?id=$id_produit&avis=error");
    exit();
}

// Vérifie que le produit existe
$checkProd = $pdo->prepare("SELECT id_produit FROM produits WHERE id_produit = ? LIMIT 1");
$checkProd->execute([$id_produit]);
if (!$checkProd->fetch()) {
    header('Location: ../pages/catalogue.php');
    exit();
}

try {
    // ✅ NOUVELLE VERSION : On autorise plusieurs avis (on ne supprime plus l'ancien)
    // Insertion directe d'un nouvel avis
    $stmt = $pdo->prepare("
        INSERT INTO avis (id_utilisateur, id_produit, note, commentaire, date_avis, statut)
        VALUES (?, ?, ?, ?, NOW(), 'approuve')
    ");
    $stmt->execute([$id_utilisateur, $id_produit, $note, $commentaire]);

    header("Location: ../pages/produit.php?id=$id_produit&avis=ok");
    exit();

} catch (PDOException $e) {
    error_log('submit_review.php — Erreur : ' . $e->getMessage());
    header("Location: ../pages/produit.php?id=$id_produit&avis=error");
    exit();
}
?>