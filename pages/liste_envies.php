<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Sécurité : utilisateur connecté
if (!isLoggedIn()) {
    header('Location: connexion.php?redirect=liste_envies.php');
    exit();
}

$id_utilisateur = (int) $_SESSION['user_id'];

// Récupération des favoris
$favoris = [];
$sql = "
    SELECT 
        p.id_produit,
        p.nom,
        p.prix,
        p.image_url,
        p.marque,
        p.promotion_pourcentage
    FROM produits p
    INNER JOIN liste_envies f ON f.id_produit = p.id_produit
    WHERE f.id_utilisateur = :id_utilisateur
    ORDER BY f.date_ajout DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id_utilisateur' => $id_utilisateur]);
$favoris = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Mes Favoris";
include '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Didact+Gothic&family=EB+Garamond:ital,wght@0,400;0,500;0,700;1,400;1,600&family=Cinzel:wght@400;600;700;900&display=swap" rel="stylesheet">

<style>
:root {
  --g1: #D4A843;
  --g2: #F5D78E;
  --g3: #B8882C;
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
}

/* ═══════════════════════════════════════════════
   CURSEUR
═══════════════════════════════════════════════ */
#cursor {
  position: fixed;
  width: 10px; height: 10px;
  background: var(--g1);
  border-radius: 50%;
  pointer-events: none;
  z-index: 99999;
  transform: translate(-50%,-50%);
  transition: transform .1s, width .25s, height .25s, background .25s;
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
body.hovering #cursor-ring { width: 54px; height: 54px; border-color: var(--g2); opacity: .4; }

/* ═══════════════════════════════════════════════
   PAGE HERO
═══════════════════════════════════════════════ */
.page-hero {
  background: var(--ink);
  padding: 100px 20px 60px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.page-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(212,168,67,.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(212,168,67,.04) 1px, transparent 1px);
  background-size: 50px 50px;
  z-index: 1;
}

.page-hero-content {
  position: relative;
  z-index: 2;
}

.page-hero-tag {
  font-family: 'Cinzel', serif;
  font-size: .58rem;
  letter-spacing: 5px;
  text-transform: uppercase;
  color: var(--g2);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  margin-bottom: 18px;
}

.page-hero-tag::before,
.page-hero-tag::after {
  content: '';
  width: 40px;
  height: 1px;
  background: var(--g1);
}

.page-hero h1 {
  font-family: 'EB Garamond', serif;
  font-size: clamp(2.5rem, 5vw, 3.8rem);
  font-weight: 400;
  color: #fff;
  letter-spacing: 1px;
  margin-bottom: 15px;
}

.page-hero h1 em {
  font-style: italic;
  color: var(--g2);
}

.page-hero p {
  font-size: .95rem;
  color: rgba(255,255,255,.5);
  letter-spacing: .5px;
}

/* ═══════════════════════════════════════════════
   ALERTS
═══════════════════════════════════════════════ */
.alert-box {
  max-width: 1200px;
  margin: 30px auto;
  padding: 18px 40px;
  border-radius: 0;
  font-family: 'Cinzel', serif;
  font-size: .75rem;
  letter-spacing: 1.5px;
  display: flex;
  align-items: center;
  gap: 12px;
  animation: slideDown .5s ease;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}

.alert-success {
  background: rgba(76, 175, 80, .1);
  border-left: 3px solid #4CAF50;
  color: #2e7d32;
}

.alert-error {
  background: rgba(244, 67, 54, .1);
  border-left: 3px solid #f44336;
  color: #c62828;
}

.alert-box i {
  font-size: 1rem;
}

/* ═══════════════════════════════════════════════
   FAVORIS GRID
═══════════════════════════════════════════════ */
.favoris-container {
  max-width: 1400px;
  margin: 60px auto;
  padding: 0 40px;
}

.favoris-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1px;
  background: var(--stone);
  border: 1px solid var(--stone);
}

.favori-card {
  background: #fff;
  transition: transform .4s cubic-bezier(.23,1,.32,1);
  position: relative;
}

.favori-card:hover {
  transform: translateY(-6px);
  z-index: 2;
  box-shadow: 0 20px 50px rgba(0,0,0,.12);
}

.favori-img {
  height: 280px;
  overflow: hidden;
  background: var(--smoke);
}

.favori-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform .8s ease;
}

.favori-card:hover .favori-img img {
  transform: scale(1.1);
}

.favori-body {
  padding: 25px;
  border-top: 1px solid var(--stone);
}

.favori-marque {
  font-family: 'Cinzel', serif;
  font-size: .52rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--g1);
  margin-bottom: 8px;
}

.favori-nom {
  font-family: 'EB Garamond', serif;
  font-size: 1.1rem;
  font-weight: 400;
  color: var(--ink);
  margin-bottom: 12px;
  line-height: 1.3;
}

.favori-prix {
  display: flex;
  align-items: baseline;
  gap: 10px;
  margin-bottom: 18px;
}

.prix-original {
  font-size: .8rem;
  color: var(--muted);
  text-decoration: line-through;
}

.prix-actuel {
  font-family: 'EB Garamond', serif;
  font-size: 1.3rem;
  font-weight: 500;
  color: var(--ink);
}

.favori-actions {
  display: flex;
  gap: 10px;
}

.btn-panier {
  flex: 1;
  background: var(--g1);
  border: none;
  color: var(--ink);
  padding: 12px;
  font-family: 'Cinzel', serif;
  font-size: .6rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  font-weight: 600;
  cursor: pointer;
  transition: background .3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-decoration: none;
}

.btn-panier:hover {
  background: var(--g2);
}

.btn-supprimer {
  width: 45px;
  height: 45px;
  background: transparent;
  border: 1px solid var(--stone);
  color: var(--red);
  cursor: pointer;
  font-size: .9rem;
  transition: all .3s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-supprimer:hover {
  background: var(--red);
  border-color: var(--red);
  color: #fff;
}

/* ═══════════════════════════════════════════════
   EMPTY STATE
═══════════════════════════════════════════════ */
.favoris-vide {
  max-width: 600px;
  margin: 80px auto;
  padding: 80px 40px;
  text-align: center;
  background: #fff;
  border: 1px solid var(--stone);
  position: relative;
}

.favoris-vide::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 150px;
  height: 3px;
  background: linear-gradient(90deg, transparent, var(--g1), transparent);
}

.favoris-vide i {
  font-size: 4rem;
  color: var(--g1);
  opacity: .3;
  margin-bottom: 25px;
}

.favoris-vide h3 {
  font-family: 'EB Garamond', serif;
  font-size: 2rem;
  font-weight: 400;
  color: var(--ink);
  margin-bottom: 15px;
}

.favoris-vide p {
  color: var(--muted);
  font-size: .9rem;
  margin-bottom: 35px;
  line-height: 1.6;
}

.btn-catalogue {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: var(--g1);
  color: var(--ink);
  padding: 15px 35px;
  font-family: 'Cinzel', serif;
  font-size: .68rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  text-decoration: none;
  font-weight: 600;
  transition: background .3s;
  position: relative;
  overflow: hidden;
}

.btn-catalogue::before {
  content: '';
  position: absolute;
  inset: 0;
  background: var(--g2);
  transform: scaleX(0);
  transform-origin: right;
  transition: transform .35s cubic-bezier(.77,0,.18,1);
}

.btn-catalogue:hover::before {
  transform: scaleX(1);
  transform-origin: left;
}

.btn-catalogue span {
  position: relative;
  z-index: 1;
}

/* ═══════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════ */
@media (max-width: 1024px) {
  .favoris-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .favoris-container {
    padding: 0 20px;
  }
  
  .favoris-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .favoris-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<!-- CURSEUR -->
<div id="cursor"></div>
<div id="cursor-ring"></div>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="page-hero-content">
    <div class="page-hero-tag">Ma Sélection</div>
    <h1>Mes <em>Favoris</em></h1>
    <p>Vos bijoux d'exception sélectionnés avec soin</p>
  </div>
</section>

<!-- ALERTS -->
<?php if (isset($_SESSION['message'])): ?>
  <div class="alert-box alert-success">
    <i class="fas fa-check-circle"></i>
    <?= htmlspecialchars($_SESSION['message']) ?>
  </div>
  <?php unset($_SESSION['message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
  <div class="alert-box alert-error">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($_SESSION['error']) ?>
  </div>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- FAVORIS CONTENT -->
<div class="favoris-container">
  <?php if (!empty($favoris)): ?>
    <div class="favoris-grid">
      <?php foreach ($favoris as $item): ?>
        <?php
          $prix_affiche = $item['prix'];
          $promo = $item['promotion_pourcentage'] ?? 0;
          if ($promo > 0) {
              $prix_promo = $item['prix'] * (1 - $promo / 100);
          }
        ?>
        <div class="favori-card">
          <div class="favori-img">
            <img src="<?= htmlspecialchars($item['image_url'] ?? 'images/placeholder.png') ?>"
                 alt="<?= htmlspecialchars($item['nom']) ?>">
          </div>
          <div class="favori-body">
            <div class="favori-marque"><?= htmlspecialchars($item['marque'] ?? '') ?></div>
            <h3 class="favori-nom"><?= htmlspecialchars($item['nom']) ?></h3>
            <div class="favori-prix">
              <?php if ($promo > 0): ?>
                <span class="prix-original"><?= number_format($item['prix'], 2, ',', ' ') ?> €</span>
                <span class="prix-actuel"><?= number_format($prix_promo, 2, ',', ' ') ?> €</span>
              <?php else: ?>
                <span class="prix-actuel"><?= number_format($item['prix'], 2, ',', ' ') ?> €</span>
              <?php endif; ?>
            </div>
            <div class="favori-actions">
              <!-- Ajouter au panier -->
              <form method="POST" action="ajouter_au_panier.php" style="flex:1; display:flex;">
                <input type="hidden" name="produit_id" value="<?= $item['id_produit'] ?>">
                <input type="hidden" name="redirect_url" value="panier.php">
                <button type="submit" name="ajouter_panier" class="btn-panier">
                  <i class="fas fa-shopping-bag"></i> Ajouter
                </button>
              </form>
              <!-- Supprimer des favoris -->
              <form method="POST" action="supprimer_favori.php">
                <input type="hidden" name="id_produit" value="<?= $item['id_produit'] ?>">
                <button type="submit" class="btn-supprimer" title="Retirer des favoris">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  <?php else: ?>
    <div class="favoris-vide">
      <i class="far fa-heart"></i>
      <h3>Aucun favori pour le moment</h3>
      <p>Ajoutez des bijoux à vos favoris en cliquant sur le cœur lors de vos découvertes</p>
      <a href="catalogue.php" class="btn-catalogue">
        <i class="fas fa-gem"></i>
        <span>Découvrir nos bijoux</span>
      </a>
    </div>
  <?php endif; ?>
</div>

<script>
// ── CURSEUR ──
const cur = document.getElementById('cursor');
const ring = document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;

document.addEventListener('mousemove',e=>{ 
    mx=e.clientX; 
    my=e.clientY; 
    cur.style.left=mx+'px'; 
    cur.style.top=my+'px'; 
});

function animRing(){ 
    rx+=(mx-rx)*.12; 
    ry+=(my-ry)*.12; 
    ring.style.left=rx+'px'; 
    ring.style.top=ry+'px'; 
    requestAnimationFrame(animRing); 
}
animRing();

document.querySelectorAll('a,button,.favori-card').forEach(el=>{
  el.addEventListener('mouseenter',()=>document.body.classList.add('hovering'));
  el.addEventListener('mouseleave',()=>document.body.classList.remove('hovering'));
});
</script>

<?php include '../includes/footer.php'; ?>