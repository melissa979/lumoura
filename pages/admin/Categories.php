<?php
/**
 * Gestion des catégories
 * Fichier : pages/admin/Categories.php
 */
require_once __DIR__ . '/auth_admin.php';

function categoryAdminE($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['categories_csrf'])) {
    $_SESSION['categories_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['categories_csrf'];
$message = null;
$erreur = null;

function categorySlug(string $value): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim($value, '-');
}

// Création, modification ou suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
            throw new Exception('Session expirée, veuillez réessayer.');
        }

        $action = (string) ($_POST['action'] ?? '');
        $id = (int) ($_POST['id_categorie'] ?? 0);

        if ($action === 'delete') {
            if ($id <= 0) throw new Exception('Catégorie invalide.');

            $stmt = $pdo->prepare('SELECT nom_categorie FROM categories WHERE id_categorie = ?');
            $stmt->execute([$id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$category) throw new Exception('Catégorie introuvable.');

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM produits WHERE id_categorie = ?');
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0) {
                throw new Exception('Cette catégorie contient encore des produits. Déplacez-les avant de la supprimer.');
            }

            $pdo->prepare('DELETE FROM categories WHERE id_categorie = ?')->execute([$id]);
            $message = 'Catégorie supprimée avec succès.';
        } elseif ($action === 'save') {
            $nom = trim((string) ($_POST['nom_categorie'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $slug = categorySlug($nom);

            if ($nom === '') throw new Exception('Le nom de la catégorie est obligatoire.');
            if ($slug === '') throw new Exception('Le nom de la catégorie est invalide.');

            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE categories SET nom_categorie = ?, description = ?, slug = ? WHERE id_categorie = ?');
                $stmt->execute([$nom, $description, $slug, $id]);
                $message = 'Catégorie modifiée avec succès.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO categories (nom_categorie, description, slug, ordre_affichage) VALUES (?, ?, ?, 0)');
                $stmt->execute([$nom, $description, $slug]);
                $message = 'Catégorie créée avec succès.';
            }
        }
    } catch (Throwable $e) {
        $erreur = $e->getMessage();
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$edit = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id_categorie = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

try {
    $stmt = $pdo->query(''
        . 'SELECT c.id_categorie, c.nom_categorie, c.description, c.slug, c.ordre_affichage, '
        . 'COUNT(p.id_produit) AS nb_produits '
        . 'FROM categories c LEFT JOIN produits p ON p.id_categorie = c.id_categorie '
        . 'GROUP BY c.id_categorie, c.nom_categorie, c.description, c.slug, c.ordre_affichage '
        . 'ORDER BY c.ordre_affichage ASC, c.nom_categorie ASC'
    );
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $categories = [];
    $erreur = $erreur ?: 'Impossible de charger les catégories.';
}

$nbCategories = count($categories);
$nbCategoriesUtilisees = count(array_filter($categories, fn($c) => (int) $c['nb_produits'] > 0));
$nbProduitsClasses = array_sum(array_map(fn($c) => (int) $c['nb_produits'], $categories));

$pageTitle = "Catégories - Admin Éclat d'Or";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= categoryAdminE($pageTitle) ?></title>
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
.layout { display:grid; grid-template-columns:340px minmax(0,1fr); gap:20px; align-items:start; }
.card { overflow:hidden; border:1px solid var(--line); border-radius:18px; background:#fff; box-shadow:0 8px 20px rgba(61,43,40,.05); }
.card-header { padding:17px 20px; background:linear-gradient(115deg,var(--brown),#5e4135); color:#fff; }
.card-header h2 { margin:0; font-family:'Playfair Display',serif; font-size:20px; }
.card-header span { display:block; margin-top:5px; color:#fff4d5; font-size:11px; }
.card-body { padding:22px; }
.form-group { display:flex; flex-direction:column; gap:6px; margin-bottom:15px; }
.form-group label { color:var(--muted); font-size:11px; font-weight:800; letter-spacing:.5px; text-transform:uppercase; }
.form-group input,.form-group textarea { width:100%; padding:11px 12px; border:1px solid var(--line); border-radius:9px; outline:0; font:inherit; color:var(--brown); }
.form-group input:focus,.form-group textarea:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(212,175,55,.12); }
.form-group textarea { min-height:110px; resize:vertical; }
.btn-gold,.btn-neutral { display:inline-flex; align-items:center; gap:8px; padding:11px 16px; border:0; border-radius:10px; color:#fff; font-size:13px; font-weight:800; cursor:pointer; }
.btn-gold { background:var(--gold); } .btn-gold:hover { background:var(--gold-dark); }
.btn-neutral { background:#8b7b73; } .btn-neutral:hover { background:var(--brown); }
.form-actions { display:flex; gap:9px; margin-top:8px; }
.table-header { display:flex; align-items:center; justify-content:space-between; padding:17px 20px; border-bottom:1px solid var(--line); background:#fffaf3; }
.table-header h2 { margin:0; font-family:'Playfair Display',serif; font-size:20px; }
.table-header span { color:var(--muted); font-size:12px; }
table { width:100%; border-collapse:collapse; }
th { padding:13px 16px; background:#fbf7f2; border-bottom:1px solid var(--line); color:var(--muted); font-size:10px; font-weight:800; letter-spacing:1px; text-align:left; text-transform:uppercase; }
td { padding:15px 16px; border-bottom:1px solid #f3ece4; vertical-align:middle; font-size:12px; }
tr:last-child td { border-bottom:0; }
.category-name strong { display:block; font-size:14px; }
.category-name span { display:block; max-width:300px; margin-top:4px; overflow:hidden; color:var(--muted); font-size:11px; text-overflow:ellipsis; white-space:nowrap; }
.slug { color:var(--gold-dark); font-size:11px; }
.product-count { color:var(--gold-dark); font-weight:800; }
.actions { display:flex; gap:6px; }
.action-btn { width:33px; height:33px; display:grid; place-items:center; border:0; border-radius:8px; cursor:pointer; }
.action-edit { background:#fff3cd; color:#856404; } .action-edit:hover { background:var(--gold); color:#fff; }
.action-delete { background:#fee2e2; color:#b42318; } .action-delete:hover { background:#b42318; color:#fff; }
.action-delete:disabled { opacity:.35; cursor:not-allowed; }
.empty { padding:55px 20px; color:var(--muted); text-align:center; }
.empty i { display:block; margin-bottom:10px; color:var(--gold); font-size:28px; }
@media (max-width:900px) { .admin-sidebar { width:72px; } .admin-logo span,.admin-nav span,.admin-footer span { display:none; } .admin-logo strong { font-size:14px; letter-spacing:0; } .admin-main { padding:24px 20px 50px; } .layout { grid-template-columns:1fr; } }
@media (max-width:650px) { .admin-main { padding:20px 12px 45px; } .kpis { gap:10px; } .kpi { padding:14px; } .kpi i { display:none; } .kpi strong { font-size:20px; } th,td { padding:11px 9px; } .hide-mobile { display:none; } }
</style>
</head>
<body>
<aside class="admin-sidebar">
    <div class="admin-logo"><strong>ÉCLAT D'OR</strong><span>Administration</span></div>
    <nav class="admin-nav">
        <a href="admin.php"><i class="fas fa-chart-pie"></i><span>Tableau de bord</span></a>
        <a href="Commandes.php"><i class="fas fa-receipt"></i><span>Commandes</span></a>
        <a href="Produits.php"><i class="fas fa-gem"></i><span>Produits</span></a>
        <a href="Categories.php" class="active"><i class="fas fa-layer-group"></i><span>Catégories</span></a>
        <a href="Utilisateurs.php"><i class="fas fa-users"></i><span>Clients</span></a>
    </nav>
    <div class="admin-footer"><a href="../../index.php"><i class="fas fa-store"></i><span>Voir la boutique</span></a><a href="../deconnexion.php"><i class="fas fa-sign-out-alt"></i><span>Déconnexion</span></a></div>
</aside>

<main class="admin-main">
    <header class="admin-topbar"><div><span class="admin-kicker">Catalogue</span><h1>Catégories</h1><p>Organisez les collections de votre boutique.</p></div><a href="../../index.php" class="shop-link"><i class="fas fa-store"></i> Voir la boutique</a></header>
    <?php if ($message): ?><div class="flash success"><i class="fas fa-check-circle"></i><?= categoryAdminE($message) ?></div><?php endif; ?>
    <?php if ($erreur): ?><div class="flash error"><i class="fas fa-exclamation-circle"></i><?= categoryAdminE($erreur) ?></div><?php endif; ?>

    <section class="kpis">
        <div class="kpi highlight"><i class="fas fa-layer-group"></i><span>Catégories</span><strong><?= $nbCategories ?></strong></div>
        <div class="kpi"><i class="fas fa-check-circle"></i><span>Catégories utilisées</span><strong><?= $nbCategoriesUtilisees ?></strong></div>
        <div class="kpi"><i class="fas fa-gem"></i><span>Produits classés</span><strong><?= $nbProduitsClasses ?></strong></div>
    </section>

    <div class="layout">
        <section class="card">
            <div class="card-header"><h2><i class="fas fa-<?= $edit ? 'pen-to-square' : 'plus-circle' ?>"></i> <?= $edit ? 'Modifier la catégorie' : 'Nouvelle catégorie' ?></h2><span><?= $edit ? 'Mettez à jour les informations.' : 'Créez une nouvelle collection.' ?></span></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf" value="<?= categoryAdminE($csrf) ?>">
                    <input type="hidden" name="action" value="save">
                    <?php if ($edit): ?><input type="hidden" name="id_categorie" value="<?= (int) $edit['id_categorie'] ?>"><?php endif; ?>
                    <div class="form-group"><label>Nom *</label><input type="text" name="nom_categorie" required maxlength="50" value="<?= categoryAdminE($edit['nom_categorie'] ?? '') ?>" placeholder="Ex : Bagues"></div>
                    <div class="form-group"><label>Description</label><textarea name="description" maxlength="500" placeholder="Décrivez cette collection…"><?= categoryAdminE($edit['description'] ?? '') ?></textarea></div>
                    <div class="form-actions"><button class="btn-gold" type="submit"><i class="fas fa-save"></i> <?= $edit ? 'Enregistrer' : 'Créer la catégorie' ?></button><?php if ($edit): ?><a class="btn-neutral" href="Categories.php"><i class="fas fa-times"></i> Annuler</a><?php endif; ?></div>
                </form>
            </div>
        </section>

        <section class="card">
            <div class="table-header"><h2><i class="fas fa-list"></i> Collections</h2><span><?= $nbCategories ?> catégorie<?= $nbCategories > 1 ? 's' : '' ?></span></div>
            <?php if (empty($categories)): ?><div class="empty"><i class="fas fa-layer-group"></i>Aucune catégorie enregistrée.</div><?php else: ?>
            <table><thead><tr><th>Catégorie</th><th>Slug</th><th>Produits</th><th>Actions</th></tr></thead><tbody>
            <?php foreach ($categories as $category): $used = (int) $category['nb_produits'] > 0; ?>
                <tr>
                    <td class="category-name"><strong><?= categoryAdminE($category['nom_categorie']) ?></strong><span><?= categoryAdminE($category['description'] ?: 'Aucune description') ?></span></td>
                    <td class="slug"><?= categoryAdminE($category['slug'] ?: categorySlug($category['nom_categorie'])) ?></td>
                    <td class="product-count"><?= (int) $category['nb_produits'] ?> produit<?= (int) $category['nb_produits'] > 1 ? 's' : '' ?></td>
                    <td><div class="actions"><a class="action-btn action-edit" href="Categories.php?edit=<?= (int) $category['id_categorie'] ?>" title="Modifier"><i class="fas fa-pen"></i></a><form method="POST" onsubmit="return confirm('Supprimer cette catégorie ?');"><input type="hidden" name="csrf" value="<?= categoryAdminE($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_categorie" value="<?= (int) $category['id_categorie'] ?>"><button class="action-btn action-delete" type="submit" title="Supprimer" <?= $used ? 'disabled' : '' ?>><i class="fas fa-trash"></i></button></form></div></td>
                </tr>
            <?php endforeach; ?></tbody></table>
            <?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>
