<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// ✅ Récupère les favoris de l'utilisateur connecté (pour colorer les cœurs au chargement)
$userFavoris = [];
if (isLoggedIn()) {
    $favStmt = $pdo->prepare("SELECT id_produit FROM favori WHERE id_utilisateur = ?");
    $favStmt->execute([$_SESSION['user_id']]);
    $userFavoris = array_column($favStmt->fetchAll(PDO::FETCH_ASSOC), 'id_produit');
}

// ============================================================
// Récupération des VRAIS produits Lumoura depuis la BDD
// ============================================================

// Nouveautés : produits avec nouveaute = 1, les 6 plus récents
$stmtNew = $pdo->query("SELECT * FROM produits WHERE nouveaute = 1 ORDER BY date_ajout DESC LIMIT 6");
$nouveautes = $stmtNew->fetchAll(PDO::FETCH_ASSOC);

// Best-sellers : produits avec bestseller = 1, les 6 plus récents
$stmtBest = $pdo->query("SELECT * FROM produits WHERE bestseller = 1 ORDER BY date_ajout DESC LIMIT 6");
$bestsellers = $stmtBest->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Accueil - Lumoura Joaillerie";
include 'includes/header.php';
?>

<!-- Section Hero avec Carousel -->
<section class="hero">
    <div class="hero-slider">
        <div class="slide active" style="background-image: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), <!-- Slide 1 -->
url('https://images.unsplash.com/photo-1586878340506-af074f2ee999?auto=format&fit=crop&w=1600&q=80'); background-size: cover; background-position: center;">
            <div class="slide-content">
                <h1>L'Art de la Joaillerie</h1>
                <p>Découvrez notre collection exclusive de bijoux d'exception, façonnés avec passion et savoir-faire.</p>
                <a href="<?= SITE_URL ?>pages/catalogue.php" class="btn-primary">Découvrir la collection</a>
            </div>
        </div>
        <div class="slide" style="background-image: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)),url('https://images.unsplash.com/photo-1634390756696-505390283f61?auto=format&fit=crop&w=1600&q=80'); background-size: cover; background-position: center;">
            <div class="slide-content">
                <h1>Nouveautés 2025</h1>
                <p>Nos dernières créations en or, diamants et pierres précieuses.</p>
                <a href="<?= SITE_URL ?>pages/catalogue.php?filter=new" class="btn-primary">Voir les nouveautés</a>
            </div>
        </div>
        <div class="slide" style="background-image: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('https://images.unsplash.com/photo-1492714485642-dd6df6baafa2?auto=format&fit=crop&w=1600&q=80'); background-size: cover; background-position: center;">
            <div class="slide-content">
                <h1>Promotions Exclusives</h1>
                <p>Jusqu'à -30% sur nos pièces iconiques – offre limitée.</p>
                <a href="<?= SITE_URL ?>pages/catalogue.php?filter=promo" class="btn-primary">Voir les promotions</a>
            </div>
        </div>
    </div>
    <div class="slider-nav">
        <button class="slider-prev"><i class="fas fa-chevron-left"></i></button>
        <button class="slider-next"><i class="fas fa-chevron-right"></i></button>
    </div>
    <div class="slider-controls">
        <span class="slider-dot active"></span>
        <span class="slider-dot"></span>
        <span class="slider-dot"></span>
    </div>
</section>

<!-- Section Catégories -->
<section class="container">
    <div class="section-title">
        <h2>Nos Collections</h2>
    </div>
    <div class="categories-grid">
        <div class="category-card">
            <div class="category-image">
                <img src="https://sn.jumia.is/unsafe/fit-in/500x500/filters:fill(white)/product/45/308121/1.jpg?8252" alt="Bijoux Femme">
            </div>
            <div class="category-content">
                <h3>Pour Elle</h3>
                <p>Créations délicates et lumineuses</p>
                <a href="<?= SITE_URL ?>pages/catalogue.php?category=Femme" class="btn-primary">Découvrir</a>
            </div>
        </div>
        <div class="category-card">
            <div class="category-image">
                <img src="https://ci.jumia.is/unsafe/fit-in/500x500/filters:fill(white)/product/99/749662/1.jpg?6877" alt="Bijoux Homme">
            </div>
            <div class="category-content">
                <h3>Pour Lui</h3>
                <p>Pièces raffinées et intemporelles</p>
                <a href="<?= SITE_URL ?>pages/catalogue.php?category=Homme" class="btn-primary">Découvrir</a>
            </div>
        </div>
        <div class="category-card">
            <div class="category-image">
                <img src="https://m.media-amazon.com/images/I/71naqPXNxXL._AC_UY1000_.jpg" alt="Bijoux Unisexe">
            </div>
            <div class="category-content">
                <h3>Unisexe</h3>
                <p>Élégance pour tous les styles</p>
                <a href="<?= SITE_URL ?>pages/catalogue.php?category=Unisexe" class="btn-primary">Découvrir</a>
            </div>
        </div>
    </div>
</section>

<?php
// ============================================================
// Fonction helper pour générer le bouton cœur
// ============================================================
function wishlistBtn(int $id, array $userFavoris): string {
    $isFav = in_array($id, $userFavoris);
    if (isLoggedIn()) {
        $class = 'btn-wishlist' . ($isFav ? ' active' : '');
        $icon  = $isFav ? 'fas fa-heart' : 'far fa-heart';
        $title = $isFav ? 'Retirer des favoris' : 'Ajouter aux favoris';
        return "<button class=\"$class\" data-product-id=\"$id\" title=\"$title\">
                    <i class=\"$icon\"></i>
                </button>";
    } else {
        return "<a href=\"" . SITE_URL . "pages/connexion.php\" class=\"btn-wishlist\" title=\"Connectez-vous\">
                    <i class=\"far fa-heart\"></i>
                </a>";
    }
}

// ============================================================
// Fonction helper pour générer une carte produit Lumoura
// (remplace les 12 blocs codés en dur Cartier/Tiffany/etc.)
// ============================================================
function renderProductCard(array $product, array $userFavoris, string $context = 'default'): void {
    $id          = (int)$product['id_produit'];
    $nom         = htmlspecialchars($product['nom']);
    $marque      = htmlspecialchars($product['marque'] ?? 'LUMOURA');
    $desc        = htmlspecialchars($product['description_courte'] ?? $product['description'] ?? '');
    $prix        = (float)$product['prix'];
    $discount    = (float)($product['promotion_pourcentage'] ?? 0);
    $finalPrix   = $discount > 0 ? $prix * (1 - $discount / 100) : $prix;
    $image       = $product['image_url'] ?: 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?auto=format&fit=crop&w=800&q=80';

    // Badge selon le contexte d'affichage pour éviter les badges incorrects
    $badge = '';
    if ($discount > 0) {
        $badge = '<span class="product-badge discount">-' . (int)$discount . '%</span>';
    } elseif ($context === 'bestseller') {
        $badge = '<span class="product-badge bestseller">BEST-SELLER</span>';
    } elseif ($context === 'nouveaute') {
        $badge = '<span class="product-badge new">NEW</span>';
    } elseif ($product['nouveaute']) {
        $badge = '<span class="product-badge new">NEW</span>';
    } elseif ($product['bestseller']) {
        $badge = '<span class="product-badge bestseller">BEST-SELLER</span>';
    }
    ?>
    <div class="product-card">
        <?= $badge ?>
        <div class="product-image">
            <img src="<?= htmlspecialchars($image) ?>" alt="<?= $nom ?>" loading="lazy">
        </div>
        <div class="product-info">
            <div class="product-brand"><?= $marque ?></div>
            <h3 class="product-name"><a href="<?= SITE_URL ?>pages/produit.php?id=<?= $id ?>"><?= $nom ?></a></h3>
            <p class="product-description"><?= $desc ?></p>
          <div class="product-pricing">
                   <?php if ($discount > 0): ?>
                    <div class="original-price"><?= formatPrice($prix) ?></div>
                          <?php endif; ?>
                             <div class="current-price"><?= formatPrice($finalPrix) ?></div> 
                              </div>
                  <div class="product-actions">
                <?php if ((int)$product['stock'] > 0): ?>
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="<?= SITE_URL ?>pages/ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="<?= $id ?>">
                            <input type="hidden" name="quantite" value="1">
                            <input type="hidden" name="redirect_url" value="<?= SITE_URL ?>pages/panier.php">
                            <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>pages/connexion.php?redirect=index.php" class="btn-add-cart disabled">
                            <i class="fas fa-lock"></i> Connectez-vous
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="stock-rupture">Rupture</span>
                <?php endif; ?>
                <?= wishlistBtn($id, $userFavoris) ?>
            </div>
        </div>
    </div>
    <?php
}
?>

<!-- Section Nouveautés -->
<section class="container">
    <div class="section-title">
        <h2>Nos Nouveautés</h2>
    </div>
    <?php if (!empty($nouveautes)): ?>
        <div class="products-grid">
            <?php foreach ($nouveautes as $product): renderProductCard($product, $userFavoris, 'nouveaute'); endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center;color:#999;">Aucune nouveauté pour le moment.</p>
    <?php endif; ?>
</section>

<!-- Section Best-sellers -->
<section class="container">
    <div class="section-title">
        <h2>Best-sellers</h2>
    </div>
    <?php if (!empty($bestsellers)): ?>
        <div class="products-grid">
            <?php foreach ($bestsellers as $product): renderProductCard($product, $userFavoris, 'bestseller'); endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center;color:#999;">Aucun best-seller pour le moment.</p>
    <?php endif; ?>
</section>

<!-- Section À Propos -->
<section class="about-section">
    <div class="container">
        <div class="about-content">
            <h2>L'Histoire d'Éclat d'Or</h2>
<p>Depuis 1920, Éclat d'Or façonne des bijoux d'exception qui traversent les époques. 
Chaque pièce est une œuvre d'art, alliant tradition artisanale et design contemporain.</p>
            <div class="about-features">
                <div class="feature">
                    <i class="fas fa-gem"></i>
                    <h4>Pierres d'Exception</h4>
                    <p>Sélectionnées pour leur pureté et leur éclat</p>
                </div>
                <div class="feature">
                    <i class="fas fa-hand-sparkles"></i>
                    <h4>Savoir-faire Artisanal</h4>
                    <p>Chaque bijou est travaillé à la main</p>
                </div>
                <div class="feature">
                    <i class="fas fa-shipping-fast"></i>
                    <h4>Livraison Soignée</h4>
                    <p>Expédition sécurisée sous 24-48h</p>
                </div>
                <div class="feature">
                    <i class="fas fa-heart"></i>
                    <h4>Excellence Garantie</h4>
                    <p>Satisfaction ou retour offert 30 jours</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>