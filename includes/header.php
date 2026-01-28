<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Lumoura - Joaillerie de Luxe</title>
    
    <!-- Font Awesome pour les icônes (déjà là, on garde) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Polices élégantes (déjà là, on garde) -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS – on l'ajoute ici -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons (très utile pour panier, cœur, loupe, etc.) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- TON CSS principal – DOIT venir APRÈS Bootstrap pour garder la priorité -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>assets/images/logo.png">
</head>
<body>

    <!-- Navigation principale -->
    <nav class="navbar" id="mainNavbar">
        <div class="nav-container">
            <!-- Logo -->
            <a href="<?php echo SITE_URL; ?>index.php" class="nav-logo" style="margin-right: 100px;">
                <span class="logo-text">Eclat D'or</span>
                <span class="logo-subtext">Parfums d'Exception</span>
            </a>

            <!-- Menu pour mobile -->
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Navigation desktop -->
            <div class="nav-menu" id="navMenu">
                <a href="<?php echo SITE_URL; ?>index.php" class="nav-link"><i class="fas fa-home"></i> Accueil</a>
                <a href="<?php echo SITE_URL; ?>pages/catalogue.php" class="nav-link"><i class="fas fa-store"></i> Catalogue</a>
                <a href="<?php echo SITE_URL; ?>pages/catalogue.php?category=femme" class="nav-link">Femme</a>
                <a href="<?php echo SITE_URL; ?>pages/catalogue.php?category=homme" class="nav-link">Homme</a>
                <a href="<?php echo SITE_URL; ?>pages/catalogue.php?category=unisexe" class="nav-link">Unisexe</a>
                
                <!-- Menu déroulant Nouveautés -->
                <div class="nav-dropdown">
                    <a href="#" class="nav-link">
                        <i class="fas fa-star"></i> Collections <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?php echo SITE_URL; ?>pages/catalogue.php?filter=new">Nouveautés</a>
                        <a href="<?php echo SITE_URL; ?>pages/catalogue.php?filter=bestseller">Best-sellers</a>
                        <a href="<?php echo SITE_URL; ?>pages/catalogue.php?filter=promo">Promotions</a>
                        <a href="<?php echo SITE_URL; ?>pages/catalogue.php?concentration=Parfum">Parfums Signature</a>
                    </div>
                </div>
                
                <a href="#" class="nav-link"><i class="fas fa-gift"></i> Cadeaux</a>
            </div>

            <!-- Actions utilisateur -->
            <div class="nav-actions">
                <!-- Recherche -->
                <div class="search-container">
                    <button class="search-btn" id="searchToggle">
                        <i class="fas fa-search"></i>
                    </button>
                    <div class="search-box" id="searchBox">
                        <input type="text" placeholder="Rechercher un parfum..." id="searchInput">
                        <button class="search-submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                
                <!-- Compte utilisateur -->
                <div class="user-dropdown">
                    <button class="user-btn">
                        <i class="fas fa-user"></i>
                    </button>
                    <div class="user-dropdown-content">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="<?php echo SITE_URL; ?>pages/compte.php"><i class="fas fa-user-circle"></i> Mon Compte</a>
                            <a href="<?php echo SITE_URL; ?>pages/compte.php?page=orders"><i class="fas fa-shopping-bag"></i> Mes Commandes</a>
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                <a href="<?php echo SITE_URL; ?>pages/admin/"><i class="fas fa-cog"></i> Administration</a>
                            <?php endif; ?>
                            <hr>
                            <a href="<?php echo SITE_URL; ?>pages/deconnexion.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>pages/connexion.php"><i class="fas fa-sign-in-alt"></i> Connexion</a>
                            <a href="<?php echo SITE_URL; ?>pages/inscription.php"><i class="fas fa-user-plus"></i> Inscription</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Panier -->
                <a href="<?php echo SITE_URL; ?>pages/panier.php" class="cart-btn" id="cartBtn">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="cart-count" id="cartCount">
                        <?php
                        // Affiche le nombre d'articles dans le panier
                        $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                        echo $cartCount > 0 ? $cartCount : '0';
                        ?>
                    </span>
                </a>
            </div>
        </div>
        
        <!-- Barre de recherche mobile -->
        <div class="mobile-search" id="mobileSearch">
            <input type="text" placeholder="Rechercher un parfum...">
            <button><i class="fas fa-search"></i></button>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="main-content">