<?php
require_once __DIR__ . '/auth_admin.php';

if (!isset($_SESSION['user_id'])) { header('Location: /lumoura/pages/connexion.php'); exit; }

$message = ''; $erreur = '';

if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
    try {
        $pdo->prepare("DELETE FROM categories WHERE id_categorie = ?")->execute([$_GET['supprimer']]);
        $message = "✅ Catégorie supprimée.";
    } catch (Exception $e) { $erreur = "❌ Impossible (des produits utilisent cette catégorie)."; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom_categorie'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $id = intval($_POST['id_categorie'] ?? 0);

    if ($nom === '') { $erreur = "❌ Le nom est obligatoire."; }
    elseif ($id) {
        $pdo->prepare("UPDATE categories SET nom_categorie=?, description=? WHERE id_categorie=?")->execute([$nom,$desc,$id]);
        $message = "✅ Catégorie modifiée.";
    } else {
        $pdo->prepare("INSERT INTO categories (nom_categorie, description) VALUES (?, ?)")->execute([$nom,$desc]);
        $message = "✅ Catégorie créée !";
    }
}

$categories = $pdo->query("SELECT c.*, COUNT(p.id_produit) AS nb_produits FROM categories c LEFT JOIN produits p ON c.id_categorie=p.id_categorie GROUP BY c.id_categorie ORDER BY c.nom_categorie")->fetchAll();

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id_categorie=?");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><title>Catégories – Admin Lumoura</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:#f0f2f5;display:flex;min-height:100vh}
.sidebar{width:240px;background:#1a1a2e;color:#ccc;display:flex;flex-direction:column;flex-shrink:0}
.sidebar-logo{padding:25px 20px;background:#16213e;color:#c9a227;font-size:1.2rem;font-weight:700;letter-spacing:1px;border-bottom:1px solid #0f3460}
.sidebar-logo span{display:block;font-size:.7rem;color:#888;font-weight:400;margin-top:3px}
.sidebar nav{flex:1;padding:15px 0}
.sidebar nav a{display:flex;align-items:center;gap:12px;padding:12px 20px;color:#aaa;text-decoration:none;font-size:.9rem;transition:all .2s}
.sidebar nav a:hover,.sidebar nav a.active{background:#0f3460;color:#c9a227;border-left:3px solid #c9a227}
.sidebar nav a i{width:18px;text-align:center}
.sidebar-footer{padding:15px 20px;border-top:1px solid #0f3460;font-size:.8rem;color:#666}
.main{flex:1;display:flex;flex-direction:column}
.topbar{background:#fff;padding:15px 30px;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 4px rgba(0,0,0,.06)}
.topbar h1{font-size:1.3rem;color:#1a1a2e}
.btn-logout{background:#e74c3c;color:#fff;border:none;padding:7px 15px;border-radius:5px;cursor:pointer;font-size:.85rem;text-decoration:none}
.content{padding:25px;flex:1;display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start}
.alert{padding:12px 18px;border-radius:6px;margin-bottom:15px;font-size:.9rem;grid-column:1/-1}
.alert-success{background:#d4edda;color:#155724}
.alert-error{background:#f8d7da;color:#721c24}
.card{background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden}
.card-header{padding:15px 20px;background:#1a1a2e;color:#c9a227}
.card-body{padding:20px}
.form-group{margin-bottom:14px}
label{display:block;font-size:.82rem;font-weight:600;color:#555;margin-bottom:5px}
input[type=text],textarea{width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:6px;font-size:.9rem;outline:none}
input:focus,textarea:focus{border-color:#c9a227}
textarea{resize:vertical;min-height:70px}
.btn-submit{background:#c9a227;color:#fff;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;font-size:.9rem;font-weight:600}
.btn-cancel{background:#6c757d;color:#fff;padding:10px 15px;border-radius:6px;text-decoration:none;font-size:.9rem;margin-left:8px}
table{width:100%;border-collapse:collapse}
th,td{padding:11px 14px;text-align:left;font-size:.84rem;border-bottom:1px solid #f0f0f0}
th{background:#f8f9fa;color:#555;font-weight:600}
tr:last-child td{border-bottom:none}
.btn-sm{padding:5px 10px;border-radius:4px;border:none;cursor:pointer;font-size:.78rem;text-decoration:none;display:inline-block;margin-right:3px}
.btn-edit{background:#c9a227;color:#fff}
.btn-del{background:#e74c3c;color:#fff}
</style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-logo">💎 LUMOURA <span>Administration</span></div>
    <nav>
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="produits.php"><i class="fas fa-gem"></i> Produits</a>
        <a href="categories.php" class="active"><i class="fas fa-tags"></i> Catégories</a>
        <a href="commandes.php"><i class="fas fa-shopping-bag"></i> Commandes</a>
        <a href="utilisateurs.php"><i class="fas fa-users"></i> Utilisateurs</a>
    </nav>
    <div class="sidebar-footer">Lumoura Admin v1.0</div>
</aside>
<div class="main">
<div class="topbar">
    <h1><i class="fas fa-tags"></i> Catégories</h1>
    <a href="../deconnexion.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
</div>
<div class="content">
<?php if ($message): ?><div class="alert alert-success" style="grid-column:1/-1"><?=$message?></div><?php endif; ?>
<?php if ($erreur):   ?><div class="alert alert-error"   style="grid-column:1/-1"><?=$erreur?></div><?php endif; ?>

<div class="card">
    <div class="card-header"><i class="fas fa-<?=$edit?'edit':'plus'?>"></i> <?=$edit?'Modifier':'Nouvelle catégorie'?></div>
    <div class="card-body">
        <form method="POST">
            <?php if ($edit): ?><input type="hidden" name="id_categorie" value="<?=$edit['id_categorie']?>"><?php endif; ?>
            <div class="form-group"><label>Nom *</label><input type="text" name="nom_categorie" required value="<?=htmlspecialchars($edit['nom_categorie']??'')?>"></div>
            <div class="form-group"><label>Description</label><textarea name="description"><?=htmlspecialchars($edit['description']??'')?></textarea></div>
            <button type="submit" class="btn-submit"><i class="fas fa-save"></i> <?=$edit?'Enregistrer':'Créer'?></button>
            <?php if ($edit): ?><a href="categories.php" class="btn-cancel">Annuler</a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-list"></i> <?=count($categories)?> catégorie(s)</div>
    <table>
        <thead><tr><th>Nom</th><th>Produits</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($categories)): ?>
            <tr><td colspan="3" style="text-align:center;padding:30px;color:#aaa;">Aucune catégorie</td></tr>
        <?php else: foreach ($categories as $c): ?>
        <tr>
            <td><strong><?=htmlspecialchars($c['nom_categorie'])?></strong><br><small style="color:#aaa;"><?=htmlspecialchars($c['description']??'')?></small></td>
            <td><?=$c['nb_produits']?> produit(s)</td>
            <td>
                <a href="categories.php?edit=<?=$c['id_categorie']?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i></a>
                <a href="categories.php?supprimer=<?=$c['id_categorie']?>" class="btn-sm btn-del" onclick="return confirm('Supprimer cette catégorie ?')"><i class="fas fa-trash"></i></a>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
</div>
</div>
</body>
</html>