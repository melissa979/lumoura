<?php
/**
 * Gestion premium des produits
 * Fichier : pages/admin/Produits.php
 */
require_once __DIR__ . '/auth_admin.php';

function productAdminE($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function productAdminMoney($value): string
{
    return number_format((float) $value, 2, ',', ' ') . ' €';
}

function storeProductImage(string $field, int $productId, string $kind): ?string
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Impossible de téléverser l’image « ' . $field . ' ».');
    }

    if ((int) $_FILES[$field]['size'] > 5 * 1024 * 1024) {
        throw new Exception('Chaque image doit faire 5 Mo maximum.');
    }

    $info = @getimagesize($_FILES[$field]['tmp_name']);
    if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new Exception('Format accepté : JPG, PNG ou WEBP.');
    }

    $extension = match ($info['mime']) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg'
    };

    $directory = dirname(__DIR__, 2) . '/assets/images/produits';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new Exception('Le dossier des images produits ne peut pas être créé.');
    }

    $filename = $productId . '-' . $kind . '.' . $extension;
    $destination = $directory . '/' . $filename;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $destination)) {
        throw new Exception('Impossible d’enregistrer l’image sur le serveur.');
    }

    return '/lumoura/assets/images/produits/' . $filename;
}

if (empty($_SESSION['products_csrf'])) {
    $_SESSION['products_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['products_csrf'];
$message = null;
$erreur = null;

// La colonne hover est optionnelle, car elle peut avoir été ajoutée après l'export SQL initial.
$hasHoverImage = false;
try {
    $columnCheck = $pdo->query("SHOW COLUMNS FROM produits LIKE 'image_hover_url'")->fetch(PDO::FETCH_ASSOC);
    $hasHoverImage = (bool) $columnCheck;
} catch (Throwable $e) {
    $hasHoverImage = false;
}

$categories = [];
try {
    $categories = $pdo->query('SELECT id_categorie, nom_categorie FROM categories ORDER BY nom_categorie')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Ajouter ou modifier un produit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
            throw new Exception('Session expirée, veuillez réessayer.');
        }

        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'delete') {
            $id = (int) ($_POST['id_produit'] ?? 0);
            if ($id <= 0) throw new Exception('Produit invalide.');

            $checks = [
                ['details_commande', 'id_produit', 'des commandes'],
                ['panier', 'id_produit', 'des paniers'],
                ['avis', 'id_produit', 'des avis'],
            ];
            foreach ($checks as [$table, $column, $label]) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` = ?");
                $stmt->execute([$id]);
                if ((int) $stmt->fetchColumn() > 0) {
                    throw new Exception("Ce produit est lié à $label. Il ne peut pas être supprimé.");
                }
            }

            $stmt = $pdo->prepare('DELETE FROM produits WHERE id_produit = ?');
            $stmt->execute([$id]);
            $message = 'Produit supprimé avec succès.';
        } elseif ($action === 'add' || $action === 'edit') {
            $id = (int) ($_POST['id_produit'] ?? 0);
            $reference = trim((string) ($_POST['reference'] ?? ''));
            $nom = trim((string) ($_POST['nom'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $descriptionCourte = trim((string) ($_POST['description_courte'] ?? ''));
            $idCategorie = (int) ($_POST['id_categorie'] ?? 0);
            $marque = trim((string) ($_POST['marque'] ?? 'Lumoura'));
            $prix = (float) ($_POST['prix'] ?? 0);
            $promotion = max(0, min(100, (float) ($_POST['promotion_pourcentage'] ?? 0)));
            $matiere = trim((string) ($_POST['matiere'] ?? ''));
            $pierre = trim((string) ($_POST['pierre'] ?? ''));
            $taille = trim((string) ($_POST['taille'] ?? ''));
            $couleurMetal = trim((string) ($_POST['couleur_metal'] ?? ''));
            $poids = $_POST['poids_g'] !== '' ? (float) $_POST['poids_g'] : null;
            $genre = in_array($_POST['genre'] ?? '', ['Homme', 'Femme', 'Unisexe'], true) ? $_POST['genre'] : 'Unisexe';
            $stock = max(0, (int) ($_POST['stock'] ?? 0));
            $seuilAlerte = max(0, (int) ($_POST['seuil_alerte'] ?? 10));
            $image = trim((string) ($_POST['image_url'] ?? ''));
            $imageHover = trim((string) ($_POST['image_hover_url'] ?? ''));
            $nouveaute = isset($_POST['nouveaute']) ? 1 : 0;
            $bestseller = isset($_POST['bestseller']) ? 1 : 0;

            if ($reference === '') $reference = 'LUM-' . strtoupper(bin2hex(random_bytes(3)));
            if ($nom === '' || $prix <= 0 || $idCategorie <= 0 || $marque === '') {
                throw new Exception('Le nom, la catégorie, la marque et le prix sont obligatoires.');
            }
            if ($action === 'edit' && $id <= 0) throw new Exception('Produit invalide.');

            // En modification, un fichier importé remplace automatiquement l’URL saisie.
            if ($action === 'edit') {
                $uploadedPrincipal = storeProductImage('image_principale', $id, 'principal');
                if ($uploadedPrincipal !== null) $image = $uploadedPrincipal;
                if ($hasHoverImage) {
                    $uploadedPortee = storeProductImage('image_portee', $id, 'porte');
                    if ($uploadedPortee !== null) $imageHover = $uploadedPortee;
                }
            }

            $fields = [
                'reference', 'nom', 'description', 'description_courte', 'id_categorie', 'marque', 'prix',
                'matiere', 'pierre', 'taille', 'couleur_metal', 'poids_g', 'genre', 'bestseller', 'nouveaute',
                'promotion_pourcentage', 'image_url', 'stock', 'seuil_alerte'
            ];
            $values = [$reference, $nom, $description, $descriptionCourte, $idCategorie, $marque, $prix, $matiere, $pierre, $taille, $couleurMetal, $poids, $genre, $bestseller, $nouveaute, $promotion, $image, $stock, $seuilAlerte];
            if ($hasHoverImage) {
                $fields[] = 'image_hover_url';
                $values[] = $imageHover;
            }

            if ($action === 'add') {
                $columns = implode(', ', $fields);
                $marks = implode(', ', array_fill(0, count($fields), '?'));
                $stmt = $pdo->prepare("INSERT INTO produits ($columns) VALUES ($marks)");
                $stmt->execute($values);
                $id = (int) $pdo->lastInsertId();

                // Après la création, le nouvel ID permet de nommer les fichiers 24-principal.jpg et 24-porte.png.
                $uploadedPrincipal = storeProductImage('image_principale', $id, 'principal');
                $uploadedPortee = $hasHoverImage ? storeProductImage('image_portee', $id, 'porte') : null;
                if ($uploadedPrincipal !== null || $uploadedPortee !== null) {
                    $updates = [];
                    $uploadValues = [];
                    if ($uploadedPrincipal !== null) { $updates[] = 'image_url = ?'; $uploadValues[] = $uploadedPrincipal; }
                    if ($hasHoverImage && $uploadedPortee !== null) { $updates[] = 'image_hover_url = ?'; $uploadValues[] = $uploadedPortee; }
                    if ($updates) {
                        $uploadValues[] = $id;
                        $pdo->prepare('UPDATE produits SET ' . implode(', ', $updates) . ' WHERE id_produit = ?')->execute($uploadValues);
                    }
                }
                $message = 'Produit « ' . $nom . ' » ajouté avec succès.';
            } else {
                $sets = implode(', ', array_map(fn($field) => "$field = ?", $fields));
                $values[] = $id;
                $stmt = $pdo->prepare("UPDATE produits SET $sets WHERE id_produit = ?");
                $stmt->execute($values);
                $message = 'Produit « ' . $nom . ' » modifié avec succès.';
            }
        }
    } catch (Throwable $e) {
        $erreur = $e->getMessage();
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$filtreCategorie = (int) ($_GET['categorie'] ?? 0);
$filtreGenre = trim((string) ($_GET['genre'] ?? ''));
$editId = (int) ($_GET['edit'] ?? 0);

$where = ['1=1'];
$params = [];
if ($search !== '') {
    $where[] = '(p.nom LIKE ? OR p.reference LIKE ? OR p.marque LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
if ($filtreCategorie > 0) { $where[] = 'p.id_categorie = ?'; $params[] = $filtreCategorie; }
if (in_array($filtreGenre, ['Homme', 'Femme', 'Unisexe'], true)) { $where[] = 'p.genre = ?'; $params[] = $filtreGenre; }

$produits = [];
try {
    $stmt = $pdo->prepare(''
        . 'SELECT p.*, c.nom_categorie AS cat_nom '
        . 'FROM produits p LEFT JOIN categories c ON c.id_categorie = p.id_categorie '
        . 'WHERE ' . implode(' AND ', $where) . ' ORDER BY p.id_produit DESC'
    );
    $stmt->execute($params);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $erreur = $erreur ?: 'Impossible de charger les produits : ' . $e->getMessage();
}

$edit = null;
if ($editId > 0) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM produits WHERE id_produit = ?');
        $stmt->execute([$editId]);
        $edit = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {}
}

$nbProduits = count($produits);
$nbRuptures = count(array_filter($produits, fn($p) => (int) $p['stock'] <= 0));
$nbAlertes = count(array_filter($produits, fn($p) => (int) $p['stock'] > 0 && (int) $p['stock'] <= (int) $p['seuil_alerte']));

$pageTitle = "Produits - Admin Éclat d'Or";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= productAdminE($pageTitle) ?></title>
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
.kpis { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:22px; }
.kpi { position:relative; padding:18px 20px; border:1px solid var(--line); border-radius:18px; background:#fff; box-shadow:0 8px 20px rgba(61,43,40,.05); }
.kpi i { position:absolute; top:18px; right:18px; width:38px; height:38px; display:grid; place-items:center; border-radius:12px; background:#fbf3df; color:var(--gold-dark); }
.kpi span { color:var(--muted); font-size:12px; font-weight:600; }
.kpi strong { display:block; margin-top:7px; font-size:24px; }
.kpi.warning strong { color:#c2410c; }
.kpi.danger strong { color:#b42318; }

.product-form-card,.table-card,.filters { overflow:hidden; border:1px solid var(--line); border-radius:18px; background:#fff; box-shadow:0 8px 20px rgba(61,43,40,.05); }
.product-form-card { margin-bottom:20px; }
.form-header { display:flex; align-items:center; justify-content:space-between; gap:15px; padding:16px 20px; background:linear-gradient(115deg,var(--brown),#5e4135); color:#fff; }
.form-header h2 { margin:0; font-family:'Playfair Display',serif; font-size:20px; }
.form-header button { border:1px solid rgba(255,255,255,.45); border-radius:9px; padding:8px 12px; background:rgba(255,255,255,.12); color:#fff; cursor:pointer; font-weight:700; }
.form-body { display:none; padding:22px; }
.form-body.open { display:block; }
.image-management-box { padding:15px; border:1px dashed var(--line); border-radius:12px; background:#fffaf3; }
.image-input-row { display:grid; grid-template-columns:minmax(150px,1fr) auto minmax(150px,1fr); align-items:center; gap:10px; }
.image-input-row span { color:var(--muted); font-size:11px; font-weight:800; text-align:center; }
.image-help { display:block; margin-top:7px; color:var(--muted); font-size:10px; }
.admin-image-preview { width:74px; height:74px; margin-top:10px; border-radius:10px; object-fit:cover; box-shadow:0 4px 12px rgba(61,43,40,.12); }
.form-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:15px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-group.full { grid-column:1/-1; }
.form-group label { color:var(--muted); font-size:11px; font-weight:800; letter-spacing:.4px; text-transform:uppercase; }
.form-group input,.form-group select,.form-group textarea { width:100%; padding:10px 12px; border:1px solid var(--line); border-radius:9px; outline:0; background:#fff; color:var(--brown); font:inherit; }
.form-group input:focus,.form-group select:focus,.form-group textarea:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(212,175,55,.12); }
.form-group textarea { min-height:90px; resize:vertical; }
.checks { display:flex; gap:18px; flex-wrap:wrap; }
.checks label { display:flex; align-items:center; gap:7px; color:var(--brown); font-size:13px; font-weight:600; letter-spacing:0; text-transform:none; }
.checks input { accent-color:var(--gold); }
.form-actions { display:flex; align-items:center; gap:10px; margin-top:18px; }
.btn-gold,.btn-neutral { display:inline-flex; align-items:center; gap:8px; padding:11px 17px; border:0; border-radius:10px; color:#fff; font-size:13px; font-weight:800; cursor:pointer; }
.btn-gold { background:var(--gold); } .btn-gold:hover { background:var(--gold-dark); }
.btn-neutral { background:#8b7b73; } .btn-neutral:hover { background:var(--brown); }

.filters { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:15px; margin-bottom:18px; }
.filters input,.filters select { min-width:180px; flex:1; padding:10px 12px; border:1px solid var(--line); border-radius:9px; outline:0; font:inherit; }
.filters button { padding:10px 17px; border:0; border-radius:9px; background:var(--gold); color:#fff; font-weight:800; cursor:pointer; }
.reset { padding:10px 13px; color:var(--muted); font-size:12px; font-weight:700; }

.table-header { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:16px 20px; border-bottom:1px solid var(--line); background:#fffaf3; }
.table-header h2 { margin:0; font-family:'Playfair Display',serif; font-size:20px; }
.table-header span { color:var(--muted); font-size:12px; }
table { width:100%; border-collapse:collapse; }
th { padding:12px 14px; background:#fbf7f2; border-bottom:1px solid var(--line); color:var(--muted); font-size:10px; font-weight:800; letter-spacing:1px; text-align:left; text-transform:uppercase; }
td { padding:12px 14px; border-bottom:1px solid #f3ece4; vertical-align:middle; font-size:12px; }
tr:last-child td { border-bottom:0; }
.product-cell { display:flex; align-items:center; gap:10px; min-width:210px; }
.product-cell img,.image-empty { width:52px; height:52px; flex-shrink:0; border-radius:11px; object-fit:cover; background:#eee4da; }
.image-empty { display:grid; place-items:center; color:#b9aaa0; }
.product-cell strong { display:block; font-size:13px; }
.product-cell small { display:block; max-width:230px; margin-top:3px; overflow:hidden; color:var(--muted); font-size:10px; text-overflow:ellipsis; white-space:nowrap; }
.price { color:var(--gold-dark); font-weight:800; white-space:nowrap; }
.old-price { color:#aaa; font-size:10px; text-decoration:line-through; }
.badge { display:inline-flex; margin:2px; padding:5px 8px; border-radius:18px; background:#d1fae5; color:#065f46; font-size:10px; font-weight:800; }
.badge.promo { background:#fff3cd; color:#856404; }
.stock { font-weight:800; } .stock-ok { color:#16804b; } .stock-alert { color:#c2410c; } .stock-out { color:#b42318; }
.actions { display:flex; gap:5px; }
.action-btn { width:32px; height:32px; display:grid; place-items:center; border:0; border-radius:8px; cursor:pointer; }
.action-edit { background:#fff3cd; color:#856404; } .action-edit:hover { background:var(--gold); color:#fff; }
.action-delete { background:#fee2e2; color:#b42318; } .action-delete:hover { background:#b42318; color:#fff; }
.empty { padding:50px 20px; text-align:center; color:var(--muted); }
.empty i { display:block; margin-bottom:10px; color:var(--gold); font-size:28px; }
@media (max-width:1100px) { .form-grid { grid-template-columns:repeat(2,1fr); } .image-input-row { grid-template-columns:1fr; } .image-input-row span { text-align:left; } }
@media (max-width:900px) { .admin-sidebar { width:72px; } .admin-logo span,.admin-nav span,.admin-footer span { display:none; } .admin-logo strong { font-size:14px; letter-spacing:0; } .admin-main { padding:24px 20px 50px; } }
@media (max-width:680px) { .admin-main { padding:20px 12px 45px; } .kpis { gap:10px; } .kpi { padding:14px; } .kpi i { display:none; } .kpi strong { font-size:19px; } .form-grid { grid-template-columns:1fr; } .form-group.full { grid-column:auto; } .filters input,.filters select { min-width:100%; } .hide-mobile { display:none; } th,td { padding:10px 8px; } .product-cell { min-width:150px; } }
</style>
</head>
<body>
<aside class="admin-sidebar">
    <div class="admin-logo"><strong>ÉCLAT D'OR</strong><span>Administration</span></div>
    <nav class="admin-nav">
        <a href="admin.php"><i class="fas fa-chart-pie"></i><span>Tableau de bord</span></a>
        <a href="Commandes.php"><i class="fas fa-receipt"></i><span>Commandes</span></a>
        <a href="Produits.php" class="active"><i class="fas fa-gem"></i><span>Produits</span></a>
        <a href="Categories.php"><i class="fas fa-layer-group"></i><span>Catégories</span></a>
        <a href="Utilisateurs.php"><i class="fas fa-users"></i><span>Clients</span></a>
    </nav>
    <div class="admin-footer"><a href="../../index.php"><i class="fas fa-store"></i><span>Voir la boutique</span></a><a href="../deconnexion.php"><i class="fas fa-sign-out-alt"></i><span>Déconnexion</span></a></div>
</aside>

<main class="admin-main">
    <header class="admin-topbar">
        <div><span class="admin-kicker">Catalogue</span><h1>Gestion des produits</h1><p>Ajoutez, modifiez et suivez vos bijoux.</p></div>
        <a href="../../index.php" class="shop-link"><i class="fas fa-store"></i> Voir la boutique</a>
    </header>

    <?php if ($message): ?><div class="flash success"><i class="fas fa-check-circle"></i><?= productAdminE($message) ?></div><?php endif; ?>
    <?php if ($erreur): ?><div class="flash error"><i class="fas fa-exclamation-circle"></i><?= productAdminE($erreur) ?></div><?php endif; ?>

    <section class="kpis">
        <div class="kpi"><i class="fas fa-gem"></i><span>Produits affichés</span><strong><?= $nbProduits ?></strong></div>
        <div class="kpi warning"><i class="fas fa-triangle-exclamation"></i><span>Stock faible</span><strong><?= $nbAlertes ?></strong></div>
        <div class="kpi danger"><i class="fas fa-box-open"></i><span>Ruptures</span><strong><?= $nbRuptures ?></strong></div>
    </section>

    <section class="product-form-card">
        <div class="form-header">
            <h2><i class="fas fa-<?= $edit ? 'pen-to-square' : 'plus-circle' ?>"></i> <?= $edit ? 'Modifier le produit' : 'Ajouter un nouveau produit' ?></h2>
            <button type="button" onclick="toggleProductForm()"><i class="fas fa-chevron-down"></i> Replier</button>
        </div>
        <div class="form-body <?= $edit ? 'open' : '' ?>" id="productFormBody">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= productAdminE($csrf) ?>">
                <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
                <?php if ($edit): ?><input type="hidden" name="id_produit" value="<?= (int) $edit['id_produit'] ?>"><?php endif; ?>
                <div class="form-grid">
                    <div class="form-group"><label>Référence</label><input type="text" name="reference" value="<?= productAdminE($edit['reference'] ?? '') ?>" placeholder="Auto si vide"></div>
                    <div class="form-group"><label>Nom *</label><input type="text" name="nom" required value="<?= productAdminE($edit['nom'] ?? '') ?>"></div>
                    <div class="form-group"><label>Marque *</label><input type="text" name="marque" required value="<?= productAdminE($edit['marque'] ?? 'Lumoura') ?>"></div>
                    <div class="form-group"><label>Catégorie *</label><select name="id_categorie" required><option value="">Choisir…</option><?php foreach ($categories as $cat): ?><option value="<?= (int) $cat['id_categorie'] ?>" <?= (int) ($edit['id_categorie'] ?? 0) === (int) $cat['id_categorie'] ? 'selected' : '' ?>><?= productAdminE($cat['nom_categorie']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Genre</label><select name="genre"><?php foreach (['Femme','Homme','Unisexe'] as $genre): ?><option <?= ($edit['genre'] ?? 'Unisexe') === $genre ? 'selected' : '' ?>><?= $genre ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Prix (€) *</label><input type="number" name="prix" step="0.01" min="0.01" required value="<?= productAdminE($edit['prix'] ?? '') ?>"></div>
                    <div class="form-group"><label>Promotion (%)</label><input type="number" name="promotion_pourcentage" step="0.01" min="0" max="100" value="<?= productAdminE($edit['promotion_pourcentage'] ?? 0) ?>"></div>
                    <div class="form-group"><label>Stock</label><input type="number" name="stock" min="0" value="<?= productAdminE($edit['stock'] ?? 0) ?>"></div>
                    <div class="form-group"><label>Seuil d'alerte</label><input type="number" name="seuil_alerte" min="0" value="<?= productAdminE($edit['seuil_alerte'] ?? 10) ?>"></div>
                    <div class="form-group"><label>Matière</label><input type="text" name="matiere" value="<?= productAdminE($edit['matiere'] ?? '') ?>" placeholder="Or 18k, Argent 925…"></div>
                    <div class="form-group"><label>Pierre</label><input type="text" name="pierre" value="<?= productAdminE($edit['pierre'] ?? '') ?>" placeholder="Diamant, Émeraude…"></div>
                    <div class="form-group"><label>Taille</label><input type="text" name="taille" value="<?= productAdminE($edit['taille'] ?? '') ?>"></div>
                    <div class="form-group"><label>Couleur métal</label><input type="text" name="couleur_metal" value="<?= productAdminE($edit['couleur_metal'] ?? '') ?>"></div>
                    <div class="form-group"><label>Poids (g)</label><input type="number" name="poids_g" step="0.01" min="0" value="<?= productAdminE($edit['poids_g'] ?? '') ?>"></div>
                    <div class="form-group full image-management-box">
                        <label>Image principale du bijou</label>
                        <div class="image-input-row"><input type="file" name="image_principale" accept="image/jpeg,image/png,image/webp"><span>ou</span><input type="url" name="image_url" value="<?= productAdminE($edit['image_url'] ?? '') ?>" placeholder="URL de l'image principale"></div>
                        <small class="image-help">Import JPG, PNG ou WEBP, 5 Mo maximum. Le fichier sera rangé automatiquement dans assets/images/produits.</small>
                        <?php if (!empty($edit['image_url'])): ?><img class="admin-image-preview" src="<?= productAdminE($edit['image_url']) ?>" alt="Aperçu principal" onerror="this.style.display='none'"> <?php endif; ?>
                    </div>
                    <?php if ($hasHoverImage): ?><div class="form-group full image-management-box">
                        <label>Image portée au survol</label>
                        <div class="image-input-row"><input type="file" name="image_portee" accept="image/jpeg,image/png,image/webp"><span>ou</span><input type="url" name="image_hover_url" value="<?= productAdminE($edit['image_hover_url'] ?? '') ?>" placeholder="URL de l'image portée"></div>
                        <small class="image-help">Cette image s’affichera quand le client survole le bijou.</small>
                        <?php if (!empty($edit['image_hover_url'])): ?><img class="admin-image-preview" src="<?= productAdminE($edit['image_hover_url']) ?>" alt="Aperçu porté" onerror="this.style.display='none'"> <?php endif; ?>
                    </div><?php endif; ?>
                    <div class="form-group full"><label>Description courte</label><input type="text" name="description_courte" maxlength="200" value="<?= productAdminE($edit['description_courte'] ?? '') ?>"></div>
                    <div class="form-group full"><label>Description complète</label><textarea name="description"><?= productAdminE($edit['description'] ?? '') ?></textarea></div>
                    <div class="form-group full"><div class="checks"><label><input type="checkbox" name="nouveaute" <?= !empty($edit['nouveaute']) ? 'checked' : '' ?>> Nouveauté</label><label><input type="checkbox" name="bestseller" <?= !empty($edit['bestseller']) ? 'checked' : '' ?>> Best-seller</label></div></div>
                </div>
                <div class="form-actions"><button class="btn-gold" type="submit"><i class="fas fa-save"></i> <?= $edit ? 'Enregistrer les modifications' : 'Ajouter le produit' ?></button><?php if ($edit): ?><a href="Produits.php" class="btn-neutral"><i class="fas fa-times"></i> Annuler</a><?php endif; ?></div>
            </form>
        </div>
    </section>

    <form method="GET" class="filters">
        <input type="search" name="search" value="<?= productAdminE($search) ?>" placeholder="Rechercher par nom, référence ou marque…">
        <select name="categorie"><option value="">Toutes les catégories</option><?php foreach ($categories as $cat): ?><option value="<?= (int) $cat['id_categorie'] ?>" <?= $filtreCategorie === (int) $cat['id_categorie'] ? 'selected' : '' ?>><?= productAdminE($cat['nom_categorie']) ?></option><?php endforeach; ?></select>
        <select name="genre"><option value="">Tous les genres</option><?php foreach (['Femme','Homme','Unisexe'] as $genre): ?><option <?= $filtreGenre === $genre ? 'selected' : '' ?>><?= $genre ?></option><?php endforeach; ?></select>
        <button type="submit"><i class="fas fa-filter"></i> Filtrer</button>
        <?php if ($search || $filtreCategorie || $filtreGenre): ?><a class="reset" href="Produits.php">Réinitialiser</a><?php endif; ?>
    </form>

    <section class="table-card">
        <div class="table-header"><h2><i class="fas fa-list"></i> Catalogue</h2><span><?= $nbProduits ?> produit<?= $nbProduits > 1 ? 's' : '' ?></span></div>
        <?php if (empty($produits)): ?><div class="empty"><i class="fas fa-gem"></i>Aucun produit trouvé.</div><?php else: ?>
        <table><thead><tr><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Statuts</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($produits as $p): $stock = (int) $p['stock']; $seuil = (int) $p['seuil_alerte']; ?>
            <tr>
                <td><div class="product-cell"><img src="<?= productAdminE($p['image_url'] ?: 'https://via.placeholder.com/100?text=Bijou') ?>" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='grid'"><div class="image-empty" style="display:none"><i class="fas fa-gem"></i></div><div><strong><?= productAdminE($p['nom']) ?></strong><small><?= productAdminE($p['reference']) ?> · <?= productAdminE(mb_strimwidth($p['description_courte'] ?? '', 0, 55, '…')) ?></small></div></div></td>
                <td><?= productAdminE($p['cat_nom'] ?? '—') ?></td>
                <td class="price"><?php if ((float) $p['promotion_pourcentage'] > 0): ?><span class="old-price"><?= productAdminMoney($p['prix']) ?></span><br><?= productAdminMoney((float) $p['prix'] * (1 - (float) $p['promotion_pourcentage'] / 100)) ?><?php else: ?><?= productAdminMoney($p['prix']) ?><?php endif; ?></td>
                <td class="stock <?= $stock <= 0 ? 'stock-out' : ($stock <= $seuil ? 'stock-alert' : 'stock-ok') ?>"><?= $stock <= 0 ? 'Rupture' : $stock ?></td>
                <td><?php if (!empty($p['nouveaute'])): ?><span class="badge">Nouveau</span><?php endif; ?><?php if (!empty($p['bestseller'])): ?><span class="badge">Best</span><?php endif; ?><?php if ((float) $p['promotion_pourcentage'] > 0): ?><span class="badge promo">-<?= (float) $p['promotion_pourcentage'] ?>%</span><?php endif; ?></td>
                <td><div class="actions"><a class="action-btn action-edit" href="Produits.php?edit=<?= (int) $p['id_produit'] ?>" title="Modifier"><i class="fas fa-pen"></i></a><form method="POST" onsubmit="return confirm('Supprimer ce produit ? Cette action est définitive si aucune commande ne le référence.');"><input type="hidden" name="csrf" value="<?= productAdminE($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id_produit" value="<?= (int) $p['id_produit'] ?>"><button class="action-btn action-delete" title="Supprimer" type="submit"><i class="fas fa-trash"></i></button></form></div></td>
            </tr>
        <?php endforeach; ?></tbody></table>
        <?php endif; ?>
    </section>
</main>
<script>
function toggleProductForm() { document.getElementById('productFormBody').classList.toggle('open'); }
<?php if ($edit): ?>document.getElementById('productFormBody').classList.add('open');<?php endif; ?>
</script>
</body>
</html>
