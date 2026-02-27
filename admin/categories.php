<?php
// ══════════════════════════════════════════
//  LUMOURA — Gestion des Catégories
//  admin/categories.php
// ══════════════════════════════════════════
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../pages/connexion.php'); exit();
}

$msg = ''; $msgType = '';

// ─── TRAITEMENT FORMULAIRE ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom         = trim($_POST['nom_categorie'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $image_url   = trim($_POST['image_url'] ?? '');

    // Upload image
    if (!empty($_FILES['image_file']['name'])) {
        $uploadDir = '../uploads/categories/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $newName = 'cat_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . $newName)) {
                $image_url = '../uploads/categories/' . $newName;
            }
        }
    }

    if (empty($nom)) {
        $msg = 'Le nom de la catégorie est obligatoire.'; $msgType = 'error';
    } else {
        try {
            if ($_POST['form_action'] === 'ajouter') {
                $pdo->prepare("INSERT INTO categories (nom_categorie, description, image_url) VALUES (?,?,?)")
                    ->execute([$nom, $description, $image_url]);
                $msg = '✦ Catégorie "'.$nom.'" ajoutée !'; $msgType = 'ok';
            } elseif ($_POST['form_action'] === 'modifier' && !empty($_POST['id_categorie'])) {
                $pdo->prepare("UPDATE categories SET nom_categorie=?, description=?, image_url=? WHERE id_categorie=?")
                    ->execute([$nom, $description, $image_url, intval($_POST['id_categorie'])]);
                $msg = '✦ Catégorie mise à jour !'; $msgType = 'ok';
            }
        } catch(Exception $e) {
            $msg = 'Erreur : '.$e->getMessage(); $msgType = 'error';
        }
    }
}

// ─── SUPPRESSION ───
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $nb = (int)$pdo->prepare("SELECT COUNT(*) FROM produits WHERE id_categorie=?")->execute([$id]) ? 
              $pdo->prepare("SELECT COUNT(*) FROM produits WHERE id_categorie=?")->execute([$id]) : 0;
        // Vérifier si des produits sont liés
        $st = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE id_categorie=?");
        $st->execute([$id]);
        $nb = (int)$st->fetchColumn();
        if ($nb > 0) {
            $msg = "Impossible de supprimer : $nb produit(s) utilisent cette catégorie."; $msgType = 'error';
        } else {
            $pdo->prepare("DELETE FROM categories WHERE id_categorie=?")->execute([$id]);
            $msg = 'Catégorie supprimée.'; $msgType = 'ok';
        }
    } catch(Exception $e) {
        $msg = 'Erreur : '.$e->getMessage(); $msgType = 'error';
    }
}

// ─── MODE ÉDITION ───
$action  = $_GET['action'] ?? 'liste';
$edit_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$edit_cat = null;
if ($action === 'modifier' && $edit_id) {
    try {
        $st = $pdo->prepare("SELECT * FROM categories WHERE id_categorie=?");
        $st->execute([$edit_id]);
        $edit_cat = $st->fetch();
        if (!$edit_cat) $action = 'liste';
    } catch(Exception $e) { $action = 'liste'; }
}

// ─── CHARGEMENT CATÉGORIES ───
$categories = [];
try {
    $categories = $pdo->query("
        SELECT c.*, COUNT(p.id_produit) AS nb_produits
        FROM categories c
        LEFT JOIN produits p ON p.id_categorie = c.id_categorie
        GROUP BY c.id_categorie
        ORDER BY c.nom_categorie
    ")->fetchAll();
} catch(Exception $e) { $db_error = $e->getMessage(); }

if ($action !== 'liste') $action = ($action === 'ajouter' || $action === 'modifier') ? $action : 'liste';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Catégories — Admin Lumoura</title>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Cinzel:wght@400;600;700&family=Didact+Gothic&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--g1:#D4A843;--g2:#F5D78E;--ink:#0D0A06;--ink2:#1A140E;--ink3:#241C12;--red:#C0392B;--green:#27AE60;--sidebar:240px;}
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

/* GRILLE CATÉGORIES */
.cat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:2px;background:rgba(255,255,255,.04);}
.cat-card{background:var(--ink2);position:relative;overflow:hidden;transition:background .2s;}
.cat-card:hover{background:var(--ink3);}
.cat-card-img{width:100%;height:140px;object-fit:cover;display:block;filter:brightness(.7);}
.cat-card-img-placeholder{width:100%;height:140px;background:rgba(212,168,67,.04);display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:rgba(212,168,67,.15);}
.cat-card-body{padding:18px 20px;}
.cat-card-name{font-family:'EB Garamond',serif;font-size:1.15rem;color:#fff;margin-bottom:6px;}
.cat-card-desc{font-size:.76rem;color:rgba(255,255,255,.35);line-height:1.5;margin-bottom:12px;min-height:34px;}
.cat-card-foot{display:flex;align-items:center;justify-content:space-between;}
.cat-card-count{font-family:'Cinzel',serif;font-size:.55rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.3);}
.cat-card-actions{display:flex;gap:6px;}
.tbl-btn{width:30px;height:30px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.78rem;transition:all .2s;text-decoration:none;}
.tbl-btn.edit{background:rgba(212,168,67,.1);color:var(--g1);}
.tbl-btn.edit:hover{background:rgba(212,168,67,.25);}
.tbl-btn.del{background:rgba(192,57,43,.1);color:var(--red);}
.tbl-btn.del:hover{background:rgba(192,57,43,.25);}

.empty-state{text-align:center;padding:60px 20px;color:rgba(255,255,255,.2);font-family:'EB Garamond',serif;font-size:1.2rem;background:var(--ink2);}

/* ═══ FORMULAIRE ═══ */
.form-wrap{max-width:640px;}
.form-panel{background:var(--ink2);padding:32px;}
.form-panel-title{font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:4px;text-transform:uppercase;color:var(--g1);padding-bottom:14px;margin-bottom:22px;border-bottom:1px solid rgba(255,255,255,.05);display:flex;align-items:center;gap:10px;}
.field{display:flex;flex-direction:column;gap:7px;margin-bottom:18px;}
.field label{font-family:'Cinzel',serif;font-size:.56rem;letter-spacing:2.5px;text-transform:uppercase;color:rgba(255,255,255,.4);}
.field input,.field textarea{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);color:rgba(255,255,255,.85);padding:11px 14px;font-family:'Didact Gothic',sans-serif;font-size:.85rem;transition:border-color .25s;width:100%;}
.field input:focus,.field textarea:focus{outline:none;border-color:rgba(212,168,67,.45);}
.field input::placeholder,.field textarea::placeholder{color:rgba(255,255,255,.15);}
.field textarea{resize:vertical;min-height:80px;}

.img-upload-area{border:2px dashed rgba(212,168,67,.2);padding:24px 20px;text-align:center;cursor:pointer;transition:border-color .3s,background .3s;position:relative;margin-bottom:10px;}
.img-upload-area:hover{border-color:rgba(212,168,67,.5);background:rgba(212,168,67,.04);}
.img-upload-area i{font-size:1.8rem;color:rgba(212,168,67,.3);display:block;margin-bottom:8px;}
.img-upload-area p{font-size:.78rem;color:rgba(255,255,255,.25);}
.img-upload-area span{font-family:'Cinzel',serif;font-size:.55rem;letter-spacing:2px;color:var(--g1);display:block;margin-top:4px;}
.img-upload-area input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;}
.img-preview{margin-top:10px;display:none;}
.img-preview img{max-width:100%;max-height:160px;object-fit:cover;border:1px solid rgba(255,255,255,.08);}
.or-sep{display:flex;align-items:center;gap:12px;color:rgba(255,255,255,.2);font-size:.72rem;margin:10px 0;}
.or-sep::before,.or-sep::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.07);}

.form-actions{display:flex;gap:10px;margin-top:24px;}
.btn-save{background:var(--g1);color:var(--ink);border:none;padding:13px 32px;font-family:'Cinzel',serif;font-size:.62rem;letter-spacing:3px;text-transform:uppercase;cursor:pointer;transition:background .25s;display:flex;align-items:center;gap:8px;}
.btn-save:hover{background:var(--g2);}
.btn-cancel{background:transparent;border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.4);padding:13px 22px;font-family:'Cinzel',serif;font-size:.62rem;letter-spacing:3px;text-transform:uppercase;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:8px;transition:all .25s;}
.btn-cancel:hover{border-color:rgba(255,255,255,.25);color:#fff;}
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
    <a href="clients.php" class="nav-link"><i class="fas fa-users"></i> Clients</a>
  </nav>
  <div class="sidebar-section">Catalogue</div>
  <nav class="sidebar-nav">
    <a href="categories.php" class="nav-link active"><i class="fas fa-tags"></i> Catégories</a>
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
      <?php if($action==='liste'): ?>Catégories (<?=count($categories)?>)
      <?php elseif($action==='ajouter'): ?>Nouvelle catégorie
      <?php else: ?>Modifier la catégorie<?php endif; ?>
    </span>
    <?php if($action==='liste'): ?>
      <a href="categories.php?action=ajouter" class="topbar-btn"><i class="fas fa-plus"></i> Ajouter</a>
    <?php else: ?>
      <a href="categories.php" class="topbar-btn ghost"><i class="fas fa-arrow-left"></i> Retour</a>
    <?php endif; ?>
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

    <?php if($action === 'liste'): ?>
    <!-- ═══ GRILLE ═══ -->
    <?php if($categories): ?>
    <div class="cat-grid">
      <?php foreach($categories as $c): ?>
      <div class="cat-card">
        <?php if(!empty($c['image_url'])): ?>
          <img class="cat-card-img" src="<?=htmlspecialchars($c['image_url'])?>" alt="<?=htmlspecialchars($c['nom_categorie'])?>">
        <?php else: ?>
          <div class="cat-card-img-placeholder"><i class="fas fa-tags"></i></div>
        <?php endif; ?>
        <div class="cat-card-body">
          <div class="cat-card-name"><?=htmlspecialchars($c['nom_categorie'])?></div>
          <div class="cat-card-desc"><?=htmlspecialchars($c['description'] ?? '')?></div>
          <div class="cat-card-foot">
            <span class="cat-card-count"><?=$c['nb_produits']?> produit<?=$c['nb_produits']!=1?'s':''?></span>
            <div class="cat-card-actions">
              <a href="categories.php?action=modifier&id=<?=$c['id_categorie']?>" class="tbl-btn edit" title="Modifier"><i class="fas fa-pen"></i></a>
              <a href="categories.php?action=supprimer&id=<?=$c['id_categorie']?>" class="tbl-btn del" title="Supprimer" onclick="return confirm('Supprimer cette catégorie ?')"><i class="fas fa-trash"></i></a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="empty-state">Aucune catégorie. <a href="categories.php?action=ajouter" style="color:var(--g1);text-decoration:none;">Créer la première →</a></div>
    <?php endif; ?>

    <?php else:
      $cat = $edit_cat ?? [];
      $is_edit = $action === 'modifier';
    ?>
    <!-- ═══ FORMULAIRE ═══ -->
    <div class="form-wrap">
      <form method="POST" action="categories.php<?=$is_edit?"?action=modifier&id=$edit_id":''?>" enctype="multipart/form-data">
        <input type="hidden" name="form_action" value="<?=$is_edit?'modifier':'ajouter'?>">
        <?php if($is_edit): ?><input type="hidden" name="id_categorie" value="<?=$edit_id?>"><?php endif; ?>

        <div class="form-panel">
          <div class="form-panel-title"><i class="fas fa-tags"></i> Informations de la catégorie</div>

          <div class="field">
            <label>Nom de la catégorie *</label>
            <input type="text" name="nom_categorie" value="<?=htmlspecialchars($cat['nom_categorie']??'')?>" placeholder="Ex: Bagues, Colliers, Bracelets..." required>
          </div>

          <div class="field">
            <label>Description</label>
            <textarea name="description" placeholder="Description courte de la catégorie..."><?=htmlspecialchars($cat['description']??'')?></textarea>
          </div>

          <div class="field">
            <label>Image de la catégorie</label>
            <div class="img-upload-area" id="dropZone">
              <input type="file" name="image_file" id="imgFile" accept="image/*">
              <i class="fas fa-cloud-upload-alt"></i>
              <p>Glisser-déposer une image</p>
              <span>ou cliquer pour choisir</span>
            </div>
            <div class="img-preview" id="imgPreview">
              <img id="previewImg" src="" alt="Aperçu">
            </div>
            <div class="or-sep">ou utiliser une URL</div>
            <input type="text" name="image_url" id="imgUrl" value="<?=htmlspecialchars($cat['image_url']??'')?>" placeholder="https://...">
            <?php if(!empty($cat['image_url'])): ?>
              <div style="margin-top:10px;">
                <p style="font-size:.7rem;color:rgba(255,255,255,.3);margin-bottom:6px;">Image actuelle :</p>
                <img src="<?=htmlspecialchars($cat['image_url'])?>" style="max-width:100%;max-height:120px;object-fit:cover;border:1px solid rgba(255,255,255,.08);" alt="">
              </div>
            <?php endif; ?>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-save">
              <i class="fas <?=$is_edit?'fa-save':'fa-plus'?>"></i>
              <?=$is_edit?'Enregistrer':'Créer la catégorie'?>
            </button>
            <a href="categories.php" class="btn-cancel"><i class="fas fa-times"></i> Annuler</a>
          </div>
        </div>
      </form>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
document.getElementById('imgFile')?.addEventListener('change', function(){
  const file = this.files[0]; if(!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('previewImg').src = e.target.result;
    document.getElementById('imgPreview').style.display = 'block';
    document.getElementById('imgUrl').value = '';
  };
  reader.readAsDataURL(file);
});
document.getElementById('imgUrl')?.addEventListener('input', function(){
  if(this.value.startsWith('http')){
    document.getElementById('previewImg').src = this.value;
    document.getElementById('imgPreview').style.display = 'block';
  }
});
</script>
</body>
</html>