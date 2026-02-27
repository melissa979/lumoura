<?php
// ══════════════════════════════════════════
//  LUMOURA — Gestion des Modes de Livraison
//  admin/livreurs.php
// ══════════════════════════════════════════
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../pages/connexion.php'); exit();
}

$msg = ''; $msgType = '';

// ─── CRÉATION TABLE ───
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS livreurs (
        id_livreur INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(100) NOT NULL,
        description VARCHAR(200) DEFAULT NULL,
        icone VARCHAR(50) DEFAULT 'fa-truck',
        prix DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        delai VARCHAR(50) DEFAULT NULL,
        zone_livraison VARCHAR(150) DEFAULT NULL,
        statut ENUM('actif','inactif') DEFAULT 'actif',
        date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e) {}

// ─── INSERTION MODES PAR DÉFAUT ───
try {
    $count = $pdo->query("SELECT COUNT(*) FROM livreurs")->fetchColumn();
    if ($count == 0) {
        $defaults = [
            ['Standard',         'Livraison classique par voie postale',        'fa-box',         9.90,  '3-5 jours',  'France entière'],
            ['Express',          'Livraison rapide garantie le lendemain',       'fa-bolt',        14.90, '24h',        'France entière'],
            ['Chronopost',       'Livraison express avec suivi en temps réel',   'fa-shipping-fast', 12.90, '48h',      'France entière'],
            ['Colissimo',        'Livraison La Poste avec suivi',                'fa-envelope',    9.90,  '3-5 jours',  'France entière'],
            ['Retrait en magasin','Venez récupérer votre commande en boutique',  'fa-store',       0.00,  'Immédiat',   'Paris'],
        ];
        $stmt = $pdo->prepare("INSERT INTO livreurs (nom, description, icone, prix, delai, zone_livraison) VALUES (?,?,?,?,?,?)");
        foreach ($defaults as $d) $stmt->execute($d);
    }
} catch(Exception $e) {}

// ─── TRAITEMENT FORMULAIRE ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom            = trim($_POST['nom'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $icone          = trim($_POST['icone'] ?? 'fa-truck');
    $prix           = floatval($_POST['prix'] ?? 0);
    $delai          = trim($_POST['delai'] ?? '');
    $zone_livraison = trim($_POST['zone_livraison'] ?? '');
    $statut         = $_POST['statut'] ?? 'actif';

    if (empty($nom)) {
        $msg = 'Le nom du mode de livraison est obligatoire.'; $msgType = 'error';
    } else {
        try {
            if ($_POST['form_action'] === 'ajouter') {
                $pdo->prepare("INSERT INTO livreurs (nom, description, icone, prix, delai, zone_livraison, statut) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$nom, $description, $icone, $prix, $delai, $zone_livraison, $statut]);
                $msg = '✦ Mode "'.$nom.'" ajouté !'; $msgType = 'ok';
            } elseif ($_POST['form_action'] === 'modifier' && !empty($_POST['id_livreur'])) {
                $pdo->prepare("UPDATE livreurs SET nom=?, description=?, icone=?, prix=?, delai=?, zone_livraison=?, statut=? WHERE id_livreur=?")
                    ->execute([$nom, $description, $icone, $prix, $delai, $zone_livraison, $statut, intval($_POST['id_livreur'])]);
                $msg = '✦ Mode mis à jour !'; $msgType = 'ok';
            }
        } catch(Exception $e) {
            $msg = 'Erreur : '.$e->getMessage(); $msgType = 'error';
        }
    }
}

// ─── SUPPRESSION ───
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    try {
        $pdo->prepare("DELETE FROM livreurs WHERE id_livreur=?")->execute([intval($_GET['id'])]);
        $msg = 'Mode supprimé.'; $msgType = 'ok';
    } catch(Exception $e) {
        $msg = 'Erreur suppression.'; $msgType = 'error';
    }
}

// ─── TOGGLE STATUT ───
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    try {
        $pdo->prepare("UPDATE livreurs SET statut = IF(statut='actif','inactif','actif') WHERE id_livreur=?")->execute([intval($_GET['id'])]);
    } catch(Exception $e) {}
    header('Location: livreurs.php'); exit();
}

// ─── MODE ÉDITION ───
$action   = $_GET['action'] ?? 'liste';
$edit_id  = isset($_GET['id']) ? intval($_GET['id']) : null;
$edit_liv = null;
if ($action === 'modifier' && $edit_id) {
    try {
        $st = $pdo->prepare("SELECT * FROM livreurs WHERE id_livreur=?");
        $st->execute([$edit_id]);
        $edit_liv = $st->fetch();
        if (!$edit_liv) $action = 'liste';
    } catch(Exception $e) { $action = 'liste'; }
}

// ─── CHARGEMENT ───
$livreurs = [];
try {
    $livreurs = $pdo->query("SELECT * FROM livreurs ORDER BY prix ASC")->fetchAll();
} catch(Exception $e) {}

if (!in_array($action, ['liste','ajouter','modifier'])) $action = 'liste';

$total    = count($livreurs);
$actifs   = count(array_filter($livreurs, fn($l) => $l['statut'] === 'actif'));
$inactifs = $total - $actifs;

$icones_dispo = [
    'fa-truck'        => '🚚 Camion',
    'fa-bolt'         => '⚡ Express',
    'fa-shipping-fast'=> '🚀 Rapide',
    'fa-box'          => '📦 Colis',
    'fa-envelope'     => '✉️ Courrier',
    'fa-store'        => '🏪 Magasin',
    'fa-plane'        => '✈️ Avion',
    'fa-bicycle'      => '🚲 Vélo',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Modes de Livraison — Admin Lumoura</title>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Cinzel:wght@400;600;700&family=Didact+Gothic&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--g1:#D4A843;--g2:#F5D78E;--ink:#0D0A06;--ink2:#1A140E;--ink3:#241C12;--red:#C0392B;--green:#27AE60;--sidebar:240px;}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Didact Gothic',sans-serif;background:var(--ink);color:rgba(255,255,255,.85);display:flex;min-height:100vh;}

.sidebar{width:var(--sidebar);flex-shrink:0;background:var(--ink2);border-right:1px solid rgba(255,255,255,.04);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;overflow-y:auto;}
.sidebar-logo{padding:28px 24px 22px;border-bottom:1px solid rgba(255,255,255,.04);}
.sidebar-logo-name{font-family:'Cinzel',serif;font-size:1.1rem;letter-spacing:3px;text-transform:uppercase;color:#fff;}
.sidebar-logo-sub{font-size:.58rem;letter-spacing:4px;text-transform:uppercase;color:var(--g1);margin-top:4px;}
.sidebar-section{font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:4px;text-transform:uppercase;color:rgba(255,255,255,.2);padding:20px 24px 8px;}
.sidebar-nav{display:flex;flex-direction:column;gap:1px;padding:0 12px;}
.nav-link{display:flex;align-items:center;gap:12px;padding:11px 14px;color:rgba(255,255,255,.45);text-decoration:none;font-size:.8rem;border-radius:2px;transition:all .2s;position:relative;}
.nav-link i{width:18px;font-size:.9rem;flex-shrink:0;}
.nav-link:hover{color:rgba(255,255,255,.85);background:rgba(255,255,255,.04);}
.nav-link.active{color:var(--g1);background:rgba(212,168,67,.08);}
.nav-link.active::before{content:'';position:absolute;left:0;top:0;bottom:0;width:2px;background:var(--g1);}
.sidebar-bottom{margin-top:auto;padding:16px 12px;border-top:1px solid rgba(255,255,255,.04);}
.nav-link.danger:hover{color:var(--red);background:rgba(192,57,43,.08);}

.main{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;}
.topbar{background:var(--ink2);border-bottom:1px solid rgba(255,255,255,.04);padding:0 40px;height:62px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50;}
.topbar-title{font-family:'EB Garamond',serif;font-size:1.5rem;color:#fff;font-weight:400;flex:1;}
.topbar-admin{display:flex;align-items:center;gap:10px;font-size:.78rem;color:rgba(255,255,255,.4);}
.topbar-avatar{width:34px;height:34px;border-radius:50%;background:rgba(212,168,67,.15);border:1px solid rgba(212,168,67,.3);display:flex;align-items:center;justify-content:center;font-family:'Cinzel',serif;font-size:.75rem;color:var(--g1);}
.topbar-btn{display:flex;align-items:center;gap:7px;background:var(--g1);color:var(--ink);padding:9px 18px;border:none;cursor:pointer;font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2px;text-transform:uppercase;text-decoration:none;transition:background .25s;}
.topbar-btn:hover{background:var(--g2);}
.topbar-btn.ghost{background:transparent;border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.5);}
.topbar-btn.ghost:hover{background:rgba(255,255,255,.05);color:#fff;}
.page-content{padding:32px 40px;flex:1;}

.msg-bar{padding:14px 20px;margin-bottom:24px;font-size:.84rem;display:flex;align-items:center;gap:10px;}
.msg-bar.ok{background:rgba(39,174,96,.12);border-left:3px solid var(--green);color:#2ecc71;}
.msg-bar.error{background:rgba(192,57,43,.12);border-left:3px solid var(--red);color:#e74c3c;}

/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:2px;margin-bottom:24px;}
.stat-card{background:var(--ink2);padding:20px 24px;display:flex;align-items:center;gap:16px;}
.stat-icon{width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.stat-icon.gold{background:rgba(212,168,67,.1);color:var(--g1);}
.stat-icon.green{background:rgba(39,174,96,.1);color:var(--green);}
.stat-icon.red{background:rgba(192,57,43,.1);color:#e74c3c;}
.stat-num{font-family:'EB Garamond',serif;font-size:1.8rem;color:#fff;line-height:1;}
.stat-label{font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,.3);margin-top:4px;}

/* GRILLE MODES */
.modes-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:2px;background:rgba(255,255,255,.04);}

.mode-card{background:var(--ink2);padding:28px;position:relative;transition:background .2s;}
.mode-card:hover{background:var(--ink3);}

.mode-card-top{display:flex;align-items:center;gap:16px;margin-bottom:16px;}
.mode-icon{width:48px;height:48px;background:rgba(212,168,67,.1);display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:var(--g1);flex-shrink:0;}
.mode-icon.inactif{background:rgba(255,255,255,.04);color:rgba(255,255,255,.2);}

.mode-nom{font-family:'EB Garamond',serif;font-size:1.2rem;color:#fff;}
.mode-desc{font-size:.76rem;color:rgba(255,255,255,.35);line-height:1.5;margin-bottom:16px;min-height:32px;}

.mode-infos{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px;}
.mode-info-pill{display:inline-flex;align-items:center;gap:5px;font-size:.7rem;color:rgba(255,255,255,.4);background:rgba(255,255,255,.04);padding:4px 10px;}
.mode-info-pill i{color:var(--g1);font-size:.65rem;}

.mode-prix{font-family:'EB Garamond',serif;font-size:1.6rem;color:var(--g1);margin-bottom:16px;}
.mode-prix.gratuit{color:#2ecc71;}
.mode-prix span{font-size:.75rem;color:rgba(255,255,255,.3);font-family:'Didact Gothic',sans-serif;}

.mode-foot{display:flex;align-items:center;justify-content:space-between;}
.mode-actions{display:flex;gap:6px;}
.tbl-btn{width:32px;height:32px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.82rem;transition:all .2s;text-decoration:none;}
.tbl-btn.edit{background:rgba(212,168,67,.1);color:var(--g1);}
.tbl-btn.edit:hover{background:rgba(212,168,67,.25);}
.tbl-btn.del{background:rgba(192,57,43,.1);color:var(--red);}
.tbl-btn.del:hover{background:rgba(192,57,43,.25);}

.statut-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;text-decoration:none;transition:all .2s;}
.statut-pill.actif{background:rgba(39,174,96,.12);color:var(--green);border:1px solid rgba(39,174,96,.2);}
.statut-pill.actif:hover{background:rgba(39,174,96,.22);}
.statut-pill.inactif{background:rgba(192,57,43,.1);color:#e74c3c;border:1px solid rgba(192,57,43,.2);}
.statut-dot{width:6px;height:6px;border-radius:50%;display:inline-block;}
.statut-dot.actif{background:var(--green);}
.statut-dot.inactif{background:#e74c3c;}

.empty-state{text-align:center;padding:70px 20px;color:rgba(255,255,255,.2);font-family:'EB Garamond',serif;font-size:1.2rem;background:var(--ink2);}

/* FORMULAIRE */
.form-wrap{max-width:680px;}
.form-panel{background:var(--ink2);padding:32px;margin-bottom:20px;}
.form-panel-title{font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:4px;text-transform:uppercase;color:var(--g1);padding-bottom:14px;margin-bottom:22px;border-bottom:1px solid rgba(255,255,255,.05);display:flex;align-items:center;gap:10px;}
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.field{display:flex;flex-direction:column;gap:7px;}
.field label{font-family:'Cinzel',serif;font-size:.56rem;letter-spacing:2.5px;text-transform:uppercase;color:rgba(255,255,255,.4);}
.field input,.field select,.field textarea{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);color:rgba(255,255,255,.85);padding:11px 14px;font-family:'Didact Gothic',sans-serif;font-size:.85rem;transition:border-color .25s;width:100%;}
.field input:focus,.field select:focus,.field textarea:focus{outline:none;border-color:rgba(212,168,67,.45);}
.field input::placeholder,.field textarea::placeholder{color:rgba(255,255,255,.15);}
.field select option{background:var(--ink2);}
.field textarea{resize:vertical;min-height:70px;}
.field-hint{font-size:.7rem;color:rgba(255,255,255,.25);margin-top:3px;}

/* Sélecteur d'icône */
.icone-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;}
.icone-opt{display:none;}
.icone-label{display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 8px;border:1px solid rgba(255,255,255,.07);cursor:pointer;transition:all .2s;font-size:.65rem;color:rgba(255,255,255,.35);text-align:center;}
.icone-label i{font-size:1.2rem;color:rgba(255,255,255,.25);}
.icone-label:hover{border-color:rgba(212,168,67,.3);color:rgba(255,255,255,.6);}
.icone-opt:checked + .icone-label{border-color:var(--g1);background:rgba(212,168,67,.08);color:var(--g1);}
.icone-opt:checked + .icone-label i{color:var(--g1);}

.statut-toggle{display:flex;gap:0;}
.statut-radio{display:none;}
.statut-opt{padding:11px 20px;font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2px;text-transform:uppercase;cursor:pointer;border:1px solid rgba(255,255,255,.07);color:rgba(255,255,255,.35);transition:all .2s;background:rgba(255,255,255,.03);}
.statut-radio#st_actif:checked + .statut-opt{border-color:var(--green);color:var(--green);background:rgba(39,174,96,.08);}
.statut-radio#st_inactif:checked + .statut-opt{border-color:var(--red);color:#e74c3c;background:rgba(192,57,43,.08);}

.form-actions{display:flex;gap:10px;margin-top:8px;}
.btn-save{background:var(--g1);color:var(--ink);border:none;padding:13px 32px;font-family:'Cinzel',serif;font-size:.62rem;letter-spacing:3px;text-transform:uppercase;cursor:pointer;transition:background .25s;display:flex;align-items:center;gap:8px;}
.btn-save:hover{background:var(--g2);}
.btn-cancel{background:transparent;border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.4);padding:13px 22px;font-family:'Cinzel',serif;font-size:.62rem;letter-spacing:3px;text-transform:uppercase;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:8px;transition:all .25s;}
.btn-cancel:hover{border-color:rgba(255,255,255,.25);color:#fff;}

.preview-mode{background:rgba(212,168,67,.05);border:1px solid rgba(212,168,67,.15);padding:16px 20px;margin-bottom:16px;display:flex;align-items:center;gap:14px;}
.preview-mode-icon{width:40px;height:40px;background:rgba(212,168,67,.1);display:flex;align-items:center;justify-content:center;color:var(--g1);font-size:1.1rem;}
.preview-mode-text{font-size:.78rem;color:rgba(255,255,255,.4);}
.preview-mode-name{font-family:'EB Garamond',serif;font-size:1rem;color:#fff;}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-name">Lumoura</div>
    <div class="sidebar-logo-sub">Administration</div>
  </div>
  <div class="sidebar-section">Principal</div>
  <nav class="sidebar-nav">
    <a href="index.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="produits.php" class="nav-link"><i class="fas fa-gem"></i> Produits</a>
    <a href="commandes.php" class="nav-link"><i class="fas fa-shopping-bag"></i> Commandes</a>
    <a href="clients.php" class="nav-link"><i class="fas fa-users"></i> Clients</a>
  </nav>
  <div class="sidebar-section">Catalogue</div>
  <nav class="sidebar-nav">
    <a href="categories.php" class="nav-link"><i class="fas fa-tags"></i> Catégories</a>
    <a href="promotions.php" class="nav-link"><i class="fas fa-percent"></i> Promotions</a>
    <a href="avis.php" class="nav-link"><i class="fas fa-star"></i> Avis clients</a>
    <a href="livreurs.php" class="nav-link active"><i class="fas fa-truck"></i> Livraison</a>
  </nav>
  <div class="sidebar-section">Site</div>
  <nav class="sidebar-nav">
    <a href="../index.php" target="_blank" class="nav-link"><i class="fas fa-external-link-alt"></i> Voir le site</a>
  </nav>
  <div class="sidebar-bottom">
    <nav class="sidebar-nav">
      <a href="../pages/deconnexion.php" class="nav-link danger"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </nav>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <span class="topbar-title">
      <?php if($action==='liste'): ?>Modes de livraison (<?=count($livreurs)?>)
      <?php elseif($action==='ajouter'): ?>Nouveau mode de livraison
      <?php else: ?>Modifier le mode<?php endif; ?>
    </span>
    <?php if($action==='liste'): ?>
      <a href="livreurs.php?action=ajouter" class="topbar-btn"><i class="fas fa-plus"></i> Ajouter un mode</a>
    <?php else: ?>
      <a href="livreurs.php" class="topbar-btn ghost"><i class="fas fa-arrow-left"></i> Retour</a>
    <?php endif; ?>
    <div class="topbar-admin">
      <div class="topbar-avatar"><?=strtoupper(mb_substr($_SESSION['prenom'] ?? $_SESSION['user_nom'] ?? 'A',0,1))?></div>
      <?=htmlspecialchars($_SESSION['prenom'] ?? $_SESSION['user_nom'] ?? 'Admin')?>
    </div>
  </div>

  <div class="page-content">

    <?php if($msg): ?>
      <div class="msg-bar <?=$msgType?>">
        <i class="fas <?=$msgType==='ok'?'fa-check-circle':'fa-exclamation-circle'?>"></i>
        <?=htmlspecialchars($msg)?>
      </div>
    <?php endif; ?>

    <?php if($action === 'liste'): ?>

    <!-- STATS -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon gold"><i class="fas fa-truck"></i></div>
        <div><div class="stat-num"><?=$total?></div><div class="stat-label">Total modes</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div><div class="stat-num"><?=$actifs?></div><div class="stat-label">Actifs</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon red"><i class="fas fa-pause-circle"></i></div>
        <div><div class="stat-num"><?=$inactifs?></div><div class="stat-label">Inactifs</div></div>
      </div>
    </div>

    <!-- GRILLE MODES -->
    <?php if($livreurs): ?>
    <div class="modes-grid">
      <?php foreach($livreurs as $l): ?>
      <div class="mode-card">
        <div class="mode-card-top">
          <div class="mode-icon <?=$l['statut']==='inactif'?'inactif':''?>">
            <i class="fas <?=htmlspecialchars($l['icone'] ?? 'fa-truck')?>"></i>
          </div>
          <div>
            <div class="mode-nom"><?=htmlspecialchars($l['nom'])?></div>
          </div>
        </div>
        <div class="mode-desc"><?=htmlspecialchars($l['description'] ?? '')?></div>
        <div class="mode-infos">
          <?php if(!empty($l['delai'])): ?>
            <span class="mode-info-pill"><i class="fas fa-clock"></i><?=htmlspecialchars($l['delai'])?></span>
          <?php endif; ?>
          <?php if(!empty($l['zone_livraison'])): ?>
            <span class="mode-info-pill"><i class="fas fa-map-marker-alt"></i><?=htmlspecialchars($l['zone_livraison'])?></span>
          <?php endif; ?>
        </div>
        <div class="mode-prix <?=$l['prix']==0?'gratuit':''?>">
          <?=$l['prix']==0 ? 'Gratuit' : number_format($l['prix'],2,',',' ').'€'?>
          <?php if($l['prix']>0): ?><span>/ livraison</span><?php endif; ?>
        </div>
        <div class="mode-foot">
          <a href="livreurs.php?action=toggle&id=<?=$l['id_livreur']?>" class="statut-pill <?=$l['statut']?>" title="Changer statut">
            <span class="statut-dot <?=$l['statut']?>"></span>
            <?=$l['statut']==='actif'?'Actif':'Inactif'?>
          </a>
          <div class="mode-actions">
            <a href="livreurs.php?action=modifier&id=<?=$l['id_livreur']?>" class="tbl-btn edit" title="Modifier"><i class="fas fa-pen"></i></a>
            <a href="livreurs.php?action=supprimer&id=<?=$l['id_livreur']?>" class="tbl-btn del" title="Supprimer" onclick="return confirm('Supprimer ce mode ?')"><i class="fas fa-trash"></i></a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-truck" style="font-size:2rem;opacity:.15;display:block;margin-bottom:14px;"></i>
        Aucun mode de livraison.<br>
        <a href="livreurs.php?action=ajouter" style="color:var(--g1);text-decoration:none;">Créer le premier →</a>
      </div>
    <?php endif; ?>

    <?php else:
      $l = $edit_liv ?? [];
      $is_edit = $action === 'modifier';
      $icone_actuelle = $l['icone'] ?? 'fa-truck';
    ?>

    <!-- FORMULAIRE -->
    <div class="form-wrap">
      <form method="POST" action="livreurs.php<?=$is_edit?"?action=modifier&id=$edit_id":''?>" id="modeForm">
        <input type="hidden" name="form_action" value="<?=$is_edit?'modifier':'ajouter'?>">
        <?php if($is_edit): ?><input type="hidden" name="id_livreur" value="<?=$edit_id?>"><?php endif; ?>

        <!-- Infos principales -->
        <div class="form-panel">
          <div class="form-panel-title"><i class="fas fa-truck"></i> Informations du mode</div>

          <!-- Prévisualisation -->
          <div class="preview-mode" id="previewMode">
            <div class="preview-mode-icon"><i class="fas <?=$icone_actuelle?>" id="previewIcon"></i></div>
            <div>
              <div class="preview-mode-name" id="previewNom"><?=htmlspecialchars($l['nom']??'Nom du mode')?></div>
              <div class="preview-mode-text" id="previewDesc"><?=htmlspecialchars($l['description']??'Description...')?></div>
            </div>
          </div>

          <div style="display:flex;flex-direction:column;gap:16px;">
            <div class="field">
              <label>Nom du mode *</label>
              <input type="text" name="nom" id="inputNom" value="<?=htmlspecialchars($l['nom']??'')?>"
                     placeholder="Ex: Standard, Express, Chronopost..." required>
            </div>
            <div class="field">
              <label>Description</label>
              <textarea name="description" id="inputDesc" placeholder="Ex: Livraison rapide garantie le lendemain"><?=htmlspecialchars($l['description']??'')?></textarea>
            </div>
          </div>
        </div>

        <!-- Icône -->
        <div class="form-panel">
          <div class="form-panel-title"><i class="fas fa-icons"></i> Icône du mode</div>
          <div class="icone-grid">
            <?php foreach($icones_dispo as $ico => $label): ?>
              <input type="radio" name="icone" id="ico_<?=str_replace('-','_',$ico)?>" class="icone-opt"
                     value="<?=$ico?>" <?=$icone_actuelle===$ico?'checked':''?>>
              <label for="ico_<?=str_replace('-','_',$ico)?>" class="icone-label" onclick="updateIcon('<?=$ico?>')">
                <i class="fas <?=$ico?>"></i>
                <?=$label?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Prix & Délai -->
        <div class="form-panel">
          <div class="form-panel-title"><i class="fas fa-euro-sign"></i> Tarif & délai</div>
          <div class="form-grid-2">
            <div class="field">
              <label>Prix (€)</label>
              <input type="number" name="prix" value="<?=htmlspecialchars($l['prix']??'0')?>" step="0.01" min="0" placeholder="0.00">
              <span class="field-hint">Mettre 0 pour livraison gratuite</span>
            </div>
            <div class="field">
              <label>Délai estimé</label>
              <select name="delai">
                <option value="">— Choisir —</option>
                <?php foreach(['Immédiat','24h','48h','3-5 jours','5-7 jours','7-10 jours','10-15 jours'] as $d): ?>
                  <option value="<?=$d?>" <?=($l['delai']??'')===$d?'selected':''?>><?=$d?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field" style="grid-column:1/-1;">
              <label>Zone de livraison</label>
              <input type="text" name="zone_livraison" value="<?=htmlspecialchars($l['zone_livraison']??'')?>"
                     placeholder="Ex: France entière, Europe, International...">
            </div>
            <div class="field" style="grid-column:1/-1;">
              <label>Statut</label>
              <div class="statut-toggle">
                <input type="radio" name="statut" id="st_actif" class="statut-radio" value="actif" <?=($l['statut']??'actif')==='actif'?'checked':''?>>
                <label for="st_actif" class="statut-opt"><i class="fas fa-check-circle"></i> Actif</label>
                <input type="radio" name="statut" id="st_inactif" class="statut-radio" value="inactif" <?=($l['statut']??'')==='inactif'?'checked':''?>>
                <label for="st_inactif" class="statut-opt"><i class="fas fa-pause-circle"></i> Inactif</label>
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-save">
            <i class="fas <?=$is_edit?'fa-save':'fa-plus'?>"></i>
            <?=$is_edit?'Enregistrer':'Créer le mode'?>
          </button>
          <a href="livreurs.php" class="btn-cancel"><i class="fas fa-times"></i> Annuler</a>
        </div>
      </form>
    </div>

    <?php endif; ?>
  </div>
</div>

<script>
// Prévisualisation live
document.getElementById('inputNom')?.addEventListener('input', function(){
  document.getElementById('previewNom').textContent = this.value || 'Nom du mode';
});
document.getElementById('inputDesc')?.addEventListener('input', function(){
  document.getElementById('previewDesc').textContent = this.value || 'Description...';
});
function updateIcon(ico) {
  const el = document.getElementById('previewIcon');
  if(el) { el.className = 'fas ' + ico; }
}
</script>
</body>
</html>