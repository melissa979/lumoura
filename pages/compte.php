<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "pages/connexion.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];



// ── Infos utilisateur ────────────────────────────────────────
try {
    $stmt = $pdo->prepare("
        SELECT email, prenom, nom,
               DATE_FORMAT(date_inscription, '%d/%m/%Y') AS date_inscription_format
        FROM utilisateurs WHERE id_utilisateur = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $user = null; }

$nom_complet      = $user ? trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) : 'Utilisateur';
$email            = $user['email']                   ?? 'Non défini';
$date_inscription = $user['date_inscription_format'] ?? 'Non défini';

// ── Commandes ────────────────────────────────────────────────
$commandes    = [];
$nb_commandes = 0;
try {
    $stmt = $pdo->prepare("
        SELECT id_commande, numero_commande, date_commande, statut, montant
        FROM commandes
        WHERE id_utilisateur = ?
        ORDER BY date_commande DESC
        LIMIT 3
    ");
    $stmt->execute([$user_id]);
    $commandes    = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $nb_commandes = count($commandes);
} catch (PDOException $e) {
    error_log('compte.php commandes: ' . $e->getMessage());
    $commandes = [];
}

// ── Adresses ────────────────────────────────────────────────
$adresses = [];
try {
    $stmt = $pdo->prepare("
        SELECT id, prenom, nom, adresse, complement_adresse,
               code_postal, ville, pays, telephone, est_principale
        FROM adresses
        WHERE id_utilisateur = ?
        ORDER BY est_principale DESC, id DESC
    ");
    $stmt->execute([$user_id]);
    $adresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('compte.php adresses: ' . $e->getMessage());
    $adresses = [];
}

// ── Favoris ─────────────────────────────────────────────────
$nb_favoris = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favori WHERE id_utilisateur = ?");
    $stmt->execute([$user_id]);
    $nb_favoris = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('compte.php favoris: ' . $e->getMessage());
    $nb_favoris = 0;
}

$pageTitle = "Mon Compte ";
include '../includes/header.php';
?>

<div class="compte-page">

    <div class="compte-hero">
        <h1>MON COMPTE</h1>
        <p>Bienvenue, <strong><?= htmlspecialchars($nom_complet) ?></strong></p>
    </div>

    <div class="compte-layout">

        <!-- ── Profil ── -->
        <div class="compte-profil">
            <div class="profil-card">
                <div class="profil-avatar"><i class="fas fa-user-circle"></i></div>
                <h3><?= htmlspecialchars($nom_complet) ?></h3>
                <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($email) ?></p>
                <p><i class="fas fa-calendar-alt"></i> Inscrit le <?= htmlspecialchars($date_inscription) ?></p>
                <a href="<?= SITE_URL ?>pages/modifier_profil.php" class="btn-modifier">
                    <i class="fas fa-edit"></i> Modifier mon profil
                </a>
            </div>
        </div>

        <!-- ── Blocs ── -->
        <div class="compte-blocs">

            <!-- Commandes -->
            <div class="bloc-card">
                <div class="bloc-header">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>Mes Commandes</h3>
                </div>
                <?php if ($nb_commandes > 0): ?>
                    <div class="commandes-list">
                        <?php foreach ($commandes as $cmd): ?>
                            <div class="commande-ligne">
                                <div>
                                    <span class="cmd-ref"><?= htmlspecialchars($cmd['numero_commande']) ?></span>
                                    <span class="cmd-date"><?= date('d/m/Y', strtotime($cmd['date_commande'])) ?></span>
                                </div>
                                <div>
                                    <span class="cmd-statut statut-<?= htmlspecialchars($cmd['statut']) ?>">
                                        <?= ucfirst(htmlspecialchars($cmd['statut'])) ?>
                                    </span>
                                    <span class="cmd-montant"><?= formatPrice($cmd['montant']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= SITE_URL ?>pages/commandes.php" class="btn-bloc">
                        Voir toutes mes commandes
                    </a>
                <?php else: ?>
                    <p class="bloc-vide">0 commande en cours</p>
                    <a href="<?= SITE_URL ?>pages/catalogue.php" class="btn-bloc">Commencer à acheter</a>
                <?php endif; ?>
            </div>

            <!-- Adresses -->
            <div class="bloc-card">
                <div class="bloc-header">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Mes Adresses</h3>
                </div>
                <?php if (!empty($adresses)): ?>
                    <?php foreach ($adresses as $addr): ?>
                        <div class="adresse-item">
                            <?php if ($addr['est_principale']): ?>
                                <span class="badge-principale">Principale</span>
                            <?php endif; ?>
                            <p>
                                <strong><?= htmlspecialchars(trim(($addr['prenom'] ?? '') . ' ' . ($addr['nom'] ?? ''))) ?></strong><br>
                                <?= htmlspecialchars($addr['adresse'] ?? '') ?><br>
                                <?php if (!empty($addr['complement_adresse'])): ?>
                                    <?= htmlspecialchars($addr['complement_adresse']) ?><br>
                                <?php endif; ?>
                                <?= htmlspecialchars(($addr['code_postal'] ?? '') . ' ' . ($addr['ville'] ?? '')) ?><br>
                                <?= htmlspecialchars($addr['pays'] ?? 'France') ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                    <a href="<?= SITE_URL ?>pages/ajouter_adresse.php" class="btn-bloc">Ajouter une adresse</a>
                <?php else: ?>
                    <p class="bloc-vide">Aucune adresse enregistrée</p>
                    <a href="<?= SITE_URL ?>pages/ajouter_adresse.php" class="btn-bloc">Ajouter une adresse</a>
                <?php endif; ?>
            </div>

            <!-- Favoris -->
            <div class="bloc-card bloc-full">
                <div class="bloc-header">
                    <i class="fas fa-heart"></i>
                    <h3>Ma Liste d'envies</h3>
                </div>
                <?php if ($nb_favoris > 0): ?>
                    <p class="bloc-info">
                        <span class="nb-favoris"><?= $nb_favoris ?></span>
                        bijou<?= $nb_favoris > 1 ? 'x' : '' ?> sauvegardé<?= $nb_favoris > 1 ? 's' : '' ?>
                    </p>
                <?php else: ?>
                    <p class="bloc-vide">Aucun article sauvegardé</p>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>pages/liste_envies.php" class="btn-bloc">
                    <i class="fas fa-heart"></i> Voir mes favoris
                </a>
            </div>

        </div><!-- /.compte-blocs -->
    </div><!-- /.compte-layout -->
</div><!-- /.compte-page -->

<style>
:root { --gold:#d4af37; --gold-dark:#b8972e; --brown:#3d2b1f; --cream:#fdfbf7; }

.compte-page { max-width:1100px; margin:0 auto; padding:40px 20px 80px; }

.compte-hero { text-align:center; margin-bottom:40px; }
.compte-hero h1 { font-size:2.5rem; color:var(--brown); font-family:'Cinzel',serif; letter-spacing:3px; }
.compte-hero p  { font-size:1.1rem; color:#888; margin-top:8px; }
.compte-hero strong { color:var(--brown); }

.compte-layout {
    display:grid;
    grid-template-columns: 300px 1fr;
    gap:28px;
    align-items:start;
}

/* Profil */
.profil-card {
    background:white; border-radius:20px; padding:35px 25px;
    text-align:center; box-shadow:0 4px 20px rgba(0,0,0,0.07);
    border:1px solid #f0ebe3;
}
.profil-avatar i { font-size:5rem; color:var(--gold); }
.profil-card h3 { font-size:1.3rem; color:var(--brown); margin:15px 0 8px; font-weight:700; }
.profil-card p  { font-size:14px; color:#666; margin:6px 0; display:flex; align-items:center; justify-content:center; gap:8px; }
.profil-card p i { color:var(--gold); }
.btn-modifier {
    display:inline-block; margin-top:20px; padding:12px 28px;
    background:var(--gold); color:white; border-radius:25px;
    text-decoration:none; font-weight:600; font-size:14px;
    transition:all .2s;
}
.btn-modifier:hover { background:var(--gold-dark); transform:translateY(-2px); }

/* Blocs */
.compte-blocs { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.bloc-card {
    background:white; border-radius:16px; padding:25px;
    box-shadow:0 4px 20px rgba(0,0,0,0.07); border:1px solid #f0ebe3;
}
.bloc-full {
    grid-column:1/-1;
    display:flex; align-items:center; gap:30px; flex-wrap:wrap;
}
.bloc-full .bloc-info { margin-bottom:0; }

.bloc-header {
    display:flex; align-items:center; gap:12px;
    margin-bottom:16px; padding-bottom:12px;
    border-bottom:2px solid var(--gold);
}
.bloc-header i  { font-size:1.5rem; color:var(--gold); }
.bloc-header h3 { font-size:1.1rem; color:var(--brown); font-weight:700; margin:0; }

.bloc-vide { color:#aaa; font-size:14px; font-style:italic; margin-bottom:15px; }
.bloc-info { font-size:15px; color:#666; margin-bottom:15px; display:flex; align-items:center; gap:10px; }
.nb-favoris { font-size:2.5rem; font-weight:800; color:var(--gold); line-height:1; }

.btn-bloc {
    display:inline-flex; align-items:center; gap:6px;
    padding:10px 22px;
    border:2px solid var(--gold); color:var(--gold);
    border-radius:25px; text-decoration:none;
    font-size:13px; font-weight:600; transition:all .2s;
    white-space:nowrap;
}
.btn-bloc:hover { background:var(--gold); color:white; }

/* Commandes */
.commandes-list { margin-bottom:15px; }
.commande-ligne {
    display:flex; justify-content:space-between; align-items:center;
    padding:10px 0; border-bottom:1px solid #f5f0ea; font-size:13px;
}
.commande-ligne:last-child { border-bottom:none; }
.cmd-ref   { font-weight:700; color:var(--brown); display:block; }
.cmd-date  { color:#aaa; font-size:11px; }
.cmd-montant { font-weight:700; color:var(--gold); }
.cmd-statut {
    padding:3px 10px; border-radius:12px;
    font-size:11px; font-weight:600; display:inline-block;
}
.statut-en.attente, [class*="statut-en"] { background:#fff3cd; color:#856404; }
.statut-payee      { background:#d1fae5; color:#065f46; }
.statut-expediee   { background:#dbeafe; color:#1e40af; }
.statut-livree     { background:#d1fae5; color:#065f46; }
.statut-annulee    { background:#fee2e2; color:#991b1b; }

/* Adresses */
.adresse-item {
    padding:12px 0; border-bottom:1px solid #f5f0ea;
    font-size:13px; color:#555; line-height:1.8;
}
.adresse-item:last-of-type { border-bottom:none; }
.badge-principale {
    background:var(--gold); color:white;
    font-size:10px; font-weight:700;
    padding:2px 8px; border-radius:10px;
    margin-bottom:5px; display:inline-block;
}

@media (max-width:768px) {
    .compte-layout { grid-template-columns:1fr; }
    .compte-blocs  { grid-template-columns:1fr; }
    .bloc-full     { flex-direction:column; align-items:flex-start; }
}
</style>

<?php include '../includes/footer.php'; ?>