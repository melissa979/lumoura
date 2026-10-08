<?php
/**
 * Page Mon compte complète avec style Luxury 3D
 * Éclat d'Or Joaillerie
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    header('Location: ' . SITE_URL . 'pages/connexion.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];

/* --------------------------------------------------------------------------
   Informations utilisateur
-------------------------------------------------------------------------- */
$user = null;

try {
    $stmt = $pdo->prepare(
        "SELECT email, prenom, nom,
                DATE_FORMAT(date_inscription, '%d/%m/%Y') AS date_inscription_format
         FROM utilisateurs
         WHERE id_utilisateur = ?
         LIMIT 1"
    );

    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('compte.php utilisateur : ' . $e->getMessage());
}

$nomComplet = $user
    ? trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))
    : 'Utilisateur';

$email = $user['email'] ?? 'Non défini';
$dateInscription = $user['date_inscription_format'] ?? 'Non défini';

/* --------------------------------------------------------------------------
   Commandes récentes
-------------------------------------------------------------------------- */
$commandes = [];

try {
    $stmt = $pdo->prepare(
        "SELECT id_commande, numero_commande, date_commande, statut, montant
         FROM commandes
         WHERE id_utilisateur = ?
         ORDER BY date_commande DESC
         LIMIT 3"
    );

    $stmt->execute([$userId]);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('compte.php commandes : ' . $e->getMessage());
}

$nbCommandes = count($commandes);

/* --------------------------------------------------------------------------
   Adresses
-------------------------------------------------------------------------- */
$adresses = [];

try {
    $stmt = $pdo->prepare(
        "SELECT id, prenom, nom, adresse, complement_adresse,
                code_postal, ville, pays, telephone, est_principale
         FROM adresses
         WHERE id_utilisateur = ?
         ORDER BY est_principale DESC, id DESC"
    );

    $stmt->execute([$userId]);
    $adresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('compte.php adresses : ' . $e->getMessage());
}

/* --------------------------------------------------------------------------
   Favoris
-------------------------------------------------------------------------- */
$nbFavoris = 0;

try {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM favori WHERE id_utilisateur = ?'
    );

    $stmt->execute([$userId]);
    $nbFavoris = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('compte.php favoris : ' . $e->getMessage());
}

$pageTitle = "Mon compte - Éclat d'Or";
include '../includes/header.php';
?>

<style>
:root {
    --account-brown: #3a2925;
    --account-brown-light: #806c63;
    --account-gold: #c9a227;
    --account-gold-light: #e8d39a;
    --account-gold-dark: #9d7815;
    --account-cream: #fbf6ef;
    --account-border: #eee4d8;
    --account-gray: #77716d;
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
   STRUCTURE PRINCIPALE
======================================================================== */

.account-page {
    position: relative;
    isolation: isolate;
    width: 100vw;
    min-height: calc(100vh - 80px);
    margin-left: calc(50% - 50vw);
    padding: 45px 20px 80px;
    overflow: hidden;
    background:
        radial-gradient(circle at 8% 8%, rgba(201, 162, 39, .16), transparent 28%),
        radial-gradient(circle at 95% 80%, rgba(128, 108, 99, .14), transparent 30%),
        linear-gradient(135deg, #fbf6ef 0%, #f3e8dc 100%);
}

.account-wrapper {
    position: relative;
    z-index: 2;
    width: min(1120px, 100%);
    margin: 0 auto;
}

/* Halos lumineux en arrière-plan */
.account-page::before,
.account-page::after {
    content: "";
    position: absolute;
    z-index: -1;
    border-radius: 50%;
    pointer-events: none;
    filter: blur(4px);
}

.account-page::before {
    width: 380px;
    height: 380px;
    top: 80px;
    left: -180px;
    background: radial-gradient(
        circle,
        rgba(201, 162, 39, .25),
        rgba(201, 162, 39, 0)
    );
    animation: lightFloat 8s ease-in-out infinite;
}

.account-page::after {
    width: 430px;
    height: 430px;
    right: -220px;
    bottom: 80px;
    background: radial-gradient(
        circle,
        rgba(128, 108, 99, .2),
        rgba(128, 108, 99, 0)
    );
    animation: lightFloatReverse 10s ease-in-out infinite;
}

@keyframes lightFloat {
    0%,
    100% {
        transform: translate3d(0, 0, 0) scale(1);
    }

    50% {
        transform: translate3d(45px, 30px, 0) scale(1.14);
    }
}

@keyframes lightFloatReverse {
    0%,
    100% {
        transform: translate3d(0, 0, 0) scale(1);
    }

    50% {
        transform: translate3d(-35px, -25px, 0) scale(1.12);
    }
}

/* ========================================================================
   HERO
======================================================================== */

.account-hero {
    position: relative;
    overflow: hidden;
    margin-bottom: 28px;
    padding: 48px 38px;
    border: 1px solid rgba(201, 162, 39, .2);
    border-radius: 24px;
    background: linear-gradient(135deg, #3a2925, #806c63);
    color: #ffffff;
    box-shadow: 0 16px 38px rgba(58, 41, 37, .16);
    transform-style: preserve-3d;
    transition: transform .5s ease, box-shadow .5s ease;
}

.account-hero:hover {
    transform: translateY(-5px) rotateX(1deg);
    box-shadow:
        0 24px 48px rgba(58, 41, 37, .22),
        0 0 30px rgba(201, 162, 39, .12);
}

.account-hero::before,
.account-hero::after {
    content: "";
    position: absolute;
    border: 1px solid rgba(201, 162, 39, .45);
    border-radius: 50%;
    pointer-events: none;
}

.account-hero::before {
    width: 310px;
    height: 310px;
    top: -175px;
    right: -55px;
}

.account-hero::after {
    width: 190px;
    height: 190px;
    right: 180px;
    bottom: -135px;
}

.account-hero-content {
    position: relative;
    z-index: 2;
}

.account-kicker {
    margin: 0 0 10px;
    color: var(--account-gold-light);
    font-size: 11px;
    font-weight: bold;
    letter-spacing: 3px;
    text-transform: uppercase;
}

.account-hero h1 {
    margin: 0 0 12px;
    color: #ffffff;
    font-family: Georgia, serif;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: normal;
    line-height: 1.15;
    animation: titleAppear .8s ease both;
}

.account-hero p:last-child {
    margin: 0;
    color: rgba(255, 255, 255, .82);
    font-size: 14px;
    animation: subtitleAppear 1s ease .15s both;
}

@keyframes titleAppear {
    from {
        opacity: 0;
        transform: translateY(18px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes subtitleAppear {
    from {
        opacity: 0;
        transform: translateY(12px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ========================================================================
   ORGANISATION
======================================================================== */

.account-layout {
    display: grid;
    grid-template-columns: 285px minmax(0, 1fr);
    gap: 24px;
    align-items: start;
}

.account-cards {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
}

/* ========================================================================
   CARTE PROFIL
======================================================================== */

.profile-card {
    position: relative;
    overflow: hidden;
    padding: 29px 22px;
    border: 1px solid rgba(201, 162, 39, .22);
    border-radius: 19px;
    background: rgba(255, 255, 255, .94);
    box-shadow: 0 10px 28px rgba(58, 41, 37, .09);
    text-align: center;
    transform-style: preserve-3d;
    will-change: transform;
    animation: cardAppear .7s ease both;
    transition: transform .25s ease, box-shadow .3s ease, border-color .3s ease;
}

.profile-card::before,
.account-card::before {
    content: "";
    position: absolute;
    z-index: 3;
    top: 0;
    left: -120%;
    width: 70%;
    height: 100%;
    pointer-events: none;
    background: linear-gradient(
        105deg,
        transparent 20%,
        rgba(255, 255, 255, .58) 50%,
        transparent 80%
    );
    transform: skewX(-18deg);
    transition: left .75s ease;
}

.profile-card:hover::before,
.account-card:hover::before {
    left: 140%;
}

.profile-card:hover,
.account-card:hover {
    border-color: rgba(201, 162, 39, .46);
    box-shadow:
        0 20px 40px rgba(58, 41, 37, .15),
        0 0 25px rgba(201, 162, 39, .1);
}

.profile-avatar {
    position: relative;
    z-index: 4;
    width: 84px;
    height: 84px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    border: 1px solid rgba(201, 162, 39, .4);
    border-radius: 50%;
    background: linear-gradient(145deg, #fffdf9, #f4e8d8);
    box-shadow:
        0 8px 20px rgba(201, 162, 39, .17),
        inset 0 0 0 6px rgba(255, 255, 255, .7);
    transform: translateZ(25px);
    animation: avatarFloat 4s ease-in-out infinite;
}

.profile-avatar i {
    color: var(--account-gold);
    font-size: 48px;
    text-shadow: 0 5px 10px rgba(157, 120, 21, .3);
}

@keyframes avatarFloat {
    0%,
    100% {
        transform: translateZ(25px) translateY(0);
    }

    50% {
        transform: translateZ(25px) translateY(-7px);
    }
}

.profile-card h2 {
    position: relative;
    z-index: 4;
    margin: 0 0 17px;
    color: var(--account-brown);
    font-family: Georgia, serif;
    font-size: 21px;
    font-weight: normal;
    transform: translateZ(18px);
}

.profile-line {
    position: relative;
    z-index: 4;
    display: flex;
    align-items: flex-start;
    gap: 9px;
    margin: 10px 0;
    color: var(--account-gray);
    font-size: 12px;
    line-height: 1.5;
    text-align: left;
    overflow-wrap: anywhere;
    transform: translateZ(13px);
}

.profile-line i {
    width: 16px;
    margin-top: 2px;
    color: var(--account-gold);
    text-align: center;
}

.profile-button,
.card-button {
    position: relative;
    z-index: 4;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: bold;
    text-decoration: none;
    transform: translateZ(18px);
    transition: transform .25s ease, box-shadow .25s ease, background .25s ease;
}

.profile-button {
    width: 100%;
    margin-top: 17px;
    padding: 12px 16px;
    background: linear-gradient(135deg, var(--account-gold), #b88a1d);
    color: #ffffff;
}

.profile-button:hover,
.card-button:hover {
    transform: translateY(-3px) translateZ(22px);
    box-shadow: 0 9px 19px rgba(201, 162, 39, .28);
}

.logout-link {
    position: relative;
    z-index: 4;
    display: block;
    margin-top: 16px;
    color: var(--account-gray);
    font-size: 12px;
    text-decoration: none;
}

.logout-link:hover {
    color: #a52828;
}

/* ========================================================================
   BLOCS DE CONTENU
======================================================================== */

.account-card {
    position: relative;
    min-width: 0;
    overflow: hidden;
    padding: 23px;
    border: 1px solid var(--account-border);
    border-radius: 17px;
    background: rgba(255, 255, 255, .95);
    box-shadow: 0 8px 24px rgba(58, 41, 37, .07);
    transform-style: preserve-3d;
    will-change: transform;
    animation: cardAppear .7s ease both;
    transition: transform .25s ease, box-shadow .3s ease, border-color .3s ease;
}

.account-card:nth-child(1) {
    animation-delay: .1s;
}

.account-card:nth-child(2) {
    animation-delay: .2s;
}

.account-card:nth-child(3) {
    animation-delay: .3s;
}

.account-card-wide {
    grid-column: 1 / -1;
}

@keyframes cardAppear {
    from {
        opacity: 0;
        transform: translateY(25px) scale(.97);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.card-heading {
    position: relative;
    z-index: 4;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 17px;
    padding-bottom: 13px;
    border-bottom: 1px solid var(--account-border);
    transform: translateZ(17px);
}

.card-heading i {
    color: var(--account-gold);
    font-size: 19px;
    text-shadow: 0 4px 9px rgba(157, 120, 21, .28);
}

.card-heading h2 {
    margin: 0;
    color: var(--account-brown);
    font-family: Georgia, serif;
    font-size: 19px;
    font-weight: normal;
}

.empty-message {
    position: relative;
    z-index: 4;
    margin: 15px 0;
    color: #9a928c;
    font-size: 13px;
    font-style: italic;
}

.card-button {
    padding: 10px 15px;
    border: 1px solid var(--account-gold);
    background: #ffffff;
    color: var(--account-gold-dark);
}

/* ========================================================================
   COMMANDES
======================================================================== */

.order-line {
    position: relative;
    z-index: 4;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 11px 0;
    border-bottom: 1px solid #f5efe8;
    transform: translateZ(12px);
}

.order-line:last-child {
    border-bottom: none;
}

.order-reference {
    display: block;
    color: var(--account-brown);
    font-size: 12px;
    font-weight: bold;
}

.order-date {
    display: block;
    margin-top: 4px;
    color: #aaa19a;
    font-size: 11px;
}

.order-right {
    display: flex;
    align-items: flex-end;
    flex-direction: column;
    gap: 5px;
}

.order-status {
    padding: 4px 8px;
    border-radius: 20px;
    background: #fff3cd;
    color: #856404;
    font-size: 10px;
    font-weight: bold;
}

.order-amount {
    color: var(--account-gold-dark);
    font-size: 13px;
    font-weight: bold;
}

/* ========================================================================
   ADRESSES
======================================================================== */

.address-item {
    position: relative;
    z-index: 4;
    margin-bottom: 12px;
    padding: 13px;
    border: 1px solid #f0e8de;
    border-radius: 10px;
    color: #655d58;
    font-size: 12px;
    line-height: 1.7;
    transform: translateZ(10px);
}

.address-item:last-of-type {
    margin-bottom: 16px;
}

.address-item p {
    margin: 0;
}

.address-item strong {
    color: var(--account-brown);
}

.main-address {
    display: inline-block;
    margin-bottom: 6px;
    padding: 3px 8px;
    border-radius: 15px;
    background: var(--account-gold);
    color: #ffffff;
    font-size: 10px;
    font-weight: bold;
}

/* ========================================================================
   FAVORIS
======================================================================== */

.favorites-summary {
    position: relative;
    z-index: 4;
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 18px;
    color: var(--account-gray);
    font-size: 13px;
    transform: translateZ(15px);
}

.favorite-number {
    color: var(--account-gold);
    font-family: Georgia, serif;
    font-size: 40px;
    text-shadow:
        0 4px 10px rgba(201, 162, 39, .3),
        0 0 18px rgba(201, 162, 39, .18);
}

/* ========================================================================
   RESPONSIVE
======================================================================== */

@media (max-width: 850px) {
    .account-layout {
        grid-template-columns: 1fr;
    }

    .profile-card {
        max-width: 500px;
        margin: 0 auto;
    }
}

@media (max-width: 570px) {
    .account-page {
        padding: 25px 12px 55px;
    }

    .account-hero {
        padding: 34px 23px;
    }

    .account-cards {
        grid-template-columns: 1fr;
    }

    .account-card-wide {
        grid-column: auto;
    }

    .order-line {
        align-items: flex-start;
        flex-direction: column;
    }

    .order-right {
        align-items: flex-start;
        flex-direction: row;
    }

    .account-hero:hover {
        transform: translateY(-3px);
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
</style>

<main class="account-page">
    <div class="account-wrapper">
        <section class="account-hero">
            <div class="account-hero-content">
                <p class="account-kicker">Espace personnel</p>
                <h1>Mon compte</h1>
                <p>
                    Bienvenue,
                    <strong><?= htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8') ?></strong>
                </p>
            </div>
        </section>

        <div class="account-layout">
            <aside class="profile-card">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>

                <h2><?= htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8') ?></h2>

                <p class="profile-line">
                    <i class="fas fa-envelope"></i>
                    <span><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></span>
                </p>

                <p class="profile-line">
                    <i class="fas fa-calendar-alt"></i>
                    <span>
                        Inscrit le <?= htmlspecialchars($dateInscription, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </p>

                <a
                    href="<?= SITE_URL ?>pages/modifier_profil.php"
                    class="profile-button"
                >
                    <i class="fas fa-edit"></i>
                    Modifier mon profil
                </a>

                <a
                    href="<?= SITE_URL ?>pages/deconnexion.php"
                    class="logout-link"
                >
                    <i class="fas fa-sign-out-alt"></i>
                    Se déconnecter
                </a>
            </aside>

            <section class="account-cards">
                <article class="account-card">
                    <div class="card-heading">
                        <i class="fas fa-shopping-bag"></i>
                        <h2>Mes commandes</h2>
                    </div>

                    <?php if ($nbCommandes > 0): ?>
                        <?php foreach ($commandes as $commande): ?>
                            <?php
                            $statut = (string) ($commande['statut'] ?? 'En attente');
                            $statutClasse = strtolower(
                                preg_replace('/[^a-zA-Z0-9_-]/', '-', $statut)
                            );
                            ?>

                            <div class="order-line">
                                <div>
                                    <span class="order-reference">
                                        <?= htmlspecialchars($commande['numero_commande'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span class="order-date">
                                        <?= date('d/m/Y', strtotime($commande['date_commande'])) ?>
                                    </span>
                                </div>

                                <div class="order-right">
                                    <span class="order-status <?= htmlspecialchars($statutClasse, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(ucfirst($statut), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <span class="order-amount">
                                        <?= formatPrice($commande['montant']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <a
                            href="<?= SITE_URL ?>pages/commandes.php"
                            class="card-button"
                        >
                            Voir toutes mes commandes
                        </a>
                    <?php else: ?>
                        <p class="empty-message">
                            Vous n'avez pas encore passé de commande.
                        </p>

                        <a
                            href="<?= SITE_URL ?>pages/catalogue.php"
                            class="card-button"
                        >
                            Découvrir la collection
                        </a>
                    <?php endif; ?>
                </article>

                <article class="account-card">
                    <div class="card-heading">
                        <i class="fas fa-map-marker-alt"></i>
                        <h2>Mes adresses</h2>
                    </div>

                    <?php if (!empty($adresses)): ?>
                        <?php foreach ($adresses as $adresse): ?>
                            <div class="address-item">
                                <?php if (!empty($adresse['est_principale'])): ?>
                                    <span class="main-address">
                                        Adresse principale
                                    </span>
                                <?php endif; ?>

                                <p>
                                    <strong>
                                        <?= htmlspecialchars(
                                            trim(
                                                ($adresse['prenom'] ?? '') . ' ' .
                                                ($adresse['nom'] ?? '')
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong><br>

                                    <?= htmlspecialchars($adresse['adresse'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>

                                    <?php if (!empty($adresse['complement_adresse'])): ?>
                                        <?= htmlspecialchars($adresse['complement_adresse'], ENT_QUOTES, 'UTF-8') ?><br>
                                    <?php endif; ?>

                                    <?= htmlspecialchars(
                                        ($adresse['code_postal'] ?? '') . ' ' .
                                        ($adresse['ville'] ?? ''),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?><br>

                                    <?= htmlspecialchars(
                                        $adresse['pays'] ?? 'France',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-message">
                            Aucune adresse enregistrée.
                        </p>
                    <?php endif; ?>

                    <a
                        href="<?= SITE_URL ?>pages/ajouter_adresse.php"
                        class="card-button"
                    >
                        <i class="fas fa-plus"></i>
                        Ajouter une adresse
                    </a>
                </article>

                <article class="account-card account-card-wide">
                    <div class="card-heading">
                        <i class="fas fa-heart"></i>
                        <h2>Ma liste d'envies</h2>
                    </div>

                    <div class="favorites-summary">
                        <span class="favorite-number">
                            <?= $nbFavoris ?>
                        </span>
                        <span>
                            bijou<?= $nbFavoris > 1 ? 'x' : '' ?>
                            sauvegardé<?= $nbFavoris > 1 ? 's' : '' ?>
                        </span>
                    </div>

                    <a
                        href="<?= SITE_URL ?>pages/liste_envies.php"
                        class="card-button"
                    >
                        <i class="fas fa-heart"></i>
                        Voir mes favoris
                    </a>
                </article>
            </section>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cards = document.querySelectorAll(
        '.profile-card, .account-card'
    );

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
                'deg) rotateY(' + rotateY + 'deg) translateY(-5px)';
        });

        card.addEventListener('mouseleave', function () {
            card.style.transform =
                'perspective(900px) rotateX(0deg) rotateY(0deg) translateY(0)';
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>