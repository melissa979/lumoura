<?php
// Page panier - version adaptée pour Lumoura Joaillerie
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

// ─── SÉLECTION MODE DE LIVRAISON ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'choisir_livreur') {
    $_SESSION['livreur_id'] = intval($_POST['livreur_id'] ?? 0);
    header('Location: panier.php'); exit();
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
            unset($_SESSION['livreur_id']);
            break;
    }
    
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
        
    } catch (PDOException $e) {
        die('Erreur lors du chargement du panier');
    }
}

// ─── CHARGEMENT MODES DE LIVRAISON ACTIFS ───
$livreurs = [];
try {
    $livreurs = $pdo->query("SELECT * FROM livreurs WHERE statut = 'actif' ORDER BY prix ASC")->fetchAll();
} catch(Exception $e) {}

// ─── MODE SÉLECTIONNÉ ───
$livreur_choisi    = null;
$livreur_id_choisi = $_SESSION['livreur_id'] ?? 0;
if ($livreur_id_choisi && $livreurs) {
    foreach ($livreurs as $lv) {
        if ($lv['id_livreur'] == $livreur_id_choisi) {
            $livreur_choisi = $lv;
            break;
        }
    }
}

// ─── CALCUL TOTAL ───
$livraison_offerte = false; // Prix toujours réels
$shipping   = $livreur_choisi ? floatval($livreur_choisi['prix']) : 0;
$cart_total = $cart_subtotal + $shipping;

// ─── Mapping icône → emoji ───
$emoji_map = [
    'fa-box'          => '📦',
    'fa-bolt'         => '⚡',
    'fa-shipping-fast'=> '🚀',
    'fa-truck'        => '🚚',
    'fa-envelope'     => '✉️',
    'fa-store'        => '🏪',
    'fa-plane'        => '✈️',
    'fa-bicycle'      => '🚲',
];

$pageTitle = "Panier - Lumoura Joaillerie";
include '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Didact+Gothic&family=EB+Garamond:ital,wght@0,400;0,500;0,700;1,400;1,600&family=Cinzel:wght@400;600;700;900&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════
   VARIABLES & RESET
═══════════════════════════════════════════════ */
:root {
  --g1: #D4A843;
  --g2: #F5D78E;
  --g3: #B8882C;
  --g4: #FFF0C0;
  --ink: #0D0A06;
  --ink2: #1E1710;
  --smoke: #F8F5EF;
  --stone: #E8E0D0;
  --muted: #8A7D6A;
  --red: #C0392B;
  --vert-liv: #2a9d8f;
}

*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

body {
  font-family: 'Didact Gothic', sans-serif;
  background: var(--smoke);
  color: var(--ink);
  overflow-x: hidden;
}

/* ═══════════════════════════════════════════════
   CURSEUR PERSONNALISÉ
═══════════════════════════════════════════════ */
#cursor {
  position: fixed;
  width: 10px; height: 10px;
  background: var(--g1);
  border-radius: 50%;
  pointer-events: none;
  z-index: 99999;
  transform: translate(-50%,-50%);
  transition: transform .1s, width .25s, height .25s, background .25s;
}
#cursor-ring {
  position: fixed;
  width: 36px; height: 36px;
  border: 1px solid var(--g1);
  border-radius: 50%;
  pointer-events: none;
  z-index: 99998;
  transform: translate(-50%,-50%);
  transition: transform .08s linear, width .3s, height .3s, opacity .3s, border-color .3s;
  opacity: .6;
}
body.hovering #cursor { width: 20px; height: 20px; background: var(--g2); }
body.hovering #cursor-ring { width: 54px; height: 54px; border-color: var(--g2); opacity: .4; }

/* ═══════════════════════════════════════════════
   PAGE HEADER
═══════════════════════════════════════════════ */
.page-hero {
  background: var(--ink);
  padding: 100px 20px 60px;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.page-hero::before {
  content: '';
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(212,168,67,.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(212,168,67,.04) 1px, transparent 1px);
  background-size: 50px 50px;
  z-index: 1;
}
.page-hero-content { position: relative; z-index: 2; }
.page-hero-tag {
  font-family: 'Cinzel', serif;
  font-size: .58rem; letter-spacing: 5px; text-transform: uppercase;
  color: var(--g2);
  display: flex; align-items: center; justify-content: center; gap: 14px;
  margin-bottom: 18px;
}
.page-hero-tag::before, .page-hero-tag::after {
  content: ''; width: 40px; height: 1px; background: var(--g1);
}
.page-hero h1 {
  font-family: 'EB Garamond', serif;
  font-size: clamp(2.8rem, 5vw, 4.2rem); font-weight: 400;
  color: #fff; letter-spacing: 1px; margin-bottom: 15px;
}
.page-hero h1 em { font-style: italic; color: var(--g2); }
.page-hero p { font-size: .95rem; color: rgba(255,255,255,.5); letter-spacing: .5px; }

/* ═══════════════════════════════════════════════
   ALERT MESSAGE
═══════════════════════════════════════════════ */
.alert {
  max-width: 1200px; margin: 30px auto;
  padding: 18px 25px;
  border-left: 3px solid var(--g1);
  background: rgba(212,168,67,.08);
  font-family: 'Cinzel', serif; font-size: .75rem; letter-spacing: 1.5px;
  color: var(--ink);
  display: flex; align-items: center; gap: 12px;
  animation: slideDown .5s ease;
}
@keyframes slideDown {
  from { opacity: 0; transform: translateY(-20px); }
  to   { opacity: 1; transform: translateY(0); }
}
.alert i { color: var(--g1); font-size: 1rem; }

/* ═══════════════════════════════════════════════
   EMPTY CART
═══════════════════════════════════════════════ */
.empty-cart {
  max-width: 600px; margin: 80px auto; padding: 80px 40px;
  text-align: center; background: #fff; border: 1px solid var(--stone);
  position: relative;
}
.empty-cart::before {
  content: '';
  position: absolute; top: 0; left: 50%;
  transform: translateX(-50%);
  width: 150px; height: 3px;
  background: linear-gradient(90deg, transparent, var(--g1), transparent);
}
.empty-cart i { font-size: 4rem; color: var(--g1); margin-bottom: 25px; opacity: .3; }
.empty-cart h2 { font-family: 'EB Garamond', serif; font-size: 2rem; font-weight: 400; color: var(--ink); margin-bottom: 15px; }
.empty-cart p  { color: var(--muted); font-size: .9rem; margin-bottom: 35px; }

.btn-primary {
  display: inline-flex; align-items: center; gap: 10px;
  background: var(--g1); color: var(--ink);
  padding: 15px 35px;
  font-family: 'Cinzel', serif; font-size: .68rem; letter-spacing: 3px;
  text-transform: uppercase; text-decoration: none; font-weight: 600;
  transition: all .3s; position: relative; overflow: hidden;
}
.btn-primary::before {
  content: ''; position: absolute; inset: 0;
  background: var(--g2);
  transform: scaleX(0); transform-origin: right;
  transition: transform .35s cubic-bezier(.77,0,.18,1);
}
.btn-primary:hover::before { transform: scaleX(1); transform-origin: left; }
.btn-primary span, .btn-primary i { position: relative; z-index: 1; }

/* ═══════════════════════════════════════════════
   CART CONTAINER
═══════════════════════════════════════════════ */
.cart-container {
  max-width: 1400px; margin: 50px auto;
  padding: 0 40px;
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 50px;
  align-items: start;
}

/* ═══════════════════════════════════════════════
   CART ITEMS
═══════════════════════════════════════════════ */
.cart-items { background: #fff; border: 1px solid var(--stone); }
.cart-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 30px 35px;
  border-bottom: 2px solid var(--g1);
}
.cart-header h2 { font-family: 'EB Garamond', serif; font-size: 1.8rem; font-weight: 400; color: var(--ink); }
.btn-clear {
  background: none; border: none;
  font-family: 'Cinzel', serif; font-size: .62rem; letter-spacing: 2px; text-transform: uppercase;
  color: var(--red); cursor: pointer;
  display: flex; align-items: center; gap: 8px;
  transition: opacity .3s;
}
.btn-clear:hover { opacity: .7; }

.cart-item {
  display: grid;
  grid-template-columns: 140px 1fr auto auto auto auto;
  gap: 25px; align-items: center;
  padding: 30px 35px;
  border-bottom: 1px solid var(--stone);
  position: relative; transition: background .3s;
}
.cart-item:hover { background: rgba(212,168,67,.02); }
.cart-item-image { width: 140px; height: 140px; overflow: hidden; background: var(--smoke); border: 1px solid var(--stone); }
.cart-item-image img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s ease; }
.cart-item:hover .cart-item-image img { transform: scale(1.08); }
.cart-item-details h3 { font-family: 'EB Garamond', serif; font-size: 1.2rem; font-weight: 400; color: var(--ink); margin-bottom: 6px; }
.cart-item-brand { font-family: 'Cinzel', serif; font-size: .58rem; letter-spacing: 3px; text-transform: uppercase; color: var(--g1); margin-bottom: 8px; }
.cart-item-detail { font-size: .8rem; color: var(--muted); }
.cart-item-price { font-family: 'EB Garamond', serif; font-size: 1.3rem; font-weight: 500; color: var(--ink); white-space: nowrap; }
.cart-item-quantity { display: flex; align-items: center; }
.quantity-form { display: flex; align-items: center; border: 1px solid var(--stone); }
.quantity-btn {
  width: 35px; height: 35px;
  background: var(--smoke); border: none;
  font-size: .9rem; color: var(--ink);
  cursor: pointer; transition: all .3s;
  display: flex; align-items: center; justify-content: center;
}
.quantity-btn:hover { background: var(--g1); color: var(--ink); }
.quantity-input {
  width: 50px; height: 35px;
  border: none;
  border-left: 1px solid var(--stone); border-right: 1px solid var(--stone);
  text-align: center;
  font-family: 'Cinzel', serif; font-size: .75rem; font-weight: 600; color: var(--ink); background: #fff;
}
.quantity-input:focus { outline: none; background: rgba(212,168,67,.05); }
.cart-item-total { font-family: 'EB Garamond', serif; font-size: 1.5rem; font-weight: 600; color: var(--g1); white-space: nowrap; min-width: 100px; text-align: right; }
.btn-remove-item {
  width: 36px; height: 36px;
  background: transparent; border: 1px solid var(--stone);
  color: var(--muted); font-size: .85rem;
  cursor: pointer; transition: all .3s;
  display: flex; align-items: center; justify-content: center;
}
.btn-remove-item:hover { background: var(--red); border-color: var(--red); color: #fff; }

/* ═══════════════════════════════════════════════
   BLOC LIVRAISON — CARTES DORÉES
═══════════════════════════════════════════════ */
.livraison-bloc-panier {
  background: #fff;
  border: 1px solid var(--stone);
  border-top: none;
  padding: 30px 35px;
}

.livraison-bloc-titre {
  font-family: 'EB Garamond', serif;
  font-size: 1.5rem; font-weight: 400; color: var(--ink);
  border-bottom: 2px solid var(--g1);
  padding-bottom: 14px; margin-bottom: 22px;
  display: flex; align-items: center; gap: 10px;
}
.livraison-bloc-titre i { color: var(--g1); font-size: 1.1rem; }

/* Bandeau livraison offerte */
.bandeau-gratuit-liv {
  display: flex; align-items: center; gap: 9px;
  padding: 11px 16px;
  background: #f0fdf4; border: 2px solid #86efac;
  border-radius: 10px; color: #166534;
  font-size: .88em; margin-bottom: 18px;
}

/* Grille cartes */
.livraison-cards { display: grid; gap: 12px; }

/* Carte individuelle */
.liv-card {
  display: flex; align-items: center; gap: 16px;
  padding: 16px 18px;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  cursor: pointer; background: #fff;
  transition: border-color .25s, background .25s, box-shadow .25s;
  user-select: none;
}
.liv-card.active {
  border-color: var(--g1);
  background: #fffbf0;
  box-shadow: 0 4px 14px rgba(212,160,23,.18);
}
.liv-card:hover:not(.active) {
  border-color: #c0c0c0;
  box-shadow: 0 3px 10px rgba(0,0,0,.07);
}

/* Radio */
.liv-card input[type="radio"] {
  width: 19px; height: 19px;
  accent-color: var(--g1);
  flex-shrink: 0; cursor: pointer;
}

/* Emoji */
.liv-card-emoji { font-size: 1.9em; flex-shrink: 0; }

/* Texte */
.liv-card-info { flex: 1; }
.liv-card-nom {
  font-weight: 700; font-size: 1em; color: var(--ink);
  margin-bottom: 3px;
  display: flex; align-items: center; flex-wrap: wrap; gap: 6px;
}
.liv-card-desc { font-size: .82em; color: var(--muted); }

/* Badge */
.liv-badge {
  font-size: .65em; padding: 2px 8px;
  border-radius: 10px; color: #fff; font-weight: 600;
}
.liv-badge-rouge { background: var(--red); }
.liv-badge-vert  { background: var(--vert-liv); }

/* Prix */
.liv-card-prix {
  font-size: 1.1em; font-weight: 700; flex-shrink: 0;
}
.liv-prix-gratuit { color: var(--vert-liv); }
.liv-prix-normal  { color: var(--ink); }

/* ═══════════════════════════════════════════════
   CART SUMMARY
═══════════════════════════════════════════════ */
.cart-summary {
  background: var(--ink); padding: 40px;
  position: sticky; top: 100px;
  border: 2px solid var(--g1);
}
.summary-title { font-family: 'EB Garamond', serif; font-size: 1.8rem; font-weight: 400; color: #fff; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid rgba(212,168,67,.3); }
.summary-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; font-size: .9rem; color: rgba(255,255,255,.7); border-bottom: 1px solid rgba(255,255,255,.08); }
.summary-row span:last-child { font-weight: 600; color: rgba(255,255,255,.9); }
.summary-total { margin-top: 10px; padding-top: 20px; border-top: 2px solid var(--g1); border-bottom: none; font-family: 'Cinzel', serif; font-size: 1rem; letter-spacing: 2px; text-transform: uppercase; }
.summary-total span { color: var(--g2); font-size: 1.8rem; }
.summary-mode { margin: 8px 0; padding: 12px 14px; background: rgba(212,168,67,.08); border-left: 3px solid var(--g1); }
.summary-mode-label { font-family: 'Cinzel', serif; font-size: .55rem; letter-spacing: 2px; text-transform: uppercase; color: var(--g1); margin-bottom: 3px; }
.summary-mode-value { font-size: .82rem; color: rgba(255,255,255,.6); display: flex; align-items: center; gap: 8px; }
.summary-mode-value i { color: var(--g1); font-size: .8rem; }
.ship-progress-wrap { margin: 0 0 6px; padding: 14px 0 10px; border-bottom: 1px solid rgba(255,255,255,.08); }
.ship-progress-text { font-size: .74rem; color: rgba(255,255,255,.45); display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.ship-progress-text i { color: var(--g1); }
.ship-progress-text.done { color: #2ecc71; }
.ship-progress-text.done i { color: #2ecc71; }
.ship-bar { height: 3px; background: rgba(255,255,255,.08); border-radius: 2px; overflow: hidden; }
.ship-bar-fill { height: 100%; background: var(--g1); border-radius: 2px; transition: width .5s ease; }
.summary-note { margin-top: 30px; padding: 25px; background: rgba(212,168,67,.08); border-left: 3px solid var(--g1); }
.summary-note p { font-size: .75rem; color: rgba(255,255,255,.6); margin-bottom: 12px; display: flex; align-items: center; gap: 10px; }
.summary-note p:last-child { margin-bottom: 0; }
.summary-note i { color: var(--g1); font-size: .85rem; }
.btn-checkout {
  display: flex; align-items: center; justify-content: center; gap: 12px;
  width: 100%; margin-top: 30px; padding: 18px;
  background: var(--g1); border: 2px solid var(--g1); color: var(--ink);
  font-family: 'Cinzel', serif; font-size: .72rem; letter-spacing: 3px; text-transform: uppercase; font-weight: 700;
  text-decoration: none; transition: all .35s; position: relative; overflow: hidden;
}
.btn-checkout::before { content: ''; position: absolute; inset: 0; background: var(--g2); transform: scaleX(0); transform-origin: left; transition: transform .35s cubic-bezier(.77,0,.18,1); }
.btn-checkout:hover::before { transform: scaleX(1); }
.btn-checkout i, .btn-checkout span { position: relative; z-index: 1; }
.btn-checkout.disabled { opacity: .45; pointer-events: none; cursor: not-allowed; }
.checkout-warning { text-align: center; margin-top: 10px; font-size: .72rem; color: rgba(212,168,67,.6); display: flex; align-items: center; justify-content: center; gap: 6px; }
.continue-shopping { text-align: center; margin-top: 25px; padding-top: 25px; border-top: 1px solid rgba(255,255,255,.1); }
.continue-shopping a { font-family: 'Cinzel', serif; font-size: .62rem; letter-spacing: 2px; text-transform: uppercase; color: var(--g2); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: gap .3s; }
.continue-shopping a:hover { gap: 12px; }

/* ═══════════════════════════════════════════════
   RECOMMENDED PRODUCTS
═══════════════════════════════════════════════ */
.recommended-products { max-width: 1400px; margin: 80px auto; padding: 0 40px; }
.recommended-products h2 { font-family: 'EB Garamond', serif; font-size: clamp(2rem, 3.5vw, 2.8rem); font-weight: 400; text-align: center; color: var(--ink); margin-bottom: 50px; }
.recommended-products h2::after { content: ''; display: block; width: 80px; height: 2px; background: linear-gradient(90deg, transparent, var(--g1), transparent); margin: 20px auto 0; }
.products-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: var(--stone); border: 1px solid var(--stone); }
.product-card { background: #fff; transition: transform .4s cubic-bezier(.23,1,.32,1); position: relative; }
.product-card:hover { transform: translateY(-6px); z-index: 2; box-shadow: 0 20px 50px rgba(0,0,0,.12); }
.product-image { height: 260px; overflow: hidden; background: var(--smoke); }
.product-image img { width: 100%; height: 100%; object-fit: cover; transition: transform .8s ease; }
.product-card:hover .product-image img { transform: scale(1.1); }
.product-info { padding: 25px; border-top: 1px solid var(--stone); }
.product-brand { font-family: 'Cinzel', serif; font-size: .52rem; letter-spacing: 3px; text-transform: uppercase; color: var(--g1); margin-bottom: 8px; }
.product-name { font-family: 'EB Garamond', serif; font-size: 1.05rem; font-weight: 400; color: var(--ink); margin-bottom: 15px; line-height: 1.3; }
.product-price { display: flex; align-items: baseline; gap: 10px; margin-bottom: 18px; }
.price-original { font-size: .8rem; color: var(--muted); text-decoration: line-through; }
.price-current { font-family: 'EB Garamond', serif; font-size: 1.25rem; font-weight: 500; color: var(--ink); }
.btn-cart { width: 100%; padding: 12px; background: var(--g1); border: 1px solid var(--g1); color: var(--ink); font-family: 'Cinzel', serif; font-size: .6rem; letter-spacing: 2px; text-transform: uppercase; font-weight: 600; cursor: pointer; transition: all .3s; display: flex; align-items: center; justify-content: center; gap: 8px; }
.btn-cart:hover { background: var(--g2); border-color: var(--g2); }

/* ═══════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════ */
@media (max-width: 1100px) {
  .cart-container { grid-template-columns: 1fr; gap: 40px; }
  .cart-summary { position: static; }
  .products-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
  .cart-container { padding: 0 20px; }
  .cart-item { grid-template-columns: 1fr; text-align: center; gap: 15px; }
  .cart-item-image { margin: 0 auto; }
  .cart-item-total { text-align: center; }
  .btn-remove-item { position: absolute; top: 15px; right: 15px; }
  .products-grid { grid-template-columns: repeat(2, 1fr); }
  .cart-header { flex-direction: column; align-items: flex-start; gap: 15px; }
  .livraison-bloc-panier { padding: 22px 20px; }
  .liv-card { flex-wrap: wrap; }
  .liv-card-prix { width: 100%; text-align: right; }
}
@media (max-width: 480px) {
  .products-grid { grid-template-columns: 1fr; }
  .page-hero { padding: 80px 20px 50px; }
}
</style>

<!-- CURSEUR -->
<div id="cursor"></div>
<div id="cursor-ring"></div>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="page-hero-content">
    <div class="page-hero-tag">Votre Sélection</div>
    <h1>Mon <em>Panier</em></h1>
    <p>Vos bijoux d'exception sélectionnés avec soin</p>
  </div>
</section>

<!-- ALERT MESSAGE -->
<?php if (isset($_SESSION['cart_message'])): ?>
    <div class="alert">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($_SESSION['cart_message']) ?>
    </div>
    <?php unset($_SESSION['cart_message']); ?>
<?php endif; ?>

<!-- CART CONTENT -->
<?php if (empty($cart_items)): ?>
    <div class="empty-cart">
        <i class="fas fa-gem"></i>
        <h2>Votre panier est vide</h2>
        <p>Découvrez nos créations exceptionnelles</p>
        <a href="catalogue.php" class="btn-primary">
            <i class="fas fa-store"></i>
            <span>Découvrir nos collections</span>
        </a>
    </div>
<?php else: ?>
    <div class="cart-container">

        <!-- COLONNE GAUCHE : articles + livraison -->
        <div>

            <!-- CART ITEMS -->
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
                            <div class="cart-item-brand"><?php echo sanitize($product['marque']); ?></div>
                            <h3><?php echo sanitize($product['nom']); ?></h3>
                            <p class="cart-item-detail"><?php echo $product['matiere'] ?? 'Élégance intemporelle'; ?></p>
                        </div>
                        <div class="cart-item-price"><?php echo formatPrice($item['price']); ?></div>
                        <div class="cart-item-quantity">
                            <form method="POST" class="quantity-form">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?php echo $product['id_produit']; ?>">
                                <button type="button" class="quantity-btn minus" onclick="updateQuantity(this, -1)">−</button>
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                       min="1" max="<?php echo $product['stock']; ?>" 
                                       class="quantity-input" onchange="this.form.submit()">
                                <button type="button" class="quantity-btn plus" onclick="updateQuantity(this, 1)">+</button>
                            </form>
                        </div>
                        <div class="cart-item-total"><?php echo formatPrice($item['total']); ?></div>
                        <form method="POST" class="remove-form">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?php echo $product['id_produit']; ?>">
                            <button type="submit" class="btn-remove-item" title="Retirer"><i class="fas fa-times"></i></button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ═══ BLOC LIVRAISON — CARTES DORÉES ═══ -->
            <div class="livraison-bloc-panier">

                <h3 class="livraison-bloc-titre">
                    <i class="fas fa-truck"></i> Mode de livraison
                </h3>

                <?php if ($livreurs): ?>
                <form method="POST" action="panier.php" id="livreurForm">
                    <input type="hidden" name="action" value="choisir_livreur">
                    <input type="hidden" name="livreur_id" id="livreurIdInput" value="<?= $livreur_id_choisi ?>">

                    <div class="livraison-cards">
                    <?php foreach ($livreurs as $lv):
                        $selected     = ($livreur_id_choisi == $lv['id_livreur']);
                        $prix_reel    = (float)$lv['prix'];
                        $est_gratuit  = ($prix_reel == 0);
                        $prix_affiche = $est_gratuit
                            ? 'GRATUIT'
                            : number_format($prix_reel, 2, ',', ' ') . ' €';
                        $icone_fa     = $lv['icone'] ?? 'fa-truck';
                        $emoji        = $emoji_map[$icone_fa] ?? '📦';
                        $nomLow       = strtolower($lv['nom']);

                        // Badge
                        if ($est_gratuit)
                            $badge = '<span class="liv-badge liv-badge-vert">Gratuit</span>';
                        elseif (str_contains($nomLow,'express') || $lv['delai'] === '24h')
                            $badge = '<span class="liv-badge liv-badge-rouge">Rapide</span>';
                        elseif (str_contains($nomLow,'chronopost') || $lv['delai'] === '48h')
                            $badge = '<span class="liv-badge liv-badge-or">Express</span>';
                        elseif (str_contains($nomLow,'europe'))
                            $badge = '<span class="liv-badge liv-badge-bleu">Europe</span>';
                        elseif (str_contains($nomLow,'international'))
                            $badge = '<span class="liv-badge liv-badge-violet">International</span>';
                        else
                            $badge = '';

                        // Description
                        $desc = !empty($lv['description'])
                            ? htmlspecialchars($lv['description'])
                            : (!empty($lv['delai']) ? 'Livraison en ' . htmlspecialchars($lv['delai']) : '');
                        if (!empty($lv['zone_livraison']))
                            $desc .= ($desc ? ' — ' : '') . htmlspecialchars($lv['zone_livraison']);
                    ?>
                        <label class="liv-card <?= $selected ? 'active' : '' ?>"
                               onclick="selectLivraison(<?= $lv['id_livreur'] ?>, <?= $prix_reel ?>, '<?= addslashes($lv['nom']) ?>')">
                            <input type="radio" name="mode_livraison_display"
                                   value="<?= $lv['id_livreur'] ?>" <?= $selected ? 'checked' : '' ?>>
                            <div class="liv-card-emoji"><?= $emoji ?></div>
                            <div class="liv-card-info">
                                <div class="liv-card-nom">
                                    <?= htmlspecialchars($lv['nom']) ?> <?= $badge ?>
                                </div>
                                <?php if ($desc): ?>
                                    <div class="liv-card-desc"><?= $desc ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="liv-card-prix <?= $est_gratuit ? 'liv-prix-gratuit' : 'liv-prix-normal' ?>">
                                <?= $prix_affiche ?>
                            </div>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </form>

                <?php else: ?>
                    <p style="color:var(--muted);font-size:.88rem;text-align:center;padding:20px 0;">
                        <i class="fas fa-info-circle" style="color:var(--g1);margin-right:6px;"></i>
                        Aucun mode de livraison disponible.
                    </p>
                <?php endif; ?>

            </div>
            <!-- ═══ FIN BLOC LIVRAISON ═══ -->

        </div><!-- fin colonne gauche -->

        <!-- RÉCAPITULATIF -->
        <div class="cart-summary">
            <h2 class="summary-title">Récapitulatif</h2>

            <!-- Barre de progression -->
            <div class="ship-progress-wrap">
              <?php if (!$livreur_choisi): ?>
                <div class="ship-progress-text" id="ship-bar-text">
                  <i class="fas fa-truck"></i> Choisissez un mode de livraison
                </div>
                <div class="ship-bar"><div class="ship-bar-fill" id="ship-bar-fill" style="width:0%"></div></div>
              <?php elseif ($shipping == 0): ?>
                <div class="ship-progress-text done" id="ship-bar-text">
                  <i class="fas fa-check-circle"></i> Retrait gratuit en boutique !
                </div>
                <div class="ship-bar"><div class="ship-bar-fill" id="ship-bar-fill" style="width:100%;background:#2ecc71;"></div></div>
              <?php else: ?>
                <div class="ship-progress-text" id="ship-bar-text">
                  <i class="fas fa-truck"></i> Frais de livraison : <strong><?php echo formatPrice($shipping); ?></strong>
                </div>
                <div class="ship-bar"><div class="ship-bar-fill" id="ship-bar-fill" style="width:100%;background:var(--g1);"></div></div>
              <?php endif; ?>
            </div>

            <div class="summary-row">
                <span>Sous-total</span>
                <span><?php echo formatPrice($cart_subtotal); ?></span>
            </div>

            <?php if ($livreur_choisi): ?>
            <div class="summary-mode" id="recap-mode-liv">
                <div class="summary-mode-label">Mode de livraison</div>
                <div class="summary-mode-value">
                    <i class="fas <?= htmlspecialchars($livreur_choisi['icone'] ?? 'fa-truck') ?>"></i>
                    <span id="recap-mode-val"><?= htmlspecialchars($livreur_choisi['nom']) ?></span>
                </div>
            </div>
            <?php else: ?>
            <div class="summary-mode" id="recap-mode-liv" style="display:none;">
                <div class="summary-mode-label">Mode de livraison</div>
                <div class="summary-mode-value">
                    <i class="fas fa-truck"></i>
                    <span id="recap-mode-val"></span>
                </div>
            </div>
            <?php endif; ?>

            <div class="summary-row">
                <span>Frais de livraison</span>
                <span id="recap-frais-liv">
                    <?php if (!$livreur_choisi): ?>
                        <span style="color:rgba(255,255,255,.3);font-size:.8rem;">À choisir</span>
                    <?php elseif ($shipping == 0): ?>
                        <span style="color:var(--g2);">Gratuit</span>
                    <?php else: ?>
                        <?php echo formatPrice($shipping); ?>
                    <?php endif; ?>
                </span>
            </div>

            <div class="summary-row summary-total">
                <span>Total</span>
                <span id="recap-total"><?php echo formatPrice($cart_total); ?></span>
            </div>

            <div class="summary-note">
                <p><i class="fas fa-shipping-fast"></i> Livraison en 24-48h</p>
                <p><i class="fas fa-undo"></i> Retour gratuit sous 30 jours</p>
                <p><i class="fas fa-lock"></i> Paiement 100% sécurisé</p>
                <p><i class="fas fa-shield-alt"></i> Certificat d'authenticité</p>
            </div>

            <?php if ($livreur_choisi): ?>
                <a href="commande.php?livreur=<?= $livreur_choisi['id_livreur'] ?>" class="btn-checkout" id="btn-checkout">
                    <i class="fas fa-lock"></i>
                    <span>Procéder au paiement</span>
                </a>
            <?php else: ?>
                <a href="#" class="btn-checkout disabled" id="btn-checkout">
                    <i class="fas fa-lock"></i>
                    <span>Procéder au paiement</span>
                </a>
                <div class="checkout-warning" id="checkout-warning">
                    <i class="fas fa-exclamation-circle"></i> Veuillez choisir un mode de livraison
                </div>
            <?php endif; ?>

            <div class="continue-shopping">
                <a href="catalogue.php"><i class="fas fa-arrow-left"></i> Continuer mes achats</a>
            </div>
        </div>

    </div>

    <!-- RECOMMENDED PRODUCTS -->
    <?php
    try {
        $recommendedQuery    = "SELECT * FROM produits WHERE bestseller = 1 ORDER BY RAND() LIMIT 4";
        $recommendedStmt     = $pdo->query($recommendedQuery);
        $recommendedProducts = $recommendedStmt->fetchAll();
        if ($recommendedProducts): ?>
            <div class="recommended-products">
                <h2>Bijoux souvent achetés ensemble</h2>
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
                                    $price    = $product['prix'];
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
                                <form method="POST" action="ajouter_au_panier.php">
                                    <input type="hidden" name="produit_id" value="<?php echo $product['id_produit']; ?>">
                                    <input type="hidden" name="quantite" value="1">
                                    <input type="hidden" name="redirect_url" value="panier.php">
                                    <button type="submit" name="ajouter_panier" class="btn-cart">
                                        <i class="fas fa-plus"></i> Ajouter
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif;
    } catch (PDOException $e) { /* silencieux */ }
    ?>

<?php endif; ?>

<script>
// ── CURSEUR ──
const cur  = document.getElementById('cursor');
const ring = document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove', e => {
    mx = e.clientX; my = e.clientY;
    cur.style.left = mx + 'px'; cur.style.top = my + 'px';
});
(function animRing(){
    rx += (mx - rx) * .12; ry += (my - ry) * .12;
    ring.style.left = rx + 'px'; ring.style.top = ry + 'px';
    requestAnimationFrame(animRing);
})();
document.querySelectorAll('a,button,.product-card,.cart-item,.liv-card').forEach(el => {
    el.addEventListener('mouseenter', () => document.body.classList.add('hovering'));
    el.addEventListener('mouseleave', () => document.body.classList.remove('hovering'));
});

// ── SÉLECTION MODE DE LIVRAISON (mise à jour instantanée) ──
const sousTotal = <?= $cart_subtotal ?>;

function selectLivraison(id, prixBrut, nomMode) {
    // Visuel
    document.querySelectorAll('.liv-card').forEach(c => c.classList.remove('active'));
    event.currentTarget.classList.add('active');
    document.getElementById('livreurIdInput').value = id;

    // Calcul
    const prixLiv = prixBrut;
    const total   = sousTotal + prixLiv;

    // Frais de livraison dans le récap
    const elFrais = document.getElementById('recap-frais-liv');
    if (elFrais) {
        elFrais.innerHTML = prixLiv === 0
            ? '<span style="color:var(--g2);">Gratuit</span>'
            : '<span style="color:rgba(255,255,255,.9);">' + prixLiv.toFixed(2).replace('.', ',') + ' €</span>';
    }

    // Mode choisi dans le récap
    const elMode = document.getElementById('recap-mode-liv');
    if (elMode) elMode.style.display = 'block';
    const elModeVal = document.getElementById('recap-mode-val');
    if (elModeVal) elModeVal.textContent = nomMode;

    // Total
    const elTotal = document.getElementById('recap-total');
    if (elTotal) elTotal.textContent = total.toFixed(2).replace('.', ',') + ' €';

    // Barre progression
    const elBarText = document.getElementById('ship-bar-text');
    const elBarFill = document.getElementById('ship-bar-fill');
    if (prixLiv === 0) {
        if (elBarText) { elBarText.innerHTML = '<i class="fas fa-check-circle"></i> Retrait gratuit en boutique !'; elBarText.className = 'ship-progress-text done'; }
        if (elBarFill) { elBarFill.style.width = '100%'; elBarFill.style.background = '#2ecc71'; }
    } else {
        if (elBarText) { elBarText.innerHTML = '<i class="fas fa-truck"></i> Frais de livraison : <strong>' + prixLiv.toFixed(2).replace('.', ',') + ' €</strong>'; elBarText.className = 'ship-progress-text'; }
        if (elBarFill) { elBarFill.style.width = '100%'; elBarFill.style.background = 'var(--g1)'; }
    }

    // Activer le bouton paiement
    const btnCheckout = document.getElementById('btn-checkout');
    if (btnCheckout) { btnCheckout.href = 'commande.php?livreur=' + id; btnCheckout.classList.remove('disabled'); }
    const warning = document.getElementById('checkout-warning');
    if (warning) warning.style.display = 'none';

    // Sauvegarder en session
    fetch('set_livreur.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'livreur_id=' + id
    }).catch(() => {});
}

// ── QUANTITY UPDATE ──
function updateQuantity(button, change) {
    const form  = button.closest('.quantity-form');
    const input = form.querySelector('.quantity-input');
    let newValue = parseInt(input.value) + change;
    if (newValue >= 1 && newValue <= parseInt(input.max)) {
        input.value = newValue;
        form.submit();
    }
}
</script>

<?php include '../includes/footer.php'; ?>