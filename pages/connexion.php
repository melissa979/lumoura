<?php
// Page de connexion - Adaptée pour boutique de bijoux
// Les changements concernent surtout les textes, le titre et le style pour un rendu plus luxueux

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Si déjà connecté → redirection (inchangé)
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

// Traitement du formulaire (logique inchangée)
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $query = "SELECT * FROM utilisateurs WHERE email = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['mot_de_passe'])) {
                $_SESSION['user_id']    = $user['id_utilisateur'];
                $_SESSION['email']      = $user['email'];
                $_SESSION['nom']        = $user['nom'];
                $_SESSION['prenom']     = $user['prenom'];
                $_SESSION['role']       = $user['role'];
                
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

// Déconnexion (inchangé)
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: connexion.php');
    exit();
}

$pageTitle = "Connexion - Lumoura Joaillerie";  // ← plus luxueux
include '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <!-- Titre changé pour ambiance bijoux -->
        <h1 class="auth-title">Bienvenue chez Lumoura</h1>
        <p class="auth-subtitle">Connectez-vous pour découvrir votre prochain trésor</p> <!-- Ajout pour plus d'élégance -->
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo sanitize($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo sanitize($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form">
            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> Adresse email
                </label>
                <input type="email" id="email" name="email" class="form-input" 
                       value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>"
                       required placeholder="votre@email.com">
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> Mot de passe
                </label>
                <input type="password" id="password" name="password" class="form-input" required
                       placeholder="••••••••">
                <div style="text-align: right; margin-top: 8px;">
                    <a href="#" style="font-size: 14px; color: var(--gold); text-decoration: underline;">Mot de passe oublié ?</a>
                </div>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" checked>
                    <span>Rester connecté</span> <!-- Légèrement plus chaleureux -->
                </label>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-sign-in-alt"></i> Se connecter à mon espace
            </button>
        </form>
        
        <div class="auth-links">
            <p>Pas encore membre ? <a href="inscription.php" style="color: var(--gold);">Créer mon compte</a></p>
            <p><a href="../index.php" class="btn-secondary">
                <i class="fas fa-home"></i> Retour à la collection
            </a></p>
        </div>
        
        <!-- Connexion sociale - on peut la garder ou la retirer selon ton choix -->
        <div class="social-login">
            <p style="text-align: center; margin: 25px 0; color: var(--gray);">Ou connectez-vous avec</p>
            <div class="social-buttons">
                <button class="social-btn facebook">
                    <i class="fab fa-facebook-f"></i> Facebook
                </button>
                <button class="social-btn google">
                    <i class="fab fa-google"></i> Google
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Styles adaptés pour une ambiance plus luxueuse (or, élégance, contraste) -->
<style>
.auth-container {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f9f5f0 0%, #f0e9e0 100%); /* fond très clair chaud, élégant */
    padding: 40px 20px;
}

.auth-box {
    background: white;
    padding: 50px 40px;
    border-radius: 12px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.08);
    max-width: 480px;
    width: 100%;
    border: 1px solid rgba(212, 175, 55, 0.15); /* touche or discrète */
}

.auth-title {
    font-size: 2.1rem;
    color: var(--deep-brown);
    margin-bottom: 8px;
    text-align: center;
    font-weight: 600;
}

.auth-subtitle {
    text-align: center;
    color: #777;
    margin-bottom: 30px;
    font-size: 1.05rem;
}

.form-input {
    border: 1px solid #d4d4d4;
    border-radius: 8px;
    padding: 14px 16px;
    transition: all 0.3s;
}

.form-input:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.15);
    outline: none;
}

.btn-submit {
    background: var(--gold);
    color: white;
    border: none;
    width: 100%;
    padding: 16px;
    font-size: 1.05rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 15px;
}

.btn-submit:hover {
    background: var(--gold-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(212, 175, 55, 0.25);
}

.auth-links p {
    text-align: center;
    margin: 18px 0;
    font-size: 0.98rem;
}

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.98rem;
}

.alert-error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ef9a9a;
}

.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.social-buttons {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

.social-btn {
    flex: 1;
    padding: 12px;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    border: none;
}

.social-btn.facebook {
    background: #1877f2;
    color: white;
}

.social-btn.google {
    background: white;
    color: #333;
    border: 1px solid #ddd;
}

.social-btn:hover {
    opacity: 0.92;
    transform: translateY(-1px);
}
</style>

<?php include '../includes/footer.php'; ?>