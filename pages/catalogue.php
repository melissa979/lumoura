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

if ($category) {
    $where[] = "c.nom_categorie = :category";
    $params[':category'] = $category;
}

if ($filter === 'new') {
    $where[] = "p.nouveaute = 1";
} elseif ($filter === 'bestseller') {
    $where[] = "p.bestseller = 1";
} elseif ($filter === 'promo') {
    $where[] = "p.promotion_pourcentage > 0";
}

if ($search) {
    $where[] = "(p.nom LIKE :search OR p.description LIKE :search OR p.description_courte LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($matiere) {
    $where[] = "p.matiere = :matiere";
    $params[':matiere'] = $matiere;
}

if ($marque) {
    $where[] = "p.marque = :marque";
    $params[':marque'] = $marque;
}

if ($max_price > 0) {
    $where[] = "p.prix <= :max_price";
    $params[':max_price'] = $max_price;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// COUNT total
$countQuery = "SELECT COUNT(*) FROM produits p LEFT JOIN categories c ON p.id_categorie = c.id_categorie $whereClause";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$total_products = (int) $countStmt->fetchColumn();

// Tri
$orderBy = match($sort) {
    'price_asc'  => 'p.prix ASC',
    'price_desc' => 'p.prix DESC',
    'name_asc'   => 'p.nom ASC',
    'name_desc'  => 'p.nom DESC',
    default      => 'p.date_ajout DESC',
};

// Requête principale
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
    die("Erreur SQL : " . $e->getMessage());
}

$totalPages = $total_products > 0 ? ceil($total_products / $limit) : 1;

// ✅ Récupère les favoris de l'utilisateur connecté (pour colorer les cœurs)
$userFavoris = [];
if (isLoggedIn()) {
    $favStmt = $pdo->prepare("SELECT id_produit FROM favori WHERE id_utilisateur = ?");
    $favStmt->execute([$_SESSION['user_id']]);
    $userFavoris = array_column($favStmt->fetchAll(PDO::FETCH_ASSOC), 'id_produit');
}

$pageTitle = "Notre Collection de Bijoux";
if ($category) $pageTitle .= " – " . ucfirst($category);
if ($filter)   $pageTitle .= " – " . ucfirst($filter);

include '../includes/header.php';
?>

<div class="container">
    <div class="catalogue-header">
        <h1 class="page-title">Notre Collection de Bijoux</h1>
        <p class="page-subtitle">Découvrez nos créations élégantes en or, argent, pierres précieuses et design intemporel</p>
    </div>

    <?php if (isset($_GET['added'])): ?>
    <div class="alert success" style="padding:15px;margin:20px 0;background:#e6ffe6;border:1px solid #b3ffb3;border-radius:6px;color:#006600;">
        <strong>Article ajouté !</strong> Le bijou a été ajouté à votre panier.
        <a href="panier.php" style="margin-left:15px;color:#006600;font-weight:bold;">Voir mon panier →</a>
    </div>
    <?php endif; ?>

    <div class="catalogue-stats">
        <div class="stat-item"><i class="fas fa-gem"></i><span><?= $total_products ?> bijoux</span></div>
        <div class="stat-item"><i class="fas fa-shipping-fast"></i><span>Livraison gratuite dès 150 €</span></div>
        <div class="stat-item"><i class="fas fa-undo"></i><span>Retour gratuit 30 jours</span></div>
    </div>

    <div class="catalogue-layout">

        <!-- ===== SIDEBAR FILTRES ===== -->
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
                    <!-- ✅ CORRIGÉ : "Tous les bijoux" vide maintenant TOUS les filtres (pas seulement category) -->
                    <li><a href="catalogue.php" class="filter-link <?= !$category && !$filter && !$matiere && !$marque && !$max_price ? 'active' : '' ?>">Tous les bijoux</a></li>
                    <li><a href="?category=Femme&page=1" class="filter-link <?= $category === 'Femme' ? 'active' : '' ?>"><i class="fas fa-venus"></i> Femme</a></li>
                    <li><a href="?category=Homme&page=1" class="filter-link <?= $category === 'Homme' ? 'active' : '' ?>"><i class="fas fa-mars"></i> Homme</a></li>
                    <li><a href="?category=Unisexe&page=1" class="filter-link <?= $category === 'Unisexe' ? 'active' : '' ?>"><i class="fas fa-venus-mars"></i> Unisexe</a></li>
                </ul>
            </div>

            <!-- Sélections -->
           <div class="filter-section">
    <h4 class="filter-title">Sélections</h4>
    <ul class="filter-list">
       <li><a href="?filter=new&page=1" class="filter-link <?= $filter === 'new' ? 'active' : '' ?>"><i class="fas fa-star"></i> Nouveautés</a></li>
       <li><a href="?filter=bestseller&page=1" class="filter-link <?= $filter === 'bestseller' ? 'active' : '' ?>"><i class="fas fa-fire"></i> Best-sellers</a></li>
       <li><a href="?filter=promo&page=1" class="filter-link <?= $filter === 'promo' ? 'active' : '' ?>"><i class="fas fa-tag"></i> Promotions</a></li>
    </ul>
</div>

            <!-- Matière -->
            <div class="filter-section">
                <h4 class="filter-title">Matière</h4>
                <ul class="filter-list">
                    <?php
                    try {
                        $rows = $pdo->query("SELECT DISTINCT matiere FROM produits WHERE matiere IS NOT NULL ORDER BY matiere")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($rows as $row) {
                            $val = $row['matiere'];
                            $active = ($matiere === $val) ? 'active' : '';
                            echo "<li><a href=\"?matiere=" . urlencode($val) . "&page=1\" class=\"filter-link $active\"><i class=\"fas fa-gem\"></i> " . htmlspecialchars($val) . "</a></li>";
                        }
                    } catch (Exception $e) {
                        echo "<li><span class='text-muted'>Indisponible</span></li>";
                    }
                    ?>
                </ul>
            </div>

            <!-- Maisons -->
            <div class="filter-section">
                <h4 class="filter-title">Maisons / Créateurs</h4>
                <ul class="filter-list">
                    <?php
                    try {
                        $rows = $pdo->query("SELECT DISTINCT marque FROM produits WHERE marque IS NOT NULL ORDER BY marque")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($rows as $row) {
                            $val = $row['marque'];
                            $active = ($marque === $val) ? 'active' : '';
                            echo "<li><a href=\"?marque=" . urlencode($val) . "&page=1\" class=\"filter-link $active\"><i class=\"fas fa-copyright\"></i> " . htmlspecialchars($val) . "</a></li>";
                        }
                    } catch (Exception $e) {
                        echo "<li><span class='text-muted'>Indisponible</span></li>";
                    }
                    ?>
                </ul>
            </div>

            <!-- Prix max -->
            <div class="filter-section">
                <h4 class="filter-title">Prix maximum</h4>
                <div class="price-range">
                    <div class="price-labels"><span>0 €</span><span>800 €</span></div>
                    <input type="range" min="0" max="800" value="<?= $max_price ?: 800 ?>" class="price-slider" id="priceSlider">
                    <div class="price-selected">Jusqu'à : <span id="priceValue"><?= $max_price ?: 800 ?> €</span></div>
                    <button id="applyPrice" class="btn-apply-price">Appliquer</button>
                </div>
            </div>
        </aside>

        <!-- ===== CONTENU PRINCIPAL ===== -->
        <main class="products-main">

            <div class="products-toolbar">
                <div class="results-info">
                    <strong><?= $total_products ?></strong>
                    <?= $total_products === 1 ? 'bijou trouvé' : 'bijoux trouvés' ?>
                    <?php if ($search): ?> pour « <?= htmlspecialchars($search) ?> »<?php endif; ?>
                </div>
                <div class="sort-options">
                    <label for="sortSelect">Trier par :</label>
                    <select id="sortSelect" class="sort-select" onchange="window.location = this.value;">
                        <?php
                        // ✅ CORRIGÉ : on retire 'sort' des paramètres existants avant de reconstruire l'URL
                        // (évite d'avoir "sort=&sort=price_asc" en doublon dans le lien généré)
                        $sortParams = $_GET;
                        unset($sortParams['sort']);
                        $sortParams['page'] = 1;
                        $baseUrl = '?' . http_build_query($sortParams);
                        $baseUrl .= empty($sortParams) ? 'sort=' : '&sort=';

                        $options = ['newest' => 'Nouveautés', 'price_asc' => 'Prix croissant', 'price_desc' => 'Prix décroissant', 'name_asc' => 'Nom A–Z', 'name_desc' => 'Nom Z–A'];
                        foreach ($options as $val => $label) {
                            $sel = ($sort === $val) ? 'selected' : '';
                            echo "<option value=\"{$baseUrl}$val\" $sel>$label</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Barre de recherche -->
            <div class="search-box-container">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Rechercher un bijou, une bague, un collier…" value="<?= htmlspecialchars($search) ?>" class="search-input">
                    <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
                    <?php foreach (['category', 'filter', 'matiere', 'marque'] as $f): ?>
                        <?php if (!empty($_GET[$f])): ?>
                            <input type="hidden" name="<?= $f ?>" value="<?= htmlspecialchars($_GET[$f]) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </form>
            </div>

            <?php if ($total_products > 0): ?>
                <div class="products-grid">
                    <?php foreach ($products as $product):
                        $price       = (float)$product['prix'];
                        $discount    = (float)($product['promotion_pourcentage'] ?? 0);
                        $final_price = $discount > 0 ? $price * (1 - $discount / 100) : $price;
                        $image       = $product['image_url'] ?: 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?auto=format&fit=crop&w=800&q=80';
                        $isFavori    = in_array($product['id_produit'], $userFavoris);
                    ?>
                        <div class="product-card" data-category="<?= htmlspecialchars($product['nom_categorie'] ?? '') ?>">

                            <!-- Badge -->
                            <?php if ($discount > 0): ?>
                                <span class="product-badge discount">−<?= (int)$discount ?>%</span>
                            <?php elseif ($product['nouveaute']): ?>
                                <span class="product-badge new">Nouveau</span>
                            <?php elseif ($product['bestseller']): ?>
                                <span class="product-badge bestseller">Best-seller</span>
                            <?php endif; ?>

                            <!-- ✅ Bouton cœur favori (en position absolue sur l'image) -->
                            <?php if (isLoggedIn()): ?>
                                <button class="btn-wishlist-card <?= $isFavori ? 'active' : '' ?>"
                                        data-product-id="<?= $product['id_produit'] ?>"
                                        title="<?= $isFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                                    <i class="<?= $isFavori ? 'fas' : 'far' ?> fa-heart"></i>
                                </button>
                            <?php else: ?>
                                <a href="connexion.php" class="btn-wishlist-card" title="Connectez-vous pour ajouter aux favoris">
                                    <i class="far fa-heart"></i>
                                </a>
                            <?php endif; ?>

                            <!-- Image -->
                            <div class="product-image">
                                <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($product['nom']) ?>" loading="lazy">
                                <div class="product-overlay">
                                    <a href="produit.php?id=<?= $product['id_produit'] ?>" class="view-details">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                </div>
                            </div>

                            <!-- Infos -->
                            <div class="product-info">
                                <div class="product-meta">
                                    <span class="product-brand"><?= htmlspecialchars($product['marque'] ?? '') ?></span>
                                    <span class="product-category"><?= htmlspecialchars($product['nom_categorie'] ?? 'Bijoux') ?></span>
                                </div>

                                <h3 class="product-name">
                                    <a href="produit.php?id=<?= $product['id_produit'] ?>">
                                        <?= htmlspecialchars($product['nom']) ?>
                                    </a>
                                </h3>

                                <div class="product-pricing">
                                    <?php if ($discount > 0): ?>
                                        <div class="original-price"><?= formatPrice($price) ?></div>
                                    <?php endif; ?>
                                    <div class="current-price"><?= formatPrice($final_price) ?></div>
                                </div>

                                <!-- Actions -->
                                <div class="product-actions">
                                    <?php if ($product['stock'] > 0): ?>
                                        <?php if (isLoggedIn()): ?>
                                            <form method="POST" action="ajouter_au_panier.php" class="add-cart-form">
                                                <input type="hidden" name="produit_id" value="<?= $product['id_produit'] ?>">
                                                <input type="hidden" name="quantite" value="1">
                                                <input type="hidden" name="redirect_url" value="panier.php">
                                                <button type="submit" name="ajouter_panier" class="btn-add-cart">
                                                    <i class="fas fa-cart-plus"></i> Ajouter
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <a href="connexion.php?redirect=catalogue" class="btn-add-cart disabled">
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
                            <a href="?<?= $queryString ? $queryString . '&' : '' ?>page=<?= $i ?>" class="<?= $active ?>"><?= $i ?></a>
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

<!-- ===== STYLES BOUTON CŒUR CATALOGUE ===== -->
<style>
/* Positionnement du cœur sur la carte */
.product-card {
    position: relative;
}

.btn-wishlist-card {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 10;
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.92);
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #ccc;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    transition: all 0.25s ease;
    backdrop-filter: blur(4px);
}

.btn-wishlist-card:hover {
    color: #e74c3c;
    background: white;
    transform: scale(1.15);
    box-shadow: 0 4px 14px rgba(231,76,60,0.25);
}

.btn-wishlist-card.active {
    color: #e74c3c;
    background: #fff0f0;
}

/* Animation pop au clic */
@keyframes heartPopCard {
    0%   { transform: scale(1); }
    40%  { transform: scale(1.5); }
    70%  { transform: scale(0.9); }
    100% { transform: scale(1); }
}
.btn-wishlist-card.pop {
    animation: heartPopCard 0.4s ease forwards;
}
</style>

<!-- ===== SCRIPT PRIX + WISHLIST CATALOGUE ===== -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    // --- Slider de prix ---
    const priceSlider = document.getElementById('priceSlider');
    const priceValue  = document.getElementById('priceValue');
    const applyBtn    = document.getElementById('applyPrice');

    if (priceSlider && priceValue && applyBtn) {
        priceSlider.addEventListener('input', function () {
            priceValue.textContent = this.value + ' €';
        });
        applyBtn.addEventListener('click', function () {
            const url = new URL(window.location);
            url.searchParams.set('max_price', priceSlider.value);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    }

    // --- ✅ Wishlist boutons cœur sur les cartes du catalogue ---
    document.querySelectorAll('.btn-wishlist-card').forEach(function (btn) {
        if (btn.tagName === 'A') return; // non connecté → redirige, pas d'AJAX

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const idProduit = this.dataset.productId;
            if (!idProduit) return;

            const self = this;
            self.style.pointerEvents = 'none';
            const originalHTML = self.innerHTML;
            self.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const formData = new FormData();
            formData.append('id_produit', idProduit);

            fetch('../includes/toggle_wishlist.php', {
                method: 'POST',
                body: formData
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                self.style.pointerEvents = '';

                if (data.success) {
                    self.classList.add('pop');
                    setTimeout(() => self.classList.remove('pop'), 400);

                    if (data.action === 'added') {
                        self.classList.add('active');
                        self.innerHTML = '<i class="fas fa-heart"></i>';
                        self.title = 'Retirer des favoris';
                        showCatalogNotif('Ajouté à vos favoris ❤️');
                    } else {
                        self.classList.remove('active');
                        self.innerHTML = '<i class="far fa-heart"></i>';
                        self.title = 'Ajouter aux favoris';
                        showCatalogNotif('Retiré de vos favoris');
                    }
                } else if (data.redirect) {
                    self.innerHTML = originalHTML;
                    showCatalogNotif('Connectez-vous pour gérer vos favoris', 'error');
                    setTimeout(() => { window.location.href = data.redirect; }, 1800);
                } else {
                    self.innerHTML = originalHTML;
                    showCatalogNotif(data.message || 'Une erreur est survenue', 'error');
                }
            })
            .catch(err => {
                self.style.pointerEvents = '';
                self.innerHTML = originalHTML;
                console.error('Wishlist error:', err);
                showCatalogNotif('Erreur réseau, réessayez', 'error');
            });
        });
    });

    // Notification légère
    function showCatalogNotif(msg, type = 'success') {
        document.querySelectorAll('.lumoura-notification').forEach(n => n.remove());

        const n = document.createElement('div');
        n.className = 'lumoura-notification lumoura-notification--' + type;
        n.innerHTML = `
            <div class="notif-icon"><i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i></div>
            <div class="notif-body"><span class="notif-msg">${msg}</span></div>
            <button class="notif-close" aria-label="Fermer"><i class="fas fa-times"></i></button>
            <div class="notif-progress"></div>
        `;
        document.body.appendChild(n);
        requestAnimationFrame(() => n.classList.add('show'));

        const t = setTimeout(() => close(n), 3500);
        n.querySelector('.notif-close').addEventListener('click', () => { clearTimeout(t); close(n); });

        function close(el) {
            el.classList.remove('show');
            el.classList.add('hiding');
            setTimeout(() => el.remove(), 350);
        }
    }

});
</script>

<?php include '../includes/footer.php'; ?>