<?php
// ══════════════════════════════════════════
//  LUMOURA — Gestion des Produits
//  admin/produits.php
// ══════════════════════════════════════════
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../pages/connexion.php'); exit();
}

$action  = $_GET['action'] ?? 'liste';
$edit_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$msg = ''; $msgType = '';

// ─── TRAITEMENT FORMULAIRE ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom         = trim($_POST['nom'] ?? '');
    $marque      = trim($_POST['marque'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $desc_courte = trim($_POST['description_courte'] ?? '');
    $prix        = floatval($_POST['prix'] ?? 0);
    $stock       = intval($_POST['stock'] ?? 0);
    $matiere     = trim($_POST['matiere'] ?? '');
    $pierre      = trim($_POST['pierre'] ?? '');
    $taille      = trim($_POST['taille'] ?? '');
    $genre       = $_POST['genre'] ?? 'Femme';
    $id_cat      = intval($_POST['id_categorie'] ?? 0);
    $promo       = floatval($_POST['promotion_pourcentage'] ?? 0);
    $nouveaute   = isset($_POST['nouveaute']) ? 1 : 0;
    $bestseller  = isset($_POST['bestseller']) ? 1 : 0;
    $image_url   = trim($_POST['image_url'] ?? '');

    // Upload image si fichier fourni
    if (!empty($_FILES['image_file']['name'])) {
        $uploadDir = '../uploads/produits/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext  = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed)) {
            $newName  = 'prod_' . uniqid() . '.' . $ext;
            $destPath = $uploadDir . $newName;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $destPath)) {
                $image_url = '../uploads/produits/' . $newName;
            }
        }
    }

    if (empty($nom) || $prix <= 0) {
        $msg = 'Le nom et le prix sont obligatoires.'; $msgType = 'error';
    } else {
        try {
            if ($_POST['form_action'] === 'ajouter') {
                $stmt = $pdo->prepare("INSERT INTO produits (nom, marque, description, description_courte, prix, stock, matiere, pierre, taille, genre, id_categorie, promotion_pourcentage, nouveaute, bestseller, image_url) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$nom,$marque,$description,$desc_courte,$prix,$stock,$matiere,$pierre,$taille,$genre,$id_cat?:null,$promo,$nouveaute,$bestseller,$image_url]);
                $msg = '✦ Produit "'.$nom.'" ajouté avec succès !'; $msgType = 'ok';
                $action = 'liste';
            } elseif ($_POST['form_action'] === 'modifier' && $edit_id) {
                $stmt = $pdo->prepare("UPDATE produits SET nom=?,marque=?,description=?,description_courte=?,prix=?,stock=?,matiere=?,pierre=?,taille=?,genre=?,id_categorie=?,promotion_pourcentage=?,nouveaute=?,bestseller=?,image_url=? WHERE id_produit=?");
                $stmt->execute([$nom,$marque,$description,$desc_courte,$prix,$stock,$matiere,$pierre,$taille,$genre,$id_cat?:null,$promo,$nouveaute,$bestseller,$image_url,$edit_id]);
                $msg = '✦ Produit mis à jour avec succès !'; $msgType = 'ok';
                $action = 'liste';
            }
        } catch(Exception $e) {
            $msg = 'Erreur : '.$e->getMessage(); $msgType = 'error';
        }
    }
}

// ─── SUPPRESSION ───
if ($action === 'supprimer' && $edit_id) {
    try {
        $pdo->prepare("DELETE FROM produits WHERE id_produit = ?")->execute([$edit_id]);
        $msg = 'Produit supprimé.'; $msgType = 'ok'; $action = 'liste';
    } catch(Exception $e) {
        $msg = 'Erreur suppression.'; $msgType = 'error'; $action = 'liste';
    }
}

// ─── CHARGEMENT DONNÉES ───
$categories = [];
try { $categories = $pdo->query("SELECT * FROM categories ORDER BY nom_categorie")->fetchAll(); } catch(Exception $e){}

$produits = [];
$search_q = trim($_GET['q'] ?? '');
if ($action === 'liste') {
    try {
        $sql = "SELECT p.*, c.nom_categorie FROM produits p LEFT JOIN categories c ON p.id_categorie = c.id_categorie";
        if ($search_q) { $sql .= " WHERE p.nom LIKE ? OR p.marque LIKE ?"; $params = ["%$search_q%","%$search_q%"]; }
        $sql .= " ORDER BY p.id_produit DESC";
        $st = $pdo->prepare($sql);
        $search_q ? $st->execute($params) : $st->execute();
        $produits = $st->fetchAll();
    } catch(Exception $e){}
}

$edit_prod = null;
if (($action === 'modifier') && $edit_id) {
    try { $st=$pdo->prepare("SELECT * FROM produits WHERE id_produit=?"); $st->execute([$edit_id]); $edit_prod=$st->fetch(); } catch(Exception $e){}
    if (!$edit_prod) { $action='liste'; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Produits — Admin Lumoura</title>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Cinzel:wght@400;600;700&family=Didact+Gothic&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--g1:#D4A843;--g2:#F5D78E;--g3:#B8882C;--ink:#0D0A06;--ink2:#1A140E;--ink3:#241C12;--smoke:#F8F5EF;--stone:#E8E0D0;--muted:#8A7D6A;--red:#C0392B;--green:#27AE60;--sidebar:240px;}
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
.nav-link i{width:18px;font-size:.9rem;}
.nav-link:hover{color:rgba(255,255,255,.85);background:rgba(255,255,255,.04);}
.nav-link.active{color:var(--g1);background:rgba(212,168,67,.08);}
.nav-link.active::before{content:'';position:absolute;left:0;top:0;bottom:0;width:2px;background:var(--g1);}
.sidebar-bottom{margin-top:auto;padding:16px 12px;border-top:1px solid rgba(255,255,255,.04);}
.nav-link.danger:hover{color:var(--red);}

/* MAIN */
.main{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;}
.topbar{background:var(--ink2);border-bottom:1px solid rgba(255,255,255,.04);padding:0 40px;height:62px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:50;}
.topbar-title{font-family:'EB Garamond',serif;font-size:1.5rem;color:#fff;font-weight:400;flex:1;}
.topbar-btn{display:flex;align-items:center;gap:7px;background:var(--g1);color:var(--ink);padding:9px 18px;border:none;cursor:pointer;font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2px;text-transform:uppercase;text-decoration:none;transition:background .25s;}
.topbar-btn:hover{background:var(--g2);}
.topbar-btn.ghost{background:transparent;border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.5);}
.topbar-btn.ghost:hover{background:rgba(255,255,255,.05);color:#fff;}

.page-content{padding:32px 40px;flex:1;}

/* MSG */
.msg-bar{padding:14px 20px;margin-bottom:28px;font-size:.84rem;display:flex;align-items:center;gap:10px;}
.msg-bar.ok{background:rgba(39,174,96,.12);border-left:3px solid var(--green);color:#2ecc71;}
.msg-bar.error{background:rgba(192,57,43,.12);border-left:3px solid var(--red);color:#e74c3c;}

/* TOOLBAR LISTE */
.list-toolbar{display:flex;align-items:center;gap:12px;margin-bottom:22px;flex-wrap:wrap;}
.search-wrap{position:relative;flex:1;min-width:200px;}
.search-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.25);font-size:.85rem;}
.search-input{width:100%;background:var(--ink2);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.8);padding:10px 14px 10px 38px;font-family:'Didact Gothic',sans-serif;font-size:.82rem;transition:border-color .25s;}
.search-input:focus{outline:none;border-color:rgba(212,168,67,.4);}
.search-input::placeholder{color:rgba(255,255,255,.2);}
.filter-select{background:var(--ink2);border:1px solid rgba(255,255,255,.08);color:rgba(255,255,255,.6);padding:10px 14px;font-size:.82rem;}
.filter-select:focus{outline:none;}

/* TABLE PRODUITS */
.prod-table{width:100%;border-collapse:collapse;background:var(--ink2);}
.prod-table thead tr{border-bottom:1px solid rgba(255,255,255,.06);}
.prod-table th{font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.3);padding:14px 16px;text-align:left;}
.prod-table td{padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.03);color:rgba(255,255,255,.7);font-size:.82rem;vertical-align:middle;}
.prod-table tr:last-child td{border-bottom:none;}
.prod-table tr:hover td{background:rgba(255,255,255,.02);}

.prod-thumb{width:48px;height:48px;object-fit:cover;border:1px solid rgba(255,255,255,.06);}
.prod-name-cell{font-family:'EB Garamond',serif;font-size:1rem;color:rgba(255,255,255,.9);}
.prod-sub{font-size:.72rem;color:rgba(255,255,255,.3);margin-top:2px;}

.stock-pill{display:inline-block;padding:3px 9px;font-size:.68rem;font-family:'Cinzel',serif;letter-spacing:1px;}
.stock-pill.low{background:rgba(192,57,43,.15);color:#e74c3c;}
.stock-pill.warn{background:rgba(243,156,18,.12);color:#F39C12;}
.stock-pill.ok{background:rgba(39,174,96,.1);color:var(--green);}

.tbl-actions{display:flex;gap:6px;}
.tbl-btn{width:32px;height:32px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.82rem;transition:all .2s;}
.tbl-btn.edit{background:rgba(212,168,67,.1);color:var(--g1);}
.tbl-btn.edit:hover{background:rgba(212,168,67,.25);}
.tbl-btn.del{background:rgba(192,57,43,.1);color:var(--red);}
.tbl-btn.del:hover{background:rgba(192,57,43,.25);}
.tbl-btn.view{background:rgba(255,255,255,.06);color:rgba(255,255,255,.4);}
.tbl-btn.view:hover{color:#fff;background:rgba(255,255,255,.1);}

.empty-state{text-align:center;padding:60px 20px;color:rgba(255,255,255,.2);font-family:'EB Garamond',serif;font-size:1.2rem;}

/* ═══ FORMULAIRE PRODUIT ═══ */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;}
.form-col{display:flex;flex-direction:column;gap:20px;}
.form-full{grid-column:1/-1;}

.form-panel{background:var(--ink2);padding:28px;}
.form-panel-title{
  font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:4px;
  text-transform:uppercase;color:var(--g1);
  padding-bottom:14px;margin-bottom:20px;
  border-bottom:1px solid rgba(255,255,255,.05);
  display:flex;align-items:center;gap:10px;
}

.field{display:flex;flex-direction:column;gap:7px;}
.field label{font-family:'Cinzel',serif;font-size:.56rem;letter-spacing:2.5px;text-transform:uppercase;color:rgba(255,255,255,.4);}
.field input,.field select,.field textarea{
  background:rgba(255,255,255,.04);
  border:1px solid rgba(255,255,255,.07);
  color:rgba(255,255,255,.85);
  padding:11px 14px;
  font-family:'Didact Gothic',sans-serif;font-size:.85rem;
  transition:border-color .25s;
  width:100%;
}
.field input:focus,.field select:focus,.field textarea:focus{outline:none;border-color:rgba(212,168,67,.45);}
.field input::placeholder,.field textarea::placeholder{color:rgba(255,255,255,.15);}
.field select option{background:var(--ink2);}
.field textarea{resize:vertical;min-height:90px;}

/* Upload image */
.img-upload-area{
  border:2px dashed rgba(212,168,67,.2);padding:28px 20px;
  text-align:center;cursor:pointer;transition:border-color .3s,background .3s;
  position:relative;
}
.img-upload-area:hover,.img-upload-area.dragover{border-color:rgba(212,168,67,.5);background:rgba(212,168,67,.04);}
.img-upload-area i{font-size:2rem;color:rgba(212,168,67,.3);display:block;margin-bottom:10px;}
.img-upload-area p{font-size:.8rem;color:rgba(255,255,255,.25);}
.img-upload-area span{font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2px;color:var(--g1);display:block;margin-top:6px;}
.img-upload-area input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;}
.img-preview{margin-top:14px;display:none;}
.img-preview img{max-width:100%;max-height:180px;object-fit:cover;border:1px solid rgba(255,255,255,.08);}

/* Séparateur ou */
.or-sep{display:flex;align-items:center;gap:12px;color:rgba(255,255,255,.2);font-size:.72rem;margin:4px 0;}
.or-sep::before,.or-sep::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.07);}

/* Toggles checkbox */
.toggle-row{display:flex;align-items:center;gap:14px;}
.toggle-wrap{display:flex;align-items:center;gap:8px;cursor:pointer;}
.toggle-wrap input[type=checkbox]{display:none;}
.toggle-switch{width:40px;height:22px;background:rgba(255,255,255,.08);border-radius:11px;position:relative;transition:background .25s;}
.toggle-switch::after{content:'';position:absolute;width:16px;height:16px;border-radius:50%;background:rgba(255,255,255,.3);top:3px;left:3px;transition:all .25s;}
.toggle-wrap input:checked + .toggle-switch{background:rgba(212,168,67,.4);}
.toggle-wrap input:checked + .toggle-switch::after{transform:translateX(18px);background:var(--g1);}
.toggle-label{font-size:.78rem;color:rgba(255,255,255,.55);}

/* Boutons form */
.form-actions{display:flex;gap:10px;margin-top:8px;}
.btn-save{background:var(--g1);color:var(--ink);border:none;padding:14px 36px;font-family:'Cinzel',serif;font-size:.65rem;letter-spacing:3px;text-transform:uppercase;cursor:pointer;transition:background .25s;display:flex;align-items:center;gap:8px;}
.btn-save:hover{background:var(--g2);}
.btn-cancel{background:transparent;border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.4);padding:14px 24px;font-family:'Cinzel',serif;font-size:.65rem;letter-spacing:3px;text-transform:uppercase;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:8px;transition:all .25s;}
.btn-cancel:hover{border-color:rgba(255,255,255,.25);color:#fff;}

/* Badge promo */
.badge-new{display:inline-flex;align-items:center;gap:4px;background:rgba(212,168,67,.12);color:var(--g1);padding:2px 8px;font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:1.5px;}
.badge-best{display:inline-flex;align-items:center;gap:4px;background:rgba(255,255,255,.06);color:rgba(255,255,255,.5);padding:2px 8px;font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:1.5px;}

/* Pagination */
.pager{display:flex;align-items:center;justify-content:space-between;margin-top:16px;font-size:.78rem;color:rgba(255,255,255,.3);}
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
    <a href="produits.php" class="nav-link active"><i class="fas fa-gem"></i> Produits</a>
    <a href="commandes.php" class="nav-link"><i class="fas fa-shopping-bag"></i> Commandes</a>
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
    <span class="topbar-title">
      <?php if($action==='liste'): ?>Produits (<?=count($produits)?>)
      <?php elseif($action==='ajouter'): ?>Nouveau produit
      <?php else: ?>Modifier un produit<?php endif; ?>
    </span>
    <?php if($action==='liste'): ?>
      <a href="produits.php?action=ajouter" class="topbar-btn"><i class="fas fa-plus"></i> Ajouter un produit</a>
    <?php else: ?>
      <a href="produits.php" class="topbar-btn ghost"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
    <?php endif; ?>
  </div>

  <div class="page-content">

    <?php if($msg): ?>
      <div class="msg-bar <?=$msgType?>">
        <i class="fas <?=$msgType==='ok'?'fa-check-circle':'fa-exclamation-circle'?>"></i>
        <?=htmlspecialchars($msg)?>
      </div>
    <?php endif; ?>

    <!-- ═══ LISTE ═══ -->
    <?php if($action === 'liste'): ?>

      <div class="list-toolbar">
        <form method="GET" action="" style="display:flex;gap:12px;flex:1;flex-wrap:wrap;">
          <input type="hidden" name="action" value="liste">
          <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="q" class="search-input" placeholder="Rechercher un produit ou une marque..." value="<?=htmlspecialchars($search_q)?>">
          </div>
          <select name="genre" class="filter-select">
            <option value="">Tous genres</option>
            <option value="Femme">Femme</option>
            <option value="Homme">Homme</option>
            <option value="Unisexe">Unisexe</option>
          </select>
          <button type="submit" class="topbar-btn" style="height:40px;">Filtrer</button>
        </form>
      </div>

      <table class="prod-table">
        <thead>
          <tr>
            <th></th>
            <th>Produit</th>
            <th>Marque</th>
            <th>Prix</th>
            <th>Promo</th>
            <th>Stock</th>
            <th>Tags</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if($produits): foreach($produits as $p):
            $sc = $p['stock'] == 0 ? 'low' : ($p['stock'] <= 3 ? 'warn' : 'ok');
          ?>
          <tr>
            <td style="width:60px;">
              <img class="prod-thumb" src="<?=htmlspecialchars($p['image_url'] ?: 'https://via.placeholder.com/48x48/1A140E/D4A843?text=✦')?>" alt="">
            </td>
            <td>
              <div class="prod-name-cell"><?=htmlspecialchars($p['nom'])?></div>
              <div class="prod-sub"><?=htmlspecialchars($p['matiere']??'')?><?=$p['matiere']&&$p['pierre']?' · ':''?><?=htmlspecialchars($p['pierre']??'')?></div>
            </td>
            <td style="color:rgba(255,255,255,.45);font-size:.78rem;"><?=htmlspecialchars($p['marque']??'')?></td>
            <td style="font-family:'EB Garamond',serif;font-size:1.05rem;color:var(--g1);">
              <?=number_format($p['prix'],2,',',' ')?>€
            </td>
            <td>
              <?php if($p['promotion_pourcentage']>0): ?>
                <span style="font-family:'Cinzel',serif;font-size:.65rem;color:var(--red);">−<?=$p['promotion_pourcentage']?>%</span>
              <?php else: ?><span style="color:rgba(255,255,255,.15);">—</span><?php endif; ?>
            </td>
            <td><span class="stock-pill <?=$sc?>"><?=$p['stock']==0?'Épuisé':$p['stock'].' pcs'?></span></td>
            <td>
              <?php if($p['nouveaute']): ?><span class="badge-new"><i class="fas fa-star"></i> Nouveau</span><?php endif; ?>
              <?php if($p['bestseller']): ?><span class="badge-best"><i class="fas fa-fire"></i> Best</span><?php endif; ?>
            </td>
            <td>
              <div class="tbl-actions">
                <a href="../pages/produit.php?id=<?=$p['id_produit']?>" target="_blank" class="tbl-btn view" title="Voir"><i class="fas fa-eye"></i></a>
                <a href="produits.php?action=modifier&id=<?=$p['id_produit']?>" class="tbl-btn edit" title="Modifier"><i class="fas fa-pen"></i></a>
                <a href="produits.php?action=supprimer&id=<?=$p['id_produit']?>" class="tbl-btn del" title="Supprimer" onclick="return confirm('Supprimer ce produit ?')"><i class="fas fa-trash"></i></a>
              </div>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="8" class="empty-state">Aucun produit trouvé. <a href="produits.php?action=ajouter" style="color:var(--g1);text-decoration:none;">Ajouter le premier →</a></td></tr>
          <?php endif; ?>
        </tbody>
      </table>

    <!-- ═══ FORMULAIRE AJOUT / MODIF ═══ -->
    <?php else:
      $p = $edit_prod ?? [];
      $is_edit = $action === 'modifier';
    ?>
    <form method="POST" action="produits.php<?=$is_edit?"?action=modifier&id=$edit_id":''?>" enctype="multipart/form-data">
      <input type="hidden" name="form_action" value="<?=$is_edit?'modifier':'ajouter'?>">

      <div class="form-grid">

        <!-- Colonne gauche -->
        <div class="form-col">

          <!-- Infos de base -->
          <div class="form-panel">
            <div class="form-panel-title"><i class="fas fa-gem"></i> Informations du produit</div>
            <div style="display:flex;flex-direction:column;gap:16px;">
              <div class="field">
                <label>Nom du produit *</label>
                <input type="text" name="nom" value="<?=htmlspecialchars($p['nom']??'')?>" placeholder="Ex: Trinity Ring" required>
              </div>
              <div class="field">
                <label>Marque / Maison</label>
                <input type="text" name="marque" value="<?=htmlspecialchars($p['marque']??'')?>" placeholder="Ex: Cartier, Tiffany & Co.">
              </div>
              <div class="field">
                <label>Description courte</label>
                <input type="text" name="description_courte" value="<?=htmlspecialchars($p['description_courte']??'')?>" placeholder="Résumé en une ligne">
              </div>
              <div class="field">
                <label>Description complète</label>
                <textarea name="description" rows="4" placeholder="Description détaillée du produit..."><?=htmlspecialchars($p['description']??'')?></textarea>
              </div>
            </div>
          </div>

          <!-- Caractéristiques -->
          <div class="form-panel">
            <div class="form-panel-title"><i class="fas fa-list"></i> Caractéristiques</div>
            <div style="display:flex;flex-direction:column;gap:14px;">
              <div class="field">
                <label>Matière</label>
                <input type="text" name="matiere" value="<?=htmlspecialchars($p['matiere']??'')?>" placeholder="Ex: Or jaune 18 carats">
              </div>
              <div class="field">
                <label>Pierre</label>
                <input type="text" name="pierre" value="<?=htmlspecialchars($p['pierre']??'')?>" placeholder="Ex: Diamant GIA, Saphir">
              </div>
              <div class="field">
                <label>Taille / Dimensions</label>
                <input type="text" name="taille" value="<?=htmlspecialchars($p['taille']??'')?>" placeholder="Ex: 52 / Ajustable / 17 cm">
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="field">
                  <label>Genre</label>
                  <select name="genre">
                    <?php foreach(['Femme','Homme','Unisexe'] as $g): ?>
                      <option value="<?=$g?>" <?=($p['genre']??'Femme')===$g?'selected':''?>><?=$g?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field">
                  <label>Catégorie</label>
                  <select name="id_categorie">
                    <option value="">— Choisir —</option>
                    <?php foreach($categories as $c): ?>
                      <option value="<?=$c['id_categorie']?>" <?=($p['id_categorie']??'')==$c['id_categorie']?'selected':''?>>
                        <?=htmlspecialchars($c['nom_categorie'])?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Colonne droite -->
        <div class="form-col">

          <!-- Image -->
          <div class="form-panel">
            <div class="form-panel-title"><i class="fas fa-image"></i> Image du produit</div>

            <!-- Upload fichier -->
            <div class="img-upload-area" id="dropZone">
              <input type="file" name="image_file" id="imgFile" accept="image/*">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>Glisser-déposer votre image ici</p>
              <span>ou cliquer pour choisir un fichier</span>
              <p style="font-size:.7rem;margin-top:6px;">JPG, PNG, WEBP — Max 5MB</p>
            </div>
            <div class="img-preview" id="imgPreview">
              <img id="previewImg" src="" alt="Aperçu">
            </div>

            <div class="or-sep">ou utiliser une URL</div>

            <div class="field">
              <label>URL de l'image</label>
              <input type="text" name="image_url" id="imgUrl" value="<?=htmlspecialchars($p['image_url']??'')?>" placeholder="https://...">
            </div>

            <?php if(!empty($p['image_url'])): ?>
              <div style="margin-top:12px;">
                <p style="font-size:.72rem;color:rgba(255,255,255,.3);margin-bottom:6px;">Image actuelle :</p>
                <img src="<?=htmlspecialchars($p['image_url'])?>" style="max-width:100%;max-height:160px;object-fit:cover;border:1px solid rgba(255,255,255,.08);" alt="">
              </div>
            <?php endif; ?>
          </div>

          <!-- Prix & Stock -->
          <div class="form-panel">
            <div class="form-panel-title"><i class="fas fa-euro-sign"></i> Prix & Stock</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
              <div class="field">
                <label>Prix (€) *</label>
                <input type="number" name="prix" value="<?=htmlspecialchars($p['prix']??'')?>" step="0.01" min="0" placeholder="0.00" required>
              </div>
              <div class="field">
                <label>Stock (pièces)</label>
                <input type="number" name="stock" value="<?=htmlspecialchars($p['stock']??0)?>" min="0" placeholder="0">
              </div>
              <div class="field">
                <label>Promotion (%)</label>
                <input type="number" name="promotion_pourcentage" value="<?=htmlspecialchars($p['promotion_pourcentage']??0)?>" min="0" max="100" step="1" placeholder="0">
              </div>
            </div>
          </div>

          <!-- Tags -->
          <div class="form-panel">
            <div class="form-panel-title"><i class="fas fa-tags"></i> Tags & Mise en avant</div>
            <div class="toggle-row">
              <label class="toggle-wrap">
                <input type="checkbox" name="nouveaute" <?=($p['nouveaute']??0)?'checked':''?>>
                <span class="toggle-switch"></span>
                <span class="toggle-label">Nouveauté</span>
              </label>
              <label class="toggle-wrap">
                <input type="checkbox" name="bestseller" <?=($p['bestseller']??0)?'checked':''?>>
                <span class="toggle-switch"></span>
                <span class="toggle-label">Best-seller</span>
              </label>
            </div>
          </div>

          <!-- Boutons -->
          <div class="form-actions">
            <button type="submit" class="btn-save">
              <i class="fas <?=$is_edit?'fa-save':'fa-plus'?>"></i>
              <?=$is_edit?'Enregistrer les modifications':'Ajouter le produit'?>
            </button>
            <a href="produits.php" class="btn-cancel"><i class="fas fa-times"></i> Annuler</a>
          </div>

        </div>
      </div>
    </form>
    <?php endif; ?>

  </div><!-- /page-content -->
</div><!-- /main -->

<script>
// ── Aperçu image upload ──
document.getElementById('imgFile').addEventListener('change', function(){
  const file = this.files[0];
  if(!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('previewImg').src = e.target.result;
    document.getElementById('imgPreview').style.display = 'block';
    document.getElementById('imgUrl').value = '';
  };
  reader.readAsDataURL(file);
});

// ── Drag & drop ──
const zone = document.getElementById('dropZone');
if(zone){
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if(file && file.type.startsWith('image/')){
      document.getElementById('imgFile').files = e.dataTransfer.files;
      const reader = new FileReader();
      reader.onload = ev => {
        document.getElementById('previewImg').src = ev.target.result;
        document.getElementById('imgPreview').style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  });
}

// ── Aperçu depuis URL ──
document.getElementById('imgUrl')?.addEventListener('input', function(){
  if(this.value.startsWith('http')){
    document.getElementById('previewImg').src = this.value;
    document.getElementById('imgPreview').style.display = 'block';
  }
});
</script>
</body>
</html>