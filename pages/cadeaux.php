<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

function getIdByRef($pdo, $ref) {
    $stmt = $pdo->prepare("SELECT id_produit FROM produits WHERE reference = ?");
    $stmt->execute([$ref]);
    $row = $stmt->fetch();
    return $row ? $row['id_produit'] : 0;
}

$pageTitle = "Cadeaux - Lumoura Joaillerie";

$ids_favoris = [];
if (isLoggedIn()) {
    try {
        $stmt_fav = $pdo->prepare("SELECT id_produit FROM liste_envies WHERE id_utilisateur = :id");
        $stmt_fav->execute([':id' => $_SESSION['user_id']]);
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

/* HERO CADEAUX */
.hero-cadeaux {
  position: relative;
  height: 70vh;
  min-height: 550px;
  background: var(--ink);
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hero-cadeaux-bg {
  position: absolute;
  inset: 0;
  background-image: url('https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=1200&q=80');
  background-size: cover;
  background-position: center;
  filter: brightness(.3) saturate(.7);
}

.hero-cadeaux-bg::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(13,10,6,.8) 0%, rgba(59,42,26,.6) 100%);
}

.hero-cadeaux-content {
  position: relative;
  z-index: 2;
  text-align: center;
  max-width: 750px;
  padding: 0 30px;
}

.hero-tag {
  font-family: 'Cinzel', serif;
  font-size: .62rem;
  letter-spacing: 5px;
  text-transform: uppercase;
  color: var(--g2);
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
}
.hero-tag::before,
.hero-tag::after {
  content: '';
  width: 40px;
  height: 1px;
  background: var(--g1);
}

.hero-cadeaux h1 {
  font-family: 'EB Garamond', serif;
  font-size: clamp(3rem, 6vw, 5rem);
  color: #fff;
  font-weight: 400;
  letter-spacing: 3px;
  margin-bottom: 24px;
  line-height: 1.1;
}
.hero-cadeaux h1 em { color: var(--g1); font-style: italic; }

.hero-cadeaux p {
  color: rgba(255,255,255,.7);
  font-size: 1rem;
  line-height: 1.9;
  letter-spacing: .5px;
  margin-bottom: 38px;
  max-width: 620px;
  margin-left: auto;
  margin-right: auto;
}

.btn-hero-cadeaux {
  background: var(--g1);
  color: var(--ink);
  padding: 16px 42px;
  font-family: 'Cinzel', serif;
  font-size: .7rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  text-decoration: none;
  font-weight: 700;
  display: inline-block;
  position: relative;
  overflow: hidden;
  transition: color .35s;
}
.btn-hero-cadeaux::before {
  content:'';
  position:absolute;
  inset:0;
  background: var(--g2);
  transform: scaleX(0);
  transform-origin: right;
  transition: transform .35s cubic-bezier(.77,0,.18,1);
}
.btn-hero-cadeaux:hover::before { transform:scaleX(1); transform-origin:left; }
.btn-hero-cadeaux:hover { color: var(--ink); }
.btn-hero-cadeaux span { position:relative; z-index:1; }

/* SECTION HEADER */
.s-head {
  text-align: center;
  padding: 90px 20px 20px;
}
.s-head-tag {
  font-size: .6rem;
  letter-spacing: 5px;
  text-transform: uppercase;
  color: var(--g1);
  font-family: 'Cinzel', serif;
  display: block;
  margin-bottom: 14px;
}
.s-head h2 {
  font-family: 'EB Garamond', serif;
  font-size: clamp(2.2rem, 4vw, 3.4rem);
  font-weight: 400;
  color: var(--ink);
  letter-spacing: 1px;
  line-height: 1.2;
}
.s-head h2 em { font-style:italic; color:var(--g3); }
.s-rule {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 18px;
  margin: 22px 0 60px;
}
.s-rule-line { width: 60px; height: 1px; background: linear-gradient(90deg, transparent, var(--g1)); }
.s-rule-line:last-child { background: linear-gradient(90deg, var(--g1), transparent); }
.s-rule-diamond {
  width: 6px; height: 6px;
  background: var(--g1);
  transform: rotate(45deg);
  position: relative;
}

/* COFFRETS GRID */
.coffrets-section {
  padding: 0 0 80px;
  background: var(--smoke);
}

.coffrets-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1px;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 40px;
  background: var(--stone);
  border-top: 1px solid var(--stone);
  border-bottom: 1px solid var(--stone);
}

.coffret-card {
  background: var(--smoke);
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform .4s cubic-bezier(.23,1,.32,1);
}
.coffret-card:hover { transform: translateY(-6px); z-index: 3; box-shadow: 0 24px 60px rgba(0,0,0,.18); }

.coffret-badge {
  position: absolute;
  top: 0; right: 0;
  z-index: 6;
  font-family: 'Cinzel', serif;
  font-size: .52rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  padding: 7px 13px;
  background: var(--g1);
  color: var(--ink);
}

.coffret-img {
  position: relative;
  height: 280px;
  overflow: hidden;
  background: var(--stone);
  flex-shrink: 0;
}
.coffret-img img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform .9s cubic-bezier(.25,.46,.45,.94);
}
.coffret-card:hover .coffret-img img { transform: scale(1.12); }

.coffret-img::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to bottom, transparent 50%, rgba(212,168,67,.12) 100%);
  opacity: 0;
  transition: opacity .4s;
}
.coffret-card:hover .coffret-img::after { opacity: 1; }

.coffret-actions-hover {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  display: flex;
  transform: translateY(101%);
  transition: transform .4s cubic-bezier(.77,0,.18,1);
  z-index: 5;
}
.coffret-card:hover .coffret-actions-hover { transform: translateY(0); }

.btn-cart-coffret {
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
.btn-cart-coffret:hover { background: var(--g1); color: var(--ink); }

.coffret-info {
  padding: 24px 22px;
  flex: 1;
  display: flex;
  flex-direction: column;
  border-top: 1px solid rgba(0,0,0,.05);
  position: relative;
}

.coffret-info::before {
  content: '';
  position: absolute;
  top: 0; left: 22px; right: 22px;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--g1), transparent);
  transform: scaleX(0);
  transition: transform .5s cubic-bezier(.77,0,.18,1);
}
.coffret-card:hover .coffret-info::before { transform: scaleX(1); }

.coffret-label {
  font-family: 'Cinzel', serif;
  font-size: .52rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--g1);
  margin-bottom: 7px;
}

.coffret-name {
  font-family: 'EB Garamond', serif;
  font-size: 1.3rem;
  font-weight: 400;
  color: var(--ink);
  line-height: 1.3;
  margin-bottom: 10px;
}

.coffret-desc {
  font-size: .76rem;
  color: var(--muted);
  line-height: 1.7;
  margin-bottom: 18px;
  flex: 1;
}

.coffret-price {
  font-family: 'EB Garamond', serif;
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--ink);
  margin-bottom: 8px;
}

/* CARTE CADEAU SECTION */
.carte-section {
  background: var(--ink);
  padding: 0;
  overflow: hidden;
}

.carte-banner {
  position: relative;
  min-height: 600px;
  display: flex;
  align-items: center;
  padding: 80px 8%;
  background-image: url('https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=1200&q=80');
  background-size: cover;
  background-position: center;
}

.carte-banner::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(110deg, rgba(13,10,6,.95) 0%, rgba(13,10,6,.75) 45%, transparent 80%);
}

.carte-content {
  position: relative;
  z-index: 2;
  max-width: 580px;
}

.carte-eyebrow {
  font-family: 'Cinzel', serif;
  font-size: .58rem;
  letter-spacing: 5px;
  text-transform: uppercase;
  color: var(--g1);
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 22px;
}
.carte-eyebrow::before { content: ''; width: 35px; height: 1px; background: var(--g1); }

.carte-content h2 {
  font-family: 'EB Garamond', serif;
  font-size: clamp(2.2rem, 4vw, 3.5rem);
  font-weight: 400;
  color: #fff;
  line-height: 1.2;
  margin-bottom: 22px;
  letter-spacing: 1px;
}
.carte-content h2 em { color: var(--g2); font-style: italic; }

.carte-content p {
  font-size: .9rem;
  color: rgba(255,255,255,.65);
  line-height: 1.9;
  margin-bottom: 34px;
  letter-spacing: .4px;
}

.btn-carte {
  background: var(--g1);
  color: var(--ink);
  padding: 15px 40px;
  font-family: 'Cinzel', serif;
  font-size: .68rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  text-decoration: none;
  font-weight: 700;
  display: inline-block;
  position: relative;
  overflow: hidden;
  transition: color .35s;
}
.btn-carte::before {
  content:'';
  position:absolute;
  inset:0;
  background: var(--g2);
  transform: scaleX(0);
  transform-origin: right;
  transition: transform .35s cubic-bezier(.77,0,.18,1);
}
.btn-carte:hover::before { transform:scaleX(1); transform-origin:left; }
.btn-carte:hover { color: var(--ink); }
.btn-carte span { position:relative; z-index:1; }

.carte-visuel-wrap {
  position: absolute;
  right: 8%;
  top: 50%;
  transform: translateY(-50%);
  z-index: 3;
}

.carte-visuel {
  width: 360px;
  height: 220px;
  background: linear-gradient(135deg, var(--g1) 0%, var(--g2) 50%, var(--g3) 100%);
  border-radius: 18px;
  padding: 32px;
  box-shadow: 0 25px 80px rgba(0,0,0,.5);
  position: relative;
  overflow: hidden;
}

.carte-visuel::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,.15) 0%, transparent 60%);
  animation: shimmer 4s ease-in-out infinite;
}

@keyframes shimmer {
  0%, 100% { transform: translate(0,0); }
  50% { transform: translate(-30px,-30px); }
}

.carte-logo {
  font-family: 'Cinzel', serif;
  font-size: .85rem;
  color: var(--ink);
  letter-spacing: 4px;
  font-weight: 700;
  margin-bottom: 50px;
  text-transform: uppercase;
}

.carte-montant {
  font-family: 'EB Garamond', serif;
  font-size: 3.5rem;
  color: var(--ink);
  font-weight: 700;
  line-height: 1;
  margin-bottom: 8px;
}

.carte-label {
  font-family: 'Cinzel', serif;
  font-size: .62rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: rgba(13,10,6,.7);
}

.carte-tagline {
  position: absolute;
  bottom: 28px;
  left: 32px;
  right: 32px;
  font-style: italic;
  font-size: .75rem;
  color: rgba(13,10,6,.6);
  text-align: center;
}

/* MONTANTS */
.montants-section {
  background: var(--ink);
  padding: 70px 40px;
  text-align: center;
}

.montants-section h3 {
  font-family: 'EB Garamond', serif;
  font-size: 2rem;
  color: #fff;
  margin-bottom: 12px;
}

.montants-section p {
  color: rgba(255,255,255,.5);
  font-size: .85rem;
  margin-bottom: 40px;
  letter-spacing: .5px;
}

.montants-grid {
  display: flex;
  gap: 14px;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 35px;
}

.montant-btn {
  background: rgba(255,255,255,.06);
  border: 2px solid rgba(255,255,255,.12);
  color: rgba(255,255,255,.7);
  padding: 16px 34px;
  font-family: 'EB Garamond', serif;
  font-size: 1.2rem;
  font-weight: 600;
  cursor: pointer;
  transition: all .3s;
  position: relative;
  overflow: hidden;
}
.montant-btn::before {
  content: '';
  position: absolute;
  inset: 0;
  background: var(--g1);
  transform: scaleX(0);
  transform-origin: left;
  transition: transform .3s;
  z-index: -1;
}
.montant-btn:hover,
.montant-btn.selected {
  border-color: var(--g1);
  color: var(--ink);
}
.montant-btn:hover::before,
.montant-btn.selected::before { transform: scaleX(1); }

.form-carte {
  max-width: 720px;
  margin: 0 auto;
  background: rgba(255,255,255,.04);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 12px;
  padding: 40px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

.form-row input,
.form-row.full input {
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.12);
  color: #fff;
  padding: 14px 18px;
  font-size: .85rem;
  font-family: 'Didact Gothic', sans-serif;
  transition: all .3s;
  outline: none;
}
.form-row input:focus,
.form-row.full input:focus {
  background: rgba(255,255,255,.1);
  border-color: var(--g1);
}
.form-row input::placeholder { color: rgba(255,255,255,.3); }

.form-row.full { grid-template-columns: 1fr; }

.btn-submit-carte {
  width: 100%;
  background: var(--g1);
  color: var(--ink);
  border: none;
  padding: 16px;
  font-family: 'Cinzel', serif;
  font-size: .68rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  cursor: pointer;
  font-weight: 700;
  margin-top: 10px;
  transition: all .3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}
.btn-submit-carte:hover {
  background: var(--g2);
  transform: translateY(-2px);
}

.alert {
  padding: 16px 24px;
  border-radius: 8px;
  margin-bottom: 30px;
  font-size: .85rem;
  display: flex;
  align-items: center;
  gap: 12px;
}
.alert-success {
  background: rgba(76,175,80,.15);
  border: 1px solid rgba(76,175,80,.3);
  color: #a5d6a7;
}
.alert-error {
  background: rgba(244,67,54,.15);
  border: 1px solid rgba(244,67,54,.3);
  color: #ef9a9a;
}

/* POURQUOI */
.pourquoi-section {
  padding: 80px 40px;
  background: var(--smoke);
}

.pourquoi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 35px;
  max-width: 1200px;
  margin: 40px auto 0;
}

.pourquoi-card {
  padding: 35px 24px;
  background: #fff;
  border-radius: 12px;
  text-align: center;
  transition: all .4s;
  border: 1px solid var(--stone);
}
.pourquoi-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 50px rgba(0,0,0,.12);
  border-color: var(--g1);
}

.icon-wrap {
  width: 68px;
  height: 68px;
  background: linear-gradient(135deg, var(--g1), var(--g2));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  transition: transform .4s;
}
.pourquoi-card:hover .icon-wrap { transform: scale(1.1) rotate(5deg); }

.icon-wrap i {
  font-size: 1.6rem;
  color: var(--ink);
}

.pourquoi-card h4 {
  font-family: 'EB Garamond', serif;
  font-size: 1.15rem;
  color: var(--ink);
  margin-bottom: 10px;
  font-weight: 600;
}

.pourquoi-card p {
  font-size: .78rem;
  color: var(--muted);
  line-height: 1.7;
}

/* RESPONSIVE */
@media (max-width: 1100px) {
  .coffrets-grid { grid-template-columns: repeat(2, 1fr); }
  .pourquoi-grid { grid-template-columns: repeat(2, 1fr); }
  .carte-visuel-wrap { position: static; transform: none; margin-top: 50px; }
}
@media (max-width: 768px) {
  .coffrets-grid { grid-template-columns: 1fr; }
  .pourquoi-grid { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
  body { cursor: auto; }
  #cursor, #cursor-ring { display: none; }
}
</style>

<!-- CURSEUR -->
<div id="cursor"></div>
<div id="cursor-ring"></div>

<!-- HERO -->
<section class="hero-cadeaux">
  <div class="hero-cadeaux-bg"></div>
  <div class="hero-cadeaux-content" data-aos="fade-up">
    <div class="hero-tag">L'Art d'Offrir</div>
    <h1>Cadeaux <em>d'Exception</em></h1>
    <p>Bijoux raffinés en coffrets luxe ou cartes cadeaux personnalisées — le présent parfait pour chaque occasion précieuse</p>
    <a href="#coffrets" class="btn-hero-cadeaux"><span>Découvrir</span></a>
  </div>
</section>

<!-- COFFRETS -->
<section class="coffrets-section" id="coffrets">
  <div class="s-head" data-aos="fade-up">
    <span class="s-head-tag">Sélection Premium</span>
    <h2>Nos Coffrets <em>Cadeaux</em></h2>
    <div class="s-rule"><div class="s-rule-line"></div><div class="s-rule-diamond"></div><div class="s-rule-line"></div></div>
  </div>

  <div class="coffrets-grid">
    
    <!-- Coffret 1 -->
    <div class="coffret-card" data-aos="fade-up" data-aos-delay="100">
      <span class="coffret-badge">Populaire</span>
      <div class="coffret-img">
        <img src="https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=500&q=80" alt="Coffret Élégance" loading="lazy">
        <div class="coffret-actions-hover">
          <?php if (isLoggedIn()): ?>
            <form method="POST" action="ajouter_au_panier.php" style="flex:1;display:flex;">
              <input type="hidden" name="produit_id" value="<?= getIdByRef($pdo, 'COFF-001') ?>">
              <input type="hidden" name="quantite" value="1">
              <input type="hidden" name="redirect_url" value="panier.php">
              <button type="submit" name="ajouter_panier" class="btn-cart-coffret"><i class="fas fa-shopping-bag"></i> Ajouter</button>
            </form>
          <?php else: ?>
            <a href="connexion.php" class="btn-cart-coffret"><i class="fas fa-lock"></i> Connexion</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="coffret-info">
        <div class="coffret-label">Pour Elle</div>
        <h3 class="coffret-name">Coffret Élégance</h3>
        <p class="coffret-desc">Collier délicat et boucles d'oreilles assorties dans un écrin velours bordeaux</p>
        <div class="coffret-price">290 €</div>
      </div>
    </div>

    <!-- Coffret 2 -->
    <div class="coffret-card" data-aos="fade-up" data-aos-delay="200">
      <span class="coffret-badge">Best-seller</span>
      <div class="coffret-img">
        <img src="https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=500&q=80" alt="Coffret Prestige" loading="lazy">
        <div class="coffret-actions-hover">
          <?php if (isLoggedIn()): ?>
            <form method="POST" action="ajouter_au_panier.php" style="flex:1;display:flex;">
              <input type="hidden" name="produit_id" value="<?= getIdByRef($pdo, 'COFF-002') ?>">
              <input type="hidden" name="quantite" value="1">
              <input type="hidden" name="redirect_url" value="panier.php">
              <button type="submit" name="ajouter_panier" class="btn-cart-coffret"><i class="fas fa-shopping-bag"></i> Ajouter</button>
            </form>
          <?php else: ?>
            <a href="connexion.php" class="btn-cart-coffret"><i class="fas fa-lock"></i> Connexion</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="coffret-info">
        <div class="coffret-label">Unisexe</div>
        <h3 class="coffret-name">Coffret Prestige</h3>
        <p class="coffret-desc">Montre raffinée et bracelet or 18K — luxe et intemporalité pour amateurs</p>
        <div class="coffret-price">850 €</div>
      </div>
    </div>

    <!-- Coffret 3 -->
    <div class="coffret-card" data-aos="fade-up" data-aos-delay="300">
      <div class="coffret-img">
        <img src="https://ae01.alicdn.com/kf/Sfc038ffb987c45b2b25fd2d5164e52b0G.jpg" alt="Coffret Amour" loading="lazy">
        <div class="coffret-actions-hover">
          <?php if (isLoggedIn()): ?>
            <form method="POST" action="ajouter_au_panier.php" style="flex:1;display:flex;">
              <input type="hidden" name="produit_id" value="<?= getIdByRef($pdo, 'COFF-003') ?>">
              <input type="hidden" name="quantite" value="1">
              <input type="hidden" name="redirect_url" value="panier.php">
              <button type="submit" name="ajouter_panier" class="btn-cart-coffret"><i class="fas fa-shopping-bag"></i> Ajouter</button>
            </form>
          <?php else: ?>
            <a href="connexion.php" class="btn-cart-coffret"><i class="fas fa-lock"></i> Connexion</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="coffret-info">
        <div class="coffret-label">Couple</div>
        <h3 class="coffret-name">Coffret Amour</h3>
        <p class="coffret-desc">Deux alliances assorties symbole d'union éternelle avec gravure personnalisée</p>
        <div class="coffret-price">1 200 €</div>
      </div>
    </div>

    <!-- Coffret 4 -->
    <div class="coffret-card" data-aos="fade-up" data-aos-delay="100">
      <span class="coffret-badge">Nouveau</span>
      <div class="coffret-img">
        <img src="https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=500&q=80" alt="Coffret Diamant" loading="lazy">
        <div class="coffret-actions-hover">
          <?php if (isLoggedIn()): ?>
            <form method="POST" action="ajouter_au_panier.php" style="flex:1;display:flex;">
              <input type="hidden" name="produit_id" value="<?= getIdByRef($pdo, 'COFF-004') ?>">
              <input type="hidden" name="quantite" value="1">
              <input type="hidden" name="redirect_url" value="panier.php">
              <button type="submit" name="ajouter_panier" class="btn-cart-coffret"><i class="fas fa-shopping-bag"></i> Ajouter</button>
            </form>
          <?php else: ?>
            <a href="connexion.php" class="btn-cart-coffret"><i class="fas fa-lock"></i> Connexion</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="coffret-info">
        <div class="coffret-label">Pour Elle</div>
        <h3 class="coffret-name">Coffret Diamant</h3>
        <p class="coffret-desc">Bague solitaire diamant en écrin exclusif — geste inoubliable grandes occasions</p>
        <div class="coffret-price">2 400 €</div>
      </div>
    </div>

    <!-- Coffret 5 -->
    <div class="coffret-card" data-aos="fade-up" data-aos-delay="200">
      <div class="coffret-img">
        <img src="https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=500&q=80" alt="Coffret Gentleman" loading="lazy">
        <div class="coffret-actions-hover">
          <?php if (isLoggedIn()): ?>
            <form method="POST" action="ajouter_au_panier.php" style="flex:1;display:flex;">
              <input type="hidden" name="produit_id" value="<?= getIdByRef($pdo, 'COFF-005') ?>">
              <input type="hidden" name="quantite" value="1">
              <input type="hidden" name="redirect_url" value="panier.php">
              <button type="submit" name="ajouter_panier" class="btn-cart-coffret"><i class="fas fa-shopping-bag"></i> Ajouter</button>
            </form>
          <?php else: ?>
            <a href="connexion.php" class="btn-cart-coffret"><i class="fas fa-lock"></i> Connexion</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="coffret-info">
        <div class="coffret-label">Pour Lui</div>
        <h3 class="coffret-name">Coffret Gentleman</h3>
        <p class="coffret-desc">Manchettes et chevalière en or blanc — élégance masculine dans sa splendeur</p>
        <div class="coffret-price">620 €</div>
      </div>
    </div>

    <!-- Coffret 6 -->
    <div class="coffret-card" data-aos="fade-up" data-aos-delay="300">
      <span class="coffret-badge">Tendance</span>
      <div class="coffret-img">
        <img src="https://images.unsplash.com/photo-1602751584552-8ba73aad10e1?w=500&q=80" alt="Coffret Naissance" loading="lazy">
        <div class="coffret-actions-hover">
          <?php if (isLoggedIn()): ?>
            <form method="POST" action="ajouter_au_panier.php" style="flex:1;display:flex;">
              <input type="hidden" name="produit_id" value="<?= getIdByRef($pdo, 'COFF-006') ?>">
              <input type="hidden" name="quantite" value="1">
              <input type="hidden" name="redirect_url" value="panier.php">
              <button type="submit" name="ajouter_panier" class="btn-cart-coffret"><i class="fas fa-shopping-bag"></i> Ajouter</button>
            </form>
          <?php else: ?>
            <a href="connexion.php" class="btn-cart-coffret"><i class="fas fa-lock"></i> Connexion</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="coffret-info">
        <div class="coffret-label">Naissance</div>
        <h3 class="coffret-name">Coffret Naissance</h3>
        <p class="coffret-desc">Bracelet et médaille gravée pour bébé — souvenir précieux premiers instants</p>
        <div class="coffret-price">180 €</div>
      </div>
    </div>

  </div>
</section>

<!-- CARTE CADEAU -->
<section class="carte-section">
  <div class="carte-banner">
    <div class="carte-content" data-aos="fade-right">
      <div class="carte-eyebrow">Le Cadeau Parfait</div>
      <h2>La Carte Cadeau<br><em>Lumoura</em></h2>
      <p>Laissez vos proches choisir le bijou de leurs rêves. Carte disponible de 50 € à 5 000 €, valable 12 mois sur toute la boutique avec emballage premium offert.</p>
      <a href="#montants" class="btn-carte"><span>Choisir un montant</span></a>
    </div>
    <div class="carte-visuel-wrap" data-aos="fade-left" data-aos-delay="200">
      <div class="carte-visuel">
        <div class="carte-logo">LUMOURA</div>
        <div class="carte-montant" id="carteAmount">250 €</div>
        <div class="carte-label">Carte Cadeau</div>
        <div class="carte-tagline">L'art de donner le meilleur</div>
      </div>
    </div>
  </div>
</section>

<!-- MONTANTS -->
<section class="montants-section" id="montants">
  <h3 data-aos="fade-up">Choisissez votre montant</h3>
  <p data-aos="fade-up">Sélectionnez un montant ou entrez une valeur personnalisée</p>

  <?php if (isset($_GET['carte_success'])): ?>
    <div class="alert alert-success" data-aos="fade-up">
      <i class="fas fa-check-circle"></i> Votre demande de carte cadeau a bien été envoyée ! Nous vous contacterons sous 24h.
    </div>
  <?php elseif (isset($_GET['carte_error'])): ?>
    <div class="alert alert-error" data-aos="fade-up">
      <i class="fas fa-exclamation-circle"></i> Une erreur s'est produite. Veuillez réessayer.
    </div>
  <?php endif; ?>

  <div class="montants-grid" data-aos="fade-up" data-aos-delay="100">
    <button class="montant-btn" onclick="selectMontant(this, 50)">50 €</button>
    <button class="montant-btn" onclick="selectMontant(this, 100)">100 €</button>
    <button class="montant-btn selected" onclick="selectMontant(this, 250)">250 €</button>
    <button class="montant-btn" onclick="selectMontant(this, 500)">500 €</button>
    <button class="montant-btn" onclick="selectMontant(this, 1000)">1 000 €</button>
  </div>

  <form method="POST" action="envoyer_carte_cadeau.php" class="form-carte" data-aos="fade-up" data-aos-delay="200">
    <input type="hidden" name="montant" id="montantHidden" value="250">
    
    <div class="form-row">
      <input type="text" name="prenom" placeholder="Votre prénom *" required>
      <input type="email" name="email" placeholder="Votre email *" required>
    </div>
    
    <div class="form-row">
      <input type="number" name="montant_custom" id="montantInput" placeholder="Montant personnalisé (50-5000€)" min="50" max="5000"
        oninput="updateMontant(this.value)">
      <input type="text" name="message" placeholder="Message pour le bénéficiaire (optionnel)">
    </div>
    
    <button type="submit" class="btn-submit-carte">
      <i class="fas fa-paper-plane"></i> Envoyer ma demande
    </button>
  </form>
</section>

<!-- POURQUOI -->
<section class="pourquoi-section">
  <div class="s-head" data-aos="fade-up">
    <span class="s-head-tag">Nos Engagements</span>
    <h2>Pourquoi offrir <em>Lumoura</em> ?</h2>
    <div class="s-rule"><div class="s-rule-line"></div><div class="s-rule-diamond"></div><div class="s-rule-line"></div></div>
  </div>

  <div class="pourquoi-grid">
    <div class="pourquoi-card" data-aos="fade-up" data-aos-delay="100">
      <div class="icon-wrap"><i class="fas fa-gift"></i></div>
      <h4>Emballage Luxe</h4>
      <p>Écrin en velours avec ruban doré et carte personnalisée pour chaque commande</p>
    </div>

    <div class="pourquoi-card" data-aos="fade-up" data-aos-delay="200">
      <div class="icon-wrap"><i class="fas fa-pen-fancy"></i></div>
      <h4>Gravure Offerte</h4>
      <p>Message manuscrit ou gravure sur le bijou pour un souvenir unique et personnel</p>
    </div>

    <div class="pourquoi-card" data-aos="fade-up" data-aos-delay="300">
      <div class="icon-wrap"><i class="fas fa-truck"></i></div>
      <h4>Livraison Express</h4>
      <p>Expédition sécurisée sous 24-48h avec suivi en temps réel jusqu'à votre porte</p>
    </div>

    <div class="pourquoi-card" data-aos="fade-up" data-aos-delay="400">
      <div class="icon-wrap"><i class="fas fa-undo"></i></div>
      <h4>Retour 30 Jours</h4>
      <p>Satisfaction garantie avec retour gratuit sous 30 jours si le bijou ne convient pas</p>
    </div>
  </div>
</section>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ duration:900, once:true, easing:'ease-out-cubic', offset:60 });

// CURSEUR
const cur = document.getElementById('cursor');
const ring = document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{ mx=e.clientX; my=e.clientY; cur.style.left=mx+'px'; cur.style.top=my+'px'; });
function animRing(){ rx+=(mx-rx)*.12; ry+=(my-ry)*.12; ring.style.left=rx+'px'; ring.style.top=ry+'px'; requestAnimationFrame(animRing); }
animRing();
document.querySelectorAll('a,button,.coffret-card').forEach(el=>{
  el.addEventListener('mouseenter',()=>document.body.classList.add('hovering'));
  el.addEventListener('mouseleave',()=>document.body.classList.remove('hovering'));
});

// MONTANTS
function selectMontant(btn, montant) {
  document.querySelectorAll('.montant-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  document.getElementById('montantHidden').value = montant;
  document.getElementById('montantInput').value = montant;
  document.getElementById('carteAmount').textContent = montant + ' €';
}

function updateMontant(val) {
  if(val >= 50 && val <= 5000) {
    document.getElementById('montantHidden').value = val;
    document.getElementById('carteAmount').textContent = val + ' €';
    document.querySelectorAll('.montant-btn').forEach(b => b.classList.remove('selected'));
  }
}
</script>

<?php include '../includes/footer.php'; ?>