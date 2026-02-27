<?php
// ══════════════════════════════════════════
//  LUMOURA — Annulation de commande
//  pages/annuler_commande.php
// ══════════════════════════════════════════
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: connexion.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirmer_annulation'])) {
    header('Location: mes_commandes.php');
    exit();
}

$id_commande = (int)($_POST['id_commande'] ?? 0);
if (!$id_commande) {
    header('Location: mes_commandes.php');
    exit();
}

function normaliserStatut($s) {
    $s = strtolower(trim($s));
    $s = str_replace(' ', '_', $s);
    $map = [
        'en_attente' => 'en_attente', 'en attente' => 'en_attente',
        'attente' => 'en_attente', 'pending' => 'en_attente',
        'payee' => 'payee', 'payée' => 'payee', 'paid' => 'payee',
        'annulee' => 'annulee', 'annulée' => 'annulee', 'cancelled' => 'annulee',
        'expediee' => 'expediee', 'expédiée' => 'expediee', 'shipped' => 'expediee',
        'livree' => 'livree', 'livrée' => 'livree', 'delivered' => 'livree',
    ];
    return $map[$s] ?? $s;
}

try {
    // Récupérer la commande (appartient à l'utilisateur)
    $stmt = $pdo->prepare("SELECT * FROM commandes WHERE id_commande = ? AND id_utilisateur = ?");
    $stmt->execute([$id_commande, $_SESSION['user_id']]);
    $commande = $stmt->fetch();

    if (!$commande) {
        $_SESSION['commande_message'] = 'Commande introuvable.';
        header('Location: mes_commandes.php');
        exit();
    }

    $statut_norm = normaliserStatut($commande['statut']);

    if ($statut_norm !== 'en_attente') {
        $_SESSION['commande_message'] = 'Cette commande ne peut plus être annulée (statut : ' . $statut_norm . ').';
        header('Location: mes_commandes.php');
        exit();
    }

    $pdo->beginTransaction();

    // Mettre à jour le statut
    $pdo->prepare("UPDATE commandes SET statut = 'annulee' WHERE id_commande = ?")
        ->execute([$id_commande]);

    // Remettre le stock
    $stmtD = $pdo->prepare("SELECT id_produit, quantite FROM details_commande WHERE id_commande = ?");
    $stmtD->execute([$id_commande]);
    foreach ($stmtD->fetchAll() as $d) {
        $pdo->prepare("UPDATE produits SET stock = stock + ? WHERE id_produit = ?")
            ->execute([$d['quantite'], $d['id_produit']]);
    }

    $pdo->commit();

    $_SESSION['commande_message'] = 'success|Commande #' . $commande['numero_commande'] . ' annulée avec succès.';
    header('Location: mes_commandes.php');
    exit();

} catch(Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['commande_message'] = 'error|Erreur : ' . $e->getMessage();
    header('Location: mes_commandes.php');
    exit();
}