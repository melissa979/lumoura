<?php
// ══════════════════════════════════════════
//  LUMOURA — Gestion des Commandes
//  admin/commandes.php
// ══════════════════════════════════════════
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../pages/connexion.php'); exit();
}

$msg = ''; $msgType = '';

// ─── MISE À JOUR STATUT ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_statut'])) {
    $id     = intval($_POST['id_commande']);
    $statut = trim($_POST['statut']);
    $allowed = ['en attente', 'payee', 'expediee', 'livree', 'annulee'];
    if ($id && in_array($statut, $allowed)) {
        try {
            $pdo->prepare("UPDATE commandes SET statut=? WHERE id_commande=?")->execute([$statut, $id]);
            $msg = '✦ Statut mis à jour avec succès !'; $msgType = 'ok';
        } catch(Exception $e) {
            $msg = 'Erreur : '.$e->getMessage(); $msgType = 'error';
        }
    }
}

// ─── SUPPRESSION ───
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $pdo->prepare("DELETE FROM commandes WHERE id_commande=?")->execute([$id]);
        $msg = 'Commande supprimée.'; $msgType = 'ok';
    } catch(Exception $e) {
        $msg = 'Erreur suppression.'; $msgType = 'error';
    }
}

// ─── FILTRES ───
$filtre_statut = trim($_GET['statut'] ?? '');
$search_q      = trim($_GET['q'] ?? '');
$detail_id     = isset($_GET['detail']) ? intval($_GET['detail']) : null;

// ─── CHARGEMENT COMMANDES ───
$commandes = [];
try {
    $sql = "SELECT c.*, u.nom, u.prenom, u.email
            FROM commandes c
            JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
            WHERE 1=1";
    $params = [];
    if ($filtre_statut) {
        $sql .= " AND LOWER(TRIM(c.statut)) = ?";
        $params[] = strtolower($filtre_statut);
    }
    if ($search_q) {
        $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR c.id_commande = ?)";
        $params[] = "%$search_q%"; $params[] = "%$search_q%";
        $params[] = "%$search_q%"; $params[] = intval($search_q);
    }
    $sql .= " ORDER BY c.date_commande DESC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $commandes = $st->fetchAll();
} catch(Exception $e) {
    $db_error = $e->getMessage();
}

// ─── DÉTAIL COMMANDE ───
$detail = null;
$detail_items = [];
if ($detail_id) {
    try {
        $st = $pdo->prepare("SELECT c.*, u.nom, u.prenom, u.email, u.telephone
                             FROM commandes c
                             JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
                             WHERE c.id_commande = ?");
        $st->execute([$detail_id]);
        $detail = $st->fetch();

        // Essayer de charger les lignes de commande
        try {
            $st2 = $pdo->prepare("SELECT lc.*, p.nom, p.marque, p.image_url
                                  FROM lignes_commande lc
                                  JOIN produits p ON lc.id_produit = p.id_produit
                                  WHERE lc.id_commande = ?");
            $st2->execute([$detail_id]);
            $detail_items = $st2->fetchAll();
        } catch(Exception $e) { $detail_items = []; }
    } catch(Exception $e) { $detail = null; }
}

// ─── STATS ───
$stats = ['total' => 0, 'en_attente' => 0, 'payee' => 0, 'expediee' => 0, 'livree' => 0, 'annulee' => 0, 'ca' => 0];
try {
    $rows = $pdo->query("SELECT LOWER(TRIM(statut)) as s, COUNT(*) as n, SUM(montant) as m FROM commandes GROUP BY LOWER(TRIM(statut))")->fetchAll();
    foreach ($rows as $r) {
        $stats['total'] += $r['n'];
        $key = str_replace(['é','è','ê','à'], ['e','e','e','a'], $r['s']);
        $key = str_replace(' ', '_', $key);
        if (isset($stats[$key])) $stats[$key] = $r['n'];
        if (!in_array($r['s'], ['annulee','annulée'])) $stats['ca'] += $r['m'];
    }
} catch(Exception $e) {}

function badgeStatut($s) {
    $s = strtolower(trim($s));
    $map = [
        'en attente'  => ['En attente', 'pend'],
        'en_attente'  => ['En attente', 'pend'],
        'payee'       => ['Payée',      'ok'],
        'payée'       => ['Payée',      'ok'],
        'expediee'    => ['Expédiée',   'ship'],
        'expédiée'    => ['Expédiée',   'ship'],
        'livree'      => ['Livrée',     'done'],
        'livrée'      => ['Livrée',     'done'],
        'annulee'     => ['Annulée',    'cancel'],
        'annulée'     => ['Annulée',    'cancel'],
    ];
    return $map[$s] ?? [ucfirst($s), 'pend'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Commandes — Admin Lumoura</title>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Cinzel:wght@400;600;700&family=Didact+Gothic&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--g1:#D4A843;--g2:#F5D78E;--g3:#B8882C;--ink:#0D0A06;--ink2:#1A140E;--ink3:#241C12;--red:#C0392B;--green:#27AE60;--blue:#3498db;--sidebar:240px;}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Didact Gothic',sans-serif;background:var(--ink);color:rgba(255,255,255,.85);display:flex;min-height:100vh;}

/* SIDEBAR */
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
.nav-link.active::before{content:'';position:absolute;left:0;top:0;bottom:0;width:2px;background:var(--g1);border-radius:0 2px 2px 0;}
.nav-badge{margin-left:auto;background:var(--red);color:#fff;font-size:.52rem;padding:2px 7px;border-radius:20px;}
.sidebar-bottom{margin-top:auto;padding:16px 12px;border-top:1px solid rgba(255,255,255,.04);}
.nav-link.danger:hover{color:var(--red);background:rgba(192,57,43,.08);}

/* MAIN */
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

/* MSG */
.msg-bar{padding:14px 20px;margin-bottom:24px;font-size:.84rem;display:flex;align-items:center;gap:10px;}
.msg-bar.ok{background:rgba(39,174,96,.12);border-left:3px solid var(--green);color:#2ecc71;}
.msg-bar.error{background:rgba(192,57,43,.12);border-left:3px solid var(--red);color:#e74c3c;}

/* STATS MINI */
.stats-strip{display:grid;grid-template-columns:repeat(5,1fr);gap:2px;background:rgba(255,255,255,.04);margin-bottom:28px;}
.stat-mini{background:var(--ink2);padding:16px 20px;cursor:pointer;transition:background .2s;text-decoration:none;display:block;}
.stat-mini:hover,.stat-mini.active{background:var(--ink3);}
.stat-mini.active{border-top:2px solid var(--g1);}
.stat-mini-num{font-family:'EB Garamond',serif;font-size:1.6rem;color:var(--g1);line-height:1;}
.stat-mini-label{font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.3);margin-top:5px;}

/* TOOLBAR */
.list-toolbar{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
.search-wrap{position:relative;flex:1;min-width:200px;}
.search-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.25);font-size:.85rem;}
.search-input{width:100%;background:var(--ink2);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.8);padding:10px 14px 10px 38px;font-family:'Didact Gothic',sans-serif;font-size:.82rem;transition:border-color .25s;}
.search-input:focus{outline:none;border-color:rgba(212,168,67,.4);}
.search-input::placeholder{color:rgba(255,255,255,.2);}
.filter-select{background:var(--ink2);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.6);padding:10px 14px;font-size:.82rem;}
.filter-select:focus{outline:none;}

/* TABLE */
.cmd-table{width:100%;border-collapse:collapse;background:var(--ink2);}
.cmd-table thead tr{border-bottom:1px solid rgba(255,255,255,.06);}
.cmd-table th{font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.3);padding:14px 16px;text-align:left;}
.cmd-table td{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.03);color:rgba(255,255,255,.7);font-size:.82rem;vertical-align:middle;}
.cmd-table tr:last-child td{border-bottom:none;}
.cmd-table tr:hover td{background:rgba(255,255,255,.02);}

.badge{display:inline-block;padding:4px 12px;font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:1.5px;text-transform:uppercase;}
.badge.pend{background:rgba(212,168,67,.15);color:var(--g1);}
.badge.ok{background:rgba(39,174,96,.15);color:#2ecc71;}
.badge.ship{background:rgba(52,152,219,.15);color:var(--blue);}
.badge.done{background:rgba(39,174,96,.25);color:#27ae60;}
.badge.cancel{background:rgba(192,57,43,.15);color:var(--red);}

.tbl-actions{display:flex;gap:6px;align-items:center;}
.tbl-btn{width:32px;height:32px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.82rem;transition:all .2s;text-decoration:none;}
.tbl-btn.view{background:rgba(255,255,255,.06);color:rgba(255,255,255,.4);}
.tbl-btn.view:hover{color:#fff;background:rgba(255,255,255,.12);}
.tbl-btn.del{background:rgba(192,57,43,.1);color:var(--red);}
.tbl-btn.del:hover{background:rgba(192,57,43,.25);}

/* INLINE STATUT SELECT */
.statut-form{display:flex;align-items:center;gap:6px;}
.statut-select{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);color:rgba(255,255,255,.7);padding:5px 10px;font-size:.75rem;font-family:'Didact Gothic',sans-serif;cursor:pointer;}
.statut-select:focus{outline:none;border-color:rgba(212,168,67,.3);}
.statut-select option{background:var(--ink2);}
.btn-statut{background:rgba(212,168,67,.1);border:1px solid rgba(212,168,67,.2);color:var(--g1);padding:5px 10px;font-size:.6rem;font-family:'Cinzel',serif;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;transition:all .2s;}
.btn-statut:hover{background:rgba(212,168,67,.2);}

.empty-state{text-align:center;padding:60px 20px;color:rgba(255,255,255,.2);font-family:'EB Garamond',serif;font-size:1.2rem;}

/* ═══ PANEL DÉTAIL ═══ */
.detail-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;display:flex;align-items:flex-start;justify-content:flex-end;}
.detail-panel{background:var(--ink2);width:520px;max-width:95vw;height:100vh;overflow-y:auto;border-left:1px solid rgba(255,255,255,.06);display:flex;flex-direction:column;}
.detail-header{padding:24px 28px;border-bottom:1px solid rgba(255,255,255,.05);display:flex;align-items:center;gap:14px;position:sticky;top:0;background:var(--ink2);z-index:10;}
.detail-header h2{font-family:'EB Garamond',serif;font-size:1.3rem;font-weight:400;flex:1;}
.detail-close{width:36px;height:36px;background:rgba(255,255,255,.05);border:none;color:rgba(255,255,255,.4);cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;transition:all .2s;}
.detail-close:hover{background:rgba(255,255,255,.1);color:#fff;}
.detail-body{padding:28px;display:flex;flex-direction:column;gap:24px;}
.detail-section{background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);padding:18px 20px;}
.detail-section-title{font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:3px;text-transform:uppercase;color:var(--g1);margin-bottom:14px;}
.detail-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.03);}
.detail-row:last-child{border-bottom:none;}
.detail-row-label{font-size:.75rem;color:rgba(255,255,255,.35);}
.detail-row-val{font-size:.82rem;color:rgba(255,255,255,.8);text-align:right;}
.detail-item{display:flex;align-items:center;gap:14px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.detail-item:last-child{border-bottom:none;}
.detail-item-img{width:52px;height:52px;object-fit:cover;border:1px solid rgba(255,255,255,.07);flex-shrink:0;}
.detail-item-name{font-family:'EB Garamond',serif;font-size:1rem;color:rgba(255,255,255,.9);}
.detail-item-sub{font-size:.72rem;color:rgba(255,255,255,.3);margin-top:2px;}
.detail-item-price{margin-left:auto;font-family:'EB Garamond',serif;font-size:1rem;color:var(--g1);white-space:nowrap;}
.detail-total{display:flex;justify-content:space-between;padding-top:14px;margin-top:4px;border-top:1px solid rgba(255,255,255,.08);}
.detail-total-label{font-family:'Cinzel',serif;font-size:.6rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.4);}
.detail-total-val{font-family:'EB Garamond',serif;font-size:1.4rem;color:var(--g1);}

/* Statut update form in panel */
.detail-statut-form{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:12px;}
.detail-statut-select{flex:1;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.75);padding:10px 14px;font-size:.82rem;font-family:'Didact Gothic',sans-serif;}
.detail-statut-select:focus{outline:none;border-color:rgba(212,168,67,.4);}
.detail-statut-select option{background:var(--ink2);}
.btn-update{background:var(--g1);color:var(--ink);border:none;padding:10px 20px;font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2px;text-transform:uppercase;cursor:pointer;transition:background .25s;}
.btn-update:hover{background:var(--g2);}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-name">Lumoura</div>
    <div class="sidebar-logo-sub">Administration</div>
  </div>
  <div class="sidebar-section">Principal</div>
  <nav class="sidebar-nav">
    <a href="index.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="produits.php" class="nav-link"><i class="fas fa-gem"></i> Produits</a>
    <a href="commandes.php" class="nav-link active"><i class="fas fa-shopping-bag"></i> Commandes
      <?php if($stats['total'] > 0): ?><span class="nav-badge"><?=$stats['total']?></span><?php endif; ?>
    </a>
    <a href="clients.php" class="nav-link"><i class="fas fa-users"></i> Clients</a>
  </nav>
  <div class="sidebar-section">Catalogue</div>
  <nav class="sidebar-nav">
    <a href="categories.php" class="nav-link"><i class="fas fa-tags"></i> Catégories</a>
    <a href="promotions.php" class="nav-link"><i class="fas fa-percent"></i> Promotions</a>
    <a href="avis.php" class="nav-link"><i class="fas fa-star"></i> Avis clients</a>
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

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <span class="topbar-title">Commandes (<?=count($commandes)?>)</span>
    <div class="topbar-admin">
      <div class="topbar-avatar"><?=strtoupper(mb_substr($_SESSION['prenom'] ?? $_SESSION['user_nom'] ?? 'A', 0, 1))?></div>
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

    <?php if(isset($db_error)): ?>
      <div class="msg-bar error"><i class="fas fa-exclamation-triangle"></i> Erreur DB : <?=htmlspecialchars($db_error)?></div>
    <?php endif; ?>

    <!-- STATS STRIP -->
    <div class="stats-strip">
      <a href="commandes.php" class="stat-mini <?=!$filtre_statut?'active':''?>">
        <div class="stat-mini-num"><?=$stats['total']?></div>
        <div class="stat-mini-label">Toutes</div>
      </a>
      <a href="commandes.php?statut=en+attente" class="stat-mini <?=$filtre_statut==='en attente'?'active':''?>">
        <div class="stat-mini-num" style="color:var(--g1);"><?=$stats['en_attente']?></div>
        <div class="stat-mini-label">En attente</div>
      </a>
      <a href="commandes.php?statut=payee" class="stat-mini <?=$filtre_statut==='payee'?'active':''?>">
        <div class="stat-mini-num" style="color:#2ecc71;"><?=$stats['payee']?></div>
        <div class="stat-mini-label">Payées</div>
      </a>
      <a href="commandes.php?statut=expediee" class="stat-mini <?=$filtre_statut==='expediee'?'active':''?>">
        <div class="stat-mini-num" style="color:var(--blue);"><?=$stats['expediee']?></div>
        <div class="stat-mini-label">Expédiées</div>
      </a>
      <a href="commandes.php?statut=livree" class="stat-mini <?=$filtre_statut==='livree'?'active':''?>">
        <div class="stat-mini-num" style="color:#27ae60;"><?=$stats['livree']?></div>
        <div class="stat-mini-label">Livrées</div>
      </a>
    </div>

    <!-- TOOLBAR -->
    <form method="GET" action="">
      <div class="list-toolbar">
        <div class="search-wrap">
          <i class="fas fa-search"></i>
          <input type="text" name="q" class="search-input" placeholder="Rechercher client, email, n° commande..." value="<?=htmlspecialchars($search_q)?>">
        </div>
        <?php if($filtre_statut): ?>
          <input type="hidden" name="statut" value="<?=htmlspecialchars($filtre_statut)?>">
        <?php endif; ?>
        <button type="submit" class="topbar-btn" style="height:40px;padding:0 18px;">Rechercher</button>
        <?php if($search_q || $filtre_statut): ?>
          <a href="commandes.php" class="topbar-btn ghost" style="height:40px;padding:0 16px;">Réinitialiser</a>
        <?php endif; ?>
      </div>
    </form>

    <!-- TABLE COMMANDES -->
    <table class="cmd-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Client</th>
          <th>Montant</th>
          <th>Statut</th>
          <th>Date</th>
          <th>Modifier statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if($commandes): foreach($commandes as $c):
          [$label, $cls] = badgeStatut($c['statut'] ?? '');
          $currentStatut = strtolower(trim($c['statut'] ?? 'en attente'));
        ?>
        <tr>
          <td style="font-family:'Cinzel',serif;font-size:.7rem;color:rgba(255,255,255,.3);">#<?=$c['id_commande']?></td>
          <td>
            <div style="font-weight:500;color:rgba(255,255,255,.9);"><?=htmlspecialchars($c['prenom'].' '.$c['nom'])?></div>
            <div style="font-size:.72rem;color:rgba(255,255,255,.3);margin-top:2px;"><?=htmlspecialchars($c['email'])?></div>
          </td>
          <td style="font-family:'EB Garamond',serif;font-size:1.1rem;color:var(--g1);"><?=number_format($c['montant'],2,',',' ')?>€</td>
          <td><span class="badge <?=$cls?>"><?=$label?></span></td>
          <td style="color:rgba(255,255,255,.35);font-size:.78rem;"><?=date('d/m/Y à H:i', strtotime($c['date_commande']))?></td>
          <td>
            <form method="POST" action="" class="statut-form">
              <input type="hidden" name="id_commande" value="<?=$c['id_commande']?>">
              <select name="statut" class="statut-select">
                <option value="en attente" <?=$currentStatut==='en attente'?'selected':''?>>En attente</option>
                <option value="payee"      <?=in_array($currentStatut,['payee','payée'])?'selected':''?>>Payée</option>
                <option value="expediee"   <?=in_array($currentStatut,['expediee','expédiée'])?'selected':''?>>Expédiée</option>
                <option value="livree"     <?=in_array($currentStatut,['livree','livrée'])?'selected':''?>>Livrée</option>
                <option value="annulee"    <?=in_array($currentStatut,['annulee','annulée'])?'selected':''?>>Annulée</option>
              </select>
              <button type="submit" name="update_statut" class="btn-statut">OK</button>
            </form>
          </td>
          <td>
            <div class="tbl-actions">
              <a href="commandes.php?detail=<?=$c['id_commande']?><?=$filtre_statut?'&statut='.urlencode($filtre_statut):''?><?=$search_q?'&q='.urlencode($search_q):''?>" class="tbl-btn view" title="Voir le détail"><i class="fas fa-eye"></i></a>
              <a href="commandes.php?action=supprimer&id=<?=$c['id_commande']?>" class="tbl-btn del" title="Supprimer" onclick="return confirm('Supprimer définitivement cette commande ?')"><i class="fas fa-trash"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="7" class="empty-state">Aucune commande trouvée.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

  </div><!-- /page-content -->
</div><!-- /main -->

<?php if($detail): ?>
<!-- PANNEAU DÉTAIL -->
<div class="detail-overlay" onclick="if(event.target===this) window.location='commandes.php<?=$filtre_statut?'?statut='.urlencode($filtre_statut):''?>';">
  <div class="detail-panel">
    <div class="detail-header">
      <h2>Commande #<?=$detail['id_commande']?></h2>
      <?php [$label,$cls] = badgeStatut($detail['statut']??''); ?>
      <span class="badge <?=$cls?>"><?=$label?></span>
      <a href="commandes.php<?=$filtre_statut?'?statut='.urlencode($filtre_statut):''?>" class="detail-close"><i class="fas fa-times"></i></a>
    </div>
    <div class="detail-body">

      <!-- Infos client -->
      <div class="detail-section">
        <div class="detail-section-title"><i class="fas fa-user"></i> &nbsp;Client</div>
        <div class="detail-row">
          <span class="detail-row-label">Nom</span>
          <span class="detail-row-val"><?=htmlspecialchars($detail['prenom'].' '.$detail['nom'])?></span>
        </div>
        <div class="detail-row">
          <span class="detail-row-label">Email</span>
          <span class="detail-row-val"><?=htmlspecialchars($detail['email'])?></span>
        </div>
        <?php if(!empty($detail['telephone'])): ?>
        <div class="detail-row">
          <span class="detail-row-label">Téléphone</span>
          <span class="detail-row-val"><?=htmlspecialchars($detail['telephone'])?></span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Infos commande -->
      <div class="detail-section">
        <div class="detail-section-title"><i class="fas fa-receipt"></i> &nbsp;Détails</div>
        <div class="detail-row">
          <span class="detail-row-label">Date</span>
          <span class="detail-row-val"><?=date('d/m/Y à H:i', strtotime($detail['date_commande']))?></span>
        </div>
        <div class="detail-row">
          <span class="detail-row-label">Montant total</span>
          <span class="detail-row-val" style="color:var(--g1);font-family:'EB Garamond',serif;font-size:1.1rem;"><?=number_format($detail['montant'],2,',',' ')?>€</span>
        </div>
        <?php if(!empty($detail['adresse_livraison'])): ?>
        <div class="detail-row">
          <span class="detail-row-label">Livraison</span>
          <span class="detail-row-val" style="max-width:280px;"><?=nl2br(htmlspecialchars($detail['adresse_livraison']))?></span>
        </div>
        <?php endif; ?>
        <?php if(!empty($detail['mode_paiement'])): ?>
        <div class="detail-row">
          <span class="detail-row-label">Paiement</span>
          <span class="detail-row-val"><?=htmlspecialchars($detail['mode_paiement'])?></span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Produits commandés -->
      <?php if($detail_items): ?>
      <div class="detail-section">
        <div class="detail-section-title"><i class="fas fa-gem"></i> &nbsp;Produits</div>
        <?php foreach($detail_items as $item): ?>
        <div class="detail-item">
          <img class="detail-item-img" src="<?=htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/52x52/1A140E/D4A843?text=✦')?>" alt="">
          <div>
            <div class="detail-item-name"><?=htmlspecialchars($item['nom'])?></div>
            <div class="detail-item-sub"><?=htmlspecialchars($item['marque']??'')?> &nbsp;·&nbsp; Qté : <?=$item['quantite']??1?></div>
          </div>
          <div class="detail-item-price"><?=number_format(($item['prix_unitaire']??0)*($item['quantite']??1),2,',',' ')?>€</div>
        </div>
        <?php endforeach; ?>
        <div class="detail-total">
          <span class="detail-total-label">Total</span>
          <span class="detail-total-val"><?=number_format($detail['montant'],2,',',' ')?>€</span>
        </div>
      </div>
      <?php endif; ?>

      <!-- Modifier statut depuis panneau -->
      <div class="detail-section">
        <div class="detail-section-title"><i class="fas fa-edit"></i> &nbsp;Modifier le statut</div>
        <form method="POST" action="commandes.php?detail=<?=$detail['id_commande']?>" class="detail-statut-form">
          <input type="hidden" name="id_commande" value="<?=$detail['id_commande']?>">
          <select name="statut" class="detail-statut-select">
            <?php
            $cs = strtolower(trim($detail['statut']??''));
            foreach(['en attente'=>'En attente','payee'=>'Payée','expediee'=>'Expédiée','livree'=>'Livrée','annulee'=>'Annulée'] as $v=>$l):
            ?>
            <option value="<?=$v?>" <?=($cs===$v||$cs===str_replace('e','ée',$v))?'selected':''?>><?=$l?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" name="update_statut" class="btn-update"><i class="fas fa-save"></i> Enregistrer</button>
        </form>
      </div>

    </div>
  </div>
</div>
<?php endif; ?>

</body>
</html>