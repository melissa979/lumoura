<?php
// ============================================
// pages/ajouter_au_panier.php
// ============================================
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'pages/connexion.php');
    exit();
}

// ——— Détermine la page de retour ———
// Priorité : redirect_url POST → HTTP_REFERER → panier.php
$redirect = '';
if (!empty($_POST['redirect_url'])) {
    $redirect = $_POST['redirect_url'];
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $redirect = $_SERVER['HTTP_REFERER'];
} else {
    $redirect = SITE_URL . 'pages/panier.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_panier'])) {
    $produit_id = (int)($_POST['produit_id'] ?? 0);
    $quantite   = (int)($_POST['quantite']   ?? 1);

    if ($produit_id > 0 && $quantite > 0) {
        try {
            // Vérifie stock
            $stmt = $pdo->prepare("SELECT id_produit, stock FROM produits WHERE id_produit = ?");
            $stmt->execute([$produit_id]);
            $produit = $stmt->fetch();

            if ($produit && $produit['stock'] > 0) {
                // Déjà dans le panier ?
                $checkStmt = $pdo->prepare(
                    "SELECT id_panier, quantite FROM panier WHERE id_utilisateur = ? AND id_produit = ?"
                );
                $checkStmt->execute([$_SESSION['user_id'], $produit_id]);
                $existing = $checkStmt->fetch();

                if ($existing) {
                    $pdo->prepare(
                        "UPDATE panier SET quantite = quantite + ? WHERE id_utilisateur = ? AND id_produit = ?"
                    )->execute([$quantite, $_SESSION['user_id'], $produit_id]);
                } else {
                    $pdo->prepare(
                        "INSERT INTO panier (id_utilisateur, id_produit, quantite, date_ajout) VALUES (?, ?, ?, NOW())"
                    )->execute([$_SESSION['user_id'], $produit_id, $quantite]);
                }

                $_SESSION['cart_message'] = 'Bijou ajouté à votre panier !';

            } else {
                $_SESSION['cart_error'] = 'Produit introuvable ou en rupture de stock.';
            }
        } catch (PDOException $e) {
            $_SESSION['cart_error'] = 'Erreur : ' . $e->getMessage();
        }
    }
}

// ✅ Retour à la page d'origine (produit, index, catalogue…)
header('Location: ' . $redirect);
exit();