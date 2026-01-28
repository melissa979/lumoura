</main>
<!-- Fin du contenu principal -->

<!-- Pied de page -->
<footer class="footer">
    <div class="footer-top">
        <div class="footer-grid">
            <!-- Colonne 1 : Logo et description -->
            <div class="footer-column">
                <div class="footer-logo">ÉCLAT D'OR</div>
                <p class="footer-description">
                    Depuis 1920, Éclat d'Or crée des bijoux d'exception qui racontent des histoires.
                    Chaque pièce est une œuvre d'art, mêlant tradition et innovation.
                </p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-pinterest-p"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>

            <!-- Colonne 2 : Navigation -->
            <div class="footer-column">
                <h3 class="footer-title">Navigation</h3>
                <div class="footer-links">
                    <a href="<?php echo SITE_URL; ?>">Accueil</a>
                    <a href="<?php echo SITE_URL; ?>pages/catalogue.php">Catalogue</a>
                    <a href="<?php echo SITE_URL; ?>pages/catalogue.php?category=femme">Bijoux Femme</a>
                    <a href="<?php echo SITE_URL; ?>pages/catalogue.php?category=homme">Bijoux Homme</a>
                    <a href="<?php echo SITE_URL; ?>pages/catalogue.php?category=unisexe">Bijoux Unisexe</a>
                    <a href="<?php echo SITE_URL; ?>pages/catalogue.php?filter=promo">Promotions</a>
                </div>
            </div>

            <!-- Colonne 3 : Informations -->
            <div class="footer-column">
                <h3 class="footer-title">Informations</h3>
                <div class="footer-links">
                    <a href="#">À propos de nous</a>
                    <a href="#">Livraison et retours</a>
                    <a href="#">Conditions générales</a>
                    <a href="#">Politique de confidentialité</a>
                    <a href="#">FAQ</a>
                    <a href="#">Contactez-nous</a>
                </div>
            </div>

            <!-- Colonne 4 : Contact -->
            <div class="footer-column">
                <h3 class="footer-title">Contact</h3>
                <div class="contact-info">
                    <p><i class="fas fa-map-marker-alt"></i> 123 Avenue des Champs-Élysées, 75008 Paris</p>
                    <p><i class="fas fa-phone"></i> 01 23 45 67 89</p>
                    <p><i class="fas fa-envelope"></i> contact@eclatdor.fr</p>
                    <p><i class="fas fa-clock"></i> Lun-Sam: 10h-19h</p>
                </div>

                <!-- Newsletter -->
                <div class="newsletter-form">
                    <h4>Newsletter</h4>
                    <p>Inscrivez-vous pour recevoir nos offres exclusives sur les bijoux</p>
                    <form class="newsletter-subscribe">
                        <input type="email" placeholder="Votre email" required>
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> Éclat d'Or - Tous droits réservés. | Site créé avec passion pour les bijoux d'exception.</p>
        <div class="payment-methods">
            <i class="fab fa-cc-visa"></i>
            <i class="fab fa-cc-mastercard"></i>
            <i class="fab fa-cc-paypal"></i>
            <i class="fab fa-cc-apple-pay"></i>
            <i class="fab fa-cc-amazon-pay"></i>
        </div>
    </div>
</footer>

<!-- Bouton retour en haut -->
<button class="back-to-top" id="backToTop">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- Scripts JavaScript -->
<script src="<?php echo SITE_URL; ?>assets/js/script.js"></script>

<script>
// Bouton retour en haut
const backToTop = document.getElementById('backToTop');

window.addEventListener('scroll', function() {
    if (window.pageYOffset > 300) {
        backToTop.style.display = 'block';
    } else {
        backToTop.style.display = 'none';
    }
});

backToTop.addEventListener('click', function() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Styles pour le bouton retour en haut et le footer
const backToTopStyles = `
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: #D4AF37; /* or élégant */
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 20px;
        cursor: pointer;
        display: none;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        transition: 0.3s;
    }
    
    .back-to-top:hover {
        background: #B8860B; /* bronze pour effet hover */
        transform: translateY(-3px);
    }
    
    .payment-methods {
        margin-top: 20px;
        font-size: 24px;
        display: flex;
        gap: 15px;
        justify-content: center;
        color: #F5DEB3; /* couleur douce bijou */
    }
    
    .footer-description {
        color: #1C1C1C;
    }
    
    .newsletter-subscribe button {
        background: #D4AF37; /* or */
    }
    
    .newsletter-subscribe button:hover {
        background: #B8860B; /* bronze */
    }
`;
const styleSheet = document.createElement('style');
styleSheet.textContent = backToTopStyles;
document.head.appendChild(styleSheet);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
