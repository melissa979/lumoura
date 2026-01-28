<?php
// Page catalogue - Affichage des produits (version BIJOUX corrigée)
// Fichier : pages/catalogue.php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Récupération et sécurisation des paramètres GET
$page          = max(1, (int)($_GET['page'] ?? 1));
$category      = trim($_GET['category'] ?? '');
$filter        = trim($_GET['filter'] ?? '');
$search        = trim($_GET['search'] ?? '');
$sort          = trim($_GET['sort'] ?? 'newest');
$matiere       = trim($_GET['matiere'] ?? '');
$marque        = trim($_GET['marque'] ?? '');
$max_price     = (float)($_GET['max_price'] ?? 0);

// Paramètres de pagination
$limit  = 12;
$offset = ($page - 1) * $limit;

// Construction dynamique de la clause WHERE
$where  = [];
$params = [];

// Filtre par catégorie
if ($category) {
    $where[] = "c.nom_categorie = :category";
    $params[':category'] = $category;
}

// Filtres nouveautés / best-seller / promo
if ($filter === 'new') {
    $where[] = "p.nouveaute = 1";
} elseif ($filter === 'bestseller') {
    $where[] = "p.bestseller = 1";
} elseif ($filter === 'promo') {
    $where[] = "p.promotion_pourcentage > 0";
}

// Recherche texte
if ($search) {
    $where[] = "(p.nom LIKE :search OR p.description LIKE :search OR p.description_courte LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

// Filtre matière
if ($matiere) {
    $where[] = "p.matiere = :matiere";
    $params[':matiere'] = $matiere;
}

// Filtre marque
if ($marque) {
    $where[] = "p.marque = :marque";
    $params[':marque'] = $marque;
}

// Filtre prix max
if ($max_price > 0) {
    $where[] = "p.prix <= :max_price";
    $params[':max_price'] = $max_price;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// 1. COUNT total produits
$countQuery = "SELECT COUNT(*) FROM produits p LEFT JOIN categories c ON p.id_categorie = c.id_categorie $whereClause";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$total_products = (int) $countStmt->fetchColumn();

// 2. Tri
$orderBy = match($sort) {
    'price_asc'  => 'p.prix ASC',
    'price_desc' => 'p.prix DESC',
    'name_asc'   => 'p.nom ASC',
    'name_desc'  => 'p.nom DESC',
    default      => 'p.date_ajout DESC',
};

// 3. Requête principale
$query = "
    SELECT p.*, c.nom_categorie
    FROM produits p 
    LEFT JOIN categories c ON p.id_categorie = c.id_categorie 
    $whereClause 
    ORDER BY $orderBy 
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

try {
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur SQL dans la requête principale : " . $e->getMessage());
}

$totalPages = $total_products > 0 ? ceil($total_products / $limit) : 1;

$pageTitle = "Notre Collection de Bijoux";
if ($category) $pageTitle .= " – " . ucfirst($category);
if ($filter) $pageTitle .= " – " . ucfirst($filter);

include '../includes/header.php';
?>

<div class="container">
    <div class="catalogue-header">
        <h1 class="page-title">Notre Collection de Bijoux</h1>
        <p class="page-subtitle">Découvrez nos créations élégantes en or, argent, pierres précieuses et design intemporel</p>
    </div>

    <?php if (isset($_GET['added'])): ?>
    <div class="alert success" style="padding: 15px; margin: 20px 0; background: #e6ffe6; border: 1px solid #b3ffb3; border-radius: 6px; color: #006600;">
        <strong>Article ajouté !</strong> Le bijou a été ajouté à votre panier.
        <a href="panier.php" style="margin-left: 15px; color: #006600; font-weight: bold;">Voir mon panier →</a>
    </div>
    <?php endif; ?>

    <div class="catalogue-stats">
        <div class="stat-item">
            <i class="fas fa-gem"></i>
            <span><?php echo $total_products; ?> bijoux</span>
        </div>
        <div class="stat-item">
            <i class="fas fa-shipping-fast"></i>
            <span>Livraison gratuite dès 150 €</span>
        </div>
        <div class="stat-item">
            <i class="fas fa-undo"></i>
            <span>Retour gratuit 30 jours</span>
        </div>
    </div>

    <div class="catalogue-layout">
        <!-- Sidebar filtres -->
        <aside class="filters-sidebar">
            <div class="filters-header">
                <h3><i class="fas fa-filter"></i> Filtres</h3>
                <?php if ($category || $filter || $search || $matiere || $marque || $max_price > 0): ?>
                    <a href="catalogue.php" class="clear-filters">Effacer tous</a>
                <?php endif; ?>
            </div>

            <!-- Catégories -->
            <div class="filter-section">
                <h4 class="filter-title">Catégories</h4>
                <ul class="filter-list">
                    <li><a href="?page=1" class="filter-link <?php echo !$category ? 'active' : ''; ?>">Tous les bijoux</a></li>
                    <li><a href="?category=Femme&page=1" class="filter-link <?php echo $category === 'Femme' ? 'active' : ''; ?>"><i class="fas fa-venus"></i> Femme</a></li>
                    <li><a href="?category=Homme&page=1" class="filter-link <?php echo $category === 'Homme' ? 'active' : ''; ?>"><i class="fas fa-mars"></i> Homme</a></li>
                    <li><a href="?category=Unisexe&page=1" class="filter-link <?php echo $category === 'Unisexe' ? 'active' : ''; ?>"><i class="fas fa-venus-mars"></i> Unisexe</a></li>
                </ul>
            </div>

            <!-- Sélections -->
            <div class="filter-section">
                <h4 class="filter-title">Sélections</h4>
                <ul class="filter-list">
                    <li><a href="?filter=new&page=1" class="<?php echo $filter === 'new' ? 'active' : ''; ?>"><i class="fas fa-star"></i> Nouveautés</a></li>
                    <li><a href="?filter=bestseller&page=1" class="<?php echo $filter === 'bestseller' ? 'active' : ''; ?>"><i class="fas fa-fire"></i> Best-sellers</a></li>
                    <li><a href="?filter=promo&page=1" class="<?php echo $filter === 'promo' ? 'active' : ''; ?>"><i class="fas fa-tag"></i> Promotions</a></li>
                </ul>
            </div>

            <!-- Matière -->
            <div class="filter-section">
                <h4 class="filter-title">Matière</h4>
                <ul class="filter-list">
                    <?php
                    try {
                        $sql = "SELECT DISTINCT matiere FROM produits WHERE matiere IS NOT NULL ORDER BY matiere";
                        $stmt = $pdo->query($sql);
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $val = $row['matiere'];
                            $active = ($matiere === $val) ? 'active' : '';
                            echo "<li><a href=\"?matiere=" . urlencode($val) . "&page=1\" class=\"filter-link $active\">";
                            echo "<i class=\"fas fa-gem\"></i> " . htmlspecialchars($val);
                            echo "</a></li>";
                        }
                    } catch (Exception $e) {
                        echo "<li><span class=\"text-muted\">Indisponible</span></li>";
                    }
                    ?>
                </ul>
            </div>

            <!-- Maisons / Créateurs -->
            <div class="filter-section">
                <h4 class="filter-title">Maisons / Créateurs</h4>
                <ul class="filter-list">
                    <?php
                    try {
                        $sql = "SELECT DISTINCT marque FROM produits WHERE marque IS NOT NULL ORDER BY marque";
                        $stmt = $pdo->query($sql);
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $val = $row['marque'];
                            $active = ($marque === $val) ? 'active' : '';
                            echo "<li><a href=\"?marque=" . urlencode($val) . "&page=1\" class=\"filter-link $active\">";
                            echo "<i class=\"fas fa-copyright\"></i> " . htmlspecialchars($val);
                            echo "</a></li>";
                        }
                    } catch (Exception $e) {
                        echo "<li><span class=\"text-muted\">Indisponible</span></li>";
                    }
                    ?>
                </ul>
            </div>

            <!-- Prix max -->
            <div class="filter-section">
                <h4 class="filter-title">Prix maximum</h4>
                <div class="price-range">
                    <div class="price-labels"><span>0 €</span><span>800 €</span></div>
                    <input type="range" min="0" max="800" value="<?php echo $max_price ?: 800; ?>" class="price-slider" id="priceSlider">
                    <div class="price-selected">Jusqu'à : <span id="priceValue"><?php echo $max_price ?: 800; ?> €</span></div>
                    <button id="applyPrice" class="btn-apply-price">Appliquer</button>
                </div>
            </div>
        </aside>

        <!-- Contenu principal -->
        <main class="products-main">
            <div class="products-toolbar">
                <div class="results-info">
                    <strong><?php echo $total_products; ?></strong> 
                    <?php echo $total_products === 1 ? 'bijou trouvé' : 'bijoux trouvés'; ?>
                    <?php if ($search): ?>
                        pour « <?php echo htmlspecialchars($search); ?> »
                    <?php endif; ?>
                </div>

                <div class="sort-options">
                    <label for="sortSelect">Trier par :</label>
                    <select id="sortSelect" class="sort-select" onchange="window.location = this.value;">
                        <?php
                        $baseUrl = '?' . http_build_query(array_merge($_GET, ['sort' => '', 'page' => 1]));
                        $options = [
                            'newest'     => 'Nouveautés',
                            'price_asc'  => 'Prix croissant',
                            'price_desc' => 'Prix décroissant',
                            'name_asc'   => 'Nom A–Z',
                            'name_desc'  => 'Nom Z–A',
                        ];
                        foreach ($options as $val => $label) {
                            $selected = ($sort === $val) ? 'selected' : '';
                            echo "<option value=\"{$baseUrl}sort=$val\" $selected>$label</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Recherche -->
            <div class="search-box-container">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Rechercher un bijou, une bague, un collier…" value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                    <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
                    <?php foreach (['category', 'filter', 'matiere', 'marque'] as $f): ?>
                        <?php if (!empty($_GET[$f])): ?>
                            <input type="hidden" name="<?php echo $f; ?>" value="<?php echo htmlspecialchars($_GET[$f]); ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </form>
            </div>

            <?php if ($total_products > 0): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): 
                        $price = $product['prix'];
                        $discount = $product['promotion_pourcentage'] ?? 0;
                        $final_price = $discount > 0 ? $price * (1 - $discount / 100) : $price;
                        $image = $product['image_url'] ?: 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?auto=format&fit=crop&w=800&q=80';
                    ?>
                        <div class="product-card">
                            <?php if ($discount > 0): ?>
                                <span class="product-badge discount">−<?php echo $discount; ?>%</span>
                            <?php elseif ($product['nouveaute']): ?>
                                <span class="product-badge new">Nouveau</span>
                            <?php elseif ($product['bestseller']): ?>
                                <span class="product-badge bestseller">Best-seller</span>
                            <?php endif; ?>

                            <div class="product-image">
                                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" loading="lazy">
                                <div class="product-overlay">
                                    <a href="produit.php?id=<?php echo $product['id_produit']; ?>" class="view-details">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                </div>
                            </div>

                            <div class="product-info">
                                <div class="product-meta">
                                    <span class="product-brand"><?php echo htmlspecialchars($product['marque'] ?? ''); ?></span>
                                    <span class="product-category"><?php echo htmlspecialchars($product['nom_categorie'] ?? 'Bijoux'); ?></span>
                                </div>

                                <h3 class="product-name">
                                    <a href="produit.php?id=<?php echo $product['id_produit']; ?>">
                                        <?php echo htmlspecialchars($product['nom']); ?>
                                    </a>
                                </h3>

                                <div class="product-pricing">
                                    <?php if ($discount > 0): ?>
                                        <div class="original-price"><?php echo formatPrice($price); ?></div>
                                    <?php endif; ?>
                                    <div class="current-price"><?php echo formatPrice($final_price); ?></div>
                                </div>

                                <div class="product-actions">
                                    <?php if ($product['stock'] > 0): ?>
                                        <?php if (isLoggedIn()): ?>
                                            <form method="POST" action="ajouter_au_panier.php" class="add-cart-form">
                                                <input type="hidden" name="produit_id" value="<?php echo $product['id_produit']; ?>">
                                                <input type="hidden" name="quantite" value="1">
                                                <input type="hidden" name="redirect_url" value="panier.php">
                                                <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                                    <i class="fas fa-cart-plus"></i> Ajouter
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <a href="connexion.php?redirect=catalogue" class="btn-add-cart disabled" title="Connectez-vous pour ajouter au panier">
                                                <i class="fas fa-lock"></i> Connectez-vous
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="stock-rupture">Rupture</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php
                        $queryString = http_build_query(array_diff_key($_GET, ['page' => '']));
                        for ($i = 1; $i <= $totalPages; $i++):
                            $active = $i === $page ? 'active' : '';
                        ?>
                            <a href="?<?php echo $queryString ? $queryString . '&' : ''; ?>page=<?php echo $i; ?>" class="<?php echo $active; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="no-products">
                    <i class="fas fa-gem fa-3x"></i>
                    <h3>Aucun bijou ne correspond à vos critères</h3>
                    <p>Modifiez les filtres ou découvrez toute notre collection.</p>
                    <a href="catalogue.php" class="btn-primary">Voir tous les bijoux</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Script pour le slider de prix -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const priceSlider = document.getElementById('priceSlider');
    const priceValue = document.getElementById('priceValue');
    const applyBtn = document.getElementById('applyPrice');

    if (priceSlider && priceValue && applyBtn) {
        priceSlider.addEventListener('input', function() {
            priceValue.textContent = this.value + ' €';
        });

        applyBtn.addEventListener('click', function() {
            let url = new URL(window.location);
            url.searchParams.set('max_price', priceSlider.value);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>