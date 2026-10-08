<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: connexion.php');
    exit();
}

$user_id = (int) ($_SESSION['user_id'] ?? 0);

// Suppression d'un article
if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
    $id_produit = (int) $_GET['supprimer'];
    $stmt = $pdo->prepare('DELETE FROM panier WHERE id_utilisateur = ? AND id_produit = ?');
    $stmt->execute([$user_id, $id_produit]);
    header('Location: panier.php?success=supprime');
    exit();
}

// Vider le panier
if (isset($_GET['vider'])) {
    $stmt = $pdo->prepare('DELETE FROM panier WHERE id_utilisateur = ?');
    $stmt->execute([$user_id]);
    header('Location: panier.php?success=vide');
    exit();
}

// Mise à jour d'une quantité avec contrôle serveur du stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    $id_produit = filter_input(INPUT_POST, 'id_produit', FILTER_VALIDATE_INT) ?: 0;
    $quantite = filter_input(INPUT_POST, 'quantite', FILTER_VALIDATE_INT) ?: 1;
    $quantite = max(1, $quantite);

    if ($id_produit > 0) {
        $stockStmt = $pdo->prepare('SELECT stock FROM produits WHERE id_produit = ? LIMIT 1');
        $stockStmt->execute([$id_produit]);
        $stock = $stockStmt->fetchColumn();

        if ($stock === false || (int) $stock <= 0) {
            $pdo->prepare('DELETE FROM panier WHERE id_utilisateur = ? AND id_produit = ?')
                ->execute([$user_id, $id_produit]);
            header('Location: panier.php?success=indisponible');
            exit();
        }

        $quantite = min($quantite, (int) $stock);
        $pdo->prepare('UPDATE panier SET quantite = ? WHERE id_utilisateur = ? AND id_produit = ?')
            ->execute([$quantite, $user_id, $id_produit]);
    }

    header('Location: panier.php?success=maj');
    exit();
}

try {
    $stmt = $pdo->prepare(''
        . 'SELECT pa.id_produit, pa.quantite, '
        . 'p.nom, p.prix, p.image_url, p.stock, p.promotion_pourcentage '
        . 'FROM panier pa '
        . 'INNER JOIN produits p ON pa.id_produit = p.id_produit '
        . 'WHERE pa.id_utilisateur = ? '
        . 'ORDER BY pa.date_ajout DESC'
    );
    $stmt->execute([$user_id]);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $articles = [];
}

$total = 0.0;
$nb_total = 0;

foreach ($articles as &$article) {
    $prixBase = (float) $article['prix'];
    $promotion = (float) ($article['promotion_pourcentage'] ?? 0);
    $prixFinal = $promotion > 0
        ? $prixBase * (1 - $promotion / 100)
        : $prixBase;

    $article['prix_base'] = $prixBase;
    $article['prix_final'] = $prixFinal;
    $article['sous_total'] = $prixFinal * (int) $article['quantite'];
    $total += $article['sous_total'];
    $nb_total += (int) $article['quantite'];
}
unset($article);

$seuilLivraisonGratuite = 150.0;
$frais_standard = $total >= $seuilLivraisonGratuite ? 0.0 : 9.90;
$frais_express = 14.90;
$frais_premium = 24.90;
$total_final = $total + $frais_standard;
$progression = min(100, ($total / $seuilLivraisonGratuite) * 100);
$resteLivraison = max(0, $seuilLivraisonGratuite - $total);

// Suggestions personnalisées, sans afficher les produits déjà présents dans le panier.
$recommendations = [];
try {
    $idsDansPanier = array_map('intval', array_column($articles, 'id_produit'));
    $recommendationSql = 'SELECT id_produit, nom, prix, image_url, promotion_pourcentage FROM produits WHERE stock > 0';
    $recommendationParams = [];

    if (!empty($idsDansPanier)) {
        $placeholders = implode(',', array_fill(0, count($idsDansPanier), '?'));
        $recommendationSql .= " AND id_produit NOT IN ($placeholders)";
        $recommendationParams = $idsDansPanier;
    }

    $recommendationSql .= ' ORDER BY nouveaute DESC, id_produit DESC LIMIT 3';
    $recommendationStmt = $pdo->prepare($recommendationSql);
    $recommendationStmt->execute($recommendationParams);
    $recommendations = $recommendationStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $recommendations = [];
}

$pageTitle = 'Mon Panier - Éclat d’Or';
include '../includes/header.php';
?>

<div class="premium-cart-page">
    <section class="premium-cart-hero">
        <div class="premium-cart-hero-content">
            <span class="premium-cart-eyebrow"><i class="fas fa-sparkles"></i> Votre sélection privée</span>
            <h1>Mon panier</h1>
            <p>Les pièces que vous avez choisies, prêtes à rejoindre votre histoire.</p>
            <div class="premium-cart-hero-stats">
                <span><strong><?= count($articles) ?></strong> produit<?= count($articles) > 1 ? 's' : '' ?></span>
                <span class="premium-cart-stat-separator"></span>
                <span><strong><?= $nb_total ?></strong> article<?= $nb_total > 1 ? 's' : '' ?> au total</span>
            </div>
        </div>
        <div class="premium-cart-orbit premium-cart-orbit-one"></div>
        <div class="premium-cart-orbit premium-cart-orbit-two"></div>
        <div class="premium-cart-hero-icon"><i class="fas fa-shopping-bag"></i></div>
    </section>

    <?php if (!empty($_GET['success'])): ?>
        <div class="premium-cart-alert" role="status">
            <i class="fas fa-check-circle"></i>
            <?php if ($_GET['success'] === 'supprime'): ?>Article retiré du panier.
            <?php elseif ($_GET['success'] === 'vide'): ?>Votre panier a été vidé.
            <?php elseif ($_GET['success'] === 'maj'): ?>Quantité mise à jour.
            <?php elseif ($_GET['success'] === 'indisponible'): ?>Un article n'est plus disponible et a été retiré.
            <?php else: ?>Votre panier a été mis à jour.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($articles)): ?>
        <section class="premium-cart-empty">
            <div class="premium-cart-empty-icon"><i class="fas fa-gem"></i></div>
            <span class="premium-cart-eyebrow">Éclat d’Or</span>
            <h2>Votre panier vous attend</h2>
            <p>Découvrez les créations qui donneront une nouvelle lumière à vos moments précieux.</p>
            <a href="catalogue.php" class="premium-cart-primary-button">
                <i class="fas fa-arrow-right"></i> Découvrir la collection
            </a>
        </section>
    <?php else: ?>
        <section class="premium-cart-shipping-progress">
            <div class="premium-cart-progress-copy">
                <div>
                    <span class="premium-cart-progress-kicker"><i class="fas fa-truck"></i> Livraison offerte</span>
                    <?php if ($resteLivraison > 0): ?>
                        <strong>Plus que <?= number_format($resteLivraison, 2, ',', ' ') ?> € pour en profiter</strong>
                    <?php else: ?>
                        <strong>Félicitations, votre livraison est offerte</strong>
                    <?php endif; ?>
                </div>
                <span class="premium-cart-progress-value"><?= number_format($total, 2, ',', ' ') ?> / <?= number_format($seuilLivraisonGratuite, 0, ',', ' ') ?> €</span>
            </div>
            <div class="premium-cart-progress-track" aria-label="Progression vers la livraison gratuite">
                <span style="width: <?= $progression ?>%"></span>
            </div>
        </section>

        <div class="premium-cart-layout">
            <main class="premium-cart-items-column">
                <div class="premium-cart-section-heading">
                    <div>
                        <span class="premium-cart-eyebrow">Votre sélection</span>
                        <h2>Pièces choisies</h2>
                    </div>
                    <a href="panier.php?vider=1" class="premium-cart-clear" onclick="return confirm('Vider tout le panier ?')">
                        <i class="fas fa-trash-alt"></i> Vider le panier
                    </a>
                </div>

                <div class="premium-cart-product-list">
                    <?php foreach ($articles as $article):
                        $image = !empty($article['image_url'])
                            ? htmlspecialchars($article['image_url'], ENT_QUOTES, 'UTF-8')
                            : 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?w=300';
                        $nom = htmlspecialchars($article['nom'], ENT_QUOTES, 'UTF-8');
                        $stock = max(1, (int) ($article['stock'] ?? 99));
                    ?>
                        <article class="premium-cart-product-card">
                            <div class="premium-cart-product-image-wrap">
                                <img src="<?= $image ?>" alt="<?= $nom ?>" class="premium-cart-product-image" onerror="this.src='https://via.placeholder.com/180x180?text=Bijou'">
                                <?php if ((float) $article['promotion_pourcentage'] > 0): ?>
                                    <span class="premium-cart-discount">-<?= (int) $article['promotion_pourcentage'] ?>%</span>
                                <?php endif; ?>
                            </div>

                            <div class="premium-cart-product-main">
                                <span class="premium-cart-product-label">Éclat d’Or</span>
                                <h3><?= $nom ?></h3>
                                <div class="premium-cart-unit-price">
                                    <?php if ((float) $article['promotion_pourcentage'] > 0): ?>
                                        <span class="premium-cart-old-price"><?= number_format($article['prix_base'], 2, ',', ' ') ?> €</span>
                                    <?php endif; ?>
                                    <strong><?= number_format($article['prix_final'], 2, ',', ' ') ?> €</strong>
                                    <span class="premium-cart-price-note">par pièce</span>
                                </div>

                                <form method="POST" action="panier.php" class="premium-cart-quantity-form">
                                    <input type="hidden" name="id_produit" value="<?= (int) $article['id_produit'] ?>">
                                    <div class="premium-cart-quantity-control">
                                        <button type="button" class="premium-cart-quantity-button" onclick="changePremiumQuantity(this, -1)" aria-label="Diminuer la quantité">−</button>
                                        <input type="number" name="quantite" value="<?= (int) $article['quantite'] ?>" min="1" max="<?= $stock ?>" class="premium-cart-quantity-input" readonly aria-label="Quantité de <?= $nom ?>">
                                        <button type="button" class="premium-cart-quantity-button" onclick="changePremiumQuantity(this, 1)" aria-label="Augmenter la quantité">+</button>
                                    </div>
                                    <button type="submit" name="update_qty" class="premium-cart-update-button">
                                        <i class="fas fa-sync-alt"></i> Mettre à jour
                                    </button>
                                </form>
                            </div>

                            <div class="premium-cart-product-side">
                                <strong><?= number_format($article['sous_total'], 2, ',', ' ') ?> €</strong>
                                <a href="panier.php?supprimer=<?= (int) $article['id_produit'] ?>" class="premium-cart-remove" aria-label="Supprimer <?= $nom ?>" onclick="return confirm('Retirer cet article du panier ?')">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <a href="catalogue.php" class="premium-cart-continue">
                    <i class="fas fa-arrow-left"></i> Continuer mes achats
                </a>
            </main>

            <aside class="premium-cart-summary">
                <div class="premium-cart-summary-header">
                    <span class="premium-cart-eyebrow">Votre commande</span>
                    <h2>Récapitulatif</h2>
                </div>

                <form method="GET" action="commande.php" id="premiumDeliveryForm">
                    <div class="premium-cart-delivery-heading">
                        <span>Mode de livraison</span>
                        <i class="fas fa-shipping-fast"></i>
                    </div>

                    <div class="premium-cart-delivery-options">
                        <label class="premium-cart-delivery-option is-selected">
                            <input type="radio" name="mode_livraison" value="standard" data-price="<?= $frais_standard ?>" checked>
                            <span class="premium-cart-radio"></span>
                            <span class="premium-cart-delivery-content">
                                <strong>Standard <small>3 à 5 jours ouvrés</small></strong>
                                <b><?= $frais_standard > 0 ? number_format($frais_standard, 2, ',', ' ') . ' €' : 'Gratuite' ?></b>
                            </span>
                        </label>
                        <label class="premium-cart-delivery-option">
                            <input type="radio" name="mode_livraison" value="express" data-price="<?= $frais_express ?>">
                            <span class="premium-cart-radio"></span>
                            <span class="premium-cart-delivery-content">
                                <strong>Express <small>48h ouvrées</small></strong>
                                <b><?= number_format($frais_express, 2, ',', ' ') ?> €</b>
                            </span>
                        </label>
                        <label class="premium-cart-delivery-option">
                            <input type="radio" name="mode_livraison" value="premium" data-price="<?= $frais_premium ?>">
                            <span class="premium-cart-radio"></span>
                            <span class="premium-cart-delivery-content">
                                <strong>Premium <small>24h chrono</small></strong>
                                <b><?= number_format($frais_premium, 2, ',', ' ') ?> €</b>
                            </span>
                        </label>
                    </div>

                    <div class="premium-cart-summary-lines">
                        <div><span>Sous-total</span><strong><?= number_format($total, 2, ',', ' ') ?> €</strong></div>
                        <div><span>Livraison</span><strong id="premiumDeliveryPrice"><?= $frais_standard > 0 ? number_format($frais_standard, 2, ',', ' ') . ' €' : 'Gratuite' ?></strong></div>
                    </div>

                    <div class="premium-cart-total-row">
                        <span>Total</span>
                        <strong id="premiumCartTotal"><?= number_format($total_final, 2, ',', ' ') ?> €</strong>
                    </div>

                    <button type="submit" class="premium-cart-checkout-button">
                        <i class="fas fa-lock"></i> Passer la commande
                    </button>
                </form>

                <div class="premium-cart-secure-note">
                    <i class="fas fa-shield-alt"></i>
                    <span>Paiement sécurisé et données protégées</span>
                </div>
                <div class="premium-cart-payment-icons">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-paypal"></i>
                </div>
            </aside>
        </div>

        <section class="premium-cart-trust-section">
            <span class="premium-cart-eyebrow">La signature Éclat d’Or</span>
            <h2>Une expérience pensée pour vous</h2>
            <div class="premium-cart-trust-grid">
                <div><i class="fas fa-truck"></i><strong>Livraison offerte</strong><span>Dès 150 € d’achat</span></div>
                <div><i class="fas fa-undo-alt"></i><strong>Retours sereins</strong><span>Sous 30 jours</span></div>
                <div><i class="fas fa-lock"></i><strong>Paiement protégé</strong><span>Transactions sécurisées</span></div>
                <div><i class="fas fa-headset"></i><strong>Conseil personnalisé</strong><span>Une équipe à votre écoute</span></div>
            </div>
        </section>

        <?php if (!empty($recommendations)): ?>
            <section class="premium-cart-recommendations">
                <div class="premium-cart-recommendations-heading">
                    <div>
                        <span class="premium-cart-eyebrow">Vous pourriez aimer</span>
                        <h2>Complétez votre éclat</h2>
                    </div>
                    <a href="catalogue.php" class="premium-cart-recommendations-link">Voir toute la collection <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="premium-cart-recommendations-grid">
                    <?php foreach ($recommendations as $recommendation):
                        $recommendationName = htmlspecialchars($recommendation['nom'], ENT_QUOTES, 'UTF-8');
                        $recommendationImage = !empty($recommendation['image_url'])
                            ? htmlspecialchars($recommendation['image_url'], ENT_QUOTES, 'UTF-8')
                            : 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?w=300';
                        $recommendationPrice = (float) $recommendation['prix'];
                        $recommendationDiscount = (float) ($recommendation['promotion_pourcentage'] ?? 0);
                        $recommendationFinalPrice = $recommendationDiscount > 0
                            ? $recommendationPrice * (1 - $recommendationDiscount / 100)
                            : $recommendationPrice;
                    ?>
                        <a class="premium-cart-recommendation-card" href="produit.php?id=<?= (int) $recommendation['id_produit'] ?>">
                            <div class="premium-cart-recommendation-image">
                                <img src="<?= $recommendationImage ?>" alt="<?= $recommendationName ?>" onerror="this.src='https://via.placeholder.com/240x180?text=Bijou'">
                            </div>
                            <div class="premium-cart-recommendation-info">
                                <span>Éclat d’Or</span>
                                <strong><?= $recommendationName ?></strong>
                                <b><?= number_format($recommendationFinalPrice, 2, ',', ' ') ?> €</b>
                            </div>
                            <i class="fas fa-arrow-up-right-from-square premium-cart-recommendation-arrow"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
:root {
    --cart-gold: #d4af37;
    --cart-gold-dark: #a98216;
    --cart-brown: #3d2b28;
    --cart-cream: #fbf3ea;
    --cart-line: #eadfd3;
    --cart-muted: #8b7b73;
}

.premium-cart-page {
    max-width: 1180px;
    margin: 0 auto;
    padding: 20px 24px 90px;
    color: var(--cart-brown);
}

.premium-cart-hero {
    min-height: 285px;
    margin: 70px 0 30px;
    padding: 58px 70px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    border-radius: 28px;
    background: linear-gradient(115deg, #f1e8e1 0%, #b9a49a 42%, #5a3825 100%);
    box-shadow: 0 24px 50px rgba(61,43,40,.14);
    isolation: isolate;
}

.premium-cart-hero::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, rgba(255,255,255,.12), transparent 55%);
    z-index: -1;
}

.premium-cart-hero-content { position: relative; z-index: 2; max-width: 650px; }
.premium-cart-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--cart-gold-dark);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
}
.premium-cart-hero .premium-cart-eyebrow { color: #fff4d5; }
.premium-cart-hero h1 { margin: 12px 0 8px; color: #fff; font-size: clamp(2.5rem, 5vw, 4.3rem); letter-spacing: -1px; }
.premium-cart-hero p { max-width: 560px; margin: 0; color: rgba(255,255,255,.84); font-size: 16px; }
.premium-cart-hero-stats { display: flex; align-items: center; gap: 15px; margin-top: 28px; color: rgba(255,255,255,.86); font-size: 13px; }
.premium-cart-hero-stats strong { color: #fff; font-size: 18px; }
.premium-cart-stat-separator { width: 4px; height: 4px; border-radius: 50%; background: var(--cart-gold); }
.premium-cart-hero-icon { position: absolute; right: 105px; bottom: 50px; width: 130px; height: 130px; display: grid; place-items: center; border: 1px solid rgba(255,255,255,.35); border-radius: 42% 58% 60% 40%; background: rgba(255,255,255,.14); color: #f5d56c; font-size: 48px; transform: rotate(9deg); box-shadow: 0 20px 50px rgba(0,0,0,.14); }
.premium-cart-orbit { position: absolute; border: 1px solid rgba(255,255,255,.25); border-radius: 50%; pointer-events: none; }
.premium-cart-orbit-one { width: 350px; height: 350px; right: -80px; top: -130px; }
.premium-cart-orbit-two { width: 220px; height: 220px; right: 10px; bottom: -120px; }

.premium-cart-alert { display: flex; gap: 10px; align-items: center; margin: 0 0 22px; padding: 14px 18px; border: 1px solid #a7e8c8; border-radius: 12px; background: #effcf5; color: #12633f; font-size: 14px; }
.premium-cart-alert i { color: #1ea968; }

.premium-cart-shipping-progress { margin: 0 0 32px; padding: 20px 24px; border: 1px solid var(--cart-line); border-radius: 18px; background: rgba(255,255,255,.8); box-shadow: 0 12px 30px rgba(61,43,40,.06); }
.premium-cart-progress-copy { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.premium-cart-progress-copy strong { display: block; margin-top: 5px; font-size: 14px; }
.premium-cart-progress-kicker { color: var(--cart-gold-dark); font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
.premium-cart-progress-value { color: var(--cart-muted); font-size: 13px; white-space: nowrap; }
.premium-cart-progress-track { height: 8px; margin-top: 15px; overflow: hidden; border-radius: 20px; background: #f0e8df; }
.premium-cart-progress-track span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #bd9222, #f0ce5e); box-shadow: 0 0 14px rgba(212,175,55,.45); transition: width .5s ease; }

.premium-cart-layout { display: grid; grid-template-columns: minmax(0, 1fr) 365px; gap: 28px; align-items: start; }
.premium-cart-section-heading { display: flex; align-items: end; justify-content: space-between; gap: 15px; margin-bottom: 18px; }
.premium-cart-section-heading h2, .premium-cart-summary h2, .premium-cart-trust-section h2 { margin: 5px 0 0; color: var(--cart-brown); font-family: 'Playfair Display', serif; }
.premium-cart-section-heading h2 { font-size: 28px; }
.premium-cart-clear { color: #bf4d45; font-size: 13px; font-weight: 700; }
.premium-cart-clear:hover { color: #8f2f2a; }

.premium-cart-product-list { display: grid; gap: 15px; }
.premium-cart-product-card { display: grid; grid-template-columns: 132px minmax(0, 1fr) auto; gap: 20px; padding: 18px; border: 1px solid var(--cart-line); border-radius: 20px; background: rgba(255,255,255,.94); box-shadow: 0 10px 24px rgba(61,43,40,.07); transition: transform .25s ease, box-shadow .25s ease; }
.premium-cart-product-card:hover { transform: translateY(-4px) rotateX(.4deg); box-shadow: 0 18px 35px rgba(61,43,40,.13); }
.premium-cart-product-image-wrap { position: relative; width: 132px; height: 132px; overflow: hidden; border-radius: 15px; background: #eee4da; }
.premium-cart-product-image { width: 100%; height: 100%; object-fit: cover; transition: transform .45s ease; }
.premium-cart-product-card:hover .premium-cart-product-image { transform: scale(1.06); }
.premium-cart-discount { position: absolute; top: 9px; left: 9px; padding: 5px 8px; border-radius: 7px; background: var(--cart-gold); color: white; font-size: 11px; font-weight: 800; }
.premium-cart-product-main { min-width: 0; padding-top: 4px; }
.premium-cart-product-label { color: var(--cart-gold-dark); font-size: 10px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; }
.premium-cart-product-main h3 { margin: 7px 0 9px; color: var(--cart-brown); font-family: 'Playfair Display', serif; font-size: 22px; }
.premium-cart-unit-price { display: flex; align-items: baseline; flex-wrap: wrap; gap: 9px; }
.premium-cart-unit-price strong { color: var(--cart-brown); font-size: 19px; }
.premium-cart-old-price { color: #aaa; font-size: 12px; text-decoration: line-through; }
.premium-cart-price-note { color: var(--cart-muted); font-size: 11px; }
.premium-cart-quantity-form { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-top: 22px; }
.premium-cart-quantity-control { display: flex; overflow: hidden; border: 1px solid #dfd2c5; border-radius: 9px; }
.premium-cart-quantity-button { width: 34px; height: 34px; border: 0; background: #f8f1e9; color: var(--cart-brown); font-size: 19px; cursor: pointer; transition: background .2s ease, color .2s ease; }
.premium-cart-quantity-button:hover { background: var(--cart-gold); color: white; }
.premium-cart-quantity-input { width: 42px; height: 34px; border: 0; outline: 0; background: white; color: var(--cart-brown); text-align: center; font-weight: 800; }
.premium-cart-update-button { padding: 9px 12px; border: 0; border-radius: 9px; background: var(--cart-gold); color: white; font-size: 11px; font-weight: 800; cursor: pointer; transition: transform .2s ease, background .2s ease; }
.premium-cart-update-button:hover { background: var(--cart-gold-dark); transform: translateY(-2px); }
.premium-cart-product-side { display: flex; flex-direction: column; align-items: flex-end; justify-content: space-between; gap: 15px; }
.premium-cart-product-side > strong { color: var(--cart-gold-dark); font-size: 19px; white-space: nowrap; }
.premium-cart-remove { display: grid; width: 32px; height: 32px; place-items: center; border-radius: 50%; color: #c8bbb0; transition: background .2s ease, color .2s ease; }
.premium-cart-remove:hover { background: #fff0ee; color: #c24d45; }
.premium-cart-continue { display: inline-flex; align-items: center; gap: 8px; margin-top: 20px; color: var(--cart-brown); font-size: 13px; font-weight: 800; }
.premium-cart-continue:hover { color: var(--cart-gold-dark); }

.premium-cart-summary { position: sticky; top: 25px; padding: 24px; border: 1px solid var(--cart-line); border-radius: 22px; background: rgba(255,255,255,.96); box-shadow: 0 18px 40px rgba(61,43,40,.1); }
.premium-cart-summary h2 { font-size: 26px; }
.premium-cart-summary-header { padding-bottom: 17px; border-bottom: 1px solid var(--cart-line); }
.premium-cart-delivery-heading { display: flex; align-items: center; justify-content: space-between; margin: 20px 0 12px; color: var(--cart-brown); font-size: 12px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase; }
.premium-cart-delivery-heading i { color: var(--cart-gold); font-size: 18px; }
.premium-cart-delivery-options { display: grid; gap: 9px; }
.premium-cart-delivery-option { display: flex; align-items: center; gap: 10px; padding: 13px; border: 1px solid var(--cart-line); border-radius: 12px; cursor: pointer; transition: border .2s ease, background .2s ease, transform .2s ease; }
.premium-cart-delivery-option:hover, .premium-cart-delivery-option.is-selected { border-color: var(--cart-gold); background: #fffaf0; transform: translateX(2px); }
.premium-cart-delivery-option input { position: absolute; opacity: 0; pointer-events: none; }
.premium-cart-radio { width: 17px; height: 17px; flex: 0 0 17px; border: 2px solid #d7c8b9; border-radius: 50%; }
.premium-cart-delivery-option input:checked + .premium-cart-radio { border: 5px solid var(--cart-gold); }
.premium-cart-delivery-content { display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 10px; }
.premium-cart-delivery-content strong { display: flex; flex-direction: column; gap: 3px; color: var(--cart-brown); font-size: 13px; }
.premium-cart-delivery-content small { color: var(--cart-muted); font-size: 10px; font-weight: 400; }
.premium-cart-delivery-content b { color: var(--cart-brown); font-size: 12px; white-space: nowrap; }
.premium-cart-summary-lines { margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--cart-line); }
.premium-cart-summary-lines > div { display: flex; justify-content: space-between; margin-bottom: 12px; color: var(--cart-muted); font-size: 13px; }
.premium-cart-summary-lines strong { color: var(--cart-brown); }
.premium-cart-total-row { display: flex; align-items: center; justify-content: space-between; margin-top: 8px; padding: 17px 0; border-top: 2px solid var(--cart-brown); color: var(--cart-brown); font-size: 18px; font-weight: 800; }
.premium-cart-total-row strong { color: var(--cart-gold-dark); font-size: 24px; }
.premium-cart-checkout-button { width: 100%; padding: 15px; border: 0; border-radius: 12px; background: linear-gradient(135deg, var(--cart-brown), #624436); color: white; font-size: 14px; font-weight: 800; cursor: pointer; box-shadow: 0 12px 22px rgba(61,43,40,.2); transition: transform .2s ease, box-shadow .2s ease; }
.premium-cart-checkout-button:hover { transform: translateY(-3px); box-shadow: 0 16px 28px rgba(61,43,40,.28); }
.premium-cart-secure-note { display: flex; justify-content: center; align-items: center; gap: 7px; margin-top: 17px; color: var(--cart-muted); font-size: 11px; text-align: center; }
.premium-cart-secure-note i { color: #3da56d; }
.premium-cart-payment-icons { display: flex; justify-content: center; gap: 12px; margin-top: 12px; color: #b8b2ad; font-size: 25px; }

.premium-cart-trust-section { margin-top: 32px; padding: 35px 28px; border: 1px solid #eadbd5; border-radius: 22px; background: linear-gradient(135deg, #fffaf4, #f7e9ea); text-align: center; }
.premium-cart-trust-section h2 { margin-bottom: 26px; font-size: 26px; }
.premium-cart-trust-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
.premium-cart-trust-grid > div { display: flex; flex-direction: column; align-items: center; gap: 7px; }
.premium-cart-trust-grid i { display: grid; width: 49px; height: 49px; place-items: center; border-radius: 50%; background: var(--cart-gold); color: white; box-shadow: 0 8px 18px rgba(212,175,55,.25); }
.premium-cart-trust-grid strong { font-size: 13px; }
.premium-cart-trust-grid span { color: var(--cart-muted); font-size: 11px; }

.premium-cart-recommendations {
    margin-top: 38px;
    padding: 30px;
    border: 1px solid #eadfd3;
    border-radius: 24px;
    background: linear-gradient(135deg, #fffdf9, #f8eee7);
}

.premium-cart-recommendations-heading {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 20px;
}

.premium-cart-recommendations-heading h2 {
    margin: 6px 0 0;
    color: var(--cart-brown);
    font-family: 'Playfair Display', serif;
    font-size: 27px;
}

.premium-cart-recommendations-link {
    color: var(--cart-gold-dark);
    font-size: 12px;
    font-weight: 800;
}

.premium-cart-recommendations-link:hover {
    color: var(--cart-brown);
}

.premium-cart-recommendations-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
}

.premium-cart-recommendation-card {
    position: relative;
    overflow: hidden;
    border: 1px solid #eadfd3;
    border-radius: 17px;
    background: #fff;
    color: var(--cart-brown);
    box-shadow: 0 8px 18px rgba(61,43,40,.06);
    transition: transform .25s ease, box-shadow .25s ease;
}

.premium-cart-recommendation-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 28px rgba(61,43,40,.13);
}

.premium-cart-recommendation-image {
    height: 145px;
    overflow: hidden;
    background: #eee3d7;
}

.premium-cart-recommendation-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .4s ease;
}

.premium-cart-recommendation-card:hover img {
    transform: scale(1.06);
}

.premium-cart-recommendation-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 14px;
}

.premium-cart-recommendation-info span {
    color: var(--cart-gold-dark);
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.3px;
    text-transform: uppercase;
}

.premium-cart-recommendation-info strong {
    overflow: hidden;
    font-family: 'Playfair Display', serif;
    font-size: 17px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.premium-cart-recommendation-info b {
    color: var(--cart-gold-dark);
    font-size: 14px;
}

.premium-cart-recommendation-arrow {
    position: absolute;
    right: 13px;
    bottom: 14px;
    color: #c8b9ab;
    font-size: 13px;
}

.premium-cart-empty { max-width: 720px; margin: 40px auto 0; padding: 65px 25px; border: 1px solid var(--cart-line); border-radius: 24px; background: white; box-shadow: 0 16px 35px rgba(61,43,40,.08); text-align: center; }
.premium-cart-empty-icon { display: grid; width: 86px; height: 86px; place-items: center; margin: 0 auto 18px; border-radius: 50%; background: #fff5d9; color: var(--cart-gold); font-size: 34px; }
.premium-cart-empty h2 { margin: 8px 0 10px; font-family: 'Playfair Display', serif; font-size: 30px; }
.premium-cart-empty p { max-width: 470px; margin: 0 auto 25px; color: var(--cart-muted); }
.premium-cart-primary-button { display: inline-flex; align-items: center; gap: 9px; padding: 13px 22px; border-radius: 11px; background: var(--cart-brown); color: white; font-size: 13px; font-weight: 800; }
.premium-cart-primary-button:hover { background: var(--cart-gold-dark); transform: translateY(-2px); }

@media (max-width: 900px) {
    .premium-cart-layout { grid-template-columns: 1fr; }
    .premium-cart-summary { position: static; }
    .premium-cart-hero-icon { right: 45px; }
}

@media (max-width: 680px) {
    .premium-cart-recommendations {
        padding: 22px 16px;
    }

    .premium-cart-recommendations-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .premium-cart-recommendations-grid {
        grid-template-columns: 1fr;
    }

    .premium-cart-recommendation-image {
        height: 180px;
    }

    .premium-cart-page { padding: 10px 14px 60px; }
    .premium-cart-hero { min-height: 260px; margin: 55px 0 24px; padding: 38px 25px; border-radius: 22px; }
    .premium-cart-hero h1 { font-size: 2.6rem; }
    .premium-cart-hero-icon { right: 24px; bottom: 24px; width: 75px; height: 75px; font-size: 27px; }
    .premium-cart-hero p { max-width: 75%; font-size: 13px; }
    .premium-cart-progress-copy { align-items: flex-start; flex-direction: column; gap: 5px; }
    .premium-cart-section-heading { align-items: flex-start; flex-direction: column; }
    .premium-cart-product-card { grid-template-columns: 86px minmax(0, 1fr); gap: 13px; padding: 13px; }
    .premium-cart-product-image-wrap { width: 86px; height: 86px; }
    .premium-cart-product-main h3 { font-size: 18px; }
    .premium-cart-product-side { grid-column: 2; grid-row: 1; }
    .premium-cart-product-side > strong { font-size: 15px; }
    .premium-cart-quantity-form { margin-top: 15px; }
    .premium-cart-update-button { width: 100%; }
    .premium-cart-trust-grid { grid-template-columns: 1fr 1fr; }
}

@media (max-width: 430px) {
    .premium-cart-hero-icon { opacity: .35; }
    .premium-cart-hero p { max-width: 100%; }
    .premium-cart-product-card { grid-template-columns: 72px minmax(0, 1fr); }
    .premium-cart-product-image-wrap { width: 72px; height: 72px; }
    .premium-cart-product-side { grid-column: 2; }
    .premium-cart-product-label { font-size: 9px; }
    .premium-cart-product-main h3 { font-size: 16px; }
}
</style>

<script>
function changePremiumQuantity(button, delta) {
    const control = button.closest('.premium-cart-quantity-control');
    const input = control ? control.querySelector('.premium-cart-quantity-input') : null;
    if (!input) return;

    const current = parseInt(input.value || '1', 10);
    const maximum = parseInt(input.max || '99', 10);
    input.value = Math.min(maximum, Math.max(1, current + delta));
}

document.addEventListener('DOMContentLoaded', function () {
    const subtotal = <?= json_encode($total, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const deliveryPrice = document.getElementById('premiumDeliveryPrice');
    const totalPrice = document.getElementById('premiumCartTotal');
    const deliveryOptions = document.querySelectorAll('.premium-cart-delivery-option');

    function euros(value) {
        return Number(value).toLocaleString('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' €';
    }

    function updateDelivery() {
        const checked = document.querySelector('input[name="mode_livraison"]:checked');
        if (!checked) return;

        const shipping = parseFloat(checked.dataset.price || '0');
        deliveryPrice.textContent = shipping > 0 ? euros(shipping) : 'Gratuite';
        totalPrice.textContent = euros(subtotal + shipping);

        deliveryOptions.forEach(function (option) {
            const radio = option.querySelector('input[type="radio"]');
            option.classList.toggle('is-selected', radio && radio.checked);
        });
    }

    document.querySelectorAll('input[name="mode_livraison"]').forEach(function (radio) {
        radio.addEventListener('change', updateDelivery);
    });

    updateDelivery();
});
</script>

<?php include '../includes/footer.php'; ?>
