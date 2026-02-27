<?php
// ══════════════════════════════════════════
//  LUMOURA — Gestion des Clients
//  admin/clients.php
// ══════════════════════════════════════════
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../pages/connexion.php'); exit();
}

$msg = ''; $msgType = '';

// ─── SUPPRESSION ───
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur=? AND role != 'admin'")->execute([$id]);
        $msg = 'Client supprimé.'; $msgType = 'ok';
    } catch(Exception $e) {
        $msg = 'Erreur suppression : '.$e->getMessage(); $msgType = 'error';
    }
}

// ─── FILTRES & RECHERCHE ───
$search_q  = trim($_GET['q'] ?? '');
$detail_id = isset($_GET['detail']) ? intval($_GET['detail']) : null;

// ─── CHARGEMENT CLIENTS ───
$clients = [];
try {
    $sql = "SELECT u.*,
                COUNT(DISTINCT c.id_commande) AS nb_commandes,
                COALESCE(SUM(c.montant), 0) AS total_depense
            FROM utilisateurs u
            LEFT JOIN commandes c ON c.id_utilisateur = u.id_utilisateur
            WHERE u.role != 'admin'";
    $params = [];
    if ($search_q) {
        $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search_q%"; $params[] = "%$search_q%"; $params[] = "%$search_q%";
    }
    $sql .= " GROUP BY u.id_utilisateur ORDER BY total_depense DESC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $clients = $st->fetchAll();
} catch(Exception $e) {
    $db_error = $e->getMessage();
}

// ─── STATS ───
$nbClients = count($clients);
$nbActifs  = count(array_filter($clients, fn($c) => $c['nb_commandes'] > 0));
$topCA     = $clients[0]['total_depense'] ?? 0;

// ─── DÉTAIL CLIENT ───
$detail = null;
$detail_cmds = [];
if ($detail_id) {
    try {
        $st = $pdo->prepare("SELECT u.*,
                COUNT(DISTINCT c.id_commande) AS nb_commandes,
                COALESCE(SUM(c.montant), 0) AS total_depense
            FROM utilisateurs u
            LEFT JOIN commandes c ON c.id_utilisateur = u.id_utilisateur
            WHERE u.id_utilisateur = ?
            GROUP BY u.id_utilisateur");
        $st->execute([$detail_id]);
        $detail = $st->fetch();

        $st2 = $pdo->prepare("SELECT * FROM commandes WHERE id_utilisateur = ? ORDER BY date_commande DESC LIMIT 10");
        $st2->execute([$detail_id]);
        $detail_cmds = $st2->fetchAll();
    } catch(Exception $e) { $detail = null; }
}

function badgeStatut($s) {
    $s = strtolower(trim($s));
    $map = [
        'en attente' => ['En attente','pend'],
        'payee'      => ['Payée','ok'],
        'payée'      => ['Payée','ok'],
        'expediee'   => ['Expédiée','ship'],
        'expédiée'   => ['Expédiée','ship'],
        'livree'     => ['Livrée','done'],
        'livrée'     => ['Livrée','done'],
        'annulee'    => ['Annulée','cancel'],
        'annulée'    => ['Annulée','cancel'],
    ];
    return $map[$s] ?? [ucfirst($s),'pend'];
}

function initiales($prenom, $nom) {
    return strtoupper(mb_substr($prenom,0,1).mb_substr($nom,0,1));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Clients — Admin Lumoura</title>
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

/* STATS STRIP */
.stats-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:2px;background:rgba(255,255,255,.04);margin-bottom:28px;}
.stat-mini{background:var(--ink2);padding:20px 24px;}
.stat-mini-num{font-family:'EB Garamond',serif;font-size:1.8rem;color:var(--g1);line-height:1;}
.stat-mini-label{font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.3);margin-top:6px;}

/* TOOLBAR */
.list-toolbar{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
.search-wrap{position:relative;flex:1;min-width:200px;}
.search-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.25);font-size:.85rem;}
.search-input{width:100%;background:var(--ink2);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.8);padding:10px 14px 10px 38px;font-family:'Didact Gothic',sans-serif;font-size:.82rem;transition:border-color .25s;}
.search-input:focus{outline:none;border-color:rgba(212,168,67,.4);}
.search-input::placeholder{color:rgba(255,255,255,.2);}

/* TABLE */
.cli-table{width:100%;border-collapse:collapse;background:var(--ink2);}
.cli-table thead tr{border-bottom:1px solid rgba(255,255,255,.06);}
.cli-table th{font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.3);padding:14px 16px;text-align:left;}
.cli-table td{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.03);color:rgba(255,255,255,.7);font-size:.82rem;vertical-align:middle;}
.cli-table tr:last-child td{border-bottom:none;}
.cli-table tr:hover td{background:rgba(255,255,255,.02);}

/* Avatar initiales */
.cli-avatar{width:40px;height:40px;border-radius:50%;background:rgba(212,168,67,.1);border:1px solid rgba(212,168,67,.2);display:flex;align-items:center;justify-content:center;font-family:'Cinzel',serif;font-size:.72rem;color:var(--g1);flex-shrink:0;}

.tbl-actions{display:flex;gap:6px;}
.tbl-btn{width:32px;height:32px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.82rem;transition:all .2s;text-decoration:none;}
.tbl-btn.view{background:rgba(255,255,255,.06);color:rgba(255,255,255,.4);}
.tbl-btn.view:hover{color:#fff;background:rgba(255,255,255,.12);}
.tbl-btn.del{background:rgba(192,57,43,.1);color:var(--red);}
.tbl-btn.del:hover{background:rgba(192,57,43,.25);}

.vip-badge{display:inline-flex;align-items:center;gap:4px;background:rgba(212,168,67,.12);color:var(--g1);padding:2px 9px;font-family:'Cinzel',serif;font-size:.48rem;letter-spacing:1.5px;text-transform:uppercase;}

.empty-state{text-align:center;padding:60px 20px;color:rgba(255,255,255,.2);font-family:'EB Garamond',serif;font-size:1.2rem;}

/* ═══ PANEL DÉTAIL ═══ */
.detail-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;display:flex;align-items:flex-start;justify-content:flex-end;}
.detail-panel{background:var(--ink2);width:500px;max-width:95vw;height:100vh;overflow-y:auto;border-left:1px solid rgba(255,255,255,.06);display:flex;flex-direction:column;}
.detail-header{padding:24px 28px;border-bottom:1px solid rgba(255,255,255,.05);display:flex;align-items:center;gap:16px;position:sticky;top:0;background:var(--ink2);z-index:10;}
.detail-avatar-lg{width:52px;height:52px;border-radius:50%;background:rgba(212,168,67,.12);border:1px solid rgba(212,168,67,.25);display:flex;align-items:center;justify-content:center;font-family:'Cinzel',serif;font-size:1rem;color:var(--g1);flex-shrink:0;}
.detail-header-info{flex:1;}
.detail-header-name{font-family:'EB Garamond',serif;font-size:1.3rem;color:#fff;font-weight:400;}
.detail-header-email{font-size:.75rem;color:rgba(255,255,255,.35);margin-top:2px;}
.detail-close{width:36px;height:36px;background:rgba(255,255,255,.05);border:none;color:rgba(255,255,255,.4);cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;transition:all .2s;}
.detail-close:hover{background:rgba(255,255,255,.1);color:#fff;}
.detail-body{padding:28px;display:flex;flex-direction:column;gap:22px;}
.detail-section{background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);padding:18px 20px;}
.detail-section-title{font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:3px;text-transform:uppercase;color:var(--g1);margin-bottom:14px;}
.detail-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.03);}
.detail-row:last-child{border-bottom:none;}
.detail-row-label{font-size:.75rem;color:rgba(255,255,255,.35);}
.detail-row-val{font-size:.82rem;color:rgba(255,255,255,.8);}

.cmd-mini{padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04);display:flex;align-items:center;gap:12px;}
.cmd-mini:last-child{border-bottom:none;}
.cmd-mini-id{font-family:'Cinzel',serif;font-size:.6rem;color:rgba(255,255,255,.3);width:40px;flex-shrink:0;}
.cmd-mini-date{font-size:.72rem;color:rgba(255,255,255,.3);flex:1;}
.cmd-mini-montant{font-family:'EB Garamond',serif;font-size:1rem;color:var(--g1);}

.badge{display:inline-block;padding:3px 10px;font-family:'Cinzel',serif;font-size:.48rem;letter-spacing:1.5px;text-transform:uppercase;}
.badge.pend{background:rgba(212,168,67,.15);color:var(--g1);}
.badge.ok{background:rgba(39,174,96,.15);color:#2ecc71;}
.badge.ship{background:rgba(52,152,219,.15);color:var(--blue);}
.badge.done{background:rgba(39,174,96,.25);color:#27ae60;}
.badge.cancel{background:rgba(192,57,43,.15);color:var(--red);}
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
    <a href="commandes.php" class="nav-link"><i class="fas fa-shopping-bag"></i> Commandes</a>
    <a href="clients.php" class="nav-link active"><i class="fas fa-users"></i> Clients</a>
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
    <span class="topbar-title">Clients (<?=$nbClients?>)</span>
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

    <!-- STATS -->
    <div class="stats-strip">
      <div class="stat-mini">
        <div class="stat-mini-num"><?=$nbClients?></div>
        <div class="stat-mini-label">Total clients</div>
      </div>
      <div class="stat-mini">
        <div class="stat-mini-num" style="color:#2ecc71;"><?=$nbActifs?></div>
        <div class="stat-mini-label">Clients actifs</div>
      </div>
      <div class="stat-mini">
        <div class="stat-mini-num"><?=number_format($topCA,0,',',' ')?>€</div>
        <div class="stat-mini-label">Meilleur client (CA)</div>
      </div>
    </div>

    <!-- TOOLBAR -->
    <form method="GET" action="">
      <div class="list-toolbar">
        <div class="search-wrap">
          <i class="fas fa-search"></i>
          <input type="text" name="q" class="search-input" placeholder="Rechercher par nom, prénom ou email..." value="<?=htmlspecialchars($search_q)?>">
        </div>
        <button type="submit" class="topbar-btn" style="height:40px;padding:0 18px;">Rechercher</button>
        <?php if($search_q): ?>
          <a href="clients.php" class="topbar-btn ghost" style="height:40px;padding:0 16px;">Réinitialiser</a>
        <?php endif; ?>
      </div>
    </form>

    <!-- TABLE -->
    <table class="cli-table">
      <thead>
        <tr>
          <th></th>
          <th>Client</th>
          <th>Email</th>
          <th>Téléphone</th>
          <th>Commandes</th>
          <th>Total dépensé</th>
          <th>Inscrit le</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if($clients): foreach($clients as $c): ?>
        <tr>
          <td style="width:52px;">
            <div class="cli-avatar"><?=initiales($c['prenom']??'', $c['nom']??'')?></div>
          </td>
          <td>
            <div style="color:rgba(255,255,255,.9);font-weight:500;"><?=htmlspecialchars($c['prenom'].' '.$c['nom'])?></div>
            <?php if($c['total_depense'] >= 3000): ?>
              <div style="margin-top:4px;"><span class="vip-badge"><i class="fas fa-crown"></i> VIP</span></div>
            <?php endif; ?>
          </td>
          <td style="color:rgba(255,255,255,.45);font-size:.78rem;"><?=htmlspecialchars($c['email'])?></td>
          <td style="color:rgba(255,255,255,.4);font-size:.78rem;"><?=htmlspecialchars($c['telephone']??'—')?></td>
          <td>
            <?php if($c['nb_commandes'] > 0): ?>
              <span style="font-family:'Cinzel',serif;font-size:.75rem;color:rgba(255,255,255,.7);"><?=$c['nb_commandes']?></span>
            <?php else: ?>
              <span style="color:rgba(255,255,255,.2);font-size:.75rem;">0</span>
            <?php endif; ?>
          </td>
          <td style="font-family:'EB Garamond',serif;font-size:1.05rem;color:<?=$c['total_depense']>0?'var(--g1)':'rgba(255,255,255,.2)'?>;">
            <?=$c['total_depense']>0 ? number_format($c['total_depense'],2,',',' ').'€' : '—'?>
          </td>
          <td style="color:rgba(255,255,255,.3);font-size:.75rem;">
            <?=!empty($c['date_inscription']) ? date('d/m/Y', strtotime($c['date_inscription'])) : '—'?>
          </td>
          <td>
            <div class="tbl-actions">
              <a href="clients.php?detail=<?=$c['id_utilisateur']?><?=$search_q?'&q='.urlencode($search_q):''?>" class="tbl-btn view" title="Voir le profil"><i class="fas fa-eye"></i></a>
              <a href="clients.php?action=supprimer&id=<?=$c['id_utilisateur']?>" class="tbl-btn del" title="Supprimer" onclick="return confirm('Supprimer ce client ? Ses commandes seront conservées.')"><i class="fas fa-trash"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="8" class="empty-state">Aucun client trouvé.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

  </div><!-- /page-content -->
</div><!-- /main -->

<!-- PANEL DÉTAIL CLIENT -->
<?php if($detail): ?>
<div class="detail-overlay" onclick="if(event.target===this) window.location='clients.php<?=$search_q?'?q='.urlencode($search_q):''?>';">
  <div class="detail-panel">
    <div class="detail-header">
      <div class="detail-avatar-lg"><?=initiales($detail['prenom']??'',$detail['nom']??'')?></div>
      <div class="detail-header-info">
        <div class="detail-header-name"><?=htmlspecialchars($detail['prenom'].' '.$detail['nom'])?></div>
        <div class="detail-header-email"><?=htmlspecialchars($detail['email'])?></div>
      </div>
      <a href="clients.php<?=$search_q?'?q='.urlencode($search_q):''?>" class="detail-close"><i class="fas fa-times"></i></a>
    </div>

    <div class="detail-body">

      <!-- Infos personnelles -->
      <div class="detail-section">
        <div class="detail-section-title"><i class="fas fa-user"></i> &nbsp;Informations</div>
        <div class="detail-row">
          <span class="detail-row-label">Nom complet</span>
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
        <?php if(!empty($detail['adresse'])): ?>
        <div class="detail-row">
          <span class="detail-row-label">Adresse</span>
          <span class="detail-row-val" style="max-width:280px;text-align:right;"><?=nl2br(htmlspecialchars($detail['adresse']))?></span>
        </div>
        <?php endif; ?>
        <?php if(!empty($detail['date_inscription'])): ?>
        <div class="detail-row">
          <span class="detail-row-label">Inscrit le</span>
          <span class="detail-row-val"><?=date('d/m/Y', strtotime($detail['date_inscription']))?></span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Résumé achats -->
      <div class="detail-section">
        <div class="detail-section-title"><i class="fas fa-chart-bar"></i> &nbsp;Résumé achats</div>
        <div class="detail-row">
          <span class="detail-row-label">Nombre de commandes</span>
          <span class="detail-row-val" style="font-family:'Cinzel',serif;"><?=$detail['nb_commandes']?></span>
        </div>
        <div class="detail-row">
          <span class="detail-row-label">Total dépensé</span>
          <span class="detail-row-val" style="font-family:'EB Garamond',serif;font-size:1.2rem;color:var(--g1);">
            <?=number_format($detail['total_depense'],2,',',' ')?>€
          </span>
        </div>
        <?php if($detail['nb_commandes'] > 0): ?>
        <div class="detail-row">
          <span class="detail-row-label">Panier moyen</span>
          <span class="detail-row-val" style="color:rgba(255,255,255,.6);">
            <?=number_format($detail['total_depense']/$detail['nb_commandes'],2,',',' ')?>€
          </span>
        </div>
        <?php endif; ?>
        <?php if($detail['total_depense'] >= 3000): ?>
        <div style="margin-top:12px;">
          <span class="vip-badge" style="font-size:.55rem;padding:4px 12px;"><i class="fas fa-crown"></i> &nbsp;Client VIP — dépasse 3 000€</span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Historique commandes -->
      <?php if($detail_cmds): ?>
      <div class="detail-section">
        <div class="detail-section-title"><i class="fas fa-history"></i> &nbsp;Dernières commandes</div>
        <?php foreach($detail_cmds as $cmd):
          [$label,$cls] = badgeStatut($cmd['statut']??'');
        ?>
        <div class="cmd-mini">
          <span class="cmd-mini-id">#<?=$cmd['id_commande']?></span>
          <span class="cmd-mini-date"><?=date('d/m/Y', strtotime($cmd['date_commande']))?></span>
          <span class="badge <?=$cls?>"><?=$label?></span>
          <span class="cmd-mini-montant"><?=number_format($cmd['montant'],2,',',' ')?>€</span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="detail-section" style="text-align:center;color:rgba(255,255,255,.2);font-family:'EB Garamond',serif;padding:28px 20px;">
        Aucune commande pour ce client.
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
<?php endif; ?>

</body>
</html>