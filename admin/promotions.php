<?php
// ══════════════════════════════════════════
//  LUMOURA — Gestion des Promotions
//  admin/promotions.php
// ══════════════════════════════════════════
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../pages/connexion.php'); exit();
}

$msg = ''; $msgType = '';

// ─── APPLIQUER PROMO À UN PRODUIT ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) {
    if ($_POST['form_action'] === 'appliquer') {
        $id_produit = intval($_POST['id_produit'] ?? 0);
        $pourcent   = min(100, max(0, floatval($_POST['promotion_pourcentage'] ?? 0)));
        if ($id_produit) {
            try {
                $pdo->prepare("UPDATE produits SET promotion_pourcentage=? WHERE id_produit=?")
                    ->execute([$pourcent, $id_produit]);
                $msg = '✦ Promotion appliquée !'; $msgType = 'ok';
            } catch(Exception $e) { $msg = 'Erreur : '.$e->getMessage(); $msgType = 'error'; }
        }
    } elseif ($_POST['form_action'] === 'masse') {
        // Appliquer à toute une catégorie
        $id_cat   = intval($_POST['id_categorie'] ?? 0);
        $pourcent = min(100, max(0, floatval($_POST['promotion_masse'] ?? 0)));
        if ($id_cat) {
            try {
                $pdo->prepare("UPDATE produits SET promotion_pourcentage=? WHERE id_categorie=?")
                    ->execute([$pourcent, $id_cat]);
                $nb = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE id_categorie=?");
                $nb->execute([$id_cat]); 
                $msg = '✦ Promotion de '.$pourcent.'% appliquée à la catégorie !'; $msgType = 'ok';
            } catch(Exception $e) { $msg = 'Erreur : '.$e->getMessage(); $msgType = 'error'; }
        }
    } elseif ($_POST['form_action'] === 'reset_cat') {
        $id_cat = intval($_POST['id_categorie_reset'] ?? 0);
        if ($id_cat) {
            try {
                $pdo->prepare("UPDATE produits SET promotion_pourcentage=0 WHERE id_categorie=?")->execute([$id_cat]);
                $msg = 'Promotions de la catégorie réinitialisées.'; $msgType = 'ok';
            } catch(Exception $e) { $msg = 'Erreur.'; $msgType = 'error'; }
        }
    } elseif ($_POST['form_action'] === 'reset_all') {
        try {
            $pdo->query("UPDATE produits SET promotion_pourcentage=0");
            $msg = 'Toutes les promotions ont été supprimées.'; $msgType = 'ok';
        } catch(Exception $e) { $msg = 'Erreur.'; $msgType = 'error'; }
    }
}

// ─── RESET RAPIDE PRODUIT ───
if (isset($_GET['reset']) && intval($_GET['reset'])) {
    try {
        $pdo->prepare("UPDATE produits SET promotion_pourcentage=0 WHERE id_produit=?")->execute([intval($_GET['reset'])]);
        $msg = 'Promotion supprimée.'; $msgType = 'ok';
    } catch(Exception $e) {}
}

// ─── DONNÉES ───
$produits    = [];
$categories  = [];
$en_promo    = [];
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY nom_categorie")->fetchAll();
    $produits   = $pdo->query("SELECT p.*, c.nom_categorie FROM produits p LEFT JOIN categories c ON p.id_categorie=c.id_categorie ORDER BY p.promotion_pourcentage DESC, p.nom")->fetchAll();
    $en_promo   = array_filter($produits, fn($p) => $p['promotion_pourcentage'] > 0);
} catch(Exception $e) { $db_error = $e->getMessage(); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Promotions — Admin Lumoura</title>
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

/* LAYOUT */
.promo-layout{display:grid;grid-template-columns:1fr 340px;gap:2px;background:rgba(255,255,255,.04);align-items:start;}
.panel{background:var(--ink2);padding:28px;}
.panel-title{font-family:'Cinzel',serif;font-size:.55rem;letter-spacing:3px;text-transform:uppercase;color:var(--g1);padding-bottom:14px;margin-bottom:20px;border-bottom:1px solid rgba(255,255,255,.05);display:flex;align-items:center;gap:10px;}

/* TABLE PRODUITS */
.promo-table{width:100%;border-collapse:collapse;}
.promo-table th{font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.3);padding:10px 12px;text-align:left;border-bottom:1px solid rgba(255,255,255,.05);}
.promo-table td{padding:11px 12px;border-bottom:1px solid rgba(255,255,255,.03);color:rgba(255,255,255,.65);font-size:.8rem;vertical-align:middle;}
.promo-table tr:last-child td{border-bottom:none;}
.promo-table tr:hover td{background:rgba(255,255,255,.02);}

.promo-badge{display:inline-block;padding:3px 10px;font-family:'Cinzel',serif;font-size:.55rem;letter-spacing:1.5px;color:var(--red);background:rgba(192,57,43,.12);}
.no-promo{color:rgba(255,255,255,.2);font-size:.75rem;}

/* FORMULAIRE INLINE */
.inline-form{display:flex;align-items:center;gap:6px;}
.pct-input{width:64px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.8);padding:6px 10px;font-size:.8rem;font-family:'Didact Gothic',sans-serif;text-align:center;}
.pct-input:focus{outline:none;border-color:rgba(212,168,67,.4);}
.btn-apply{background:rgba(212,168,67,.12);border:1px solid rgba(212,168,67,.2);color:var(--g1);padding:6px 12px;font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;transition:all .2s;}
.btn-apply:hover{background:rgba(212,168,67,.25);}
.btn-reset-sm{background:rgba(192,57,43,.08);border:none;color:var(--red);width:28px;height:28px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.75rem;transition:all .2s;text-decoration:none;}
.btn-reset-sm:hover{background:rgba(192,57,43,.2);}

/* SIDEBAR FORMULAIRES */
.field{display:flex;flex-direction:column;gap:7px;margin-bottom:16px;}
.field label{font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.35);}
.field input,.field select{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);color:rgba(255,255,255,.8);padding:10px 12px;font-size:.82rem;font-family:'Didact Gothic',sans-serif;width:100%;}
.field input:focus,.field select:focus{outline:none;border-color:rgba(212,168,67,.4);}
.field select option{background:var(--ink2);}
.btn-full{width:100%;background:var(--g1);color:var(--ink);border:none;padding:12px;font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2.5px;text-transform:uppercase;cursor:pointer;transition:background .25s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:4px;}
.btn-full:hover{background:var(--g2);}
.btn-danger{width:100%;background:rgba(192,57,43,.1);color:var(--red);border:1px solid rgba(192,57,43,.2);padding:11px;font-family:'Cinzel',serif;font-size:.55rem;letter-spacing:2px;text-transform:uppercase;cursor:pointer;transition:all .2s;margin-top:8px;}
.btn-danger:hover{background:rgba(192,57,43,.25);}
.divider{height:1px;background:rgba(255,255,255,.05);margin:20px 0;}

.stat-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.stat-row:last-child{border-bottom:none;}
.stat-row-label{font-size:.75rem;color:rgba(255,255,255,.35);}
.stat-row-val{font-family:'EB Garamond',serif;font-size:1.05rem;color:var(--g1);}
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
    <a href="promotions.php" class="nav-link active"><i class="fas fa-percent"></i> Promotions</a>
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
    <span class="topbar-title">Promotions — <?=count($en_promo)?> produit<?=count($en_promo)!=1?'s':''?> en promo</span>
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

    <div class="promo-layout">

      <!-- TABLE PRODUITS -->
      <div class="panel">
        <div class="panel-title"><i class="fas fa-gem"></i> Tous les produits</div>
        <table class="promo-table">
          <thead>
            <tr>
              <th>Produit</th>
              <th>Catégorie</th>
              <th>Prix</th>
              <th>Promo actuelle</th>
              <th>Modifier</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($produits as $p): ?>
            <tr>
              <td style="font-family:'EB Garamond',serif;font-size:.95rem;color:rgba(255,255,255,.9);"><?=htmlspecialchars($p['nom'])?></td>
              <td style="color:rgba(255,255,255,.3);font-size:.75rem;"><?=htmlspecialchars($p['nom_categorie']??'—')?></td>
              <td style="font-family:'EB Garamond',serif;color:var(--g1);"><?=number_format($p['prix'],2,',',' ')?>€</td>
              <td>
                <?php if($p['promotion_pourcentage'] > 0): ?>
                  <span class="promo-badge">−<?=$p['promotion_pourcentage']?>%</span>
                <?php else: ?>
                  <span class="no-promo">—</span>
                <?php endif; ?>
              </td>
              <td>
                <form method="POST" action="" class="inline-form">
                  <input type="hidden" name="form_action" value="appliquer">
                  <input type="hidden" name="id_produit" value="<?=$p['id_produit']?>">
                  <input type="number" name="promotion_pourcentage" class="pct-input" value="<?=$p['promotion_pourcentage']?>" min="0" max="100" placeholder="0">
                  <span style="font-size:.7rem;color:rgba(255,255,255,.3);">%</span>
                  <button type="submit" class="btn-apply">OK</button>
                  <?php if($p['promotion_pourcentage'] > 0): ?>
                    <a href="promotions.php?reset=<?=$p['id_produit']?>" class="btn-reset-sm" title="Supprimer la promo"><i class="fas fa-times"></i></a>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- PANNEAU DROITE -->
      <div style="display:flex;flex-direction:column;gap:2px;background:rgba(255,255,255,.04);">

        <!-- Stats -->
        <div class="panel">
          <div class="panel-title"><i class="fas fa-chart-pie"></i> Résumé</div>
          <div class="stat-row">
            <span class="stat-row-label">Produits en promotion</span>
            <span class="stat-row-val"><?=count($en_promo)?></span>
          </div>
          <div class="stat-row">
            <span class="stat-row-label">Total produits</span>
            <span class="stat-row-val"><?=count($produits)?></span>
          </div>
          <?php if(count($en_promo)>0): 
            $moy = array_sum(array_column(iterator_to_array((function() use($en_promo){ foreach($en_promo as $p) yield $p; })()), 'promotion_pourcentage')) / count($en_promo);
          ?>
          <div class="stat-row">
            <span class="stat-row-label">Remise moyenne</span>
            <span class="stat-row-val"><?=round($moy)?>%</span>
          </div>
          <?php endif; ?>
        </div>

        <!-- Promo par catégorie -->
        <div class="panel">
          <div class="panel-title"><i class="fas fa-layer-group"></i> Par catégorie</div>
          <form method="POST" action="">
            <input type="hidden" name="form_action" value="masse">
            <div class="field">
              <label>Catégorie</label>
              <select name="id_categorie">
                <option value="">— Choisir —</option>
                <?php foreach($categories as $c): ?>
                  <option value="<?=$c['id_categorie']?>"><?=htmlspecialchars($c['nom_categorie'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label>Remise (%)</label>
              <input type="number" name="promotion_masse" min="0" max="100" placeholder="Ex: 20" value="">
            </div>
            <button type="submit" class="btn-full"><i class="fas fa-bolt"></i> Appliquer à la catégorie</button>
          </form>

          <div class="divider"></div>

          <form method="POST" action="">
            <input type="hidden" name="form_action" value="reset_cat">
            <div class="field">
              <label>Réinitialiser une catégorie</label>
              <select name="id_categorie_reset">
                <option value="">— Choisir —</option>
                <?php foreach($categories as $c): ?>
                  <option value="<?=$c['id_categorie']?>"><?=htmlspecialchars($c['nom_categorie'])?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn-danger" onclick="return confirm('Supprimer toutes les promos de cette catégorie ?')">
              <i class="fas fa-eraser"></i> Retirer les promos
            </button>
          </form>
        </div>

        <!-- Reset global -->
        <div class="panel">
          <div class="panel-title"><i class="fas fa-exclamation-triangle" style="color:var(--red)"></i> Zone dangereuse</div>
          <p style="font-size:.78rem;color:rgba(255,255,255,.3);margin-bottom:16px;">Supprimer toutes les promotions en cours sur l'ensemble du catalogue.</p>
          <form method="POST" action="">
            <input type="hidden" name="form_action" value="reset_all">
            <button type="submit" class="btn-danger" onclick="return confirm('Supprimer TOUTES les promotions du catalogue ?')">
              <i class="fas fa-trash"></i> Tout réinitialiser
            </button>
          </form>
        </div>

      </div>
    </div>
  </div>
</div>
</body>
</html>