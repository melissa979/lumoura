<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: connexion.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id_produit'])) {
    $id_produit     = (int)$_POST['id_produit'];
    $id_utilisateur = (int)$_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("
            DELETE FROM liste_envies 
            WHERE id_utilisateur = ? AND id_produit = ?
        ");
        $stmt->execute([$id_utilisateur, $id_produit]);
        $_SESSION['message'] = "Bijou retiré de vos favoris.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la suppression.";
    }
}

header('Location: liste_envies.php');
exit;