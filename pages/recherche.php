<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Récupération du terme de recherche
$q = trim($_GET['q'] ?? '');

if (empty($q)) {
    header("Location: catalogue.php");
    exit;
}

// On cherche d'abord si ça matche EXACTEMENT un produit (nom ou référence)
$stmt = $pdo->prepare("
    SELECT id_produit 
    FROM produits 
    WHERE nom = :exact OR reference = :exact
    LIMIT 1
");
$stmt->execute([':exact' => $q]);
$exactMatch = $stmt->fetch();

if ($exactMatch) {
    // Redirection directe vers le produit si match exact
    header("Location: produit.php?id=" . $exactMatch['id_produit']);
    exit;
}

// Sinon → recherche large
$searchTerm = '%' . $q . '%';
$params = [':search' => $searchTerm];

$query = "
    SELECT p.*, c.nom_categorie
    FROM produits p 
    LEFT JOIN categories c ON p.id_categorie = c.id_categorie 
    WHERE p.nom LIKE :search 
       OR p.marque LIKE :search 
       OR p.description LIKE :search 
       OR p.description_courte LIKE :search
    ORDER BY p.date_ajout DESC
    LIMIT 20
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_products = count($products);

$pageTitle = "Recherche : " . htmlspecialchars($q);
include '../includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Résultats pour « <?php echo htmlspecialchars($q); ?> »</h1>

    <?php if ($total_products > 0): ?>
        <p class="lead mb-5"><?php echo $total_products; ?> résultat<?php echo $total_products > 1 ? 's' : ''; ?></p>

        <div class="products-grid">
            <?php foreach ($products as $product): 
                $price = $product['prix'];
                $discount = $product['promotion_pourcentage'];
                $final_price = $discount > 0 ? $price * (1 - $discount / 100) : $price;
                $image = $product['image_url'] ?: 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?auto=format&fit=crop&w=800&q=80';
            ?>
                <div class="product-card">
                    <?php if ($discount > 0): ?>
                        <span class="product-badge discount">−<?php echo round($discount); ?>%</span>
                    <?php elseif ($product['nouveaute']): ?>
                        <span class="product-badge new">Nouveau</span>
                    <?php elseif ($product['bestseller']): ?>
                        <span class="product-badge bestseller">Best-seller</span>
                    <?php endif; ?>

                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" loading="lazy">
                        <div class="product-overlay">
                            <a href="produit.php?id=<?php echo $product['id_produit']; ?>" class="view-details">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                        </div>
                    </div>

                    <div class="product-info">
                        <div class="product-meta">
                            <span class="product-brand"><?php echo htmlspecialchars($product['marque'] ?: 'Lumoura'); ?></span>
                            <span class="product-category"><?php echo htmlspecialchars($product['nom_categorie'] ?? 'Bijoux'); ?></span>
                        </div>

                        <h3 class="product-name">
                            <a href="produit.php?id=<?php echo $product['id_produit']; ?>">
                                <?php echo htmlspecialchars($product['nom']); ?>
                            </a>
                        </h3>

                        <div class="product-pricing">
                            <?php if ($discount > 0): ?>
                                <div class="original-price"><?php echo formatPrice($price); ?></div>
                            <?php endif; ?>
                            <div class="current-price"><?php echo formatPrice($final_price); ?></div>
                        </div>

                        <div class="product-actions">
                            <?php if ($product['stock'] > 0): ?>
                                <button class="btn-add-cart" data-id="<?php echo $product['id_produit']; ?>">
                                    <i class="fas fa-cart-plus"></i> Ajouter
                                </button>
                            <?php else: ?>
                                <button class="btn-add-cart disabled" disabled>Indisponible</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-products text-center py-5">
            <i class="fas fa-search fa-5x text-muted mb-4"></i>
            <h3>Aucun bijou trouvé pour « <?php echo htmlspecialchars($q); ?> »</h3>
            <p>Essayez un autre mot-clé ou découvrez notre collection complète.</p>
            <a href="catalogue.php" class="btn btn-primary mt-3">Voir tous les bijoux</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>