<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

/* =============================
   SÉCURITÉ : UTILISATEUR CONNECTÉ
============================= */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_utilisateur = (int) $_SESSION['user_id'];

/* =============================
   RÉCUPÉRATION DES FAVORIS
============================= */
$favoris = [];

$sql = "
    SELECT p.id, p.libelle, p.prix, p.image
    FROM produits p
    INNER JOIN favori f ON f.id_produit = p.id
    WHERE f.id_utilisateur = :id_utilisateur
    ORDER BY f.date_ajout DESC
";


$stmt = $pdo->prepare($sql);
$stmt->execute([
    'id_utilisateur' => $id_utilisateur
]);

$favoris = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Mes Favoris";
include '../includes/header.php';
?>

<!-- =============================
     AFFICHAGE
============================= -->
<div class="container py-5" style="padding-top:120px!important;">
    <h1 class="mb-4">Mes Favoris</h1>

    <?php if (!empty($favoris)) : ?>
        <div class="row g-4">
            <?php foreach ($favoris as $item) : ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <img
                            src="<?= htmlspecialchars($item['image'] ?? 'images/placeholder.png') ?>"
                            class="card-img-top"
                            alt="<?= htmlspecialchars($item['libelle']) ?>"
                        >
                        <div class="card-body text-center">
                            <h5 class="card-title">
                                <?= htmlspecialchars($item['libelle']) ?>
                            </h5>
                            <p class="card-text fw-bold">
                                <?= number_format($item['prix'], 2, ',', ' ') ?> €
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p class="text-muted">
            Aucun produit dans vos favoris pour le moment.
        </p>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
