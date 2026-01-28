<?php
// Pas de session_start() ici (déjà dans config.php)

// Ordre important : config en premier (définit DB_NAME + session)
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "Accueil - Lumoura Joaillerie";
include 'includes/header.php';
?>

<link rel= "stylesheet" href= "assets/css/style.css">

<!-- Section Hero avec Carousel -->
<section class="hero">
    <div class="hero-slider">
        <div class="slide active" style="background-image: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('https://www.goldmarket.fr/wp-content/uploads/2025/07/12f534e7thumbnail.jpeg'); background-size: cover; background-position: center;">
            <div class="slide-content">
                <h1>L'Art de la Joaillerie</h1>
                <p>Découvrez notre collection exclusive de bijoux d'exception, façonnés avec passion et savoir-faire.</p>
                <a href="catalogue.php" class="btn-primary">Découvrir la collection</a>
            </div>
        </div>
        
        <div class="slide" style="background-image: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('https://www.goldmarket.fr/wp-content/uploads/2025/09/4d549417thumbnail.jpeg'); background-size: cover; background-position: center;">
            <div class="slide-content">
                <h1>Nouveautés 2025</h1>
                <p>Nos dernières créations en or, diamants et pierres précieuses.</p>
                <a href="catalogue.php?filter=new" class="btn-primary">Voir les nouveautés</a>
            </div>
        </div>
        
        <div class="slide" style="background-image: linear-gradient(rgba(0,0,0,0.45), rgba(0,0,0,0.45)), url('https://www.goldmarket.fr/wp-content/uploads/2024/11/03f5d621thumbnail-1024x512.jpeg'); background-size: cover; background-position: center;">
            <div class="slide-content">
                <h1>Promotions Exclusives</h1>
                <p>Jusqu'à -30% sur nos pièces iconiques – offre limitée.</p>
                <a href="catalogue.php?filter=promo" class="btn-primary">Voir les promotions</a>
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
                <a href="catalogue.php?category=Femme" class="btn-primary">Découvrir</a>
            </div>
        </div>
        
        <div class="category-card">
            <div class="category-image">
                <img src="https://ci.jumia.is/unsafe/fit-in/500x500/filters:fill(white)/product/99/749662/1.jpg?6877" alt="Bijoux Homme">
            </div>
            <div class="category-content">
                <h3>Pour Lui</h3>
                <p>Pièces raffinées et intemporelles</p>
                <a href="catalogue.php?category=Homme" class="btn-primary">Découvrir</a>
            </div>
        </div>
        
        <div class="category-card">
            <div class="category-image">
                <img src="https://m.media-amazon.com/images/I/71naqPXNxXL._AC_UY1000_.jpg" alt="Bijoux Unisexe">
            </div>
            <div class="category-content">
                <h3>Unisexe</h3>
                <p>Élégance pour tous les styles</p>
                <a href="catalogue.php?category=Unisexe" class="btn-primary">Découvrir</a>
            </div>
        </div>
    </div>
</section>

<!-- Section Nouveautés -->
<section class="container">
    <div class="section-title">
        <h2>Nos Nouveautés</h2>
    </div>
    
    <div class="products-grid">  <!-- Même grille que Best-sellers -->
        <!-- 1. Cartier Trinity Ring -->
        <div class="product-card">
            <span class="product-badge new">NEW</span>
            <div class="product-image">
                <img src="https://www.luxe-em.com/cdn/shop/files/001copie.jpg?v=1750064676" alt="Bijoux Femme">
            </div>
            <div class="product-info">
                <div class="product-brand">CARTIER</div>
                <h3 class="product-name">Trinity Ring</h3>
                <p class="product-description">Bague trois ors entrelacés – symbole d'amour, fidélité et amitié depuis 1924</p>
                <div class="product-price">
                    <span class="price-current">1450 €</span>
                </div>
                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="pages/ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="1"> <!-- ← REMPLACE PAR VRAI ID -->
                            <input type="hidden" name="quantite" value="1">
                            <input type="hidden" name="redirect_url" value="pages/panier.php">
                            <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="pages/connexion.php?redirect=index.php" class="btn-add-cart disabled">
                            <i class="fas fa-lock"></i> Connectez-vous
                        </a>
                    <?php endif; ?>
                    <button class="btn-wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Tiffany & Co. HardWear Bracelet -->
        <div class="product-card">
            <span class="product-badge discount">-10%</span>
            <div class="product-image">
                <img src="https://media.tiffany.com/is/image/tco/60451095_BLT_ALT3X1" alt="Bijoux Femme">
            </div>
            <div class="product-info">
                <div class="product-brand">TIFFANY & CO.</div>
                <h3 class="product-name">HardWear Bracelet</h3>
                <p class="product-description">Bracelet maillons hexagonaux en or jaune 18K – audace et force new-yorkaise</p>
                <div class="product-price">
                    <span class="price-original">900 €</span>
                    <span class="price-current">810 €</span>
                </div>
                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="pages/ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="2"> <!-- ← REMPLACE PAR VRAI ID -->
                            <input type="hidden" name="quantite" value="1">
                            <input type="hidden" name="redirect_url" value="pages/panier.php">
                            <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="pages/connexion.php?redirect=index.php" class="btn-add-cart disabled">
                            <i class="fas fa-lock"></i> Connectez-vous
                        </a>
                    <?php endif; ?>
                    <button class="btn-wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. Van Cleef & Arpels Frivole Ring -->
        <div class="product-card">
            <div class="product-image">
                <img src="https://www.vancleefarpels.com/content/dam/rcq/vca/18/93/76/7/1893767.png" alt="Bijoux Femme">
            </div>
            <div class="product-info">
                <div class="product-brand">VAN CLEEF & ARPELS</div>
                <h3 class="product-name">Frivole Ring</h3>
                <p class="product-description">Bague or jaune et diamants taille poire – légèreté et féminité absolue</p>
                <div class="product-price">
                    <span class="price-current">1 800 €</span>
                </div>
                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="pages/ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="3"> <!-- ← REMPLACE PAR VRAI ID -->
                            <input type="hidden" name="quantite" value="1">
                            <input type="hidden" name="redirect_url" value="pages/panier.php">
                            <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="pages/connexion.php?redirect=index.php" class="btn-add-cart disabled">
                            <i class="fas fa-lock"></i> Connectez-vous
                        </a>
                    <?php endif; ?>
                    <button class="btn-wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. Bulgari Divas' Dream Necklace -->
        <div class="product-card">
            <span class="product-badge promo">PROMO</span>
            <div class="product-image">
                <img src="https://media.bulgari.com/image/upload/c_pad,h_851,w_1090/q_auto/f_auto/459602.png" alt="Bijoux Femme">
            </div>
            <div class="product-info">
                <div class="product-brand">BULGARI</div>
                <h3 class="product-name">Divas' Dream Necklace</h3>
                <p class="product-description">Collier éventail en or rose et diamants – glamour et sensualité romaine</p>
                <div class="product-price">
                    <span class="price-original">1 200 €</span>
                    <span class="price-current">920 €</span>
                </div>
                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="pages/ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="4"> <!-- ← REMPLACE PAR VRAI ID -->
                            <input type="hidden" name="quantite" value="1">
                            <input type="hidden" name="redirect_url" value="pages/panier.php">
                            <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="pages/connexion.php?redirect=index.php" class="btn-add-cart disabled">
                            <i class="fas fa-lock"></i> Connectez-vous
                        </a>
                    <?php endif; ?>
                    <button class="btn-wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 5. Chopard Imperiale Ring -->
        <div class="product-card">
            <div class="product-image">
                <img src="https://www.lepage.fr/91691-zoom_default/bague-chopard-ice-cube-en-or-rose-et-diamants.jpg" alt="Bijoux Homme">
            </div>
            <div class="product-info">
                <div class="product-brand">CHOPARD</div>
                <h3 class="product-name">Imperiale Ring</h3>
                <p class="product-description">Bague or blanc, diamants et onyx – inspiration impériale majestueuse</p>
                <div class="product-price">
                    <span class="price-current">1 500 €</span>
                </div>
                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="pages/ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="5"> <!-- ← REMPLACE PAR VRAI ID -->
                            <input type="hidden" name="quantite" value="1">
                            <input type="hidden" name="redirect_url" value="pages/panier.php">
                            <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="pages/connexion.php?redirect=index.php" class="btn-add-cart disabled">
                            <i class="fas fa-lock"></i> Connectez-vous
                        </a>
                    <?php endif; ?>
                    <button class="btn-wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 6. Piaget Limelight Gala Ring -->
        <div class="product-card">
            <span class="product-badge discount">-20%</span>
            <div class="product-image">
                <img src="https://img.piaget.com/cards-row-split-3/d1fefdd704368d2b986c2df4dda800a64a4d5f5f.jpg" alt="Bijoux Homme">
            </div>
            <div class="product-info">
                <div class="product-brand">PIAGET</div>
                <h3 class="product-name">Limelight Gala Ring</h3>
                <p class="product-description">Bague or rose et diamants taille baguette – glamour et sophistication festive</p>
                <div class="product-price">
                    <span class="price-original">900 €</span>
                    <span class="price-current">720 €</span>
                </div>
                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="pages/ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="6"> <!-- ← REMPLACE PAR VRAI ID -->
                            <input type="hidden" name="quantite" value="1">
                            <input type="hidden" name="redirect_url" value="pages/panier.php">
                            <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="pages/connexion.php?redirect=index.php" class="btn-add-cart disabled">
                            <i class="fas fa-lock"></i> Connectez-vous
                        </a>
                    <?php endif; ?>
                    <button class="btn-wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Best-sellers -->
<section class="container">
    <div class="section-title">
        <h2>Best-sellers</h2>
    </div>
    
    <div class="products-grid">
        <!-- 1. Cartier Love Bracelet -->
        <div class="product-card">
            <span class="product-badge bestseller">BEST-SELLER</span>
            <div class="product-image">
                <img src="https://www.bijouxbaume.com/upload/image/bracelet-…-4-diamants-diametre-17-p-image-143430-grande.jpg" alt="Bijoux Homme">
            </div>
            <div class="product-info">
                <div class="product-brand">CARTIER</div>
                <h3 class="product-name">Love Bracelet</h3>
                <p class="product-description">Bracelet iconique en or jaune 18K avec vis signature – symbole d'amour éternel</p>
                <div class="product-price">
                    <span class="price-current">1200 €</span>
                </div>
                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="pages/ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="7"> <!-- ← REMPLACE PAR LE VRAI ID -->
                            <input type="hidden" name="quantite" value="1">
                            <input type="hidden" name="redirect_url" value="pages/panier.php">
                            <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="pages/connexion.php?redirect=index.php" class="btn-add-cart disabled">
                            <i class="fas fa-lock"></i> Connectez-vous
                        </a>
                    <?php endif; ?>
                    <button class="btn-wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Tiffany & Co. T1 Ring -->
        <div class="product-card">
            <div class="product-image">
                <img src="https://media.tiffany.com/is/image/tco/67795113_RG_MAIN1X1?hei=2000&wid=2000&fmt=webp" alt="Bijoux Homme">
            </div>
            <div class="product-info">
                <div class="product-brand">TIFFANY & CO.</div>
                <h3 class="product-name">T1 Ring</h3>
                <p class="product-description">Bague en or jaune 18K avec diamants – design architectural moderne et élégant</p>
                <div class="product-price">
                    <span class="price-current">1625 €</span>
                </div>
                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="pages/ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="8"> <!-- ← REMPLACE PAR LE VRAI ID -->
                            <input type="hidden" name="quantite" value="1">
                            <input type="hidden" name="redirect_url" value="pages/panier.php">
                            <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="pages/connexion.php?redirect=index.php" class="btn-add-cart disabled">
                            <i class="fas fa-lock"></i> Connectez-vous
                        </a>
                    <?php endif; ?>
                    <button class="btn-wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. Van Cleef & Arpels Vintage Alhambra -->
        <div class="product-card">
            <span class="product-badge bestseller">BEST-SELLER</span>
            <div class="product-image">
                <img src="https://dandelion-antiques.co.uk/cdn/shop/files/photo_2024-12-0510.08.53.jpg?v=1734355598&width=1080" alt="Bijoux Homme">
            </div>
            <div class="product-info">
                <div class="product-brand">VAN CLEEF & ARPELS</div>
                <h3 class="product-name">Vintage Alhambra Necklace</h3>
                <p class="product-description">Collier motif trèfle en or jaune 18K et nacre – porte-bonheur intemporel</p>
                <div class="product-price">
                    <span class="price-current">800 €</span>
                </div>
                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="pages/ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="9"> <!-- ← REMPLACE PAR LE VRAI ID -->
                            <input type="hidden" name="quantite" value="1">
                            <input type="hidden" name="redirect_url" value="pages/panier.php">
                            <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="pages/connexion.php?redirect=index.php" class="btn-add-cart disabled">
                            <i class="fas fa-lock"></i> Connectez-vous
                        </a>
                    <?php endif; ?>
                    <button class="btn-wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. Bulgari Serpenti Viper Bracelet -->
        <div class="product-card">
            <div class="product-image">
                <img src="https://www.mytheresa.com/media/1094/1238/100/f8/P01030224.jpg" alt="Bijoux Homme">
            </div>
            <div class="product-info">
                <div class="product-brand">BULGARI</div>
                <h3 class="product-name">Serpenti Viper Bracelet</h3>
                <p class="product-description">Bracelet serpent en or jaune et diamants – sensualité et puissance italienne</p>
                <div class="product-price">
                    <span class="price-current">1500 €</span>
                </div>
                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="pages/ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="10"> <!-- ← REMPLACE PAR LE VRAI ID -->
                            <input type="hidden" name="quantite" value="1">
                            <input type="hidden" name="redirect_url" value="pages/panier.php">
                            <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="pages/connexion.php?redirect=index.php" class="btn-add-cart disabled">
                            <i class="fas fa-lock"></i> Connectez-vous
                        </a>
                    <?php endif; ?>
                    <button class="btn-wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 5. Harry Winston Cluster Diamond Ring -->
        <div class="product-card">
            <span class="product-badge discount">-17%</span>
            <div class="product-image">
                <img src="https://img.fril.jp/img/759856928/l/2566399238.jpg?1745498908" alt="Bijoux Homme">
            </div>
            <div class="product-info">
                <div class="product-brand">HARRY WINSTON</div>
                <h3 class="product-name">Cluster Diamond Ring</h3>
                <p class="product-description">Bague cluster diamants en platine – éclat exceptionnel et pureté</p>
                <div class="product-price">
                    <span class="price-original">1000 €</span>
                    <span class="price-current">500 €</span>
                </div>
                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="pages/ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="11"> <!-- ← REMPLACE PAR LE VRAI ID -->
                            <input type="hidden" name="quantite" value="1">
                            <input type="hidden" name="redirect_url" value="pages/panier.php">
                            <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="pages/connexion.php?redirect=index.php" class="btn-add-cart disabled">
                            <i class="fas fa-lock"></i> Connectez-vous
                        </a>
                    <?php endif; ?>
                    <button class="btn-wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 6. Chopard Happy Diamonds Pendant -->
        <div class="product-card">
            <div class="product-image">
                <img src="https://images.hbjo-online.com/webp/images/all/collier-chopard-happy-diamonds-799224-5003.jpg" alt="Bijoux Homme">
            </div>
            <div class="product-info">
                <div class="product-brand">CHOPARD</div>
                <h3 class="product-name">Happy Diamonds Pendant</h3>
                <p class="product-description">Pendentif diamants mobiles en or rose – joie et mouvement</p>
                <div class="product-price">
                    <span class="price-current">1800 €</span>
                </div>
                <div class="product-actions">
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="pages/ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="12"> <!-- ← REMPLACE PAR LE VRAI ID -->
                            <input type="hidden" name="quantite" value="1">
                            <input type="hidden" name="redirect_url" value="pages/panier.php">
                            <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="pages/connexion.php?redirect=index.php" class="btn-add-cart disabled">
                            <i class="fas fa-lock"></i> Connectez-vous
                        </a>
                    <?php endif; ?>
                    <button class="btn-wishlist">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section À Propos -->
<section class="about-section">
    <div class="container">
        <div class="about-content">
            <h2>L'Histoire de Lumoura</h2>
            <p>Depuis 1920, Lumoura façonne des bijoux d'exception qui traversent les époques. 
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