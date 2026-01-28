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
    $sql .= ", mot_de_passe = :mot_de_passe";  // ← CHANGE ICI
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

<div class="container py-5" style="padding-top:120px!important;">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card border-0 shadow-lg" style="border-radius:30px;">
                <div class="card-body p-5">

                    <h1 class="text-center mb-5 fw-bold">
                        Modifier mon profil
                    </h1>

                    <?php if ($success): ?>
                        <div class="alert alert-success text-center">
                            Profil mis à jour avec succès !
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($erreurs)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($erreurs as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="row g-4">

                            <div class="col-md-6">
                                <label class="form-label">Prénom</label>
                                <input type="text" name="prenom"
                                       class="form-control form-control-lg"
                                       value="<?= htmlspecialchars($prenom ?? '') ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nom</label>
                                <input type="text" name="nom"
                                       class="form-control form-control-lg"
                                       value="<?= htmlspecialchars($nom ?? '') ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input type="email" name="email"
                                       class="form-control form-control-lg"
                                       value="<?= htmlspecialchars($email ?? '') ?>" required>
                            </div>

                            <div class="col-12 mt-4">
                                <h5>Changer le mot de passe (optionnel)</h5>
                            </div>

                            <div class="col-md-6">
                                <input type="password" name="mdp"
                                       class="form-control form-control-lg"
                                       placeholder="Nouveau mot de passe">
                            </div>

                            <div class="col-md-6">
                                <input type="password" name="mdp_confirm"
                                       class="form-control form-control-lg"
                                       placeholder="Confirmer le mot de passe">
                            </div>

                            <div class="col-12 text-center mt-4">
                                <button class="btn btn-primary btn-lg px-5">
                                    Enregistrer
                                </button>
                                <a href="<?= SITE_URL ?>pages/comptes.php"
                                   class="btn btn-outline-secondary btn-lg ms-3">
                                    Annuler
                                </a>
                            </div>

                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>