<?php
// ===============================
// SCRIPT AJAX : AJOUT / RETRAIT DES FAVORIS
// POUR ÉCLAT D'OR (SITE DE BIJOUX)
// ===============================

require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

session_start();

// Indique que la réponse sera en JSON
header('Content-Type: application/json');

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifie que l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Connectez-vous pour ajouter un bijou aux favoris']);
    exit;
}

// Vérifie que l'ID du produit est valide
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bijou invalide']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = (int)$_POST['product_id'];

try {
    // Vérifie si le bijou est déjà dans les favoris
    $stmt = $pdo->prepare("SELECT id_favori FROM favoris WHERE id_utilisateur = ? AND id_produit = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Si déjà favori → retirer
        $stmt = $pdo->prepare("DELETE FROM favoris WHERE id_utilisateur = ? AND id_produit = ?");
        $stmt->execute([$user_id, $product_id]);
        $is_favorite = false;
    } else {
        // Si pas encore favori → ajouter
        $stmt = $pdo->prepare("INSERT INTO favoris (id_utilisateur, id_produit) VALUES (?, ?)");
        $stmt->execute([$user_id, $product_id]);
        $is_favorite = true;
    }
    
    // Réponse JSON envoyée au front-end
    echo json_encode([
        'success' => true,
        'is_favorite' => $is_favorite,
        'message' => $is_favorite ? 'Bijou ajouté aux favoris' : 'Bijou retiré des favoris'
    ]);
    
} catch (PDOException $e) {
    // En cas d'erreur avec la base
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}
