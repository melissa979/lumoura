<?php
// ============================================
// pages/produit.php — Page détail produit
// ============================================
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Vérifier l'ID produit
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: catalogue.php');
    exit();
}

$productId = (int) $_GET['id'];

// Récupérer le produit
$stmt = $pdo->prepare("
    SELECT p.*, c.nom_categorie
    FROM produits p
    LEFT JOIN categories c ON p.id_categorie = c.id_categorie
    WHERE p.id_produit = ?
    LIMIT 1
");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: catalogue.php');
    exit();
}

// ——— Vérifie si déjà en favori ———
$isFavori = false;
if (isLoggedIn()) {
    $checkFav = $pdo->prepare("SELECT id FROM favori WHERE id_utilisateur = ? AND id_produit = ? LIMIT 1");
    $checkFav->execute([$_SESSION['user_id'], $productId]);
    $isFavori = (bool) $checkFav->fetch();
}

// ——— Récupérer les avis ———
$avisStmt = $pdo->prepare("
    SELECT a.*, u.prenom, u.nom
    FROM avis a
    LEFT JOIN utilisateurs u ON a.id_utilisateur = u.id_utilisateur
    WHERE a.id_produit = ? AND a.statut = 'approuve'
    ORDER BY a.date_avis DESC
");
$avisStmt->execute([$productId]);
$avisList = $avisStmt->fetchAll(PDO::FETCH_ASSOC);

$nbAvis     = count($avisList);
$moyenneNote = $nbAvis > 0
    ? round(array_sum(array_column($avisList, 'note')) / $nbAvis, 1)
    : 0;

// ——— Produits similaires ("Vous aimerez aussi") ———
$similStmt = $pdo->prepare("
    SELECT p.id_produit, p.nom, p.prix, p.image_url, p.marque, p.promotion_pourcentage
    FROM produits p
    WHERE p.id_categorie = ? AND p.id_produit != ?
    ORDER BY RAND()
    LIMIT 4
");
$similStmt->execute([$product['id_categorie'], $productId]);
$similaires = $similStmt->fetchAll(PDO::FETCH_ASSOC);

// Prix final
$price    = (float) $product['prix'];
$discount = (float) ($product['promotion_pourcentage'] ?? 0);
$finalPrice = $discount > 0 ? $price * (1 - $discount / 100) : $price;

// ✅ CORRIGÉ : titre d'onglet
$pageTitle = htmlspecialchars($product['nom']) . " - Éclat d'Or";
include '../includes/header.php';
?>

<!-- ===== MESSAGE FLASH ===== -->
<?php if (isset($_GET['avis']) && $_GET['avis'] === 'ok'): ?>
    <div class="flash-success"><i class="fas fa-check-circle"></i> Votre avis a été publié, merci !</div>
<?php elseif (isset($_GET['avis']) && $_GET['avis'] === 'error'): ?>
    <div class="flash-error"><i class="fas fa-exclamation-circle"></i> Erreur lors de la publication de l'avis.</div>
<?php endif; ?>
<?php if (!empty($_SESSION['cart_message'])): ?>
    <div class="flash-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['cart_message']) ?></div>
    <?php unset($_SESSION['cart_message']); ?>
<?php elseif (!empty($_SESSION['cart_error'])): ?>
    <div class="flash-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['cart_error']) ?></div>
    <?php unset($_SESSION['cart_error']); ?>
<?php endif; ?>

<div class="product-detail-page">

    <!-- ===== BREADCRUMB ===== -->
    <nav class="breadcrumb-nav">
        <a href="../index.php">Accueil</a>
        <span>/</span>
        <a href="catalogue.php">Catalogue</a>
        <span>/</span>
        <?php if ($product['nom_categorie']): ?>
            <a href="catalogue.php?category=<?= urlencode($product['nom_categorie']) ?>"><?= htmlspecialchars($product['nom_categorie']) ?></a>
            <span>/</span>
        <?php endif; ?>
        <span><?= htmlspecialchars($product['nom']) ?></span>
    </nav>

    <!-- ===== SECTION PRINCIPALE ===== -->
    <div class="product-detail-container">

        <!-- IMAGE -->
        <div class="product-gallery">
            <div class="main-image">
                <img src="<?= htmlspecialchars($product['image_url'] ?: 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?auto=format&fit=crop&w=800&q=80') ?>"
                     alt="<?= htmlspecialchars($product['nom']) ?>"
                     id="mainProductImg">
                <?php if ($discount > 0): ?>
                    <span class="detail-badge">−<?= (int)$discount ?>%</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- INFOS -->
        <div class="product-detail-info">

            <?php if ($product['marque']): ?>
                <div class="detail-brand"><?= htmlspecialchars($product['marque']) ?></div>
            <?php endif; ?>

            <h1 class="detail-title"><?= htmlspecialchars($product['nom']) ?></h1>

            <!-- Note moyenne -->
            <?php if ($nbAvis > 0): ?>
            <div class="detail-rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="<?= $i <= round($moyenneNote) ? 'fas' : 'far' ?> fa-star"></i>
                <?php endfor; ?>
                <span><?= $moyenneNote ?>/5 (<?= $nbAvis ?> avis)</span>
            </div>
            <?php endif; ?>

            <!-- Prix -->
            <div class="detail-price">
                <?php if ($discount > 0): ?>
                    <span class="detail-price-original"><?= formatPrice($price) ?></span>
                <?php endif; ?>
                <span class="detail-price-final"><?= formatPrice($finalPrice) ?></span>
                <?php if ($discount > 0): ?>
                    <span class="detail-economy">Économie : <?= formatPrice($price - $finalPrice) ?></span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <?php if ($product['description_courte']): ?>
                <p class="detail-desc-short"><?= nl2br(htmlspecialchars($product['description_courte'])) ?></p>
            <?php endif; ?>

            <!-- Stock -->
            <div class="detail-stock <?= $product['stock'] > 0 ? 'en-stock' : 'hors-stock' ?>">
                <i class="fas fa-circle"></i>
                <?= $product['stock'] > 0 ? 'En stock (' . $product['stock'] . ' disponibles)' : 'Rupture de stock' ?>
            </div>

            <!-- ===== ACTIONS ===== -->
            <div class="product-actions-detail">

                <?php if ($product['stock'] > 0): ?>
                    <?php if (isLoggedIn()): ?>
                        <form method="POST" action="ajouter_au_panier.php">
                            <input type="hidden" name="produit_id" value="<?= $productId ?>">
                            <input type="hidden" name="quantite" value="1">
                            <button type="submit" name="ajouter_panier" class="btn-add-to-cart">
                                <i class="fas fa-shopping-cart"></i> Ajouter au panier
                            </button>
                        </form>
                        <button class="btn-buy-now">
                            <i class="fas fa-bolt"></i> Acheter maintenant
                        </button>
                    <?php else: ?>
                        <a href="connexion.php?redirect=<?= urlencode('produit.php?id=' . $productId) ?>" class="btn-add-to-cart">
                            <i class="fas fa-lock"></i> Connectez-vous pour acheter
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="btn-add-to-cart" disabled style="opacity:.5;cursor:not-allowed;">
                        <i class="fas fa-ban"></i> Rupture de stock
                    </button>
                <?php endif; ?>

                <!-- Bouton cœur favori -->
                <?php if (isLoggedIn()): ?>
                    <button class="btn-wishlist <?= $isFavori ? 'active' : '' ?>"
                            data-product-id="<?= $productId ?>"
                            title="<?= $isFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                        <i class="<?= $isFavori ? 'fas' : 'far' ?> fa-heart"></i>
                    </button>
                <?php else: ?>
                    <a href="connexion.php?redirect=<?= urlencode('produit.php?id=' . $productId) ?>" class="btn-wishlist" title="Connectez-vous">
                        <i class="far fa-heart"></i>
                    </a>
                <?php endif; ?>

            </div>

            <!-- Infos livraison -->
            <div class="detail-reassurance">
                <div class="reassurance-item"><i class="fas fa-shipping-fast"></i> Livraison 24-48h</div>
                <div class="reassurance-item"><i class="fas fa-undo"></i> Retour 30 jours</div>
                <div class="reassurance-item"><i class="fas fa-shield-alt"></i> Paiement sécurisé</div>
            </div>

        </div>
    </div>

    <!-- ===== DESCRIPTION COMPLÈTE ===== -->
    <?php if ($product['description']): ?>
    <div class="product-description-section">
        <h2>Description</h2>
        <div class="description-content">
            <?= nl2br(htmlspecialchars($product['description'])) ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== AVIS CLIENTS ===== -->
    <div class="reviews-section">
        <h2>Avis clients
            <?php if ($nbAvis > 0): ?>
                <span class="reviews-count">(<?= $nbAvis ?>)</span>
            <?php endif; ?>
        </h2>

        <!-- Liste des avis -->
        <?php if ($nbAvis > 0): ?>
            <div class="reviews-list">
                <?php foreach ($avisList as $avis): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-avatar">
                                <?= strtoupper(substr($avis['prenom'] ?? 'A', 0, 1)) ?>
                            </div>
                            <div class="review-meta">
                                <span class="review-author"><?= htmlspecialchars(($avis['prenom'] ?? '') . ' ' . strtoupper(substr($avis['nom'] ?? '', 0, 1)) . '.') ?></span>
                                <span class="review-date"><?= date('d/m/Y', strtotime($avis['date_avis'])) ?></span>
                            </div>
                            <div class="review-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="<?= $i <= $avis['note'] ? 'fas' : 'far' ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if (!empty($avis['commentaire'])): ?>
                            <p class="review-comment"><?= nl2br(htmlspecialchars($avis['commentaire'])) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-reviews">Aucun avis pour ce bijou. Soyez le premier à donner votre avis !</p>
        <?php endif; ?>

        <!-- Formulaire avis -->
        <?php if (isLoggedIn()): ?>
            <div class="review-form-section">
                <h3>Laisser un avis</h3>
                <form method="POST" action="../includes/submit_review.php" class="review-form" id="reviewForm">
                    <input type="hidden" name="id_produit" value="<?= $productId ?>">

                    <label class="rating-label">Votre note</label>
                    <div class="star-rating" id="starRating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="note" id="star<?= $i ?>" value="<?= $i ?>">
                            <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                    <button type="button" id="resetRating" class="btn-reset-rating">
                        <i class="fas fa-times"></i> Effacer ma note
                    </button>

                    <div class="form-group">
                        <label for="commentaire">Votre commentaire</label>
                        <textarea name="commentaire" id="commentaire" rows="4" placeholder="Partagez votre expérience avec ce bijou…"></textarea>
                    </div>

                    <button type="submit" class="btn-submit-review">
                        <i class="fas fa-paper-plane"></i> Publier mon avis
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="review-login-prompt">
                <i class="fas fa-user-circle"></i>
                <a href="connexion.php?redirect=<?= urlencode('produit.php?id=' . $productId) ?>">Connectez-vous</a> pour laisser un avis.
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== VOUS AIMEREZ AUSSI ===== -->
    <?php if (!empty($similaires)): ?>
    <div class="similaires-section">
        <h2>Vous aimerez aussi</h2>
        <div class="similaires-grid">
            <?php foreach ($similaires as $sim):
                $simPrice    = (float)$sim['prix'];
                $simDiscount = (float)($sim['promotion_pourcentage'] ?? 0);
                $simFinal    = $simDiscount > 0 ? $simPrice * (1 - $simDiscount / 100) : $simPrice;
                $simImg      = $sim['image_url'] ?: 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?auto=format&fit=crop&w=400&q=80';
            ?>
                <div class="sim-card">
                    <?php if ($simDiscount > 0): ?>
                        <span class="sim-badge">−<?= (int)$simDiscount ?>%</span>
                    <?php endif; ?>
                    <a href="produit.php?id=<?= $sim['id_produit'] ?>" class="sim-img-link">
                        <img src="<?= htmlspecialchars($simImg) ?>" alt="<?= htmlspecialchars($sim['nom']) ?>">
                    </a>
                    <div class="sim-info">
                        <?php if ($sim['marque']): ?>
                            <span class="sim-brand"><?= htmlspecialchars($sim['marque']) ?></span>
                        <?php endif; ?>
                        <h4><a href="produit.php?id=<?= $sim['id_produit'] ?>"><?= htmlspecialchars($sim['nom']) ?></a></h4>
                        <div class="sim-price">
                            <?php if ($simDiscount > 0): ?>
                                <span class="sim-original"><?= formatPrice($simPrice) ?></span>
                            <?php endif; ?>
                            <span class="sim-final"><?= formatPrice($simFinal) ?></span>
                        </div>
                        <a href="produit.php?id=<?= $sim['id_produit'] ?>" class="btn-voir-sim">
                            <i class="fas fa-eye"></i> Voir
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- ===== STYLES ===== -->
<style>
:root {
    --gold:#d4af37; --gold-dark:#b8972e; --brown:#3d2b1f;
    --red:#e74c3c; --green:#27ae60; --cream:#fdfbf7;
    --shadow:0 4px 20px rgba(0,0,0,.09);
    --radius:14px;
}

/* Flash */
.flash-success,.flash-error {
    max-width:900px; margin:20px auto; padding:14px 20px;
    border-radius:10px; font-weight:600; display:flex; align-items:center; gap:10px;
}
.flash-success { background:#eafaf1; border-left:4px solid var(--green); color:var(--green); }
.flash-error   { background:#fdf0f0; border-left:4px solid var(--red);   color:var(--red); }

/* Page */
.product-detail-page { max-width:1100px; margin:0 auto; padding:30px 20px 80px; }

/* Breadcrumb */
.breadcrumb-nav { font-size:13px; color:#bbb; margin-bottom:28px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.breadcrumb-nav a { color:var(--gold-dark); text-decoration:none; }
.breadcrumb-nav a:hover { color:var(--brown); }

/* Conteneur principal */
.product-detail-container {
    display:grid; grid-template-columns:1fr 1fr; gap:50px;
    align-items:start; margin-bottom:60px;
}
@media(max-width:768px) { .product-detail-container { grid-template-columns:1fr; gap:28px; } }

/* Galerie */
.main-image { position:relative; border-radius:var(--radius); overflow:hidden; box-shadow:var(--shadow); }
.main-image img { width:100%; height:480px; object-fit:cover; display:block; transition:transform .4s; }
.main-image:hover img { transform:scale(1.04); }
.detail-badge {
    position:absolute; top:14px; left:14px;
    background:linear-gradient(135deg,#e74c3c,#c0392b);
    color:white; padding:6px 14px; border-radius:20px;
    font-size:12px; font-weight:800;
}

/* Infos */
.detail-brand { font-size:11px; font-weight:800; color:var(--gold); text-transform:uppercase; letter-spacing:2px; margin-bottom:8px; }
.detail-title { font-size:2rem; color:var(--brown); margin:0 0 14px; font-family:'Playfair Display',Georgia,serif; line-height:1.2; }

/* Note */
.detail-rating { display:flex; align-items:center; gap:8px; margin-bottom:18px; }
.detail-rating .fa-star { color:var(--gold); font-size:16px; }
.detail-rating span { font-size:14px; color:#888; }

/* Prix */
.detail-price { display:flex; align-items:baseline; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.detail-price-original { text-decoration:line-through; color:#ccc; font-size:16px; }
.detail-price-final { font-size:2.2rem; font-weight:900; color:var(--brown); }
.detail-economy { font-size:13px; font-weight:700; color:var(--green); background:#eafaf1; padding:3px 10px; border-radius:12px; }

/* Description courte */
.detail-desc-short { color:#666; line-height:1.7; margin-bottom:18px; font-size:15px; }

/* Stock */
.detail-stock { font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px; margin-bottom:24px; }
.detail-stock i { font-size:9px; }
.detail-stock.en-stock  { color:var(--green); }
.detail-stock.hors-stock{ color:var(--red); }

/* Actions */
.product-actions-detail { display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:28px; }
.product-actions-detail form { flex:1; min-width:160px; }

.btn-add-to-cart {
    width:100%; padding:14px 20px;
    background:linear-gradient(135deg,var(--gold),var(--gold-dark));
    color:white; border:none; border-radius:12px;
    font-size:15px; font-weight:700; cursor:pointer;
    display:flex; align-items:center; justify-content:center; gap:8px;
    transition:all .25s; box-shadow:0 4px 16px rgba(212,175,55,.35);
    text-decoration:none;
}
.btn-add-to-cart:hover { transform:translateY(-2px); box-shadow:0 6px 22px rgba(212,175,55,.5); }

.btn-buy-now {
    padding:14px 20px;
    background:var(--brown); color:white;
    border:none; border-radius:12px; font-size:14px; font-weight:700;
    cursor:pointer; display:flex; align-items:center; gap:8px;
    transition:all .25s; white-space:nowrap;
}
.btn-buy-now:hover { background:#2c1e15; transform:translateY(-2px); }

/* Bouton cœur */
.btn-wishlist {
    width:50px; height:50px; flex-shrink:0;
    background:white; border:2px solid #e0d5c5; border-radius:50%;
    font-size:20px; color:#ccc; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    text-decoration:none; transition:all .25s;
}
.btn-wishlist:hover { border-color:var(--red); color:var(--red); background:#fff5f5; transform:scale(1.1); }
.btn-wishlist.active { background:#fff0f0; border-color:var(--red); color:var(--red); }
@keyframes heartPop { 0%{transform:scale(1)} 40%{transform:scale(1.5)} 70%{transform:scale(.9)} 100%{transform:scale(1)} }
.btn-wishlist.pop { animation:heartPop .4s ease forwards; }

/* Réassurance */
.detail-reassurance { display:flex; gap:20px; flex-wrap:wrap; }
.reassurance-item { font-size:12px; color:#888; display:flex; align-items:center; gap:6px; }
.reassurance-item i { color:var(--gold); }

/* Description section */
.product-description-section { background:white; border-radius:var(--radius); padding:36px; margin-bottom:50px; box-shadow:var(--shadow); }
.product-description-section h2 { font-size:1.5rem; color:var(--brown); margin:0 0 18px; font-family:'Playfair Display',Georgia,serif; }
.description-content { color:#555; line-height:1.8; font-size:15px; }

/* Avis */
.reviews-section { background:white; border-radius:var(--radius); padding:36px; margin-bottom:50px; box-shadow:var(--shadow); }
.reviews-section h2 { font-size:1.5rem; color:var(--brown); margin:0 0 28px; font-family:'Playfair Display',Georgia,serif; }
.reviews-count { font-size:1rem; color:#999; font-weight:400; }

.reviews-list { display:flex; flex-direction:column; gap:18px; margin-bottom:40px; }
.review-card { background:#fdfbf7; border:1px solid #f0ebe3; border-radius:12px; padding:20px; }
.review-header { display:flex; align-items:center; gap:14px; margin-bottom:12px; flex-wrap:wrap; }
.review-avatar {
    width:40px; height:40px; border-radius:50%;
    background:linear-gradient(135deg,var(--gold),var(--gold-dark));
    color:white; font-weight:800; font-size:16px;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.review-meta { flex:1; }
.review-author { display:block; font-weight:700; color:var(--brown); font-size:14px; }
.review-date   { font-size:12px; color:#bbb; }
.review-stars .fa-star { color:var(--gold); font-size:13px; }
.review-comment { color:#555; font-size:14px; line-height:1.6; margin:0; }
.no-reviews { color:#bbb; font-style:italic; margin-bottom:30px; }

/* Formulaire avis */
.review-form-section { border-top:1px solid #f0ebe3; padding-top:30px; }
.review-form-section h3 { font-size:1.2rem; color:var(--brown); margin:0 0 22px; }
.rating-label { display:block; font-size:14px; font-weight:600; color:var(--brown); margin-bottom:10px; }
.form-group { margin-bottom:20px; }
.form-group label { display:block; font-size:14px; font-weight:600; color:var(--brown); margin-bottom:8px; }
.form-group textarea {
    width:100%; padding:14px; border:1.5px solid #e8e0d5;
    border-radius:10px; font-size:14px; resize:vertical; min-height:110px;
    font-family:inherit; color:var(--brown); transition:border .2s;
    box-sizing:border-box;
}
.form-group textarea:focus { outline:none; border-color:var(--gold); }

/* Étoiles interactives — état vide forcé par défaut */
.star-rating { display:flex; flex-direction:row-reverse; gap:4px; width:fit-content; margin-bottom:10px; }
.star-rating input[type="radio"] { display:none !important; }
.star-rating label { font-size:28px; cursor:pointer; transition:color .15s; }
.star-rating label i.fa-star,
.star-rating label i { color:#ddd !important; }
.star-rating input[type="radio"]:checked ~ label i { color:var(--gold) !important; }
.star-rating label:hover i,
.star-rating label:hover ~ label i { color:var(--gold) !important; }

/* Bouton "Effacer ma note" */
.btn-reset-rating {
    display:inline-flex; align-items:center; gap:6px;
    background:none; border:none; color:#aaa; font-size:12px;
    cursor:pointer; margin-bottom:22px; padding:2px 0;
    text-decoration:underline; transition:color .2s;
}
.btn-reset-rating:hover { color:var(--red); }

.btn-submit-review {
    padding:13px 30px;
    background:linear-gradient(135deg,var(--gold),var(--gold-dark));
    color:white; border:none; border-radius:12px;
    font-size:15px; font-weight:700; cursor:pointer;
    display:inline-flex; align-items:center; gap:8px;
    transition:all .25s; box-shadow:0 4px 14px rgba(212,175,55,.35);
}
.btn-submit-review:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(212,175,55,.5); }

.review-login-prompt {
    padding:20px; background:#fdfbf7; border-radius:10px;
    color:#888; font-size:14px; text-align:center;
    border:1px dashed #e0d5c5;
}
.review-login-prompt a { color:var(--gold-dark); font-weight:700; }

/* Vous aimerez aussi */
.similaires-section { margin-top:10px; }
.similaires-section h2 { font-size:1.6rem; color:var(--brown); margin-bottom:28px; font-family:'Playfair Display',Georgia,serif; text-align:center; }
.similaires-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:24px; }

.sim-card {
    background:white; border-radius:var(--radius); overflow:hidden;
    box-shadow:var(--shadow); border:1px solid #f0ebe3;
    transition:transform .25s,box-shadow .25s; position:relative;
}
.sim-card:hover { transform:translateY(-5px); box-shadow:0 8px 30px rgba(0,0,0,.13); }
.sim-badge {
    position:absolute; top:10px; left:10px; z-index:3;
    background:linear-gradient(135deg,#e74c3c,#c0392b);
    color:white; padding:4px 10px; border-radius:16px; font-size:11px; font-weight:800;
}
.sim-img-link { display:block; overflow:hidden; height:180px; }
.sim-img-link img { width:100%; height:100%; object-fit:cover; transition:transform .35s; }
.sim-card:hover .sim-img-link img { transform:scale(1.06); }
.sim-info { padding:16px; }
.sim-brand { font-size:10px; font-weight:800; color:var(--gold); text-transform:uppercase; letter-spacing:1.5px; }
.sim-info h4 { font-size:14px; font-weight:700; color:var(--brown); margin:5px 0 8px; }
.sim-info h4 a { text-decoration:none; color:inherit; }
.sim-info h4 a:hover { color:var(--gold-dark); }
.sim-price { display:flex; align-items:baseline; gap:8px; margin-bottom:12px; }
.sim-original { text-decoration:line-through; color:#ccc; font-size:12px; }
.sim-final { font-size:17px; font-weight:800; color:var(--brown); }
.btn-voir-sim {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; background:var(--gold); color:white;
    border-radius:8px; font-size:13px; font-weight:700;
    text-decoration:none; transition:all .2s;
}
.btn-voir-sim:hover { background:var(--gold-dark); transform:translateY(-1px); }

@media(max-width:480px) {
    .similaires-grid { grid-template-columns:1fr 1fr; }
    .detail-title { font-size:1.5rem; }
    .detail-price-final { font-size:1.7rem; }
}
</style>

<!-- ===== SCRIPT : reset de la note + wishlist ===== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const resetBtn = document.getElementById('resetRating');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            document.querySelectorAll('#starRating input[name="note"]').forEach(function (input) {
                input.checked = false;
            });
        });
    }

    // ✅ CORRIGÉ : bouton cœur favori (fiche produit)
    const wishBtn = document.querySelector('.btn-wishlist[data-product-id]');
    if (wishBtn) {
        wishBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const idProduit = this.dataset.productId;
            const self = this;
            self.style.pointerEvents = 'none';

            const formData = new FormData();
            formData.append('id_produit', idProduit);

            fetch('../includes/toggle_wishlist.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    self.style.pointerEvents = '';
                    if (data.success) {
                        self.classList.add('pop');
                        setTimeout(() => self.classList.remove('pop'), 400);
                        if (data.action === 'added') {
                            self.classList.add('active');
                            self.innerHTML = '<i class="fas fa-heart"></i>';
                            self.title = 'Retirer des favoris';
                        } else {
                            self.classList.remove('active');
                            self.innerHTML = '<i class="far fa-heart"></i>';
                            self.title = 'Ajouter aux favoris';
                        }
                    } else if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                })
                .catch(() => { self.style.pointerEvents = ''; });
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>