<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Récupération paramètres
$page = max(1, (int)($_GET['page'] ?? 1));
$category = trim($_GET['category'] ?? '');
$filter = trim($_GET['filter'] ?? '');
$search = trim($_GET['search'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');
$matiere = trim($_GET['matiere'] ?? '');
$marque = trim($_GET['marque'] ?? '');
$max_price = (float)($_GET['max_price'] ?? 0);

$limit = 12;
$offset = ($page - 1) * $limit;

// Construction WHERE
$where = [];
$params = [];

if ($category) {
    $genre_map = ['Femme' => 'Femme', 'Homme' => 'Homme', 'Unisexe' => 'Unisexe'];
    if (isset($genre_map[$category])) {
        $where[] = "p.genre = :genre";
        $params[':genre'] = $genre_map[$category];
    }
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
    $where[] = "((p.promotion_pourcentage > 0 AND p.prix * (1 - p.promotion_pourcentage / 100) <= :max_price) OR (p.promotion_pourcentage <= 0 AND p.prix <= :max_price))";
    $params[':max_price'] = $max_price;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$countQuery = "SELECT COUNT(*) FROM produits p LEFT JOIN categories c ON p.id_categorie = c.id_categorie $whereClause";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$total_products = (int) $countStmt->fetchColumn();

$orderBy = match($sort) {
    'price_asc' => 'p.prix ASC',
    'price_desc' => 'p.prix DESC',
    'name_asc' => 'p.nom ASC',
    'name_desc' => 'p.nom DESC',
    default => 'p.date_ajout DESC',
};

$query = "SELECT p.*, c.nom_categorie FROM produits p LEFT JOIN categories c ON p.id_categorie = c.id_categorie $whereClause ORDER BY $orderBy LIMIT :limit OFFSET :offset";
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

$pageTitle = "Catalogue - Lumoura Joaillerie";

$ids_favoris = [];
if (isLoggedIn()) {
    try {
        $stmt_fav = $pdo->prepare("SELECT id_produit FROM liste_envies WHERE id_utilisateur = :id_utilisateur");
        $stmt_fav->execute([':id_utilisateur' => $_SESSION['user_id']]);
        $ids_favoris = $stmt_fav->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}
}

include '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Didact+Gothic&family=EB+Garamond:ital,wght@0,400;0,500;0,700;1,400;1,600&family=Cinzel:wght@400;600;700;900&display=swap" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<style>
:root {
  --g1: #D4A843;
  --g2: #F5D78E;
  --g3: #B8882C;
  --g4: #FFF0C0;
  --ink: #0D0A06;
  --ink2: #1E1710;
  --smoke: #F8F5EF;
  --stone: #E8E0D0;
  --muted: #8A7D6A;
  --red: #C0392B;
}

*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

body {
  font-family: 'Didact Gothic', sans-serif;
  background: var(--smoke);
  color: var(--ink);
  overflow-x: hidden;
  cursor: none;
}

/* CURSEUR */
#cursor {
  position: fixed;
  width: 10px; height: 10px;
  background: var(--g1);
  border-radius: 50%;
  pointer-events: none;
  z-index: 99999;
  transform: translate(-50%,-50%);
  transition: transform .1s, width .25s, height .25s;
  mix-blend-mode: normal;
}
#cursor-ring {
  position: fixed;
  width: 36px; height: 36px;
  border: 1px solid var(--g1);
  border-radius: 50%;
  pointer-events: none;
  z-index: 99998;
  transform: translate(-50%,-50%);
  transition: transform .08s linear, width .3s, height .3s, opacity .3s;
  opacity: .6;
}
body.hovering #cursor { width: 20px; height: 20px; background: var(--g2); }
body.hovering #cursor-ring { width: 54px; height: 54px; opacity: .4; }

/* HERO CATALOGUE */
.cat-hero {
  position: relative;
  height: 55vh;
  min-height: 450px;
  background: var(--ink);
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}
.cat-hero-bg {
  position: absolute;
  inset: 0;
  background-image: url('https://www.goldmarket.fr/wp-content/uploads/2025/07/12f534e7thumbnail.jpeg');
  background-size: cover;
  background-position: center;
  filter: brightness(.35);
}
.cat-hero-content {
  position: relative;
  z-index: 2;
  text-align: center;
  max-width: 700px;
  padding: 0 20px;
}
.cat-hero-tag {
  font-family: 'Cinzel', serif;
  font-size: .6rem;
  letter-spacing: 5px;
  text-transform: uppercase;
  color: var(--g2);
  margin-bottom: 18px;
}
.cat-hero h1 {
  font-family: 'EB Garamond', serif;
  font-size: clamp(2.8rem, 5vw, 4.2rem);
  color: #fff;
  font-weight: 400;
  letter-spacing: 2px;
  margin-bottom: 18px;
}
.cat-hero h1 em { color: var(--g1); font-style: italic; }
.cat-hero-sub {
  font-size: .9rem;
  color: rgba(255,255,255,.6);
  line-height: 1.8;
  letter-spacing: .5px;
}

/* TOOLBAR */
.cat-toolbar {
  background: var(--ink);
  padding: 20px 40px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid rgba(255,255,255,.08);
  flex-wrap: wrap;
  gap: 20px;
}
.cat-stats {
  display: flex;
  align-items: center;
  gap: 30px;
}
.cat-stat-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: .72rem;
  color: rgba(255,255,255,.5);
  letter-spacing: 1px;
}
.cat-stat-item i { color: var(--g1); font-size: .85rem; }
.cat-stat-item strong { color: var(--g1); font-weight: 600; }

.cat-search-form {
  flex: 1;
  max-width: 400px;
  position: relative;
}
.cat-search-input {
  width: 100%;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.12);
  color: #fff;
  padding: 12px 45px 12px 18px;
  font-size: .8rem;
  font-family: 'Didact Gothic', sans-serif;
  transition: all .3s;
}
.cat-search-input:focus {
  outline: none;
  background: rgba(255,255,255,.1);
  border-color: var(--g1);
}
.cat-search-input::placeholder { color: rgba(255,255,255,.3); }
.cat-search-btn {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: var(--g1);
  font-size: .9rem;
  cursor: pointer;
  padding: 8px 12px;
}

.cat-sort {
  display: flex;
  align-items: center;
  gap: 12px;
}
.cat-sort label {
  font-family: 'Cinzel', serif;
  font-size: .58rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: rgba(255,255,255,.5);
}
.cat-sort-select {
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.12);
  color: #fff;
  padding: 10px 35px 10px 16px;
  font-size: .75rem;
  font-family: 'Didact Gothic', sans-serif;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23D4A843'%3E%3Cpath d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  transition: all .3s;
}
.cat-sort-select:hover { border-color: var(--g1); }

/* LAYOUT */
.cat-layout {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 0;
  max-width: 1600px;
  margin: 0 auto;
  background: var(--smoke);
}

/* SIDEBAR */
.cat-sidebar {
  background: var(--ink);
  padding: 40px 30px;
  border-right: 1px solid rgba(255,255,255,.05);
  position: sticky;
  top: 0;
  height: fit-content;
  max-height: 100vh;
  overflow-y: auto;
}
.cat-sidebar::-webkit-scrollbar { width: 4px; }
.cat-sidebar::-webkit-scrollbar-thumb { background: var(--g1); border-radius: 2px; }

.filter-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 35px;
}
.filter-head h3 {
  font-family: 'Cinzel', serif;
  font-size: .7rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--g2);
  display: flex;
  align-items: center;
  gap: 10px;
}
.filter-head h3 i { font-size: .75rem; }
.filter-clear {
  font-size: .6rem;
  color: rgba(255,255,255,.4);
  text-decoration: none;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  transition: color .3s;
}
.filter-clear:hover { color: var(--red); }

.filter-block {
  margin-bottom: 35px;
  padding-bottom: 30px;
  border-bottom: 1px solid rgba(255,255,255,.06);
}
.filter-block:last-child { border-bottom: none; }

.filter-title {
  font-family: 'Cinzel', serif;
  font-size: .62rem;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  color: rgba(255,255,255,.7);
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.filter-title::before {
  content: '';
  width: 20px;
  height: 1px;
  background: var(--g1);
}

.filter-list {
  list-style: none;
}
.filter-list li {
  margin-bottom: 10px;
}
.filter-link {
  font-size: .75rem;
  color: rgba(255,255,255,.5);
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 0;
  transition: all .3s;
  letter-spacing: .3px;
}
.filter-link i {
  font-size: .7rem;
  color: var(--g1);
  opacity: .5;
  transition: opacity .3s;
}
.filter-link:hover,
.filter-link.active {
  color: var(--g2);
  padding-left: 6px;
}
.filter-link:hover i,
.filter-link.active i { opacity: 1; }

/* Prix Slider */
.price-range {
  padding: 10px 0;
}
.price-labels {
  display: flex;
  justify-content: space-between;
  font-size: .65rem;
  color: rgba(255,255,255,.4);
  margin-bottom: 12px;
}
.price-slider {
  width: 100%;
  height: 4px;
  background: rgba(255,255,255,.1);
  border-radius: 2px;
  outline: none;
  -webkit-appearance: none;
  margin-bottom: 14px;
}
.price-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 16px;
  height: 16px;
  background: var(--g1);
  cursor: pointer;
  border-radius: 50%;
  border: 2px solid var(--ink);
}
.price-slider::-moz-range-thumb {
  width: 16px;
  height: 16px;
  background: var(--g1);
  cursor: pointer;
  border-radius: 50%;
  border: 2px solid var(--ink);
}
.price-selected {
  font-size: .72rem;
  color: rgba(255,255,255,.6);
  margin-bottom: 12px;
  text-align: center;
}
.price-selected span { color: var(--g1); font-weight: 600; }
.btn-apply-price {
  width: 100%;
  background: var(--g1);
  border: none;
  color: var(--ink);
  padding: 10px;
  font-family: 'Cinzel', serif;
  font-size: .6rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  cursor: pointer;
  transition: all .3s;
  font-weight: 600;
}
.btn-apply-price:hover {
  background: var(--g2);
  transform: translateY(-2px);
}

/* MAIN CONTENT */
.cat-main {
  padding: 50px 40px;
}

.results-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 40px;
  padding-bottom: 20px;
  border-bottom: 1px solid var(--stone);
}
.results-info {
  font-size: .8rem;
  color: var(--muted);
  letter-spacing: .5px;
}
.results-info strong {
  color: var(--g1);
  font-weight: 700;
}

/* GRILLE PRODUITS (même style index) */
.prods-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1px;
  background: var(--stone);
  border-top: 1px solid var(--stone);
  border-bottom: 1px solid var(--stone);
}

.prod-card {
  background: var(--smoke);
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform .4s cubic-bezier(.23,1,.32,1);
}
.prod-card:hover { transform: translateY(-4px); z-index: 3; box-shadow: 0 24px 60px rgba(0,0,0,.18); }

.prod-badge {
  position: absolute;
  top: 0; right: 0;
  z-index: 6;
  font-family: 'Cinzel', serif;
  font-size: .52rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  padding: 7px 13px;
}
.prod-badge.new { background: var(--g1); color: var(--ink); }
.prod-badge.promo { background: var(--ink); color: var(--g1); }
.prod-badge.best { background: transparent; border-bottom: 1px solid var(--g1); border-left: 1px solid var(--g1); color: var(--g1); }

.prod-img-wrap {
  position: relative;
  height: 280px;
  overflow: hidden;
  background: var(--stone);
  flex-shrink: 0;
}
.prod-img-wrap img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform .9s cubic-bezier(.25,.46,.45,.94);
}
.prod-card:hover .prod-img-wrap img { transform: scale(1.1); }

.prod-img-wrap::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, transparent 50%, rgba(212,168,67,.12) 100%);
  opacity: 0;
  transition: opacity .4s;
}
.prod-card:hover .prod-img-wrap::after { opacity: 1; }

.prod-actions-hover {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  display: flex;
  transform: translateY(101%);
  transition: transform .4s cubic-bezier(.77,0,.18,1);
  z-index: 5;
}
.prod-card:hover .prod-actions-hover { transform: translateY(0); }

.btn-cart-inline {
  flex: 1;
  background: var(--ink);
  border: none;
  color: #fff;
  font-family: 'Cinzel', serif;
  font-size: .58rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  padding: 14px 10px;
  cursor: pointer;
  transition: background .3s, color .3s;
  text-decoration: none;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
.btn-cart-inline:hover { background: var(--g1); color: var(--ink); }

.btn-fav-inline {
  width: 50px;
  background: rgba(13,10,6,.9);
  border: none;
  border-left: 1px solid rgba(255,255,255,.08);
  color: rgba(255,255,255,.4);
  font-size: .95rem;
  cursor: pointer;
  transition: color .3s, background .3s;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.btn-fav-inline:hover { color: var(--red); background: rgba(192,57,43,.15); }
.btn-fav-inline.active { color: var(--red); }

.prod-info {
  padding: 22px 22px 24px;
  flex: 1;
  display: flex;
  flex-direction: column;
  border-top: 1px solid rgba(0,0,0,.05);
  position: relative;
}

.prod-info::before {
  content: '';
  position: absolute;
  top: 0; left: 22px; right: 22px;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--g1), transparent);
  transform: scaleX(0);
  transition: transform .5s cubic-bezier(.77,0,.18,1);
}
.prod-card:hover .prod-info::before { transform: scaleX(1); }

.prod-brand {
  font-family: 'Cinzel', serif;
  font-size: .52rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--g1);
  margin-bottom: 7px;
}

.prod-name {
  font-family: 'EB Garamond', serif;
  font-size: 1.1rem;
  font-weight: 400;
  color: var(--ink);
  line-height: 1.3;
  margin-bottom: 7px;
  flex: 1;
}

.prod-desc {
  font-size: .72rem;
  color: var(--muted);
  line-height: 1.6;
  margin-bottom: 14px;
}

.prod-price {
  display: flex;
  align-items: baseline;
  gap: 10px;
  margin-top: auto;
}
.price-now {
  font-family: 'EB Garamond', serif;
  font-size: 1.3rem;
  font-weight: 500;
  color: var(--ink);
}
.price-old { font-size: .8rem; color: #bbb; text-decoration: line-through; }

/* PAGINATION */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: 60px;
}
.pagination a {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: rgba(13,10,6,.04);
  border: 1px solid var(--stone);
  color: var(--ink);
  text-decoration: none;
  font-family: 'Cinzel', serif;
  font-size: .7rem;
  font-weight: 600;
  transition: all .3s;
}
.pagination a:hover {
  background: var(--g1);
  border-color: var(--g1);
  color: #fff;
  transform: translateY(-2px);
}
.pagination a.active {
  background: var(--ink);
  border-color: var(--ink);
  color: var(--g1);
}

/* NO RESULTS */
.no-results {
  text-align: center;
  padding: 100px 20px;
}
.no-results i {
  font-size: 4rem;
  color: var(--stone);
  margin-bottom: 30px;
}
.no-results h3 {
  font-family: 'EB Garamond', serif;
  font-size: 2rem;
  color: var(--ink);
  margin-bottom: 15px;
}
.no-results p {
  color: var(--muted);
  font-size: .9rem;
  margin-bottom: 30px;
}
.no-results a {
  display: inline-block;
  background: var(--g1);
  color: var(--ink);
  padding: 14px 35px;
  font-family: 'Cinzel', serif;
  font-size: .65rem;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  text-decoration: none;
  font-weight: 600;
  transition: all .3s;
}
.no-results a:hover {
  background: var(--ink);
  color: var(--g1);
}

/* RESPONSIVE */
@media (max-width: 1200px) {
  .prods-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 900px) {
  .cat-layout { grid-template-columns: 1fr; }
  .cat-sidebar { position: static; max-height: none; border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); }
  .prods-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 600px) {
  .prods-grid { grid-template-columns: 1fr; }
  .cat-toolbar { flex-direction: column; align-items: stretch; }
  .cat-search-form { max-width: 100%; }
}
</style>

<!-- CURSEUR -->
<div id="cursor"></div>
<div id="cursor-ring"></div>

<!-- HERO CATALOGUE -->
<section class="cat-hero">
  <div class="cat-hero-bg"></div>
  <div class="cat-hero-content" data-aos="fade-up">
    <div class="cat-hero-tag">Notre Collection Complète</div>
    <h1>Catalogue <em>Joaillerie</em></h1>
    <p class="cat-hero-sub"><?= $total_products ?> bijoux d'exception – Or, diamants & pierres précieuses</p>
  </div>
</section>

<!-- TOOLBAR -->
<div class="cat-toolbar">
  <div class="cat-stats">
    <div class="cat-stat-item">
      <i class="fas fa-gem"></i>
      <span><strong><?= $total_products ?></strong> bijoux</span>
    </div>
    <div class="cat-stat-item">
      <i class="fas fa-shipping-fast"></i>
      <span>Livraison gratuite </span>
    </div>
    <div class="cat-stat-item">
      <i class="fas fa-undo"></i>
      <span>Retour 30 jours</span>
    </div>
  </div>

  <form method="GET" class="cat-search-form">
    <input type="text" name="search" placeholder="Rechercher un bijou..." value="<?= htmlspecialchars($search) ?>" class="cat-search-input">
    <button type="submit" class="cat-search-btn"><i class="fas fa-search"></i></button>
    <?php foreach (['category', 'filter', 'matiere', 'marque'] as $f): ?>
      <?php if (!empty($_GET[$f])): ?>
        <input type="hidden" name="<?= $f ?>" value="<?= htmlspecialchars($_GET[$f]) ?>">
      <?php endif; ?>
    <?php endforeach; ?>
  </form>

  <div class="cat-sort">
    <label for="sortSelect">Trier par :</label>
    <select id="sortSelect" class="cat-sort-select" onchange="window.location = this.value;">
      <?php
      $queryParams = $_GET;
      unset($queryParams['sort'], $queryParams['page']);
      $baseUrl = '?' . http_build_query($queryParams);
      if ($baseUrl !== '?') $baseUrl .= '&';

      $options = [
        'newest' => 'Nouveautés',
        'price_asc' => 'Prix croissant',
        'price_desc' => 'Prix décroissant',
        'name_asc' => 'Nom A–Z',
        'name_desc' => 'Nom Z–A',
      ];

      foreach ($options as $val => $label) {
        $selected = ($sort === $val) ? 'selected' : '';
        $url = $baseUrl . "sort=$val&page=1";
        echo "<option value=\"$url\" $selected>$label</option>";
      }
      ?>
    </select>
  </div>
</div>

<!-- LAYOUT SIDEBAR + GRID -->
<div class="cat-layout">
  
  <!-- SIDEBAR FILTRES -->
  <aside class="cat-sidebar">
    <div class="filter-head">
      <h3><i class="fas fa-sliders-h"></i> Filtres</h3>
      <?php if ($category || $filter || $search || $matiere || $marque || $max_price > 0): ?>
        <a href="catalogue.php" class="filter-clear">Tout effacer</a>
      <?php endif; ?>
    </div>

    <!-- Catégories -->
    <div class="filter-block">
      <div class="filter-title">Catégories</div>
      <ul class="filter-list">
        <li><a href="?page=1" class="filter-link <?= !$category ? 'active' : '' ?>"><i class="fas fa-gem"></i> Tous</a></li>
        <li><a href="?category=Femme&page=1" class="filter-link <?= $category === 'Femme' ? 'active' : '' ?>"><i class="fas fa-venus"></i> Femme</a></li>
        <li><a href="?category=Homme&page=1" class="filter-link <?= $category === 'Homme' ? 'active' : '' ?>"><i class="fas fa-mars"></i> Homme</a></li>
        <li><a href="?category=Unisexe&page=1" class="filter-link <?= $category === 'Unisexe' ? 'active' : '' ?>"><i class="fas fa-venus-mars"></i> Unisexe</a></li>
      </ul>
    </div>

    <!-- Sélections -->
    <div class="filter-block">
      <div class="filter-title">Sélections</div>
      <ul class="filter-list">
        <li><a href="?filter=new&page=1" class="filter-link <?= $filter === 'new' ? 'active' : '' ?>"><i class="fas fa-star"></i> Nouveautés</a></li>
        <li><a href="?filter=bestseller&page=1" class="filter-link <?= $filter === 'bestseller' ? 'active' : '' ?>"><i class="fas fa-fire"></i> Best-sellers</a></li>
        <li><a href="?filter=promo&page=1" class="filter-link <?= $filter === 'promo' ? 'active' : '' ?>"><i class="fas fa-tag"></i> Promotions</a></li>
      </ul>
    </div>

    <!-- Matière -->
    <div class="filter-block">
      <div class="filter-title">Matière</div>
      <ul class="filter-list">
        <?php
        try {
          $stmt = $pdo->query("SELECT DISTINCT matiere FROM produits WHERE matiere IS NOT NULL ORDER BY matiere");
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $val = $row['matiere'];
            $active = ($matiere === $val) ? 'active' : '';
            echo "<li><a href=\"?matiere=" . urlencode($val) . "&page=1\" class=\"filter-link $active\"><i class=\"fas fa-gem\"></i> " . htmlspecialchars($val) . "</a></li>";
          }
        } catch (Exception $e) {}
        ?>
      </ul>
    </div>

    <!-- Marque -->
    <div class="filter-block">
      <div class="filter-title">Marques</div>
      <ul class="filter-list">
        <?php
        try {
          $stmt = $pdo->query("SELECT DISTINCT marque FROM produits WHERE marque IS NOT NULL ORDER BY marque");
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $val = $row['marque'];
            $active = ($marque === $val) ? 'active' : '';
            echo "<li><a href=\"?marque=" . urlencode($val) . "&page=1\" class=\"filter-link $active\"><i class=\"fas fa-copyright\"></i> " . htmlspecialchars($val) . "</a></li>";
          }
        } catch (Exception $e) {}
        ?>
      </ul>
    </div>

    <!-- Prix -->
    <div class="filter-block">
      <div class="filter-title">Prix maximum</div>
      <div class="price-range">
        <div class="price-labels"><span>0 €</span><span>2800€</span></div>
        <input type="range" min="0" max="2800" value="<?= $max_price ?: 2800 ?>" class="price-slider" id="priceSlider">
        <div class="price-selected">Jusqu'à : <span id="priceValue"><?= $max_price ?: 2800 ?> €</span></div>
        <button id="applyPrice" class="btn-apply-price">Appliquer</button>
      </div>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="cat-main">
    
    <?php if ($total_products > 0): ?>
      
      <div class="prods-grid">
        <?php foreach ($products as $i => $p):
          $price = $p['prix'];
          $discount = $p['promotion_pourcentage'] ?? 0;
          $final_price = $discount > 0 ? $price * (1 - $discount / 100) : $price;
          $image = $p['image_url'] ?: 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?auto=format&fit=crop&w=800&q=80';
          $fav = in_array($p['id_produit'], $ids_favoris);
          
          $badgeClass = '';
          $badgeText = '';
          if ($discount > 0) {
            $badgeClass = 'promo';
            $badgeText = '-' . $discount . '%';
          } elseif ($p['nouveaute']) {
            $badgeClass = 'new';
            $badgeText = 'Nouveau';
          } elseif ($p['bestseller']) {
            $badgeClass = 'best';
            $badgeText = 'Best-seller';
          }
        ?>
          <div class="prod-card" data-aos="fade-up" data-aos-delay="<?= ($i % 12) * 50 ?>">
            <?php if ($badgeClass): ?><span class="prod-badge <?= $badgeClass ?>"><?= $badgeText ?></span><?php endif; ?>
            
            <div class="prod-img-wrap">
              <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($p['nom']) ?>" loading="lazy">
              <div class="prod-actions-hover">
                <?php if ($p['stock'] > 0): ?>
                  <?php if (isLoggedIn()): ?>
                    <form method="POST" action="ajouter_au_panier.php" style="flex:1;display:flex;">
                      <input type="hidden" name="produit_id" value="<?= $p['id_produit'] ?>">
                      <input type="hidden" name="quantite" value="1">
                      <input type="hidden" name="redirect_url" value="panier.php">
                      <button type="submit" class="btn-cart-inline"><i class="fas fa-cart-plus"></i> Ajouter</button>
                    </form>
                  <?php else: ?>
                    <a href="connexion.php" class="btn-cart-inline"><i class="fas fa-lock"></i> Connexion</a>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="btn-cart-inline" style="opacity:.5;cursor:not-allowed;">Rupture</span>
                <?php endif; ?>
                
                <button class="btn-fav-inline <?= $fav ? 'active' : '' ?>" data-id="<?= $p['id_produit'] ?>" title="Favoris">
                  <i class="<?= $fav ? 'fas' : 'far' ?> fa-heart"></i>
                </button>
              </div>
            </div>

            <div class="prod-info">
              <div class="prod-brand"><?= htmlspecialchars($p['marque'] ?? '') ?></div>
              <h3 class="prod-name"><?= htmlspecialchars($p['nom']) ?></h3>
              <p class="prod-desc"><?= htmlspecialchars(substr($p['description_courte'] ?? $p['description'] ?? '', 0, 80)) ?>...</p>
              <div class="prod-price">
                <?php if ($discount > 0): ?><span class="price-old"><?= formatPrice($price) ?></span><?php endif; ?>
                <span class="price-now"><?= formatPrice($final_price) ?></span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- PAGINATION -->
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
      
      <div class="no-results">
        <i class="fas fa-gem"></i>
        <h3>Aucun bijou trouvé</h3>
        <p>Modifiez vos filtres ou découvrez toute notre collection</p>
        <a href="catalogue.php">Voir tous les bijoux</a>
      </div>

    <?php endif; ?>
  </main>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ duration:800, once:true, easing:'ease-out-cubic', offset:50 });

// CURSEUR
const cur = document.getElementById('cursor');
const ring = document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{ mx=e.clientX; my=e.clientY; cur.style.left=mx+'px'; cur.style.top=my+'px'; });
function animRing(){ rx+=(mx-rx)*.12; ry+=(my-ry)*.12; ring.style.left=rx+'px'; ring.style.top=ry+'px'; requestAnimationFrame(animRing); }
animRing();
document.querySelectorAll('a,button,.prod-card').forEach(el=>{
  el.addEventListener('mouseenter',()=>document.body.classList.add('hovering'));
  el.addEventListener('mouseleave',()=>document.body.classList.remove('hovering'));
});

// PRIX SLIDER
const slider = document.getElementById('priceSlider');
const valueDisplay = document.getElementById('priceValue');
const applyButton = document.getElementById('applyPrice');

if (slider && valueDisplay && applyButton) {
  slider.addEventListener('input', function() {
    valueDisplay.textContent = this.value + ' €';
  });
  
  applyButton.addEventListener('click', function() {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('max_price', slider.value);
    currentUrl.searchParams.set('page', '1');
    window.location.href = currentUrl.toString();
  });
}

// FAVORIS
<?php if(isLoggedIn()): ?>
document.querySelectorAll('.btn-fav-inline').forEach(btn=>{
  btn.addEventListener('click',function(e){
    e.preventDefault();
    const id=this.dataset.id, self=this, icon=self.querySelector('i');
    fetch('ajouter_favori.php',{ method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id_produit='+id })
    .then(r=>r.json())
    .then(data=>{
      if(data.action==='added'){ self.classList.add('active'); icon.className='fas fa-heart'; }
      else { self.classList.remove('active'); icon.className='far fa-heart'; }
    })
    .catch(()=>{ window.location.href='ajouter_favori.php?id_produit='+id; });
  });
});
<?php else: ?>
document.querySelectorAll('.btn-fav-inline').forEach(btn=>btn.addEventListener('click',()=>window.location.href='connexion.php'));
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>