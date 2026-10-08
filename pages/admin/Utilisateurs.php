<?php
/**
 * Gestion des utilisateurs et clients
 * Fichier : pages/admin/Utilisateurs.php
 */
require_once __DIR__ . '/auth_admin.php';

function userAdminE($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$flash = null;
$search = trim((string) ($_GET['search'] ?? ''));
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

if (empty($_SESSION['users_csrf'])) {
    $_SESSION['users_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['users_csrf'];

// Suppression protégée par POST. Un compte admin ou un client ayant des commandes ne peut pas être supprimé.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $idUser = (int) ($_POST['id_utilisateur'] ?? 0);

    try {
        if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
            throw new Exception('Session expirée, veuillez réessayer.');
        }
        if ($idUser <= 0) {
            throw new Exception('Utilisateur invalide.');
        }
        if ($idUser === $currentUserId) {
            throw new Exception('Vous ne pouvez pas supprimer votre propre compte administrateur.');
        }

        $stmt = $pdo->prepare('SELECT role, prenom, nom FROM utilisateurs WHERE id_utilisateur = ?');
        $stmt->execute([$idUser]);
        $userToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userToDelete) {
            throw new Exception('Utilisateur introuvable.');
        }
        if (($userToDelete['role'] ?? 'client') === 'admin') {
            throw new Exception('Un compte administrateur ne peut pas être supprimé depuis cette page.');
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM commandes WHERE id_utilisateur = ?');
        $stmt->execute([$idUser]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new Exception('Ce client possède des commandes. Désactivez-le plutôt que de supprimer son historique.');
        }

        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM panier WHERE id_utilisateur = ?')->execute([$idUser]);
        $pdo->prepare('DELETE FROM favori WHERE id_utilisateur = ?')->execute([$idUser]);
        $pdo->prepare('DELETE FROM utilisateurs WHERE id_utilisateur = ?')->execute([$idUser]);
        $pdo->commit();

        $flash = ['type' => 'success', 'text' => 'Utilisateur supprimé avec succès.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $flash = ['type' => 'error', 'text' => $e->getMessage()];
    }
}

$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR u.telephone LIKE ?';
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
}

try {
    $stmt = $pdo->prepare(''
        . 'SELECT u.id_utilisateur, u.email, u.nom, u.prenom, u.telephone, u.role, u.statut, u.date_inscription, '
        . 'COUNT(DISTINCT c.id_commande) AS nb_commandes '
        . 'FROM utilisateurs u '
        . 'LEFT JOIN commandes c ON c.id_utilisateur = u.id_utilisateur '
        . $where . ' '
        . 'GROUP BY u.id_utilisateur, u.email, u.nom, u.prenom, u.telephone, u.role, u.statut, u.date_inscription '
        . 'ORDER BY u.date_inscription DESC'
    );
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $users = [];
    $flash = ['type' => 'error', 'text' => 'Impossible de charger les utilisateurs.'];
}

$nbUsers = count($users);
$nbClients = 0;
$nbActifs = 0;
$nbAdmins = 0;
foreach ($users as $u) {
    if (($u['role'] ?? 'client') === 'admin') $nbAdmins++;
    else $nbClients++;
    if (($u['statut'] ?? 'actif') === 'actif') $nbActifs++;
}

$pageTitle = "Utilisateurs - Admin Éclat d'Or";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= userAdminE($pageTitle) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root { --gold:#d4af37; --gold-dark:#a98216; --brown:#3d2b28; --dark:#211816; --line:#ece3d9; --muted:#8b7b73; --bg:#f7f2ec; }
* { box-sizing:border-box; }
body { margin:0; min-height:100vh; display:flex; background:var(--bg); color:var(--brown); font-family:'Montserrat',Arial,sans-serif; font-size:14px; }
a { color:inherit; text-decoration:none; }
.admin-sidebar { width:245px; min-height:100vh; flex-shrink:0; display:flex; flex-direction:column; position:sticky; top:0; background:linear-gradient(180deg,#2a1e1b,var(--dark)); color:#cdbfb6; }
.admin-logo { padding:28px 24px 24px; border-bottom:1px solid rgba(255,255,255,.07); }
.admin-logo strong { display:block; color:#fff; font-family:'Playfair Display',serif; font-size:21px; letter-spacing:2px; }
.admin-logo span { color:var(--gold); font-size:10px; letter-spacing:3px; text-transform:uppercase; }
.admin-nav { flex:1; padding:18px 12px; }
.admin-nav a,.admin-footer a { display:flex; align-items:center; gap:12px; padding:12px 14px; margin-bottom:4px; border-radius:10px; font-size:13px; font-weight:600; transition:.2s; }
.admin-nav a i,.admin-footer a i { width:18px; text-align:center; color:#8f7f75; }
.admin-nav a:hover,.admin-footer a:hover { background:rgba(255,255,255,.06); color:#fff; }
.admin-nav a.active { background:rgba(212,175,55,.14); color:#fff; }
.admin-nav a.active i { color:var(--gold); }
.admin-footer { padding:16px 12px; border-top:1px solid rgba(255,255,255,.07); }
.admin-main { flex:1; min-width:0; padding:32px 36px 60px; }
.admin-topbar { display:flex; align-items:flex-end; justify-content:space-between; gap:20px; margin-bottom:24px; flex-wrap:wrap; }
.admin-kicker { color:var(--gold-dark); font-size:11px; font-weight:800; letter-spacing:2px; text-transform:uppercase; }
.admin-topbar h1 { margin:4px 0 0; font-family:'Playfair Display',serif; font-size:34px; }
.admin-topbar p { margin:8px 0 0; color:var(--muted); }
.shop-link { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border:1px solid var(--line); border-radius:10px; background:#fff; font-size:12px; font-weight:700; }
.shop-link:hover { border-color:var(--gold); color:var(--gold-dark); }
.flash { display:flex; align-items:center; gap:10px; padding:14px 18px; margin-bottom:20px; border-radius:12px; font-size:13px; font-weight:600; }
.flash.success { background:#effcf5; border:1px solid #a7e8c8; color:#12633f; }
.flash.error { background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; }
.kpis { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; }
.kpi { position:relative; padding:20px; border:1px solid var(--line); border-radius:18px; background:#fff; box-shadow:0 8px 20px rgba(61,43,40,.05); }
.kpi i { position:absolute; top:18px; right:18px; width:40px; height:40px; display:grid; place-items:center; border-radius:12px; background:#fbf3df; color:var(--gold-dark); }
.kpi span { color:var(--muted); font-size:12px; font-weight:600; }
.kpi strong { display:block; margin-top:8px; font-size:24px; }
.kpi.highlight { border:0; background:linear-gradient(135deg,var(--brown),#5e4135); color:#fff; }
.kpi.highlight span { color:rgba(255,255,255,.75); }
.kpi.highlight i { background:rgba(255,255,255,.14); color:#f5d56c; }
.search-box { display:flex; align-items:center; gap:10px; padding:0 15px; margin-bottom:18px; border:1px solid var(--line); border-radius:14px; background:#fff; box-shadow:0 8px 20px rgba(61,43,40,.04); }
.search-box i { color:var(--muted); }
.search-box input { flex:1; border:0; outline:0; padding:14px 0; font:inherit; }
.search-box button { padding:10px 17px; border:0; border-radius:9px; background:var(--gold); color:#fff; font-size:12px; font-weight:800; cursor:pointer; }
.search-box button:hover { background:var(--gold-dark); }
.reset-link { color:var(--muted); font-size:12px; font-weight:700; }
.table-card { overflow:hidden; border:1px solid var(--line); border-radius:18px; background:#fff; box-shadow:0 8px 20px rgba(61,43,40,.05); }
.table-header { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:16px 20px; border-bottom:1px solid var(--line); background:#fffaf3; }
.table-header h2 { margin:0; font-family:'Playfair Display',serif; font-size:20px; }
.table-header span { color:var(--muted); font-size:12px; }
table { width:100%; border-collapse:collapse; }
th { padding:13px 16px; color:var(--muted); background:#fbf7f2; border-bottom:1px solid var(--line); font-size:10px; font-weight:700; letter-spacing:1px; text-align:left; text-transform:uppercase; }
td { padding:14px 16px; border-bottom:1px solid #f3ece4; vertical-align:middle; font-size:12px; }
tr:last-child td { border-bottom:0; }
.user-cell { display:flex; align-items:center; gap:10px; }
.avatar { width:40px; height:40px; display:grid; place-items:center; flex-shrink:0; border-radius:50%; background:var(--gold); color:#fff; font-weight:800; }
.user-cell strong { display:block; font-size:13px; }
.user-cell span { display:block; color:var(--muted); font-size:10px; margin-top:3px; }
.muted { color:var(--muted); }
.role-badge,.status-badge { display:inline-flex; padding:5px 10px; border-radius:20px; font-size:10px; font-weight:800; }
.role-admin { background:#fce7f3; color:#9d174d; }
.role-client { background:#e0f2fe; color:#075985; }
.status-active { background:#d1fae5; color:#065f46; }
.status-inactive { background:#fee2e2; color:#991b1b; }
.count-badge { color:var(--gold-dark); font-weight:800; }
.delete-form { margin:0; }
.delete-btn { width:32px; height:32px; display:grid; place-items:center; border:0; border-radius:8px; background:#fee2e2; color:#b42318; cursor:pointer; }
.delete-btn:hover { background:#b42318; color:#fff; }
.delete-btn:disabled { opacity:.35; cursor:not-allowed; }
.table-empty { padding:50px 20px; color:var(--muted); text-align:center; }
.table-empty i { display:block; margin-bottom:10px; color:var(--gold); font-size:28px; }
@media (max-width:900px) { .admin-sidebar { width:72px; } .admin-logo span,.admin-nav span,.admin-footer span { display:none; } .admin-logo strong { font-size:14px; letter-spacing:0; } .admin-main { padding:24px 20px 50px; } }
@media (max-width:680px) { .admin-main { padding:20px 12px 45px; } .kpis { gap:10px; } .kpi { padding:14px; } .kpi i { display:none; } .kpi strong { font-size:20px; } .hide-mobile { display:none; } th,td { padding:11px 9px; } }
</style>
</head>
<body>
<aside class="admin-sidebar">
    <div class="admin-logo"><strong>ÉCLAT D'OR</strong><span>Administration</span></div>
    <nav class="admin-nav">
        <a href="admin.php"><i class="fas fa-chart-pie"></i><span>Tableau de bord</span></a>
        <a href="Commandes.php"><i class="fas fa-receipt"></i><span>Commandes</span></a>
        <a href="Produits.php"><i class="fas fa-gem"></i><span>Produits</span></a>
        <a href="Categories.php"><i class="fas fa-layer-group"></i><span>Catégories</span></a>
        <a href="Utilisateurs.php" class="active"><i class="fas fa-users"></i><span>Clients</span></a>
    </nav>
    <div class="admin-footer">
        <a href="../../index.php"><i class="fas fa-store"></i><span>Voir la boutique</span></a>
        <a href="../deconnexion.php"><i class="fas fa-sign-out-alt"></i><span>Déconnexion</span></a>
    </div>
</aside>

<main class="admin-main">
    <header class="admin-topbar">
        <div><span class="admin-kicker">Administration</span><h1>Clients & utilisateurs</h1><p>Gérez les comptes clients sans perdre leur historique.</p></div>
        <a href="../../index.php" class="shop-link"><i class="fas fa-store"></i> Voir la boutique</a>
    </header>

    <?php if ($flash): ?><div class="flash <?= userAdminE($flash['type']) ?>"><i class="fas <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i><?= userAdminE($flash['text']) ?></div><?php endif; ?>

    <section class="kpis">
        <div class="kpi highlight"><i class="fas fa-users"></i><span>Clients</span><strong><?= $nbClients ?></strong></div>
        <div class="kpi"><i class="fas fa-user-check"></i><span>Comptes actifs</span><strong><?= $nbActifs ?></strong></div>
        <div class="kpi"><i class="fas fa-user-shield"></i><span>Administrateurs</span><strong><?= $nbAdmins ?></strong></div>
    </section>

    <form method="GET" class="search-box">
        <i class="fas fa-search"></i>
        <input type="search" name="search" value="<?= userAdminE($search) ?>" placeholder="Rechercher par nom, prénom, email ou téléphone…">
        <button type="submit">Rechercher</button>
        <?php if ($search): ?><a class="reset-link" href="Utilisateurs.php">Réinitialiser</a><?php endif; ?>
    </form>

    <section class="table-card">
        <div class="table-header"><h2><i class="fas fa-users"></i> Utilisateurs</h2><span><?= $nbUsers ?> résultat<?= $nbUsers > 1 ? 's' : '' ?></span></div>
        <?php if (empty($users)): ?>
            <div class="table-empty"><i class="fas fa-user-slash"></i>Aucun utilisateur trouvé.</div>
        <?php else: ?>
            <table>
                <thead><tr><th>Utilisateur</th><th>Email</th><th class="hide-mobile">Téléphone</th><th>Rôle</th><th>Statut</th><th>Commandes</th><th>Inscription</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u):
                    $initial = strtoupper(substr((string) ($u['prenom'] ?: $u['nom'] ?: '?'), 0, 1));
                    $isAdmin = ($u['role'] ?? 'client') === 'admin';
                    $hasOrders = (int) $u['nb_commandes'] > 0;
                ?>
                    <tr>
                        <td><div class="user-cell"><div class="avatar"><?= userAdminE($initial) ?></div><div><strong><?= userAdminE(trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? ''))) ?></strong><span>ID #<?= (int) $u['id_utilisateur'] ?></span></div></div></td>
                        <td><?= userAdminE($u['email']) ?></td>
                        <td class="hide-mobile"><?= userAdminE($u['telephone'] ?: '—') ?></td>
                        <td><span class="role-badge <?= $isAdmin ? 'role-admin' : 'role-client' ?>"><?= $isAdmin ? 'Admin' : 'Client' ?></span></td>
                        <td><span class="status-badge <?= ($u['statut'] ?? 'actif') === 'actif' ? 'status-active' : 'status-inactive' ?>"><?= ($u['statut'] ?? 'actif') === 'actif' ? 'Actif' : 'Inactif' ?></span></td>
                        <td class="count-badge"><?= $hasOrders ? (int) $u['nb_commandes'] : '0' ?></td>
                        <td class="muted"><?= !empty($u['date_inscription']) ? date('d/m/Y', strtotime($u['date_inscription'])) : '—' ?></td>
                        <td>
                            <form method="POST" class="delete-form" onsubmit="return confirm('Supprimer cet utilisateur ? Cette action est définitive.');">
                                <input type="hidden" name="csrf" value="<?= userAdminE($csrf) ?>">
                                <input type="hidden" name="id_utilisateur" value="<?= (int) $u['id_utilisateur'] ?>">
                                <button type="submit" name="delete_user" class="delete-btn" title="Supprimer" <?= $isAdmin || $hasOrders || (int) $u['id_utilisateur'] === $currentUserId ? 'disabled' : '' ?>><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
