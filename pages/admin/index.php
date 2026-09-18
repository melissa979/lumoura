<?php
require_once __DIR__ . '/auth_admin.php';


// Statistiques
try {
    $nb_produits   = $pdo->query("SELECT COUNT(*) FROM produits")->fetchColumn();
    $nb_commandes  = $pdo->query("SELECT COUNT(*) FROM commandes")->fetchColumn();
    $nb_users      = $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
    $ca_total      = $pdo->query("SELECT SUM(montant) FROM commandes WHERE statut != 'annulee'")->fetchColumn();
    $last_products = $pdo->query("SELECT * FROM produits ORDER BY id_produit DESC LIMIT 5")->fetchAll();
    $last_orders   = $pdo->query("SELECT c.*, u.nom, u.prenom FROM commandes c LEFT JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur ORDER BY c.date_commande DESC LIMIT 5")->fetchAll();
} catch (Exception $e) {
    $nb_produits = $nb_commandes = $nb_users = $ca_total = 0;
    $last_products = $last_orders = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin – Lumoura Joaillerie</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',sans-serif; background:#f0f2f5; display:flex; min-height:100vh; }

/* ── SIDEBAR ── */
.sidebar {
    width:240px; background:#1a1a2e; color:#ccc;
    display:flex; flex-direction:column; padding:0; flex-shrink:0;
}
.sidebar-logo {
    padding:25px 20px; background:#16213e;
    color:#c9a227; font-size:1.2rem; font-weight:700; letter-spacing:1px;
    border-bottom:1px solid #0f3460;
}
.sidebar-logo span { display:block; font-size:.7rem; color:#888; font-weight:400; margin-top:3px; }
.sidebar nav { flex:1; padding:15px 0; }
.sidebar nav a {
    display:flex; align-items:center; gap:12px;
    padding:12px 20px; color:#aaa; text-decoration:none;
    font-size:.9rem; transition:all .2s;
}
.sidebar nav a:hover, .sidebar nav a.active {
    background:#0f3460; color:#c9a227; border-left:3px solid #c9a227;
}
.sidebar nav a i { width:18px; text-align:center; }
.sidebar-footer { padding:15px 20px; border-top:1px solid #0f3460; font-size:.8rem; color:#666; }

/* ── MAIN ── */
.main { flex:1; display:flex; flex-direction:column; }
.topbar {
    background:#fff; padding:15px 30px;
    display:flex; justify-content:space-between; align-items:center;
    box-shadow:0 2px 4px rgba(0,0,0,.06);
}
.topbar h1 { font-size:1.3rem; color:#1a1a2e; }
.topbar .user-info { display:flex; align-items:center; gap:15px; color:#555; font-size:.9rem; }
.topbar .btn-logout {
    background:#e74c3c; color:#fff; border:none; padding:7px 15px;
    border-radius:5px; cursor:pointer; font-size:.85rem; text-decoration:none;
}
.content { padding:30px; flex:1; }

/* ── CARDS STATS ── */
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:20px; margin-bottom:30px; }
.stat-card {
    background:#fff; padding:20px; border-radius:10px;
    box-shadow:0 2px 8px rgba(0,0,0,.07);
    display:flex; align-items:center; gap:15px;
}
.stat-icon { width:55px; height:55px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.4rem; }
.stat-icon.gold   { background:#fff3cd; color:#c9a227; }
.stat-icon.blue   { background:#d1ecf1; color:#0c5460; }
.stat-icon.green  { background:#d4edda; color:#155724; }
.stat-icon.purple { background:#e2d9f3; color:#6f42c1; }
.stat-info h3 { font-size:1.6rem; color:#1a1a2e; }
.stat-info p  { font-size:.8rem; color:#888; margin-top:2px; }

/* ── TABLES ── */
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media(max-width:900px){ .grid-2 { grid-template-columns:1fr; } }
.table-card { background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.07); overflow:hidden; }
.table-card-header {
    padding:15px 20px; background:#1a1a2e; color:#c9a227;
    display:flex; justify-content:space-between; align-items:center;
}
.table-card-header a { color:#c9a227; font-size:.8rem; }
table { width:100%; border-collapse:collapse; }
th,td { padding:11px 15px; text-align:left; font-size:.85rem; border-bottom:1px solid #f0f0f0; }
th { background:#f8f9fa; color:#555; font-weight:600; }
td { color:#333; }
tr:last-child td { border-bottom:none; }
.badge {
    padding:3px 9px; border-radius:20px; font-size:.75rem; font-weight:600;
}
.badge-green  { background:#d4edda; color:#155724; }
.badge-yellow { background:#fff3cd; color:#856404; }
.badge-red    { background:#f8d7da; color:#721c24; }
.badge-blue   { background:#d1ecf1; color:#0c5460; }

.btn-sm {
    padding:4px 10px; border-radius:4px; border:none; cursor:pointer;
    font-size:.78rem; text-decoration:none; display:inline-block;
}
.btn-edit  { background:#c9a227; color:#fff; }
.btn-del   { background:#e74c3c; color:#fff; margin-left:4px; }
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">💎 LUMOURA <span>Administration</span></div>
    <nav>
        <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="produits.php"><i class="fas fa-gem"></i> Produits</a>
        <a href="categories.php"><i class="fas fa-tags"></i> Catégories</a>
        <a href="commandes.php"><i class="fas fa-shopping-bag"></i> Commandes</a>
        <a href="utilisateurs.php"><i class="fas fa-users"></i> Utilisateurs</a>
    </nav>
    <div class="sidebar-footer">Lumoura Admin v1.0</div>
</aside>

<div class="main">
    <div class="topbar">
        <h1>Tableau de bord</h1>
        <div class="user-info">
            <i class="fas fa-user-circle"></i> Admin
            <a href="../deconnexion.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </div>
    </div>

    <div class="content">

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fas fa-gem"></i></div>
                <div class="stat-info"><h3><?= $nb_produits ?></h3><p>Produits</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-shopping-bag"></i></div>
                <div class="stat-info"><h3><?= $nb_commandes ?></h3><p>Commandes</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-users"></i></div>
                <div class="stat-info"><h3><?= $nb_users ?></h3><p>Clients</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-euro-sign"></i></div>
                <div class="stat-info"><h3><?= number_format($ca_total ?? 0, 0, ',', ' ') ?> €</h3><p>Chiffre d'affaires</p></div>
            </div>
        </div>

        <!-- Tables -->
        <div class="grid-2">

            <!-- Derniers produits -->
            <div class="table-card">
                <div class="table-card-header">
                    <span><i class="fas fa-gem"></i> Derniers produits</span>
                    <a href="produits.php">Voir tout →</a>
                </div>
                <table>
                    <thead><tr><th>Nom</th><th>Prix</th><th>Stock</th><th></th></tr></thead>
                    <tbody>
                    <?php if (empty($last_products)): ?>
                        <tr><td colspan="4" style="text-align:center;color:#aaa;padding:20px;">Aucun produit</td></tr>
                    <?php else: foreach ($last_products as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['nom'] ?? '') ?></td>
                            <td><?= number_format($p['prix'] ?? 0, 2, ',', ' ') ?> €</td>
                            <td><?= $p['stock'] ?? 0 ?></td>
                            <td><a href="modifier_produit.php?id=<?= $p['id_produit'] ?>" class="btn-sm btn-edit">Éditer</a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Dernières commandes -->
            <div class="table-card">
                <div class="table-card-header">
                    <span><i class="fas fa-shopping-bag"></i> Dernières commandes</span>
                    <a href="commandes.php">Voir tout →</a>
                </div>
                <table>
                    <thead><tr><th>Client</th><th>Total</th><th>Statut</th></tr></thead>
                    <tbody>
                    <?php if (empty($last_orders)): ?>
                        <tr><td colspan="3" style="text-align:center;color:#aaa;padding:20px;">Aucune commande</td></tr>
                    <?php else: foreach ($last_orders as $o):
                        $badge = match($o['statut'] ?? '') {
                            'livree','confirmee' => 'badge-green',
                            'en_attente'         => 'badge-yellow',
                            'annulee'            => 'badge-red',
                            default              => 'badge-blue'
                        };
                    ?>
                        <tr>
                            <td><?= htmlspecialchars(($o['prenom'] ?? '').' '.($o['nom'] ?? '')) ?></td>
                            <td><?= number_format($o['montant'] ?? 0, 2, ',', ' ') ?> €</td>
                            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($o['statut'] ?? '') ?></span></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
</body>
</html>