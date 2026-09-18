<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Éclat d'Or - Joaillerie de Luxe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Playfair+Display:wght@400;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>assets/images/logo.png">

    <style>
        /* ===== NAVBAR PREMIUM LUMOURA ===== */

        /* Logo */
        .nav-logo .logo-text {
            font-family: 'Cormorant Garamond', serif !important;
            font-size: 28px !important;
            font-weight: 600 !important;
            letter-spacing: 4px !important;
            text-transform: uppercase !important;
            color: #1a1008 !important;
            line-height: 1 !important;
        }
        .nav-logo .logo-subtext {
            font-family: 'Montserrat', sans-serif !important;
            font-size: 9px !important;
            font-weight: 300 !important;
            letter-spacing: 4px !important;
            text-transform: uppercase !important;
            color: #a07828 !important;
            margin-top: 3px !important;
            display: block !important;
        }

        /* Liens de navigation */
        .nav-link {
            font-family: 'Montserrat', sans-serif !important;
            font-size: 10.5px !important;
            font-weight: 400 !important;
            letter-spacing: 2.5px !important;
            text-transform: uppercase !important;
            position: relative !important;
            transition: color 0.25s ease !important;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 1px;
            background: #a07828;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after,
        .nav-link.active::after {
            width: 60%;
        }

        /* Dropdown Collections */
        .dropdown-content a {
            font-family: 'Montserrat', sans-serif !important;
            font-size: 10px !important;
            font-weight: 400 !important;
            letter-spacing: 2px !important;
            text-transform: uppercase !important;
            transition: color 0.2s, padding-left 0.2s !important;
        }
        .dropdown-content a:hover {
            padding-left: 1.6rem !important;
        }
        .dropdown-content {
            border-top: 2px solid #a07828 !important;
        }
    </style>
</head>
<body>

    <nav class="navbar" id="mainNavbar">
        <div class="nav-container">

            <!-- Logo -->
            <a href="<?php echo SITE_URL; ?>index.php" class="nav-logo" style="margin-right: 100px;">
               <span class="logo-text">Éclat d'Or</span>
                <span class="logo-subtext">Bijoux d'Exception</span>
            </a>

            <!-- Menu mobile -->
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Navigation desktop -->
            <div class="nav-menu" id="navMenu">
                <a href="<?php echo SITE_URL; ?>index.php" class="nav-link">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <a href="<?php echo SITE_URL; ?>pages/catalogue.php" class="nav-link">
                    <i class="fas fa-store"></i> Catalogue
                </a>
                <!-- ✅ CORRIGÉ : majuscules pour correspondre à la BDD -->
                <a href="<?php echo SITE_URL; ?>pages/catalogue.php?category=Femme" class="nav-link">Femme</a>
                <a href="<?php echo SITE_URL; ?>pages/catalogue.php?category=Homme" class="nav-link">Homme</a>
                <a href="<?php echo SITE_URL; ?>pages/catalogue.php?category=Unisexe" class="nav-link">Unisexe</a>

                <!-- Menu déroulant Collections -->
                <div class="nav-dropdown">
                    <a href="#" class="nav-link">
                        <i class="fas fa-star"></i> Collections <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?php echo SITE_URL; ?>pages/catalogue.php?filter=new">Nouveautés</a>
                        <a href="<?php echo SITE_URL; ?>pages/catalogue.php?filter=bestseller">Best-sellers</a>
                        <a href="<?php echo SITE_URL; ?>pages/catalogue.php?filter=promo">Promotions</a>
                    </div>
                </div>

                <a href="#" class="nav-link"><i class="fas fa-gift"></i> Cadeaux</a>
            </div>

            <!-- Actions utilisateur -->
            <div class="nav-actions">

                <!-- ✅ CORRIGÉ : recherche dans un vrai <form> qui pointe vers catalogue.php -->
                <div class="search-container">
                    <button class="search-btn" id="searchToggle" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                    <div class="search-box" id="searchBox">
                        <form method="GET" action="<?php echo SITE_URL; ?>pages/catalogue.php" id="searchForm">
                            <input type="text" name="search" placeholder="Rechercher un bijou..." id="searchInput">
                            <button type="submit" class="search-submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Compte utilisateur -->
                <div class="user-dropdown">
                    <button class="user-btn">
                        <i class="fas fa-user"></i>
                    </button>
                    <div class="user-dropdown-content">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="<?php echo SITE_URL; ?>pages/compte.php">
                                <i class="fas fa-user-circle"></i> Mon Compte
                            </a>
                            <a href="<?php echo SITE_URL; ?>pages/compte.php?page=orders">
                                <i class="fas fa-shopping-bag"></i> Mes Commandes
                            </a>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <a href="<?php echo SITE_URL; ?>pages/admin/">
                                    <i class="fas fa-cog"></i> Administration
                                </a>
                            <?php endif; ?>
                            <hr>
                            <a href="<?php echo SITE_URL; ?>pages/deconnexion.php">
                                <i class="fas fa-sign-out-alt"></i> Déconnexion
                            </a>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>pages/connexion.php">
                                <i class="fas fa-sign-in-alt"></i> Connexion
                            </a>
                            <a href="<?php echo SITE_URL; ?>pages/inscription.php">
                                <i class="fas fa-user-plus"></i> Inscription
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ✅ CORRIGÉ : panier depuis la BDD (plus fiable que SESSION['cart']) -->
                <a href="<?php echo SITE_URL; ?>pages/panier.php" class="cart-btn" id="cartBtn">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-count" id="cartCount">
                        <?php
                        $cartCount = 0;
                        if (isset($_SESSION['user_id']) && isset($pdo)) {
                            try {
                                $cartStmt = $pdo->prepare("SELECT COALESCE(SUM(quantite), 0) FROM panier WHERE id_utilisateur = ?");
                                $cartStmt->execute([$_SESSION['user_id']]);
                                $cartCount = (int) $cartStmt->fetchColumn();
                            } catch (Exception $e) {
                                $cartCount = 0;
                            }
                        }
                        echo $cartCount;
                        ?>
                    </span>
                </a>

            </div>
        </div>

        <!-- ✅ CORRIGÉ : barre de recherche mobile aussi dans un <form> -->
        <div class="mobile-search" id="mobileSearch">
            <form method="GET" action="<?php echo SITE_URL; ?>pages/catalogue.php">
                <input type="text" name="search" placeholder="Rechercher un bijou..." id="mobileSearchInput">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </nav>

    <main class="main-content">