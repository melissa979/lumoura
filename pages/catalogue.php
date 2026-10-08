<?php
/**
 * Catalogue complet et refait pour Éclat d'Or
 * Fichier : pages/catalogue.php
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* --------------------------------------------------------------------------
   Paramètres
-------------------------------------------------------------------------- */
$page = max(1, (int) ($_GET['page'] ?? 1));
$category = trim($_GET['category'] ?? '');
$filter = trim($_GET['filter'] ?? '');
$search = trim($_GET['search'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');
$matiere = trim($_GET['matiere'] ?? '');
$marque = trim($_GET['marque'] ?? '');
$maxPrice = (float) ($_GET['max_price'] ?? 0);

$limit = 12;
$offset = ($page - 1) * $limit;

/* --------------------------------------------------------------------------
   Construction des filtres SQL
-------------------------------------------------------------------------- */
$where = [];
$params = [];

if ($category !== '') {
    $where[] = 'c.nom_categorie = :category';
    $params[':category'] = $category;
}

if ($filter === 'new') {
    $where[] = 'p.nouveaute = 1';
} elseif ($filter === 'bestseller') {
    $where[] = 'p.bestseller = 1';
} elseif ($filter === 'promo') {
    $where[] = 'p.promotion_pourcentage > 0';
}

if ($search !== '') {
    $where[] = '(
        p.nom LIKE :search_nom
        OR p.description LIKE :search_description
        OR p.description_courte LIKE :search_courte
    )';

    $searchValue = '%' . $search . '%';
    $params[':search_nom'] = $searchValue;
    $params[':search_description'] = $searchValue;
    $params[':search_courte'] = $searchValue;
}

if ($matiere !== '') {
    $where[] = 'p.matiere = :matiere';
    $params[':matiere'] = $matiere;
}

if ($marque !== '') {
    $where[] = 'p.marque = :marque';
    $params[':marque'] = $marque;
}

if ($maxPrice > 0) {
    $where[] = 'p.prix <= :max_price';
    $params[':max_price'] = $maxPrice;
}

$whereClause = empty($where)
    ? ''
    : 'WHERE ' . implode(' AND ', $where);

/* --------------------------------------------------------------------------
   Tri sécurisé
-------------------------------------------------------------------------- */
$orderBy = match ($sort) {
    'price_asc' => 'p.prix ASC',
    'price_desc' => 'p.prix DESC',
    'name_asc' => 'p.nom ASC',
    'name_desc' => 'p.nom DESC',
    default => 'p.date_ajout DESC',
};

/* --------------------------------------------------------------------------
   Nombre total de produits
-------------------------------------------------------------------------- */
$countQuery = "
    SELECT COUNT(*)
    FROM produits p
    LEFT JOIN categories c
        ON p.id_categorie = c.id_categorie
    $whereClause
";

$countStmt = $pdo->prepare($countQuery);

foreach ($params as $key => $value) {
    $countStmt->bindValue(
        $key,
        $value,
        is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
    );
}

$countStmt->execute();
$totalProducts = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalProducts / $limit));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

/* --------------------------------------------------------------------------
   Produits
-------------------------------------------------------------------------- */
$query = "
    SELECT p.*, c.nom_categorie
    FROM produits p
    LEFT JOIN categories c
        ON p.id_categorie = c.id_categorie
    $whereClause
    ORDER BY $orderBy
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue(
        $key,
        $value,
        is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
    );
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------------------------------
   Favoris
-------------------------------------------------------------------------- */
$userFavoris = [];

if (isLoggedIn()) {
    $favStmt = $pdo->prepare(
        'SELECT id_produit FROM favori WHERE id_utilisateur = ?'
    );
    $favStmt->execute([$_SESSION['user_id']]);
    $userFavoris = array_map(
        'intval',
        array_column($favStmt->fetchAll(PDO::FETCH_ASSOC), 'id_produit')
    );
}

/* --------------------------------------------------------------------------
   Valeurs des filtres disponibles
-------------------------------------------------------------------------- */
$matieres = [];
$marques = [];

try {
    $matieres = $pdo->query(
        "SELECT DISTINCT matiere
         FROM produits
         WHERE matiere IS NOT NULL AND matiere <> ''
         ORDER BY matiere"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $matieres = [];
}

try {
    $marques = $pdo->query(
        "SELECT DISTINCT marque
         FROM produits
         WHERE marque IS NOT NULL AND marque <> ''
         ORDER BY marque"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $marques = [];
}

/* --------------------------------------------------------------------------
   Liens du catalogue
-------------------------------------------------------------------------- */
function catalogueUrl(array $changes = []): string
{
    $query = $_GET;

    foreach ($changes as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }

    return 'catalogue.php' . (
        empty($query) ? '' : '?' . http_build_query($query)
    );
}

function catalogueProductImage(array $product, string $field): string
{
    $value = trim((string) ($product[$field] ?? ''));

    if ($value === '') {
        return '';
    }

    return $value;
}

$pageTitle = 'Notre collection de bijoux';
include '../includes/header.php';
?>

<style>
:root {
    --catalogue-brown: #3a2925;
    --catalogue-brown-light: #806c63;
    --catalogue-gold: #c9a227;
    --catalogue-gold-dark: #9d7815;
    --catalogue-gold-light: #e8d39a;
    --catalogue-cream: #fbf6ef;
    --catalogue-border: #eee4d8;
    --catalogue-gray: #77716d;
}

* {
    box-sizing: border-box;
}

html,
body {
    overflow-x: hidden;
}

body {
    margin: 0;
    background: var(--catalogue-cream);
}

/* ========================================================================
   STRUCTURE
======================================================================== */

.catalogue-page {
    width: 100vw;
    min-height: calc(100vh - 80px);
    margin-left: calc(50% - 50vw);
    padding: 38px 20px 75px;
    background:
        radial-gradient(circle at 8% 8%, rgba(201, 162, 39, .13), transparent 27%),
        linear-gradient(135deg, #fbf6ef, #f3e8dc);
}

.catalogue-wrapper {
    width: min(1240px, 100%);
    margin: 0 auto;
}

/* ========================================================================
   HERO DU CATALOGUE
======================================================================== */

.catalogue-hero {
    position: relative;
    overflow: hidden;
    margin-bottom: 25px;
    padding: 50px 38px;
    border-radius: 22px;
    background: linear-gradient(135deg, #3a2925, #806c63);
    color: #ffffff;
    box-shadow: 0 16px 38px rgba(58, 41, 37, .15);
}

.catalogue-hero::before,
.catalogue-hero::after {
    content: "";
    position: absolute;
    border: 1px solid rgba(232, 211, 154, .45);
    border-radius: 50%;
}

.catalogue-hero::before {
    width: 280px;
    height: 280px;
    top: -165px;
    right: -35px;
}

.catalogue-hero::after {
    width: 150px;
    height: 150px;
    right: 180px;
    bottom: -115px;
}

.catalogue-hero-content {
    position: relative;
    z-index: 2;
    max-width: 680px;
}

.catalogue-kicker {
    margin: 0 0 10px;
    color: var(--catalogue-gold-light);
    font-size: 10px;
    font-weight: bold;
    letter-spacing: 3px;
    text-transform: uppercase;
}

.catalogue-hero h1 {
    margin: 0 0 13px;
    color: #ffffff;
    font-family: Georgia, serif;
    font-size: clamp(2rem, 4vw, 3.3rem);
    font-weight: normal;
}

.catalogue-hero p {
    max-width: 600px;
    margin: 0;
    color: rgba(255, 255, 255, .8);
    font-size: 14px;
    line-height: 1.7;
}

/* ========================================================================
   MESSAGE AJOUT PANIER
======================================================================== */

.catalogue-alert {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin: 20px 0;
    padding: 15px 18px;
    border: 1px solid #b8dfc0;
    border-radius: 10px;
    background: #effaf1;
    color: #28733a;
    font-size: 13px;
}

.catalogue-alert a {
    color: #28733a;
    font-weight: bold;
}

/* ========================================================================
   STATISTIQUES
======================================================================== */

.catalogue-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 25px;
}

.catalogue-stat {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 54px;
    padding: 13px;
    border: 1px solid var(--catalogue-border);
    border-radius: 12px;
    background: rgba(255, 255, 255, .75);
    color: var(--catalogue-gray);
    font-size: 12px;
    text-align: center;
}

.catalogue-stat i {
    color: var(--catalogue-gold);
    font-size: 17px;
}

/* ========================================================================
   FILTRES + PRODUITS
======================================================================== */

.catalogue-layout {
    display: grid;
    grid-template-columns: 245px minmax(0, 1fr);
    gap: 25px;
    align-items: start;
}

.filters-sidebar {
    position: sticky;
    top: 20px;
    padding: 21px;
    border: 1px solid var(--catalogue-border);
    border-radius: 17px;
    background: rgba(255, 255, 255, .92);
    box-shadow: 0 9px 25px rgba(58, 41, 37, .08);
}

.filters-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 19px;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--catalogue-border);
}

.filters-heading h2 {
    margin: 0;
    color: var(--catalogue-brown);
    font-family: Georgia, serif;
    font-size: 20px;
    font-weight: normal;
}

.filters-heading h2 i {
    margin-right: 7px;
    color: var(--catalogue-gold);
    font-size: 15px;
}

.clear-filters {
    color: var(--catalogue-gold-dark);
    font-size: 10px;
    text-decoration: none;
}

.clear-filters:hover {
    text-decoration: underline;
}

.filter-section {
    margin-bottom: 23px;
}

.filter-section:last-child {
    margin-bottom: 0;
}

.filter-title {
    margin: 0 0 10px;
    color: var(--catalogue-brown);
    font-size: 10px;
    font-weight: bold;
    letter-spacing: 1.4px;
    text-transform: uppercase;
}

.filter-list {
    display: grid;
    gap: 4px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.filter-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 9px;
    border-radius: 7px;
    color: var(--catalogue-gray);
    font-size: 12px;
    text-decoration: none;
    transition: background .2s ease, color .2s ease, transform .2s ease;
}

.filter-link i {
    width: 14px;
    color: var(--catalogue-gold);
    text-align: center;
}

.filter-link:hover,
.filter-link.active {
    background: #f5eadc;
    color: var(--catalogue-brown);
    transform: translateX(3px);
}

.price-labels,
.price-selected {
    display: flex;
    justify-content: space-between;
    color: var(--catalogue-gray);
    font-size: 11px;
}

.price-selected {
    justify-content: center;
    margin: 10px 0;
}

.price-selected strong {
    margin-left: 4px;
    color: var(--catalogue-gold-dark);
}

.price-slider {
    width: 100%;
    accent-color: var(--catalogue-gold);
}

.btn-apply-price {
    width: 100%;
    padding: 9px;
    border: none;
    border-radius: 7px;
    background: var(--catalogue-gold);
    color: #ffffff;
    cursor: pointer;
    font-size: 11px;
    font-weight: bold;
}

.btn-apply-price:hover {
    background: var(--catalogue-gold-dark);
}

.products-main {
    min-width: 0;
}

.products-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 14px;
    padding: 13px 17px;
    border: 1px solid var(--catalogue-border);
    border-radius: 12px;
    background: rgba(255, 255, 255, .9);
}

.results-info {
    color: var(--catalogue-gray);
    font-size: 12px;
}

.results-info strong {
    color: var(--catalogue-brown);
}

.sort-options {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--catalogue-gray);
    font-size: 11px;
}

.sort-select {
    padding: 8px 10px;
    border: 1px solid var(--catalogue-border);
    border-radius: 7px;
    outline: none;
    background: #fffdf9;
    color: var(--catalogue-brown);
    font-size: 11px;
}

.catalogue-search-box {
    display: flex;
    margin-bottom: 21px;
}

.catalogue-search-form {
    width: 100%;
    display: flex;
    overflow: hidden;
    border: 1px solid var(--catalogue-border);
    border-radius: 25px;
    background: #ffffff;
    box-shadow: 0 5px 16px rgba(58, 41, 37, .05);
}

.catalogue-search-input {
    flex: 1;
    min-width: 0;
    padding: 13px 17px;
    border: none;
    outline: none;
    background: transparent;
    color: var(--catalogue-brown);
    font-size: 12px;
}

.catalogue-search-button {
    width: 48px;
    border: none;
    background: var(--catalogue-gold);
    color: #ffffff;
    cursor: pointer;
}

.catalogue-search-button:hover {
    background: var(--catalogue-gold-dark);
}

/* ========================================================================
   CARTES PRODUITS
======================================================================== */

.catalogue-products-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 19px;
}

.catalogue-product-card {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(58, 41, 37, .09);
    border-radius: 15px;
    background: #ffffff;
    box-shadow: 0 8px 22px rgba(58, 41, 37, .07);
    transform-style: preserve-3d;
    transition: box-shadow .3s ease;
}

.catalogue-product-card:hover {
    box-shadow:
        0 19px 35px rgba(58, 41, 37, .15),
        0 0 20px rgba(201, 162, 39, .1);
}

.catalogue-badge {
    position: absolute;
    z-index: 7;
    top: 10px;
    left: 10px;
    padding: 5px 7px;
    border-radius: 5px;
    color: #ffffff;
    font-size: 9px;
    font-weight: bold;
}

.catalogue-badge-new {
    background: #27825c;
}

.catalogue-badge-best {
    background: var(--catalogue-gold-dark);
}

.catalogue-badge-promo {
    background: #a24343;
}

.catalogue-wishlist {
    position: absolute;
    z-index: 8;
    top: 10px;
    right: 10px;
    width: 33px;
    height: 33px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 50%;
    background: rgba(255, 255, 255, .94);
    color: var(--catalogue-gold-dark);
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(58, 41, 37, .13);
    transition: .25s ease;
}

.catalogue-wishlist:hover,
.catalogue-wishlist.active {
    background: var(--catalogue-gold);
    color: #ffffff;
    transform: scale(1.12);
}

.catalogue-image-box {
    position: relative;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    background: #f3e8dc;
}

.catalogue-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    transition: opacity .45s ease, transform .6s ease;
}

.catalogue-image-main {
    position: relative;
    opacity: 1;
}

.catalogue-image-hover {
    opacity: 0;
    transform: scale(1.08);
}

.catalogue-product-card:hover .catalogue-image-main {
    opacity: 0;
    transform: scale(1.08);
}

.catalogue-product-card:hover .catalogue-image-hover {
    opacity: 1;
    transform: scale(1);
}

.catalogue-hover-label {
    position: absolute;
    z-index: 6;
    left: 50%;
    bottom: 12px;
    padding: 5px 9px;
    border-radius: 20px;
    background: rgba(58, 41, 37, .85);
    color: #ffffff;
    font-size: 9px;
    opacity: 0;
    pointer-events: none;
    transform: translate(-50%, 8px);
    transition: .3s ease;
}

.catalogue-product-card:hover .catalogue-hover-label {
    opacity: 1;
    transform: translate(-50%, 0);
}

.catalogue-product-info {
    position: relative;
    z-index: 3;
    padding: 15px;
    transform: translateZ(11px);
}

.catalogue-product-meta {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 7px;
}

.catalogue-brand,
.catalogue-category {
    color: var(--catalogue-gold-dark);
    font-size: 9px;
    font-weight: bold;
    letter-spacing: .5px;
    text-transform: uppercase;
}

.catalogue-category {
    color: #a69a91;
    font-weight: normal;
}

.catalogue-product-name {
    min-height: 39px;
    margin: 0 0 9px;
    color: var(--catalogue-brown);
    font-family: Georgia, serif;
    font-size: 17px;
    font-weight: normal;
    line-height: 1.25;
}

.catalogue-product-name a {
    color: inherit;
    text-decoration: none;
}

.catalogue-product-name a:hover {
    color: var(--catalogue-gold-dark);
}

.catalogue-prices {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 13px;
}

.catalogue-current-price {
    color: var(--catalogue-brown);
    font-size: 19px;
    font-weight: bold;
}

.catalogue-old-price {
    color: #aaa19a;
    font-size: 11px;
    text-decoration: line-through;
}

.catalogue-cart-form {
    width: 100%;
}

.catalogue-cart-button {
    width: 100%;
    min-height: 37px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border: none;
    border-radius: 7px;
    background: var(--catalogue-brown);
    color: #ffffff;
    cursor: pointer;
    font-size: 11px;
    font-weight: bold;
    text-decoration: none;
    transition: .25s ease;
}

.catalogue-cart-button:hover {
    background: var(--catalogue-gold-dark);
    transform: translateY(-2px);
}

.catalogue-out-stock {
    display: block;
    color: #a24343;
    font-size: 11px;
    font-weight: bold;
    text-align: center;
}

/* ========================================================================
   PAGINATION ET ÉTAT VIDE
======================================================================== */

.catalogue-pagination {
    display: flex;
    justify-content: center;
    gap: 7px;
    margin-top: 30px;
}

.catalogue-pagination a {
    min-width: 32px;
    padding: 9px 11px;
    border: 1px solid var(--catalogue-border);
    border-radius: 7px;
    background: #ffffff;
    color: var(--catalogue-brown);
    font-size: 12px;
    text-align: center;
    text-decoration: none;
}

.catalogue-pagination a:hover,
.catalogue-pagination a.active {
    border-color: var(--catalogue-gold);
    background: var(--catalogue-gold);
    color: #ffffff;
}

.catalogue-empty {
    padding: 75px 25px;
    border: 1px solid var(--catalogue-border);
    border-radius: 17px;
    background: #ffffff;
    text-align: center;
}

.catalogue-empty i {
    margin-bottom: 20px;
    color: var(--catalogue-gold);
}

.catalogue-empty h2 {
    margin: 0 0 10px;
    color: var(--catalogue-brown);
    font-family: Georgia, serif;
    font-size: 24px;
    font-weight: normal;
}

.catalogue-empty p {
    margin: 0 0 20px;
    color: var(--catalogue-gray);
    font-size: 13px;
}

/* Responsive */
@media (max-width: 1050px) {
    .catalogue-products-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 800px) {
    .catalogue-layout {
        grid-template-columns: 1fr;
    }

    .filters-sidebar {
        position: static;
    }

    .catalogue-products-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 620px) {
    .catalogue-page {
        padding: 25px 12px 55px;
    }

    .catalogue-hero {
        padding: 35px 24px;
    }

    .catalogue-stats {
        grid-template-columns: 1fr;
    }

    .products-toolbar {
        align-items: flex-start;
        flex-direction: column;
    }

    .catalogue-products-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 13px;
    }

    .catalogue-product-info {
        padding: 12px;
    }

    .catalogue-product-name {
        font-size: 15px;
    }

    .catalogue-image-hover,
    .catalogue-hover-label {
        display: none;
    }

    .catalogue-product-card:hover .catalogue-image-main {
        opacity: 1;
        transform: none;
    }
}

@media (max-width: 390px) {
    .catalogue-products-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main class="catalogue-page">
    <div class="catalogue-wrapper">
        <section class="catalogue-hero">
            <div class="catalogue-hero-content">
                <p class="catalogue-kicker">Maison Éclat d'Or</p>
                <h1>Notre collection de bijoux</h1>
                <p>
                    Découvrez nos créations élégantes en or, argent et pierres
                    précieuses, imaginées pour accompagner chaque moment.
                </p>
            </div>
        </section>

        <?php if (isset($_GET['added'])): ?>
            <div class="catalogue-alert">
                <span>
                    <strong>Article ajouté !</strong>
                    Le bijou a été ajouté à votre panier.
                </span>
                <a href="panier.php">Voir mon panier</a>
            </div>
        <?php endif; ?>

        <div class="catalogue-stats">
            <div class="catalogue-stat">
                <i class="fas fa-gem"></i>
                <span><?= $totalProducts ?> bijoux</span>
            </div>

            <div class="catalogue-stat">
                <i class="fas fa-shipping-fast"></i>
                <span>Livraison gratuite dès 150 €</span>
            </div>

            <div class="catalogue-stat">
                <i class="fas fa-undo"></i>
                <span>Retour gratuit sous 30 jours</span>
            </div>
        </div>

        <div class="catalogue-layout">
            <aside class="filters-sidebar">
                <div class="filters-heading">
                    <h2>
                        <i class="fas fa-sliders-h"></i>
                        Filtres
                    </h2>

                    <?php if ($category || $filter || $search || $matiere || $marque || $maxPrice > 0): ?>
                        <a href="catalogue.php" class="clear-filters">
                            Effacer
                        </a>
                    <?php endif; ?>
                </div>

                <div class="filter-section">
                    <h3 class="filter-title">Catégories</h3>
                    <ul class="filter-list">
                        <li>
                            <a
                                href="catalogue.php"
                                class="filter-link <?= !$category && !$filter && !$matiere && !$marque && !$maxPrice ? 'active' : '' ?>"
                            >
                                <i class="fas fa-th-large"></i>
                                Tous les bijoux
                            </a>
                        </li>

                        <?php foreach (['Femme', 'Homme', 'Unisexe'] as $cat): ?>
                            <li>
                                <a
                                    href="<?= catalogueUrl(['category' => $cat, 'page' => 1]) ?>"
                                    class="filter-link <?= $category === $cat ? 'active' : '' ?>"
                                >
                                    <i class="fas fa-gem"></i>
                                    <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="filter-section">
                    <h3 class="filter-title">Sélections</h3>
                    <ul class="filter-list">
                        <li>
                            <a
                                href="<?= catalogueUrl(['filter' => 'new', 'page' => 1]) ?>"
                                class="filter-link <?= $filter === 'new' ? 'active' : '' ?>"
                            >
                                <i class="fas fa-star"></i>
                                Nouveautés
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?= catalogueUrl(['filter' => 'bestseller', 'page' => 1]) ?>"
                                class="filter-link <?= $filter === 'bestseller' ? 'active' : '' ?>"
                            >
                                <i class="fas fa-fire"></i>
                                Best-sellers
                            </a>
                        </li>

                        <li>
                            <a
                                href="<?= catalogueUrl(['filter' => 'promo', 'page' => 1]) ?>"
                                class="filter-link <?= $filter === 'promo' ? 'active' : '' ?>"
                            >
                                <i class="fas fa-tag"></i>
                                Promotions
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="filter-section">
                    <h3 class="filter-title">Matière</h3>
                    <ul class="filter-list">
                        <?php foreach ($matieres as $matiereValue): ?>
                            <li>
                                <a
                                    href="<?= catalogueUrl(['matiere' => $matiereValue, 'page' => 1]) ?>"
                                    class="filter-link <?= $matiere === $matiereValue ? 'active' : '' ?>"
                                >
                                    <i class="fas fa-gem"></i>
                                    <?= htmlspecialchars($matiereValue, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="filter-section">
                    <h3 class="filter-title">Créateur</h3>
                    <ul class="filter-list">
                        <?php foreach ($marques as $marqueValue): ?>
                            <li>
                                <a
                                    href="<?= catalogueUrl(['marque' => $marqueValue, 'page' => 1]) ?>"
                                    class="filter-link <?= $marque === $marqueValue ? 'active' : '' ?>"
                                >
                                    <i class="fas fa-copyright"></i>
                                    <?= htmlspecialchars($marqueValue, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="filter-section">
                    <h3 class="filter-title">Prix maximum</h3>

                    <div class="price-labels">
                        <span>0 €</span>
                        <span>800 €</span>
                    </div>

                    <input
                        type="range"
                        min="0"
                        max="800"
                        value="<?= $maxPrice > 0 ? $maxPrice : 800 ?>"
                        class="price-slider"
                        id="priceSlider"
                    >

                    <div class="price-selected">
                        Jusqu'à :
                        <strong id="priceValue">
                            <?= $maxPrice > 0 ? $maxPrice : 800 ?> €
                        </strong>
                    </div>

                    <button type="button" id="applyPrice" class="btn-apply-price">
                        Appliquer
                    </button>
                </div>
            </aside>

            <section class="products-main">
                <div class="products-toolbar">
                    <div class="results-info">
                        <strong><?= $totalProducts ?></strong>
                        <?= $totalProducts > 1 ? 'bijoux trouvés' : 'bijou trouvé' ?>

                        <?php if ($search !== ''): ?>
                            pour « <?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?> »
                        <?php endif; ?>
                    </div>

                    <div class="sort-options">
                        <label for="sortSelect">Trier par</label>
                        <select id="sortSelect" class="sort-select">
                            <?php
                            $sortOptions = [
                                'newest' => 'Nouveautés',
                                'price_asc' => 'Prix croissant',
                                'price_desc' => 'Prix décroissant',
                                'name_asc' => 'Nom A à Z',
                                'name_desc' => 'Nom Z à A',
                            ];
                            ?>

                            <?php foreach ($sortOptions as $sortValue => $sortLabel): ?>
                                <option
                                    value="<?= $sortValue ?>"
                                    <?= $sort === $sortValue ? 'selected' : '' ?>
                                >
                                    <?= $sortLabel ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="catalogue-search-box">
                    <form method="GET" class="catalogue-search-form">
                        <input
                            type="search"
                            name="search"
                            class="catalogue-search-input"
                            placeholder="Rechercher une bague, un collier..."
                            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
                        >

                        <?php foreach (['category', 'filter', 'matiere', 'marque', 'sort'] as $hidden): ?>
                            <?php if (!empty($_GET[$hidden])): ?>
                                <input
                                    type="hidden"
                                    name="<?= $hidden ?>"
                                    value="<?= htmlspecialchars($_GET[$hidden], ENT_QUOTES, 'UTF-8') ?>"
                                >
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <button type="submit" class="catalogue-search-button">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <?php if (!empty($products)): ?>
                    <div class="catalogue-products-grid">
                        <?php foreach ($products as $product): ?>
                            <?php
                            $productId = (int) $product['id_produit'];
                            $price = (float) $product['prix'];
                            $discount = (float) ($product['promotion_pourcentage'] ?? 0);
                            $finalPrice = $discount > 0
                                ? $price * (1 - $discount / 100)
                                : $price;
                            $mainImage = catalogueProductImage($product, 'image_url');
                            $hoverImage = catalogueProductImage($product, 'image_hover_url');
                            $fallbackImage = 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?auto=format&fit=crop&w=800&q=85';
                            $mainImage = $mainImage !== '' ? $mainImage : $fallbackImage;
                            $hoverImage = $hoverImage !== '' ? $hoverImage : $mainImage;
                            $isFavorite = in_array($productId, $userFavoris, true);
                            ?>

                            <article class="catalogue-product-card tilt-catalogue-card">
                                <?php if ($discount > 0): ?>
                                    <span class="catalogue-badge catalogue-badge-promo">
                                        -<?= (int) $discount ?>%
                                    </span>
                                <?php elseif (!empty($product['nouveaute'])): ?>
                                    <span class="catalogue-badge catalogue-badge-new">
                                        Nouveau
                                    </span>
                                <?php elseif (!empty($product['bestseller'])): ?>
                                    <span class="catalogue-badge catalogue-badge-best">
                                        Best-seller
                                    </span>
                                <?php endif; ?>

                                <?php if (isLoggedIn()): ?>
                                    <button
                                        type="button"
                                        class="catalogue-wishlist <?= $isFavorite ? 'active' : '' ?>"
                                        data-product-id="<?= $productId ?>"
                                        title="<?= $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>"
                                    >
                                        <i class="<?= $isFavorite ? 'fas' : 'far' ?> fa-heart"></i>
                                    </button>
                                <?php else: ?>
                                    <a
                                        href="connexion.php?redirect=catalogue"
                                        class="catalogue-wishlist"
                                        title="Connectez-vous"
                                    >
                                        <i class="far fa-heart"></i>
                                    </a>
                                <?php endif; ?>

                                <div class="catalogue-image-box">
                                    <img
                                        src="<?= htmlspecialchars($mainImage, ENT_QUOTES, 'UTF-8') ?>"
                                        alt="<?= htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8') ?>"
                                        class="catalogue-image catalogue-image-main"
                                        loading="lazy"
                                    >

                                    <img
                                        src="<?= htmlspecialchars($hoverImage, ENT_QUOTES, 'UTF-8') ?>"
                                        alt="<?= htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8') ?> porté"
                                        class="catalogue-image catalogue-image-hover"
                                        loading="lazy"
                                    >

                                    <span class="catalogue-hover-label">
                                        Bijou porté
                                    </span>
                                </div>

                                <div class="catalogue-product-info">
                                    <div class="catalogue-product-meta">
                                        <span class="catalogue-brand">
                                            <?= htmlspecialchars($product['marque'] ?? "Éclat d'Or", ENT_QUOTES, 'UTF-8') ?>
                                        </span>

                                        <span class="catalogue-category">
                                            <?= htmlspecialchars($product['nom_categorie'] ?? 'Bijoux', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>

                                    <h2 class="catalogue-product-name">
                                        <a href="produit.php?id=<?= $productId ?>">
                                            <?= htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </h2>

                                    <div class="catalogue-prices">
                                        <?php if ($discount > 0): ?>
                                            <span class="catalogue-old-price">
                                                <?= formatPrice($price) ?>
                                            </span>
                                        <?php endif; ?>

                                        <span class="catalogue-current-price">
                                            <?= formatPrice($finalPrice) ?>
                                        </span>
                                    </div>

                                    <?php if ((int) $product['stock'] > 0): ?>
                                        <?php if (isLoggedIn()): ?>
                                            <form
                                                method="POST"
                                                action="ajouter_au_panier.php"
                                                class="catalogue-cart-form"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="produit_id"
                                                    value="<?= $productId ?>"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="quantite"
                                                    value="1"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="redirect_url"
                                                    value="catalogue.php"
                                                >

                                                <button
                                                    type="submit"
                                                    name="ajouter_panier"
                                                    class="catalogue-cart-button"
                                                >
                                                    <i class="fas fa-shopping-bag"></i>
                                                    Ajouter au panier
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <a
                                                href="connexion.php?redirect=catalogue"
                                                class="catalogue-cart-button"
                                            >
                                                <i class="fas fa-lock"></i>
                                                Connectez-vous
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="catalogue-out-stock">
                                            Rupture de stock
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <nav class="catalogue-pagination" aria-label="Pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a
                                    href="<?= catalogueUrl(['page' => $i]) ?>"
                                    class="<?= $i === $page ? 'active' : '' ?>"
                                >
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="catalogue-empty">
                        <i class="fas fa-gem fa-3x"></i>
                        <h2>Aucun bijou trouvé</h2>
                        <p>
                            Modifiez les filtres ou découvrez toute notre collection.
                        </p>
                        <a href="catalogue.php" class="catalogue-cart-button">
                            Voir tous les bijoux
                        </a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sortSelect = document.getElementById('sortSelect');

    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', this.value);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    }

    const priceSlider = document.getElementById('priceSlider');
    const priceValue = document.getElementById('priceValue');
    const applyPrice = document.getElementById('applyPrice');

    if (priceSlider && priceValue) {
        priceSlider.addEventListener('input', function () {
            priceValue.textContent = this.value + ' €';
        });
    }

    if (priceSlider && applyPrice) {
        applyPrice.addEventListener('click', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('max_price', priceSlider.value);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });
    }

    /* Rotation 3D légère des cartes */
    document.querySelectorAll('.tilt-catalogue-card').forEach(function (card) {
        card.addEventListener('mousemove', function (event) {
            if (window.innerWidth <= 700) {
                return;
            }

            const rect = card.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            const rotateY = ((x - rect.width / 2) / (rect.width / 2)) * 2.5;
            const rotateX = ((rect.height / 2 - y) / (rect.height / 2)) * 2.5;

            card.style.transform =
                'perspective(900px) rotateX(' + rotateX +
                'deg) rotateY(' + rotateY + 'deg) translateY(-5px)';
        });

        card.addEventListener('mouseleave', function () {
            card.style.transform =
                'perspective(900px) rotateX(0deg) rotateY(0deg) translateY(0)';
        });
    });

    /* Favoris */
    document.querySelectorAll('.catalogue-wishlist[data-product-id]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            const productId = this.dataset.productId;
            const currentButton = this;

            currentButton.disabled = true;

            const formData = new FormData();
            formData.append('id_produit', productId);

            fetch('../includes/toggle_wishlist.php', {
                method: 'POST',
                body: formData
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    currentButton.disabled = false;

                    if (data.success && data.action === 'added') {
                        currentButton.classList.add('active');
                        currentButton.innerHTML = '<i class="fas fa-heart"></i>';
                    } else if (data.success) {
                        currentButton.classList.remove('active');
                        currentButton.innerHTML = '<i class="far fa-heart"></i>';
                    }
                })
                .catch(function () {
                    currentButton.disabled = false;
                });
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>