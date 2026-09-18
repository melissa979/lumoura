<?php
require_once __DIR__ . '/auth_admin.php';

if (!isset($_SESSION['user_id'])) { header('Location: /lumoura/pages/connexion.php'); exit; }

$message = ''; $erreur = '';

if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
    try {
        $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur=?")->execute([$_GET['supprimer']]);
        $message = "✅ Utilisateur supprimé.";
    } catch (Exception $e) { $erreur = "❌ Impossible de supprimer (commandes liées ?)."; }
}

$search = trim($_GET['search'] ?? '');
$sql = "SELECT u.*, COUNT(c.id_commande) AS nb_commandes FROM utilisateurs u LEFT JOIN commandes c ON u.id_utilisateur=c.id_utilisateur WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ?)"; $params = ["%$search%","%$search%","%$search%"]; }
$sql .= " GROUP BY u.id_utilisateur ORDER BY u.date_inscription DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><title>Utilisateurs – Admin Lumoura</title>
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
.btn-logout{background:#e74c3c;color:#fff;border:none;padding:7px 15px;border-radius:5px;text-decoration:none;font-size:.85rem}
.content{padding:25px;flex:1}
.alert{padding:12px 18px;border-radius:6px;margin-bottom:20px;font-size:.9rem}
.alert-success{background:#d4edda;color:#155724}
.alert-error{background:#f8d7da;color:#721c24}
.filtres{background:#fff;padding:15px 20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.07);margin-bottom:20px;display:flex;gap:12px}
.filtres input{flex:1;padding:9px 12px;border:1px solid #ddd;border-radius:6px;font-size:.9rem}
.filtres button{background:#c9a227;color:#fff;border:none;padding:9px 20px;border-radius:6px;cursor:pointer}
.table-card{background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden}
.table-card-header{padding:15px 20px;background:#1a1a2e;color:#c9a227}
table{width:100%;border-collapse:collapse}
th,td{padding:11px 14px;text-align:left;font-size:.84rem;border-bottom:1px solid #f0f0f0}
th{background:#f8f9fa;color:#555;font-weight:600}
tr:last-child td{border-bottom:none}
.avatar{width:36px;height:36px;border-radius:50%;background:#c9a227;color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.9rem}
.btn-sm{padding:5px 10px;border-radius:4px;border:none;cursor:pointer;font-size:.78rem;text-decoration:none;display:inline-block}
.btn-del{background:#e74c3c;color:#fff}
</style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-logo">💎 LUMOURA <span>Administration</span></div>
    <nav>
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="produits.php"><i class="fas fa-gem"></i> Produits</a>
        <a href="categories.php"><i class="fas fa-tags"></i> Catégories</a>
        <a href="commandes.php"><i class="fas fa-shopping-bag"></i> Commandes</a>
        <a href="utilisateurs.php" class="active"><i class="fas fa-users"></i> Utilisateurs</a>
    </nav>
    <div class="sidebar-footer">Lumoura Admin v1.0</div>
</aside>
<div class="main">
<div class="topbar">
    <h1><i class="fas fa-users"></i> Utilisateurs / Clients</h1>
    <a href="../deconnexion.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
</div>
<div class="content">
<?php if ($message): ?><div class="alert alert-success"><?=$message?></div><?php endif; ?>
<?php if ($erreur):   ?><div class="alert alert-error"><?=$erreur?></div><?php endif; ?>

<form method="GET" class="filtres">
    <input type="text" name="search" placeholder="🔍 Rechercher par nom, prénom ou email…" value="<?=htmlspecialchars($search)?>">
    <button type="submit">Rechercher</button>
    <?php if ($search): ?><a href="utilisateurs.php" style="padding:9px 15px;background:#6c757d;color:#fff;border-radius:6px;text-decoration:none;font-size:.88rem;">✕</a><?php endif; ?>
</form>

<div class="table-card">
    <div class="table-card-header"><i class="fas fa-users"></i> <?=count($users)?> utilisateur(s)</div>
    <table>
        <thead><tr><th></th><th>Nom</th><th>Email</th><th>Téléphone</th><th>Commandes</th><th>Inscrit le</th><th>Action</th></tr></thead>
        <tbody>
        <?php if (empty($users)): ?>
            <tr><td colspan="7" style="text-align:center;padding:30px;color:#aaa;">Aucun utilisateur</td></tr>
        <?php else: foreach ($users as $u): ?>
        <tr>
            <td><div class="avatar"><?=strtoupper(substr($u['prenom']??'?',0,1))?></div></td>
            <td><strong><?=htmlspecialchars(($u['prenom']??'').' '.($u['nom']??''))?></strong></td>
            <td><?=htmlspecialchars($u['email']??'')?></td>
            <td><?=htmlspecialchars($u['telephone']??'—')?></td>
            <td><?=$u['nb_commandes']?> commande(s)</td>
            <td><?=isset($u['date_inscription'])?date('d/m/Y', strtotime($u['date_inscription'])):'—'?></td>
            <td>
                <a href="?supprimer=<?=$u['id_utilisateur']?>" class="btn-sm btn-del" onclick="return confirm('Supprimer cet utilisateur ?')"><i class="fas fa-trash"></i></a>
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