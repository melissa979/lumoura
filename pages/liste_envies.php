<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "pages/connexion.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Récupère les bijoux favoris de l'utilisateur
$favoris = [];
try {
    $stmt = $pdo->prepare("
        SELECT p.*
        FROM favori f
        JOIN produits p ON p.id_produit = f.id_produit
        WHERE f.id_utilisateur = ?
        ORDER BY f.id DESC
    ");
    $stmt->execute([$user_id]);
    $favoris = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $favoris = [];
}

$pageTitle = "Ma Liste d'envies - Éclat d'Or";
include '../includes/header.php';
?>

<div class="container" style="margin-top: 120px; margin-bottom: 80px;">
    <div class="catalogue-header">
        <h1 class="page-title"><i class="fas fa-heart"></i> Ma Liste d'envies</h1>
        <p class="page-subtitle">
            <?= count($favoris) ?> bijou<?= count($favoris) > 1 ? 'x' : '' ?> sauvegardé<?= count($favoris) > 1 ? 's' : '' ?>
        </p>
    </div>

    <?php if (!empty($favoris)): ?>
        <div class="products-grid">
            <?php foreach ($favoris as $product):
                $price       = (float)$product['prix'];
                $discount    = (float)($product['promotion_pourcentage'] ?? 0);
                $final_price = $discount > 0 ? $price * (1 - $discount / 100) : $price;
                $image       = $product['image_url'] ?: 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?auto=format&fit=crop&w=800&q=80';
            ?>
                <div class="product-card">
                    <?php if ($discount > 0): ?>
                        <span class="product-badge discount">−<?= (int)$discount ?>%</span>
                    <?php endif; ?>

                    <button class="btn-wishlist-card active"
                            data-product-id="<?= $product['id_produit'] ?>"
                            title="Retirer des favoris">
                        <i class="fas fa-heart"></i>
                    </button>

                    <div class="product-image">
                        <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($product['nom']) ?>" loading="lazy">
                        <div class="product-overlay">
                            <a href="produit.php?id=<?= $product['id_produit'] ?>" class="view-details">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                        </div>
                    </div>

                    <div class="product-info">
                        <div class="product-meta">
                            <span class="product-brand"><?= htmlspecialchars($product['marque'] ?? '') ?></span>
                        </div>
                        <h3 class="product-name">
                            <a href="produit.php?id=<?= $product['id_produit'] ?>"><?= htmlspecialchars($product['nom']) ?></a>
                        </h3>
                        <div class="product-pricing">
                            <?php if ($discount > 0): ?>
                                <div class="original-price"><?= formatPrice($price) ?></div>
                            <?php endif; ?>
                            <div class="current-price"><?= formatPrice($final_price) ?></div>
                        </div>
                        <div class="product-actions">
                            <?php if ($product['stock'] > 0): ?>
                                <form method="POST" action="ajouter_au_panier.php" class="add-cart-form">
                                    <input type="hidden" name="produit_id" value="<?= $product['id_produit'] ?>">
                                    <input type="hidden" name="quantite" value="1">
                                    <input type="hidden" name="redirect_url" value="liste_envies.php">
                                    <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                        <i class="fas fa-cart-plus"></i> Ajouter
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="stock-rupture">Rupture</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-products">
            <i class="fas fa-heart fa-3x" style="color:#eee;"></i>
            <h3>Votre liste d'envies est vide</h3>
            <p>Ajoutez des bijoux à vos favoris en cliquant sur le cœur depuis le catalogue.</p>
            <a href="catalogue.php" class="btn-primary">Découvrir la collection</a>
        </div>
    <?php endif; ?>
</div>

<style>
.btn-wishlist-card {
    position: absolute; top: 12px; right: 12px; z-index: 10;
    width: 36px; height: 36px; background: rgba(255,255,255,0.92);
    border: none; border-radius: 50%; display: flex; align-items: center;
    justify-content: center; font-size: 15px; color: #e74c3c; cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12); transition: all 0.25s ease;
}
.btn-wishlist-card:hover { transform: scale(1.15); background: #fff0f0; }
.product-card { position: relative; }
</style>

<script>
document.querySelectorAll('.btn-wishlist-card').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        const idProduit = this.dataset.productId;
        const card = this.closest('.product-card');

        const formData = new FormData();
        formData.append('id_produit', idProduit);

        fetch('../includes/toggle_wishlist.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.action === 'removed') {
                    card.style.transition = 'opacity 0.3s ease';
                    card.style.opacity = '0';
                    setTimeout(() => { card.remove(); if (!document.querySelector('.product-card')) location.reload(); }, 300);
                }
            });
    });
});
</script>

<?php include '../includes/footer.php'; ?>