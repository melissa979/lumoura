<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

/*
|--------------------------------------------------------------------------
| Vérification de la connexion existante
|--------------------------------------------------------------------------
*/
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

/*
|--------------------------------------------------------------------------
| Traitement du formulaire
|--------------------------------------------------------------------------
*/
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez saisir une adresse email valide.';
    } else {
        try {
            $query = "
                SELECT *
                FROM utilisateurs
                WHERE email = ?
                LIMIT 1
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                $_SESSION['user_id'] = $user['id_utilisateur'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['prenom'] = $user['prenom'];
                $_SESSION['role'] = $user['role'];

                header('Location: ../index.php');
                exit();
            } else {
                $error = 'Email ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $error = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
}

$pageTitle = "Connexion - Éclat d'Or";

include '../includes/header.php';
?>

<style>
/* ==========================================================================
   PAGE DE CONNEXION ÉCLAT D'OR
========================================================================== */

:root {
    --login-brown: #3a2925;
    --login-brown-light: #806c63;
    --login-gold: #c9a227;
    --login-gold-dark: #9d7815;
    --login-cream: #fbf6ef;
    --login-text: #302522;
    --login-gray: #77716d;
}

/*
|--------------------------------------------------------------------------
| Conteneur principal
|--------------------------------------------------------------------------
| width: 100vw et margin-left permettent de corriger le problème
| lorsque le header ou un conteneur global pousse le contenu à droite.
|--------------------------------------------------------------------------
*/

.luxury-login-page {
    width: 100vw;
    min-height: calc(100vh - 80px);
    margin-left: calc(50% - 50vw);
    padding: 55px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    background:
        radial-gradient(
            circle at 10% 10%,
            rgba(201, 162, 39, 0.15),
            transparent 30%
        ),
        linear-gradient(
            135deg,
            #fbf6ef 0%,
            #f2e7da 100%
        );
}

/*
|--------------------------------------------------------------------------
| Mise en page en deux colonnes
|--------------------------------------------------------------------------
*/

.luxury-login-layout {
    width: min(1080px, 100%);
    min-height: 590px;
    display: grid;
    grid-template-columns: 1fr 450px;
    overflow: hidden;
    border-radius: 24px;
    background: #ffffff;
    box-shadow: 0 25px 70px rgba(58, 41, 37, 0.18);
}

/*
|--------------------------------------------------------------------------
| Partie présentation
|--------------------------------------------------------------------------
*/

.login-showcase {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 65px;
    overflow: hidden;
    background:
        linear-gradient(
            rgba(58, 41, 37, 0.83),
            rgba(58, 41, 37, 0.95)
        ),
        url('https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=1200&q=85')
        center / cover;
    color: #ffffff;
}

.login-showcase::before {
    content: "";
    position: absolute;
    width: 280px;
    height: 280px;
    top: -110px;
    right: -100px;
    border: 1px solid rgba(201, 162, 39, 0.45);
    border-radius: 50%;
}

.login-showcase::after {
    content: "";
    position: absolute;
    width: 430px;
    height: 430px;
    bottom: -250px;
    left: -180px;
    border: 1px solid rgba(201, 162, 39, 0.35);
    border-radius: 50%;
}

.showcase-content {
    position: relative;
    z-index: 2;
    max-width: 450px;
}

.showcase-logo {
    margin-bottom: 55px;
    color: var(--login-gold);
    font-family: Georgia, serif;
    font-size: 20px;
    font-weight: bold;
    letter-spacing: 4px;
    text-transform: uppercase;
}

.showcase-line {
    width: 70px;
    height: 3px;
    margin-bottom: 28px;
    background: var(--login-gold);
}

.showcase-title {
    margin: 0 0 20px;
    color: #ffffff;
    font-family: Georgia, serif;
    font-size: clamp(2rem, 4vw, 3.4rem);
    font-weight: normal;
    line-height: 1.15;
}

.showcase-text {
    max-width: 410px;
    margin: 0 0 35px;
    color: rgba(255, 255, 255, 0.82);
    font-size: 15px;
    line-height: 1.8;
}

.showcase-features {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-top: 45px;
}

.showcase-feature {
    color: rgba(255, 255, 255, 0.85);
    font-size: 12px;
    line-height: 1.5;
}

.showcase-feature i {
    display: block;
    margin-bottom: 10px;
    color: var(--login-gold);
    font-size: 22px;
}

.showcase-feature strong {
    display: block;
    margin-bottom: 4px;
    color: #ffffff;
}

/*
|--------------------------------------------------------------------------
| Carte de connexion
|--------------------------------------------------------------------------
*/

.login-form-area {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 50px 42px;
    background: #ffffff;
}

.login-form-box {
    width: 100%;
    max-width: 360px;
}

.login-title {
    margin: 0 0 10px;
    color: var(--login-brown);
    font-family: Georgia, serif;
    font-size: 30px;
    font-weight: normal;
    text-align: center;
}

.login-subtitle {
    margin: 0 0 30px;
    color: var(--login-gray);
    font-size: 13px;
    line-height: 1.6;
    text-align: center;
}

/*
|--------------------------------------------------------------------------
| Messages d'erreur
|--------------------------------------------------------------------------
*/

.login-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 22px;
    padding: 13px 15px;
    border-radius: 9px;
    font-size: 13px;
    line-height: 1.5;
}

.login-alert-error {
    border: 1px solid #efb2b2;
    background: #fff0f0;
    color: #a52828;
}

/*
|--------------------------------------------------------------------------
| Champs du formulaire
|--------------------------------------------------------------------------
*/

.login-form-group {
    margin-bottom: 20px;
}

.login-label {
    display: block;
    margin-bottom: 8px;
    color: var(--login-brown);
    font-size: 13px;
    font-weight: 600;
}

.login-label i {
    width: 18px;
    margin-right: 4px;
    color: var(--login-gold);
    text-align: center;
}

.login-input {
    width: 100%;
    min-height: 47px;
    padding: 12px 14px;
    box-sizing: border-box;
    border: 1px solid #ddd4ca;
    border-radius: 8px;
    outline: none;
    background: #fffdf9;
    color: var(--login-text);
    font-family: inherit;
    font-size: 14px;
    transition: border-color 0.25s ease, box-shadow 0.25s ease;
}

.login-input:focus {
    border-color: var(--login-gold);
    box-shadow: 0 0 0 4px rgba(201, 162, 39, 0.14);
}

.login-input::placeholder {
    color: #aaa19b;
}

/*
|--------------------------------------------------------------------------
| Mot de passe oublié
|--------------------------------------------------------------------------
*/

.login-password-row {
    display: flex;
    justify-content: flex-end;
    margin-top: 8px;
}

.login-forgotten {
    color: var(--login-gold);
    font-size: 12px;
    text-decoration: none;
}

.login-forgotten:hover {
    color: var(--login-gold-dark);
    text-decoration: underline;
}

/*
|--------------------------------------------------------------------------
| Case rester connecté
|--------------------------------------------------------------------------
*/

.login-remember {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--login-gray);
    font-size: 12px;
    cursor: pointer;
}

.login-remember input {
    accent-color: var(--login-gold);
}

/*
|--------------------------------------------------------------------------
| Bouton principal
|--------------------------------------------------------------------------
*/

.login-submit {
    width: 100%;
    min-height: 49px;
    margin-top: 8px;
    padding: 13px 18px;
    border: none;
    border-radius: 8px;
    background: linear-gradient(
        135deg,
        var(--login-gold),
        #b88a1d
    );
    color: #ffffff;
    cursor: pointer;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}

.login-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 23px rgba(201, 162, 39, 0.3);
}

.login-submit i {
    margin-right: 6px;
}

/*
|--------------------------------------------------------------------------
| Liens
|--------------------------------------------------------------------------
*/

.login-links {
    margin-top: 25px;
    text-align: center;
}

.login-links p {
    margin: 13px 0;
    color: var(--login-gray);
    font-size: 12px;
}

.login-links a {
    color: var(--login-gold);
    text-decoration: none;
}

.login-links a:hover {
    color: var(--login-gold-dark);
    text-decoration: underline;
}

.login-back-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--login-brown) !important;
}

/*
|--------------------------------------------------------------------------
| Connexion sociale
|--------------------------------------------------------------------------
*/

.social-login {
    margin-top: 25px;
    padding-top: 22px;
    border-top: 1px solid #eee6de;
}

.social-login-title {
    margin: 0 0 16px;
    color: var(--login-gray);
    font-size: 12px;
    text-align: center;
}

.social-buttons {
    display: flex;
    gap: 10px;
}

.social-button {
    flex: 1;
    min-height: 40px;
    border-radius: 7px;
    cursor: pointer;
    font-family: inherit;
    font-size: 12px;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.social-button:hover {
    transform: translateY(-1px);
    opacity: 0.9;
}

.social-facebook {
    border: none;
    background: #1877f2;
    color: #ffffff;
}

.social-google {
    border: 1px solid #d9d2cc;
    background: #ffffff;
    color: #333333;
}

/*
|--------------------------------------------------------------------------
| Version tablette
|--------------------------------------------------------------------------
*/

@media (max-width: 850px) {
    .luxury-login-layout {
        grid-template-columns: 1fr;
    }

    .login-showcase {
        min-height: 350px;
        padding: 45px;
    }

    .showcase-logo {
        margin-bottom: 30px;
    }

    .showcase-features {
        margin-top: 28px;
    }
}

/*
|--------------------------------------------------------------------------
| Version téléphone
|--------------------------------------------------------------------------
*/

@media (max-width: 520px) {
    .luxury-login-page {
        min-height: calc(100vh - 60px);
        padding: 25px 12px;
    }

    .login-showcase {
        min-height: auto;
        padding: 38px 25px;
    }

    .showcase-title {
        font-size: 2rem;
    }

    .showcase-text {
        font-size: 13px;
    }

    .showcase-features {
        grid-template-columns: 1fr;
        gap: 14px;
        margin-top: 25px;
    }

    .showcase-feature {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .showcase-feature i {
        margin: 0;
        font-size: 18px;
    }

    .login-form-area {
        padding: 35px 22px;
    }

    .login-title {
        font-size: 26px;
    }

    .social-buttons {
        flex-direction: column;
    }
}
</style>

<main class="luxury-login-page">
    <div class="luxury-login-layout">

        <!-- Partie présentation -->
        <section class="login-showcase">
            <div class="showcase-content">
                <div class="showcase-logo">
                    Éclat d'Or
                </div>

                <div class="showcase-line"></div>

                <h1 class="showcase-title">
                    L'élégance<br>
                    à chaque détail
                </h1>

                <p class="showcase-text">
                    Découvrez une collection de bijoux raffinés,
                    imaginés pour accompagner les moments précieux
                    de votre vie.
                </p>

                <div class="showcase-features">
                    <div class="showcase-feature">
                        <i class="fas fa-gem"></i>
                        <strong>Créations raffinées</strong>
                        Bijoux sélectionnés avec soin
                    </div>

                    <div class="showcase-feature">
                        <i class="fas fa-box-open"></i>
                        <strong>Livraison soignée</strong>
                        Emballage élégant et sécurisé
                    </div>

                    <div class="showcase-feature">
                        <i class="fas fa-heart"></i>
                        <strong>Qualité garantie</strong>
                        Votre satisfaction avant tout
                    </div>
                </div>
            </div>
        </section>

        <!-- Formulaire de connexion -->
        <section class="login-form-area">
            <div class="login-form-box">

                <h2 class="login-title">
                    Bienvenue
                </h2>

                <p class="login-subtitle">
                    Connectez-vous à votre espace Éclat d'Or
                </p>

                <?php if (!empty($error)): ?>
                    <div class="login-alert login-alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>
                            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="login-form">

                    <div class="login-form-group">
                        <label for="email" class="login-label">
                            <i class="fas fa-envelope"></i>
                            Adresse email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="login-input"
                            value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="votre@email.com"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="login-form-group">
                        <label for="password" class="login-label">
                            <i class="fas fa-lock"></i>
                            Mot de passe
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="login-input"
                            placeholder="Votre mot de passe"
                            autocomplete="current-password"
                            required
                        >

                        <div class="login-password-row">
                            <a href="#" class="login-forgotten">
                                Mot de passe oublié ?
                            </a>
                        </div>
                    </div>

                    <div class="login-form-group">
                        <label class="login-remember">
                            <input
                                type="checkbox"
                                name="remember"
                                value="1"
                            >
                            <span>Rester connecté</span>
                        </label>
                    </div>

                    <button type="submit" class="login-submit">
                        <i class="fas fa-sign-in-alt"></i>
                        Se connecter
                    </button>
                </form>

                <div class="login-links">
                    <p>
                        Pas encore membre ?
                        <a href="inscription.php">
                            Créer mon compte
                        </a>
                    </p>

                    <p>
                        <a href="../index.php" class="login-back-link">
                            <i class="fas fa-arrow-left"></i>
                            Retour à la boutique
                        </a>
                    </p>
                </div>

                <div class="social-login">
                    <p class="social-login-title">
                        Ou connectez-vous avec
                    </p>

                    <div class="social-buttons">
                        <button
                            type="button"
                            class="social-button social-facebook"
                        >
                            <i class="fab fa-facebook-f"></i>
                            Facebook
                        </button>

                        <button
                            type="button"
                            class="social-button social-google"
                        >
                            <i class="fab fa-google"></i>
                            Google
                        </button>
                    </div>
                </div>

            </div>
        </section>

    </div>
</main>

<?php include '../includes/footer.php'; ?>