<?php
// Inclusion du header si tu en as un
// include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadeaux - Éclat d'Or | Lumoura Joaillerie</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Raleway:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --or:        #C9A84C;
            --or-clair:  #E2C97E;
            --or-fonce:  #A07830;
            --brun:      #3B2A1A;
            --brun-clair:#5C3D2E;
            --beige:     #F5F0E8;
            --beige-sec: #EDE5D0;
            --blanc:     #FFFFFF;
            --texte:     #2C1A0E;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Raleway', sans-serif;
            background: var(--beige);
            color: var(--texte);
        }

        /* ─── NAVBAR (cohérente avec ton site) ─── */
        nav {
            background: var(--blanc);
            padding: 18px 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--texte);
            letter-spacing: 2px;
            text-decoration: none;
        }
        .nav-links { display: flex; gap: 36px; list-style: none; }
        .nav-links a {
            font-size: 0.88rem;
            font-weight: 500;
            letter-spacing: 1px;
            color: var(--texte);
            text-decoration: none;
            text-transform: uppercase;
            transition: color .2s;
        }
        .nav-links a:hover,
        .nav-links a.active { color: var(--or); }
        .nav-links a.active {
            border-bottom: 2px solid var(--or);
            padding-bottom: 2px;
        }
        .nav-icons { display: flex; align-items: center; gap: 20px; }
        .nav-icons a { color: var(--texte); font-size: 1.1rem; text-decoration: none; transition: color .2s; }
        .nav-icons a:hover { color: var(--or); }
        .cart-badge {
            position: relative;
        }
        .cart-badge span {
            position: absolute;
            top: -8px; right: -8px;
            background: var(--or);
            color: #fff;
            font-size: 0.65rem;
            width: 17px; height: 17px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
        }

        /* ─── HERO BANNIÈRE ─── */
        .hero-cadeaux {
            background: linear-gradient(135deg, var(--brun) 0%, var(--brun-clair) 50%, var(--or-fonce) 100%);
            padding: 90px 60px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero-cadeaux::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="40" fill="none" stroke="rgba(201,168,76,0.08)" stroke-width="1"/><circle cx="80" cy="80" r="60" fill="none" stroke="rgba(201,168,76,0.05)" stroke-width="1"/></svg>');
            background-size: cover;
        }
        .hero-cadeaux h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.2rem;
            color: var(--or-clair);
            letter-spacing: 3px;
            position: relative;
            margin-bottom: 16px;
        }
        .hero-cadeaux p {
            color: rgba(255,255,255,0.8);
            font-size: 1.05rem;
            font-weight: 300;
            letter-spacing: 1px;
            position: relative;
            max-width: 520px;
            margin: 0 auto 32px;
            line-height: 1.7;
        }
        .divider-or {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin: 30px 0;
        }
        .divider-or::before,
        .divider-or::after {
            content: '';
            width: 80px;
            height: 1px;
            background: var(--or);
            opacity: 0.6;
        }
        .divider-or i { color: var(--or); font-size: 1rem; }

        /* ─── SECTION TITRE ─── */
        .section-title {
            text-align: center;
            padding: 60px 20px 10px;
        }
        .section-title h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--texte);
        }
        .section-title .line-deco {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 12px;
        }
        .section-title .line-deco::before,
        .section-title .line-deco::after {
            content: '';
            width: 60px;
            height: 1px;
            background: var(--or);
        }
        .section-title .line-deco i { color: var(--or); }

        /* ─── CARTES COFFRETS CADEAUX ─── */
        .coffrets-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            max-width: 1200px;
            margin: 40px auto 60px;
            padding: 0 40px;
        }
        .coffret-card {
            background: var(--blanc);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: transform .3s ease, box-shadow .3s ease;
            cursor: pointer;
        }
        .coffret-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.14);
        }
        .coffret-img {
            height: 220px;
            position: relative;
            overflow: hidden;
        }
        .coffret-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .5s ease;
        }
        .coffret-card:hover .coffret-img img {
            transform: scale(1.05);
        }
        .coffret-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            background: var(--or);
            color: #fff;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 5px 12px;
            border-radius: 20px;
        }
        .coffret-body {
            padding: 22px 24px;
        }
        .coffret-body .label {
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--or);
            margin-bottom: 6px;
        }
        .coffret-body h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.25rem;
            color: var(--texte);
            margin-bottom: 8px;
        }
        .coffret-body p {
            font-size: 0.85rem;
            color: #777;
            line-height: 1.6;
            margin-bottom: 16px;
        }
        .coffret-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 24px;
            border-top: 1px solid var(--beige-sec);
        }
        .prix {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: var(--brun);
            font-weight: 700;
        }
        .btn-ajouter {
            background: var(--brun);
            color: #fff;
            border: none;
            padding: 9px 18px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: background .25s;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .btn-ajouter:hover { background: var(--or-fonce); }

        /* ─── CARTE CADEAU SECTION ─── */
        .carte-cadeau-section {
            background: var(--brun);
            padding: 70px 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 60px;
            max-width: 100%;
        }
        .carte-cadeau-text { flex: 1; }
        .carte-cadeau-text h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: var(--or-clair);
            margin-bottom: 16px;
        }
        .carte-cadeau-text p {
            color: rgba(255,255,255,0.75);
            font-size: 0.95rem;
            line-height: 1.8;
            max-width: 440px;
            margin-bottom: 28px;
        }
        .btn-or {
            background: var(--or);
            color: var(--brun);
            border: none;
            padding: 14px 34px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: background .25s, transform .2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-or:hover {
            background: var(--or-clair);
            transform: translateY(-2px);
        }
        .carte-cadeau-visuel {
            flex: 0 0 340px;
        }
        .carte-visuel {
            background: linear-gradient(135deg, var(--or) 0%, var(--or-clair) 50%, var(--or-fonce) 100%);
            border-radius: 16px;
            padding: 36px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        .carte-visuel .cv-logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            color: var(--brun);
            letter-spacing: 3px;
            margin-bottom: 24px;
            font-weight: 700;
        }
        .carte-visuel .cv-amount {
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: var(--brun);
            font-weight: 700;
            margin-bottom: 10px;
        }
        .carte-visuel .cv-sub {
            font-size: 0.75rem;
            color: rgba(59,42,26,0.7);
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .carte-visuel .cv-deco {
            width: 60px;
            height: 1px;
            background: var(--brun);
            opacity: 0.3;
            margin: 18px auto;
        }
        .carte-visuel .cv-tagline {
            font-style: italic;
            font-size: 0.8rem;
            color: var(--brun);
            opacity: 0.8;
        }

        /* ─── MONTANTS CARTES ─── */
        .montants-section {
            background: var(--beige-sec);
            padding: 60px 40px;
            text-align: center;
        }
        .montants-section h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--texte);
            margin-bottom: 30px;
        }
        .montants-grid {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        .montant-btn {
            background: var(--blanc);
            border: 2px solid var(--beige-sec);
            padding: 16px 32px;
            border-radius: 8px;
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            color: var(--texte);
            cursor: pointer;
            transition: all .25s;
        }
        .montant-btn:hover,
        .montant-btn.selected {
            border-color: var(--or);
            background: var(--or);
            color: var(--blanc);
        }
        .montant-custom {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 10px;
        }
        .montant-custom input {
            border: 2px solid var(--beige-sec);
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Raleway', sans-serif;
            width: 180px;
            outline: none;
            transition: border-color .2s;
        }
        .montant-custom input:focus { border-color: var(--or); }

        /* ─── POURQUOI OFFRIR ─── */
        .pourquoi-section {
            padding: 70px 40px;
            text-align: center;
            background: var(--blanc);
        }
        .pourquoi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            max-width: 1100px;
            margin: 40px auto 0;
        }
        .pourquoi-card {
            padding: 30px 20px;
        }
        .pourquoi-card .icon-wrap {
            width: 64px;
            height: 64px;
            background: var(--beige);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
        }
        .pourquoi-card i {
            font-size: 1.4rem;
            color: var(--or);
        }
        .pourquoi-card h4 {
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            color: var(--texte);
            margin-bottom: 8px;
        }
        .pourquoi-card p {
            font-size: 0.82rem;
            color: #888;
            line-height: 1.6;
        }

        /* ─── FOOTER ─── */
        footer {
            background: var(--brun);
            color: rgba(255,255,255,0.75);
            padding: 60px 60px 30px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: var(--or-clair);
            letter-spacing: 2px;
            margin-bottom: 14px;
        }
        .footer-about {
            font-size: 0.82rem;
            line-height: 1.7;
            margin-bottom: 18px;
        }
        .footer-social a {
            color: rgba(255,255,255,0.6);
            font-size: 1rem;
            margin-right: 14px;
            transition: color .2s;
        }
        .footer-social a:hover { color: var(--or); }
        .footer-col h4 {
            font-size: 0.8rem;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--or);
            margin-bottom: 18px;
        }
        .footer-col ul { list-style: none; }
        .footer-col ul li { margin-bottom: 10px; }
        .footer-col ul a {
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            font-size: 0.82rem;
            transition: color .2s;
        }
        .footer-col ul a:hover { color: var(--or); }
        .footer-contact p {
            font-size: 0.82rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .footer-contact i { color: var(--or); width: 14px; }
        .footer-newsletter {
            display: flex;
            margin-top: 14px;
        }
        .footer-newsletter input {
            flex: 1;
            padding: 10px 14px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            color: #fff;
            font-size: 0.8rem;
            border-radius: 4px 0 0 4px;
            outline: none;
        }
        .footer-newsletter button {
            background: var(--or);
            border: none;
            padding: 10px 14px;
            color: var(--brun);
            cursor: pointer;
            border-radius: 0 4px 4px 0;
            font-weight: 700;
        }
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            text-align: center;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.4);
        }

        /* ─── ANIMATIONS ─── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .coffret-card { animation: fadeInUp .5s ease both; }
        .coffret-card:nth-child(2) { animation-delay: .1s; }
        .coffret-card:nth-child(3) { animation-delay: .2s; }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 900px) {
            .coffrets-grid { grid-template-columns: 1fr 1fr; }
            .pourquoi-grid { grid-template-columns: 1fr 1fr; }
            .carte-cadeau-section { flex-direction: column; padding: 50px 30px; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 600px) {
            .coffrets-grid { grid-template-columns: 1fr; }
            .pourquoi-grid { grid-template-columns: 1fr 1fr; }
            nav { padding: 14px 20px; }
            .hero-cadeaux { padding: 60px 20px; }
            .hero-cadeaux h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>

<!-- ─── NAVBAR ─── -->
<nav>
    <a href="../index.php" class="logo">ÉCLAT D'OR</a>
    <ul class="nav-links">
        <li><a href="../index.php">Accueil</a></li>
        <li><a href="catalogue.php">Catalogue</a></li>
        <li><a href="femme.php">Femme</a></li>
        <li><a href="homme.php">Homme</a></li>
        <li><a href="unisexe.php">Unisexe</a></li>
        <li><a href="collections.php">Collections</a></li>
        <li><a href="cadeaux.php" class="active">Cadeaux</a></li>
    </ul>
    <div class="nav-icons">
        <a href="#"><i class="fas fa-search"></i></a>
        <a href="profil.php"><i class="fas fa-user"></i></a>
        <a href="panier.php" class="cart-badge">
            <i class="fas fa-shopping-bag"></i>
            <span>0</span>
        </a>
    </div>
</nav>

<!-- ─── HERO ─── -->
<section class="hero-cadeaux">
    <h1>L'Art d'Offrir</h1>
    <div class="divider-or"><i class="fas fa-gem"></i></div>
    <p>Offrez un bijou d'exception ou une carte cadeau Éclat d'Or — le cadeau parfait pour chaque occasion précieuse.</p>
    <a href="#coffrets" class="btn-or">Découvrir nos coffrets</a>
</section>

<!-- ─── COFFRETS CADEAUX ─── -->
<div class="section-title" id="coffrets">
    <h2>Nos Coffrets Cadeaux</h2>
    <div class="line-deco"><i class="fas fa-gem"></i></div>
</div>

<div class="coffrets-grid">
    <!-- Coffret 1 -->
    <div class="coffret-card">
        <div class="coffret-img">
            <img src="https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=500&q=80" alt="Coffret Élégance">
            <span class="coffret-badge">Populaire</span>
        </div>
        <div class="coffret-body">
            <div class="label">Pour Elle</div>
            <h3>Coffret Élégance</h3>
            <p>Un collier délicat et des boucles d'oreilles assorties, présentés dans un écrin en velours bordeaux.</p>
        </div>
        <div class="coffret-footer">
            <span class="prix">290 €</span>
            <button class="btn-ajouter"><i class="fas fa-shopping-bag"></i> Ajouter</button>
        </div>
    </div>

    <!-- Coffret 2 -->
    <div class="coffret-card">
        <div class="coffret-img">
            <img src="https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=500&q=80" alt="Coffret Prestige">
            <span class="coffret-badge">Best-seller</span>
        </div>
        <div class="coffret-body">
            <div class="label">Unisexe</div>
            <h3>Coffret Prestige</h3>
            <p>Une montre raffinée et un bracelet en or 18K — alliance de luxe et d'intemporalité pour les amateurs.</p>
        </div>
        <div class="coffret-footer">
            <span class="prix">850 €</span>
            <button class="btn-ajouter"><i class="fas fa-shopping-bag"></i> Ajouter</button>
        </div>
    </div>

    <!-- Coffret 3 -->
    <div class="coffret-card">
        <div class="coffret-img">
            <img src="https://images.unsplash.com/photo-1573408301185-9519f94816b5?w=500&q=80" alt="Coffret Amour">
        </div>
        <div class="coffret-body">
            <div class="label">Couple</div>
            <h3>Coffret Amour</h3>
            <p>Deux alliances assorties symbolisant l'union éternelle, gravées à votre prénom sur demande.</p>
        </div>
        <div class="coffret-footer">
            <span class="prix">1 200 €</span>
            <button class="btn-ajouter"><i class="fas fa-shopping-bag"></i> Ajouter</button>
        </div>
    </div>

    <!-- Coffret 4 -->
    <div class="coffret-card">
        <div class="coffret-img">
            <img src="https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=500&q=80" alt="Coffret Diamant">
            <span class="coffret-badge">Nouveau</span>
        </div>
        <div class="coffret-body">
            <div class="label">Pour Elle</div>
            <h3>Coffret Diamant</h3>
            <p>Bague solitaire diamant dans un écrin exclusif — un geste inoubliable pour les grandes occasions.</p>
        </div>
        <div class="coffret-footer">
            <span class="prix">2 400 €</span>
            <button class="btn-ajouter"><i class="fas fa-shopping-bag"></i> Ajouter</button>
        </div>
    </div>

    <!-- Coffret 5 -->
    <div class="coffret-card">
        <div class="coffret-img">
            <img src="https://images.unsplash.com/photo-1611591437281-460bfbe1220a?w=500&q=80" alt="Coffret Gentleman">
        </div>
        <div class="coffret-body">
            <div class="label">Pour Lui</div>
            <h3>Coffret Gentleman</h3>
            <p>Manchettes et chevalière en or blanc — l'élégance masculine dans toute sa splendeur.</p>
        </div>
        <div class="coffret-footer">
            <span class="prix">620 €</span>
            <button class="btn-ajouter"><i class="fas fa-shopping-bag"></i> Ajouter</button>
        </div>
    </div>

    <!-- Coffret 6 -->
    <div class="coffret-card">
        <div class="coffret-img">
            <img src="https://images.unsplash.com/photo-1602751584552-8ba73aad10e1?w=500&q=80" alt="Coffret Naissance">
            <span class="coffret-badge">Tendance</span>
        </div>
        <div class="coffret-body">
            <div class="label">Naissance</div>
            <h3>Coffret Naissance</h3>
            <p>Bracelet et médaille gravée pour bébé — un souvenir précieux pour les premiers instants de vie.</p>
        </div>
        <div class="coffret-footer">
            <span class="prix">180 €</span>
            <button class="btn-ajouter"><i class="fas fa-shopping-bag"></i> Ajouter</button>
        </div>
    </div>
</div>

<!-- ─── CARTE CADEAU ─── -->
<section class="carte-cadeau-section">
    <div class="carte-cadeau-text">
        <h2>La Carte Cadeau Éclat d'Or</h2>
        <p>Laissez vos proches choisir le bijou de leurs rêves. Notre carte cadeau est disponible de 50 € à 5 000 €, valable 12 mois sur toute la boutique.</p>
        <a href="#montants" class="btn-or">Choisir un montant</a>
    </div>
    <div class="carte-cadeau-visuel">
        <div class="carte-visuel">
            <div class="cv-logo">ÉCLAT D'OR</div>
            <div class="cv-deco"></div>
            <div class="cv-amount">250 €</div>
            <div class="cv-sub">Carte Cadeau</div>
            <div class="cv-deco"></div>
            <div class="cv-tagline">L'art de donner le meilleur</div>
        </div>
    </div>
</section>

<!-- ─── MONTANTS ─── -->
<section class="montants-section" id="montants">
    <h3>Choisissez votre montant</h3>
    <div class="montants-grid">
        <button class="montant-btn" onclick="selectMontant(this)">50 €</button>
        <button class="montant-btn" onclick="selectMontant(this)">100 €</button>
        <button class="montant-btn selected" onclick="selectMontant(this)">250 €</button>
        <button class="montant-btn" onclick="selectMontant(this)">500 €</button>
        <button class="montant-btn" onclick="selectMontant(this)">1 000 €</button>
    </div>
    <div class="montant-custom">
        <input type="number" placeholder="Montant personnalisé (€)" min="50" max="5000" id="montantInput">
        <button class="btn-or" onclick="ajouterCarte()">Ajouter au panier</button>
    </div>
</section>

<!-- ─── POURQUOI OFFRIR ─── -->
<section class="pourquoi-section">
    <div class="section-title">
        <h2>Pourquoi offrir Éclat d'Or ?</h2>
        <div class="line-deco"><i class="fas fa-gem"></i></div>
    </div>
    <div class="pourquoi-grid">
        <div class="pourquoi-card">
            <div class="icon-wrap"><i class="fas fa-gift"></i></div>
            <h4>Emballage Luxe</h4>
            <p>Chaque commande arrive dans un écrin en velours avec ruban doré et carte personnalisée.</p>
        </div>
        <div class="pourquoi-card">
            <div class="icon-wrap"><i class="fas fa-pen-fancy"></i></div>
            <h4>Message Personnalisé</h4>
            <p>Ajoutez un message manuscrit ou une gravure sur le bijou pour un souvenir unique.</p>
        </div>
        <div class="pourquoi-card">
            <div class="icon-wrap"><i class="fas fa-truck"></i></div>
            <h4>Livraison Express</h4>
            <p>Livraison sécurisée sous 24-48h, avec suivi en temps réel jusqu'à votre porte.</p>
        </div>
        <div class="pourquoi-card">
            <div class="icon-wrap"><i class="fas fa-undo"></i></div>
            <h4>Retour 30 Jours</h4>
            <p>Satisfaction garantie — retour gratuit sous 30 jours si le bijou ne convient pas.</p>
        </div>
    </div>
</section>

<!-- ─── FOOTER ─── -->
<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-logo">ÉCLAT D'OR</div>
            <p class="footer-about">Depuis 1920, Éclat d'Or crée des bijoux d'exception qui racontent des histoires. Chaque pièce est une œuvre d'art, mêlant tradition et innovation.</p>
            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-pinterest-p"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h4>Navigation</h4>
            <ul>
                <li><a href="../index.php">Accueil</a></li>
                <li><a href="catalogue.php">Catalogue</a></li>
                <li><a href="femme.php">Bijoux Femme</a></li>
                <li><a href="homme.php">Bijoux Homme</a></li>
                <li><a href="unisexe.php">Bijoux Unisexe</a></li>
                <li><a href="cadeaux.php">Promotions</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Informations</h4>
            <ul>
                <li><a href="#">À propos de nous</a></li>
                <li><a href="#">Livraison et retours</a></li>
                <li><a href="#">Conditions générales</a></li>
                <li><a href="#">Politique de confidentialité</a></li>
                <li><a href="#">FAQ</a></li>
                <li><a href="#">Contactez-nous</a></li>
            </ul>
        </div>
        <div class="footer-col footer-contact">
            <h4>Contact</h4>
            <p><i class="fas fa-map-marker-alt"></i> 123 Avenue des Champs-Élysées, 75008 Paris</p>
            <p><i class="fas fa-phone"></i> 01 23 45 67 89</p>
            <p><i class="fas fa-envelope"></i> contact@eclatdor.fr</p>
            <p><i class="fas fa-clock"></i> Lun-Sam 10h-19h</p>
            <div class="footer-newsletter">
                <input type="email" placeholder="Votre email">
                <button><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>© 2026 Éclat d'Or — Tous droits réservés | Site créé avec passion pour les bijoux d'exception.</p>
    </div>
</footer>

<script>
    // Sélection du montant
    function selectMontant(btn) {
        document.querySelectorAll('.montant-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        const montant = btn.textContent.replace(' €', '').replace(' ', '');
        document.getElementById('montantInput').value = montant;
        document.querySelector('.cv-amount').textContent = btn.textContent;
    }

    // Mise à jour du visuel carte selon montant custom
    document.getElementById('montantInput').addEventListener('input', function() {
        if (this.value) {
            document.querySelector('.cv-amount').textContent = this.value + ' €';
            document.querySelectorAll('.montant-btn').forEach(b => b.classList.remove('selected'));
        }
    });

    // Ajout au panier
    function ajouterCarte() {
        const montant = document.getElementById('montantInput').value;
        if (!montant || montant < 50) {
            alert('Veuillez sélectionner ou entrer un montant minimum de 50 €');
            return;
        }
        alert('Carte cadeau de ' + montant + ' € ajoutée au panier !');
        // Ici tu peux ajouter ta logique PHP/AJAX pour le panier
    }

    // Ajout au panier pour les coffrets
    document.querySelectorAll('.btn-ajouter').forEach(btn => {
        btn.addEventListener('click', function() {
            const nom = this.closest('.coffret-card').querySelector('h3').textContent;
            const prix = this.closest('.coffret-card').querySelector('.prix').textContent;
            this.innerHTML = '<i class="fas fa-check"></i> Ajouté !';
            this.style.background = '#2e7d32';
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-shopping-bag"></i> Ajouter';
                this.style.background = '';
            }, 2000);
        });
    });
</script>

</body>
</html>