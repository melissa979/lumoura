<?php
/**
 * Page de déconnexion - Lumoura Joaillerie
 * 
 * Cette page :
 * - détruit la session de l'utilisateur
 * - supprime le cookie de session si présent
 * - affiche un message de confirmation pendant 2-3 secondes
 * - redirige ensuite automatiquement vers la page d'accueil
 */

session_start();

// === 1. Nettoyage complet de la session ===

// On vide toutes les variables de session
$_SESSION = [];

// Si un cookie de session existe, on le supprime aussi (sécurité renforcée)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),               // nom du cookie de session (souvent PHPSESSID)
        '',                           // valeur vide
        time() - 42000,               // expiration dans le passé
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// === 2. Destruction définitive de la session ===
session_destroy();

// === 3. Message de confirmation (optionnel mais plus élégant) ===
$message = "Déconnexion réussie. À très bientôt chez Lumoura 💎";
$redirect_url = "../index.php";  // ← change ici si tu veux rediriger ailleurs (ex: catalogue.php)
$delay_seconds = 2;              // temps avant redirection automatique

?>
<!-- 
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Déconnexion - Lumoura Joaillerie</title>
    
     Redirection automatique après X secondes 
    <meta http-equiv="refresh" content="<?php echo $delay_seconds; ?>;url=<?php echo $redirect_url; ?>">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #fdfaf5 0%, #f8f3e8 100%);
            color: #333;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .logout-container {
            max-width: 500px;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 35px rgba(0,0,0,0.08);
            border: 1px solid rgba(212, 175, 55, 0.12); /* touche or discrète */
        }
        h1 {
            color: #8b6f47;           /* brun/or élégant */
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        .message {
            font-size: 1.15rem;
            color: #555;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        .gem-icon {
            font-size: 3.5rem;
            color: #d4af37;           /* or */
            margin-bottom: 20px;
        }
        .redirect-info {
            font-size: 0.95rem;
            color: #777;
        }
        .btn-home {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 30px;
            background: #d4af37;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-home:hover {
            background: #b8972e;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <div class="logout-container">
        <div class="gem-icon">💎</div>
        <h1>Déconnexion réussie</h1>
        <p class="message">
            <?php echo htmlspecialchars($message); ?> <br>
            Vous avez été déconnecté de votre espace client.
        </p>
        <p class="redirect-info">
            Redirection automatique dans <?php echo $delay_seconds; ?> secondes...
        </p>
        <a href="<?php echo $redirect_url; ?>" class="btn-home">
            Retour à l'accueil maintenant
        </a>
    </div>

    Petit script de secours si le meta refresh ne fonctionne pas 
    <script>
        setTimeout(() => {
            window.location.href = "<?php echo addslashes($redirect_url); ?>";
        }, <?php echo $delay_seconds * 1000; ?>);
    </script>

</body>
</html> -->