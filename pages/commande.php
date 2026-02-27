<?php
// ══════════════════════════════════════════
//  LUMOURA — Page de commande / Checkout
//  pages/commande.php
// ══════════════════════════════════════════
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// ─── Helper affichage mode de livraison ───
// Basé sur le NOM du mode (Standard, Express, etc.)
// et non plus sur un prénom/nom de livreur-personne
function getLivreurDisplay(array $lv): array {
    $nom = strtolower($lv['nom'] ?? '');

    if (str_contains($nom, 'international') || str_contains($nom, 'monde'))
        return ['icon'=>'fa-plane',        'badge'=>'International', 'color'=>'#8E44AD', 'label'=>htmlspecialchars($lv['nom'])];
    if (str_contains($nom, 'europe'))
        return ['icon'=>'fa-plane',        'badge'=>'Europe',        'color'=>'#2980B9', 'label'=>htmlspecialchars($lv['nom'])];
    if (str_contains($nom, 'express') || str_contains($nom, 'chronopost'))
        return ['icon'=>'fa-bolt',         'badge'=>'Express',       'color'=>'#E67E22', 'label'=>htmlspecialchars($lv['nom'])];
    if (str_contains($nom, 'relais') || str_contains($nom, 'point'))
        return ['icon'=>'fa-box',          'badge'=>'Point Relais',  'color'=>'#27AE60', 'label'=>htmlspecialchars($lv['nom'])];
    if (str_contains($nom, 'magasin') || str_contains($nom, 'retrait') || str_contains($nom, 'collect'))
        return ['icon'=>'fa-store',        'badge'=>'Click & Collect','color'=>'#2a9d8f','label'=>htmlspecialchars($lv['nom'])];
    // Par défaut : Standard
    return     ['icon'=>'fa-truck',        'badge'=>'Standard',      'color'=>'#D4A843', 'label'=>htmlspecialchars($lv['nom'])];
}

// ─── Détecte si le mode est un retrait en magasin ───
function isClickCollect(array $lv): bool {
    $nom = strtolower($lv['nom'] ?? '');
    return str_contains($nom, 'magasin')
        || str_contains($nom, 'retrait')
        || str_contains($nom, 'collect')
        || floatval($lv['prix']) == 0 && str_contains($nom, 'collect');
}

if (!isLoggedIn()) {
    header('Location: connexion.php?redirect=commande');
    exit();
}

// Panier vide → retour panier
if (empty($_SESSION['cart'])) {
    header('Location: panier.php');
    exit();
}

// ── Charger les produits du panier ──
$cart_ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
try {
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id_produit IN ($placeholders)");
    $stmt->execute($cart_ids);
    $cart_products = [];
    foreach ($stmt->fetchAll() as $p) {
        $cart_products[$p['id_produit']] = $p;
    }
} catch(Exception $e) { $cart_products = []; }

// ── Calculs ──
$sous_total = 0;
foreach ($_SESSION['cart'] as $id => $qty) {
    if (!isset($cart_products[$id])) continue;
    $p = $cart_products[$id];
    $prix = isset($p['promotion_pourcentage']) && $p['promotion_pourcentage'] > 0
        ? calculateDiscount($p['prix'], $p['promotion_pourcentage'])
        : $p['prix'];
    $sous_total += $prix * $qty;
}

// ── Récupérer le mode de livraison choisi ──
$livreur_id = intval($_GET['livreur'] ?? $_SESSION['livreur_id'] ?? 0);
$livreur_choisi = null;
if ($livreur_id) {
    try {
        $stLiv = $pdo->prepare("SELECT * FROM livreurs WHERE id_livreur = ? AND statut = 'actif'");
        $stLiv->execute([$livreur_id]);
        $livreur_choisi = $stLiv->fetch();
    } catch(Exception $e) {}
}

$livraison_offerte = ($sous_total >= 150);
$livraison = $livreur_choisi
    ? ($livraison_offerte ? 0 : floatval($livreur_choisi['prix']))
    : 9.90;
$total = $sous_total + $livraison;

$d_liv          = $livreur_choisi ? getLivreurDisplay($livreur_choisi) : null;
$is_click_collect = $livreur_choisi && isClickCollect($livreur_choisi);

// Adresse magasin (Click & Collect)
$ADRESSE_MAGASIN = '123 Avenue des Champs-Élysées, 75008 Paris';

// ── TRAITEMENT COMMANDE ──
$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['passer_commande'])) {
    $prenom  = trim($_POST['prenom']  ?? '');
    $nom     = trim($_POST['nom']     ?? '');
    $email   = trim($_POST['email']   ?? '');
    $tel     = trim($_POST['tel']     ?? '');
    $note    = trim($_POST['note']    ?? '');

    // Adresse : fixe si Click & Collect, saisie sinon
    if ($is_click_collect) {
        $adresse = $ADRESSE_MAGASIN;
        $ville   = 'Paris';
        $cp      = '75008';
        $pays    = 'France';
    } else {
        $adresse = trim($_POST['adresse']      ?? '');
        $ville   = trim($_POST['ville']        ?? '');
        $cp      = trim($_POST['code_postal']  ?? '');
        $pays    = trim($_POST['pays']         ?? 'France');
    }

    if (empty($prenom))  $errors[] = 'Prénom requis';
    if (empty($nom))     $errors[] = 'Nom requis';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide';
    if (!$is_click_collect) {
        if (empty($adresse)) $errors[] = 'Adresse requise';
        if (empty($ville))   $errors[] = 'Ville requise';
        if (empty($cp))      $errors[] = 'Code postal requis';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $numero       = 'CMD-' . strtoupper(uniqid());
            $modePaiement = $_POST['paiement'] ?? 'carte';

            $stmtCmd = $pdo->prepare("
                INSERT INTO commandes
                    (numero_commande, id_utilisateur, statut, montant, frais_livraison, mode_paiement, notes)
                VALUES (?, ?, 'en_attente', ?, ?, ?, ?)
            ");
            $stmtCmd->execute([$numero, $_SESSION['user_id'], $total, $livraison, $modePaiement, $note]);
            $commande_id = $pdo->lastInsertId();

            if (!$commande_id) throw new Exception("Impossible de créer la commande.");

            $stmtDetail = $pdo->prepare("
                INSERT INTO details_commande (id_commande, id_produit, quantite, prix_unitaire)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($_SESSION['cart'] as $id => $qty) {
                if (!isset($cart_products[$id])) continue;
                $p  = $cart_products[$id];
                $pu = isset($p['promotion_pourcentage']) && $p['promotion_pourcentage'] > 0
                    ? calculateDiscount($p['prix'], $p['promotion_pourcentage'])
                    : $p['prix'];
                $stmtDetail->execute([$commande_id, $id, $qty, $pu]);
                $pdo->prepare("UPDATE produits SET stock = stock - ? WHERE id_produit = ? AND stock >= ?")
                    ->execute([$qty, $id, $qty]);
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            unset($_SESSION['livreur_id']);
            $_SESSION['commande_message'] = 'Votre commande #'.$commande_id.' a été enregistrée avec succès !';
            header('Location: commande_confirmation.php');
            exit();

        } catch(Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Erreur lors de la commande : '.$e->getMessage();
        }
    }
}

// ── Charger les données utilisateur ──
try {
    $userStmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();
} catch(Exception $e) { $user = []; }

// ── Adresse principale ──
$adresse_principale = null;
try {
    $stmtAddr = $pdo->prepare("
        SELECT * FROM adresses
        WHERE id_utilisateur = ?
        ORDER BY est_principale DESC, id_adresse DESC
        LIMIT 1
    ");
    $stmtAddr->execute([$_SESSION['user_id']]);
    $adresse_principale = $stmtAddr->fetch(PDO::FETCH_ASSOC);
} catch(Exception $e) { $adresse_principale = null; }

$pageTitle = "Finaliser ma commande — Lumoura";
include '../includes/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Cinzel:wght@400;600;700&family=Didact+Gothic&display=swap" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<style>
:root{--g1:#D4A843;--g2:#F5D78E;--g3:#B8882C;--ink:#0D0A06;--ink2:#1E1710;--smoke:#F8F5EF;--stone:#E8E0D0;--muted:#8A7D6A;--red:#C0392B;--vert-collect:#2a9d8f;}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Didact Gothic',sans-serif;background:var(--smoke);color:var(--ink);overflow-x:hidden;cursor:none;}

#cursor{position:fixed;width:10px;height:10px;background:var(--g1);border-radius:50%;pointer-events:none;z-index:99999;transform:translate(-50%,-50%);transition:width .25s,height .25s;}
#cursor-ring{position:fixed;width:36px;height:36px;border:1px solid var(--g1);border-radius:50%;pointer-events:none;z-index:99998;transform:translate(-50%,-50%);transition:width .3s,height .3s,opacity .3s;opacity:.6;}
body.hovering #cursor{width:20px;height:20px;background:var(--g2);}
body.hovering #cursor-ring{width:54px;height:54px;opacity:.4;}

.breadcrumb{background:var(--ink);padding:13px 60px;display:flex;align-items:center;gap:10px;font-family:'Cinzel',serif;font-size:.56rem;letter-spacing:2.5px;text-transform:uppercase;}
.breadcrumb a{color:rgba(255,255,255,.35);text-decoration:none;transition:color .2s;}
.breadcrumb a:hover{color:var(--g1);}
.breadcrumb-sep{color:rgba(255,255,255,.15);}
.breadcrumb-cur{color:var(--g1);}

.steps-bar{background:#fff;border-bottom:1px solid var(--stone);padding:20px 60px;display:flex;align-items:center;justify-content:center;gap:0;}
.step{display:flex;align-items:center;gap:10px;font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2px;text-transform:uppercase;color:rgba(0,0,0,.25);}
.step.active{color:var(--g3);}
.step.done{color:var(--g1);}
.step-num{width:28px;height:28px;border-radius:50%;border:1px solid currentColor;display:flex;align-items:center;justify-content:center;font-size:.65rem;flex-shrink:0;}
.step.active .step-num,.step.done .step-num{background:var(--g1);border-color:var(--g1);color:var(--ink);}
.step-line{width:60px;height:1px;background:var(--stone);margin:0 12px;}

.checkout-wrap{max-width:1200px;margin:0 auto;padding:50px 60px 80px;display:grid;grid-template-columns:1fr 400px;gap:40px;align-items:start;}

.panel{background:#fff;margin-bottom:2px;}
.panel-head{padding:20px 28px;border-bottom:1px solid var(--stone);display:flex;align-items:center;gap:14px;}
.panel-head i{color:var(--g1);font-size:1rem;}
.panel-head h2{font-family:'EB Garamond',serif;font-size:1.3rem;font-weight:400;color:var(--ink);}
.panel-body{padding:28px;}

/* ── Bannière adresse pré-remplie ── */
.addr-banner{background:rgba(212,168,67,.08);border-left:3px solid var(--g1);padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px;font-size:.82rem;color:var(--g3);}
.addr-banner i{font-size:1rem;color:var(--g1);}
.addr-banner a{color:var(--g1);text-decoration:underline;font-family:'Cinzel',serif;font-size:.6rem;letter-spacing:1.5px;margin-left:auto;}

/* ── Bannière Click & Collect ── */
.collect-banner {
    background: rgba(42,157,143,.08);
    border: 2px solid rgba(42,157,143,.35);
    border-radius: 10px;
    padding: 20px 22px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 4px;
}
.collect-banner-icon {
    font-size: 2rem;
    flex-shrink: 0;
    margin-top: 2px;
}
.collect-banner-title {
    font-family: 'Cinzel', serif;
    font-size: .68rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--vert-collect);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.collect-banner-title .badge-collect {
    background: var(--vert-collect);
    color: #fff;
    font-size: .5rem;
    padding: 2px 8px;
    border-radius: 10px;
    letter-spacing: 1.5px;
}
.collect-banner-addr {
    font-size: .88rem;
    color: var(--ink);
    font-weight: 600;
    margin-bottom: 4px;
}
.collect-banner-note {
    font-size: .78rem;
    color: var(--muted);
}

.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.form-row.single{grid-template-columns:1fr;}
.form-row.triple{grid-template-columns:2fr 1fr 1fr;}
.field{display:flex;flex-direction:column;gap:6px;}
.field label{font-family:'Cinzel',serif;font-size:.55rem;letter-spacing:2.5px;text-transform:uppercase;color:var(--muted);}
.field input,.field select,.field textarea{border:1px solid var(--stone);background:var(--smoke);padding:12px 16px;font-family:'Didact Gothic',sans-serif;font-size:.85rem;color:var(--ink);transition:border-color .25s,background .25s;width:100%;}
.field input:focus,.field select:focus,.field textarea:focus{outline:none;border-color:var(--g1);background:#fff;}
.field input::placeholder{color:rgba(0,0,0,.2);}
.field input:disabled,.field select:disabled{opacity:.5;cursor:not-allowed;background:#f0f0f0;}

.pay-options{display:flex;flex-direction:column;gap:2px;}
.pay-opt{display:flex;align-items:center;gap:16px;padding:16px 20px;background:var(--smoke);border:2px solid transparent;cursor:pointer;transition:all .25s;position:relative;}
.pay-opt:hover{background:#fff;}
.pay-opt.selected{background:#fff;border-color:var(--g1);}
.pay-opt input[type=radio]{display:none;}
.pay-radio{width:18px;height:18px;border-radius:50%;border:1px solid var(--stone);flex-shrink:0;display:flex;align-items:center;justify-content:center;transition:border-color .25s;}
.pay-opt.selected .pay-radio{border-color:var(--g1);}
.pay-opt.selected .pay-radio::after{content:'';width:8px;height:8px;border-radius:50%;background:var(--g1);}
.pay-icon{font-size:1.4rem;color:var(--muted);}
.pay-opt.selected .pay-icon{color:var(--g1);}
.pay-info h4{font-family:'EB Garamond',serif;font-size:1rem;font-weight:400;color:var(--ink);margin-bottom:2px;}
.pay-info p{font-size:.72rem;color:var(--muted);}
.pay-badge{margin-left:auto;font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:1.5px;text-transform:uppercase;background:var(--g1);color:var(--ink);padding:3px 9px;}
.card-fields{padding:20px;background:var(--smoke);border-top:1px solid var(--stone);display:none;}
.card-fields.visible{display:block;}
.card-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.card-input-wrap{position:relative;}
.card-input-wrap i{position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.9rem;}
.card-input-wrap input{padding-right:40px;}

/* ── RÉCAP ── */
.recap{background:var(--ink);color:rgba(255,255,255,.85);position:sticky;top:20px;}
.recap-head{padding:22px 26px;border-bottom:1px solid rgba(255,255,255,.06);}
.recap-head h3{font-family:'EB Garamond',serif;font-size:1.3rem;color:#fff;font-weight:400;}
.recap-items{padding:16px 26px;display:flex;flex-direction:column;gap:0;}
.recap-item{display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.recap-item:last-child{border-bottom:none;}
.recap-thumb{width:50px;height:50px;object-fit:cover;border:1px solid rgba(255,255,255,.08);flex-shrink:0;}
.recap-item-info{flex:1;min-width:0;}
.recap-item-name{font-family:'EB Garamond',serif;font-size:.95rem;color:rgba(255,255,255,.85);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.recap-item-brand{font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:2px;text-transform:uppercase;color:var(--g1);margin-bottom:2px;}
.recap-item-qty{font-size:.72rem;color:rgba(255,255,255,.3);}
.recap-item-price{font-family:'EB Garamond',serif;font-size:1rem;color:var(--g2);flex-shrink:0;}
.recap-totals{padding:16px 26px;border-top:1px solid rgba(255,255,255,.06);}
.recap-line{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-size:.82rem;}
.recap-line span:first-child{color:rgba(255,255,255,.4);}
.recap-line span:last-child{color:rgba(255,255,255,.75);}
.recap-line.free span:last-child{color:#2ecc71;font-family:'Cinzel',serif;font-size:.65rem;letter-spacing:1px;}
.recap-total-line{display:flex;justify-content:space-between;align-items:baseline;padding-top:14px;border-top:1px solid rgba(255,255,255,.08);margin-top:4px;}
.recap-total-line span:first-child{font-family:'Cinzel',serif;font-size:.6rem;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,.4);}
.recap-total-line .total-price{font-family:'EB Garamond',serif;font-size:1.8rem;color:var(--g1);}
.free-ship-bar{background:var(--ink);padding:12px 26px;display:flex;align-items:center;gap:12px;font-size:.75rem;color:rgba(255,255,255,.45);}
.free-ship-bar.done{color:#2ecc71;}
.free-ship-bar.done i{color:#2ecc71;}
.ship-progress{flex:1;height:3px;background:rgba(255,255,255,.08);border-radius:2px;overflow:hidden;}
.ship-progress-fill{height:100%;background:var(--g1);transition:width .4s;}
.ship-progress-fill.done{background:#2ecc71;}
.ship-badge{display:inline-flex;align-items:center;gap:4px;padding:1px 7px;color:#fff;font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:1.5px;text-transform:uppercase;}
.recap-livreur{margin:0 26px 10px;padding:10px 14px;background:rgba(212,168,67,.08);border-left:3px solid var(--g1);}
.recap-livreur-label{font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:2px;text-transform:uppercase;color:var(--g1);margin-bottom:4px;display:flex;align-items:center;gap:8px;}
.recap-livreur-info{font-size:.78rem;color:rgba(255,255,255,.5);display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.btn-commander{width:100%;background:var(--g1);color:var(--ink);border:none;padding:18px;font-family:'Cinzel',serif;font-size:.7rem;letter-spacing:3px;text-transform:uppercase;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:12px;margin-top:2px;transition:background .3s;}
.btn-commander:hover{background:var(--g2);}
.secure-badges{padding:16px 26px;border-top:1px solid rgba(255,255,255,.04);display:flex;gap:16px;justify-content:center;flex-wrap:wrap;}
.secure-badge{display:flex;align-items:center;gap:5px;font-size:.68rem;color:rgba(255,255,255,.25);}
.secure-badge i{color:rgba(255,255,255,.2);font-size:.8rem;}
.errors-block{background:rgba(192,57,43,.08);border-left:3px solid var(--red);padding:14px 20px;margin-bottom:24px;list-style:none;}
.errors-block li{color:var(--red);font-size:.82rem;margin-bottom:4px;display:flex;align-items:center;gap:8px;}
.errors-block li:last-child{margin-bottom:0;}

/* jQuery UI Autocomplete */
.addr-wrap{position:relative;}
.spinner-adresse{display:none;position:absolute;right:14px;top:50%;transform:translateY(-50%);width:16px;height:16px;border:2px solid var(--stone);border-top-color:var(--g1);border-radius:50%;animation:addrSpin .7s linear infinite;pointer-events:none;}
@keyframes addrSpin{to{transform:translateY(-50%) rotate(360deg);}}
.ui-autocomplete{max-height:260px;overflow-y:auto;overflow-x:hidden;background:#fff!important;border:1px solid var(--g1)!important;border-top:none!important;border-radius:0!important;box-shadow:0 8px 32px rgba(0,0,0,.12)!important;z-index:9999!important;padding:0!important;font-family:'Didact Gothic',sans-serif!important;}
.ui-menu-item .ui-menu-item-wrapper{padding:10px 16px!important;border-bottom:1px solid var(--stone)!important;border-radius:0!important;font-size:.83rem!important;color:var(--ink)!important;cursor:pointer;transition:background .15s!important;display:flex!important;flex-direction:column!important;gap:2px!important;white-space:normal!important;line-height:1.4!important;}
.ui-menu-item .ui-menu-item-wrapper.ui-state-active,.ui-menu-item .ui-menu-item-wrapper.ui-state-focus{background:rgba(212,168,67,.10)!important;color:var(--ink)!important;margin:0!important;}
.addr-sub{font-size:.72rem;color:var(--muted);}
.addr-cp-badge{display:inline-block;font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:1.5px;color:var(--g3);background:rgba(212,168,67,.10);padding:1px 6px;margin-left:6px;}

@media(max-width:1024px){.checkout-wrap{grid-template-columns:1fr;padding:30px;}.recap{position:static;}.steps-bar,.breadcrumb{padding-left:30px;padding-right:30px;}}
@media(max-width:640px){.form-row{grid-template-columns:1fr;}.form-row.triple{grid-template-columns:1fr;}.steps-bar{padding:14px 20px;}.step span{display:none;}}
</style>

<div id="cursor"></div>
<div id="cursor-ring"></div>

<nav class="breadcrumb">
  <a href="../index.php">Accueil</a>
  <span class="breadcrumb-sep">›</span>
  <a href="panier.php">Mon panier</a>
  <span class="breadcrumb-sep">›</span>
  <span class="breadcrumb-cur">Finaliser la commande</span>
</nav>

<div class="steps-bar">
  <div class="step done"><div class="step-num"><i class="fas fa-check"></i></div><span>Panier</span></div>
  <div class="step-line"></div>
  <div class="step active"><div class="step-num">2</div><span>Livraison & Paiement</span></div>
  <div class="step-line"></div>
  <div class="step"><div class="step-num">3</div><span>Confirmation</span></div>
</div>

<form method="POST" action="commande.php<?= $livreur_id ? '?livreur='.$livreur_id : '' ?>" id="checkoutForm">
<div class="checkout-wrap">

  <div>
    <?php if(!empty($errors)): ?>
      <ul class="errors-block">
        <?php foreach($errors as $e): ?>
          <li><i class="fas fa-exclamation-circle"></i> <?=htmlspecialchars($e)?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <!-- ── Infos personnelles ── -->
    <div class="panel">
      <div class="panel-head"><i class="fas fa-user"></i><h2>Informations personnelles</h2></div>
      <div class="panel-body">
        <div class="form-row">
          <div class="field">
            <label>Prénom *</label>
            <input type="text" name="prenom" value="<?=htmlspecialchars($_POST['prenom'] ?? $user['prenom'] ?? '')?>" placeholder="Marie" required>
          </div>
          <div class="field">
            <label>Nom *</label>
            <input type="text" name="nom" value="<?=htmlspecialchars($_POST['nom'] ?? $user['nom'] ?? '')?>" placeholder="Dupont" required>
          </div>
        </div>
        <div class="form-row">
          <div class="field">
            <label>Email *</label>
            <input type="email" name="email" value="<?=htmlspecialchars($_POST['email'] ?? $user['email'] ?? '')?>" placeholder="marie@example.com" required>
          </div>
          <div class="field">
            <label>Téléphone</label>
            <input type="tel" name="tel" value="<?=htmlspecialchars($_POST['tel'] ?? $user['telephone'] ?? $adresse_principale['telephone'] ?? '')?>" placeholder="06 12 34 56 78">
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════
         ADRESSE DE LIVRAISON — conditionnel
         · Click & Collect → bannière magasin
         · Domicile        → formulaire normal
    ══════════════════════════════════════════ -->
    <div class="panel">
      <div class="panel-head">
        <i class="fas <?= $is_click_collect ? 'fa-store' : 'fa-map-marker-alt' ?>"></i>
        <h2><?= $is_click_collect ? 'Retrait en boutique' : 'Adresse de livraison' ?></h2>
      </div>
      <div class="panel-body">

        <?php if ($is_click_collect): ?>
          <!-- ── MODE RETRAIT MAGASIN ── -->
          <div class="collect-banner">
            <div class="collect-banner-icon">🏪</div>
            <div>
              <div class="collect-banner-title">
                <i class="fas fa-store"></i>
                Click &amp; Collect
                <span class="badge-collect">Gratuit</span>
              </div>
              <div class="collect-banner-addr">
                <?= htmlspecialchars($ADRESSE_MAGASIN) ?>
              </div>
              <div class="collect-banner-note">
                Votre commande sera disponible sous 24h après confirmation.<br>
                Munissez-vous de votre numéro de commande et d'une pièce d'identité.
              </div>
            </div>
          </div>
          <!-- Champs cachés pour ne pas casser la validation -->
          <input type="hidden" name="adresse"     value="<?= htmlspecialchars($ADRESSE_MAGASIN) ?>">
          <input type="hidden" name="ville"       value="Paris">
          <input type="hidden" name="code_postal" value="75008">
          <input type="hidden" name="pays"        value="France">

          <!-- Note optionnelle -->
          <div class="form-row single" style="margin-top:18px;">
            <div class="field">
              <label>Note (optionnel)</label>
              <input type="text" name="note" value="<?=htmlspecialchars($_POST['note'] ?? '')?>" placeholder="Instructions particulières...">
            </div>
          </div>

        <?php else: ?>
          <!-- ── MODE LIVRAISON À DOMICILE ── -->
          <?php if ($adresse_principale && empty($_POST)): ?>
            <div class="addr-banner">
              <i class="fas fa-check-circle"></i>
              Votre adresse principale a été pré-remplie automatiquement.
              <a href="ajouter_adresse.php">Gérer mes adresses →</a>
            </div>
          <?php endif; ?>

          <div class="form-row single">
            <div class="field">
              <label>Adresse *</label>
              <div class="addr-wrap">
                <input
                  type="text" name="adresse" id="adresse-input"
                  value="<?=htmlspecialchars($_POST['adresse'] ?? $adresse_principale['adresse'] ?? $user['adresse'] ?? '')?>"
                  placeholder="12 Rue de la Paix" required autocomplete="off">
                <span class="spinner-adresse"></span>
              </div>
            </div>
          </div>
          <div class="form-row triple">
            <div class="field">
              <label>Ville *</label>
              <input type="text" name="ville" id="ville-input" value="<?=htmlspecialchars($_POST['ville'] ?? $adresse_principale['ville'] ?? $user['ville'] ?? '')?>" placeholder="Paris" required>
            </div>
            <div class="field">
              <label>Code postal *</label>
              <input type="text" name="code_postal" id="cp-input" value="<?=htmlspecialchars($_POST['code_postal'] ?? $adresse_principale['code_postal'] ?? $user['code_postal'] ?? '')?>" placeholder="75001" required>
            </div>
            <div class="field">
              <label>Pays</label>
              <select name="pays">
                <?php $pays_sel = $_POST['pays'] ?? $adresse_principale['pays'] ?? 'France';
                foreach(['France','Belgique','Suisse','Luxembourg'] as $p): ?>
                <option value="<?=$p?>" <?=$pays_sel===$p?'selected':''?>><?=$p?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row single">
            <div class="field">
              <label>Note pour la livraison</label>
              <input type="text" name="note" value="<?=htmlspecialchars($_POST['note'] ?? '')?>" placeholder="Interphone, instructions particulières...">
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- ── Paiement ── -->
    <div class="panel">
      <div class="panel-head"><i class="fas fa-credit-card"></i><h2>Mode de paiement</h2></div>
      <div class="panel-body" style="padding:0;">
        <div class="pay-options">
          <label class="pay-opt selected" id="opt-carte" onclick="selectPay(this,'carte')">
            <input type="radio" name="paiement" value="carte" checked>
            <div class="pay-radio"></div>
            <i class="pay-icon fas fa-credit-card"></i>
            <div class="pay-info"><h4>Carte bancaire</h4><p>Visa, Mastercard, American Express</p></div>
            <span class="pay-badge">Recommandé</span>
          </label>
          <div class="card-fields visible" id="card-fields">
            <div class="form-row single" style="margin-bottom:12px;">
              <div class="field">
                <label>Numéro de carte</label>
                <div class="card-input-wrap">
                  <input type="text" placeholder="1234 5678 9012 3456" maxlength="19" id="cardNum">
                  <i class="far fa-credit-card"></i>
                </div>
              </div>
            </div>
            <div class="card-row">
              <div class="field"><label>Date d'expiration</label><input type="text" placeholder="MM/AA" maxlength="5" id="cardExp"></div>
              <div class="field"><label>CVV</label><input type="text" placeholder="123" maxlength="3"></div>
            </div>
            <p style="font-size:.72rem;color:var(--muted);margin-top:12px;"><i class="fas fa-lock" style="color:var(--g1);margin-right:4px;"></i> Paiement 100% sécurisé — Données cryptées SSL</p>
          </div>
          <label class="pay-opt" id="opt-paypal" onclick="selectPay(this,'paypal')">
            <input type="radio" name="paiement" value="paypal">
            <div class="pay-radio"></div>
            <i class="pay-icon fab fa-paypal"></i>
            <div class="pay-info"><h4>PayPal</h4><p>Payez en toute sécurité avec votre compte PayPal</p></div>
          </label>
          <label class="pay-opt" id="opt-virement" onclick="selectPay(this,'virement')">
            <input type="radio" name="paiement" value="virement">
            <div class="pay-radio"></div>
            <i class="pay-icon fas fa-university"></i>
            <div class="pay-info"><h4>Virement bancaire</h4><p>Délai de traitement 2-3 jours ouvrés</p></div>
          </label>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══ RÉCAP ═══ -->
  <div>
    <div class="recap">
      <div class="recap-head"><h3>Votre commande</h3></div>

      <?php if ($livreur_choisi && $d_liv): ?>
      <div class="free-ship-bar <?=$livraison==0?'done':''?>">
        <i class="fas <?=$d_liv['icon']?>" style="color:<?=$livraison==0?'#2ecc71':$d_liv['color']?>;"></i>
        <span style="display:flex;align-items:center;gap:6px;">
          <span class="ship-badge" style="background:<?=$d_liv['color']?>;"><?=htmlspecialchars($d_liv['badge'])?></span>
          <?=$livraison==0?'Livraison gratuite !':number_format($livraison,2,',',' ').'€'?>
        </span>
        <div class="ship-progress">
          <div class="ship-progress-fill <?=$livraison==0?'done':''?>" style="width:100%"></div>
        </div>
      </div>
      <?php else: ?>
      <div class="free-ship-bar">
        <i class="fas fa-shipping-fast"></i>
        <span>Frais de livraison : <?=number_format($livraison,2,',',' ')?>€</span>
        <div class="ship-progress">
          <div class="ship-progress-fill" style="width:<?=min(100, ($sous_total / 150) * 100)?>%"></div>
        </div>
      </div>
      <?php endif; ?>

      <div class="recap-items">
        <?php foreach($_SESSION['cart'] as $id => $qty):
          if(!isset($cart_products[$id])) continue;
          $p = $cart_products[$id];
          $pu = isset($p['promotion_pourcentage']) && $p['promotion_pourcentage'] > 0
            ? calculateDiscount($p['prix'], $p['promotion_pourcentage'])
            : $p['prix'];
        ?>
        <div class="recap-item">
          <img class="recap-thumb" src="<?=htmlspecialchars($p['image_url'] ?: 'https://via.placeholder.com/50x50/1E1710/D4A843?text=✦')?>" alt="">
          <div class="recap-item-info">
            <div class="recap-item-brand"><?=htmlspecialchars($p['marque']??'')?></div>
            <div class="recap-item-name"><?=htmlspecialchars($p['nom'])?></div>
            <div class="recap-item-qty">× <?=$qty?></div>
          </div>
          <div class="recap-item-price"><?=number_format($pu*$qty,2,',',' ')?>€</div>
        </div>
        <?php endforeach; ?>
      </div>

      <?php if ($livreur_choisi && $d_liv): ?>
      <div class="recap-livreur">
        <div class="recap-livreur-label">
          <i class="fas <?=$d_liv['icon']?>" style="color:<?=$d_liv['color']?>;"></i>
          <span class="ship-badge" style="background:<?=$d_liv['color']?>;"><?=htmlspecialchars($d_liv['badge'])?></span>
          Mode de livraison
        </div>
        <div class="recap-livreur-info">
          <span><?=$d_liv['label']?></span>
          <?php if(!empty($livreur_choisi['delai'])): ?>
            <span>· <?=htmlspecialchars($livreur_choisi['delai'])?></span>
          <?php endif; ?>
          <?php if($is_click_collect): ?>
            <span>· <?=htmlspecialchars($ADRESSE_MAGASIN)?></span>
          <?php elseif(!empty($livreur_choisi['zone_livraison'])): ?>
            <span>· <?=htmlspecialchars($livreur_choisi['zone_livraison'])?></span>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="recap-totals">
        <div class="recap-line">
          <span>Sous-total</span>
          <span><?=number_format($sous_total,2,',',' ')?>€</span>
        </div>
        <div class="recap-line <?=$livraison==0?'free':''?>">
          <span>Livraison</span>
          <span><?=$livraison==0?'GRATUITE':number_format($livraison,2,',',' ').'€'?></span>
        </div>
        <div class="recap-total-line">
          <span>Total TTC</span>
          <span class="total-price"><?=number_format($total,2,',',' ')?>€</span>
        </div>
      </div>

      <button type="submit" name="passer_commande" class="btn-commander">
        <i class="fas fa-lock"></i> Confirmer ma commande
      </button>

      <div class="secure-badges">
        <div class="secure-badge"><i class="fas fa-lock"></i> SSL sécurisé</div>
        <div class="secure-badge"><i class="fas fa-undo"></i> Retour 30j</div>
        <div class="secure-badge"><i class="fas fa-certificate"></i> Certifié</div>
      </div>
    </div>
  </div>

</div>
</form>

<script>
/* ── Curseur ── */
const cur=document.getElementById('cursor'),ring=document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';});
(function r(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(r);})();
document.querySelectorAll('a,button,label,input').forEach(el=>{
  el.addEventListener('mouseenter',()=>document.body.classList.add('hovering'));
  el.addEventListener('mouseleave',()=>document.body.classList.remove('hovering'));
});

/* ── Paiement ── */
function selectPay(el,mode){
  document.querySelectorAll('.pay-opt').forEach(o=>o.classList.remove('selected'));
  el.classList.add('selected');
  el.querySelector('input').checked=true;
  document.getElementById('card-fields').classList.toggle('visible',mode==='carte');
}

/* ── Formatage carte ── */
document.getElementById('cardNum')?.addEventListener('input',function(){
  let v=this.value.replace(/\D/g,'').substring(0,16);
  this.value=v.match(/.{1,4}/g)?.join(' ')||v;
});
document.getElementById('cardExp')?.addEventListener('input',function(){
  let v=this.value.replace(/\D/g,'').substring(0,4);
  if(v.length>=2) v=v.substring(0,2)+'/'+v.substring(2);
  this.value=v;
});

/* ── Autocomplétion adresse (seulement si pas Click & Collect) ── */
<?php if (!$is_click_collect): ?>
$(function(){
  var timer=null;
  $('#adresse-input').autocomplete({
    source:function(req,res){
      clearTimeout(timer);
      $('#adresse-input').siblings('.spinner-adresse').show();
      timer=setTimeout(function(){
        $.ajax({
          url:'https://api-adresse.data.gouv.fr/search/',
          data:{q:req.term,limit:8,countrycodes:'fr'},
          dataType:'json',
          success:function(data){
            $('#adresse-input').siblings('.spinner-adresse').hide();
            res($.map(data.features,function(item){
              return{label:item.properties.label,value:item.properties.name,cp:item.properties.postcode,ville:item.properties.city};
            }));
          },
          error:function(){$('#adresse-input').siblings('.spinner-adresse').hide();res([]);}
        });
      },300);
    },
    minLength:3,
    select:function(e,ui){
      $('#adresse-input').val(ui.item.value);
      $('#cp-input').val(ui.item.cp);
      $('#ville-input').val(ui.item.ville);
      flashField($('#cp-input')); flashField($('#ville-input'));
      return false;
    },
    focus:function(){return false;}
  });
  $('#adresse-input').autocomplete('instance')._renderItem=function(ul,item){
    var cp=item.cp?'<span class="addr-cp-badge">'+item.cp+'</span>':'';
    var city=item.ville?'<span class="addr-sub">'+item.ville+'</span>':'';
    return $('<li>').append('<div class="ui-menu-item-wrapper"><span>'+$('<div>').text(item.value).html()+cp+'</span>'+city+'</div>').appendTo(ul);
  };
  function flashField($f){
    $f.css({borderColor:'var(--g1)',background:'rgba(212,168,67,.08)'});
    setTimeout(function(){$f.css({borderColor:'',background:''}); },1600);
  }
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>