<?php
// Fichier pour ajouter des produits au panier
require_once '../includes/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['login_redirect'] = 'Veuillez vous connecter pour ajouter des articles au panier.';
    $redirect = $_POST['redirect_url'] ?? $_SERVER['HTTP_REFERER'] ?? 'catalogue.php';
    header('Location: connexion.php?redirect=' . urlencode($redirect));
    exit();
}

// Initialiser le panier s'il n'existe pas
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Traitement de l'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_panier'])) {
    $produit_id = intval($_POST['produit_id'] ?? 0);
    $quantite   = intval($_POST['quantite'] ?? 1);
    
    if ($produit_id > 0 && $quantite > 0) {
        if (isset($_SESSION['cart'][$produit_id])) {
            $_SESSION['cart'][$produit_id] += $quantite;
        } else {
            $_SESSION['cart'][$produit_id] = $quantite;
        }
        
        $_SESSION['cart_message'] = 'Le produit a été ajouté à votre panier !';
    }
}

// Redirection
// Priorité : redirect_url envoyé dans le formulaire > referer > panier.php
$redirect_url = $_POST['redirect_url'] 
    ?? $_SERVER['HTTP_REFERER'] 
    ?? 'panier.php';

// Sécurité : on évite les redirections externes
if (strpos($redirect_url, $_SERVER['HTTP_HOST']) === false) {
    $redirect_url = 'panier.php';
}

header('Location: ' . $redirect_url);
exit();