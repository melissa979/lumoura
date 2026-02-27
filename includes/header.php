<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Lumoura - Joaillerie de Luxe</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Ton CSS principal -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>assets/images/logo.png">
</head>
<body>

    <!-- Navigation principale -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand fw-bold text-dark" href="<?php echo SITE_URL; ?>index.php">
                <span class="logo-text">ECLAT D'OR</span>
                <small class="d-block text-muted fs-6"></small>
            </a>

            <!-- Bouton mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>pages/catalogue.php">Catalogue</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>pages/catalogue.php?category=Femme">Femme</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>pages/catalogue.php?category=Homme">Homme</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>pages/catalogue.php?category=Unisexe">Unisexe</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="collectionsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Collections
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="collectionsDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>pages/catalogue.php?filter=new">Nouveautés</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>pages/catalogue.php?filter=bestseller">Best-sellers</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>pages/catalogue.php?filter=promo">Promotions</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>pages/cadeaux.php">Cadeaux</a>
                    </li>
                </ul>

                <!-- Barre de recherche + actions -->
                <div class="d-flex align-items-center ms-4">
                    <!-- Recherche -->
                    <form method="GET" action="<?php echo SITE_URL; ?>pages/recherche.php" class="d-flex me-3">
                        <div class="input-group" style="width: 280px;">
                            <input type="text" name="q" class="form-control rounded-pill border-0 bg-light" placeholder="Rechercher un bijou, bague, collier..." aria-label="Rechercher">
                            <button class="btn btn-outline-secondary rounded-pill ms-2" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Icônes utilisateur et panier -->
                    <div class="d-flex align-items-center">
                        <a href="<?php echo SITE_URL; ?>pages/compte.php" class="text-dark me-3">
                            <i class="fas fa-user fs-5"></i>
                        </a>
                        <a href="<?php echo SITE_URL; ?>pages/panier.php" class="text-dark position-relative">
                            <i class="fas fa-shopping-bag fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Espace pour que le contenu ne soit pas caché sous la navbar fixe -->
    <div style="height: 80px;"></div>

    <!-- Contenu principal -->
    <main class="main-content">