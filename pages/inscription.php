<?php
// Page d'inscription
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Si l'utilisateur est déjà connecté, rediriger vers la page d'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Traitement du formulaire d'inscription
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $telephone = trim($_POST['telephone'] ?? '');
    $civilite = $_POST['civilite'] ?? '';
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    $conditions = isset($_POST['conditions']) ? 1 : 0;
    
    // Validation
    $errors = [];
    
    if (empty($nom)) $errors[] = 'Le nom est requis.';
    if (empty($prenom)) $errors[] = 'Le prénom est requis.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Une adresse email valide est requise.';
    if (empty($password)) $errors[] = 'Le mot de passe est requis.';
    if (strlen($password) < 6) $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    if ($password !== $password_confirm) $errors[] = 'Les mots de passe ne correspondent pas.';
    if (!$conditions) $errors[] = 'Vous devez accepter les conditions générales.';
    
    // Vérifier si l'email existe déjà
    if (empty($errors)) {
        try {
            $query = "SELECT id_utilisateur FROM utilisateurs WHERE email = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $errors[] = 'Cet email est déjà utilisé.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }
    
    // Inscription si pas d'erreurs
    if (empty($errors)) {
        try {
            // Hash du mot de passe
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertion dans la base de données
            $query = "INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, telephone, civilite, newsletter, date_inscription, role) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'client')";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$email, $password_hash, $nom, $prenom, $telephone, $civilite, $newsletter]);
            
            // Récupérer l'ID du nouvel utilisateur
            $user_id = $pdo->lastInsertId();
            
           // Connexion automatique
$_SESSION['user_id'] = $user_id;
$_SESSION['email'] = $email;
$_SESSION['nom'] = $nom;
$_SESSION['prenom'] = $prenom;
$_SESSION['role'] = 'client';


// Redirection vers l'accueil (chemin absolu complet)
header('Location: http://localhost/lumoura/index.php?inscription=success');
exit();
        } catch (PDOException $e) {
            $error = 'Une erreur est survenue lors de l\'inscription: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

$pageTitle = "Inscription - Lumoura";
include '../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-box">
        <h1 class="auth-title">Créer un compte</h1>
        
        <?php if (isset($_GET['inscription']) && $_GET['inscription'] === 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Inscription réussie ! Bienvenue <?php echo isset($_SESSION['prenom']) ? htmlspecialchars($_SESSION['prenom']) : ''; ?> !
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form">
            <!-- Civilité -->
            <div class="form-group">
                <label class="form-label">Civilité</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="civilite" value="Mme" <?php echo (!isset($_POST['civilite']) || $_POST['civilite'] == 'Mme') ? 'checked' : ''; ?>>
                        <span>Madame</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="civilite" value="M" <?php echo (isset($_POST['civilite']) && $_POST['civilite'] == 'M') ? 'checked' : ''; ?>>
                        <span>Monsieur</span>
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="civilite" value="Mlle" <?php echo (isset($_POST['civilite']) && $_POST['civilite'] == 'Mlle') ? 'checked' : ''; ?>>
                        <span>Mademoiselle</span>
                    </label>
                </div>
            </div>
            
            <!-- Nom et Prénom -->
            <div class="form-row">
                <div class="form-group">
                    <label for="nom" class="form-label">
                        <i class="fas fa-user"></i> Nom *
                    </label>
                    <input type="text" id="nom" name="nom" class="form-input" 
                           value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="prenom" class="form-label">
                        <i class="fas fa-user"></i> Prénom *
                    </label>
                    <input type="text" id="prenom" name="prenom" class="form-input" 
                           value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>"
                           required>
                </div>
            </div>
            
            <!-- Email -->
            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> Adresse email *
                </label>
                <input type="email" id="email" name="email" class="form-input" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       required>
            </div>
            
            <!-- Téléphone -->
            <div class="form-group">
                <label for="telephone" class="form-label">
                    <i class="fas fa-phone"></i> Téléphone
                </label>
                <input type="tel" id="telephone" name="telephone" class="form-input" 
                       value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
            </div>
            
            <!-- Mot de passe -->
            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Mot de passe *
                    </label>
                    <input type="password" id="password" name="password" class="form-input" required>
                    <div class="password-hint">
                        <small>Au moins 6 caractères</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm" class="form-label">
                        <i class="fas fa-lock"></i> Confirmer Mdp *
                    </label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-input" required>
                </div>
            </div>
            
            <!-- Newsletter -->
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="newsletter" value="1" <?php echo (!isset($_POST['newsletter']) || isset($_POST['newsletter'])) ? 'checked' : ''; ?>>
                    <span>Je souhaite m'abonner à la newsletter pour recevoir les offres exclusives</span>
                </label>
            </div>
            
            <!-- Conditions -->
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="conditions" required <?php echo (isset($_POST['conditions'])) ? 'checked' : ''; ?>>
                    <span>J'accepte les <a href="#" style="color: var(--gold);">conditions générales</a> et la <a href="#" style="color: var(--gold);">politique de confidentialité</a> *</span>
                </label>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-user-plus"></i> Créer mon compte
            </button>
        </form>
        
        <div class="auth-links">
            <p>Vous avez déjà un compte ? <a href="connexion.php">Se connecter</a></p>
            <p><a href="index.php" class="btn-secondary">
                <i class="fas fa-home"></i> Retour à l'accueil
            </a></p>
        </div>
    </div>
</div>


<?php include '../includes/footer.php'; ?>