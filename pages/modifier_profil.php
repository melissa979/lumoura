<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

/* =========================
   SÉCURITÉ : UTILISATEUR CONNECTÉ
========================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "pages/connexion.php");
    exit();
}
$user_id = (int)$_SESSION['user_id'];

$erreurs = [];
$success = false;

/* =========================
   RÉCUPÉRER LES INFOS UTILISATEUR
========================= */
try {
    $stmt = $pdo->prepare("
        SELECT nom, prenom, email
        FROM utilisateurs
        WHERE id_utilisateur = :user_id
    ");
    $stmt->execute(['user_id' => $user_id]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $erreurs[] = "Profil non trouvé. Veuillez vous reconnecter.";
    } else {
        $nom    = $user['nom'];
        $prenom = $user['prenom'];
        $email  = $user['email'];
    }

} catch (PDOException $e) {
    $erreurs[] = "Erreur lors du chargement du profil : " . $e->getMessage();
}

/* =========================
   TRAITEMENT DU FORMULAIRE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom          = trim($_POST['nom'] ?? '');
    $prenom       = trim($_POST['prenom'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $mdp          = trim($_POST['mdp'] ?? '');
    $mdp_confirm  = trim($_POST['mdp_confirm'] ?? '');

    // Validations
    if ($nom === '')     $erreurs[] = "Le nom est obligatoire.";
    if ($prenom === '')  $erreurs[] = "Le prénom est obligatoire.";
    if ($email === '')   $erreurs[] = "L'email est obligatoire.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "Email invalide.";
    }

    // Mot de passe
    $update_mdp = false;
    if ($mdp !== '') {
        if (strlen($mdp) < 6) {
            $erreurs[] = "Le mot de passe doit contenir au moins 6 caractères.";
        } elseif ($mdp !== $mdp_confirm) {
            $erreurs[] = "Les mots de passe ne correspondent pas.";
        } else {
            $update_mdp = true;
        }
    }

    /* =========================
       EMAIL UNIQUE
    ========================= */
    if (empty($erreurs)) {
        $check = $pdo->prepare("
            SELECT id_utilisateur
            FROM utilisateurs
            WHERE email = :email AND id_utilisateur != :user_id
        ");
        $check->execute([
            'email'   => $email,
            'user_id' => $user_id
        ]);

        if ($check->fetch()) {
            $erreurs[] = "Cet email est déjà utilisé par un autre utilisateur.";
        }
    }

    /* =========================
       MISE À JOUR
    ========================= */
    if (empty($erreurs)) {
        try {
            $sql = "UPDATE utilisateurs
                    SET nom = :nom,
                        prenom = :prenom,
                        email = :email";

            $params = [
                'nom'    => $nom,
                'prenom' => $prenom,
                'email'  => $email,
                'user_id' => $user_id
            ];

            if ($update_mdp) {
                $sql .= ", mot_de_passe = :mot_de_passe";
                $params['mot_de_passe'] = password_hash($mdp, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id_utilisateur = :user_id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Mise à jour session
            $_SESSION['user_nom'] = $prenom . ' ' . $nom;

            $success = true;

        } catch (PDOException $e) {
            $erreurs[] = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}

$pageTitle = "Modifier mon profil";
include '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Didact+Gothic&family=EB+Garamond:ital,wght@0,400;0,500;0,700;1,400;1,600&family=Cinzel:wght@400;600;700;900&display=swap" rel="stylesheet">

<style>
:root {
  --g1: #D4A843;
  --g2: #F5D78E;
  --g3: #B8882C;
  --ink: #0D0A06;
  --ink2: #1E1710;
  --smoke: #F8F5EF;
  --stone: #E8E0D0;
  --muted: #8A7D6A;
}

*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

body {
  font-family: 'Didact Gothic', sans-serif;
  background: var(--smoke);
  color: var(--ink);
}

/* ═══════════════════════════════════════════════
   CURSEUR PERSONNALISÉ
═══════════════════════════════════════════════ */
#cursor {
  position: fixed;
  width: 10px; height: 10px;
  background: var(--g1);
  border-radius: 50%;
  pointer-events: none;
  z-index: 99999;
  transform: translate(-50%,-50%);
  transition: transform .1s, width .25s, height .25s, background .25s;
}
#cursor-ring {
  position: fixed;
  width: 36px; height: 36px;
  border: 1px solid var(--g1);
  border-radius: 50%;
  pointer-events: none;
  z-index: 99998;
  transform: translate(-50%,-50%);
  transition: transform .08s linear, width .3s, height .3s, opacity .3s;
  opacity: .6;
}
body.hovering #cursor { width: 20px; height: 20px; background: var(--g2); }
body.hovering #cursor-ring { width: 54px; height: 54px; border-color: var(--g2); opacity: .4; }

/* ═══════════════════════════════════════════════
   PAGE HERO
═══════════════════════════════════════════════ */
.page-hero {
  background: var(--ink);
  padding: 100px 20px 60px;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.page-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(212,168,67,.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(212,168,67,.04) 1px, transparent 1px);
  background-size: 50px 50px;
  z-index: 1;
}

.page-hero-content {
  position: relative;
  z-index: 2;
}

.page-hero-tag {
  font-family: 'Cinzel', serif;
  font-size: .58rem;
  letter-spacing: 5px;
  text-transform: uppercase;
  color: var(--g2);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  margin-bottom: 18px;
}

.page-hero-tag::before,
.page-hero-tag::after {
  content: '';
  width: 40px;
  height: 1px;
  background: var(--g1);
}

.page-hero h1 {
  font-family: 'EB Garamond', serif;
  font-size: clamp(2.5rem, 5vw, 3.8rem);
  font-weight: 400;
  color: #fff;
  letter-spacing: 1px;
  margin-bottom: 15px;
}

.page-hero h1 em {
  font-style: italic;
  color: var(--g2);
}

/* ═══════════════════════════════════════════════
   FORM CONTAINER
═══════════════════════════════════════════════ */
.form-container {
  max-width: 800px;
  margin: 60px auto;
  padding: 0 20px;
}

.form-card {
  background: #fff;
  border: 1px solid var(--stone);
  padding: 50px;
  position: relative;
}

.form-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 150px;
  height: 3px;
  background: linear-gradient(90deg, transparent, var(--g1), transparent);
}

/* ═══════════════════════════════════════════════
   ALERTS
═══════════════════════════════════════════════ */
.alert {
  padding: 18px 25px;
  margin-bottom: 30px;
  border-radius: 0;
  font-family: 'Cinzel', serif;
  font-size: .75rem;
  letter-spacing: 1.5px;
  display: flex;
  align-items: center;
  gap: 12px;
  animation: slideDown .5s ease;
}

@keyframes slideDown {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}

.alert-success {
  background: rgba(76, 175, 80, .1);
  border-left: 3px solid #4CAF50;
  color: #2e7d32;
}

.alert-danger {
  background: rgba(244, 67, 54, .1);
  border-left: 3px solid #f44336;
  color: #c62828;
}

.alert i {
  font-size: 1rem;
}

.alert ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.alert li {
  margin-bottom: 5px;
}

.alert li:last-child {
  margin-bottom: 0;
}

/* ═══════════════════════════════════════════════
   FORM ELEMENTS
═══════════════════════════════════════════════ */
.form-section {
  margin-bottom: 35px;
}

.form-section h5 {
  font-family: 'EB Garamond', serif;
  font-size: 1.3rem;
  font-weight: 500;
  color: var(--ink);
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--stone);
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin-bottom: 20px;
}

.form-row.full {
  grid-template-columns: 1fr;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-family: 'Cinzel', serif;
  font-size: .62rem;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 8px;
  font-weight: 600;
}

.form-control {
  padding: 14px 18px;
  border: 1px solid var(--stone);
  background: var(--smoke);
  font-family: 'Didact Gothic', sans-serif;
  font-size: .95rem;
  color: var(--ink);
  transition: all .3s;
}

.form-control:focus {
  outline: none;
  border-color: var(--g1);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(212,168,67,.1);
}

/* ═══════════════════════════════════════════════
   BUTTONS
═══════════════════════════════════════════════ */
.form-actions {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 40px;
  padding-top: 30px;
  border-top: 1px solid var(--stone);
}

.btn {
  padding: 15px 40px;
  font-family: 'Cinzel', serif;
  font-size: .68rem;
  letter-spacing: 3px;
  text-transform: uppercase;
  font-weight: 600;
  border: none;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all .35s;
  position: relative;
  overflow: hidden;
}

.btn-primary {
  background: var(--g1);
  color: var(--ink);
}

.btn-primary::before {
  content: '';
  position: absolute;
  inset: 0;
  background: var(--g2);
  transform: scaleX(0);
  transform-origin: right;
  transition: transform .35s cubic-bezier(.77,0,.18,1);
  z-index: 0;
}

.btn-primary:hover::before {
  transform: scaleX(1);
  transform-origin: left;
}

.btn-primary span {
  position: relative;
  z-index: 1;
}

.btn-secondary {
  background: transparent;
  border: 1px solid var(--stone);
  color: var(--muted);
}

.btn-secondary:hover {
  background: var(--smoke);
  border-color: var(--g1);
  color: var(--ink);
}

/* ═══════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════ */
@media (max-width: 768px) {
  .form-card {
    padding: 35px 25px;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
}
</style>

<!-- CURSEUR -->
<div id="cursor"></div>
<div id="cursor-ring"></div>

<!-- PAGE HERO -->
<section class="page-hero">
  <div class="page-hero-content">
    <div class="page-hero-tag">Mon Compte</div>
    <h1>Modifier mon <em>Profil</em></h1>
  </div>
</section>

<!-- FORM CONTENT -->
<div class="form-container">
  <div class="form-card">
    
    <?php if ($success): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <span>Profil mis à jour avec succès !</span>
      </div>
    <?php endif; ?>

    <?php if (!empty($erreurs)): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <ul>
          <?php foreach ($erreurs as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post">
      
      <div class="form-section">
        <h5>Informations personnelles</h5>
        
        <div class="form-row">
          <div class="form-group">
            <label for="prenom">Prénom</label>
            <input type="text" 
                   id="prenom" 
                   name="prenom" 
                   class="form-control"
                   value="<?= htmlspecialchars($prenom ?? '') ?>" 
                   required>
          </div>

          <div class="form-group">
            <label for="nom">Nom</label>
            <input type="text" 
                   id="nom" 
                   name="nom" 
                   class="form-control"
                   value="<?= htmlspecialchars($nom ?? '') ?>" 
                   required>
          </div>
        </div>

        <div class="form-row full">
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   class="form-control"
                   value="<?= htmlspecialchars($email ?? '') ?>" 
                   required>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h5>Changer le mot de passe (optionnel)</h5>
        
        <div class="form-row">
          <div class="form-group">
            <label for="mdp">Nouveau mot de passe</label>
            <input type="password" 
                   id="mdp" 
                   name="mdp" 
                   class="form-control"
                   placeholder="Minimum 6 caractères">
          </div>

          <div class="form-group">
            <label for="mdp_confirm">Confirmer le mot de passe</label>
            <input type="password" 
                   id="mdp_confirm" 
                   name="mdp_confirm" 
                   class="form-control"
                   placeholder="Retapez le mot de passe">
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">
          <span>Enregistrer</span>
        </button>
        <a href="<?= SITE_URL ?>pages/comptes.php" class="btn btn-secondary">
          Annuler
        </a>
      </div>

    </form>

  </div>
</div>

<script>
// ── CURSEUR ──
const cur = document.getElementById('cursor');
const ring = document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;

document.addEventListener('mousemove',e=>{ 
    mx=e.clientX; 
    my=e.clientY; 
    cur.style.left=mx+'px'; 
    cur.style.top=my+'px'; 
});

function animRing(){ 
    rx+=(mx-rx)*.12; 
    ry+=(my-ry)*.12; 
    ring.style.left=rx+'px'; 
    ring.style.top=ry+'px'; 
    requestAnimationFrame(animRing); 
}
animRing();

document.querySelectorAll('a,button,input,.form-control').forEach(el=>{
  el.addEventListener('mouseenter',()=>document.body.classList.add('hovering'));
  el.addEventListener('mouseleave',()=>document.body.classList.remove('hovering'));
});
</script>

<?php include '../includes/footer.php'; ?>