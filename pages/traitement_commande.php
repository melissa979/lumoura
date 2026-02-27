<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Vérif connexion (sans isLoggedIn())
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['login_redirect'] = 'Veuillez vous connecter pour passer commande.';
    header('Location: connexion.php');
    exit();
}

// Ici : traitement futur du paiement (Stripe, PayPal, etc.)
// Pour l'instant : on vide le panier et on affiche un message de succès

unset($_SESSION['cart']);
$_SESSION['commande_message'] = 'Votre commande a été enregistrée avec succès ! (Paiement simulé)';

header('Location: commande_confirmation.php');
exit();
?>