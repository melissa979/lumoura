<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header('Location: catalogue.php'); exit(); }
$productId = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id_produit = ?");
    $stmt->execute([$productId]); $product = $stmt->fetch();
    if (!$product) { header('Location: catalogue.php'); exit(); }

    $reviewsStmt = $pdo->prepare("SELECT a.*, u.nom, u.prenom FROM avis a JOIN utilisateurs u ON a.id_utilisateur = u.id_utilisateur WHERE a.id_produit = ? ORDER BY a.date_avis DESC LIMIT 5");
    $reviewsStmt->execute([$productId]); $reviews = $reviewsStmt->fetchAll();

    $avgStmt = $pdo->prepare("SELECT AVG(note) as avg_rating, COUNT(*) as review_count FROM avis WHERE id_produit = ?");
    $avgStmt->execute([$productId]); $ratingData = $avgStmt->fetch();
    $avgRating = $ratingData['avg_rating'] ? round($ratingData['avg_rating'], 1) : 0;
    $reviewCount = $ratingData['review_count'];
} catch (PDOException $e) { die('Erreur'); }

try {
    $simStmt = $pdo->prepare("SELECT * FROM produits WHERE id_categorie = ? AND id_produit != ? ORDER BY RAND() LIMIT 4");
    $simStmt->execute([$product['id_categorie'], $productId]); $similarProducts = $simStmt->fetchAll();
} catch (PDOException $e) { $similarProducts = []; }

$est_favori = false;
if (isLoggedIn()) {
    try {
        $fs = $pdo->prepare("SELECT id FROM liste_envies WHERE id_utilisateur = ? AND id_produit = ?");
        $fs->execute([$_SESSION['user_id'], $productId]); $est_favori = (bool)$fs->fetch();
    } catch (Exception $e) {}
}

$price = $product['prix'];
$discount = $product['promotion_pourcentage'];
$finalPrice = $discount > 0 ? calculateDiscount($price, $discount) : $price;

$pageTitle = $product['nom'] . " - Lumoura Joaillerie";
include '../includes/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;0,700;1,400&family=Cinzel:wght@400;600;700&family=Didact+Gothic&display=swap" rel="stylesheet">

<style>
:root{--g1:#D4A843;--g2:#F5D78E;--g3:#B8882C;--ink:#0D0A06;--ink2:#1E1710;--smoke:#F8F5EF;--stone:#E8E0D0;--muted:#8A7D6A;--red:#C0392B;}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Didact Gothic',sans-serif;background:var(--smoke);color:var(--ink);overflow-x:hidden;cursor:none;}

#cursor{position:fixed;width:10px;height:10px;background:var(--g1);border-radius:50%;pointer-events:none;z-index:99999;transform:translate(-50%,-50%);transition:width .25s,height .25s,background .25s;}
#cursor-ring{position:fixed;width:36px;height:36px;border:1px solid var(--g1);border-radius:50%;pointer-events:none;z-index:99998;transform:translate(-50%,-50%);transition:width .3s,height .3s,opacity .3s;opacity:.6;}
body.hovering #cursor{width:20px;height:20px;background:var(--g2);}
body.hovering #cursor-ring{width:54px;height:54px;opacity:.4;}

/* Flash */
.flash-bar{padding:12px 60px;font-size:.8rem;border:none;}
.flash-bar.ok{background:rgba(212,168,67,.1);color:var(--g3);border-left:3px solid var(--g1);}
.flash-bar.err{background:rgba(192,57,43,.08);color:var(--red);border-left:3px solid var(--red);}

/* Breadcrumb */
.breadcrumb{background:var(--ink);padding:13px 60px;display:flex;align-items:center;gap:10px;font-family:'Cinzel',serif;font-size:.56rem;letter-spacing:2.5px;text-transform:uppercase;}
.breadcrumb a{color:rgba(255,255,255,.35);text-decoration:none;transition:color .2s;}
.breadcrumb a:hover{color:var(--g1);}
.breadcrumb-sep{color:rgba(255,255,255,.15);}
.breadcrumb-current{color:var(--g1);}

/* ═══ LAYOUT PRODUIT ═══ */
.prod-page{max-width:1300px;margin:0 auto;padding:60px 60px 90px;display:grid;grid-template-columns:52% 48%;gap:70px;align-items:start;}

/* GALERIE */
.gallery{}
.main-img{position:relative;overflow:hidden;background:var(--stone);aspect-ratio:4/3;margin-bottom:10px;}
.main-img img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .9s cubic-bezier(.25,.46,.45,.94);cursor:zoom-in;}
.main-img:hover img{transform:scale(1.05);}
.gallery-badge{position:absolute;top:0;right:0;font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:2px;text-transform:uppercase;padding:8px 14px;z-index:5;}
.gallery-badge.promo{background:var(--ink);color:var(--g1);}
.gallery-badge.new{background:var(--g1);color:var(--ink);}

.thumbs{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;}
.thumb{cursor:pointer;overflow:hidden;border:2px solid transparent;aspect-ratio:1;transition:border-color .25s;}
.thumb.active,.thumb:hover{border-color:var(--g1);}
.thumb img{width:100%;height:100%;object-fit:cover;transition:transform .4s;}
.thumb:hover img{transform:scale(1.07);}

/* INFO COL */
.info-col{}

.prod-brand-tag{font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:4px;text-transform:uppercase;color:var(--g1);display:flex;align-items:center;gap:12px;margin-bottom:14px;animation:fadeUp .6s .1s both;}
.prod-brand-tag::before{content:'';width:28px;height:1px;background:var(--g1);}

.prod-h1{font-family:'EB Garamond',serif;font-size:clamp(1.8rem,3vw,2.8rem);font-weight:400;color:var(--ink);line-height:1.15;margin-bottom:18px;letter-spacing:.5px;animation:fadeUp .6s .2s both;}

/* Étoiles */
.prod-stars{display:flex;align-items:center;gap:10px;margin-bottom:24px;animation:fadeUp .6s .3s both;}
.stars-wrap{display:flex;gap:3px;}
.stars-wrap i{font-size:.85rem;color:var(--g1);}
.stars-wrap i.empty{color:var(--stone);}
.review-txt{font-size:.75rem;color:var(--muted);}

/* Bloc prix */
.price-block{background:var(--ink);padding:22px 26px;margin-bottom:26px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;animation:fadeUp .6s .35s both;}
.price-main{font-family:'EB Garamond',serif;font-size:2.4rem;font-weight:500;color:var(--g1);line-height:1;}
.price-old{font-size:.95rem;color:rgba(255,255,255,.3);text-decoration:line-through;}
.price-save{font-family:'Cinzel',serif;font-size:.55rem;letter-spacing:2px;background:var(--g1);color:var(--ink);padding:5px 12px;margin-left:auto;}

/* Séparateur doré */
.gold-sep{width:36px;height:1px;background:var(--g1);margin:0 0 24px;animation:fadeUp .6s .4s both;}

/* Description */
.info-section{margin-bottom:24px;animation:fadeUp .6s .45s both;}
.info-section-title{font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:4px;text-transform:uppercase;color:var(--g1);margin-bottom:12px;}
.info-section p{font-size:.86rem;color:var(--muted);line-height:1.9;}

/* Caractéristiques */
.feats-grid{border:1px solid var(--stone);animation:fadeUp .6s .5s both;}
.feat-row{display:flex;align-items:center;gap:14px;padding:11px 16px;border-bottom:1px solid var(--stone);font-size:.79rem;color:var(--ink);transition:background .25s;}
.feat-row:last-child{border-bottom:none;}
.feat-row:hover{background:rgba(212,168,67,.04);}
.feat-row i{color:var(--g1);font-size:.8rem;width:16px;flex-shrink:0;}
.feat-label{color:var(--muted);font-size:.7rem;min-width:72px;}

/* Stock */
.stock-ok{display:inline-flex;align-items:center;gap:8px;font-size:.72rem;color:#4CAF50;margin-bottom:22px;animation:fadeUp .6s .55s both;}
.stock-ok::before{content:'';width:6px;height:6px;border-radius:50%;background:#4CAF50;}
.stock-ko{color:var(--red);}
.stock-ko::before{background:var(--red);}

/* Quantité */
.qty-row{display:flex;align-items:center;gap:16px;margin-bottom:24px;animation:fadeUp .6s .6s both;}
.qty-label{font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:3px;text-transform:uppercase;color:var(--muted);}
.qty-wrap{display:flex;border:1px solid var(--stone);}
.qty-btn{width:42px;height:42px;background:transparent;border:none;font-size:1.3rem;font-family:'EB Garamond',serif;cursor:pointer;color:var(--ink);transition:background .2s,color .2s;display:flex;align-items:center;justify-content:center;}
.qty-btn:hover{background:var(--g1);color:var(--ink);}
.qty-input{width:55px;height:42px;border:none;border-left:1px solid var(--stone);border-right:1px solid var(--stone);text-align:center;font-family:'EB Garamond',serif;font-size:1.1rem;background:transparent;color:var(--ink);}
.qty-input::-webkit-inner-spin-button{-webkit-appearance:none;}

/* Boutons action */
.action-row{display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap;animation:fadeUp .6s .65s both;}
.btn-panier{flex:1;background:var(--ink);color:#fff;border:none;padding:15px 18px;font-family:'Cinzel',serif;font-size:.6rem;letter-spacing:3px;text-transform:uppercase;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .3s;min-width:150px;position:relative;overflow:hidden;}
.btn-panier::before{content:'';position:absolute;inset:0;background:var(--g1);transform:scaleX(0);transform-origin:right;transition:transform .35s cubic-bezier(.77,0,.18,1);}
.btn-panier:hover::before{transform:scaleX(1);transform-origin:left;}
.btn-panier:hover{color:var(--ink);}
.btn-panier span,.btn-panier i{position:relative;z-index:1;}

.btn-acheter{flex:1;background:var(--g1);color:var(--ink);border:none;padding:15px 18px;font-family:'Cinzel',serif;font-size:.6rem;letter-spacing:3px;text-transform:uppercase;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .3s;min-width:150px;}
.btn-acheter:hover{background:var(--g2);}

.btn-fav{width:48px;height:48px;background:transparent;border:1px solid var(--stone);color:var(--muted);cursor:pointer;font-size:1rem;transition:all .3s;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.btn-fav:hover,.btn-fav.active{border-color:var(--red);color:var(--red);background:rgba(192,57,43,.06);}
@keyframes heartPop{0%{transform:scale(1);}35%{transform:scale(1.4);}70%{transform:scale(.92);}100%{transform:scale(1);}}
.btn-fav.pop i{animation:heartPop .4s ease;}

/* Meta livraison */
.prod-meta{display:grid;grid-template-columns:1fr 1fr;gap:2px;background:var(--stone);animation:fadeUp .6s .7s both;}
.meta-item{background:var(--smoke);padding:14px 18px;display:flex;align-items:center;gap:10px;font-size:.75rem;color:var(--muted);transition:background .25s;}
.meta-item:hover{background:rgba(212,168,67,.06);}
.meta-item i{color:var(--g1);font-size:.9rem;flex-shrink:0;}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:none;}}

/* ═══ AVIS ═══ */
.reviews-section{max-width:1300px;margin:0 auto;padding:0 60px 90px;}
.section-head{display:flex;align-items:center;gap:20px;margin-bottom:40px;}
.section-head h2{font-family:'EB Garamond',serif;font-size:2rem;font-weight:400;color:var(--ink);}
.section-head-line{flex:1;height:1px;background:linear-gradient(90deg,var(--stone),transparent);}

/* Résumé rating */
.rating-summary{display:flex;gap:40px;align-items:center;background:var(--ink);padding:30px 36px;margin-bottom:36px;flex-wrap:wrap;}
.rating-big{font-family:'EB Garamond',serif;font-size:4rem;font-weight:400;color:var(--g1);line-height:1;}
.rating-info{display:flex;flex-direction:column;gap:6px;}
.rating-stars-big{display:flex;gap:4px;}
.rating-stars-big i{font-size:1rem;color:var(--g1);}
.rating-stars-big i.empty{color:rgba(255,255,255,.15);}
.rating-count-txt{font-size:.75rem;color:rgba(255,255,255,.4);letter-spacing:.5px;}
.btn-leave-review{margin-left:auto;background:transparent;border:1px solid rgba(212,168,67,.4);color:var(--g1);padding:12px 28px;font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2.5px;text-transform:uppercase;cursor:pointer;transition:all .3s;}
.btn-leave-review:hover{background:var(--g1);color:var(--ink);}

/* Liste avis */
.review-item{padding:28px 0;border-bottom:1px solid var(--stone);display:flex;gap:24px;}
.review-item:last-child{border-bottom:none;}
.review-avatar{width:44px;height:44px;background:var(--ink);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Cinzel',serif;font-size:.9rem;color:var(--g1);flex-shrink:0;}
.review-body{flex:1;}
.review-header{display:flex;align-items:center;gap:14px;margin-bottom:8px;flex-wrap:wrap;}
.review-author{font-family:'Cinzel',serif;font-size:.65rem;letter-spacing:2px;text-transform:uppercase;color:var(--ink);}
.review-stars{display:flex;gap:2px;}
.review-stars i{font-size:.72rem;color:var(--g1);}
.review-stars i.empty{color:var(--stone);}
.review-date{font-size:.7rem;color:var(--muted);margin-left:auto;}
.review-text{font-size:.84rem;color:var(--muted);line-height:1.8;}
.review-empty{text-align:center;padding:50px;color:var(--muted);font-size:.88rem;font-style:italic;}

/* Formulaire avis */
.review-form-wrap{background:var(--ink);padding:40px;margin-top:40px;}
.review-form-wrap h3{font-family:'EB Garamond',serif;font-size:1.5rem;color:#fff;font-weight:400;margin-bottom:28px;}
.form-group{margin-bottom:22px;}
.form-label{font-family:'Cinzel',serif;font-size:.56rem;letter-spacing:3px;text-transform:uppercase;color:var(--g2);display:block;margin-bottom:10px;}
.form-textarea{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.8);padding:16px;font-family:'Didact Gothic',sans-serif;font-size:.86rem;resize:vertical;min-height:120px;transition:border-color .3s;}
.form-textarea:focus{outline:none;border-color:rgba(212,168,67,.4);}
.form-textarea::placeholder{color:rgba(255,255,255,.2);}

/* Étoiles interactives */
.star-pick{display:flex;gap:6px;margin-bottom:4px;}
.star-pick i{font-size:1.5rem;color:rgba(255,255,255,.15);cursor:pointer;transition:color .2s,transform .2s;}
.star-pick i.active,.star-pick i:hover{color:var(--g1);transform:scale(1.15);}

.btn-submit-review{background:var(--g1);color:var(--ink);border:none;padding:14px 36px;font-family:'Cinzel',serif;font-size:.62rem;letter-spacing:3px;text-transform:uppercase;cursor:pointer;transition:background .3s;}
.btn-submit-review:hover{background:var(--g2);}

/* ═══ PRODUITS SIMILAIRES ═══ */
.similar-section{max-width:1300px;margin:0 auto;padding:0 60px 90px;background:var(--ink2);}
.similar-section .section-head h2{color:#fff;}
.similar-section .section-head-line{background:linear-gradient(90deg,rgba(255,255,255,.08),transparent);}
.sim-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:rgba(255,255,255,.04);}
.sim-card{background:var(--ink2);overflow:hidden;cursor:pointer;transition:transform .35s;}
.sim-card:hover{transform:translateY(-4px);box-shadow:0 20px 50px rgba(0,0,0,.3);z-index:2;}
.sim-img{height:220px;overflow:hidden;background:rgba(255,255,255,.04);}
.sim-img img{width:100%;height:100%;object-fit:cover;transition:transform .7s;}
.sim-card:hover .sim-img img{transform:scale(1.08);}
.sim-info{padding:18px 20px;}
.sim-brand{font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:3px;text-transform:uppercase;color:var(--g2);margin-bottom:6px;}
.sim-name{font-family:'EB Garamond',serif;font-size:1rem;color:rgba(255,255,255,.85);font-weight:400;margin-bottom:8px;line-height:1.3;}
.sim-price{font-family:'EB Garamond',serif;font-size:1.2rem;color:var(--g1);font-weight:500;}
.sim-price-old{font-size:.8rem;color:rgba(255,255,255,.25);text-decoration:line-through;margin-right:8px;}

/* Responsive */
@media(max-width:1024px){.prod-page{grid-template-columns:1fr;padding:40px 30px 70px;gap:40px;}.reviews-section,.similar-section{padding-left:30px;padding-right:30px;}.sim-grid{grid-template-columns:repeat(2,1fr);}.breadcrumb{padding:13px 30px;}}
@media(max-width:640px){.sim-grid{grid-template-columns:1fr;}.rating-summary{flex-direction:column;}.prod-meta{grid-template-columns:1fr;}}
</style>

<div id="cursor"></div>
<div id="cursor-ring"></div>

<?php /* Flash messages */
if(isset($_SESSION['success'])): ?>
  <div class="flash-bar ok"><i class="fas fa-check"></i> <?=htmlspecialchars($_SESSION['success'])?></div>
  <?php unset($_SESSION['success']); endif;
if(isset($_SESSION['error'])): ?>
  <div class="flash-bar err"><i class="fas fa-times"></i> <?=htmlspecialchars($_SESSION['error'])?></div>
  <?php unset($_SESSION['error']); endif; ?>

<!-- Breadcrumb -->
<nav class="breadcrumb">
  <a href="../index.php">Accueil</a>
  <span class="breadcrumb-sep">›</span>
  <a href="catalogue.php">Catalogue</a>
  <span class="breadcrumb-sep">›</span>
  <span class="breadcrumb-current"><?=sanitize($product['nom'])?></span>
</nav>

<!-- ═══ PRODUIT PRINCIPAL ═══ -->
<div class="prod-page">

  <!-- GALERIE -->
  <div class="gallery">
    <div class="main-img">
      <?php if($discount > 0): ?>
        <span class="gallery-badge promo">−<?=$discount?>%</span>
      <?php endif; ?>
      <img id="mainImg" src="<?=$product['image_url'] ?: 'https://images.unsplash.com/photo-1611590027211-b954fd027b51?w=800'?>" alt="<?=sanitize($product['nom'])?>">
    </div>
    <div class="thumbs">
      <div class="thumb active" onclick="switchImg(this,'<?=$product['image_url']?>')">
        <img src="<?=$product['image_url'] ?: 'https://images.unsplash.com/photo-1611590027211-b954fd027b51?w=800'?>" alt="Vue 1">
      </div>
      <div class="thumb" onclick="switchImg(this,'https://img.piaget.com/cards-row-split-3/d1fefdd704368d2b986c2df4dda800a64a4d5f5f.jpg')">
        <img src="https://img.piaget.com/cards-row-split-3/d1fefdd704368d2b986c2df4dda800a64a4d5f5f.jpg" alt="Vue 2">
      </div>
      <div class="thumb" onclick="switchImg(this,'https://img.piaget.com/cards-row-split-3/d1fefdd704368d2b986c2df4dda800a64a4d5f5f.jpg')">
        <img src="https://img.piaget.com/cards-row-split-3/d1fefdd704368d2b986c2df4dda800a64a4d5f5f.jpg" alt="Vue 3">
      </div>
      <div class="thumb" onclick="switchImg(this,'https://img.piaget.com/cards-row-split-3/d1fefdd704368d2b986c2df4dda800a64a4d5f5f.jpg')">
        <img src="https://img.piaget.com/cards-row-split-3/d1fefdd704368d2b986c2df4dda800a64a4d5f5f.jpg" alt="Vue 4">
      </div>
    </div>
  </div>

  <!-- INFOS -->
  <div class="info-col">

    <div class="prod-brand-tag"><?=sanitize($product['marque'])?></div>
    <h1 class="prod-h1"><?=sanitize($product['nom'])?></h1>

    <!-- Étoiles -->
    <div class="prod-stars">
      <div class="stars-wrap">
        <?php for($i=1;$i<=5;$i++): ?>
          <i class="<?=$i<=$avgRating?'fas':'far'?> fa-star<?=$i>$avgRating&&$i<=$avgRating+.5?' fa-star-half-alt':''?>"></i>
        <?php endfor; ?>
      </div>
      <span class="review-txt"><?=$avgRating?>/5 &nbsp;·&nbsp; <?=$reviewCount?> avis</span>
    </div>

    <!-- Prix -->
    <div class="price-block">
      <?php if($discount > 0): ?>
        <span class="price-old"><?=formatPrice($price)?></span>
        <span class="price-main"><?=formatPrice($finalPrice)?></span>
        <span class="price-save">−<?=$discount?>%</span>
      <?php else: ?>
        <span class="price-main"><?=formatPrice($price)?></span>
      <?php endif; ?>
    </div>

    <div class="gold-sep"></div>

    <!-- Description -->
    <div class="info-section">
      <div class="info-section-title">Description</div>
      <p><?=nl2br(sanitize($product['description']))?></p>
    </div>

    <!-- Caractéristiques -->
    <div class="info-section">
      <div class="info-section-title">Caractéristiques</div>
      <div class="feats-grid">
        <div class="feat-row"><i class="fas fa-gem"></i><span class="feat-label">Matière</span><?=$product['matiere'] ?? 'Or 18k'?></div>
        <div class="feat-row"><i class="fas fa-circle"></i><span class="feat-label">Pierre</span><?=$product['pierre'] ?? 'Diamant'?></div>
        <div class="feat-row"><i class="fas fa-ruler-combined"></i><span class="feat-label">Taille</span><?=$product['taille'] ?? 'Ajustable'?></div>
        <div class="feat-row"><i class="fas fa-tag"></i><span class="feat-label">Catégorie</span><?=htmlspecialchars($product['nom_categorie'] ?? 'Joaillerie')?></div>
        <div class="feat-row"><i class="fas fa-box"></i><span class="feat-label">Stock</span><?=$product['stock']>0?'En stock — '.$product['stock'].' pièces':'Rupture de stock'?></div>
      </div>
    </div>

    <!-- Stock indicator -->
    <div class="stock-ok <?=$product['stock']<=0?'stock-ko':''?>">
      <?=$product['stock']>0?'En stock — livraison sous 48h':'Rupture de stock'?>
    </div>

    <!-- Quantité -->
    <div class="qty-row">
      <span class="qty-label">Quantité</span>
      <div class="qty-wrap">
        <button class="qty-btn minus" onclick="changeQty(-1)">−</button>
        <input type="number" class="qty-input" id="qtyInput" value="1" min="1" max="<?=$product['stock']?>">
        <button class="qty-btn plus" onclick="changeQty(1)">+</button>
      </div>
    </div>

    <!-- Boutons -->
    <div class="action-row">
      <!-- Panier -->
      <form method="POST" action="ajouter_au_panier.php" style="flex:1;display:flex;">
        <input type="hidden" name="produit_id" value="<?=$productId?>">
        <input type="hidden" name="quantite" id="qtyHidden1" value="1">
        <input type="hidden" name="redirect" value="catalogue.php">
        <button type="submit" class="btn-panier" style="width:100%">
          <i class="fas fa-shopping-bag"></i><span>Ajouter au panier</span>
        </button>
      </form>
      <!-- Acheter -->
      <form method="POST" action="ajouter_au_panier.php" id="buyNow" style="flex:1;display:flex;">
        <input type="hidden" name="produit_id" value="<?=$productId?>">
        <input type="hidden" name="quantite" id="qtyHidden2" value="1">
        <input type="hidden" name="redirect" value="panier.php">
        <button type="submit" class="btn-acheter" style="width:100%">
          <i class="fas fa-bolt"></i> Acheter
        </button>
      </form>
      <!-- Favoris -->
      <?php if(isLoggedIn()): ?>
        <button class="btn-fav <?=$est_favori?'active':''?>" id="favBtn" data-id="<?=$productId?>" title="Favoris">
          <i class="<?=$est_favori?'fas':'far'?> fa-heart"></i>
        </button>
      <?php else: ?>
        <a href="connexion.php" class="btn-fav" title="Connexion requis"><i class="far fa-heart"></i></a>
      <?php endif; ?>
    </div>

    <!-- Meta -->
    <div class="prod-meta">
      <div class="meta-item"><i class="fas fa-shipping-fast"></i> Livraison gratuite dès 100€</div>
      <div class="meta-item"><i class="fas fa-undo"></i> Retour gratuit 30 jours</div>
      <div class="meta-item"><i class="fas fa-shield-alt"></i> Paiement 100% sécurisé</div>
      <div class="meta-item"><i class="fas fa-certificate"></i> Certificat d'authenticité</div>
    </div>

  </div>
</div>

<!-- ═══ AVIS ═══ -->
<section class="reviews-section">
  <div class="section-head">
    <h2>Avis clients</h2>
    <div class="section-head-line"></div>
  </div>

  <div class="rating-summary">
    <div class="rating-big"><?=$avgRating?></div>
    <div class="rating-info">
      <div class="rating-stars-big">
        <?php for($i=1;$i<=5;$i++): ?><i class="<?=$i<=$avgRating?'fas':'far'?> fa-star<?=$i>$avgRating&&$i<$avgRating+1?' empty':''?>"></i><?php endfor; ?>
      </div>
      <div class="rating-count-txt">Basé sur <?=$reviewCount?> avis</div>
    </div>
    <?php if(isLoggedIn()): ?>
      <button class="btn-leave-review" onclick="document.getElementById('reviewForm').scrollIntoView({behavior:'smooth'})">
        Laisser un avis
      </button>
    <?php endif; ?>
  </div>

  <?php if($reviews): ?>
    <?php foreach($reviews as $r): ?>
      <div class="review-item">
        <div class="review-avatar"><?=strtoupper(mb_substr($r['prenom'],0,1))?></div>
        <div class="review-body">
          <div class="review-header">
            <span class="review-author"><?=sanitize($r['prenom'].' '.$r['nom'])?></span>
            <div class="review-stars">
              <?php for($i=1;$i<=5;$i++): ?><i class="<?=$i<=$r['note']?'fas':'far'?> fa-star<?=$i>$r['note']?' empty':''?>"></i><?php endfor; ?>
            </div>
            <span class="review-date"><?=date('d/m/Y',strtotime($r['date_avis']))?></span>
          </div>
          <p class="review-text"><?=nl2br(sanitize($r['commentaire']))?></p>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="review-empty">Soyez le premier à laisser un avis sur ce bijou ✦</div>
  <?php endif; ?>

  <?php if(isLoggedIn()): ?>
    <div id="reviewForm" class="review-form-wrap">
      <h3>Laisser un avis</h3>
      <form method="POST" action="../includes/submit_review.php">
        <input type="hidden" name="product_id" value="<?=$productId?>">
        <div class="form-group">
          <label class="form-label">Votre note</label>
          <div class="star-pick" id="starPick">
            <?php for($i=1;$i<=5;$i++): ?><i class="fas fa-star" data-v="<?=$i?>"></i><?php endfor; ?>
          </div>
          <input type="hidden" name="rating" id="ratingVal" value="5">
        </div>
        <div class="form-group">
          <label class="form-label" for="reviewComment">Votre commentaire</label>
          <textarea id="reviewComment" name="comment" class="form-textarea" rows="5" placeholder="Partagez votre expérience avec ce bijou..." required></textarea>
        </div>
        <button type="submit" class="btn-submit-review">Publier mon avis</button>
      </form>
    </div>
  <?php endif; ?>
</section>

<!-- ═══ PRODUITS SIMILAIRES ═══ -->
<?php if($similarProducts): ?>
<section class="similar-section">
  <div class="section-head" style="padding-top:60px;">
    <h2>Vous aimerez aussi</h2>
    <div class="section-head-line"></div>
  </div>
  <div class="sim-grid">
    <?php foreach($similarProducts as $s):
      $sp = $s['prix']; $sd = $s['promotion_pourcentage'];
      $sfp = $sd > 0 ? calculateDiscount($sp,$sd) : $sp;
    ?>
      <div class="sim-card" onclick="window.location.href='produit.php?id=<?=$s['id_produit']?>'">
        <div class="sim-img"><img src="<?=$s['image_url']?>" alt="<?=sanitize($s['nom'])?>" loading="lazy"></div>
        <div class="sim-info">
          <div class="sim-brand"><?=sanitize($s['marque'])?></div>
          <div class="sim-name"><?=sanitize($s['nom'])?></div>
          <div>
            <?php if($sd>0): ?><span class="sim-price-old"><?=formatPrice($sp)?></span><?php endif; ?>
            <span class="sim-price"><?=formatPrice($sfp)?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div style="height:60px;background:var(--ink2);"></div>
</section>
<?php endif; ?>

<script>
// Curseur
const cur=document.getElementById('cursor'),ring=document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';});
function animRing(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(animRing);}
animRing();
document.querySelectorAll('a,button,.sim-card,.thumb').forEach(el=>{
  el.addEventListener('mouseenter',()=>document.body.classList.add('hovering'));
  el.addEventListener('mouseleave',()=>document.body.classList.remove('hovering'));
});

// Galerie thumbnails
function switchImg(thumb, url){
  document.getElementById('mainImg').src = url;
  document.querySelectorAll('.thumb').forEach(t=>t.classList.remove('active'));
  thumb.classList.add('active');
}

// Quantité
function changeQty(d){
  const inp=document.getElementById('qtyInput');
  const max=parseInt(inp.max)||99;
  let v=parseInt(inp.value)||1;
  v=Math.max(1,Math.min(max,v+d));
  inp.value=v;
  document.getElementById('qtyHidden1').value=v;
  document.getElementById('qtyHidden2').value=v;
}
document.getElementById('qtyInput').addEventListener('input',function(){
  document.getElementById('qtyHidden1').value=this.value;
  document.getElementById('qtyHidden2').value=this.value;
});

// Étoiles interactives
const stars=document.querySelectorAll('#starPick i');
stars.forEach(s=>s.addEventListener('click',function(){
  const v=parseInt(this.dataset.v);
  document.getElementById('ratingVal').value=v;
  stars.forEach((st,i)=>{
    st.classList.toggle('active',i<v);
    st.style.color=i<v?'#D4A843':'rgba(255,255,255,.15)';
  });
}));
// Init 5 étoiles actives
stars.forEach(s=>{s.classList.add('active');s.style.color='#D4A843';});

// Favoris AJAX
<?php if(isLoggedIn()): ?>
const favBtn=document.getElementById('favBtn');
if(favBtn){
  favBtn.addEventListener('click',function(){
    const id=this.dataset.id,icon=this.querySelector('i');
    this.classList.add('pop');icon.classList.add('pop');
    setTimeout(()=>{this.classList.remove('pop');icon.classList.remove('pop');},450);
    fetch('ajouter_favori.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'produit_id='+id+'&ajax=1'})
    .then(r=>r.json())
    .then(data=>{
      if(data.action==='added'){this.classList.add('active');icon.className='fas fa-heart';}
      else{this.classList.remove('active');icon.className='far fa-heart';}
    });
  });
}
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>