<?php
/*
 * Header commun Éclat d'Or
 * Important : ce fichier n'ouvre pas de balise <main>.
 * Chaque page doit gérer son propre contenu principal.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('SITE_URL')) {
    require_once __DIR__ . '/config.php';
}

$headerCartCount = 0;

if (isset($_SESSION['user_id']) && isset($pdo)) {
    try {
        $cartStmt = $pdo->prepare(
            'SELECT COALESCE(SUM(quantite), 0) FROM panier WHERE id_utilisateur = ?'
        );
        $cartStmt->execute([$_SESSION['user_id']]);
        $headerCartCount = (int) $cartStmt->fetchColumn();
    } catch (Throwable $e) {
        $headerCartCount = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' - ' : '' ?>Éclat d'Or
    </title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Montserrat:wght@400;500;600&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="<?= SITE_URL ?>assets/css/style.css"
    >

    <style>
        :root {
            --site-brown: #3a2925;
            --site-gold: #c9a227;
            --site-gold-dark: #9d7815;
            --site-cream: #fbf6ef;
            --site-border: #eee4d8;
        }

        .site-header {
            position: sticky;
            z-index: 1000;
            top: 0;
            width: 100%;
            border-bottom: 1px solid rgba(58, 41, 37, .1);
            background: rgba(255, 255, 255, .97);
            box-shadow: 0 5px 18px rgba(58, 41, 37, .07);
            backdrop-filter: blur(12px);
        }

        .site-nav {
            width: min(1380px, calc(100% - 44px));
            min-height: 76px;
            display: flex;
            align-items: center;
            gap: 30px;
            margin: 0 auto;
        }

        .site-logo {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            color: var(--site-brown);
            line-height: 1;
            text-decoration: none;
        }

        .site-logo strong {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 31px;
            font-weight: 600;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        .site-logo small {
            margin-top: 5px;
            color: var(--site-gold-dark);
            font-family: Montserrat, Arial, sans-serif;
            font-size: 8px;
            letter-spacing: 4px;
            text-align: center;
            text-transform: uppercase;
        }

        .site-menu {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .site-link,
        .site-dropdown-toggle {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 28px 10px 25px;
            border: none;
            background: transparent;
            color: #675851;
            cursor: pointer;
            font-family: Montserrat, Arial, sans-serif;
            font-size: 10px;
            letter-spacing: 1.7px;
            text-decoration: none;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .site-link::after,
        .site-dropdown-toggle::after {
            content: "";
            position: absolute;
            right: 10px;
            bottom: 17px;
            left: 10px;
            height: 1px;
            background: var(--site-gold);
            transform: scaleX(0);
            transform-origin: center;
            transition: transform .25s ease;
        }

        .site-link:hover,
        .site-link.active,
        .site-dropdown:hover .site-dropdown-toggle {
            color: var(--site-gold-dark);
        }

        .site-link:hover::after,
        .site-link.active::after,
        .site-dropdown:hover .site-dropdown-toggle::after {
            transform: scaleX(1);
        }

        .site-dropdown {
            position: relative;
        }

        .site-dropdown-menu {
            position: absolute;
            top: 70px;
            left: 50%;
            min-width: 190px;
            padding: 9px;
            border-top: 2px solid var(--site-gold);
            background: #ffffff;
            box-shadow: 0 14px 30px rgba(58, 41, 37, .14);
            opacity: 0;
            pointer-events: none;
            transform: translate(-50%, 8px);
            transition: opacity .2s ease, transform .2s ease;
        }

        .site-dropdown:hover .site-dropdown-menu,
        .site-dropdown:focus-within .site-dropdown-menu {
            opacity: 1;
            pointer-events: auto;
            transform: translate(-50%, 0);
        }

        .site-dropdown-menu a {
            display: block;
            padding: 11px 13px;
            color: #675851;
            font-family: Montserrat, Arial, sans-serif;
            font-size: 10px;
            letter-spacing: 1px;
            text-decoration: none;
            text-transform: uppercase;
        }

        .site-dropdown-menu a:hover {
            background: var(--site-cream);
            color: var(--site-gold-dark);
        }

        .site-actions {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .site-action,
        .site-search-toggle,
        .site-mobile-toggle {
            width: 37px;
            height: 37px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 50%;
            background: transparent;
            color: var(--site-brown);
            cursor: pointer;
            font-size: 15px;
            text-decoration: none;
            transition: background .2s ease, color .2s ease, transform .2s ease;
        }

        .site-action:hover,
        .site-search-toggle:hover,
        .site-mobile-toggle:hover {
            background: var(--site-cream);
            color: var(--site-gold-dark);
            transform: translateY(-2px);
        }

        .site-user,
        .site-search {
            position: relative;
        }

        .site-user-menu {
            position: absolute;
            top: 48px;
            right: 0;
            min-width: 190px;
            padding: 9px;
            border-top: 2px solid var(--site-gold);
            background: #ffffff;
            box-shadow: 0 14px 30px rgba(58, 41, 37, .14);
            opacity: 0;
            pointer-events: none;
            transform: translateY(8px);
            transition: opacity .2s ease, transform .2s ease;
        }

        .site-user:hover .site-user-menu,
        .site-user:focus-within .site-user-menu {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .site-user-menu a {
            display: block;
            padding: 10px 12px;
            color: #675851;
            font-size: 12px;
            text-decoration: none;
        }

        .site-user-menu a:hover {
            background: var(--site-cream);
            color: var(--site-gold-dark);
        }

        .site-user-menu hr {
            margin: 5px 0;
            border: none;
            border-top: 1px solid var(--site-border);
        }

        .site-cart {
            position: relative;
        }

        .site-cart-count {
            position: absolute;
            top: -1px;
            right: -2px;
            min-width: 17px;
            height: 17px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--site-gold);
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
        }

        .site-search-panel {
            position: absolute;
            top: 55px;
            right: 0;
            width: 310px;
            padding: 15px;
            border: 1px solid var(--site-border);
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 15px 35px rgba(58, 41, 37, .14);
            opacity: 0;
            pointer-events: none;
            transform: translateY(-7px);
            transition: opacity .2s ease, transform .2s ease;
        }

        .site-search.is-open .site-search-panel {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .site-search-form {
            display: flex;
            overflow: hidden;
            border: 1px solid var(--site-border);
            border-radius: 25px;
        }

        .site-search-form input {
            flex: 1;
            min-width: 0;
            padding: 11px 13px;
            border: none;
            outline: none;
            font-size: 12px;
        }

        .site-search-form button {
            width: 43px;
            border: none;
            background: var(--site-gold);
            color: #ffffff;
            cursor: pointer;
        }

        .site-mobile-toggle {
            display: none;
        }

        .site-mobile-search {
            display: none;
        }

        @media (max-width: 1050px) {
            .site-nav {
                gap: 12px;
            }

            .site-link,
            .site-dropdown-toggle {
                padding-right: 7px;
                padding-left: 7px;
                font-size: 9px;
                letter-spacing: 1px;
            }

            .site-logo strong {
                font-size: 25px;
            }
        }

        @media (max-width: 800px) {
            .site-nav {
                min-height: 66px;
                justify-content: space-between;
            }

            .site-mobile-toggle {
                display: inline-flex;
            }

            .site-menu {
                position: absolute;
                top: 66px;
                right: 12px;
                left: 12px;
                display: none;
                flex-direction: column;
                align-items: stretch;
                padding: 10px;
                border: 1px solid var(--site-border);
                background: #ffffff;
                box-shadow: 0 14px 30px rgba(58, 41, 37, .12);
            }

            .site-menu.is-open {
                display: flex;
            }

            .site-link,
            .site-dropdown-toggle {
                justify-content: center;
                padding: 13px;
            }

            .site-link::after,
            .site-dropdown-toggle::after {
                bottom: 7px;
            }

            .site-dropdown-menu {
                position: static;
                display: none;
                margin-top: 4px;
                box-shadow: none;
                opacity: 1;
                transform: none;
            }

            .site-dropdown:hover .site-dropdown-menu,
            .site-dropdown:focus-within .site-dropdown-menu {
                display: block;
            }

            .site-actions {
                margin-left: auto;
            }

            .site-search {
                display: none;
            }

            .site-mobile-search {
                padding: 0 14px 12px;
                background: #ffffff;
            }

            .site-mobile-search.is-visible {
                display: block;
            }

            .site-mobile-search form {
                display: flex;
                overflow: hidden;
                border: 1px solid var(--site-border);
                border-radius: 25px;
            }

            .site-mobile-search input {
                flex: 1;
                min-width: 0;
                padding: 11px 13px;
                border: none;
                outline: none;
            }

            .site-mobile-search button {
                width: 46px;
                border: none;
                background: var(--site-gold);
                color: #ffffff;
            }
        }
    </style>
</head>
<body>
<header class="site-header">
    <nav class="site-nav">
        <a href="<?= SITE_URL ?>index.php" class="site-logo">
            <strong>Éclat d'Or</strong>
            <small>Bijoux d'exception</small>
        </a>

        <button type="button" class="site-mobile-toggle" id="siteMobileToggle" aria-label="Ouvrir le menu">
            <i class="fas fa-bars"></i>
        </button>

        <div class="site-menu" id="siteMenu">
            <a href="<?= SITE_URL ?>index.php" class="site-link">
                <i class="fas fa-home"></i>
                Accueil
            </a>

            <a href="<?= SITE_URL ?>pages/catalogue.php" class="site-link">
                <i class="fas fa-store"></i>
                Catalogue
            </a>

            <a href="<?= SITE_URL ?>pages/catalogue.php?category=Femme" class="site-link">Femme</a>
            <a href="<?= SITE_URL ?>pages/catalogue.php?category=Homme" class="site-link">Homme</a>
            <a href="<?= SITE_URL ?>pages/catalogue.php?category=Unisexe" class="site-link">Unisexe</a>

            <div class="site-dropdown">
                <button type="button" class="site-dropdown-toggle">
                    <i class="fas fa-star"></i>
                    Collections
                    <i class="fas fa-chevron-down"></i>
                </button>

                <div class="site-dropdown-menu">
                    <a href="<?= SITE_URL ?>pages/catalogue.php?filter=new">Nouveautés</a>
                    <a href="<?= SITE_URL ?>pages/catalogue.php?filter=bestseller">Best-sellers</a>
                    <a href="<?= SITE_URL ?>pages/catalogue.php?filter=promo">Promotions</a>
                </div>
            </div>

            <a href="<?= SITE_URL ?>pages/cadeaux.php" class="site-link">
                <i class="fas fa-gift"></i>
                Cadeaux
            </a>
        </div>

        <div class="site-actions">
            <div class="site-search" id="siteSearch">
                <button type="button" class="site-search-toggle" id="siteSearchToggle" aria-label="Rechercher">
                    <i class="fas fa-search"></i>
                </button>

                <div class="site-search-panel">
                    <form method="GET" action="<?= SITE_URL ?>pages/catalogue.php" class="site-search-form">
                        <input type="search" name="search" placeholder="Rechercher un bijou...">
                        <button type="submit" aria-label="Lancer la recherche">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div class="site-user">
                <button type="button" class="site-action" aria-label="Compte">
                    <i class="fas fa-user"></i>
                </button>

                <div class="site-user-menu">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?= SITE_URL ?>pages/compte.php">
                            <i class="fas fa-user-circle"></i> Mon compte
                        </a>
                        <a href="<?= SITE_URL ?>pages/commandes.php">
                            <i class="fas fa-shopping-bag"></i> Mes commandes
                        </a>

                        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                            <a href="<?= SITE_URL ?>pages/admin/">
                                <i class="fas fa-cog"></i> Administration
                            </a>
                        <?php endif; ?>

                        <hr>

                        <a href="<?= SITE_URL ?>pages/deconnexion.php">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>pages/connexion.php">
                            <i class="fas fa-sign-in-alt"></i> Connexion
                        </a>
                        <a href="<?= SITE_URL ?>pages/inscription.php">
                            <i class="fas fa-user-plus"></i> Inscription
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <a href="<?= SITE_URL ?>pages/panier.php" class="site-action site-cart" aria-label="Panier">
                <i class="fas fa-shopping-bag"></i>
                <span class="site-cart-count"><?= $headerCartCount ?></span>
            </a>
        </div>
    </nav>

    <div class="site-mobile-search" id="siteMobileSearch">
        <form method="GET" action="<?= SITE_URL ?>pages/catalogue.php">
            <input type="search" name="search" placeholder="Rechercher un bijou...">
            <button type="submit" aria-label="Lancer la recherche">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('siteSearch');
    const searchToggle = document.getElementById('siteSearchToggle');
    const mobileToggle = document.getElementById('siteMobileToggle');
    const mobileSearch = document.getElementById('siteMobileSearch');
    const menu = document.getElementById('siteMenu');

    if (searchToggle && search) {
        searchToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            search.classList.toggle('is-open');
        });
    }

    document.addEventListener('click', function (event) {
        if (search && !search.contains(event.target)) {
            search.classList.remove('is-open');
        }
    });

    if (mobileToggle && menu) {
        mobileToggle.addEventListener('click', function () {
            menu.classList.toggle('is-open');
            mobileSearch.classList.toggle('is-visible');
        });
    }
});
</script>