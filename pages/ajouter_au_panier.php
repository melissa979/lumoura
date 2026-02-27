<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Vérifier si l'utilisateur est connecté (optionnel selon ton choix)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_redirect'] = 'Veuillez vous connecter pour ajouter des articles au panier.';
    $redirect = $_POST['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? 'catalogue.php';
    header('Location: connexion.php?redirect=' . urlencode($redirect));
    exit();
}

// Initialiser le panier s'il n'existe pas
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Traitement de l'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['produit_id'])) {
    $produit_id = intval($_POST['produit_id']);
    $quantite   = intval($_POST['quantite'] ?? 1);

    if ($produit_id > 0 && $quantite > 0) {
        if (isset($_SESSION['cart'][$produit_id])) {
            $_SESSION['cart'][$produit_id] += $quantite;
        } else {
            $_SESSION['cart'][$produit_id] = $quantite;
        }

        $_SESSION['cart_message'] = 'Le produit a été ajouté à votre panier !';
    }

    // Redirection selon le bouton cliqué
    if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
        $redirect = $_POST['redirect'];
        // Sécurité : on accepte seulement des URLs internes
        if (strpos($redirect, $_SERVER['HTTP_HOST']) !== false || $redirect === 'panier.php' || $redirect === 'catalogue.php') {
            header("Location: $redirect");
            exit;
        }
    }

    // Par défaut : retour catalogue avec message
    header("Location: catalogue.php?added=1");
    exit;
}

// Si rien n'est soumis → redirection par sécurité
header("Location: catalogue.php");
exit;
?>