<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "pages/connexion.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$commandes = [];
try {
    $stmt = $pdo->prepare("
        SELECT id_commande, numero_commande, date_commande, statut, montant
        FROM commandes
        WHERE id_utilisateur = ?
        ORDER BY date_commande DESC
    ");
    $stmt->execute([$user_id]);
    $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $commandes = [];
}

$pageTitle = "Mes Commandes - Éclat d'Or";
include '../includes/header.php';
?>

<div class="compte-page" style="margin-top: 100px;">
    <div class="compte-hero">
        <h1>MES COMMANDES</h1>
        <p><a href="compte.php" style="color:#d4af37;"><i class="fas fa-arrow-left"></i> Retour à mon compte</a></p>
    </div>

    <div class="bloc-card" style="max-width: 800px; margin: 0 auto;">
        <?php if (!empty($commandes)): ?>
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
        <?php else: ?>
            <p class="bloc-vide">Vous n'avez encore passé aucune commande.</p>
            <a href="catalogue.php" class="btn-bloc">Découvrir la collection</a>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<style>
:root { --gold:#d4af37; --gold-dark:#b8972e; --brown:#3d2b1f; }

.compte-page { max-width:1100px; margin:0 auto; padding:40px 20px 80px; }
.compte-hero { text-align:center; margin-bottom:40px; }
.compte-hero h1 { font-size:2.5rem; color:var(--brown); font-family:'Playfair Display',serif; letter-spacing:3px; }
.compte-hero p { font-size:1.1rem; color:#888; margin-top:8px; }

.bloc-card { background:white; border-radius:16px; padding:25px; box-shadow:0 4px 20px rgba(0,0,0,0.07); border:1px solid #f0ebe3; }
.bloc-vide { color:#aaa; font-size:14px; font-style:italic; margin-bottom:15px; }

.commandes-list { margin-bottom:15px; }
.commande-ligne {
    display:flex; justify-content:space-between; align-items:center;
    padding:16px 0; border-bottom:1px solid #f5f0ea; font-size:14px;
}
.commande-ligne:last-child { border-bottom:none; }
.cmd-ref { font-weight:700; color:var(--brown); display:block; font-size:15px; }
.cmd-date { color:#aaa; font-size:12px; }
.cmd-montant { font-weight:700; color:var(--gold); margin-left:15px; }
.cmd-statut {
    padding:4px 12px; border-radius:12px;
    font-size:11px; font-weight:600; display:inline-block;
}
.statut-en.attente, [class*="statut-en"] { background:#fff3cd; color:#856404; }
.statut-payee    { background:#d1fae5; color:#065f46; }
.statut-expediee { background:#dbeafe; color:#1e40af; }
.statut-livree   { background:#d1fae5; color:#065f46; }
.statut-annulee  { background:#fee2e2; color:#991b1b; }

.btn-bloc {
    display:inline-flex; align-items:center; gap:6px; padding:10px 22px;
    border:2px solid var(--gold); color:var(--gold); border-radius:25px;
    text-decoration:none; font-size:13px; font-weight:600; transition:all .2s;
}
.btn-bloc:hover { background:var(--gold); color:white; }
</style>