<?php
// ══════════════════════════════════════════
//  LUMOURA — Gestion des Avis Clients
//  admin/avis.php
// ══════════════════════════════════════════
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../pages/connexion.php'); exit();
}

$msg = ''; $msgType = '';

// ─── APPROBATION / REFUS ───
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    try {
        if ($action === 'approuver') {
            $pdo->prepare("UPDATE avis SET statut='approuve' WHERE id_avis=?")->execute([$id]);
            $msg = '✦ Avis approuvé et publié.'; $msgType = 'ok';
        } elseif ($action === 'refuser') {
            $pdo->prepare("UPDATE avis SET statut='refuse' WHERE id_avis=?")->execute([$id]);
            $msg = 'Avis refusé.'; $msgType = 'ok';
        } elseif ($action === 'supprimer') {
            $pdo->prepare("DELETE FROM avis WHERE id_avis=?")->execute([$id]);
            $msg = 'Avis supprimé.'; $msgType = 'ok';
        }
    } catch(Exception $e) {
        $msg = 'Erreur : '.$e->getMessage(); $msgType = 'error';
    }
}

// ─── FILTRE ───
$filtre = $_GET['filtre'] ?? 'tous';

// ─── CHARGEMENT AVIS ───
$avis = [];
try {
    $sql = "SELECT a.*, u.nom, u.prenom, p.nom AS nom_produit
            FROM avis a
            LEFT JOIN utilisateurs u ON a.id_utilisateur = u.id_utilisateur
            LEFT JOIN produits p ON a.id_produit = p.id_produit
            WHERE 1=1";
    if ($filtre === 'attente')  $sql .= " AND a.statut = 'attente'";
    if ($filtre === 'approuve') $sql .= " AND a.statut = 'approuve'";
    if ($filtre === 'refuse')   $sql .= " AND a.statut = 'refuse'";
    $sql .= " ORDER BY a.date_avis DESC";
    $avis = $pdo->query($sql)->fetchAll();
} catch(Exception $e) { $db_error = $e->getMessage(); }

// ─── STATS ───
$stats = ['tous' => 0, 'attente' => 0, 'approuve' => 0, 'refuse' => 0, 'note_moy' => 0];
try {
    $rows = $pdo->query("SELECT statut, COUNT(*) as n, AVG(note) as moy FROM avis GROUP BY statut")->fetchAll();
    $total_note = 0; $total_n = 0;
    foreach ($rows as $r) {
        $stats['tous'] += $r['n'];
        $stats[$r['statut']] = $r['n'];
        $total_note += $r['moy'] * $r['n'];
        $total_n    += $r['n'];
    }
    $stats['note_moy'] = $total_n > 0 ? round($total_note / $total_n, 1) : 0;
} catch(Exception $e) {}

function stars($note) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<i class="fas fa-star" style="color:'.($i <= $note ? '#D4A843' : 'rgba(255,255,255,.1)').'"></i>';
    }
    return $out;
}

function badgeAvis($s) {
    return match($s) {
        'approuve' => ['Approuvé',  'ok'],
        'refuse'   => ['Refusé',    'cancel'],
        default    => ['En attente','pend'],
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Avis clients — Admin Lumoura</title>
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
.page-content{padding:32px 40px;flex:1;}
.msg-bar{padding:14px 20px;margin-bottom:24px;font-size:.84rem;display:flex;align-items:center;gap:10px;}
.msg-bar.ok{background:rgba(39,174,96,.12);border-left:3px solid var(--green);color:#2ecc71;}
.msg-bar.error{background:rgba(192,57,43,.12);border-left:3px solid var(--red);color:#e74c3c;}

/* STATS STRIP */
.stats-strip{display:grid;grid-template-columns:repeat(4,1fr);gap:2px;background:rgba(255,255,255,.04);margin-bottom:24px;}
.stat-mini{background:var(--ink2);padding:18px 22px;text-decoration:none;display:block;transition:background .2s;}
.stat-mini:hover,.stat-mini.active{background:var(--ink3);}
.stat-mini.active{border-top:2px solid var(--g1);}
.stat-mini-num{font-family:'EB Garamond',serif;font-size:1.6rem;color:var(--g1);line-height:1;}
.stat-mini-label{font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.3);margin-top:5px;}

/* CARTES AVIS */
.avis-grid{display:flex;flex-direction:column;gap:2px;background:rgba(255,255,255,.04);}
.avis-card{background:var(--ink2);padding:22px 26px;display:grid;grid-template-columns:1fr auto;gap:20px;align-items:start;}
.avis-card:hover{background:var(--ink3);}
.avis-header{display:flex;align-items:center;gap:14px;margin-bottom:12px;flex-wrap:wrap;}
.avis-avatar{width:38px;height:38px;border-radius:50%;background:rgba(212,168,67,.1);border:1px solid rgba(212,168,67,.2);display:flex;align-items:center;justify-content:center;font-family:'Cinzel',serif;font-size:.7rem;color:var(--g1);flex-shrink:0;}
.avis-author{font-size:.88rem;color:rgba(255,255,255,.85);font-weight:500;}
.avis-date{font-size:.72rem;color:rgba(255,255,255,.3);}
.avis-produit{font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:1.5px;text-transform:uppercase;color:var(--g1);background:rgba(212,168,67,.08);padding:2px 10px;}
.avis-stars{font-size:.78rem;letter-spacing:2px;}
.avis-commentaire{font-family:'EB Garamond',serif;font-size:1rem;color:rgba(255,255,255,.65);line-height:1.6;font-style:italic;margin-top:8px;}
.badge{display:inline-block;padding:3px 10px;font-family:'Cinzel',serif;font-size:.48rem;letter-spacing:1.5px;text-transform:uppercase;}
.badge.pend{background:rgba(212,168,67,.15);color:var(--g1);}
.badge.ok{background:rgba(39,174,96,.15);color:#2ecc71;}
.badge.cancel{background:rgba(192,57,43,.15);color:var(--red);}

/* ACTIONS */
.avis-actions{display:flex;flex-direction:column;gap:6px;min-width:120px;}
.btn-action{display:flex;align-items:center;justify-content:center;gap:6px;padding:8px 14px;border:none;cursor:pointer;font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:1.5px;text-transform:uppercase;text-decoration:none;transition:all .2s;white-space:nowrap;}
.btn-action.approve{background:rgba(39,174,96,.12);color:#2ecc71;border:1px solid rgba(39,174,96,.2);}
.btn-action.approve:hover{background:rgba(39,174,96,.25);}
.btn-action.refuse{background:rgba(212,168,67,.08);color:var(--g1);border:1px solid rgba(212,168,67,.15);}
.btn-action.refuse:hover{background:rgba(212,168,67,.18);}
.btn-action.del{background:rgba(192,57,43,.08);color:var(--red);border:1px solid rgba(192,57,43,.15);}
.btn-action.del:hover{background:rgba(192,57,43,.2);}

.empty-state{text-align:center;padding:60px 20px;color:rgba(255,255,255,.2);font-family:'EB Garamond',serif;font-size:1.2rem;background:var(--ink2);}
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
    <a href="avis.php" class="nav-link active"><i class="fas fa-star"></i> Avis clients</a>
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
    <span class="topbar-title">Avis clients — Note moyenne : <?=$stats['note_moy']?>/5</span>
    <div class="topbar-admin">
      <div class="topbar-avatar"><?=strtoupper(mb_substr($_SESSION['prenom'] ?? $_SESSION['user_nom'] ?? 'A', 0, 1))?></div>
      <?=htmlspecialchars($_SESSION['prenom'] ?? $_SESSION['user_nom'] ?? 'Admin')?>
    </div>
  </div>

  <div class="page-content">
    <?php if($msg): ?>
      <div class="msg-bar <?=$msgType?>"><i class="fas <?=$msgType==='ok'?'fa-check-circle':'fa-exclamation-circle'?>"></i> <?=htmlspecialchars($msg)?></div>
    <?php endif; ?>
    <?php if(isset($db_error)): ?>
      <div class="msg-bar error"><i class="fas fa-exclamation-triangle"></i> Erreur DB : <?=htmlspecialchars($db_error)?></div>
    <?php endif; ?>

    <!-- STATS / FILTRES -->
    <div class="stats-strip">
      <a href="avis.php?filtre=tous" class="stat-mini <?=$filtre==='tous'?'active':''?>">
        <div class="stat-mini-num"><?=$stats['tous']?></div>
        <div class="stat-mini-label">Tous les avis</div>
      </a>
      <a href="avis.php?filtre=attente" class="stat-mini <?=$filtre==='attente'?'active':''?>">
        <div class="stat-mini-num" style="color:var(--g1);"><?=$stats['attente']?></div>
        <div class="stat-mini-label">En attente</div>
      </a>
      <a href="avis.php?filtre=approuve" class="stat-mini <?=$filtre==='approuve'?'active':''?>">
        <div class="stat-mini-num" style="color:#2ecc71;"><?=$stats['approuve']?></div>
        <div class="stat-mini-label">Approuvés</div>
      </a>
      <a href="avis.php?filtre=refuse" class="stat-mini <?=$filtre==='refuse'?'active':''?>">
        <div class="stat-mini-num" style="color:var(--red);"><?=$stats['refuse']?></div>
        <div class="stat-mini-label">Refusés</div>
      </a>
    </div>

    <!-- LISTE AVIS -->
    <?php if($avis): ?>
    <div class="avis-grid">
      <?php foreach($avis as $a):
        [$blabel, $bcls] = badgeAvis($a['statut'] ?? 'attente');
        $initiales = strtoupper(mb_substr($a['prenom']??'?',0,1).mb_substr($a['nom']??'',0,1));
      ?>
      <div class="avis-card">
        <div>
          <div class="avis-header">
            <div class="avis-avatar"><?=$initiales?></div>
            <div>
              <div class="avis-author"><?=htmlspecialchars(($a['prenom']??'').' '.($a['nom']??'Anonyme'))?></div>
              <div class="avis-date"><?=!empty($a['date_avis'])?date('d/m/Y à H:i',strtotime($a['date_avis'])):'—'?></div>
            </div>
            <?php if(!empty($a['nom_produit'])): ?>
              <span class="avis-produit"><?=htmlspecialchars($a['nom_produit'])?></span>
            <?php endif; ?>
            <span class="badge <?=$bcls?>"><?=$blabel?></span>
          </div>

          <div class="avis-stars"><?=stars($a['note']??0)?> <span style="font-size:.75rem;color:rgba(255,255,255,.35);margin-left:6px;"><?=$a['note']?>/5</span></div>

          <?php if(!empty($a['commentaire'])): ?>
            <div class="avis-commentaire">"<?=htmlspecialchars($a['commentaire'])?>"</div>
          <?php endif; ?>
        </div>

        <div class="avis-actions">
          <?php if(($a['statut']??'') !== 'approuve'): ?>
            <a href="avis.php?action=approuver&id=<?=$a['id_avis']?>&filtre=<?=$filtre?>" class="btn-action approve"><i class="fas fa-check"></i> Approuver</a>
          <?php endif; ?>
          <?php if(($a['statut']??'') !== 'refuse'): ?>
            <a href="avis.php?action=refuser&id=<?=$a['id_avis']?>&filtre=<?=$filtre?>" class="btn-action refuse"><i class="fas fa-ban"></i> Refuser</a>
          <?php endif; ?>
          <a href="avis.php?action=supprimer&id=<?=$a['id_avis']?>&filtre=<?=$filtre?>" class="btn-action del" onclick="return confirm('Supprimer définitivement cet avis ?')"><i class="fas fa-trash"></i> Supprimer</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="empty-state">
        <?php if($filtre !== 'tous'): ?>Aucun avis dans cette catégorie.<?php else: ?>Aucun avis pour le moment.<?php endif; ?>
      </div>
    <?php endif; ?>

  </div>
</div>
</body>
</html>