<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Sécurité : rediriger si pas connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "pages/connexion.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Récupération des informations utilisateur (corrigé avec les vrais noms)
$email = 'Non défini';
$date_inscription = 'Non défini';
$nom_complet = $_SESSION['user_nom'] ?? 'Utilisateur';

try {
    $stmt = $pdo->prepare("
        SELECT email, 
               DATE_FORMAT(date_inscription, '%d/%m/%Y') AS date_inscription_format,
               prenom,
               nom
        FROM utilisateurs 
        WHERE id_utilisateur = :id
    ");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $email = $user['email'] ?? 'Non défini';
        $date_inscription = $user['date_inscription_format'] ?? 'Non défini';
        
        $prenom = trim($user['prenom'] ?? '');
        $nom    = trim($user['nom'] ?? '');
        $nom_complet = $prenom . ($prenom && $nom ? ' ' : '') . $nom;
        $nom_complet = $nom_complet ?: 'Utilisateur';

        $_SESSION['user_nom'] = $nom_complet;
        $_SESSION['user_email'] = $email;
    }

} catch (PDOException $e) {
    echo '<div style="background:#ffebee; color:#c62828; padding:15px; margin:20px 0; border-radius:8px;">
        Erreur chargement profil : ' . htmlspecialchars($e->getMessage()) . '
    </div>';
}

// Récupération des adresses (table n'existe pas encore → on gère l'erreur)
$has_adresses = false;
$adresses = [];

try {
    $stmt_adresses = $pdo->prepare("
        SELECT id, prenom, nom, adresse, complement, code_postal, ville, pays, telephone, est_principale
        FROM adresses 
        WHERE utilisateur_id = :uid 
        ORDER BY est_principale DESC, id DESC
    ");
    $stmt_adresses->execute(['uid' => $user_id]);
    $adresses = $stmt_adresses->fetchAll(PDO::FETCH_ASSOC);
    $has_adresses = count($adresses) > 0;
} catch (PDOException $e) {
    // Pas d'erreur visible pour l'utilisateur final, on garde juste le message "Aucune adresse"
    $has_adresses = false;
}

$pageTitle = "Mon Compte";
include '../includes/header.php';
?>

<div class="container py-5" style="padding-top: 120px !important;">
    <!-- Titre principal -->
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold" style="color: var(--deep-brown); font-family: 'Playfair Display', serif;">
            MON COMPTE
        </h1>
        <p class="lead mt-3" style="color: var(--warm-brown);">
            Bienvenue, <strong style="color: var(--deep-brown);">
                <?php echo htmlspecialchars($nom_complet); ?>
            </strong>
        </p>
    </div>

    <div class="row g-5 justify-content-center">
        <!-- Bloc Profil principal -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-lg text-center" style="background: var(--white); border-radius: 30px; overflow: hidden;">
                <div class="card-body p-5">
                    <i class="bi bi-person-circle display-3 mb-4" style="color: var(--gold);"></i>
                    <h4 class="fw-bold mb-3" style="color: var(--deep-brown);">
                        <?php echo htmlspecialchars($nom_complet); ?>
                    </h4>
                    <p class="mb-3" style="color: var(--gray); font-size: 1.1rem;">
                        <strong>Email :</strong> <?php echo htmlspecialchars($email); ?>
                    </p>
                    <p class="mb-4" style="color: var(--gray); font-size: 1.1rem;">
                        <strong>Inscrit le :</strong> <?php echo htmlspecialchars($date_inscription); ?>
                    </p>
                    <a href="<?php echo SITE_URL; ?>pages/modifier_profil.php" class="btn btn-primary px-5 py-3 rounded-pill fw-semibold">
                        Modifier mon profil
                    </a>
                </div>
            </div>
        </div>

        <!-- Blocs actions rapides -->
        <div class="col-lg-7">
            <div class="row g-4">

                <!-- Mes Commandes -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-lg text-center h-100" style="background: var(--white); border-radius: 30px;">
                        <div class="card-body p-5">
                            <i class="bi bi-bag display-4 mb-3" style="color: var(--gold);"></i>
                            <h5 class="fw-bold mb-3" style="color: var(--deep-brown);">Mes Commandes</h5>
                            <p class="text-muted mb-4" style="font-size: 1.05rem;">
                                0 commande en cours
                            </p>
                            <a href="<?php echo SITE_URL; ?>pages/catalogue.php" class="btn btn-outline-warning px-4 py-2 rounded-pill">
                                Commencer à acheter
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mes Adresses -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-lg text-center h-100" style="background: var(--white); border-radius: 30px;">
                        <div class="card-body p-5">
                            <i class="bi bi-house-door display-4 mb-3" style="color: var(--gold);"></i>
                            <h5 class="fw-bold mb-3" style="color: var(--deep-brown);">Mes Adresses</h5>

                            <?php if ($has_adresses): ?>
                                <div class="text-start mb-4">
                                    <?php foreach ($adresses as $addr): ?>
                                        <div class="border-bottom pb-3 mb-3">
                                            <strong>
                                                <?php echo htmlspecialchars(($addr['prenom'] ?? '') . ' ' . ($addr['nom'] ?? '')); ?>
                                                <?php if (isset($addr['est_principale']) && $addr['est_principale']): ?>
                                                    <span class="badge bg-success ms-2">Principale</span>
                                                <?php endif; ?>
                                            </strong><br>
                                            <?php echo htmlspecialchars($addr['adresse'] ?? ''); ?>
                                            <?php if (isset($addr['complement']) && $addr['complement']): ?>
                                                <br><?php echo htmlspecialchars($addr['complement']); ?>
                                            <?php endif; ?><br>
                                            <?php echo htmlspecialchars(($addr['code_postal'] ?? '') . ' ' . ($addr['ville'] ?? '')); ?><br>
                                            <?php echo htmlspecialchars($addr['pays'] ?? ''); ?>
                                            <?php if (isset($addr['telephone']) && $addr['telephone']): ?>
                                                <br>Tél : <?php echo htmlspecialchars($addr['telephone']); ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-4" style="font-size: 1.05rem;">
                                    Aucune adresse enregistrée
                                </p>
                            <?php endif; ?>

                            <a href="<?php echo SITE_URL; ?>pages/ajouter_adresse.php" class="btn btn-outline-warning px-4 py-2 rounded-pill">
                                Ajouter une adresse
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Liste d'envies -->
                <div class="col-12">
                    <div class="card border-0 shadow-lg text-center" style="background: var(--white); border-radius: 30px;">
                        <div class="card-body p-5">
                            <i class="bi bi-heart display-4 mb-3" style="color: var(--gold);"></i>
                            <h5 class="fw-bold mb-3" style="color: var(--deep-brown);">Ma Liste d'envies</h5>
                            <p class="text-muted mb-4" style="font-size: 1.05rem;">
                                Aucun article sauvegardé
                            </p>
                            <a href="<?php echo SITE_URL; ?>pages/liste_envies.php" class="btn btn-outline-warning px-4 py-2 rounded-pill">
    Voir mes favoris
</a>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>