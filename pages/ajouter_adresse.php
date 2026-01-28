<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "pages/connexion.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$erreurs = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom       = trim($_POST['prenom'] ?? '');
    $nom          = trim($_POST['nom'] ?? '');
    $adresse      = trim($_POST['adresse'] ?? '');
    $complement   = trim($_POST['complement'] ?? '');
    $code_postal  = trim($_POST['code_postal'] ?? '');
    $ville        = trim($_POST['ville'] ?? '');
    $pays         = trim($_POST['pays'] ?? 'France');
    $telephone    = trim($_POST['telephone'] ?? '');
    $principale   = isset($_POST['principale']) ? 1 : 0;

    // Validation simple
    if (empty($prenom))     $erreurs[] = "Le prénom est obligatoire";
    if (empty($nom))        $erreurs[] = "Le nom est obligatoire";
    if (empty($adresse))    $erreurs[] = "L'adresse est obligatoire";
    if (empty($code_postal))$erreurs[] = "Le code postal est obligatoire";
    if (empty($ville))      $erreurs[] = "La ville est obligatoire";

    if (empty($erreurs)) {
        try {
            // Si on définit comme principale → on enlève le statut aux autres
            if ($principale) {
                $pdo->prepare("UPDATE adresses SET est_principale = 0 WHERE id_utilisateur = ?")
                    ->execute([$user_id]);
            }

           $stmt = $pdo->prepare("
    INSERT INTO adresses 
    (id_utilisateur, prenom, nom, adresse, complement_adresse, code_postal, ville, pays, telephone, est_principale, date_creation)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
$stmt->execute([
    $user_id, $prenom, $nom, $adresse, $complement, $code_postal, $ville, $pays, $telephone, $principale
]);

            $success = true;
        } catch (Exception $e) {
            $erreurs[] = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}

$pageTitle = "Ajouter une adresse";
include '../includes/header.php';
?>

<div class="container py-5" style="padding-top: 120px !important;">
    <h1 class="text-center mb-5" style="color: var(--deep-brown);">Ajouter une adresse de livraison</h1>

    <?php if ($success): ?>
        <div class="alert alert-success text-center">
            Adresse ajoutée avec succès !
            <div class="mt-3">
                <a href="<?php echo SITE_URL; ?>pages/comptes.php" class="btn btn-primary">Retour à mon compte</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($erreurs)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($erreurs as $err): ?>
                    <li><?php echo htmlspecialchars($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-lg border-0" style="max-width: 600px; margin: 0 auto;">
        <div class="card-body p-5">
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Prénom *</label>
                        <input type="text" name="prenom" class="form-control" required value="<?php echo htmlspecialchars($_POST['prenom']??''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom" class="form-control" required value="<?php echo htmlspecialchars($_POST['nom']??''); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Adresse *</label>
                        <input type="text" name="adresse" class="form-control" required value="<?php echo htmlspecialchars($_POST['adresse']??''); ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Complément d'adresse</label>
                        <input type="text" name="complement" class="form-control" value="<?php echo htmlspecialchars($_POST['complement']??''); ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Code postal *</label>
                        <input type="text" name="code_postal" class="form-control" required value="<?php echo htmlspecialchars($_POST['code_postal']??''); ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Ville *</label>
                        <input type="text" name="ville" class="form-control" required value="<?php echo htmlspecialchars($_POST['ville']??''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Pays</label>
                        <input type="text" name="pays" class="form-control" value="<?php echo htmlspecialchars($_POST['pays']??'France'); ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="telephone" class="form-control" value="<?php echo htmlspecialchars($_POST['telephone']??''); ?>">
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="principale" id="principale" <?php echo isset($_POST['principale']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="principale">
                                Définir comme adresse principale
                            </label>
                        </div>
                    </div>

                    <div class="col-12 text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg px-5">Enregistrer l'adresse</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>