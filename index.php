<?php
/**
 * Page d'accueil complète avec style Luxury 3D
 * Éclat d'Or Joaillerie
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* --------------------------------------------------------------------------
   Favoris de l'utilisateur connecté
-------------------------------------------------------------------------- */
$userFavoris = [];

if (isLoggedIn()) {
    try {
        $favStmt = $pdo->prepare(
            'SELECT id_produit FROM favori WHERE id_utilisateur = ?'
        );
        $favStmt->execute([$_SESSION['user_id']]);
        $userFavoris = array_column(
            $favStmt->fetchAll(PDO::FETCH_ASSOC),
            'id_produit'
        );
    } catch (PDOException $e) {
        error_log('index.php favoris : ' . $e->getMessage());
    }
}

/* --------------------------------------------------------------------------
   Produits de la page d'accueil
-------------------------------------------------------------------------- */
$nouveautes = [];
$bestsellers = [];

try {
    $stmtNew = $pdo->query(
        'SELECT * FROM produits
         WHERE nouveaute = 1
         ORDER BY date_ajout DESC
         LIMIT 6'
    );
    $nouveautes = $stmtNew->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('index.php nouveautés : ' . $e->getMessage());
}

try {
    $stmtBest = $pdo->query(
        'SELECT * FROM produits
         WHERE bestseller = 1
         ORDER BY date_ajout DESC
         LIMIT 6'
    );
    $bestsellers = $stmtBest->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('index.php best-sellers : ' . $e->getMessage());
}

/* --------------------------------------------------------------------------
   Bouton favoris
-------------------------------------------------------------------------- */
function homeWishlistButton(int $id, array $userFavoris): string
{
    $isFavorite = in_array($id, $userFavoris, true);

    if (isLoggedIn()) {
        $class = 'lux-wishlist-button' . ($isFavorite ? ' active' : '');
        $icon = $isFavorite ? 'fas fa-heart' : 'far fa-heart';
        $title = $isFavorite
            ? 'Retirer des favoris'
            : 'Ajouter aux favoris';

        return '<button type="button" class="' . $class . '" ' .
            'data-product-id="' . $id . '" ' .
            'title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '">' .
            '<i class="' . $icon . '"></i>' .
            '</button>';
    }

    return '<a href="' . SITE_URL . 'pages/connexion.php" ' .
        'class="lux-wishlist-button" title="Connectez-vous">' .
        '<i class="far fa-heart"></i>' .
        '</a>';
}

/* --------------------------------------------------------------------------
   Carte produit
-------------------------------------------------------------------------- */
function renderHomeProductCard(
    array $product,
    array $userFavoris,
    string $context = 'default'
): void {
    $id = (int) ($product['id_produit'] ?? 0);
    $nom = htmlspecialchars($product['nom'] ?? 'Bijou', ENT_QUOTES, 'UTF-8');
    $marque = htmlspecialchars(
        $product['marque'] ?? "Éclat d'Or",
        ENT_QUOTES,
        'UTF-8'
    );
    $description = htmlspecialchars(
        $product['description_courte'] ?? $product['description'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
    $prix = (float) ($product['prix'] ?? 0);
    $promotion = (float) ($product['promotion_pourcentage'] ?? 0);
    $prixFinal = $promotion > 0
        ? $prix * (1 - $promotion / 100)
        : $prix;
    $stock = (int) ($product['stock'] ?? 0);
    $image = !empty($product['image_url'])
        ? $product['image_url']
        : 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?auto=format&fit=crop&w=800&q=80';

    $imageHover = !empty($product['image_hover_url'])
        ? $product['image_hover_url']
        : $image;

    $badge = '';

    if ($promotion > 0) {
        $badge = '<span class="lux-product-badge lux-badge-promo">-' .
            (int) $promotion . '%</span>';
    } elseif ($context === 'bestseller') {
        $badge = '<span class="lux-product-badge lux-badge-best">BEST-SELLER</span>';
    } else {
        $badge = '<span class="lux-product-badge lux-badge-new">NOUVEAU</span>';
    }
    ?>

    <article class="lux-product-card lux-tilt-card">
        <?= $badge ?>

        <div class="lux-product-image-wrap lux-flip-scene">
            <div class="lux-product-image-flipper">
                <div class="lux-product-face lux-product-front">
                    <img
                        src="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= $nom ?>"
                        class="lux-product-image"
                        loading="lazy"
                    >
                </div>

                <div class="lux-product-face lux-product-back">
                    <img
                        src="<?= htmlspecialchars($imageHover, ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= $nom ?> porté"
                        class="lux-product-image"
                        loading="lazy"
                    >

                    <span class="lux-hover-label">Bijou porté</span>
                </div>
            </div>

            <?= homeWishlistButton($id, $userFavoris) ?>
        </div>

        <div class="lux-product-content">
            <p class="lux-product-brand">
                <?= $marque ?>
            </p>

            <h3 class="lux-product-name">
                <a href="<?= SITE_URL ?>pages/produit.php?id=<?= $id ?>">
                    <?= $nom ?>
                </a>
            </h3>

            <?php if ($description !== ''): ?>
                <p class="lux-product-description">
                    <?= $description ?>
                </p>
            <?php endif; ?>

            <div class="lux-product-pricing">
                <?php if ($promotion > 0): ?>
                    <span class="lux-original-price">
                        <?= formatPrice($prix) ?>
                    </span>
                <?php endif; ?>

                <span class="lux-current-price">
                    <?= formatPrice($prixFinal) ?>
                </span>
            </div>

            <div class="lux-product-actions">
                <?php if ($stock > 0): ?>
                    <?php if (isLoggedIn()): ?>
                        <form
                            method="POST"
                            action="<?= SITE_URL ?>pages/ajouter_au_panier.php"
                        >
                            <input
                                type="hidden"
                                name="produit_id"
                                value="<?= $id ?>"
                            >
                            <input
                                type="hidden"
                                name="quantite"
                                value="1"
                            >
                            <input
                                type="hidden"
                                name="redirect_url"
                                value="<?= SITE_URL ?>index.php"
                            >

                            <button
                                type="submit"
                                name="ajouter_panier"
                                class="lux-cart-button"
                            >
                                <i class="fas fa-shopping-bag"></i>
                                Ajouter
                            </button>
                        </form>
                    <?php else: ?>
                        <a
                            href="<?= SITE_URL ?>pages/connexion.php?redirect=index.php"
                            class="lux-cart-button lux-cart-disabled"
                        >
                            <i class="fas fa-lock"></i>
                            Connectez-vous
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="lux-out-of-stock">Rupture de stock</span>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}

$pageTitle = "Accueil - Éclat d'Or";
include 'includes/header.php';
?>

<style>
:root {
    --home-brown: #3a2925;
    --home-brown-light: #806c63;
    --home-gold: #c9a227;
    --home-gold-light: #e8d39a;
    --home-gold-dark: #9d7815;
    --home-cream: #fbf6ef;
    --home-text: #302522;
    --home-border: #eee4d8;
}

* {
    box-sizing: border-box;
}

html {
    overflow-x: hidden;
}

body {
    overflow-x: hidden;
}

/* ========================================================================
   STRUCTURE GLOBALE
======================================================================== */

.lux-home-page {
    position: relative;
    isolation: isolate;
    width: 100vw;
    margin-left: calc(50% - 50vw);
    overflow: hidden;
    background:
        radial-gradient(circle at 5% 15%, rgba(201, 162, 39, .12), transparent 25%),
        linear-gradient(135deg, #fbf6ef, #f4e9dd);
}

.lux-home-section {
    position: relative;
    z-index: 2;
    width: min(1180px, calc(100% - 40px));
    margin: 0 auto;
    padding: 75px 0;
}

.lux-section-heading {
    margin-bottom: 30px;
    text-align: center;
}

.lux-section-kicker {
    margin: 0 0 10px;
    color: var(--home-gold-dark);
    font-size: 10px;
    font-weight: bold;
    letter-spacing: 3px;
    text-transform: uppercase;
}

.lux-section-heading h2 {
    margin: 0 0 12px;
    color: var(--home-brown);
    font-family: Georgia, serif;
    font-size: clamp(1.8rem, 4vw, 2.5rem);
    font-weight: normal;
}

.lux-section-heading p {
    max-width: 550px;
    margin: 0 auto;
    color: #776f69;
    font-size: 13px;
    line-height: 1.7;
}

/* ========================================================================
   HERO CAROUSEL
======================================================================== */

.lux-hero {
    position: relative;
    min-height: 610px;
    overflow: hidden;
    background: var(--home-brown);
}

.lux-hero-slide {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 80px 30px;
    opacity: 0;
    background-position: center;
    background-size: cover;
    transition: opacity 1s ease;
}

.lux-hero-slide.active {
    opacity: 1;
}

.lux-hero-slide::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(90deg, rgba(34, 23, 20, .86), rgba(58, 41, 37, .34)),
        radial-gradient(circle at 78% 40%, rgba(201, 162, 39, .22), transparent 28%);
}

.lux-hero-content {
    position: relative;
    z-index: 3;
    width: min(1180px, 100%);
    color: #ffffff;
}

.lux-hero-inner {
    max-width: 580px;
    animation: heroContentIn .9s ease both;
}

.lux-hero-kicker {
    margin: 0 0 18px;
    color: var(--home-gold-light);
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 4px;
    text-transform: uppercase;
}

.lux-hero h1 {
    margin: 0 0 21px;
    color: #ffffff;
    font-family: Georgia, serif;
    font-size: clamp(2.6rem, 6vw, 5.2rem);
    font-weight: normal;
    line-height: 1.04;
}

.lux-hero p {
    max-width: 500px;
    margin: 0 0 30px;
    color: rgba(255, 255, 255, .82);
    font-size: 15px;
    line-height: 1.8;
}

@keyframes heroContentIn {
    from {
        opacity: 0;
        transform: translateY(25px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.lux-hero-rings {
    position: absolute;
    z-index: 2;
    width: 370px;
    height: 370px;
    right: 9%;
    top: 50%;
    border: 1px solid rgba(232, 211, 154, .45);
    border-radius: 50%;
    transform: translateY(-50%);
    animation: ringFloat 6s ease-in-out infinite;
}

.lux-hero-rings::before,
.lux-hero-rings::after {
    content: "";
    position: absolute;
    border: 1px solid rgba(232, 211, 154, .3);
    border-radius: 50%;
}

.lux-hero-rings::before {
    inset: 35px;
}

.lux-hero-rings::after {
    inset: 75px;
}

@keyframes ringFloat {
    0%,
    100% {
        transform: translateY(-50%) rotate(0deg) scale(1);
    }

    50% {
        transform: translateY(-53%) rotate(8deg) scale(1.07);
    }
}

.lux-primary-button {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-width: 190px;
    padding: 14px 22px;
    overflow: hidden;
    border-radius: 8px;
    background: linear-gradient(135deg, var(--home-gold), #b88a1d);
    color: #ffffff;
    font-size: 12px;
    font-weight: bold;
    text-decoration: none;
    box-shadow: 0 9px 22px rgba(201, 162, 39, .22);
    transition: transform .25s ease, box-shadow .25s ease;
}

.lux-primary-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 14px 28px rgba(201, 162, 39, .35);
}

.lux-primary-button::after,
.lux-cart-button::after {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 45%;
    height: 100%;
    background: rgba(255, 255, 255, .3);
    transform: skewX(-20deg);
    transition: left .55s ease;
}

.lux-primary-button:hover::after,
.lux-cart-button:hover::after {
    left: 130%;
}

.lux-hero-controls {
    position: absolute;
    z-index: 5;
    right: 35px;
    bottom: 28px;
    display: flex;
    gap: 8px;
}

.lux-hero-dot {
    width: 9px;
    height: 9px;
    border: 1px solid rgba(255, 255, 255, .7);
    border-radius: 50%;
    background: transparent;
    cursor: pointer;
}

.lux-hero-dot.active {
    border-color: var(--home-gold);
    background: var(--home-gold);
    box-shadow: 0 0 12px rgba(201, 162, 39, .65);
}

/* ========================================================================
   COLLECTIONS
======================================================================== */

.lux-collections-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
}

.lux-collection-card {
    position: relative;
    min-height: 300px;
    overflow: hidden;
    border-radius: 17px;
    background: var(--home-brown);
    box-shadow: 0 13px 30px rgba(58, 41, 37, .12);
    transform-style: preserve-3d;
    will-change: transform;
    transition: transform .25s ease, box-shadow .3s ease;
}

.lux-collection-card:hover {
    box-shadow: 0 23px 42px rgba(58, 41, 37, .22);
}

.lux-collection-card img {
    width: 100%;
    height: 100%;
    min-height: 300px;
    display: block;
    object-fit: cover;
    opacity: .76;
    transition: transform .6s ease, opacity .4s ease;
}

.lux-collection-card:hover img {
    opacity: .9;
    transform: scale(1.09);
}

.lux-collection-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    padding: 26px 20px;
    text-align: center;
    background: linear-gradient(transparent 25%, rgba(42, 28, 24, .9));
}

.lux-collection-overlay h3 {
    margin: 0 0 7px;
    color: #ffffff;
    font-family: Georgia, serif;
    font-size: 23px;
    font-weight: normal;
    transform: translateZ(25px);
}

.lux-collection-overlay p {
    margin: 0 0 15px;
    color: rgba(255, 255, 255, .8);
    font-size: 12px;
}

.lux-small-button {
    display: inline-flex;
    padding: 10px 17px;
    border: 1px solid var(--home-gold-light);
    border-radius: 6px;
    color: var(--home-gold-light);
    font-size: 11px;
    font-weight: bold;
    text-decoration: none;
    transition: .25s ease;
}

.lux-small-button:hover {
    background: var(--home-gold);
    border-color: var(--home-gold);
    color: #ffffff;
}

/* ========================================================================
   PRODUITS
======================================================================== */

.lux-products-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
}

.lux-product-card {
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(58, 41, 37, .08);
    border-radius: 16px;
    background: #ffffff;
    box-shadow: 0 9px 23px rgba(58, 41, 37, .08);
    transform-style: preserve-3d;
    will-change: transform;
    transition: transform .25s ease, box-shadow .3s ease;
}

.lux-product-card:hover {
    box-shadow:
        0 20px 38px rgba(58, 41, 37, .16),
        0 0 24px rgba(201, 162, 39, .1);
}

.lux-product-badge {
    position: absolute;
    z-index: 4;
    top: 12px;
    left: 12px;
    padding: 5px 8px;
    border-radius: 5px;
    color: #ffffff;
    font-size: 9px;
    font-weight: bold;
    letter-spacing: .3px;
}

.lux-badge-new {
    background: #27825c;
}

.lux-badge-best {
    background: var(--home-gold-dark);
}

.lux-badge-promo {
    background: #a24343;
}

.lux-product-image-wrap {
    position: relative;
    overflow: hidden;
    aspect-ratio: 1 / 1;
    background: #f3e8dc;
    transform: translateZ(4px);
}

.lux-product-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    transition:
        opacity .45s ease,
        transform .6s ease,
        filter .45s ease;
}

.lux-product-image-main {
    position: relative;
    opacity: 1;
    transform: scale(1);
}

.lux-product-image-hover {
    opacity: 0;
    transform: scale(1.08);
}

.lux-product-card:hover .lux-product-image-main {
    opacity: 0;
    transform: scale(1.08);
}

.lux-product-card:hover .lux-product-image-hover {
    opacity: 1;
    transform: scale(1);
}

.lux-hover-label {
    position: absolute;
    z-index: 6;
    left: 50%;
    bottom: 14px;
    padding: 6px 10px;
    border-radius: 20px;
    background: rgba(58, 41, 37, .85);
    color: #ffffff;
    font-size: 10px;
    opacity: 0;
    pointer-events: none;
    transform: translate(-50%, 8px);
    transition: opacity .35s ease, transform .35s ease;
}

.lux-product-card:hover .lux-hover-label {
    opacity: 1;
    transform: translate(-50%, 0);
}

@media (max-width: 700px) {
    .lux-product-image-hover,
    .lux-hover-label {
        display: none;
    }

    .lux-product-card:hover .lux-product-image-main {
        opacity: 1;
        transform: scale(1);
    }
}

.lux-wishlist-button {
    position: absolute;
    z-index: 5;
    top: 12px;
    right: 12px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 50%;
    background: rgba(255, 255, 255, .92);
    color: var(--home-gold-dark);
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(58, 41, 37, .13);
    transition: transform .25s ease, color .25s ease, background .25s ease;
}

.lux-wishlist-button:hover,
.lux-wishlist-button.active {
    transform: scale(1.12);
    background: var(--home-gold);
    color: #ffffff;
}

.lux-product-content {
    position: relative;
    z-index: 3;
    padding: 17px;
    transform: translateZ(13px);
}

.lux-product-brand {
    margin: 0 0 7px;
    color: var(--home-gold-dark);
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.lux-product-name {
    min-height: 42px;
    margin: 0 0 7px;
    color: var(--home-brown);
    font-family: Georgia, serif;
    font-size: 18px;
    font-weight: normal;
    line-height: 1.25;
}

.lux-product-name a {
    color: inherit;
    text-decoration: none;
}

.lux-product-name a:hover {
    color: var(--home-gold-dark);
}

.lux-product-description {
    min-height: 32px;
    margin: 0 0 12px;
    overflow: hidden;
    color: #918983;
    font-size: 11px;
    line-height: 1.45;
}

.lux-product-pricing {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 14px;
}

.lux-current-price {
    color: var(--home-brown);
    font-size: 20px;
    font-weight: bold;
}

.lux-original-price {
    color: #aaa19a;
    font-size: 12px;
    text-decoration: line-through;
}

.lux-product-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.lux-product-actions form {
    flex: 1;
}

.lux-cart-button {
    position: relative;
    width: 100%;
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    overflow: hidden;
    border: none;
    border-radius: 7px;
    background: var(--home-brown);
    color: #ffffff;
    cursor: pointer;
    font-size: 11px;
    font-weight: bold;
    text-decoration: none;
    transition: transform .25s ease, background .25s ease, box-shadow .25s ease;
}

.lux-cart-button:hover {
    transform: translateY(-2px);
    background: var(--home-gold-dark);
    box-shadow: 0 8px 16px rgba(201, 162, 39, .25);
}

.lux-cart-disabled {
    background: #a59b96;
}

.lux-out-of-stock {
    color: #a24343;
    font-size: 12px;
    font-weight: bold;
}

/* ========================================================================
   HISTOIRE
======================================================================== */

.lux-about-section {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #f4e9dc, #fffaf4);
}

.lux-about-inner {
    width: min(1180px, calc(100% - 40px));
    margin: 0 auto;
    padding: 75px 0;
    text-align: center;
}

.lux-about-inner p {
    max-width: 700px;
    margin: 0 auto 35px;
    color: #776f69;
    font-size: 13px;
    line-height: 1.9;
}

.lux-about-features {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 25px;
}

.lux-about-feature {
    padding: 20px 10px;
    border: 1px solid rgba(201, 162, 39, .2);
    border-radius: 14px;
    background: rgba(255, 255, 255, .55);
    transition: transform .3s ease, box-shadow .3s ease;
}

.lux-about-feature:hover {
    transform: translateY(-7px);
    box-shadow: 0 12px 25px rgba(58, 41, 37, .1);
}

.lux-about-feature i {
    margin-bottom: 12px;
    color: var(--home-gold);
    font-size: 25px;
}

.lux-about-feature h3 {
    margin: 0 0 7px;
    color: var(--home-brown);
    font-family: Georgia, serif;
    font-size: 16px;
    font-weight: normal;
}

.lux-about-feature p {
    margin: 0;
    color: #8a817b;
    font-size: 11px;
    line-height: 1.5;
}

/* ========================================================================
   RESPONSIVE
======================================================================== */

@media (max-width: 950px) {
    .lux-hero-rings {
        right: -80px;
        opacity: .5;
    }

    .lux-products-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .lux-about-features {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 700px) {
    .lux-hero {
        min-height: 570px;
    }

    .lux-hero-slide {
        align-items: flex-end;
        padding: 75px 25px;
    }

    .lux-hero-rings {
        top: 17%;
        right: -120px;
        width: 300px;
        height: 300px;
    }

    .lux-collections-grid {
        grid-template-columns: 1fr;
    }

    .lux-collection-card,
    .lux-collection-card img {
        min-height: 260px;
    }
}

@media (max-width: 520px) {
    .lux-home-section,
    .lux-about-inner {
        width: calc(100% - 26px);
        padding: 55px 0;
    }

    .lux-products-grid {
        grid-template-columns: 1fr;
    }

    .lux-about-features {
        grid-template-columns: 1fr;
    }

    .lux-hero h1 {
        font-size: 2.7rem;
    }

    .lux-hero p {
        font-size: 13px;
    }
}

@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: .01ms !important;
    }
}
/* ========================================================================
   MODELE CARTE RETOURNEE 3D
======================================================================== */

.lux-flip-scene {
    perspective: 1000px;
}

.lux-product-image-flipper {
    position: absolute;
    inset: 0;
    transform-style: preserve-3d;
    transition: transform .75s cubic-bezier(.2, .75, .25, 1);
}

.lux-flip-scene:hover .lux-product-image-flipper {
    transform: rotateY(180deg);
}

.lux-product-face {
    position: absolute;
    inset: 0;
    overflow: hidden;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

.lux-product-front {
    transform: rotateY(0deg);
}

.lux-product-back {
    transform: rotateY(180deg);
}

.lux-product-face .lux-product-image {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transform: none !important;
    opacity: 1 !important;
}

.lux-product-back::after {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(
        145deg,
        transparent 45%,
        rgba(255, 255, 255, .25),
        transparent 65%
    );
}

.lux-flip-scene .lux-hover-label {
    opacity: 0;
    transform: translate(-50%, 8px);
}

.lux-flip-scene:hover .lux-hover-label {
    opacity: 1;
    transform: translate(-50%, 0);
}

@media (max-width: 700px) {
    .lux-flip-scene:hover .lux-product-image-flipper {
        transform: none;
    }

    .lux-product-back {
        display: none;
    }

    .lux-flip-scene .lux-hover-label {
        display: none;
    }
}
</style>

<main class="lux-home-page">
    <!-- Hero -->
    <section class="lux-hero" aria-label="Présentation de la boutique">
        <div
            class="lux-hero-slide active"
            style="background-image: url('https://images.unsplash.com/photo-1586878340506-af074f2ee999?auto=format&fit=crop&w=1800&q=85');"
        >
            <div class="lux-hero-content">
                <div class="lux-hero-inner">
                    <p class="lux-hero-kicker">Maison Éclat d'Or</p>
                    <h1>L'art de la joaillerie</h1>
                    <p>
                        Découvrez des bijoux d'exception, imaginés pour révéler
                        votre élégance et accompagner vos moments précieux.
                    </p>
                    <a
                        href="<?= SITE_URL ?>pages/catalogue.php"
                        class="lux-primary-button"
                    >
                        Découvrir la collection
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="lux-hero-rings"></div>
        </div>

        <div
            class="lux-hero-slide"
            style="background-image: url('https://images.unsplash.com/photo-1634390756696-505390283f61?auto=format&fit=crop&w=1800&q=85');"
        >
            <div class="lux-hero-content">
                <div class="lux-hero-inner">
                    <p class="lux-hero-kicker">Nouvelles créations</p>
                    <h1>Une lumière unique</h1>
                    <p>
                        Des pièces délicates, conçues avec passion pour
                        illuminer chaque instant de votre quotidien.
                    </p>
                    <a
                        href="<?= SITE_URL ?>pages/catalogue.php?filter=new"
                        class="lux-primary-button"
                    >
                        Voir les nouveautés
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="lux-hero-rings"></div>
        </div>

        <div
            class="lux-hero-slide"
            style="background-image: url('https://images.unsplash.com/photo-1492714485642-dd6df6baafa2?auto=format&fit=crop&w=1800&q=85');"
        >
            <div class="lux-hero-content">
                <div class="lux-hero-inner">
                    <p class="lux-hero-kicker">Offres exclusives</p>
                    <h1>Le luxe autrement</h1>
                    <p>
                        Profitez d'une sélection de bijoux iconiques et de
                        promotions exclusives, disponibles en quantité limitée.
                    </p>
                    <a
                        href="<?= SITE_URL ?>pages/catalogue.php?filter=promo"
                        class="lux-primary-button"
                    >
                        Voir les offres
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="lux-hero-rings"></div>
        </div>

        <div class="lux-hero-controls">
            <button class="lux-hero-dot active" type="button" aria-label="Slide 1"></button>
            <button class="lux-hero-dot" type="button" aria-label="Slide 2"></button>
            <button class="lux-hero-dot" type="button" aria-label="Slide 3"></button>
        </div>
    </section>

    <!-- Collections -->
    <section class="lux-home-section">
        <div class="lux-section-heading">
            <p class="lux-section-kicker">Trouvez votre style</p>
            <h2>Nos collections</h2>
            <p>
                Des bijoux pensés pour chaque personnalité, chaque occasion
                et chaque histoire.
            </p>
        </div>

        <div class="lux-collections-grid">
            <article class="lux-collection-card lux-tilt-card">
                <img
                    src="https://sn.jumia.is/unsafe/fit-in/500x500/filters:fill(white)/product/45/308121/1.jpg?8252"
                    alt="Bijoux pour femme"
                    loading="lazy"
                >
                <div class="lux-collection-overlay">
                    <h3>Pour Elle</h3>
                    <p>Créations délicates et lumineuses</p>
                    <a
                        href="<?= SITE_URL ?>pages/catalogue.php?category=Femme"
                        class="lux-small-button"
                    >
                        Découvrir
                    </a>
                </div>
            </article>

            <article class="lux-collection-card lux-tilt-card">
                <img
                    src="https://ci.jumia.is/unsafe/fit-in/500x500/filters:fill(white)/product/99/749662/1.jpg?6877"
                    alt="Bijoux pour homme"
                    loading="lazy"
                >
                <div class="lux-collection-overlay">
                    <h3>Pour Lui</h3>
                    <p>Pièces raffinées et intemporelles</p>
                    <a
                        href="<?= SITE_URL ?>pages/catalogue.php?category=Homme"
                        class="lux-small-button"
                    >
                        Découvrir
                    </a>
                </div>
            </article>

            <article class="lux-collection-card lux-tilt-card">
                <img
                    src="https://m.media-amazon.com/images/I/71naqPXNxXL._AC_UY1000_.jpg"
                    alt="Bijoux unisexes"
                    loading="lazy"
                >
                <div class="lux-collection-overlay">
                    <h3>Unisexe</h3>
                    <p>Élégance pour tous les styles</p>
                    <a
                        href="<?= SITE_URL ?>pages/catalogue.php?category=Unisexe"
                        class="lux-small-button"
                    >
                        Découvrir
                    </a>
                </div>
            </article>
        </div>
    </section>

    <!-- Nouveautés -->
    <section class="lux-home-section">
        <div class="lux-section-heading">
            <p class="lux-section-kicker">Les dernières créations</p>
            <h2>Nos nouveautés</h2>
            <p>
                Découvrez les dernières pièces ajoutées à notre collection.
            </p>
        </div>

        <?php if (!empty($nouveautes)): ?>
            <div class="lux-products-grid">
                <?php foreach ($nouveautes as $product): ?>
                    <?php renderHomeProductCard($product, $userFavoris, 'nouveaute'); ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align:center;color:#999;">
                Aucune nouveauté pour le moment.
            </p>
        <?php endif; ?>
    </section>

    <!-- Best-sellers -->
    <section class="lux-home-section">
        <div class="lux-section-heading">
            <p class="lux-section-kicker">Les plus appréciés</p>
            <h2>Best-sellers</h2>
            <p>
                Les bijoux préférés de notre communauté.
            </p>
        </div>

        <?php if (!empty($bestsellers)): ?>
            <div class="lux-products-grid">
                <?php foreach ($bestsellers as $product): ?>
                    <?php renderHomeProductCard($product, $userFavoris, 'bestseller'); ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align:center;color:#999;">
                Aucun best-seller pour le moment.
            </p>
        <?php endif; ?>
    </section>

    <!-- Histoire -->
    <section class="lux-about-section">
        <div class="lux-about-inner">
            <div class="lux-section-heading">
                <p class="lux-section-kicker">Notre savoir-faire</p>
                <h2>L'histoire d'Éclat d'Or</h2>
            </div>

            <p>
                Depuis 1920, Éclat d'Or imagine des bijoux d'exception qui
                traversent les époques. Chaque pièce associe savoir-faire
                artisanal, élégance intemporelle et design contemporain.
            </p>

            <div class="lux-about-features">
                <article class="lux-about-feature">
                    <i class="fas fa-gem"></i>
                    <h3>Pierres d'exception</h3>
                    <p>Sélectionnées pour leur pureté et leur éclat.</p>
                </article>

                <article class="lux-about-feature">
                    <i class="fas fa-hand-sparkles"></i>
                    <h3>Savoir-faire artisanal</h3>
                    <p>Chaque bijou est travaillé avec attention.</p>
                </article>

                <article class="lux-about-feature">
                    <i class="fas fa-shipping-fast"></i>
                    <h3>Livraison soignée</h3>
                    <p>Expédition sécurisée sous 24 à 48 heures.</p>
                </article>

                <article class="lux-about-feature">
                    <i class="fas fa-heart"></i>
                    <h3>Excellence garantie</h3>
                    <p>Satisfaction ou retour offert sous 30 jours.</p>
                </article>
            </div>
        </div>
    </section>
</main>

<script>
/* ========================================================================
   Carousel automatique
======================================================================== */

document.addEventListener('DOMContentLoaded', function () {
    const slides = document.querySelectorAll('.lux-hero-slide');
    const dots = document.querySelectorAll('.lux-hero-dot');
    let currentSlide = 0;
    let interval;

    function showSlide(index) {
        slides.forEach(function (slide, slideIndex) {
            slide.classList.toggle('active', slideIndex === index);
        });

        dots.forEach(function (dot, dotIndex) {
            dot.classList.toggle('active', dotIndex === index);
        });

        currentSlide = index;
    }

    function nextSlide() {
        showSlide((currentSlide + 1) % slides.length);
    }

    function startCarousel() {
        interval = setInterval(nextSlide, 6500);
    }

    function resetCarousel() {
        clearInterval(interval);
        startCarousel();
    }

    dots.forEach(function (dot, index) {
        dot.addEventListener('click', function () {
            showSlide(index);
            resetCarousel();
        });
    });

    startCarousel();
});

/* ========================================================================
   Effet de rotation 3D sur les cartes
======================================================================== */

document.addEventListener('DOMContentLoaded', function () {
    const cards = document.querySelectorAll('.lux-tilt-card');

    cards.forEach(function (card) {
        card.addEventListener('mousemove', function (event) {
            if (window.innerWidth <= 700) {
                return;
            }

            const rect = card.getBoundingClientRect();
            const x = event.clientX - rect.left;
            const y = event.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            const rotateY = ((x - centerX) / centerX) * 3;
            const rotateX = ((centerY - y) / centerY) * 3;

            card.style.transform =
                'perspective(900px) rotateX(' + rotateX +
                'deg) rotateY(' + rotateY + 'deg) translateY(-6px)';
        });

        card.addEventListener('mouseleave', function () {
            card.style.transform =
                'perspective(900px) rotateX(0deg) rotateY(0deg) translateY(0)';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>