<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

$is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

if (!isLoggedIn()) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'not_logged_in']);
        exit;
    }
    header('Location: connexion.php?redirect=liste_envies.php');
    exit;
}

$id_utilisateur = $_SESSION['user_id'];
$id_produit     = isset($_POST['produit_id']) ? (int)$_POST['produit_id'] : 0;

if (!$id_produit) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'invalid_product']);
        exit;
    }
    header('Location: ../index.php');
    exit;
}

try {
    // Vérifier si déjà en favoris
    $stmt_check = $pdo->prepare("SELECT id FROM liste_envies WHERE id_utilisateur = :uid AND id_produit = :pid");
    $stmt_check->execute([':uid' => $id_utilisateur, ':pid' => $id_produit]);
    $exists = $stmt_check->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("DELETE FROM liste_envies WHERE id_utilisateur = :uid AND id_produit = :pid");
        $stmt->execute([':uid' => $id_utilisateur, ':pid' => $id_produit]);
        $action  = 'removed';
        $message = 'Retiré des favoris';
    } else {
        $stmt = $pdo->prepare("INSERT INTO liste_envies (id_utilisateur, id_produit, date_ajout) VALUES (:uid, :pid, NOW())");
        $stmt->execute([':uid' => $id_utilisateur, ':pid' => $id_produit]);
        $action  = 'added';
        $message = 'Ajouté aux favoris !';
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['action' => $action, 'message' => $message]);
        exit;
    }

    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../index.php';
    header('Location: ' . $redirect . '?msg=' . urlencode($message));
    exit;

} catch (PDOException $e) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'db_error']);
        exit;
    }
    header('Location: ../index.php?error=1');
    exit;
}
?>