<?php
require_once __DIR__ . '/auth_admin.php';

if (!isset($_SESSION['user_id'])) { header('Location: /lumoura/pages/connexion.php'); exit; }

$message = ''; $erreur = '';

// Changer statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_commande'])) {
    try {
        $pdo->prepare("UPDATE commandes SET statut=? WHERE id_commande=?")
            ->execute([$_POST['statut'], $_POST['id_commande']]);
        $message = "✅ Statut mis à jour.";
    } catch (Exception $e) { $erreur = "❌ " . $e->getMessage(); }
}

$filtre = $_GET['statut'] ?? '';
$sql = "SELECT c.*, u.nom AS u_nom, u.prenom AS u_prenom, u.email FROM commandes c
        LEFT JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur WHERE 1=1";
$params = [];
if ($filtre) { $sql .= " AND c.statut=?"; $params[] = $filtre; }
$sql .= " ORDER BY c.date_commande DESC";
$commandes = $pdo->prepare($sql);
$commandes->execute($params);
$commandes = $commandes->fetchAll();

// Détail commande
$detail = null;
if (isset($_GET['detail']) && is_numeric($_GET['detail'])) {
    $stmt = $pdo->prepare("SELECT dc.*, p.nom AS p_nom, p.image_url FROM details_commande dc
        LEFT JOIN produits p ON dc.id_produit=p.id_produit WHERE dc.id_commande=?");
    $stmt->execute([$_GET['detail']]);
    $detail = $stmt->fetchAll();
    $stmt2 = $pdo->prepare("SELECT c.*, u.nom, u.prenom, u.email FROM commandes c LEFT JOIN utilisateurs u ON c.id_utilisateur=u.id_utilisateur WHERE c.id_commande=?");
    $stmt2->execute([$_GET['detail']]);
    $commande_detail = $stmt2->fetch();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><title>Commandes – Admin Lumoura</title>
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
.filtres{background:#fff;padding:15px 20px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.07);margin-bottom:20px;display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.filtres a{padding:7px 14px;border-radius:20px;text-decoration:none;font-size:.83rem;font-weight:600;color:#666;background:#f0f0f0;transition:all .2s}
.filtres a:hover,.filtres a.active{background:#c9a227;color:#fff}
.table-card{background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.07);overflow:hidden}
.table-card-header{padding:15px 20px;background:#1a1a2e;color:#c9a227}
table{width:100%;border-collapse:collapse}
th,td{padding:11px 14px;text-align:left;font-size:.84rem;border-bottom:1px solid #f0f0f0}
th{background:#f8f9fa;color:#555;font-weight:600}
tr:last-child td{border-bottom:none}
.badge{padding:3px 9px;border-radius:20px;font-size:.73rem;font-weight:600}
.b-attente{background:#fff3cd;color:#856404}
.b-confirmee{background:#d1ecf1;color:#0c5460}
.b-livree{background:#d4edda;color:#155724}
.b-annulee{background:#f8d7da;color:#721c24}
.btn-sm{padding:5px 10px;border-radius:4px;border:none;cursor:pointer;font-size:.78rem;text-decoration:none;display:inline-block}
.btn-view{background:#0f3460;color:#fff}
select{padding:5px 8px;border:1px solid #ddd;border-radius:4px;font-size:.82rem}
.btn-save{background:#c9a227;color:#fff;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;font-size:.78rem}
/* Modal */
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;justify-content:center;align-items:center}
.modal.open{display:flex}
.modal-box{background:#fff;border-radius:10px;padding:25px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto}
.modal-box h3{margin-bottom:15px;color:#1a1a2e}
.modal-close{float:right;background:#e74c3c;color:#fff;border:none;padding:4px 10px;border-radius:4px;cursor:pointer}
.item-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f0f0f0}
.item-img{width:45px;height:45px;object-fit:cover;border-radius:4px}
</style>
</head>
<body>
<aside class="sidebar">
    <div class="sidebar-logo">💎 LUMOURA <span>Administration</span></div>
    <nav>
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="produits.php"><i class="fas fa-gem"></i> Produits</a>
        <a href="categories.php"><i class="fas fa-tags"></i> Catégories</a>
        <a href="commandes.php" class="active"><i class="fas fa-shopping-bag"></i> Commandes</a>
        <a href="utilisateurs.php"><i class="fas fa-users"></i> Utilisateurs</a>
    </nav>
    <div class="sidebar-footer">Lumoura Admin v1.0</div>
</aside>
<div class="main">
<div class="topbar">
    <h1><i class="fas fa-shopping-bag"></i> Commandes</h1>
    <a href="../deconnexion.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
</div>
<div class="content">
<?php if ($message): ?><div class="alert alert-success"><?=$message?></div><?php endif; ?>
<?php if ($erreur):   ?><div class="alert alert-error"><?=$erreur?></div><?php endif; ?>

<div class="filtres">
    <strong style="color:#555">Filtrer :</strong>
    <a href="commandes.php" <?= !$filtre?'class="active"':'' ?>>Toutes</a>
    <a href="?statut=en_attente" <?= $filtre==='en_attente'?'class="active"':'' ?>>En attente</a>
    <a href="?statut=confirmee"  <?= $filtre==='confirmee'?'class="active"':'' ?>>Confirmées</a>
    <a href="?statut=livree"     <?= $filtre==='livree'?'class="active"':'' ?>>Livrées</a>
    <a href="?statut=annulee"    <?= $filtre==='annulee'?'class="active"':'' ?>>Annulées</a>
</div>

<div class="table-card">
    <div class="table-card-header"><i class="fas fa-list"></i> <?=count($commandes)?> commande(s)</div>
    <table>
        <thead><tr><th>#</th><th>Client</th><th>Date</th><th>Total</th><th>Statut</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (empty($commandes)): ?>
            <tr><td colspan="6" style="text-align:center;padding:30px;color:#aaa;">Aucune commande</td></tr>
        <?php else: foreach ($commandes as $c):
            $bc = match($c['statut']) { 'en_attente'=>'b-attente','confirmee'=>'b-confirmee','livree'=>'b-livree','annulee'=>'b-annulee', default=>'b-attente' };
        ?>
        <tr>
            <td><strong>#<?=$c['id_commande']?></strong></td>
            <td><?=htmlspecialchars(($c['u_prenom']??'').' '.($c['u_nom']??''))?><br><small style="color:#aaa"><?=htmlspecialchars($c['email']??'')?></small></td>
            <td><?=date('d/m/Y H:i', strtotime($c['date_commande']))?></td>
            <td><strong><?=number_format($c['montant'],2,',',' ')?> €</strong></td>
            <td><span class="badge <?=$bc?>"><?=$c['statut']?></span></td>
            <td>
                <a href="?detail=<?=$c['id_commande']?><?=$filtre?"&statut=$filtre":''?>" class="btn-sm btn-view"><i class="fas fa-eye"></i> Voir</a>
                <form method="POST" style="display:inline-flex;gap:4px;margin-top:4px;">
                    <input type="hidden" name="id_commande" value="<?=$c['id_commande']?>">
                    <select name="statut">
                        <option value="en_attente" <?=$c['statut']==='en_attente'?'selected':''?>>En attente</option>
                        <option value="confirmee"  <?=$c['statut']==='confirmee'?'selected':''?>>Confirmée</option>
                        <option value="livree"     <?=$c['statut']==='livree'?'selected':''?>>Livrée</option>
                        <option value="annulee"    <?=$c['statut']==='annulee'?'selected':''?>>Annulée</option>
                    </select>
                    <button type="submit" class="btn-save">✓</button>
                </form>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal détail -->
<?php if ($detail && $commande_detail): ?>
<div class="modal open" id="modal">
    <div class="modal-box">
        <button class="modal-close" onclick="document.getElementById('modal').classList.remove('open')">✕ Fermer</button>
        <h3>Commande #<?=$commande_detail['id_commande']?></h3>
        <p style="color:#555;margin-bottom:15px;">
            Client : <strong><?=htmlspecialchars(($commande_detail['prenom']??'').' '.($commande_detail['nom']??''))?></strong><br>
            Email : <?=htmlspecialchars($commande_detail['email']??'')?><br>
            Date : <?=date('d/m/Y H:i', strtotime($commande_detail['date_commande']))?><br>
            Total : <strong><?=number_format($commande_detail['montant'],2,',',' ')?> €</strong>
        </p>
        <h4 style="margin-bottom:10px">Articles :</h4>
        <?php foreach ($detail as $d): ?>
        <div class="item-row">
            <?php if ($d['image_url']): ?>
            <img src="<?=htmlspecialchars($d['image_url'])?>" class="item-img" onerror="this.style.display='none'">
            <?php endif; ?>
            <div style="flex:1">
                <strong><?=htmlspecialchars($d['p_nom']??'Produit #'.$d['id_produit'])?></strong><br>
                <small>Qté : <?=$d['quantite']?> × <?=number_format($d['prix_unitaire'],2,',',' ')?> €</small>
            </div>
            <strong><?=number_format($d['quantite']*$d['prix_unitaire'],2,',',' ')?> €</strong>
        </div>
        <?php endforeach; ?>
        <a href="commandes.php" style="display:inline-block;margin-top:15px;background:#1a1a2e;color:#c9a227;padding:8px 18px;border-radius:6px;text-decoration:none;">← Retour</a>
    </div>
</div>
<?php endif; ?>
</div>
</div>
</body>
</html>