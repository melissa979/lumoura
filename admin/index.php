<?php
// ══════════════════════════════════════════
//  LUMOURA — Panneau d'administration
//  admin/index.php
// ══════════════════════════════════════════

// Éviter le conflit session_start() avec config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protection admin
if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../pages/connexion.php?redirect=admin');
    exit();
}

// Stats rapides — utilise 'montant' (pas 'total')
try {
    $nbProduits  = (int)$pdo->query("SELECT COUNT(*) FROM produits")->fetchColumn();
    $nbCommandes = (int)$pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
    $caTotal     = (float)$pdo->query("SELECT COALESCE(SUM(montant),0) FROM commandes WHERE LOWER(TRIM(statut)) != 'annulee' AND LOWER(TRIM(statut)) != 'annulée'")->fetchColumn();
    $nbUsers     = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role != 'admin'")->fetchColumn();

    $lastOrders = $pdo->query("
        SELECT c.*, u.nom, u.prenom
        FROM commandes c
        JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur
        ORDER BY c.date_commande DESC LIMIT 5
    ")->fetchAll();

    $lowStock = $pdo->query("
        SELECT * FROM produits WHERE stock <= 3 ORDER BY stock ASC LIMIT 5
    ")->fetchAll();

} catch(Exception $e) {
    $nbProduits = $nbCommandes = $caTotal = $nbUsers = 0;
    $lastOrders = $lowStock = [];
    $db_error = $e->getMessage();
}

// Normaliser statut pour l'affichage
function badgeStatut($s) {
    $s = strtolower(trim($s));
    $map = [
        'en attente'  => ['En attente', 'pend'],
        'en_attente'  => ['En attente', 'pend'],
        'payee'       => ['Payée',      'ok'],
        'payée'       => ['Payée',      'ok'],
        'expediee'    => ['Expédiée',   'ship'],
        'expédiée'    => ['Expédiée',   'ship'],
        'livree'      => ['Livrée',     'ok'],
        'livrée'      => ['Livrée',     'ok'],
        'annulee'     => ['Annulée',    'cancel'],
        'annulée'     => ['Annulée',    'cancel'],
    ];
    return $map[$s] ?? [ucfirst($s), 'pend'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Administration — Lumoura</title>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Cinzel:wght@400;600;700&family=Didact+Gothic&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{
  --g1:#D4A843;--g2:#F5D78E;--g3:#B8882C;
  --ink:#0D0A06;--ink2:#1A140E;--ink3:#241C12;
  --smoke:#F8F5EF;--stone:#E8E0D0;--muted:#8A7D6A;
  --red:#C0392B;--green:#27AE60;
  --sidebar:240px;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Didact Gothic',sans-serif;background:var(--ink);color:rgba(255,255,255,.85);display:flex;min-height:100vh;overflow-x:hidden;}

/* ═══ SIDEBAR ═══ */
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

/* ═══ MAIN ═══ */
.main{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;}
.topbar{background:var(--ink2);border-bottom:1px solid rgba(255,255,255,.04);padding:0 40px;height:62px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50;}
.topbar-title{font-family:'EB Garamond',serif;font-size:1.5rem;color:#fff;font-weight:400;flex:1;}
.topbar-admin{display:flex;align-items:center;gap:10px;font-size:.78rem;color:rgba(255,255,255,.4);}
.topbar-avatar{width:34px;height:34px;border-radius:50%;background:rgba(212,168,67,.15);border:1px solid rgba(212,168,67,.3);display:flex;align-items:center;justify-content:center;font-family:'Cinzel',serif;font-size:.75rem;color:var(--g1);}
.topbar-btn{display:flex;align-items:center;gap:7px;background:var(--g1);color:var(--ink);padding:9px 18px;border:none;cursor:pointer;font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2px;text-transform:uppercase;text-decoration:none;transition:background .25s;}
.topbar-btn:hover{background:var(--g2);}

.page-content{padding:36px 40px;flex:1;}

/* Erreur DB */
.db-error{background:rgba(192,57,43,.1);border-left:3px solid var(--red);padding:12px 18px;margin-bottom:24px;font-size:.82rem;color:#e74c3c;}

/* ═══ STATS ═══ */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:2px;background:rgba(255,255,255,.04);margin-bottom:32px;}
.stat-card{background:var(--ink2);padding:26px 24px;position:relative;overflow:hidden;transition:background .3s;cursor:default;}
.stat-card:hover{background:var(--ink3);}
.stat-card::after{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--g1),var(--g2));transform:scaleX(0);transform-origin:left;transition:transform .4s;}
.stat-card:hover::after{transform:scaleX(1);}
.stat-icon{font-size:1.4rem;color:rgba(212,168,67,.35);margin-bottom:14px;}
.stat-num{font-family:'EB Garamond',serif;font-size:2.2rem;color:var(--g1);font-weight:500;line-height:1;margin-bottom:6px;}
.stat-label{font-size:.7rem;color:rgba(255,255,255,.35);letter-spacing:1.5px;text-transform:uppercase;font-family:'Cinzel',serif;}

/* ═══ GRILLE ═══ */
.dash-grid{display:grid;grid-template-columns:1fr 1fr;gap:2px;background:rgba(255,255,255,.04);}
.panel{background:var(--ink2);padding:26px 28px;}
.panel-head{display:flex;align-items:center;gap:14px;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid rgba(255,255,255,.05);}
.panel-head h3{font-family:'EB Garamond',serif;font-size:1.2rem;color:#fff;font-weight:400;flex:1;}
.panel-link{font-family:'Cinzel',serif;font-size:.55rem;letter-spacing:2px;text-transform:uppercase;color:var(--g1);text-decoration:none;}
.panel-link:hover{color:var(--g2);}

/* Table */
.admin-table{width:100%;border-collapse:collapse;font-size:.8rem;}
.admin-table th{font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.3);padding:0 0 12px;text-align:left;border-bottom:1px solid rgba(255,255,255,.04);}
.admin-table td{padding:12px 0;border-bottom:1px solid rgba(255,255,255,.03);color:rgba(255,255,255,.65);vertical-align:middle;}
.admin-table tr:last-child td{border-bottom:none;}
.admin-table tr:hover td{color:rgba(255,255,255,.85);}

.badge{display:inline-block;padding:3px 10px;font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:1.5px;text-transform:uppercase;}
.badge.pend{background:rgba(212,168,67,.15);color:var(--g1);}
.badge.ok{background:rgba(39,174,96,.15);color:#2ecc71;}
.badge.ship{background:rgba(52,152,219,.15);color:#3498db;}
.badge.cancel{background:rgba(192,57,43,.15);color:var(--red);}

.stock-low{color:var(--red);}
.stock-warn{color:#F39C12;}
.stock-ok{color:var(--green);}

.empty-row td{text-align:center;padding:28px;color:rgba(255,255,255,.2);font-style:italic;border:none!important;}
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
    <a href="index.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="produits.php" class="nav-link"><i class="fas fa-gem"></i> Produits</a>
    <a href="commandes.php" class="nav-link"><i class="fas fa-shopping-bag"></i> Commandes
      <?php if($nbCommandes > 0): ?><span class="nav-badge"><?=$nbCommandes?></span><?php endif; ?>
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

<div class="main">
  <div class="topbar">
    <span class="topbar-title">Dashboard</span>
    <a href="produits.php?action=ajouter" class="topbar-btn"><i class="fas fa-plus"></i> Nouveau produit</a>
    <div class="topbar-admin">
      <div class="topbar-avatar"><?=strtoupper(mb_substr($_SESSION['prenom'] ?? $_SESSION['user_nom'] ?? 'A', 0, 1))?></div>
      <?=htmlspecialchars($_SESSION['prenom'] ?? $_SESSION['user_nom'] ?? 'Admin')?>
    </div>
  </div>

  <div class="page-content">

    <?php if(isset($db_error)): ?>
    <div class="db-error"><i class="fas fa-exclamation-triangle"></i> Erreur DB : <?=htmlspecialchars($db_error)?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-gem"></i></div>
        <div class="stat-num"><?=$nbProduits?></div>
        <div class="stat-label">Produits</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-num"><?=$nbCommandes?></div>
        <div class="stat-label">Commandes</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-euro-sign"></i></div>
        <div class="stat-num"><?=number_format($caTotal, 0, ',', ' ')?>€</div>
        <div class="stat-label">Chiffre d'affaires</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-num"><?=$nbUsers?></div>
        <div class="stat-label">Clients</div>
      </div>
    </div>

    <div class="dash-grid">

      <!-- Dernières commandes -->
      <div class="panel">
        <div class="panel-head">
          <h3>Dernières commandes</h3>
          <a href="commandes.php" class="panel-link">Tout voir →</a>
        </div>
        <table class="admin-table">
          <tr><th>Client</th><th>Montant</th><th>Statut</th><th>Date</th></tr>
          <?php if($lastOrders): foreach($lastOrders as $o):
            [$label, $cls] = badgeStatut($o['statut'] ?? '');
          ?>
          <tr>
            <td><?=htmlspecialchars($o['prenom'].' '.$o['nom'])?></td>
            <td><?=number_format($o['montant'], 2, ',', ' ')?>€</td>
            <td><span class="badge <?=$cls?>"><?=$label?></span></td>
            <td style="color:rgba(255,255,255,.3);font-size:.75rem;"><?=date('d/m/y', strtotime($o['date_commande']))?></td>
          </tr>
          <?php endforeach; else: ?>
          <tr class="empty-row"><td colspan="4">Aucune commande pour le moment</td></tr>
          <?php endif; ?>
        </table>
      </div>

      <!-- Stock faible -->
      <div class="panel">
        <div class="panel-head">
          <h3>Stock faible <i class="fas fa-exclamation-triangle" style="color:var(--red);font-size:.9rem;"></i></h3>
          <a href="produits.php" class="panel-link">Gérer →</a>
        </div>
        <table class="admin-table">
          <tr><th>Produit</th><th>Marque</th><th>Stock</th></tr>
          <?php if($lowStock): foreach($lowStock as $p): ?>
          <tr>
            <td><?=htmlspecialchars($p['nom'])?></td>
            <td style="color:rgba(255,255,255,.3);font-size:.75rem;"><?=htmlspecialchars($p['marque']??'')?></td>
            <td class="<?=$p['stock']==0?'stock-low':($p['stock']<=2?'stock-warn':'stock-ok')?>" style="font-family:'Cinzel',serif;">
              <?=$p['stock']==0?'Épuisé':$p['stock'].' pcs'?>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr class="empty-row"><td colspan="3">Tous les stocks sont OK ✦</td></tr>
          <?php endif; ?>
        </table>
      </div>

    </div>
  </div>
</div>

</body>
</html>