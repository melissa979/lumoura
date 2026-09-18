<?php
// Inclusion des fichiers nécessaires (configuration, base de données, fonctions)
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

// Démarrage de la session (obligatoire pour le panier)
session_start();

// Le script va répondre en JSON (utilisé par AJAX)
header('Content-Type: application/json');

// Vérifier que la requête est bien envoyée en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
    exit;
}

// Vérifier que l’ID du bijou est présent et valide
if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Bijou invalide'
    ]);
    exit;
}

// Récupération de l’ID du bijou
$product_id = (int) $_POST['product_id'];

// Récupération de la quantité (minimum 1)
$quantity = isset($_POST['quantity']) ? max(1, (int) $_POST['quantity']) : 1;

// Initialiser le panier s’il n’existe pas encore
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

try {
    // Vérifier que le bijou existe et récupérer ses infos
    $stmt = $pdo->prepare("
        SELECT id_produit, nom, prix, stock 
        FROM produits 
        WHERE id_produit = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    // Si le bijou n’existe pas
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Bijou non trouvé'
        ]);
        exit;
    }

    // Si le bijou est en rupture de stock
    if ($product['stock'] <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Bijou en rupture de stock'
        ]);
        exit;
    }

    // Ajouter le bijou au panier ou augmenter la quantité
    if (isset($_SESSION['cart'][$product_id])) {
        // Le bijou est déjà dans le panier
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        // Nouveau bijou dans le panier
        $_SESSION['cart'][$product_id] = [
            'id'       => $product_id,
            'nom'      => $product['nom'],   // Nom du bijou (collier, bague, etc.)
            'prix'     => $product['prix'],  // Prix du bijou
            'quantity' => $quantity           // Quantité choisie
        ];
    }

    // Calcul du nombre total d’articles dans le panier
    $cart_count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }

    // Réponse envoyée au navigateur
    echo json_encode([
        'success' => true,
        'cart_count' => $cart_count,
        'message' => 'Bijou ajouté au panier'
    ]);

} catch (PDOException $e) {
    // Erreur base de données
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de base de données'
    ]);
}
