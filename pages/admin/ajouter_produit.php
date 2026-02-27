<?php
session_start();

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Protection : seulement admin ou propriétaire
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    $_SESSION['error'] = "Accès réservé aux administrateurs.";
    header('Location: ../connexion.php');
    exit;
}

$message = '';
$erreur  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom         = trim($_POST['nom'] ?? '');
    $prix        = floatval($_POST['prix'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $categorie   = trim($_POST['categorie'] ?? ''); // Femme / Homme / Unisexe / Cadeaux

    if (empty($nom) || $prix <= 0 || empty($categorie)) {
        $erreur = "Nom, prix et catégorie sont obligatoires !";
    } else {
        $image_url = 'images/placeholder.png'; // image par défaut

        // Upload image
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 3000000) { // max ~3Mo
                $newName = 'produit_' . uniqid() . '.' . $ext;
                $dossier = '../images/produits/'; // Crée ce dossier si pas existant !

                if (!is_dir($dossier)) {
                    mkdir($dossier, 0755, true);
                }

                $chemin = $dossier . $newName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $chemin)) {
                    $image_url = 'images/produits/' . $newName;
                } else {
                    $erreur = "Erreur lors de l'enregistrement de l'image.";
                }
            } else {
                $erreur = "Image invalide (formats : jpg, jpeg, png, webp | max 3 Mo).";
            }
        }

        if (empty($erreur)) {
            try {
                $sql = "INSERT INTO produits 
                        (nom, prix, description, image_url, categorie, date_ajout) 
                        VALUES (:nom, :prix, :description, :image_url, :categorie, NOW())";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'nom'         => $nom,
                    'prix'        => $prix,
                    'description' => $description,
                    'image_url'   => $image_url,
                    'categorie'   => $categorie
                ]);

                $message = "Le bijou « $nom » a été ajouté avec succès ! ✨";
            } catch (PDOException $e) {
                $erreur = "Erreur lors de l'ajout : " . $e->getMessage();
            }
        }
    }
}

$pageTitle = "Ajouter un Produit - Admin Lumoura";
include '../includes/header.php';
?>

<div class="container py-5" style="padding-top: 120px !important;">
    <h1 class="mb-4 text-center">Ajouter un nouveau bijou 💎</h1>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($erreur): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= htmlspecialchars($erreur) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="row g-4">
        <div class="col-md-6">
            <label class="form-label">Nom du bijou *</label>
            <input type="text" name="nom" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Prix (€) *</label>
            <input type="number" step="0.01" min="0" name="prix" class="form-control" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Catégorie *</label>
            <select name="categorie" class="form-select" required>
                <option value="">Choisir...</option>
                <option value="Femme">Femme</option>
                <option value="Homme">Homme</option>
                <option value="Unisexe">Unisexe</option>
                <option value="Cadeaux">Cadeaux</option>
                <option value="Collections">Collections</option>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Description (optionnel)</label>
            <textarea name="description" class="form-control" rows="4"></textarea>
        </div>

        <div class="col-12">
            <label class="form-label">Image du bijou</label>
            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
            <small class="text-muted">Formats : JPG, PNG, WebP – Max 3 Mo</small>
        </div>

        <div class="col-12 text-center">
            <button type="submit" class="btn btn-primary btn-lg px-5">Ajouter le bijou</button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>