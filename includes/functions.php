[file name]: functions.php
[file content begin]
<?php
// ===============================
// FONCTIONS UTILITAIRES POUR ÉCLAT D'OR
// ===============================
// Ce fichier contient des fonctions réutilisables sur toutes les pages du site de bijoux

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool True si connecté, False sinon
 */
function isLoggedIn() {
    // Si la session 'user_id' existe, l'utilisateur est connecté
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est administrateur
 * @return bool True si admin, False sinon
 */
function isAdmin() {
    // Vérifie si l'utilisateur est connecté ET si son rôle est 'admin'
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirige vers la page de connexion avec un message
 * @param string $message Message à afficher
 * @return void
 */
function requireLogin($message = 'Veuillez vous connecter pour accéder à cette fonctionnalité.') {
    // Stocke le message dans la session et redirige vers la connexion
    $_SESSION['login_redirect'] = $message;
    header('Location: connexion.php');
    exit();
}

/**
 * Formate un prix avec le symbole euro pour les bijoux
 * @param float $price Le prix à formater
 * @return string Prix formaté (ex: "125,50 €")
 */
function formatPrice($price) {
    // Formate le prix avec 2 décimales, une virgule et le symbole €
    return number_format($price, 2, ',', ' ') . ' €';
}

/**
 * Calcule le prix après promotion
 * @param float $price Prix original du bijou
 * @param float $discount Pourcentage de réduction
 * @return float Prix après réduction
 */
function calculateDiscount($price, $discount) {
    // Prix réduit = prix original - (prix original * réduction / 100)
    return $price - ($price * $discount / 100);
}

/**
 * Affiche les étoiles pour la notation des bijoux
 * @param int $rating Note de 1 à 5
 * @return string HTML des étoiles
 */
function displayRating($rating) {
    $html = '<div class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star"></i>'; // Étoile pleine
        } else {
            $html .= '<i class="far fa-star"></i>'; // Étoile vide
        }
    }
    $html .= '</div>';
    return $html;
}

/**
 * Raccourcit un texte (ex: description d'un bijou)
 * @param string $text Texte à raccourcir
 * @param int $length Longueur maximale
 * @return string Texte raccourci
 */
function shortenText($text, $length = 100) {
    if (strlen($text) > $length) {
        return substr($text, 0, $length) . '...';
    }
    return $text;
}

/**
 * Sécurise une chaîne pour l'affichage HTML
 * @param string $string Chaîne à sécuriser
 * @return string Chaîne sécurisée (évite les injections XSS)
 */
function sanitize($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>