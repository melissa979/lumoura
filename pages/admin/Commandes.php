<?php
/**
 * Admin - Gestion des commandes
 * Fichier : pages/admin/Commandes.php
 */
require_once __DIR__ . '/auth_admin.php'; // session + $pdo + contrôle du rôle admin

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function euroAdmin($v) { return number_format((float) $v, 2, ',', ' ') . ' €'; }

// Jeton anti-falsification des formulaires (CSRF)
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['admin_csrf'];

$STATUTS = [
    'en attente' => ['label' => 'En attente', 'class' => 'attente',  'icon' => 'fa-hourglass-half'],
    'payee'      => ['label' => 'Payée',      'class' => 'payee',    'icon' => 'fa-credit-card'],
    'expediee'   => ['label' => 'Expédiée',   'class' => 'expediee', 'icon' => 'fa-truck'],
    'livree'     => ['label' => 'Livrée',     'class' => 'livree',   'icon' => 'fa-gift'],
    'annulee'    => ['label' => 'Annulée',    'class' => 'annulee',  'icon' => 'fa-ban'],
];
$ETAPES = ['en attente', 'payee', 'expediee', 'livree'];
$MODES  = ['standard' => 'Standard', 'express' => 'Express', 'premium' => 'Premium'];

// Paramètres d'URL conservés après une action
$filtre = isset($_GET['statut']) && isset($STATUTS[$_GET['statut']]) ? $_GET['statut'] : '';
$q      = trim((string) ($_GET['q'] ?? ''));
$page   = max(1, (int) ($_GET['page'] ?? 1));
$detailId = (int) ($_GET['detail'] ?? 0);

function adminUrl(array $changes = []) {
    global $filtre, $q, $page, $detailId;
    $p = array_merge(['statut' => $filtre, 'q' => $q, 'page' => $page, 'detail' => $detailId], $changes);
    $p = array_filter($p, fn($v) => $v !== '' && $v !== 0 && $v !== null);
    return 'Commandes.php' . ($p ? '?' . http_build_query($p) : '');
}

/* ---------------------------------------------------------------
   Changement de statut
--------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changer_statut'])) {
    $id      = (int) ($_POST['id_commande'] ?? 0);
    $nouveau = (string) ($_POST['statut'] ?? '');

    try {
        if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
            throw new Exception('Session expirée, veuillez réessayer.');
        }
        if (!isset($STATUTS[$nouveau])) {
            throw new Exception('Statut invalide.');
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT statut, numero_commande FROM commandes WHERE id_commande = ? FOR UPDATE');
        $stmt->execute([$id]);
        $cmd = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cmd) {
            throw new Exception('Commande introuvable.');
        }
        $ancien = $cmd['statut'] ?: 'en attente';

        if ($ancien === 'annulee') {
            throw new Exception('Une commande annulée ne peut plus être modifiée.');
        }
        if ($ancien === $nouveau) {
            throw new Exception('La commande a déjà ce statut.');
        }

        // Annulation : les bijoux reviennent en stock
        if ($nouveau === 'annulee') {
            $pdo->prepare('
                UPDATE produits p
                INNER JOIN details_commande d ON d.id_produit = p.id_produit
                SET p.stock = p.stock + d.quantite
                WHERE d.id_commande = ?
            ')->execute([$id]);
        }

        // Paiement : on enregistre la date la première fois
        if ($nouveau === 'payee') {
            $pdo->prepare('UPDATE commandes SET statut = ?, date_paiement = COALESCE(date_paiement, NOW()) WHERE id_commande = ?')
                ->execute([$nouveau, $id]);
        } else {
            $pdo->prepare('UPDATE commandes SET statut = ? WHERE id_commande = ?')
                ->execute([$nouveau, $id]);
        }

        $pdo->commit();

        $msg = 'Commande ' . strtoupper($cmd['numero_commande']) . ' : ' . $STATUTS[$nouveau]['label'] . '.';
        if ($nouveau === 'annulee') {
            $msg .= ' Les articles ont été remis en stock.';
        }
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => $msg];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['admin_flash'] = ['type' => 'err', 'text' => $ex->getMessage()];
    }

    header('Location: ' . adminUrl(['detail' => $id]));
    exit;
}

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

/* ---------------------------------------------------------------
   Statistiques
--------------------------------------------------------------- */
$stats = $pdo->query("
    SELECT
        SUM(DATE(date_commande) = CURDATE())                                         AS jour,
        COALESCE(SUM(CASE WHEN statut <> 'annulee' OR statut IS NULL THEN montant END), 0) AS ca_total,
        COALESCE(SUM(CASE WHEN (statut <> 'annulee' OR statut IS NULL)
              AND DATE_FORMAT(date_commande, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN montant END), 0) AS ca_mois,
        SUM(statut IN ('en attente', 'payee') OR statut IS NULL OR statut = '')       AS a_traiter
    FROM commandes
")->fetch(PDO::FETCH_ASSOC);

$compteurs = ['' => 0];
foreach ($pdo->query("SELECT COALESCE(NULLIF(statut, ''), 'en attente') AS s, COUNT(*) AS n FROM commandes GROUP BY s") as $r) {
    $compteurs[$r['s']] = (int) $r['n'];
    $compteurs[''] += (int) $r['n'];
}

/* ---------------------------------------------------------------
   Liste filtrée + pagination
--------------------------------------------------------------- */
$where = ['1=1'];
$params = [];
if ($filtre === 'en attente') {
    $where[] = "(c.statut = 'en attente' OR c.statut IS NULL OR c.statut = '')";
} elseif ($filtre !== '') {
    $where[] = 'c.statut = :statut';
    $params[':statut'] = $filtre;
}
if ($q !== '') {
    $where[] = '(c.numero_commande LIKE :q1 OR u.nom LIKE :q2 OR u.prenom LIKE :q3 OR u.email LIKE :q4)';
    $like = '%' . $q . '%';
    $params[':q1'] = $params[':q2'] = $params[':q3'] = $params[':q4'] = $like;
}
$whereSql = implode(' AND ', $where);

$parPage = 15;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM commandes c LEFT JOIN utilisateurs u ON u.id_utilisateur = c.id_utilisateur WHERE $whereSql");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$nbPages = max(1, (int) ceil($total / $parPage));
$page = min($page, $nbPages);
$offset = ($page - 1) * $parPage;

$stmt = $pdo->prepare("
    SELECT c.*, u.nom AS u_nom, u.prenom AS u_prenom, u.email AS u_email,
           (SELECT SUM(quantite) FROM details_commande d WHERE d.id_commande = c.id_commande) AS nb_articles
    FROM commandes c
    LEFT JOIN utilisateurs u ON u.id_utilisateur = c.id_utilisateur
    WHERE $whereSql
    ORDER BY c.date_commande DESC
    LIMIT $parPage OFFSET $offset
");
$stmt->execute($params);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------------------------------------------------------------
   Détail d'une commande
--------------------------------------------------------------- */
$detail = null;
$lignes = [];
if ($detailId > 0) {
    $stmt = $pdo->prepare('
        SELECT c.*, u.nom AS u_nom, u.prenom AS u_prenom, u.email AS u_email
        FROM commandes c LEFT JOIN utilisateurs u ON u.id_utilisateur = c.id_utilisateur
        WHERE c.id_commande = ?
    ');
    $stmt->execute([$detailId]);
    $detail = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($detail) {
        $stmt = $pdo->prepare('
            SELECT d.*, p.nom AS p_nom, p.image_url, p.reference, p.stock
            FROM details_commande d LEFT JOIN produits p ON p.id_produit = d.id_produit
            WHERE d.id_commande = ?
        ');
        $stmt->execute([$detailId]);
        $lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Commandes - Admin Éclat d'Or</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root { --gold:#d4af37; --gold-dark:#a98216; --brown:#3d2b28; --dark:#211816; --line:#ece3d9; --muted:#8b7b73; --bg:#f7f2ec; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Montserrat',sans-serif; background:var(--bg); color:var(--brown); display:flex; min-height:100vh; font-size:14px; }
a { color:inherit; text-decoration:none; }

/* Barre latérale */
.sidebar { width:245px; flex-shrink:0; background:linear-gradient(180deg,#2a1e1b,var(--dark)); color:#cdbfb6;
    display:flex; flex-direction:column; position:sticky; top:0; height:100vh; }
.sidebar-logo { padding:28px 24px 24px; border-bottom:1px solid rgba(255,255,255,.07); }
.sidebar-logo strong { display:block; color:#fff; font-family:'Playfair Display',serif; font-size:21px; letter-spacing:2px; }
.sidebar-logo span { color:var(--gold); font-size:10px; letter-spacing:3px; text-transform:uppercase; }
.sidebar nav { padding:18px 12px; flex:1; }
.sidebar nav a { display:flex; align-items:center; gap:12px; padding:12px 14px; margin-bottom:4px; border-radius:10px;
    font-size:13px; font-weight:600; transition:background .2s, color .2s; }
.sidebar nav a i { width:18px; text-align:center; color:#8f7f75; }
.sidebar nav a:hover { background:rgba(255,255,255,.06); color:#fff; }
.sidebar nav a.active { background:rgba(212,175,55,.14); color:#fff; }
.sidebar nav a.active i { color:var(--gold); }
.sidebar-foot { padding:16px 12px; border-top:1px solid rgba(255,255,255,.07); }
.sidebar-foot a { display:flex; gap:10px; align-items:center; padding:10px 14px; font-size:12px; border-radius:10px; }
.sidebar-foot a:hover { background:rgba(255,255,255,.06); color:#fff; }

/* Contenu */
.main { flex:1; min-width:0; padding:32px 36px 60px; }
.topbar { display:flex; justify-content:space-between; align-items:flex-end; gap:20px; margin-bottom:26px; flex-wrap:wrap; }
.topbar small { color:var(--gold-dark); font-size:11px; font-weight:800; letter-spacing:2px; text-transform:uppercase; }
.topbar h1 { font-family:'Playfair Display',serif; font-size:32px; margin-top:4px; }
.search { display:flex; align-items:center; gap:8px; padding:0 14px; background:#fff; border:1px solid var(--line); border-radius:12px; min-width:300px; }
.search i { color:var(--muted); }
.search input { border:0; outline:0; padding:12px 0; width:100%; font:inherit; background:none; }

.flash { display:flex; gap:10px; align-items:center; padding:14px 18px; border-radius:12px; margin-bottom:22px; font-weight:600; }
.flash.ok { background:#effcf5; border:1px solid #a7e8c8; color:#12633f; }
.flash.err { background:#fef2f2; border:1px solid #fca5a5; color:#991b1b; }

.kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
.kpi { position:relative; overflow:hidden; padding:20px; background:#fff; border:1px solid var(--line); border-radius:18px; box-shadow:0 8px 20px rgba(61,43,40,.05); }
.kpi i { position:absolute; right:18px; top:18px; width:40px; height:40px; display:grid; place-items:center; border-radius:12px; background:#fbf3df; color:var(--gold-dark); }
.kpi span { color:var(--muted); font-size:12px; font-weight:600; }
.kpi strong { display:block; margin-top:8px; font-size:24px; }
.kpi.highlight { background:linear-gradient(135deg,var(--brown),#5e4135); color:#fff; border:0; }
.kpi.highlight span { color:rgba(255,255,255,.75); }
.kpi.highlight i { background:rgba(255,255,255,.14); color:#f5d56c; }

.tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.tabs a { display:inline-flex; align-items:center; gap:8px; padding:9px 15px; border-radius:30px; background:#fff; border:1px solid var(--line); font-size:12px; font-weight:700; transition:all .2s; }
.tabs a b { padding:1px 8px; border-radius:20px; background:#f3ece4; font-size:11px; }
.tabs a:hover, .tabs a.active { background:var(--brown); border-color:var(--brown); color:#fff; }
.tabs a.active b, .tabs a:hover b { background:rgba(255,255,255,.18); }

.layout { display:grid; grid-template-columns:minmax(0,1fr); gap:20px; align-items:start; }
.layout.with-detail { grid-template-columns:minmax(0,1fr) 400px; }

.table-card { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; box-shadow:0 8px 20px rgba(61,43,40,.05); }
table { width:100%; border-collapse:collapse; }
th { padding:13px 16px; background:#fbf7f2; color:var(--muted); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1px; text-align:left; }
td { padding:14px 16px; border-top:1px solid #f3ece4; vertical-align:middle; }
tr.row { cursor:pointer; transition:background .15s; }
tr.row:hover { background:#fffaf2; }
tr.row.selected { background:#fdf4de; box-shadow:inset 3px 0 0 var(--gold); }
.num { font-weight:800; letter-spacing:.4px; }
.sub { display:block; color:var(--muted); font-size:12px; margin-top:2px; }
.amount { font-weight:800; color:var(--gold-dark); white-space:nowrap; }
.badge { display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border-radius:20px; font-size:11px; font-weight:800; white-space:nowrap; }
.b-attente { background:#fff3cd; color:#856404; }
.b-payee { background:#e0f2fe; color:#075985; }
.b-expediee { background:#dbeafe; color:#1e40af; }
.b-livree { background:#d1fae5; color:#065f46; }
.b-annulee { background:#fee2e2; color:#991b1b; }
.empty { padding:50px 20px; text-align:center; color:var(--muted); }
.empty i { font-size:30px; color:var(--gold); margin-bottom:10px; display:block; }

.pagination { display:flex; justify-content:center; gap:6px; padding:16px; border-top:1px solid #f3ece4; }
.pagination a, .pagination span { min-width:34px; height:34px; display:grid; place-items:center; padding:0 8px; border-radius:9px; border:1px solid var(--line); font-size:12px; font-weight:700; }
.pagination span { background:var(--brown); color:#fff; border-color:var(--brown); }

/* Panneau de détail */
.detail { position:sticky; top:20px; background:#fff; border:1px solid var(--line); border-radius:18px; box-shadow:0 14px 34px rgba(61,43,40,.1); overflow:hidden; animation:slideIn .3s ease; }
@keyframes slideIn { from { opacity:0; transform:translateX(16px); } to { opacity:1; transform:none; } }
.detail-head { padding:20px 22px; background:linear-gradient(115deg,#f1e8e1,#b9a49a 55%,#5a3825); color:#fff; position:relative; }
.detail-head .close { position:absolute; right:16px; top:16px; width:30px; height:30px; display:grid; place-items:center; border-radius:50%; background:rgba(255,255,255,.2); }
.detail-head small { font-size:11px; letter-spacing:1.5px; text-transform:uppercase; color:#fff4d5; font-weight:700; }
.detail-head h2 { font-family:'Playfair Display',serif; font-size:22px; margin:4px 0 8px; }
.detail-body { padding:20px 22px; max-height:calc(100vh - 190px); overflow-y:auto; }
.block { margin-bottom:20px; }
.block h3 { font-size:11px; color:var(--muted); text-transform:uppercase; letter-spacing:1.2px; margin-bottom:10px; }
.block p { margin-bottom:5px; line-height:1.5; }
.block i.ico { width:16px; color:var(--gold); margin-right:6px; }

.track { display:flex; margin:6px 0 4px; }
.track div { flex:1; position:relative; padding-top:22px; text-align:center; font-size:11px; color:#b0a299; }
.track div::after { content:''; position:absolute; top:0; left:50%; width:14px; height:14px; margin-left:-7px; border-radius:50%; background:#eadfd3; z-index:1; }
.track div::before { content:''; position:absolute; top:6px; left:-50%; width:100%; height:2px; background:#eadfd3; }
.track div:first-child::before { display:none; }
.track div.done { color:var(--brown); font-weight:700; }
.track div.done::after, .track div.done::before { background:var(--gold); }

.item { display:flex; gap:12px; align-items:center; padding:9px 0; border-bottom:1px solid #f3ece4; }
.item img { width:46px; height:46px; border-radius:10px; object-fit:cover; background:#eee4da; }
.item div { flex:1; min-width:0; }
.item strong { display:block; font-size:13px; }
.item b { color:var(--gold-dark); font-size:13px; white-space:nowrap; }
.totals p { display:flex; justify-content:space-between; }
.totals .grand { margin-top:8px; padding-top:10px; border-top:2px solid var(--brown); font-weight:800; font-size:16px; }
.totals .grand span:last-child { color:var(--gold-dark); }

.actions { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.actions form { margin:0; }
.actions button { width:100%; padding:11px 10px; border-radius:10px; border:1px solid var(--line); background:#fff; font:inherit; font-size:12px; font-weight:800; color:var(--brown); cursor:pointer; transition:all .2s; display:flex; align-items:center; justify-content:center; gap:7px; }
.actions button:hover { border-color:var(--gold); background:#fffaf0; }
.actions button.next { grid-column:1 / -1; background:linear-gradient(135deg,var(--brown),#5e4135); color:#fff; border:0; padding:13px; font-size:13px; }
.actions button.next:hover { transform:translateY(-2px); }
.actions button.danger { color:#b42318; }
.actions button.danger:hover { border-color:#fca5a5; background:#fef2f2; }
.locked { padding:12px 14px; border-radius:10px; background:#f8f4ef; color:var(--muted); font-size:12px; }

@media (max-width:1250px) { .layout.with-detail { grid-template-columns:1fr; } .detail { position:static; } .detail-body { max-height:none; } }
@media (max-width:1000px) { .kpis { grid-template-columns:repeat(2,1fr); } .sidebar { width:72px; } .sidebar-logo span, .sidebar nav a span, .sidebar-foot a span { display:none; } .sidebar-logo strong { font-size:14px; letter-spacing:0; } }
@media (max-width:700px) { .main { padding:22px 16px; } .search { min-width:0; width:100%; } .col-hide { display:none; } }
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo"><strong>ÉCLAT D'OR</strong><span>Administration</span></div>
    <nav>
        <a href="admin.php"><i class="fas fa-chart-pie"></i><span>Tableau de bord</span></a>
        <a href="Commandes.php" class="active"><i class="fas fa-receipt"></i><span>Commandes</span></a>
        <a href="Produits.php"><i class="fas fa-gem"></i><span>Produits</span></a>
        <a href="Categories.php"><i class="fas fa-layer-group"></i><span>Catégories</span></a>
        <a href="Utilisateurs.php"><i class="fas fa-users"></i><span>Clients</span></a>
    </nav>
    <div class="sidebar-foot">
        <a href="../../index.php"><i class="fas fa-store"></i><span>Voir la boutique</span></a>
        <a href="../deconnexion.php"><i class="fas fa-sign-out-alt"></i><span>Déconnexion</span></a>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <div>
            <small>Gestion</small>
            <h1>Commandes</h1>
        </div>
        <form method="GET" class="search">
            <i class="fas fa-search"></i>
            <?php if ($filtre): ?><input type="hidden" name="statut" value="<?= e($filtre) ?>"><?php endif; ?>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="N° de commande, nom ou email…">
        </form>
    </div>

    <?php if ($flash): ?>
        <div class="flash <?= $flash['type'] ?>">
            <i class="fas <?= $flash['type'] === 'ok' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <?= e($flash['text']) ?>
        </div>
    <?php endif; ?>

    <section class="kpis">
        <div class="kpi highlight"><i class="fas fa-bell"></i><span>À traiter</span><strong><?= (int) $stats['a_traiter'] ?></strong></div>
        <div class="kpi"><i class="fas fa-calendar-day"></i><span>Commandes du jour</span><strong><?= (int) $stats['jour'] ?></strong></div>
        <div class="kpi"><i class="fas fa-chart-line"></i><span>CA du mois</span><strong><?= euroAdmin($stats['ca_mois']) ?></strong></div>
        <div class="kpi"><i class="fas fa-coins"></i><span>CA total</span><strong><?= euroAdmin($stats['ca_total']) ?></strong></div>
    </section>

    <nav class="tabs">
        <a href="<?= e(adminUrl(['statut' => '', 'page' => 1, 'detail' => 0])) ?>" class="<?= $filtre === '' ? 'active' : '' ?>">Toutes <b><?= $compteurs[''] ?></b></a>
        <?php foreach ($STATUTS as $key => $s): ?>
            <a href="<?= e(adminUrl(['statut' => $key, 'page' => 1, 'detail' => 0])) ?>" class="<?= $filtre === $key ? 'active' : '' ?>">
                <?= $s['label'] ?> <b><?= $compteurs[$key] ?? 0 ?></b>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="layout <?= $detail ? 'with-detail' : '' ?>">
        <section class="table-card">
            <?php if (empty($commandes)): ?>
                <div class="empty"><i class="fas fa-inbox"></i>Aucune commande trouvée<?= $q ? ' pour « ' . e($q) . ' »' : '' ?>.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Commande</th>
                            <th>Client</th>
                            <th class="col-hide">Livraison</th>
                            <th>Statut</th>
                            <th style="text-align:right">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $c):
                            $s = $STATUTS[$c['statut'] ?: 'en attente'] ?? $STATUTS['en attente']; ?>
                            <tr class="row <?= $detailId === (int) $c['id_commande'] ? 'selected' : '' ?>"
                                data-href="<?= e(adminUrl(['detail' => (int) $c['id_commande']])) ?>">
                                <td>
                                    <span class="num"><?= e(strtoupper($c['numero_commande'] ?? '')) ?></span>
                                    <span class="sub"><?= date('d/m/Y H:i', strtotime($c['date_commande'])) ?> · <?= (int) $c['nb_articles'] ?> art.</span>
                                </td>
                                <td>
                                    <?= e(trim(($c['u_prenom'] ?? '') . ' ' . ($c['u_nom'] ?? '')) ?: 'Client supprimé') ?>
                                    <span class="sub"><?= e($c['u_email'] ?? '') ?></span>
                                </td>
                                <td class="col-hide"><?= $MODES[$c['mode_livraison']] ?? '—' ?></td>
                                <td><span class="badge b-<?= $s['class'] ?>"><i class="fas <?= $s['icon'] ?>"></i><?= $s['label'] ?></span></td>
                                <td style="text-align:right" class="amount"><?= euroAdmin($c['montant']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($nbPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $nbPages; $i++): ?>
                            <?php if ($i === $page): ?><span><?= $i ?></span>
                            <?php else: ?><a href="<?= e(adminUrl(['page' => $i, 'detail' => 0])) ?>"><?= $i ?></a><?php endif; ?>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <?php if ($detail):
            $actuel = $detail['statut'] ?: 'en attente';
            $s = $STATUTS[$actuel];
            $rang = array_search($actuel, $ETAPES, true);
            $suivant = ($rang !== false && isset($ETAPES[$rang + 1])) ? $ETAPES[$rang + 1] : null;
            $sousTotal = 0;
            foreach ($lignes as $l) { $sousTotal += $l['prix_unitaire'] * $l['quantite']; }
        ?>
            <aside class="detail">
                <div class="detail-head">
                    <a href="<?= e(adminUrl(['detail' => 0])) ?>" class="close" title="Fermer"><i class="fas fa-times"></i></a>
                    <small>Commande</small>
                    <h2><?= e(strtoupper($detail['numero_commande'])) ?></h2>
                    <span class="badge b-<?= $s['class'] ?>"><i class="fas <?= $s['icon'] ?>"></i><?= $s['label'] ?></span>
                </div>

                <div class="detail-body">
                    <?php if ($actuel !== 'annulee'): ?>
                        <div class="block">
                            <div class="track">
                                <?php foreach ($ETAPES as $i => $etape): ?>
                                    <div class="<?= $rang !== false && $i <= $rang ? 'done' : '' ?>"><?= $STATUTS[$etape]['label'] ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="block">
                        <h3>Actions</h3>
                        <?php if ($actuel === 'annulee'): ?>
                            <div class="locked"><i class="fas fa-lock"></i> Commande annulée : articles remis en stock, plus de modification possible.</div>
                        <?php else: ?>
                            <div class="actions">
                                <?php if ($suivant): ?>
                                    <form method="POST" action="<?= e(adminUrl()) ?>">
                                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                        <input type="hidden" name="id_commande" value="<?= (int) $detail['id_commande'] ?>">
                                        <input type="hidden" name="statut" value="<?= e($suivant) ?>">
                                        <button type="submit" name="changer_statut" class="next">
                                            <i class="fas <?= $STATUTS[$suivant]['icon'] ?>"></i> Marquer comme « <?= $STATUTS[$suivant]['label'] ?> »
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php foreach ($ETAPES as $etape):
                                    if ($etape === $actuel || $etape === $suivant) continue; ?>
                                    <form method="POST" action="<?= e(adminUrl()) ?>">
                                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                        <input type="hidden" name="id_commande" value="<?= (int) $detail['id_commande'] ?>">
                                        <input type="hidden" name="statut" value="<?= e($etape) ?>">
                                        <button type="submit" name="changer_statut"><i class="fas <?= $STATUTS[$etape]['icon'] ?>"></i><?= $STATUTS[$etape]['label'] ?></button>
                                    </form>
                                <?php endforeach; ?>
                                <form method="POST" action="<?= e(adminUrl()) ?>"
                                      onsubmit="return confirm('Annuler cette commande ? Les articles seront remis en stock. Cette action est définitive.');">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="id_commande" value="<?= (int) $detail['id_commande'] ?>">
                                    <input type="hidden" name="statut" value="annulee">
                                    <button type="submit" name="changer_statut" class="danger"><i class="fas fa-ban"></i>Annuler</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="block">
                        <h3>Client</h3>
                        <p><i class="fas fa-user ico"></i><?= e(trim(($detail['u_prenom'] ?? '') . ' ' . ($detail['u_nom'] ?? '')) ?: 'Client supprimé') ?></p>
                        <?php if (!empty($detail['u_email'])): ?>
                            <p><i class="fas fa-envelope ico"></i><a href="mailto:<?= e($detail['u_email']) ?>"><?= e($detail['u_email']) ?></a></p>
                        <?php endif; ?>
                    </div>

                    <div class="block">
                        <h3>Livraison</h3>
                        <p><i class="fas fa-map-marker-alt ico"></i><?= e(preg_replace('/^Livraison : /', '', (string) $detail['notes']) ?: 'Adresse non renseignée') ?></p>
                        <p><i class="fas fa-truck ico"></i><?= $MODES[$detail['mode_livraison']] ?? 'Standard' ?></p>
                        <p><i class="fas fa-calendar ico"></i>Commandée le <?= date('d/m/Y à H:i', strtotime($detail['date_commande'])) ?></p>
                        <?php if (!empty($detail['date_paiement'])): ?>
                            <p><i class="fas fa-credit-card ico"></i>Payée le <?= date('d/m/Y à H:i', strtotime($detail['date_paiement'])) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="block">
                        <h3>Articles</h3>
                        <?php foreach ($lignes as $l): ?>
                            <div class="item">
                                <img src="<?= e($l['image_url'] ?: 'https://via.placeholder.com/80?text=Bijou') ?>" alt="" onerror="this.src='https://via.placeholder.com/80?text=Bijou'">
                                <div>
                                    <strong><?= e($l['p_nom'] ?? 'Produit supprimé') ?></strong>
                                    <span class="sub"><?= e($l['reference'] ?? '') ?> · <?= (int) $l['quantite'] ?> × <?= euroAdmin($l['prix_unitaire']) ?> · stock <?= (int) ($l['stock'] ?? 0) ?></span>
                                </div>
                                <b><?= euroAdmin($l['prix_unitaire'] * $l['quantite']) ?></b>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($lignes)): ?><p class="sub">Aucun article enregistré.</p><?php endif; ?>
                    </div>

                    <div class="block totals">
                        <p><span>Sous-total</span><span><?= euroAdmin($sousTotal) ?></span></p>
                        <p><span>Livraison</span><span><?= (float) $detail['frais_livraison'] > 0 ? euroAdmin($detail['frais_livraison']) : 'Offerte' ?></span></p>
                        <p class="grand"><span>Total</span><span><?= euroAdmin($detail['montant']) ?></span></p>
                    </div>
                </div>
            </aside>
        <?php endif; ?>
    </div>
</main>

<script>
// Clic sur une ligne = ouvrir le détail
document.querySelectorAll('tr.row').forEach(function (row) {
    row.addEventListener('click', function () { window.location = row.dataset.href; });
});
</script>
</body>
</html>
