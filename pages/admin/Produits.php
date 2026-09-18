<?php
require_once __DIR__ . '/auth_admin.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /lumoura/pages/connexion.php'); exit;
}

$message = '';
$erreur  = '';

// ── SUPPRESSION ──────────────────────────────────────────
if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM produits WHERE id_produit = ?");
        $stmt->execute([$_GET['supprimer']]);
        $message = "✅ Produit supprimé avec succès.";
    } catch (Exception $e) {
        $erreur = "❌ Erreur : " . $e->getMessage();
    }
}

// ── AJOUT ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom         = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix        = floatval($_POST['prix'] ?? 0);
    $stock       = intval($_POST['stock'] ?? 0);
    $id_cat      = intval($_POST['id_categorie'] ?? 0) ?: null;
    $image       = trim($_POST['image_url'] ?? '');
    $nouveaute   = isset($_POST['nouveaute']) ? 1 : 0;
    $bestseller  = isset($_POST['bestseller']) ? 1 : 0;
    $en_promo    = isset($_POST['en_promo']) ? 1 : 0;
    $prix_promo  = $en_promo ? floatval($_POST['prix_promo'] ?? 0) : null;

    if ($nom === '' || $prix <= 0) {
        $erreur = "❌ Le nom et le prix sont obligatoires.";
    } else {
        try {
            $sql = "INSERT INTO produits (nom, description, prix, stock, id_categorie, image_url, nouveaute, bestseller, en_promo, prix_promo, date_ajout)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $pdo->prepare($sql)->execute([$nom, $description, $prix, $stock, $id_cat, $image, $nouveaute, $bestseller, $en_promo, $prix_promo]);
            $message = "✅ Produit « $nom » ajouté avec succès !";
        } catch (Exception $e) {
            $erreur = "❌ Erreur BDD : " . $e->getMessage();
        }
    }
}

// ── MODIFICATION ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'modifier') {
    $id          = intval($_POST['id_produit']);
    $nom         = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix        = floatval($_POST['prix'] ?? 0);
    $stock       = intval($_POST['stock'] ?? 0);
    $id_cat      = intval($_POST['id_categorie'] ?? 0) ?: null;
    $image       = trim($_POST['image_url'] ?? '');
    $nouveaute   = isset($_POST['nouveaute']) ? 1 : 0;
    $bestseller  = isset($_POST['bestseller']) ? 1 : 0;
    $en_promo    = isset($_POST['en_promo']) ? 1 : 0;
    $prix_promo  = $en_promo ? floatval($_POST['prix_promo'] ?? 0) : null;

    try {
        $sql = "UPDATE produits SET nom=?, description=?, prix=?, stock=?, id_categorie=?, image_url=?,
                nouveaute=?, bestseller=?, en_promo=?, prix_promo=? WHERE id_produit=?";
        $pdo->prepare($sql)->execute([$nom, $description, $prix, $stock, $id_cat, $image, $nouveaute, $bestseller, $en_promo, $prix_promo, $id]);
        $message = "✅ Produit modifié avec succès !";
    } catch (Exception $e) {
        $erreur = "❌ Erreur BDD : " . $e->getMessage();
    }
}

// ── CHARGEMENT ───────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$filtre_cat = intval($_GET['categorie'] ?? 0);

$sql = "SELECT p.*, c.nom_categorie AS cat_nom FROM produits p LEFT JOIN categories c ON p.id_categorie = c.id_categorie WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND p.nom LIKE ?"; $params[] = "%$search%"; }
if ($filtre_cat) { $sql .= " AND p.id_categorie = ?"; $params[] = $filtre_cat; }
$sql .= " ORDER BY p.id_produit DESC";

try {
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $produits = $stmt->fetchAll();
    $categories = $pdo->query("SELECT * FROM categories ORDER BY nom_categorie")->fetchAll();
} catch (Exception $e) {
    $produits = []; $categories = [];
    $erreur = "❌ " . $e->getMessage();
}

// Produit à éditer ?
$edit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM produits WHERE id_produit = ?");
        $stmt->execute([$_GET['edit']]);
        $edit = $stmt->fetch();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Produits – Admin Lumoura</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',sans-serif; background:#f0f2f5; display:flex; min-height:100vh; }

.sidebar {
    width:240px; background:#1a1a2e; color:#ccc;
    display:flex; flex-direction:column; flex-shrink:0;
}
.sidebar-logo { padding:25px 20px; background:#16213e; color:#c9a227; font-size:1.2rem; font-weight:700; letter-spacing:1px; border-bottom:1px solid #0f3460; }
.sidebar-logo span { display:block; font-size:.7rem; color:#888; font-weight:400; margin-top:3px; }
.sidebar nav { flex:1; padding:15px 0; }
.sidebar nav a { display:flex; align-items:center; gap:12px; padding:12px 20px; color:#aaa; text-decoration:none; font-size:.9rem; transition:all .2s; }
.sidebar nav a:hover, .sidebar nav a.active { background:#0f3460; color:#c9a227; border-left:3px solid #c9a227; }
.sidebar nav a i { width:18px; text-align:center; }
.sidebar-footer { padding:15px 20px; border-top:1px solid #0f3460; font-size:.8rem; color:#666; }

.main { flex:1; display:flex; flex-direction:column; }
.topbar { background:#fff; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 4px rgba(0,0,0,.06); }
.topbar h1 { font-size:1.3rem; color:#1a1a2e; }
.btn-logout { background:#e74c3c; color:#fff; border:none; padding:7px 15px; border-radius:5px; cursor:pointer; font-size:.85rem; text-decoration:none; }
.content { padding:25px; flex:1; }

.alert { padding:12px 18px; border-radius:6px; margin-bottom:20px; font-size:.9rem; }
.alert-success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.alert-error   { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

/* ── Formulaire ── */
.form-card { background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.07); margin-bottom:25px; overflow:hidden; }
.form-card-header { padding:15px 20px; background:#1a1a2e; color:#c9a227; display:flex; justify-content:space-between; align-items:center; }
.form-card-header button { background:transparent; border:1px solid #c9a227; color:#c9a227; padding:4px 12px; border-radius:4px; cursor:pointer; font-size:.8rem; }
.form-body { padding:20px; display:none; }
.form-body.open { display:block; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
.form-group { display:flex; flex-direction:column; gap:5px; }
.form-group.full { grid-column:1/-1; }
label { font-size:.82rem; font-weight:600; color:#555; }
input[type=text], input[type=number], input[type=url], textarea, select {
    padding:9px 12px; border:1px solid #ddd; border-radius:6px; font-size:.9rem; outline:none; transition:border .2s; width:100%;
}
input:focus, textarea:focus, select:focus { border-color:#c9a227; }
textarea { resize:vertical; min-height:80px; }
.checkboxes { display:flex; gap:20px; align-items:center; flex-wrap:wrap; }
.checkboxes label { display:flex; align-items:center; gap:6px; font-weight:400; cursor:pointer; }
.btn-submit { background:#c9a227; color:#fff; border:none; padding:10px 25px; border-radius:6px; cursor:pointer; font-size:.95rem; font-weight:600; }
.btn-submit:hover { background:#a8861f; }
.btn-cancel { background:#6c757d; color:#fff; border:none; padding:10px 20px; border-radius:6px; cursor:pointer; font-size:.9rem; text-decoration:none; margin-left:10px; }

/* ── Filtres ── */
.filtres { background:#fff; padding:15px 20px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.07); margin-bottom:20px; display:flex; gap:15px; align-items:center; flex-wrap:wrap; }
.filtres input, .filtres select { padding:8px 12px; border:1px solid #ddd; border-radius:6px; font-size:.88rem; }
.filtres button { background:#c9a227; color:#fff; border:none; padding:8px 18px; border-radius:6px; cursor:pointer; }

/* ── Table ── */
.table-card { background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,.07); overflow:hidden; }
.table-card-header { padding:15px 20px; background:#1a1a2e; color:#c9a227; display:flex; justify-content:space-between; align-items:center; }
table { width:100%; border-collapse:collapse; }
th,td { padding:11px 14px; text-align:left; font-size:.84rem; border-bottom:1px solid #f0f0f0; }
th { background:#f8f9fa; color:#555; font-weight:600; }
tr:hover td { background:#fafafa; }
tr:last-child td { border-bottom:none; }
.product-img { width:50px; height:50px; object-fit:cover; border-radius:5px; }
.img-placeholder { width:50px; height:50px; background:#eee; border-radius:5px; display:flex; align-items:center; justify-content:center; color:#aaa; font-size:1.2rem; }
.badge { padding:3px 8px; border-radius:20px; font-size:.73rem; font-weight:600; }
.badge-yes { background:#d4edda; color:#155724; }
.badge-no  { background:#f8f9fa; color:#aaa; }
.btn-sm { padding:5px 11px; border-radius:4px; border:none; cursor:pointer; font-size:.78rem; text-decoration:none; display:inline-block; margin-right:3px; }
.btn-edit { background:#c9a227; color:#fff; }
.btn-del  { background:#e74c3c; color:#fff; }
.no-data { text-align:center; padding:40px; color:#aaa; }
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">💎 LUMOURA <span>Administration</span></div>
    <nav>
        <a href="index.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="produits.php" class="active"><i class="fas fa-gem"></i> Produits</a>
        <a href="categories.php"><i class="fas fa-tags"></i> Catégories</a>
        <a href="commandes.php"><i class="fas fa-shopping-bag"></i> Commandes</a>
        <a href="utilisateurs.php"><i class="fas fa-users"></i> Utilisateurs</a>
    </nav>
    <div class="sidebar-footer">Lumoura Admin v1.0</div>
</aside>

<div class="main">
<div class="topbar">
    <h1><i class="fas fa-gem"></i> Gestion des Produits</h1>
    <a href="../deconnexion.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
</div>
<div class="content">

<?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
<?php if ($erreur):   ?><div class="alert alert-error"><?= $erreur ?></div><?php endif; ?>

<!-- ── Formulaire Ajout / Édition ── -->
<div class="form-card">
    <div class="form-card-header">
        <span><i class="fas fa-<?= $edit ? 'edit' : 'plus-circle' ?>"></i>
              <?= $edit ? 'Modifier le produit : '.htmlspecialchars($edit['nom']) : 'Ajouter un nouveau produit' ?></span>
        <button onclick="toggleForm()">▼ Replier</button>
    </div>
    <div class="form-body <?= $edit ? 'open' : '' ?>" id="formBody">
        <form method="POST">
            <input type="hidden" name="action" value="<?= $edit ? 'modifier' : 'ajouter' ?>">
            <?php if ($edit): ?><input type="hidden" name="id_produit" value="<?= $edit['id_produit'] ?>"><?php endif; ?>

            <div class="form-grid">
                <div class="form-group">
                    <label>Nom du produit *</label>
                    <input type="text" name="nom" required value="<?= htmlspecialchars($edit['nom'] ?? '') ?>" placeholder="Ex: Bague Trinity Or">
                </div>
                <div class="form-group">
                    <label>Catégorie</label>
                    <select name="id_categorie">
                        <option value="">-- Aucune --</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id_categorie'] ?>" <?= ($edit['id_categorie'] ?? '') == $c['id_categorie'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nom_categorie']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Prix (€) *</label>
                    <input type="number" name="prix" step="0.01" min="0" required value="<?= $edit['prix'] ?? '' ?>" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" min="0" value="<?= $edit['stock'] ?? 0 ?>">
                </div>
                <div class="form-group full">
                    <label>URL de l'image</label>
                    <input type="text" name="image_url" value="<?= htmlspecialchars($edit['image_url'] ?? '') ?>" placeholder="https://... ou chemin relatif">
                </div>
                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="description"><?= htmlspecialchars($edit['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group full">
                    <div class="checkboxes">
                        <label><input type="checkbox" name="nouveaute" <?= ($edit['nouveaute'] ?? 0) ? 'checked' : '' ?>> Nouveauté</label>
                        <label><input type="checkbox" name="bestseller" <?= ($edit['bestseller'] ?? 0) ? 'checked' : '' ?>> Best-seller</label>
                        <label><input type="checkbox" name="en_promo" id="cbPromo" <?= ($edit['en_promo'] ?? 0) ? 'checked' : '' ?> onchange="togglePromo()"> En promo</label>
                        <span id="promoField" style="<?= ($edit['en_promo'] ?? 0) ? '' : 'display:none' ?>">
                            Prix promo : <input type="number" name="prix_promo" step="0.01" min="0" style="width:120px" value="<?= $edit['prix_promo'] ?? '' ?>"> €
                        </span>
                    </div>
                </div>
            </div>
            <br>
            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> <?= $edit ? 'Enregistrer les modifications' : 'Ajouter le produit' ?>
            </button>
            <?php if ($edit): ?>
            <a href="produits.php" class="btn-cancel"><i class="fas fa-times"></i> Annuler</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- ── Filtres ── -->
<form method="GET" class="filtres">
    <input type="text" name="search" placeholder="🔍 Rechercher un produit…" value="<?= htmlspecialchars($search) ?>">
    <select name="categorie">
        <option value="">Toutes catégories</option>
        <?php foreach ($categories as $c): ?>
        <option value="<?= $c['id_categorie'] ?>" <?= $filtre_cat == $c['id_categorie'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['nom_categorie']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Filtrer</button>
    <a href="produits.php" style="padding:8px 14px; background:#6c757d; color:#fff; border-radius:6px; text-decoration:none; font-size:.88rem;">Réinitialiser</a>
</form>

<!-- ── Liste produits ── -->
<div class="table-card">
    <div class="table-card-header">
        <span><i class="fas fa-list"></i> <?= count($produits) ?> produit(s)</span>
        <span style="font-size:.8rem;color:#aaa;">Cliquez sur Ajouter ci-dessus pour créer un produit</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Nom</th>
                <th>Catégorie</th>
                <th>Prix</th>
                <th>Stock</th>
                <th>Flags</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($produits)): ?>
            <tr><td colspan="7" class="no-data"><i class="fas fa-gem fa-2x" style="color:#ddd;display:block;margin-bottom:10px;"></i>Aucun produit trouvé.<br><small>Utilisez le formulaire ci-dessus pour ajouter votre premier produit.</small></td></tr>
        <?php else: foreach ($produits as $p): ?>
        <tr>
            <td>
                <?php if (!empty($p['image_url'])): ?>
                    <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="" class="product-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="img-placeholder" style="display:none"><i class="fas fa-gem"></i></div>
                <?php else: ?>
                    <div class="img-placeholder"><i class="fas fa-gem"></i></div>
                <?php endif; ?>
            </td>
            <td><strong><?= htmlspecialchars($p['nom']) ?></strong>
                <br><small style="color:#aaa;"><?= mb_strimwidth(htmlspecialchars($p['description'] ?? ''), 0, 50, '…') ?></small>
            </td>
            <td><?= htmlspecialchars($p['cat_nom'] ?? '—') ?></td>
            <td>
                <?php if ($p['en_promo'] && $p['prix_promo']): ?>
                    <span style="text-decoration:line-through;color:#aaa;"><?= number_format($p['prix'],2,',',' ') ?> €</span><br>
                    <strong style="color:#c9a227;"><?= number_format($p['prix_promo'],2,',',' ') ?> €</strong>
                <?php else: ?>
                    <?= number_format($p['prix'],2,',',' ') ?> €
                <?php endif; ?>
            </td>
            <td><?= $p['stock'] ?></td>
            <td>
                <?php if ($p['nouveaute']): ?><span class="badge badge-yes">Nouveau</span> <?php endif; ?>
                <?php if ($p['bestseller']): ?><span class="badge badge-yes">Best</span> <?php endif; ?>
                <?php if ($p['en_promo']): ?><span class="badge" style="background:#fff3cd;color:#856404;">Promo</span><?php endif; ?>
            </td>
            <td>
                <a href="produits.php?edit=<?= $p['id_produit'] ?>" class="btn-sm btn-edit"><i class="fas fa-edit"></i> Éditer</a>
                <a href="produits.php?supprimer=<?= $p['id_produit'] ?>" class="btn-sm btn-del" onclick="return confirm('Supprimer ce produit ?')"><i class="fas fa-trash"></i></a>
            </td>
        </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

</div><!-- /content -->
</div><!-- /main -->

<script>
function toggleForm() {
    const fb = document.getElementById('formBody');
    fb.classList.toggle('open');
}
// Ouvrir le form automatiquement si pas en mode édition
<?php if (!$edit && (isset($_POST['action']) || $erreur)): ?>
document.getElementById('formBody').classList.add('open');
<?php elseif (!$edit): ?>
document.getElementById('formBody').classList.remove('open');
<?php endif; ?>

function togglePromo() {
    const show = document.getElementById('cbPromo').checked;
    document.getElementById('promoField').style.display = show ? '' : 'none';
}
</script>
</body>
</html>