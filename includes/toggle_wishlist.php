<?php
// ============================================
// toggle_wishlist.php — Ajouter / Retirer un favori
// Appelé en AJAX (POST) depuis script.js
// À placer dans : lumoura/includes/toggle_wishlist.php
// Table utilisée : favori (id, id_utilisateur, id_categorie, id_produit, date_ajout)
// ============================================

require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json; charset=utf-8');

// ——— Sécurité : refus si pas connecté ———
if (!isLoggedIn()) {
    echo json_encode([
        'success'  => false,
        'message'  => 'Non connecté',
        'redirect' => 'connexion.php'
    ]);
    exit();
}

// ——— Refus si méthode invalide ———
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// ——— Validation des données ———
$id_produit     = isset($_POST['id_produit']) ? (int) $_POST['id_produit'] : 0;
$id_utilisateur = (int) $_SESSION['user_id'];

if ($id_produit <= 0) {
    echo json_encode(['success' => false, 'message' => 'Identifiant produit invalide']);
    exit();
}

try {
    // ——— Vérifie que le produit existe et récupère sa catégorie ———
    $checkProd = $pdo->prepare("SELECT id_produit, id_categorie FROM produits WHERE id_produit = ? LIMIT 1");
    $checkProd->execute([$id_produit]);
    $produit = $checkProd->fetch();

    if (!$produit) {
        echo json_encode(['success' => false, 'message' => 'Produit introuvable']);
        exit();
    }

    $id_categorie = $produit['id_categorie'];

    // ——— Vérifie si déjà en favori (table : favori) ———
    $stmt = $pdo->prepare(
        "SELECT id FROM favori WHERE id_utilisateur = ? AND id_produit = ? LIMIT 1"
    );
    $stmt->execute([$id_utilisateur, $id_produit]);
    $existe = $stmt->fetch();

    if ($existe) {
        // ——— SUPPRESSION ———
        $pdo->prepare(
            "DELETE FROM favori WHERE id_utilisateur = ? AND id_produit = ?"
        )->execute([$id_utilisateur, $id_produit]);

        echo json_encode([
            'success' => true,
            'action'  => 'removed',
            'message' => 'Retiré de vos favori'
        ]);

    } else {
        // ——— AJOUT ———
        $pdo->prepare(
            "INSERT INTO favori (id_utilisateur, id_categorie, id_produit, date_ajout)
             VALUES (?, ?, ?, NOW())"
        )->execute([$id_utilisateur, $id_categorie, $id_produit]);

        echo json_encode([
            'success' => true,
            'action'  => 'added',
            'message' => 'Ajouté à vos favori'
        ]);
    }

} catch (PDOException $e) {
    error_log('toggle_wishlist.php — Erreur PDO : ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur, veuillez réessayer'
    ]);
}