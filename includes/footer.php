<?php
/**
 * Footer commun Éclat d'Or
 * Important : ce fichier ne ferme pas de balise <main>.
 */
?>

<footer class="site-footer">
    <div class="site-footer-top">
        <div class="site-footer-grid">
            <section class="site-footer-column site-footer-brand">
                <a href="<?= SITE_URL ?>index.php" class="site-footer-logo">
                    Éclat d'Or
                </a>

                <p>
                    Depuis 1920, Éclat d'Or crée des bijoux d'exception qui
                    racontent des histoires. Chaque pièce associe tradition,
                    élégance et savoir-faire.
                </p>

                <div class="site-socials">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Pinterest"><i class="fab fa-pinterest-p"></i></a>
                    <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                </div>
            </section>

            <section class="site-footer-column">
                <h2>Navigation</h2>
                <a href="<?= SITE_URL ?>index.php">Accueil</a>
                <a href="<?= SITE_URL ?>pages/catalogue.php">Catalogue</a>
                <a href="<?= SITE_URL ?>pages/catalogue.php?category=Femme">Bijoux femme</a>
                <a href="<?= SITE_URL ?>pages/catalogue.php?category=Homme">Bijoux homme</a>
                <a href="<?= SITE_URL ?>pages/catalogue.php?category=Unisexe">Bijoux unisexe</a>
                <a href="<?= SITE_URL ?>pages/catalogue.php?filter=promo">Promotions</a>
            </section>

            <section class="site-footer-column">
                <h2>Informations</h2>
                <a href="#">À propos de nous</a>
                <a href="#">Livraison et retours</a>
                <a href="#">Conditions générales</a>
                <a href="#">Politique de confidentialité</a>
                <a href="#">FAQ</a>
                <a href="#">Contactez-nous</a>
            </section>

            <section class="site-footer-column site-footer-contact">
                <h2>Contact</h2>
                <p><i class="fas fa-map-marker-alt"></i> Paris, France</p>
                <p><i class="fas fa-phone"></i> 01 23 45 67 89</p>
                <p>
                    <i class="fas fa-envelope"></i>
                    <a href="mailto:contact@eclatdor.fr">contact@eclatdor.fr</a>
                </p>
                <p><i class="fas fa-clock"></i> Lun-Sam : 10h-19h</p>

                <div class="site-newsletter">
                    <h3>Newsletter</h3>
                    <p>Recevez nos nouveautés et offres exclusives.</p>

                    <form class="site-newsletter-form">
                        <input type="email" placeholder="Votre email" required>
                        <button type="submit" aria-label="S'inscrire">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <div class="site-footer-bottom">
        <p>
            &copy; <?= date('Y') ?> Éclat d'Or. Tous droits réservés.
        </p>

        <div class="site-payment-icons" aria-label="Moyens de paiement">
            <i class="fab fa-cc-visa"></i>
            <i class="fab fa-cc-mastercard"></i>
            <i class="fab fa-cc-paypal"></i>
            <i class="fab fa-cc-apple-pay"></i>
        </div>
    </div>
</footer>

<button type="button" class="site-back-to-top" id="siteBackToTop" aria-label="Retour en haut">
    <i class="fas fa-chevron-up"></i>
</button>

<style>
.site-footer {
    position: relative;
    overflow: hidden;
    background: #3a2925;
    color: rgba(255, 255, 255, .78);
}

.site-footer-top {
    width: min(1180px, calc(100% - 40px));
    margin: 0 auto;
    padding: 60px 0 45px;
}

.site-footer-grid {
    display: grid;
    grid-template-columns: 1.5fr repeat(3, 1fr);
    gap: 38px;
}

.site-footer-column {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.site-footer-logo {
    margin-bottom: 14px;
    color: #e8d39a;
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: 29px;
    letter-spacing: 3px;
    text-decoration: none;
    text-transform: uppercase;
}

.site-footer-brand p {
    max-width: 280px;
    margin: 0;
    color: rgba(255, 255, 255, .6);
    font-size: 12px;
    line-height: 1.8;
}

.site-footer-column h2 {
    margin: 0 0 10px;
    color: #e8d39a;
    font-family: Georgia, serif;
    font-size: 17px;
    font-weight: normal;
}

.site-footer-column h3 {
    margin: 20px 0 0;
    color: #e8d39a;
    font-family: Georgia, serif;
    font-size: 16px;
    font-weight: normal;
}

.site-footer-column a {
    color: rgba(255, 255, 255, .67);
    font-size: 12px;
    text-decoration: none;
    transition: color .2s ease, transform .2s ease;
}

.site-footer-column a:hover {
    color: #e8d39a;
    transform: translateX(3px);
}

.site-footer-contact p {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin: 0;
    color: rgba(255, 255, 255, .67);
    font-size: 12px;
    line-height: 1.5;
}

.site-footer-contact p i {
    width: 15px;
    margin-top: 2px;
    color: #c9a227;
    text-align: center;
}

.site-newsletter p {
    margin: 0;
    color: rgba(255, 255, 255, .6);
    font-size: 11px;
    line-height: 1.5;
}

.site-newsletter-form {
    display: flex;
    overflow: hidden;
    margin-top: 8px;
    border: 1px solid rgba(232, 211, 154, .35);
    border-radius: 7px;
}

.site-newsletter-form input {
    flex: 1;
    min-width: 0;
    padding: 10px;
    border: none;
    outline: none;
    background: rgba(255, 255, 255, .1);
    color: #ffffff;
    font-size: 11px;
}

.site-newsletter-form input::placeholder {
    color: rgba(255, 255, 255, .55);
}

.site-newsletter-form button {
    width: 42px;
    border: none;
    background: #c9a227;
    color: #ffffff;
    cursor: pointer;
}

.site-socials {
    display: flex;
    gap: 9px;
    margin-top: 23px;
}

.site-socials a {
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(232, 211, 154, .4);
    border-radius: 50%;
    color: #e8d39a;
    font-size: 12px;
}

.site-socials a:hover {
    background: #c9a227;
    color: #ffffff;
    transform: translateY(-2px);
}

.site-footer-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 18px max(20px, calc((100% - 1180px) / 2));
    border-top: 1px solid rgba(232, 211, 154, .16);
}

.site-footer-bottom p {
    margin: 0;
    color: rgba(255, 255, 255, .5);
    font-size: 10px;
}

.site-payment-icons {
    display: flex;
    gap: 10px;
    color: #e8d39a;
    font-size: 21px;
}

.site-back-to-top {
    position: fixed;
    z-index: 900;
    right: 25px;
    bottom: 25px;
    width: 45px;
    height: 45px;
    display: none;
    border: none;
    border-radius: 50%;
    background: #c9a227;
    color: #ffffff;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(58, 41, 37, .25);
    transition: transform .2s ease, background .2s ease;
}

.site-back-to-top:hover {
    background: #9d7815;
    transform: translateY(-3px);
}

@media (max-width: 800px) {
    .site-footer-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
    }

    .site-footer-bottom {
        align-items: flex-start;
        flex-direction: column;
    }
}

@media (max-width: 520px) {
    .site-footer-top {
        width: calc(100% - 30px);
        padding: 45px 0 35px;
    }

    .site-footer-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const backToTop = document.getElementById('siteBackToTop');

    if (!backToTop) {
        return;
    }

    window.addEventListener('scroll', function () {
        backToTop.style.display = window.scrollY > 300 ? 'flex' : 'none';
        backToTop.style.alignItems = 'center';
        backToTop.style.justifyContent = 'center';
    });

    backToTop.addEventListener('click', function () {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});
</script>

</body>
</html>