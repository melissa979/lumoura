<?php
// Page panier - version adaptée pour une boutique de BIJOUX
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['login_redirect'] = 'Veuillez vous connecter pour accéder à votre panier.';
    header('Location: connexion.php');
    exit();
}

// Initialiser le panier dans la session s'il n'existe pas encore
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Gestion des actions sur le panier (ajout, mise à jour, suppression, vider)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    switch ($action) {
        case 'add':
            if ($product_id > 0) {
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                $_SESSION['cart_message'] = 'Le produit a été ajouté à votre panier.';
            }
            break;
            
        case 'update':
            if ($product_id > 0 && $quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } elseif ($product_id > 0 && $quantity <= 0) {
                unset($_SESSION['cart'][$product_id]);
            }
            break;
            
        case 'remove':
            if ($product_id > 0) {
                unset($_SESSION['cart'][$product_id]);
            }
            break;
            
        case 'clear':
            $_SESSION['cart'] = [];
            break;
    }
    
    // Redirection pour éviter la resoumission du formulaire (PRG pattern)
    header('Location: panier.php');
    exit();
}

// Récupérer les détails des bijoux présents dans le panier
$cart_items = [];
$cart_total = 0;
$cart_subtotal = 0;

if (!empty($_SESSION['cart'])) {
    try {
        $placeholders = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';
        $query = "SELECT * FROM produits WHERE id_produit IN ($placeholders)";
        $stmt = $pdo->prepare($query);
        $stmt->execute(array_keys($_SESSION['cart']));
        $products = $stmt->fetchAll();
        
        foreach ($products as $product) {
            $quantity = $_SESSION['cart'][$product['id_produit']];
            $price = $product['prix'];
            $discount = $product['promotion_pourcentage'];
            
            if ($discount > 0) {
                $price = calculateDiscount($price, $discount);
            }
            
            $total = $price * $quantity;
            
            $cart_items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'price' => $price,
                'total' => $total
            ];
            
            $cart_subtotal += $total;
        }
        
        $shipping = ($cart_subtotal >= 100 || $cart_subtotal == 0) ? 0 : 4.90;
        $cart_total = $cart_subtotal + $shipping;
        
    } catch (PDOException $e) {
        die('Erreur lors du chargement du panier');
    }
}

$pageTitle = "Panier - Lumoura Bijoux";
include '../includes/header.php';
?>

<div class="cart-page">
    <h1 class="cart-title">Mon Panier</h1>

    <?php if (isset($_SESSION['cart_message'])): ?>
        <div class="alert success" style="padding: 15px; margin-bottom: 20px; background: #e6ffe6; border: 1px solid #b3ffb3; border-radius: 6px; color: #006600;">
            <?= htmlspecialchars($_SESSION['cart_message']) ?>
        </div>
        <?php unset($_SESSION['cart_message']); ?>
    <?php endif; ?>
    
    <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fas fa-gem"></i>
            <h2>Votre panier est vide</h2>
            <p>Ajoutez des bijoux pour commencer vos achats</p>
            <a href="catalogue.php" class="btn-primary">
                <i class="fas fa-store"></i> Découvrir nos collections
            </a>
        </div>
    <?php else: ?>
        <div class="cart-container">
            <div class="cart-items">
                <div class="cart-header">
                    <h2>Articles (<?php echo count($cart_items); ?>)</h2>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn-clear" onclick="return confirm('Vider tout le panier ?')">
                            <i class="fas fa-trash"></i> Vider le panier
                        </button>
                    </form>
                </div>
                
                <?php foreach ($cart_items as $item): 
                    $product = $item['product'];
                ?>
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <img src="<?php echo $product['image_url'] ?: 'https://images.unsplash.com/photo-1611590027211-b954fd027b51?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'; ?>" 
                                 alt="<?php echo sanitize($product['nom']); ?>">
                        </div>
                        
                        <div class="cart-item-details">
                            <h3><?php echo sanitize($product['nom']); ?></h3>
                            <p class="cart-item-brand"><?php echo sanitize($product['marque']); ?></p>
                            <p class="cart-item-detail"><?php 
                                echo $product['matiere'] ?? 'Élégance intemporelle'; 
                            ?></p>
                        </div>
                        
                        <div class="cart-item-price">
                            <?php echo formatPrice($item['price']); ?>
                        </div>
                        
                        <div class="cart-item-quantity">
                            <form method="POST" class="quantity-form">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?php echo $product['id_produit']; ?>">
                                <button type="button" class="quantity-btn minus" onclick="updateQuantity(this, -1)">-</button>
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                       min="1" max="<?php echo $product['stock']; ?>" 
                                       class="quantity-input" onchange="this.form.submit()">
                                <button type="button" class="quantity-btn plus" onclick="updateQuantity(this, 1)">+</button>
                            </form>
                        </div>
                        
                        <div class="cart-item-total">
                            <?php echo formatPrice($item['total']); ?>
                        </div>
                        
                        <form method="POST" class="remove-form">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?php echo $product['id_produit']; ?>">
                            <button type="submit" class="btn-remove-item">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cart-summary">
                <h2 class="summary-title">Récapitulatif</h2>
                
                <div class="summary-row">
                    <span>Sous-total</span>
                    <span><?php echo formatPrice($cart_subtotal); ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Frais de livraison</span>
                    <span>
                        <?php if ($shipping == 0): ?>
                            <span style="color: #d4af37;">Gratuit</span>
                        <?php else: ?>
                            <?php echo formatPrice($shipping); ?>
                        <?php endif; ?>
                    </span>
                </div>
                
                <?php if ($shipping > 0 && $cart_subtotal > 0): ?>
                    <div class="summary-row" style="color: #d4af37; font-size: 14px;">
                        <span>Plus que <?php echo formatPrice(100 - $cart_subtotal); ?> pour la livraison gratuite !</span>
                    </div>
                <?php endif; ?>
                
                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span><?php echo formatPrice($cart_total); ?></span>
                </div>
                
                <div class="summary-note">
                    <p><i class="fas fa-check"></i> Livraison en 24-48h</p>
                    <p><i class="fas fa-check"></i> Retour gratuit sous 30 jours</p>
                    <p><i class="fas fa-check"></i> Paiement sécurisé</p>
                    <p><i class="fas fa-shield-alt"></i> Certificat d'authenticité</p>
                </div>
                
                <a href="commande.php" class="btn-checkout">
                    <i class="fas fa-lock"></i> Procéder au paiement
                </a>
                
                <div class="continue-shopping">
                    <a href="catalogue.php">
                        <i class="fas fa-arrow-left"></i> Continuer mes achats
                    </a>
                </div>
            </div>
        </div>
        
        <?php
        try {
            $recommendedQuery = "SELECT * FROM produits WHERE bestseller = 1 ORDER BY RAND() LIMIT 4";
            $recommendedStmt = $pdo->query($recommendedQuery);
            $recommendedProducts = $recommendedStmt->fetchAll();
            
            if ($recommendedProducts): ?>
                <div class="recommended-products">
                    <h2 style="margin: 60px 0 30px; text-align: center;">Bijoux souvent achetés ensemble</h2>
                    
                    <div class="products-grid">
                        <?php foreach ($recommendedProducts as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?php echo $product['image_url'] ?: 'https://images.unsplash.com/photo-1611590027211-b954fd027b51?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'; ?>" 
                                         alt="<?php echo sanitize($product['nom']); ?>">
                                </div>
                                
                                <div class="product-info">
                                    <div class="product-brand"><?php echo sanitize($product['marque']); ?></div>
                                    <h3 class="product-name"><?php echo sanitize($product['nom']); ?></h3>
                                    
                                    <div class="product-price">
                                        <?php
                                        $price = $product['prix'];
                                        $discount = $product['promotion_pourcentage'];
                                        
                                        if ($discount > 0):
                                            $discountedPrice = calculateDiscount($price, $discount);
                                        ?>
                                            <span class="price-original"><?php echo formatPrice($price); ?></span>
                                            <span class="price-current"><?php echo formatPrice($discountedPrice); ?></span>
                                        <?php else: ?>
                                            <span class="price-current"><?php echo formatPrice($price); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <form method="POST" action="ajouter_au_panier.php" style="display: inline;">
                                            <input type="hidden" name="produit_id" value="<?php echo $product['id_produit']; ?>">
                                            <input type="hidden" name="quantite" value="1">
                                            <input type="hidden" name="redirect_url" value="panier.php">
                                            <button type="submit" name="ajouter_panier" class="btn-cart">
                                                <i class="fas fa-plus"></i> Ajouter
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif;
        } catch (PDOException $e) {
            // Silencieux
        }
        ?>
    <?php endif; ?>
</div>

<script>
function updateQuantity(button, change) {
    const form = button.closest('.quantity-form');
    const input = form.querySelector('.quantity-input');
    let newValue = parseInt(input.value) + change;
    
    if (newValue >= 1 && newValue <= parseInt(input.max)) {
        input.value = newValue;
        form.submit();
    }
}
</script>

<style>
/* ==============================================
   Adaptation couleurs + ambiance bijoux / luxe
   ============================================== */

:root {
    --gold: #d4af37;
    --gold-dark: #b8972e;
    --cream: #fdfbf7;
    --gray-light: #e8e5df;
    --text-dark: #2d2d2d;
    --accent: #c9a96e;
}

.cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--gold);
}

.btn-clear {
    background: none;
    border: none;
    color: #c0392b;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.btn-clear:hover {
    color: #a93226;
    text-decoration: underline;
}

.summary-note {
    margin-top: 20px;
    padding: 20px;
    background: var(--cream);
    border: 1px solid var(--gold);
    border-radius: 8px;
    font-size: 14px;
}

.summary-note p {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--text-dark);
}

.summary-note i {
    color: #27ae60;
}

.continue-shopping {
    text-align: center;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid var(--gray-light);
}

.continue-shopping a {
    color: var(--gold);
    font-weight: 500;
    transition: color 0.2s;
}

.continue-shopping a:hover {
    color: var(--gold-dark);
    text-decoration: underline;
}

.recommended-products {
    margin-top: 70px;
    padding-top: 50px;
    border-top: 1px solid var(--gray-light);
}

@media (max-width: 768px) {
    .cart-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .cart-item {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<?php include '../includes/footer.php'; ?>