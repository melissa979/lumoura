<?php
/**
 * Tableau de bord admin unifié
 * Fichier : pages/admin/admin.php
 */
require_once __DIR__ . '/auth_admin.php';

function adminEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminMoney($value): string
{
    return number_format((float) $value, 2, ',', ' ') . ' €';
}

function adminFetchValue(PDO $pdo, string $sql, $default = 0)
{
    try {
        $value = $pdo->query($sql)->fetchColumn();
        return $value !== false && $value !== null ? $value : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

$nbProduits = (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM produits');
$nbCommandes = (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM commandes');
$nbUtilisateurs = (int) adminFetchValue($pdo, 'SELECT COUNT(*) FROM utilisateurs');
$nbEnAttente = (int) adminFetchValue($pdo, "SELECT COUNT(*) FROM commandes WHERE statut = 'en attente' OR statut IS NULL OR statut = ''");
$caTotal = (float) adminFetchValue($pdo, "SELECT COALESCE(SUM(CASE WHEN statut <> 'annulee' OR statut IS NULL THEN montant ELSE 0 END), 0) FROM commandes");
$caMois = (float) adminFetchValue($pdo, "SELECT COALESCE(SUM(CASE WHEN (statut <> 'annulee' OR statut IS NULL) AND DATE_FORMAT(date_commande, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN montant ELSE 0 END), 0) FROM commandes");

try {
    $stmt = $pdo->query('SELECT id_produit, nom, prix, stock, image_url, id_categorie FROM produits ORDER BY id_produit DESC LIMIT 5');
    $produitsRecents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $produitsRecents = [];
}

try {
    $stmt = $pdo->query(''
        . 'SELECT c.id_commande, c.numero_commande, c.date_commande, c.statut, c.montant, '
        . 'u.prenom, u.nom '
        . 'FROM commandes c '
        . 'LEFT JOIN utilisateurs u ON u.id_utilisateur = c.id_utilisateur '
        . 'ORDER BY c.date_commande DESC LIMIT 5'
    );
    $commandesRecentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $commandesRecentes = [];
}

$statutLabels = [
    'en attente' => ['label' => 'En attente', 'class' => 'status-wait'],
    'payee' => ['label' => 'Payée', 'class' => 'status-paid'],
    'expediee' => ['label' => 'Expédiée', 'class' => 'status-shipped'],
    'livree' => ['label' => 'Livrée', 'class' => 'status-delivered'],
    'annulee' => ['label' => 'Annulée', 'class' => 'status-cancelled'],
];

$pageTitle = "Tableau de bord admin - Éclat d'Or";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= adminEscape($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #d4af37;
            --gold-dark: #a98216;
            --brown: #3d2b28;
            --dark: #211816;
            --line: #ece3d9;
            --muted: #8b7b73;
            --bg: #f7f2ec;
        }

        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; background: var(--bg); color: var(--brown); font-family: 'Montserrat', Arial, sans-serif; font-size: 14px; }
        a { color: inherit; text-decoration: none; }

        .admin-sidebar { width: 245px; min-height: 100vh; flex-shrink: 0; display: flex; flex-direction: column; position: sticky; top: 0; background: linear-gradient(180deg, #2a1e1b, var(--dark)); color: #cdbfb6; }
        .admin-logo { padding: 28px 24px 24px; border-bottom: 1px solid rgba(255,255,255,.07); }
        .admin-logo strong { display: block; color: #fff; font-family: 'Playfair Display', serif; font-size: 21px; letter-spacing: 2px; }
        .admin-logo span { color: var(--gold); font-size: 10px; letter-spacing: 3px; text-transform: uppercase; }
        .admin-nav { flex: 1; padding: 18px 12px; }
        .admin-nav a, .admin-footer a { display: flex; align-items: center; gap: 12px; padding: 12px 14px; margin-bottom: 4px; border-radius: 10px; font-size: 13px; font-weight: 600; transition: .2s; }
        .admin-nav a i, .admin-footer a i { width: 18px; text-align: center; color: #8f7f75; }
        .admin-nav a:hover, .admin-footer a:hover { background: rgba(255,255,255,.06); color: #fff; }
        .admin-nav a.active { background: rgba(212,175,55,.14); color: #fff; }
        .admin-nav a.active i { color: var(--gold); }
        .admin-footer { padding: 16px 12px; border-top: 1px solid rgba(255,255,255,.07); }

        .admin-main { flex: 1; min-width: 0; padding: 32px 36px 60px; }
        .admin-topbar { display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; margin-bottom: 26px; flex-wrap: wrap; }
        .admin-kicker { color: var(--gold-dark); font-size: 11px; font-weight: 800; letter-spacing: 2px; text-transform: uppercase; }
        .admin-topbar h1 { margin: 4px 0 0; font-family: 'Playfair Display', serif; font-size: 34px; }
        .admin-topbar p { margin: 8px 0 0; color: var(--muted); }
        .admin-view-shop { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border: 1px solid var(--line); border-radius: 10px; background: #fff; color: var(--brown); font-size: 12px; font-weight: 700; }
        .admin-view-shop:hover { border-color: var(--gold); color: var(--gold-dark); }

        .admin-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
        .admin-kpi { position: relative; overflow: hidden; padding: 20px; border: 1px solid var(--line); border-radius: 18px; background: #fff; box-shadow: 0 8px 20px rgba(61,43,40,.05); }
        .admin-kpi i { position: absolute; top: 18px; right: 18px; width: 40px; height: 40px; display: grid; place-items: center; border-radius: 12px; background: #fbf3df; color: var(--gold-dark); }
        .admin-kpi span { color: var(--muted); font-size: 12px; font-weight: 600; }
        .admin-kpi strong { display: block; margin-top: 8px; font-size: 24px; }
        .admin-kpi.highlight { border: 0; background: linear-gradient(135deg, var(--brown), #5e4135); color: #fff; }
        .admin-kpi.highlight span { color: rgba(255,255,255,.75); }
        .admin-kpi.highlight i { background: rgba(255,255,255,.14); color: #f5d56c; }

        .admin-grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); gap: 22px; }
        .admin-card { overflow: hidden; border: 1px solid var(--line); border-radius: 18px; background: #fff; box-shadow: 0 8px 20px rgba(61,43,40,.05); }
        .admin-card-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--line); }
        .admin-card-header h2 { margin: 0; font-family: 'Playfair Display', serif; font-size: 20px; }
        .admin-card-header a { color: var(--gold-dark); font-size: 12px; font-weight: 800; }
        .admin-card-header a:hover { color: var(--brown); }
        .admin-card-body { padding: 0 20px 18px; }

        .admin-table { width: 100%; border-collapse: collapse; }
        .admin-table th { padding: 12px 8px; color: var(--muted); border-bottom: 1px solid var(--line); font-size: 10px; text-align: left; letter-spacing: 1px; text-transform: uppercase; }
        .admin-table td { padding: 12px 8px; border-bottom: 1px solid #f3ece4; vertical-align: middle; font-size: 12px; }
        .admin-table tr:last-child td { border-bottom: 0; }
        .product-cell { display: flex; align-items: center; gap: 10px; }
        .product-cell img { width: 42px; height: 42px; border-radius: 9px; object-fit: cover; background: #eee4da; }
        .product-cell strong { display: block; font-size: 12px; }
        .product-cell span, .admin-muted { color: var(--muted); font-size: 10px; }
        .admin-price { color: var(--gold-dark); font-weight: 800; white-space: nowrap; }
        .admin-stock { font-weight: 700; }
        .stock-low { color: #c2410c; }
        .stock-ok { color: #16804b; }

        .order-row { display: flex; align-items: center; gap: 12px; padding: 13px 0; border-bottom: 1px solid #f3ece4; }
        .order-row:last-child { border-bottom: 0; }
        .order-row-main { flex: 1; min-width: 0; }
        .order-row-main strong { display: block; font-size: 12px; }
        .order-row-main span { display: block; margin-top: 3px; color: var(--muted); font-size: 10px; }
        .order-row-amount { color: var(--gold-dark); font-size: 12px; font-weight: 800; white-space: nowrap; }
        .status { display: inline-flex; align-items: center; gap: 5px; padding: 5px 9px; border-radius: 20px; font-size: 10px; font-weight: 800; white-space: nowrap; }
        .status-wait { background: #fff3cd; color: #856404; }
        .status-paid { background: #e0f2fe; color: #075985; }
        .status-shipped { background: #dbeafe; color: #1e40af; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .admin-shortcuts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 22px; }
        .shortcut { display: flex; align-items: center; gap: 10px; padding: 15px; border: 1px solid var(--line); border-radius: 14px; background: #fff; font-size: 12px; font-weight: 700; transition: .2s; }
        .shortcut i { color: var(--gold-dark); font-size: 18px; }
        .shortcut:hover { border-color: var(--gold); transform: translateY(-2px); box-shadow: 0 8px 18px rgba(61,43,40,.08); }

        .admin-empty { padding: 28px 10px; color: var(--muted); text-align: center; font-size: 13px; }
        .admin-empty i { display: block; margin-bottom: 8px; color: var(--gold); font-size: 24px; }

        @media (max-width: 1100px) { .admin-grid { grid-template-columns: 1fr; } }
        @media (max-width: 900px) { .admin-sidebar { width: 72px; } .admin-logo span, .admin-nav span, .admin-footer span { display: none; } .admin-logo strong { font-size: 14px; letter-spacing: 0; } .admin-kpis { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 650px) { .admin-main { padding: 22px 15px 50px; } .admin-kpis { grid-template-columns: 1fr 1fr; gap: 10px; } .admin-kpi { padding: 15px; } .admin-kpi strong { font-size: 19px; } .admin-kpi i { display: none; } .admin-shortcuts { grid-template-columns: 1fr; } .hide-mobile { display: none; } }
    </style>
</head>
<body>
    <aside class="admin-sidebar">
        <div class="admin-logo">
            <strong>ÉCLAT D'OR</strong>
            <span>Administration</span>
        </div>
        <nav class="admin-nav">
            <a href="admin.php" class="active"><i class="fas fa-chart-pie"></i><span>Tableau de bord</span></a>
            <a href="Commandes.php"><i class="fas fa-receipt"></i><span>Commandes</span></a>
            <a href="Produits.php"><i class="fas fa-gem"></i><span>Produits</span></a>
            <a href="Categories.php"><i class="fas fa-layer-group"></i><span>Catégories</span></a>
            <a href="Utilisateurs.php"><i class="fas fa-users"></i><span>Clients</span></a>
        </nav>
        <div class="admin-footer">
            <a href="../../index.php"><i class="fas fa-store"></i><span>Voir la boutique</span></a>
            <a href="../deconnexion.php"><i class="fas fa-sign-out-alt"></i><span>Déconnexion</span></a>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <span class="admin-kicker">Espace sécurisé</span>
                <h1>Tableau de bord</h1>
                <p>Bienvenue dans l'administration d'Éclat d'Or.</p>
            </div>
            <a href="../../index.php" class="admin-view-shop"><i class="fas fa-store"></i> Voir la boutique</a>
        </header>

        <section class="admin-kpis">
            <div class="admin-kpi highlight"><i class="fas fa-bell"></i><span>Commandes à traiter</span><strong><?= $nbEnAttente ?></strong></div>
            <div class="admin-kpi"><i class="fas fa-gem"></i><span>Produits</span><strong><?= $nbProduits ?></strong></div>
            <div class="admin-kpi"><i class="fas fa-users"></i><span>Clients</span><strong><?= $nbUtilisateurs ?></strong></div>
            <div class="admin-kpi"><i class="fas fa-chart-line"></i><span>Chiffre d'affaires</span><strong><?= adminMoney($caTotal) ?></strong></div>
        </section>

        <div class="admin-grid">
            <section class="admin-card">
                <div class="admin-card-header">
                    <h2>Derniers produits</h2>
                    <a href="Produits.php">Gérer les produits <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="admin-card-body">
                    <?php if (empty($produitsRecents)): ?>
                        <div class="admin-empty"><i class="fas fa-gem"></i>Aucun produit enregistré.</div>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead><tr><th>Produit</th><th>Prix</th><th>Stock</th></tr></thead>
                            <tbody>
                            <?php foreach ($produitsRecents as $produit): ?>
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <img src="<?= adminEscape($produit['image_url'] ?: 'https://via.placeholder.com/80?text=Bijou') ?>" alt="" onerror="this.src='https://via.placeholder.com/80?text=Bijou'">
                                            <div><strong><?= adminEscape($produit['nom']) ?></strong><span>ID #<?= (int) $produit['id_produit'] ?></span></div>
                                        </div>
                                    </td>
                                    <td class="admin-price"><?= adminMoney($produit['prix']) ?></td>
                                    <td class="admin-stock <?= (int) $produit['stock'] <= 5 ? 'stock-low' : 'stock-ok' ?>"><?= (int) $produit['stock'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>

            <section class="admin-card">
                <div class="admin-card-header">
                    <h2>Dernières commandes</h2>
                    <a href="Commandes.php">Tout voir <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="admin-card-body">
                    <?php if (empty($commandesRecentes)): ?>
                        <div class="admin-empty"><i class="fas fa-receipt"></i>Aucune commande enregistrée.</div>
                    <?php else: ?>
                        <?php foreach ($commandesRecentes as $commande):
                            $status = $statutLabels[$commande['statut'] ?: 'en attente'] ?? $statutLabels['en attente'];
                        ?>
                            <a class="order-row" href="Commandes.php?detail=<?= (int) $commande['id_commande'] ?>">
                                <div class="order-row-main">
                                    <strong><?= adminEscape(strtoupper($commande['numero_commande'])) ?></strong>
                                    <span><?= adminEscape(trim(($commande['prenom'] ?? '') . ' ' . ($commande['nom'] ?? ''))) ?> · <?= date('d/m/Y', strtotime($commande['date_commande'])) ?></span>
                                </div>
                                <span class="status <?= $status['class'] ?>"><i class="fas <?= $status['class'] === 'status-wait' ? 'fa-hourglass-half' : 'fa-circle' ?>"></i><?= $status['label'] ?></span>
                                <span class="order-row-amount"><?= adminMoney($commande['montant']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="admin-shortcuts">
            <a href="Commandes.php?statut=en+attente" class="shortcut"><i class="fas fa-bell"></i> Traiter les commandes</a>
            <a href="Produits.php" class="shortcut"><i class="fas fa-plus-circle"></i> Ajouter un produit</a>
            <a href="Utilisateurs.php" class="shortcut"><i class="fas fa-users"></i> Gérer les clients</a>
        </div>
    </main>
</body>
</html>
