<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = "Accueil - Lumoura Joaillerie";

$ids_favoris = [];
if (isLoggedIn()) {
    try {
        $stmt_fav = $pdo->prepare("SELECT id_produit FROM liste_envies WHERE id_utilisateur = :id");
        $stmt_fav->execute([':id' => $_SESSION['user_id']]);
        $ids_favoris = $stmt_fav->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}
}

include 'includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Didact+Gothic&family=EB+Garamond:ital,wght@0,400;0,500;0,700;1,400;1,600&family=Cinzel:wght@400;600;700;900&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════
   VARIABLES & RESET
═══════════════════════════════════════════════ */
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

/* ═══════════════════════════════════════════════
   CURSEUR PERSONNALISÉ
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
  transition: transform .08s linear, width .3s, height .3s, opacity .3s, border-color .3s;
  opacity: .6;
}
body.hovering #cursor { width: 20px; height: 20px; background: var(--g2); }
body.hovering #cursor-ring { width: 54px; height: 54px; border-color: var(--g2); opacity: .4; }

/* ═══════════════════════════════════════════════
   HERO — LUXURY SOMBRE NIVEAU CARTIER
═══════════════════════════════════════════════ */
.hero {
  position: relative;
  height: 100vh;
  min-height: 700px;
  overflow: hidden;
  background: #060402;
}

/* Grain cinématique */
.hero-grain {
  position: absolute; inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='400'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.045'/%3E%3C/svg%3E");
  background-size: 200px;
  pointer-events: none;
  z-index: 30;
  mix-blend-mode: overlay;
}

/* Vignette circulaire */
.hero-vignette {
  position: absolute; inset: 0;
  background: radial-gradient(ellipse at center, transparent 40%, rgba(6,4,2,.85) 100%);
  z-index: 4; pointer-events: none;
}

/* Lignes Art Déco */
.hero-deco {
  position: absolute; inset: 0; z-index: 5; pointer-events: none;
}
/* Ligne verticale gauche */
.hero-deco::before {
  content: '';
  position: absolute;
  left: 7%; top: 12%; bottom: 12%;
  width: 1px;
  background: linear-gradient(to bottom,
    transparent 0%,
    rgba(212,168,67,.4) 20%,
    rgba(212,168,67,.6) 50%,
    rgba(212,168,67,.4) 80%,
    transparent 100%);
  animation: decoFade 3s ease forwards;
}
/* Ligne horizontale bas */
.hero-deco::after {
  content: '';
  position: absolute;
  bottom: 18%; left: 7%; right: 7%;
  height: 1px;
  background: linear-gradient(to right,
    var(--g1) 0%,
    rgba(212,168,67,.15) 50%,
    transparent 100%);
  transform: scaleX(0);
  transform-origin: left;
  animation: decoLine 1.2s 1.5s cubic-bezier(.77,0,.18,1) forwards;
}
@keyframes decoFade { from{opacity:0} to{opacity:1} }
@keyframes decoLine { from{transform:scaleX(0)} to{transform:scaleX(1)} }

/* Coin doré Art Déco haut-gauche */
.hero-corner {
  position: absolute;
  top: 32px; left: 7%;
  width: 60px; height: 60px;
  z-index: 6; pointer-events: none;
  opacity: 0;
  animation: cornerIn 1s .5s ease forwards;
}
.hero-corner::before {
  content: '';
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 2px;
  background: linear-gradient(to right, var(--g1), transparent);
}
.hero-corner::after {
  content: '';
  position: absolute;
  top: 0; left: 0;
  width: 2px; height: 100%;
  background: linear-gradient(to bottom, var(--g1), transparent);
}
@keyframes cornerIn { from{opacity:0;transform:scale(.8)} to{opacity:1;transform:scale(1)} }

/* Coin doré bas-droite */
.hero-corner-br {
  position: absolute;
  bottom: 32px; right: 7%;
  width: 60px; height: 60px;
  z-index: 6; pointer-events: none;
  opacity: 0;
  animation: cornerIn 1s .7s ease forwards;
}
.hero-corner-br::before {
  content: '';
  position: absolute;
  bottom: 0; right: 0;
  width: 100%; height: 2px;
  background: linear-gradient(to left, var(--g1), transparent);
}
.hero-corner-br::after {
  content: '';
  position: absolute;
  bottom: 0; right: 0;
  width: 2px; height: 100%;
  background: linear-gradient(to top, var(--g1), transparent);
}

/* ── SLIDES ── */
.hero-slides { position: absolute; inset: 0; }
.hero-slide {
  position: absolute; inset: 0;
  opacity: 0;
  transition: opacity 2s cubic-bezier(.4,0,.2,1);
}
.hero-slide.active { opacity: 1; z-index: 2; }

.hero-slide-bg {
  position: absolute; inset: 0;
  background-size: cover;
  background-position: center;
  transform: scale(1.1);
  transition: transform 10s ease;
  filter: brightness(.3) saturate(.5);
}
.hero-slide.active .hero-slide-bg {
  transform: scale(1.02);
}

/* Masque split — côté gauche très sombre pour le texte */
.hero-slide::before {
  content: '';
  position: absolute; inset: 0;
  background:
    linear-gradient(110deg,
      rgba(6,4,2,.97) 0%,
      rgba(6,4,2,.88) 38%,
      rgba(6,4,2,.45) 58%,
      rgba(6,4,2,.1) 75%,
      transparent 90%);
  z-index: 1;
}

/* Masque bas pour la lisibilité */
.hero-slide::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 35%;
  background: linear-gradient(to top, rgba(6,4,2,.9), transparent);
  z-index: 1;
}

/* ── CONTENU ── */
.hero-content {
  position: absolute;
  z-index: 10;
  top: 50%;
  left: 9%;
  transform: translateY(-50%);
  max-width: 600px;
}

/* Numéro géant en filigrane */
.hero-slide-num {
  position: absolute;
  right: -10px; top: -60px;
  font-family: 'Cinzel', serif;
  font-size: 14rem;
  font-weight: 900;
  color: rgba(212,168,67,.04);
  line-height: 1;
  z-index: -1;
  user-select: none;
  letter-spacing: -8px;
}

/* Eyebrow */
.hero-eyebrow {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 32px;
  opacity: 0;
  transform: translateX(-20px);
  transition: opacity .7s .1s, transform .7s .1s;
}
.hero-slide.active .hero-eyebrow { opacity: 1; transform: none; }
.hero-eyebrow-line {
  width: 40px; height: 1px;
  background: linear-gradient(to right, var(--g1), var(--g2));
}
.hero-eyebrow-diamond {
  width: 5px; height: 5px;
  background: var(--g1);
  transform: rotate(45deg);
  flex-shrink: 0;
}
.hero-eyebrow-text {
  font-family: 'Cinzel', serif;
  font-size: .58rem;
  letter-spacing: 5px;
  text-transform: uppercase;
  color: var(--g2);
  flex: 1;
}

/* Titre — lettres individuelles */
.hero-title {
  font-family: 'Cinzel', serif;
  font-size: clamp(3.4rem, 6.5vw, 6.4rem);
  font-weight: 400;
  color: #fff;
  line-height: .98;
  letter-spacing: 4px;
  margin-bottom: 10px;
  overflow: hidden;
}
.hero-title-line {
  display: block;
  overflow: hidden;
}
.hero-title-word {
  display: inline-block;
  transform: translateY(110%);
  transition: transform .9s cubic-bezier(.16,1,.3,1);
}
.hero-slide.active .hero-title-word { transform: translateY(0); }
.hero-title-word:nth-child(1) { transition-delay: .3s; }
.hero-title-word:nth-child(2) { transition-delay: .45s; }

.hero-title-gold {
  display: block;
  font-family: 'EB Garamond', serif;
  font-style: italic;
  font-size: clamp(2.8rem, 5.5vw, 5.2rem);
  color: var(--g1);
  letter-spacing: 3px;
  overflow: hidden;
  margin-bottom: 4px;
}
.hero-title-gold .hero-title-word { transition-delay: .6s; }

/* Trait sous le titre */
.hero-title-rule {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 28px 0;
  opacity: 0;
  transition: opacity .8s .9s;
}
.hero-slide.active .hero-title-rule { opacity: 1; }
.hero-title-rule-line {
  height: 1px;
  width: 50px;
  background: linear-gradient(to right, var(--g1), transparent);
}
.hero-title-rule-dot {
  width: 4px; height: 4px;
  background: var(--g1);
  transform: rotate(45deg);
}

.hero-sub {
  font-size: .85rem;
  color: rgba(255,255,255,.45);
  line-height: 1.95;
  letter-spacing: .8px;
  margin-bottom: 44px;
  max-width: 400px;
  opacity: 0;
  transform: translateY(12px);
  transition: opacity .8s 1s, transform .8s 1s;
}
.hero-slide.active .hero-sub { opacity: 1; transform: none; }

/* Boutons */
.hero-btn {
  display: flex;
  align-items: center;
  gap: 20px;
  opacity: 0;
  transform: translateY(12px);
  transition: opacity .8s 1.15s, transform .8s 1.15s;
}
.hero-slide.active .hero-btn { opacity: 1; transform: none; }

.btn-primary {
  background: transparent;
  color: var(--ink);
  padding: 0;
  font-family: 'Cinzel', serif;
  font-size: .68rem;
  letter-spacing: 3.5px;
  text-transform: uppercase;
  text-decoration: none;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  position: relative;
  overflow: hidden;
}
/* Bouton principal avec remplissage doré */
.btn-primary-inner {
  background: var(--g1);
  color: var(--ink);
  padding: 16px 42px;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  position: relative;
  overflow: hidden;
  transition: color .4s;
}
.btn-primary-inner::before {
  content: '';
  position: absolute; inset: 0;
  background: #fff;
  transform: translateX(-101%);
  transition: transform .4s cubic-bezier(.77,0,.18,1);
}
.btn-primary-inner:hover::before { transform: translateX(0); }
.btn-primary-inner span { position: relative; z-index: 1; }
.btn-primary-inner i { position: relative; z-index: 1; font-size: .65rem; transition: transform .3s; }
.btn-primary-inner:hover i { transform: translateX(4px); }

/* Bouton ghost */
.btn-ghost {
  font-family: 'Cinzel', serif;
  font-size: .62rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: rgba(255,255,255,.4);
  text-decoration: none;
  display: flex; align-items: center; gap: 10px;
  transition: color .3s;
  position: relative;
  padding-bottom: 2px;
}
.btn-ghost::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0;
  width: 0; height: 1px;
  background: var(--g1);
  transition: width .35s cubic-bezier(.77,0,.18,1);
}
.btn-ghost:hover { color: var(--g2); }
.btn-ghost:hover::after { width: 100%; }
.btn-ghost i { font-size: .6rem; transition: transform .3s; }
.btn-ghost:hover i { transform: translateX(5px); }

/* ── PANEL IMAGE DROITE ── */
.hero-img-panel {
  position: absolute;
  right: 0; top: 0; bottom: 0;
  width: 42%;
  z-index: 3;
  overflow: hidden;
}
/* Masque dégradé vers la gauche */
.hero-img-panel::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to right, rgba(6,4,2,.9) 0%, transparent 40%);
  z-index: 2;
}
.hero-img-panel-img {
  width: 100%; height: 100%;
  object-fit: cover;
  filter: brightness(.65) saturate(.8);
  transform: scale(1.05);
  transition: transform 10s ease, opacity 1.6s;
  opacity: 0;
}
.hero-slide.active .hero-img-panel-img {
  transform: scale(1);
  opacity: 1;
}

/* Cadre doré sur l'image */
.hero-img-frame {
  position: absolute;
  top: 40px; right: 40px; bottom: 40px; left: 60px;
  border: 1px solid rgba(212,168,67,.2);
  z-index: 3; pointer-events: none;
  opacity: 0;
  animation: none;
}
.hero-slide.active .hero-img-frame {
  opacity: 1;
  transition: opacity .8s 1.4s;
}

/* ── INDICATEURS ── */
.hero-side {
  position: absolute;
  right: 44%; /* centré sur la séparation */
  top: 50%;
  transform: translateY(-50%) translateX(50%);
  z-index: 20;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}
.hero-progress { display: flex; flex-direction: column; gap: 6px; }
.hero-tick {
  width: 2px; height: 24px;
  background: rgba(255,255,255,.12);
  transition: background .4s, height .4s;
  cursor: pointer;
}
.hero-tick.active { background: var(--g1); height: 46px; }
.hero-counter {
  font-family: 'Cinzel', serif;
  font-size: .62rem;
  color: rgba(255,255,255,.28);
  letter-spacing: 2px;
  writing-mode: vertical-rl;
  transform: rotate(180deg);
}

/* ── BARRE DE PROGRESSION CINÉMATIQUE ── */
.hero-progress-bar {
  position: absolute;
  bottom: 0; left: 0;
  height: 2px;
  background: rgba(255,255,255,.06);
  width: 100%;
  z-index: 25;
}
.hero-progress-fill {
  height: 100%;
  background: linear-gradient(to right, var(--g3), var(--g1), var(--g2));
  width: 0%;
  transition: none;
}
.hero-progress-fill.running {
  width: 100%;
  transition: width 6.5s linear;
}

/* ── FLÈCHES ── */
.hero-arrows {
  position: absolute;
  bottom: 44px;
  left: 9%;
  z-index: 20;
  display: flex;
  gap: 2px;
}
.hero-arrows button {
  width: 48px; height: 48px;
  background: rgba(212,168,67,.08);
  border: 1px solid rgba(212,168,67,.2);
  color: rgba(255,255,255,.5);
  font-size: .85rem;
  cursor: pointer;
  transition: all .3s;
  display: flex; align-items: center; justify-content: center;
}
.hero-arrows button:hover {
  background: var(--g1);
  border-color: var(--g1);
  color: var(--ink);
}

/* ── SCROLL ── */
.hero-scroll {
  position: absolute;
  bottom: 44px;
  right: 9%;
  z-index: 20;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  color: rgba(255,255,255,.25);
  font-size: .56rem;
  letter-spacing: 4px;
  text-transform: uppercase;
}
.hero-scroll-bar {
  width: 1px; height: 55px;
  background: rgba(255,255,255,.08);
  position: relative; overflow: hidden;
}
.hero-scroll-bar::after {
  content: '';
  position: absolute;
  top: -100%; left: 0;
  width: 100%; height: 100%;
  background: var(--g1);
  animation: scrollDown 2.2s ease-in-out infinite;
}
@keyframes scrollDown {
  0%   { top:-100%; }
  50%  { top:100%; }
  100% { top:100%; opacity:0; }
}

/* Slide number côté droit bas */
.hero-slide-info {
  position: absolute;
  bottom: 44px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 20;
  text-align: center;
}
.hero-slide-title-mini {
  font-family: 'Cinzel', serif;
  font-size: .55rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: rgba(255,255,255,.25);
}

/* ═══════════════════════════════════════════════
   TICKER
═══════════════════════════════════════════════ */
.ticker {
  background: var(--g1);
  padding: 14px 0;
  overflow: hidden;
  white-space: nowrap;
  position: relative;
  z-index: 10;
}
.ticker-track {
  display: inline-flex;
  animation: ticker 22s linear infinite;
}
.ticker-item {
  display: inline-flex;
  align-items: center;
  gap: 16px;
  padding: 0 40px;
  font-family: 'Cinzel', serif;
  font-size: .65rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  color: var(--ink);
}
.ticker-dot { width: 4px; height: 4px; border-radius: 50%; background: var(--ink2); opacity: .4; }
@keyframes ticker { 0%{transform:translateX(0)} 100%{transform:translateX(-50%)} }

/* ═══════════════════════════════════════════════
   SECTION HEADER
═══════════════════════════════════════════════ */
.s-head { text-align: center; padding: 90px 20px 0; }
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
.s-rule-diamond::before, .s-rule-diamond::after {
  content:''; position:absolute;
  width:14px; height:1px; background:var(--g1); top:50%;
}
.s-rule-diamond::before { right:8px; }
.s-rule-diamond::after  { left:8px; }

/* ═══════════════════════════════════════════════
   CATÉGORIES
═══════════════════════════════════════════════ */
.cats { background: var(--ink); padding-bottom: 0; overflow: hidden; }
.cats .s-head { padding-top: 90px; }
.cats .s-head h2 { color: #fff; }
.cats .s-head h2 em { color: var(--g2); }
.cats .s-head-tag { color: var(--g2); }
.cats .s-rule-line { background: linear-gradient(90deg, transparent, var(--g2)); }
.cats .s-rule-line:last-child { background: linear-gradient(90deg, var(--g2), transparent); }
.cats .s-rule-diamond { background: var(--g2); }

.cats-rail {
  display: flex;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  cursor: grab;
}
.cats-rail:active { cursor: grabbing; }
.cats-rail::-webkit-scrollbar { display: none; }

.cat-card {
  position: relative;
  min-width: 380px;
  height: 520px;
  flex-shrink: 0;
  overflow: hidden;
  scroll-snap-align: start;
  cursor: pointer;
}
.cat-img {
  width:100%; height:100%;
  object-fit:cover;
  transition: transform 1s cubic-bezier(.25,.46,.45,.94), filter .6s;
  filter: brightness(.5) saturate(.7);
}
.cat-card:hover .cat-img { transform: scale(1.08); filter: brightness(.35) saturate(1.1); }

.cat-card::before {
  content:'';
  position:absolute;
  bottom:0; left:0;
  width:100%; height:3px;
  background:linear-gradient(90deg, transparent, var(--g1), transparent);
  transform:scaleX(0);
  transform-origin:left;
  transition:transform .5s cubic-bezier(.77,0,.18,1);
  z-index:5;
}
.cat-card:hover::before { transform:scaleX(1); }

.cat-num {
  position:absolute;
  top:24px; right:24px;
  font-family:'Cinzel',serif;
  font-size:5rem;
  color:rgba(255,255,255,.06);
  font-weight:900;
  line-height:1;
  z-index:2;
  transition:color .4s;
}
.cat-card:hover .cat-num { color:rgba(212,168,67,.12); }

.cat-overlay {
  position:absolute; inset:0;
  display:flex; flex-direction:column; justify-content:flex-end;
  padding:36px; z-index:3;
}
.cat-tag {
  font-family:'Cinzel',serif;
  font-size:.56rem; letter-spacing:4px; text-transform:uppercase;
  color:var(--g1); margin-bottom:10px;
  display:flex; align-items:center; gap:10px;
}
.cat-tag::before { content:''; width:24px; height:1px; background:var(--g1); }
.cat-name {
  font-family:'EB Garamond',serif;
  font-size:2.6rem; color:#fff; font-weight:400;
  line-height:1.05; margin-bottom:18px;
  transform:translateY(8px); transition:transform .4s;
}
.cat-card:hover .cat-name { transform:translateY(0); }
.cat-link {
  display:inline-flex; align-items:center; gap:0;
  width:44px; height:44px;
  border:1px solid rgba(212,168,67,.5);
  color:var(--g1); text-decoration:none;
  font-size:1rem; justify-content:center;
  transition:all .35s cubic-bezier(.77,0,.18,1);
  overflow:hidden; white-space:nowrap;
}
.cat-link span {
  font-family:'Cinzel',serif; font-size:.6rem;
  letter-spacing:2.5px; text-transform:uppercase;
  max-width:0; overflow:hidden;
  transition:max-width .4s .05s, padding .4s;
}
.cat-card:hover .cat-link { width:auto; padding:0 20px; background:var(--g1); color:var(--ink); border-color:var(--g1); gap:10px; }
.cat-card:hover .cat-link span { max-width:120px; padding-left:6px; }

.cats-scroll-hint {
  display:flex; align-items:center; justify-content:center;
  gap:14px; padding:22px; background:var(--ink);
}
.cats-hint-dot {
  width:5px; height:5px; border-radius:50%;
  background:rgba(255,255,255,.15); transition:all .3s; cursor:pointer;
}
.cats-hint-dot.active { background:var(--g1); transform:scale(1.4); }

/* ═══════════════════════════════════════════════
   ✅ NOUVEAU — PROMO BANNER
═══════════════════════════════════════════════ */
.promo-banner {
  background: var(--ink2);
  position: relative;
  overflow: hidden;
  padding: 0;
}
.promo-banner-inner {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 220px;
}
.promo-banner-left {
  background: var(--g1);
  padding: 48px 52px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
  overflow: hidden;
}
.promo-banner-left::before {
  content: '';
  position: absolute;
  right: -60px; top: -60px;
  width: 220px; height: 220px;
  border-radius: 50%;
  background: rgba(255,255,255,.08);
}
.promo-banner-left::after {
  content: '';
  position: absolute;
  right: 40px; bottom: -80px;
  width: 160px; height: 160px;
  border-radius: 50%;
  background: rgba(255,255,255,.05);
}
.promo-banner-eyebrow {
  font-family: 'Cinzel', serif;
  font-size: .55rem;
  letter-spacing: 4px;
  text-transform: uppercase;
  color: var(--ink);
  opacity: .7;
  margin-bottom: 10px;
}
.promo-banner-title {
  font-family: 'EB Garamond', serif;
  font-size: clamp(2rem, 3vw, 2.8rem);
  font-weight: 400;
  color: var(--ink);
  line-height: 1.1;
  margin-bottom: 20px;
  position: relative;
  z-index: 1;
}
.promo-banner-btn {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: var(--ink);
  color: var(--g1);
  padding: 12px 28px;
  font-family: 'Cinzel', serif;
  font-size: .6rem;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  text-decoration: none;
  transition: background .3s, color .3s;
  position: relative;
  z-index: 1;
  width: fit-content;
}
.promo-banner-btn:hover { background: var(--ink2); color: var(--g2); }

.promo-banner-right {
  padding: 48px 52px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 20px;
  border-left: 1px solid rgba(255,255,255,.04);
}
.promo-item {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 14px 0;
  border-bottom: 1px solid rgba(255,255,255,.04);
}
.promo-item:last-child { border-bottom: none; }
.promo-item-icon {
  width: 40px; height: 40px;
  border: 1px solid rgba(212,168,67,.25);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.promo-item-icon i { color: var(--g1); font-size: .9rem; }
.promo-item-text h4 {
  font-family: 'EB Garamond', serif;
  font-size: 1rem; color: rgba(255,255,255,.85);
  font-weight: 400; margin-bottom: 2px;
}
.promo-item-text p { font-size: .72rem; color: rgba(255,255,255,.3); }

/* ═══════════════════════════════════════════════
   PRODUITS
═══════════════════════════════════════════════ */
.prods { padding: 0; background: var(--smoke); }
.prods.dark { background: var(--ink2); }

.prods-header {
  display: flex;
  align-items: center;
  max-width: 1400px;
  margin: 0 auto;
  padding: 80px 40px 0;
}
.prods-header-text { flex: 1; }
.prods-header-text .s-head-tag { text-align: left; }
.prods-header-text h2 {
  font-family: 'EB Garamond', serif;
  font-size: clamp(2rem, 3.5vw, 3.2rem);
  font-weight: 400;
  line-height: 1.15;
  letter-spacing: 1px;
}
.prods-header-text h2 em { font-style:italic; color:var(--g3); }
.prods.dark .prods-header-text h2 { color: #fff; }
.prods.dark .prods-header-text h2 em { color: var(--g2); }
.prods.dark .s-head-tag { color: var(--g2); }
.prods-header-line {
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, var(--stone), transparent);
  margin-left: 40px;
}
.prods.dark .prods-header-line { background: linear-gradient(90deg, rgba(255,255,255,.08), transparent); }
.prods-see-all {
  font-family: 'Cinzel', serif;
  font-size: .58rem;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  color: var(--g1);
  text-decoration: none;
  display: flex; align-items: center; gap: 8px;
  transition: gap .3s;
  margin-left: 30px;
  white-space: nowrap;
}
.prods-see-all:hover { gap: 14px; }
.prods-see-all::after { content: '→'; }

.prods-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1px;
  max-width: 1400px;
  margin: 40px auto 0;
  padding: 0 40px;
  background: var(--stone);
  border-top: 1px solid var(--stone);
  border-bottom: 1px solid var(--stone);
}
.prods.dark .prods-grid {
  background: rgba(255,255,255,.05);
  border-color: rgba(255,255,255,.05);
}

/* ✅ Skeleton loader */
.prod-card {
  background: var(--smoke);
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform .4s cubic-bezier(.23,1,.32,1);
}
.prods.dark .prod-card { background: var(--ink2); }
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
.prod-badge.new   { background: var(--g1); color: var(--ink); }
.prod-badge.promo { background: var(--ink); color: var(--g1); }
.prod-badge.best  { background: transparent; border-bottom: 1px solid var(--g1); border-left: 1px solid var(--g1); color: var(--g1); }

.prod-img-wrap {
  position: relative;
  height: 280px;
  overflow: hidden;
  background: var(--stone);
  flex-shrink: 0;
}
.prods.dark .prod-img-wrap { background: rgba(255,255,255,.04); }

/* ✅ Skeleton animation */
.prod-img-wrap.loading::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(90deg, var(--stone) 25%, rgba(232,224,208,.5) 50%, var(--stone) 75%);
  background-size: 200% 100%;
  animation: skeleton 1.5s infinite;
  z-index: 2;
}
@keyframes skeleton { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

.prod-img-wrap img {
  width:100%; height:100%;
  object-fit:cover;
  transition: transform .9s cubic-bezier(.25,.46,.45,.94), opacity .4s;
  opacity: 0;
}
.prod-img-wrap img.loaded { opacity: 1; }
.prod-card:hover .prod-img-wrap img { transform: scale(1.1); }

.prod-img-wrap::after {
  content:'';
  position:absolute; inset:0;
  background:linear-gradient(to bottom, transparent 50%, rgba(212,168,67,.12) 100%);
  opacity:0; transition:opacity .4s;
}
.prod-card:hover .prod-img-wrap::after { opacity:1; }

.prod-actions-hover {
  position:absolute; bottom:0; left:0; right:0;
  display:flex;
  transform:translateY(101%);
  transition:transform .4s cubic-bezier(.77,0,.18,1);
  z-index:5;
}
.prod-card:hover .prod-actions-hover { transform:translateY(0); }

.btn-cart-inline {
  flex:1; background:var(--ink); border:none;
  color:#fff; font-family:'Cinzel',serif;
  font-size:.58rem; letter-spacing:2px; text-transform:uppercase;
  padding:14px 10px; cursor:pointer;
  transition:background .3s, color .3s;
  text-decoration:none;
  display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-cart-inline:hover { background:var(--g1); color:var(--ink); }

.btn-fav-inline {
  width:50px; background:rgba(13,10,6,.9);
  border:none; border-left:1px solid rgba(255,255,255,.08);
  color:rgba(255,255,255,.4); font-size:.95rem; cursor:pointer;
  transition:color .3s, background .3s;
  display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.btn-fav-inline:hover { color:var(--red); background:rgba(192,57,43,.15); }
.btn-fav-inline.active { color:var(--red); }
@keyframes heartPop { 0%{transform:scale(1)} 30%{transform:scale(1.5)} 60%{transform:scale(.9)} 100%{transform:scale(1)} }
.btn-fav-inline.pop i { animation:heartPop .45s ease; }

.prod-info {
  padding:22px 22px 24px; flex:1;
  display:flex; flex-direction:column;
  border-top:1px solid rgba(0,0,0,.05);
  position:relative;
}
.prods.dark .prod-info { border-top-color:rgba(255,255,255,.04); }
.prod-info::before {
  content:''; position:absolute;
  top:0; left:22px; right:22px; height:1px;
  background:linear-gradient(90deg, transparent, var(--g1), transparent);
  transform:scaleX(0);
  transition:transform .5s cubic-bezier(.77,0,.18,1);
}
.prod-card:hover .prod-info::before { transform:scaleX(1); }

.prod-brand { font-family:'Cinzel',serif; font-size:.52rem; letter-spacing:3px; text-transform:uppercase; color:var(--g1); margin-bottom:7px; }
.prods.dark .prod-brand { color:var(--g2); }
.prod-name { font-family:'EB Garamond',serif; font-size:1.1rem; font-weight:400; color:var(--ink); line-height:1.3; margin-bottom:7px; flex:1; }
.prods.dark .prod-name { color:rgba(255,255,255,.88); }
.prod-desc { font-size:.72rem; color:var(--muted); line-height:1.6; margin-bottom:14px; }
.prods.dark .prod-desc { color:rgba(255,255,255,.3); }
.prod-price { display:flex; align-items:baseline; gap:10px; margin-top:auto; }
.price-now { font-family:'EB Garamond',serif; font-size:1.3rem; font-weight:500; color:var(--ink); }
.prods.dark .price-now { color:var(--g2); }
.price-old { font-size:.8rem; color:#bbb; text-decoration:line-through; }

/* ═══════════════════════════════════════════════
   ✅ NOUVEAU — TÉMOIGNAGES
═══════════════════════════════════════════════ */
.testimonials {
  background: var(--smoke);
  padding-bottom: 80px;
  overflow: hidden;
}
.testimonials-track-wrap {
  overflow: hidden;
  position: relative;
  margin-top: 0;
}
.testimonials-track {
  display: flex;
  transition: transform .6s cubic-bezier(.77,0,.18,1);
}
.testi-card {
  min-width: calc(33.333% - 2px);
  padding: 44px 38px;
  background: #fff;
  border-right: 1px solid var(--stone);
  flex-shrink: 0;
  position: relative;
  transition: background .3s;
}
.testi-card:hover { background: #fffdf8; }
.testi-card::before {
  content: '\201C';
  position: absolute;
  top: 20px; left: 30px;
  font-family: 'EB Garamond', serif;
  font-size: 6rem;
  color: var(--g1);
  opacity: .12;
  line-height: 1;
}
.testi-stars {
  display: flex; gap: 4px;
  margin-bottom: 18px;
}
.testi-stars i { color: var(--g1); font-size: .75rem; }
.testi-text {
  font-family: 'EB Garamond', serif;
  font-size: 1.05rem;
  color: var(--ink);
  line-height: 1.75;
  margin-bottom: 26px;
  font-style: italic;
}
.testi-author {
  display: flex;
  align-items: center;
  gap: 14px;
}
.testi-avatar {
  width: 42px; height: 42px;
  border-radius: 50%;
  background: var(--g1);
  display: flex; align-items: center; justify-content: center;
  font-family: 'Cinzel', serif;
  font-size: .75rem;
  color: var(--ink);
  font-weight: 600;
  flex-shrink: 0;
}
.testi-author-info {}
.testi-author-name {
  font-family: 'Cinzel', serif;
  font-size: .62rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: var(--ink);
  margin-bottom: 2px;
}
.testi-author-sub { font-size: .7rem; color: var(--muted); }

/* Produit acheté */
.testi-product {
  position: absolute;
  top: 20px; right: 24px;
  font-family: 'Cinzel', serif;
  font-size: .5rem;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  color: var(--g1);
  background: rgba(212,168,67,.08);
  padding: 4px 10px;
  border: 1px solid rgba(212,168,67,.2);
}

.testi-nav {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  margin-top: 40px;
}
.testi-nav button {
  width: 42px; height: 42px;
  background: transparent;
  border: 1px solid var(--stone);
  color: var(--muted);
  cursor: pointer;
  transition: all .3s;
  display: flex; align-items: center; justify-content: center;
  font-size: .85rem;
}
.testi-nav button:hover { background: var(--g1); border-color: var(--g1); color: var(--ink); }
.testi-dots { display: flex; gap: 8px; }
.testi-dot {
  width: 6px; height: 6px;
  border-radius: 50%;
  background: var(--stone);
  cursor: pointer;
  transition: all .3s;
}
.testi-dot.active { background: var(--g1); transform: scale(1.3); }

/* ═══════════════════════════════════════════════
   ABOUT
═══════════════════════════════════════════════ */
.about { background: var(--smoke); position: relative; overflow: hidden; }
.about-banner {
  position: relative;
  height: 70vh; min-height: 500px;
  overflow: hidden;
}
.about-banner-img { width:100%; height:100%; object-fit:cover; filter:brightness(.3) saturate(.6); }
.about-banner-content {
  position: absolute; inset: 0;
  display: flex; align-items: center;
  padding: 0 8%; z-index: 2;
}
.about-banner-text { max-width: 520px; }
.about-eyebrow {
  font-family: 'Cinzel', serif;
  font-size: .58rem; letter-spacing: 5px; text-transform: uppercase;
  color: var(--g1);
  display: flex; align-items: center; gap: 14px;
  margin-bottom: 22px;
}
.about-eyebrow::before { content:''; width:35px; height:1px; background:var(--g1); }
.about-title {
  font-family: 'EB Garamond', serif;
  font-size: clamp(2.2rem, 4vw, 3.5rem);
  font-weight: 400; color: #fff;
  line-height: 1.2; margin-bottom: 22px; letter-spacing: 1px;
}
.about-title em { color: var(--g2); font-style: italic; }
.about-body { font-size:.88rem; color:rgba(255,255,255,.5); line-height:1.9; margin-bottom:34px; }
.about-link {
  display: inline-flex; align-items: center; gap: 12px;
  font-family: 'Cinzel', serif;
  font-size: .6rem; letter-spacing: 3px; text-transform: uppercase;
  color: #fff; text-decoration: none;
  border: 1px solid rgba(255,255,255,.3);
  padding: 13px 28px;
  transition: all .35s;
  position: relative; overflow: hidden;
}
.about-link::before {
  content:''; position:absolute; inset:0;
  background:var(--g1);
  transform:scaleX(0); transform-origin:left;
  transition:transform .35s cubic-bezier(.77,0,.18,1); z-index:-1;
}
.about-link:hover::before { transform:scaleX(1); }
.about-link:hover { border-color:var(--g1); color:var(--ink); }
.about-link::after { content:'→'; transition:transform .3s; }
.about-link:hover::after { transform:translateX(5px); }

/* ✅ Stats avec compteurs animés */
.about-stats {
  position: absolute;
  right: 8%; top: 50%;
  transform: translateY(-50%);
  z-index: 3;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.about-stat {
  background: rgba(13,10,6,.7);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(212,168,67,.2);
  border-left: 3px solid var(--g1);
  padding: 20px 32px;
  text-align: center;
  min-width: 160px;
  transition: background .3s;
}
.about-stat:hover { background: rgba(212,168,67,.1); }
.about-stat-num {
  font-family: 'Cinzel', serif;
  font-size: 2rem; color: var(--g1);
  display: block; line-height: 1; margin-bottom: 5px;
}
.about-stat-label { font-size:.6rem; letter-spacing:2.5px; text-transform:uppercase; color:rgba(255,255,255,.45); }

.about-feats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  background: var(--ink);
}
.about-feat {
  padding: 44px 32px;
  border-right: 1px solid rgba(255,255,255,.04);
  transition: background .4s;
  position: relative; overflow: hidden;
}
.about-feat:last-child { border-right: none; }
.about-feat:hover { background: rgba(212,168,67,.06); }
.about-feat::before {
  content:''; position:absolute;
  top:0; left:0; right:0; height:2px;
  background:linear-gradient(90deg, var(--g1), var(--g2));
  transform:scaleX(0); transform-origin:left;
  transition:transform .5s cubic-bezier(.77,0,.18,1);
}
.about-feat:hover::before { transform:scaleX(1); }
.about-feat-icon {
  width:48px; height:48px;
  border:1px solid rgba(212,168,67,.25); border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  margin-bottom:18px;
  transition:border-color .3s, background .3s;
}
.about-feat:hover .about-feat-icon { border-color:var(--g1); background:rgba(212,168,67,.08); }
.about-feat-icon i { font-size:1rem; color:var(--g1); }
.about-feat h4 { font-family:'EB Garamond',serif; font-size:1.05rem; color:rgba(255,255,255,.9); font-weight:400; margin-bottom:10px; }
.about-feat p { font-size:.75rem; color:rgba(255,255,255,.3); line-height:1.8; }

/* ═══════════════════════════════════════════════
   ✅ NOUVEAU — NEWSLETTER
═══════════════════════════════════════════════ */
.newsletter {
  background: var(--ink);
  padding: 80px 40px;
  position: relative;
  overflow: hidden;
}
.newsletter::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(212,168,67,.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(212,168,67,.04) 1px, transparent 1px);
  background-size: 40px 40px;
}
/* Cercle décoratif */
.newsletter::after {
  content: '';
  position: absolute;
  right: -200px; top: 50%;
  transform: translateY(-50%);
  width: 600px; height: 600px;
  border-radius: 50%;
  border: 1px solid rgba(212,168,67,.06);
}
.newsletter-inner {
  max-width: 640px;
  margin: 0 auto;
  text-align: center;
  position: relative;
  z-index: 2;
}
.newsletter-tag {
  font-family: 'Cinzel', serif;
  font-size: .55rem;
  letter-spacing: 5px;
  text-transform: uppercase;
  color: var(--g1);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  margin-bottom: 18px;
}
.newsletter-tag::before,
.newsletter-tag::after { content:''; width:30px; height:1px; background:var(--g1); opacity:.5; }
.newsletter-title {
  font-family: 'EB Garamond', serif;
  font-size: clamp(2rem, 3.5vw, 3rem);
  font-weight: 400;
  color: #fff;
  line-height: 1.2;
  margin-bottom: 14px;
}
.newsletter-title em { color: var(--g2); font-style: italic; }
.newsletter-sub {
  font-size: .82rem;
  color: rgba(255,255,255,.4);
  line-height: 1.7;
  margin-bottom: 36px;
}
.newsletter-form {
  display: flex;
  gap: 0;
  max-width: 480px;
  margin: 0 auto 16px;
}
.newsletter-input {
  flex: 1;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.1);
  border-right: none;
  padding: 14px 20px;
  font-family: 'Didact Gothic', sans-serif;
  font-size: .85rem;
  color: #fff;
  outline: none;
  transition: border-color .3s, background .3s;
}
.newsletter-input::placeholder { color: rgba(255,255,255,.25); }
.newsletter-input:focus {
  border-color: var(--g1);
  background: rgba(212,168,67,.05);
}
.newsletter-btn {
  background: var(--g1);
  border: none;
  padding: 14px 28px;
  font-family: 'Cinzel', serif;
  font-size: .6rem;
  letter-spacing: 2.5px;
  text-transform: uppercase;
  color: var(--ink);
  cursor: pointer;
  transition: background .3s;
  white-space: nowrap;
  display: flex; align-items: center; gap: 8px;
}
.newsletter-btn:hover { background: var(--g2); }
.newsletter-note {
  font-size: .68rem;
  color: rgba(255,255,255,.2);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}
.newsletter-note i { color: var(--g1); font-size: .7rem; }

/* ═══════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════ */
@media (max-width:1100px) {
  .prods-grid { grid-template-columns: repeat(3,1fr); }
  .about-feats { grid-template-columns:1fr 1fr; }
  .promo-banner-inner { grid-template-columns:1fr; }
  .promo-banner-right { display:none; }
  .testi-card { min-width:100%; }
}
@media (max-width:768px) {
  .prods-grid { grid-template-columns: repeat(2,1fr); padding:0 20px; }
  .about-feats { grid-template-columns:1fr; }
  .hero-side { display:none; }
  .about-stats { display:none; }
  .newsletter-form { flex-direction:column; }
  .newsletter-input { border-right:1px solid rgba(255,255,255,.1); border-bottom:none; }
}
@media (max-width:480px) {
  .prods-grid { grid-template-columns:1fr; }
  .hero-content { left:5%; max-width:90%; }
  .newsletter { padding:60px 20px; }
}
</style>

<!-- CURSEUR -->
<div id="cursor"></div>
<div id="cursor-ring"></div>

<!-- ═══ HERO — LUXURY CARTIER ═══ -->
<section class="hero">

  <!-- Éléments décoratifs fixes -->
  <div class="hero-grain"></div>
  <div class="hero-vignette"></div>
  <div class="hero-deco"></div>
  <div class="hero-corner"></div>
  <div class="hero-corner-br"></div>

  <!-- Slides -->
  <div class="hero-slides">

    <!-- SLIDE 1 -->
    <div class="hero-slide active">
      <div class="hero-img-panel">
        <img class="hero-img-panel-img"
          src="https://www.goldmarket.fr/wp-content/uploads/2025/07/12f534e7thumbnail.jpeg" alt="">
        <div class="hero-img-frame"></div>
      </div>
      <div class="hero-content">
        <span class="hero-slide-num">01</span>
        <div class="hero-eyebrow">
          <div class="hero-eyebrow-line"></div>
          <div class="hero-eyebrow-diamond"></div>
          <span class="hero-eyebrow-text">Maison de Haute Joaillerie — Depuis 1920</span>
        </div>
        <h1 class="hero-title">
          <span class="hero-title-line">
            <span class="hero-title-word">L'Art</span>&nbsp;<span class="hero-title-word">de la</span>
          </span>
        </h1>
        <span class="hero-title-gold">
          <span class="hero-title-word">Joaillerie</span>
        </span>
        <div class="hero-title-rule">
          <div class="hero-title-rule-line"></div>
          <div class="hero-title-rule-dot"></div>
        </div>
        <p class="hero-sub">Bijoux d'exception façonnés avec passion<br>et savoir-faire ancestral depuis un siècle.</p>
        <div class="hero-btn">
          <a href="pages/catalogue.php" class="btn-primary">
            <div class="btn-primary-inner">
              <span>Découvrir</span>
              <i class="fas fa-arrow-right"></i>
            </div>
          </a>
          <a href="pages/catalogue.php?filter=new" class="btn-ghost">
            Nouveautés <i class="fas fa-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- SLIDE 2 -->
    <div class="hero-slide">
      <div class="hero-img-panel">
        <img class="hero-img-panel-img"
          src="https://www.goldmarket.fr/wp-content/uploads/2025/09/4d549417thumbnail.jpeg" alt="">
        <div class="hero-img-frame"></div>
      </div>
      <div class="hero-content">
        <span class="hero-slide-num">02</span>
        <div class="hero-eyebrow">
          <div class="hero-eyebrow-line"></div>
          <div class="hero-eyebrow-diamond"></div>
          <span class="hero-eyebrow-text">Nouvelles Créations 2025</span>
        </div>
        <h1 class="hero-title">
          <span class="hero-title-line">
            <span class="hero-title-word">Nouveautés</span>
          </span>
        </h1>
        <span class="hero-title-gold">
          <span class="hero-title-word">Collection 2025</span>
        </span>
        <div class="hero-title-rule">
          <div class="hero-title-rule-line"></div>
          <div class="hero-title-rule-dot"></div>
        </div>
        <p class="hero-sub">Nos dernières pièces en or, diamants<br>et pierres précieuses rares.</p>
        <div class="hero-btn">
          <a href="pages/catalogue.php?filter=new" class="btn-primary">
            <div class="btn-primary-inner">
              <span>Voir la collection</span>
              <i class="fas fa-arrow-right"></i>
            </div>
          </a>
        </div>
      </div>
    </div>

    <!-- SLIDE 3 -->
    <div class="hero-slide">
      <div class="hero-img-panel">
        <img class="hero-img-panel-img"
          src="https://www.goldmarket.fr/wp-content/uploads/2024/11/03f5d621thumbnail-1024x512.jpeg" alt="">
        <div class="hero-img-frame"></div>
      </div>
      <div class="hero-content">
        <span class="hero-slide-num">03</span>
        <div class="hero-eyebrow">
          <div class="hero-eyebrow-line"></div>
          <div class="hero-eyebrow-diamond"></div>
          <span class="hero-eyebrow-text">Offres Exclusives — Durée limitée</span>
        </div>
        <h1 class="hero-title">
          <span class="hero-title-line">
            <span class="hero-title-word">Promotions</span>
          </span>
        </h1>
        <span class="hero-title-gold">
          <span class="hero-title-word">Jusqu'à −30%</span>
        </span>
        <div class="hero-title-rule">
          <div class="hero-title-rule-line"></div>
          <div class="hero-title-rule-dot"></div>
        </div>
        <p class="hero-sub">Sur nos pièces iconiques sélectionnées.<br>Offre valable jusqu'à épuisement des stocks.</p>
        <div class="hero-btn">
          <a href="pages/catalogue.php?filter=promo" class="btn-primary">
            <div class="btn-primary-inner">
              <span>En profiter</span>
              <i class="fas fa-arrow-right"></i>
            </div>
          </a>
        </div>
      </div>
    </div>

  </div><!-- /hero-slides -->

  <!-- Indicateurs verticaux -->
  <div class="hero-side">
    <div class="hero-progress">
      <div class="hero-tick active" data-i="0"></div>
      <div class="hero-tick" data-i="1"></div>
      <div class="hero-tick" data-i="2"></div>
    </div>
    <div class="hero-counter" id="hCounter">01 / 03</div>
  </div>

  <!-- Flèches -->
  <div class="hero-arrows">
    <button id="hPrev" aria-label="Précédent"><i class="fas fa-chevron-left"></i></button>
    <button id="hNext" aria-label="Suivant"><i class="fas fa-chevron-right"></i></button>
  </div>

  <!-- Scroll indicator droite -->
  <div class="hero-scroll">
    <div class="hero-scroll-bar"></div>
    <span>Scroll</span>
  </div>

  <!-- Barre de progression cinématique -->
  <div class="hero-progress-bar">
    <div class="hero-progress-fill" id="hProgressFill"></div>
  </div>

</section>

<!-- ═══ TICKER ═══ -->
<div class="ticker">
  <div class="ticker-track">
    <?php $items = ['Haute Joaillerie','Cartier','Tiffany & Co.','Van Cleef & Arpels','Bulgari','Chopard','Piaget','Harry Winston','Or 18 Carats','Diamants GIA','Livraison Offerte dès 150€','Retour 30j Gratuit']; ?>
    <?php for($t=0;$t<2;$t++): foreach($items as $it): ?>
    <span class="ticker-item"><?=$it?><span class="ticker-dot"></span></span>
    <?php endforeach; endfor; ?>
  </div>
</div>

<!-- ═══ CATÉGORIES ═══ -->
<section class="cats">
  <div class="s-head" data-aos="fade-up">
    <span class="s-head-tag">Nos Univers</span>
    <h2>Nos <em>Collections</em></h2>
    <div class="s-rule"><div class="s-rule-line"></div><div class="s-rule-diamond"></div><div class="s-rule-line"></div></div>
  </div>
  <div class="cats-rail" id="catsRail">
    <div class="cat-card">
      <span class="cat-num">01</span>
      <img class="cat-img" src="https://sn.jumia.is/unsafe/fit-in/500x500/filters:fill(white)/product/45/308121/1.jpg?8252" alt="Pour Elle">
      <div class="cat-overlay">
        <span class="cat-tag">Collection Femme</span>
        <h3 class="cat-name">Pour Elle</h3>
        <a href="pages/catalogue.php?category=Femme" class="cat-link"><i class="fas fa-arrow-right"></i><span>Découvrir</span></a>
      </div>
    </div>
    <div class="cat-card">
      <span class="cat-num">02</span>
      <img class="cat-img" src="https://ci.jumia.is/unsafe/fit-in/500x500/filters:fill(white)/product/99/749662/1.jpg?6877" alt="Pour Lui">
      <div class="cat-overlay">
        <span class="cat-tag">Collection Homme</span>
        <h3 class="cat-name">Pour Lui</h3>
        <a href="pages/catalogue.php?category=Homme" class="cat-link"><i class="fas fa-arrow-right"></i><span>Découvrir</span></a>
      </div>
    </div>
    <div class="cat-card">
      <span class="cat-num">03</span>
      <img class="cat-img" src="https://m.media-amazon.com/images/I/71naqPXNxXL._AC_UY1000_.jpg" alt="Unisexe">
      <div class="cat-overlay">
        <span class="cat-tag">Collection Unisexe</span>
        <h3 class="cat-name">Unisexe</h3>
        <a href="pages/catalogue.php?category=Unisexe" class="cat-link"><i class="fas fa-arrow-right"></i><span>Découvrir</span></a>
      </div>
    </div>
    <div class="cat-card">
      <span class="cat-num">04</span>
      <img class="cat-img" src="https://www.vancleefarpels.com/content/dam/rcq/vca/18/93/76/7/1893767.png" alt="Cadeaux">
      <div class="cat-overlay">
        <span class="cat-tag">Idées Cadeaux</span>
        <h3 class="cat-name">Cadeaux</h3>
        <a href="pages/cadeaux.php" class="cat-link"><i class="fas fa-arrow-right"></i><span>Découvrir</span></a>
      </div>
    </div>
  </div>
  <div class="cats-scroll-hint">
    <div class="cats-hint-dot active" data-ci="0"></div>
    <div class="cats-hint-dot" data-ci="1"></div>
    <div class="cats-hint-dot" data-ci="2"></div>
    <div class="cats-hint-dot" data-ci="3"></div>
  </div>
</section>

<!-- ═══ NOUVEAUTÉS ═══ -->
<section class="prods">
  <div class="prods-header" data-aos="fade-up">
    <div class="prods-header-text">
      <span class="s-head-tag">Arrivages</span>
      <h2>Nos <em>Nouveautés</em></h2>
    </div>
    <div class="prods-header-line"></div>
    <a href="pages/catalogue.php" class="prods-see-all">Tout voir</a>
  </div>
  <div class="prods-grid">
  <?php
  $nouveautes=[
    ['id'=>1,'badge'=>'new','bt'=>'Nouveau','brand'=>'CARTIER','name'=>'Trinity Ring','desc'=>'Bague trois ors entrelacés, symbole d\'amour depuis 1924','price'=>'1 450 €','old'=>'','img'=>'https://img.lemde.fr/2024/02/02/331/0/1145/763/1440/960/60/0/a2ed0ca_321473-3358008.jpg'],
    ['id'=>2,'badge'=>'promo','bt'=>'-10%','brand'=>'TIFFANY & CO.','name'=>'HardWear Bracelet','desc'=>'Maillons hexagonaux en or jaune 18K – force new-yorkaise','price'=>'729 €','old'=>'810 €','img'=>'https://media.tiffany.com/is/image/tco/74754627_BLT_MAIN1X1'],
    ['id'=>3,'badge'=>'','bt'=>'','brand'=>'VAN CLEEF & ARPELS','name'=>'Frivole Ring','desc'=>'Or jaune et diamants taille poire – légèreté absolue','price'=>'1 800 €','old'=>'','img'=>'https://www.vancleefarpels.com/content/dam/rcq/vca/18/93/76/7/1893767.png'],
    ['id'=>4,'badge'=>'best','bt'=>'Best-seller','brand'=>'BULGARI','name'=>'Divas\' Dream Necklace','desc'=>'Éventail or rose et diamants – glamour romain','price'=>'920 €','old'=>'','img'=>'https://i.etsystatic.com/59181605/r/il/15b365/7256284126/il_fullxfull.7256284126_d9m1.jpg'],
    ['id'=>5,'badge'=>'','bt'=>'','brand'=>'CHOPARD','name'=>'Imperiale Ring','desc'=>'Or blanc, diamants et onyx – inspiration impériale','price'=>'1 500 €','old'=>'','img'=>'https://www.lepage.fr/91691-zoom_default/bague-chopard-ice-cube-en-or-rose-et-diamants.jpg'],
    ['id'=>6,'badge'=>'promo','bt'=>'-20%','brand'=>'PIAGET','name'=>'Limelight Gala Ring','desc'=>'Or rose et diamants baguette – sophistication festive','price'=>'576 €','old'=>'720 €','img'=>'https://img.piaget.com/cards-row-split-3/d1fefdd704368d2b986c2df4dda800a64a4d5f5f.jpg'],
  ];
  foreach($nouveautes as $i=>$p): $fav=in_array($p['id'],$ids_favoris); ?>
    <div class="prod-card" data-aos="fade-up" data-aos-delay="<?=($i*60)?>">
      <?php if($p['badge']): ?><span class="prod-badge <?=$p['badge']?>"><?=$p['bt']?></span><?php endif; ?>
      <div class="prod-img-wrap loading">
        <img src="<?=$p['img']?>" alt="<?=$p['name']?>" loading="lazy" onload="this.classList.add('loaded');this.parentElement.classList.remove('loading')">
        <div class="prod-actions-hover">
          <?php if(isLoggedIn()): ?>
            <form method="POST" action="pages/ajouter_au_panier.php" style="flex:1;display:flex;">
              <input type="hidden" name="produit_id" value="<?=$p['id']?>">
              <input type="hidden" name="quantite" value="1">
              <input type="hidden" name="redirect_url" value="pages/panier.php">
              <button type="submit" class="btn-cart-inline"><i class="fas fa-cart-plus"></i> Ajouter</button>
            </form>
          <?php else: ?>
            <a href="pages/connexion.php" class="btn-cart-inline"><i class="fas fa-lock"></i> Connexion</a>
          <?php endif; ?>
          <button class="btn-fav-inline <?=$fav?'active':''?>" data-id="<?=$p['id']?>" title="Favoris">
            <i class="<?=$fav?'fas':'far'?> fa-heart"></i>
          </button>
        </div>
      </div>
      <div class="prod-info">
        <div class="prod-brand"><?=$p['brand']?></div>
        <h3 class="prod-name"><?=$p['name']?></h3>
        <p class="prod-desc"><?=$p['desc']?></p>
        <div class="prod-price">
          <?php if($p['old']): ?><span class="price-old"><?=$p['old']?></span><?php endif; ?>
          <span class="price-now"><?=$p['price']?></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
</section>

<!-- ═══ ✅ PROMO BANNER ═══ -->
<div class="promo-banner" data-aos="fade-up">
  <div class="promo-banner-inner">
    <div class="promo-banner-left">
      <div class="promo-eyebrow">Offre spéciale</div>
      <div class="promo-banner-title">Jusqu'à −30%<br>sur les Best-sellers</div>
      <a href="pages/catalogue.php?filter=promo" class="promo-banner-btn">
        <i class="fas fa-tag"></i> Voir les offres
      </a>
    </div>
    <div class="promo-banner-right">
      <div class="promo-item">
        <div class="promo-item-icon"><i class="fas fa-shipping-fast"></i></div>
        <div class="promo-item-text">
          <h4>Livraison offerte dès 150€</h4>
          <p>Expédition sécurisée sous 24–48h</p>
        </div>
      </div>
      <div class="promo-item">
        <div class="promo-item-icon"><i class="fas fa-undo"></i></div>
        <div class="promo-item-text">
          <h4>Retour gratuit 30 jours</h4>
          <p>Satisfait ou remboursé sans condition</p>
        </div>
      </div>
      <div class="promo-item">
        <div class="promo-item-icon"><i class="fas fa-lock"></i></div>
        <div class="promo-item-text">
          <h4>Paiement 100% sécurisé</h4>
          <p>SSL, Visa, Mastercard, PayPal</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ BEST-SELLERS ═══ -->
<section class="prods dark">
  <div class="prods-header" data-aos="fade-up">
    <div class="prods-header-text">
      <span class="s-head-tag">Les Plus Aimés</span>
      <h2>Nos <em>Best-sellers</em></h2>
    </div>
    <div class="prods-header-line"></div>
    <a href="pages/catalogue.php?filter=bestseller" class="prods-see-all" style="color:var(--g2)">Tout voir</a>
  </div>
  <div class="prods-grid">
  <?php
  $bests=[
    ['id'=>7,'badge'=>'best','bt'=>'Best-seller','brand'=>'CARTIER','name'=>'Love Bracelet','desc'=>'Or jaune 18K avec vis signature – amour éternel','price'=>'1 200 €','old'=>'','img'=>'https://www.bijouxbaume.com/upload/image/bracelet-cartier-love-or-jaune-18-carats-4-diamants-diametre-17-p-image-143430-grande.jpg'],
    ['id'=>8,'badge'=>'','bt'=>'','brand'=>'TIFFANY & CO.','name'=>'T1 Ring','desc'=>'Or jaune 18K et diamants – architecture new-yorkaise','price'=>'1 625 €','old'=>'','img'=>'https://media.tiffany.com/is/image/tco/67795113_RG_MAIN1X1?hei=2000&wid=2000&fmt=webp'],
    ['id'=>9,'badge'=>'best','bt'=>'Best-seller','brand'=>'VAN CLEEF & ARPELS','name'=>'Vintage Alhambra','desc'=>'Trèfle en or 18K et nacre – porte-bonheur intemporel','price'=>'800 €','old'=>'','img'=>'https://dandelion-antiques.co.uk/cdn/shop/files/photo_2024-12-0510.08.53.jpg?v=1734355598&width=1080'],
    ['id'=>10,'badge'=>'','bt'=>'','brand'=>'BULGARI','name'=>'Serpenti Viper Bracelet','desc'=>'Serpent en or et diamants – sensualité italienne','price'=>'1 500 €','old'=>'','img'=>'https://www.mytheresa.com/media/1094/1238/100/f8/P01030224.jpg'],
    ['id'=>11,'badge'=>'promo','bt'=>'-50%','brand'=>'HARRY WINSTON','name'=>'Cluster Diamond Ring','desc'=>'Cluster diamants en platine – éclat pur','price'=>'250 €','old'=>'500 €','img'=>'https://img.fril.jp/img/759856928/l/2566399238.jpg'],
  ];
  foreach($bests as $i=>$p): $fav=in_array($p['id'],$ids_favoris); ?>
    <div class="prod-card" data-aos="fade-up" data-aos-delay="<?=($i*60)?>">
      <?php if($p['badge']): ?><span class="prod-badge <?=$p['badge']?>"><?=$p['bt']?></span><?php endif; ?>
      <div class="prod-img-wrap loading">
        <img src="<?=$p['img']?>" alt="<?=$p['name']?>" loading="lazy" onload="this.classList.add('loaded');this.parentElement.classList.remove('loading')">
        <div class="prod-actions-hover">
          <?php if(isLoggedIn()): ?>
            <form method="POST" action="pages/ajouter_au_panier.php" style="flex:1;display:flex;">
              <input type="hidden" name="produit_id" value="<?=$p['id']?>">
              <input type="hidden" name="quantite" value="1">
              <input type="hidden" name="redirect_url" value="pages/panier.php">
              <button type="submit" class="btn-cart-inline"><i class="fas fa-cart-plus"></i> Ajouter</button>
            </form>
          <?php else: ?>
            <a href="pages/connexion.php" class="btn-cart-inline"><i class="fas fa-lock"></i> Connexion</a>
          <?php endif; ?>
          <button class="btn-fav-inline <?=$fav?'active':''?>" data-id="<?=$p['id']?>" title="Favoris">
            <i class="<?=$fav?'fas':'far'?> fa-heart"></i>
          </button>
        </div>
      </div>
      <div class="prod-info">
        <div class="prod-brand"><?=$p['brand']?></div>
        <h3 class="prod-name"><?=$p['name']?></h3>
        <p class="prod-desc"><?=$p['desc']?></p>
        <div class="prod-price">
          <?php if($p['old']): ?><span class="price-old"><?=$p['old']?></span><?php endif; ?>
          <span class="price-now"><?=$p['price']?></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
</section>

<!-- ═══ ✅ TÉMOIGNAGES ═══ -->
<section class="testimonials">
  <div class="s-head" data-aos="fade-up">
    <span class="s-head-tag">Avis Clients</span>
    <h2>Ils nous font <em>confiance</em></h2>
    <div class="s-rule"><div class="s-rule-line"></div><div class="s-rule-diamond"></div><div class="s-rule-line"></div></div>
  </div>
  <div class="testimonials-track-wrap" data-aos="fade-up" data-aos-delay="100">
    <div class="testimonials-track" id="testiTrack">

      <div class="testi-card">
        <span class="testi-product">Love Bracelet — Cartier</span>
        <div class="testi-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
        </div>
        <p class="testi-text">Un bijou absolument magnifique, la qualité est irréprochable. La livraison était soignée dans un écrin superbe. Je recommande Lumoura les yeux fermés !</p>
        <div class="testi-author">
          <div class="testi-avatar">SC</div>
          <div class="testi-author-info">
            <div class="testi-author-name">Sophie C.</div>
            <div class="testi-author-sub">Achat vérifié · Paris</div>
          </div>
        </div>
      </div>

      <div class="testi-card">
        <span class="testi-product">Vintage Alhambra — VCA</span>
        <div class="testi-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
        </div>
        <p class="testi-text">Cadeau parfait pour l'anniversaire de ma femme. Elle était sans voix en ouvrant l'écrin. Le collier correspond exactement aux photos, encore plus beau en vrai.</p>
        <div class="testi-author">
          <div class="testi-avatar">TM</div>
          <div class="testi-author-info">
            <div class="testi-author-name">Thomas M.</div>
            <div class="testi-author-sub">Achat vérifié · Lyon</div>
          </div>
        </div>
      </div>

      <div class="testi-card">
        <span class="testi-product">T1 Ring — Tiffany & Co.</span>
        <div class="testi-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
        </div>
        <p class="testi-text">Service client réactif et produit conforme. La bague est d'une finition exceptionnelle. Je suis cliente depuis 3 ans et Lumoura ne déçoit jamais.</p>
        <div class="testi-author">
          <div class="testi-avatar">AB</div>
          <div class="testi-author-info">
            <div class="testi-author-name">Amélie B.</div>
            <div class="testi-author-sub">Achat vérifié · Bordeaux</div>
          </div>
        </div>
      </div>

      <div class="testi-card">
        <span class="testi-product">Serpenti — Bulgari</span>
        <div class="testi-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
        </div>
        <p class="testi-text">Livraison ultra rapide en 24h ! Le bracelet est splendide, les photos ne rendent pas justice à l'éclat réel. Emballage luxueux, tout est parfait.</p>
        <div class="testi-author">
          <div class="testi-avatar">LR</div>
          <div class="testi-author-info">
            <div class="testi-author-name">Lucas R.</div>
            <div class="testi-author-sub">Achat vérifié · Marseille</div>
          </div>
        </div>
      </div>

      <div class="testi-card">
        <span class="testi-product">Trinity Ring — Cartier</span>
        <div class="testi-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
        </div>
        <p class="testi-text">Site magnifique, expérience d'achat fluide et sécurisée. La bague Trinity est un chef-d'œuvre. Je reviendrai certainement pour mes prochains cadeaux.</p>
        <div class="testi-author">
          <div class="testi-avatar">CF</div>
          <div class="testi-author-info">
            <div class="testi-author-name">Camille F.</div>
            <div class="testi-author-sub">Achat vérifié · Nantes</div>
          </div>
        </div>
      </div>

      <div class="testi-card">
        <span class="testi-product">Divas' Dream — Bulgari</span>
        <div class="testi-stars">
          <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
        </div>
        <p class="testi-text">Qualité premium, équipe disponible et à l'écoute. J'ai eu un doute sur ma taille de bague et ils m'ont guidée avec beaucoup de gentillesse. Merci Lumoura !</p>
        <div class="testi-author">
          <div class="testi-avatar">MK</div>
          <div class="testi-author-info">
            <div class="testi-author-name">Marie K.</div>
            <div class="testi-author-sub">Achat vérifié · Strasbourg</div>
          </div>
        </div>
      </div>

    </div>
  </div>
  <div class="testi-nav">
    <button id="testiPrev"><i class="fas fa-chevron-left"></i></button>
    <div class="testi-dots" id="testiDots">
      <div class="testi-dot active"></div>
      <div class="testi-dot"></div>
    </div>
    <button id="testiNext"><i class="fas fa-chevron-right"></i></button>
  </div>
</section>

<!-- ═══ ABOUT ═══ -->
<section class="about">
  <div class="about-banner">
    <img class="about-banner-img" src="https://www.goldmarket.fr/wp-content/uploads/2025/07/12f534e7thumbnail.jpeg" alt="Lumoura">
    <div class="about-banner-content">
      <div class="about-banner-text" data-aos="fade-right">
        <div class="about-eyebrow">Notre Histoire</div>
        <h2 class="about-title">L'Histoire de<br><em>Lumoura</em></h2>
        <p class="about-body">Depuis 1920, Lumoura façonne des bijoux d'exception qui traversent les époques. Chaque pièce est une œuvre d'art alliant tradition artisanale et design contemporain.</p>
        <a href="pages/catalogue.php" class="about-link">Voir toute la collection</a>
      </div>
    </div>
    <div class="about-stats" data-aos="fade-left" data-aos-delay="200">
      <div class="about-stat"><span class="about-stat-num" data-count="1920" data-prefix="">1920</span><span class="about-stat-label">Fondée en</span></div>
      <div class="about-stat"><span class="about-stat-num" data-count="500" data-suffix="+">500+</span><span class="about-stat-label">Créations</span></div>
      <div class="about-stat"><span class="about-stat-num" data-count="25" data-suffix="+">25+</span><span class="about-stat-label">Maisons</span></div>
      <div class="about-stat"><span class="about-stat-num" data-count="30" data-suffix="j">30j</span><span class="about-stat-label">Retour gratuit</span></div>
    </div>
  </div>
  <div class="about-feats" data-aos="fade-up">
    <div class="about-feat">
      <div class="about-feat-icon"><i class="fas fa-gem"></i></div>
      <h4>Pierres d'Exception</h4>
      <p>Sélectionnées pour leur pureté et leur éclat incomparable dans le monde entier</p>
    </div>
    <div class="about-feat">
      <div class="about-feat-icon"><i class="fas fa-hand-sparkles"></i></div>
      <h4>Savoir-faire Artisanal</h4>
      <p>Chaque bijou façonné à la main par nos maîtres joailliers depuis 1920</p>
    </div>
    <div class="about-feat">
      <div class="about-feat-icon"><i class="fas fa-shipping-fast"></i></div>
      <h4>Livraison Soignée</h4>
      <p>Expédition sécurisée et assurée sous 24 à 48 heures partout en France</p>
    </div>
    <div class="about-feat">
      <div class="about-feat-icon"><i class="fas fa-undo"></i></div>
      <h4>Retour 30 Jours</h4>
      <p>Satisfaction garantie ou remboursement complet sans aucune condition</p>
    </div>
  </div>
</section>

<!-- ═══ ✅ NEWSLETTER ═══ -->
<section class="newsletter" data-aos="fade-up">
  <div class="newsletter-inner">
    <div class="newsletter-tag">Exclusivités</div>
    <h2 class="newsletter-title">Restez dans<br>l'<em>univers Lumoura</em></h2>
    <p class="newsletter-sub">Recevez en avant-première nos nouvelles collections, offres exclusives et inspirations joaillerie directement dans votre boîte mail.</p>
    <div class="newsletter-form">
      <input type="email" class="newsletter-input" placeholder="votre@email.com" id="newsletterEmail">
      <button class="newsletter-btn" onclick="subscribeNewsletter()">
        <i class="fas fa-paper-plane"></i> S'inscrire
      </button>
    </div>
    <p class="newsletter-note"><i class="fas fa-lock"></i> Aucun spam — Désinscription en 1 clic</p>
  </div>
</section>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({ duration:900, once:true, easing:'ease-out-quart', offset:60 });

/* ── CURSEUR ── */
const cur=document.getElementById('cursor'), ring=document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{ mx=e.clientX; my=e.clientY; cur.style.left=mx+'px'; cur.style.top=my+'px'; });
(function animRing(){ rx+=(mx-rx)*.12; ry+=(my-ry)*.12; ring.style.left=rx+'px'; ring.style.top=ry+'px'; requestAnimationFrame(animRing); })();
document.querySelectorAll('a,button,.cat-card,.prod-card').forEach(el=>{
  el.addEventListener('mouseenter',()=>document.body.classList.add('hovering'));
  el.addEventListener('mouseleave',()=>document.body.classList.remove('hovering'));
});

/* ── HERO LUXURY ── */
const slides  = document.querySelectorAll('.hero-slide');
const ticks   = document.querySelectorAll('.hero-tick');
const counter = document.getElementById('hCounter');
const fill    = document.getElementById('hProgressFill');
const nums    = ['01','02','03'];
let curH = 0, heroTimer;

function heroGo(n) {
  // Reset sortante
  slides[curH].classList.remove('active');
  ticks[curH].classList.remove('active');

  // Barre de progression — reset
  fill.classList.remove('running');
  fill.style.transition = 'none';
  fill.style.width = '0%';

  curH = (n + slides.length) % slides.length;

  slides[curH].classList.add('active');
  ticks[curH].classList.add('active');
  counter.textContent = nums[curH] + ' / 03';

  // Relancer la barre après micro-délai
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      fill.classList.add('running');
    });
  });
}

function heroStart() {
  fill.classList.add('running');
  heroTimer = setInterval(() => heroGo(curH + 1), 6500);
}

document.getElementById('hPrev').onclick = () => {
  clearInterval(heroTimer); heroGo(curH - 1); heroStart();
};
document.getElementById('hNext').onclick = () => {
  clearInterval(heroTimer); heroGo(curH + 1); heroStart();
};
ticks.forEach((t, i) => t.onclick = () => {
  clearInterval(heroTimer); heroGo(i); heroStart();
});
heroStart();

/* ── RAIL CATÉGORIES ── */
const rail=document.getElementById('catsRail'), hintDots=document.querySelectorAll('.cats-hint-dot');
let isDown=false, startX, scrollLeft;
rail.addEventListener('mousedown',e=>{ isDown=true; startX=e.pageX-rail.offsetLeft; scrollLeft=rail.scrollLeft; });
rail.addEventListener('mouseleave',()=>isDown=false);
rail.addEventListener('mouseup',()=>isDown=false);
rail.addEventListener('mousemove',e=>{ if(!isDown) return; e.preventDefault(); rail.scrollLeft=scrollLeft-(e.pageX-rail.offsetLeft-startX)*1.5; });
rail.addEventListener('scroll',()=>{ const idx=Math.round(rail.scrollLeft/380); hintDots.forEach((d,i)=>d.classList.toggle('active',i===idx)); });
hintDots.forEach((d,i)=>d.addEventListener('click',()=>rail.scrollTo({left:i*380,behavior:'smooth'})));

/* ── PARALLAXE ── */
window.addEventListener('scroll',()=>{
  const y=window.scrollY;
  document.querySelectorAll('.hero-slide-bg').forEach(bg=>bg.style.transform=`scale(1.08) translateY(${y*.16}px)`);
});

/* ── ✅ TÉMOIGNAGES CAROUSEL ── */
const testiTrack=document.getElementById('testiTrack'), testiDots=document.querySelectorAll('.testi-dot');
const cardsPerView=()=>window.innerWidth<1100?1:3;
let testiIdx=0, totalGroups=()=>Math.ceil(6/cardsPerView());
function testiGo(n){
  testiIdx=Math.max(0,Math.min(n,totalGroups()-1));
  const cardW=testiTrack.children[0].offsetWidth;
  testiTrack.style.transform=`translateX(-${testiIdx*cardW*cardsPerView()}px)`;
  testiDots.forEach((d,i)=>d.classList.toggle('active',i===testiIdx));
}
document.getElementById('testiPrev').onclick=()=>testiGo(testiIdx-1);
document.getElementById('testiNext').onclick=()=>testiGo(testiIdx+1);
testiDots.forEach((d,i)=>d.addEventListener('click',()=>testiGo(i)));
// Auto-play témoignages
let testiTimer=setInterval(()=>testiGo(testiIdx+1>=totalGroups()?0:testiIdx+1),5000);
testiTrack.addEventListener('mouseenter',()=>clearInterval(testiTimer));
testiTrack.addEventListener('mouseleave',()=>testiTimer=setInterval(()=>testiGo(testiIdx+1>=totalGroups()?0:testiIdx+1),5000));

/* ── ✅ COMPTEURS ANIMÉS ── */
function animateCount(el){
  const target=parseInt(el.dataset.count);
  const suffix=el.dataset.suffix||'';
  const isYear=(target===1920);
  const start=isYear?1900:0;
  const duration=1800;
  const step=timestamp=>{
    if(!start_ts) start_ts=timestamp;
    const progress=Math.min((timestamp-start_ts)/duration,1);
    const ease=1-Math.pow(1-progress,3);
    el.textContent=Math.floor(start+(target-start)*ease)+(progress<1?'':suffix);
    if(progress<1) requestAnimationFrame(step);
  };
  let start_ts=null;
  requestAnimationFrame(step);
}
// Observer pour déclencher au scroll
const statsObs=new IntersectionObserver(entries=>{
  entries.forEach(e=>{
    if(e.isIntersecting){
      e.target.querySelectorAll('[data-count]').forEach(animateCount);
      statsObs.unobserve(e.target);
    }
  });
},{threshold:.3});
document.querySelectorAll('.about-stats').forEach(s=>statsObs.observe(s));

/* ── ✅ NEWSLETTER ── */
function subscribeNewsletter(){
  const input=document.getElementById('newsletterEmail');
  const email=input.value.trim();
  if(!email || !email.includes('@')){
    input.style.borderColor='#C0392B';
    setTimeout(()=>input.style.borderColor='',1500);
    return;
  }
  const btn=input.nextElementSibling;
  btn.innerHTML='<i class="fas fa-check"></i> Inscrit !';
  btn.style.background='#2ecc71';
  input.value='';
  setTimeout(()=>{ btn.innerHTML='<i class="fas fa-paper-plane"></i> S\'inscrire'; btn.style.background=''; },3000);
}
document.getElementById('newsletterEmail').addEventListener('keydown',e=>{ if(e.key==='Enter') subscribeNewsletter(); });

/* ── FAVORIS AJAX ── */
<?php if(isLoggedIn()): ?>
document.querySelectorAll('.btn-fav-inline').forEach(btn=>{
  btn.addEventListener('click',function(e){
    e.preventDefault();
    const id=this.dataset.id, self=this, icon=self.querySelector('i');
    icon.style.animation='none'; void icon.offsetHeight; icon.style.animation='heartPop .45s ease';
    fetch('pages/ajouter_favori.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'produit_id='+id+'&ajax=1'})
    .then(r=>r.json())
    .then(data=>{
      if(data.action==='added'){ self.classList.add('active'); icon.className='fas fa-heart'; }
      else { self.classList.remove('active'); icon.className='far fa-heart'; }
    }).catch(()=>window.location.href='pages/ajouter_favori.php?produit_id='+id);
  });
});
<?php else: ?>
document.querySelectorAll('.btn-fav-inline').forEach(btn=>btn.addEventListener('click',()=>window.location.href='pages/connexion.php'));
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>