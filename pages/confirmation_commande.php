<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: connexion.php');
    exit();
}

$user_id  = (int) $_SESSION['user_id'];
$order_id = filter_input(INPUT_GET, 'order', FILTER_VALIDATE_INT) ?: 0;

// La commande doit appartenir à l'utilisateur connecté
$stmt = $pdo->prepare('SELECT * FROM commandes WHERE id_commande = ? AND id_utilisateur = ? LIMIT 1');
$stmt->execute([$order_id, $user_id]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    header('Location: commandes.php');
    exit();
}

$stmt = $pdo->prepare(''
    . 'SELECT d.quantite, d.prix_unitaire, p.nom, p.image_url '
    . 'FROM details_commande d '
    . 'INNER JOIN produits p ON p.id_produit = d.id_produit '
    . 'WHERE d.id_commande = ?'
);
$stmt->execute([$order_id]);
$lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sous_total = 0.0;
foreach ($lignes as $l) {
    $sous_total += (float) $l['prix_unitaire'] * (int) $l['quantite'];
}

$modes = [
    'standard' => ['Standard', '3 à 5 jours ouvrés', '+5 days'],
    'express'  => ['Express', '48h ouvrées', '+2 days'],
    'premium'  => ['Premium', '24h chrono', '+1 day'],
];
$mode = $modes[$commande['mode_livraison']] ?? $modes['standard'];
$livraison_estimee = date('d/m/Y', strtotime($commande['date_commande'] . ' ' . $mode[2]));
$adresse = preg_replace('/^Livraison : /', '', (string) $commande['notes']);

function euro($v) { return number_format((float) $v, 2, ',', ' ') . ' €'; }

$pageTitle = 'Commande confirmée - Éclat d\'Or';
include '../includes/header.php';
?>

<div class="confirm-page">

    <section class="confirm-hero">
        <div class="confirm-check"><i class="fas fa-check"></i></div>
        <span class="confirm-eyebrow">Merci pour votre confiance</span>
        <h1>Votre commande est confirmée</h1>
        <p>Nous préparons vos bijoux avec le plus grand soin.</p>
        <div class="confirm-number">
            Commande <strong><?= htmlspecialchars($commande['numero_commande']) ?></strong>
            <button type="button" class="confirm-copy" data-copy="<?= htmlspecialchars($commande['numero_commande']) ?>" title="Copier le numéro">
                <i class="far fa-copy"></i>
            </button>
        </div>
    </section>

    <!-- Suivi en étapes -->
    <section class="confirm-steps">
        <div class="step done"><i class="fas fa-receipt"></i><span>Commande reçue</span></div>
        <div class="step-line"></div>
        <div class="step"><i class="fas fa-gem"></i><span>Préparation</span></div>
        <div class="step-line"></div>
        <div class="step"><i class="fas fa-truck"></i><span>Expédition</span></div>
        <div class="step-line"></div>
        <div class="step"><i class="fas fa-gift"></i><span>Livraison</span></div>
    </section>

    <div class="confirm-layout">
        <section class="confirm-card">
            <h2>Vos bijoux</h2>
            <?php foreach ($lignes as $l):
                $img = !empty($l['image_url']) ? $l['image_url'] : 'https://via.placeholder.com/120?text=Bijou'; ?>
                <div class="confirm-item">
                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($l['nom']) ?>"
                         onerror="this.src='https://via.placeholder.com/120?text=Bijou'">
                    <div class="confirm-item-info">
                        <strong><?= htmlspecialchars($l['nom']) ?></strong>
                        <span>Quantité : <?= (int) $l['quantite'] ?> × <?= euro($l['prix_unitaire']) ?></span>
                    </div>
                    <b><?= euro($l['prix_unitaire'] * $l['quantite']) ?></b>
                </div>
            <?php endforeach; ?>

            <div class="confirm-totals">
                <div><span>Sous-total</span><span><?= euro($sous_total) ?></span></div>
                <div><span>Livraison (<?= $mode[0] ?>)</span>
                    <span><?= (float) $commande['frais_livraison'] > 0 ? euro($commande['frais_livraison']) : 'Gratuite' ?></span></div>
                <div class="confirm-total"><span>Total</span><strong><?= euro($commande['montant']) ?></strong></div>
            </div>
        </section>

        <aside class="confirm-side">
            <div class="confirm-card">
                <h3><i class="fas fa-map-marker-alt"></i> Livraison</h3>
                <p><?= htmlspecialchars($adresse ?: 'Adresse enregistrée') ?></p>
                <p class="confirm-muted"><?= $mode[0] ?> · <?= $mode[1] ?></p>
                <p class="confirm-date">Arrivée estimée : <strong><?= $livraison_estimee ?></strong></p>
            </div>
            <div class="confirm-card">
                <h3><i class="fas fa-info-circle"></i> Détails</h3>
                <p>Date : <?= date('d/m/Y à H:i', strtotime($commande['date_commande'])) ?></p>
                <p>Statut : <span class="confirm-badge"><?= ucfirst(htmlspecialchars($commande['statut'])) ?></span></p>
            </div>
            <a href="commandes.php" class="confirm-btn confirm-btn-dark"><i class="fas fa-list"></i> Suivre mes commandes</a>
            <a href="catalogue.php" class="confirm-btn confirm-btn-light"><i class="fas fa-arrow-left"></i> Continuer mes achats</a>
            <button type="button" class="confirm-btn confirm-btn-light" onclick="window.print()"><i class="fas fa-print"></i> Imprimer</button>
        </aside>
    </div>
</div>

<style>
.confirm-page { --gold:#d4af37; --gold-dark:#a98216; --brown:#3d2b28; --line:#eadfd3;
    max-width:1150px; margin:0 auto; padding:20px 24px 90px; color:var(--brown); }

.confirm-hero { position:relative; overflow:hidden; margin:70px 0 30px; padding:55px 30px; text-align:center;
    border-radius:28px; background:linear-gradient(115deg,#f1e8e1 0%,#b9a49a 45%,#5a3825 100%);
    box-shadow:0 24px 50px rgba(61,43,40,.14); color:#fff; }
.confirm-check { width:82px; height:82px; margin:0 auto 18px; display:grid; place-items:center; border-radius:50%;
    background:var(--gold); font-size:34px; box-shadow:0 0 0 10px rgba(255,255,255,.18);
    animation:confirmPop .6s cubic-bezier(.2,1.6,.4,1) both; }
@keyframes confirmPop { from { transform:scale(0); } to { transform:scale(1); } }
.confirm-eyebrow { font-size:11px; font-weight:800; letter-spacing:2px; text-transform:uppercase; color:#fff4d5; }
.confirm-hero h1 { margin:10px 0 8px; color:#fff; font-family:'Playfair Display',serif; font-size:clamp(2rem,4vw,3.2rem); }
.confirm-hero p { margin:0; color:rgba(255,255,255,.88); }
.confirm-number { display:inline-flex; align-items:center; gap:10px; margin-top:22px; padding:10px 18px;
    border-radius:30px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.35); }
.confirm-copy { border:0; background:none; color:#fff; cursor:pointer; font-size:15px; }

.confirm-steps { display:flex; align-items:center; justify-content:center; margin:0 0 30px; padding:22px;
    border:1px solid var(--line); border-radius:18px; background:#fff; }
.step { display:flex; flex-direction:column; align-items:center; gap:7px; font-size:12px; color:#a99a90; }
.step i { width:44px; height:44px; display:grid; place-items:center; border-radius:50%; background:#f3ece4; }
.step.done { color:var(--brown); font-weight:700; }
.step.done i { background:var(--gold); color:#fff; }
.step-line { flex:1; max-width:110px; height:2px; margin:0 10px 22px; background:#eadfd3; }

.confirm-layout { display:grid; grid-template-columns:minmax(0,1fr) 340px; gap:26px; align-items:start; }
.confirm-card { padding:24px; margin-bottom:18px; border:1px solid var(--line); border-radius:20px; background:#fff;
    box-shadow:0 10px 24px rgba(61,43,40,.06); }
.confirm-card h2, .confirm-card h3 { margin:0 0 16px; font-family:'Playfair Display',serif; }
.confirm-card h3 { font-size:18px; } .confirm-card h3 i { color:var(--gold); margin-right:6px; }
.confirm-card p { margin:0 0 8px; font-size:14px; }
.confirm-muted { color:#8b7b73; }
.confirm-date strong { color:var(--gold-dark); }
.confirm-badge { padding:3px 10px; border-radius:12px; background:#fff3cd; color:#856404; font-size:12px; font-weight:700; }

.confirm-item { display:flex; align-items:center; gap:15px; padding:12px 0; border-bottom:1px solid var(--line); }
.confirm-item img { width:70px; height:70px; object-fit:cover; border-radius:12px; }
.confirm-item-info { flex:1; min-width:0; } .confirm-item-info strong { display:block; }
.confirm-item-info span { font-size:13px; color:#8b7b73; }
.confirm-item b { color:var(--gold-dark); white-space:nowrap; }
.confirm-totals { margin-top:16px; } .confirm-totals div { display:flex; justify-content:space-between; margin-bottom:9px; font-size:14px; }
.confirm-total { padding-top:12px; border-top:2px solid var(--brown); font-size:18px !important; font-weight:800; }
.confirm-total strong { color:var(--gold-dark); font-size:22px; }

.confirm-btn { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:13px; margin-bottom:10px;
    border-radius:12px; font-size:14px; font-weight:800; text-decoration:none; cursor:pointer; transition:transform .2s; }
.confirm-btn:hover { transform:translateY(-2px); }
.confirm-btn-dark { background:linear-gradient(135deg,var(--brown),#624436); color:#fff; border:0; }
.confirm-btn-dark:hover { color:#fff; }
.confirm-btn-light { background:#fff; color:var(--brown); border:1px solid var(--line); }

@media (max-width:900px) { .confirm-layout { grid-template-columns:1fr; } }
@media (max-width:600px) { .step span { display:none; } .step-line { margin-bottom:0; } .confirm-hero { padding:40px 20px; } }
@media print { header, footer, .confirm-btn, .confirm-copy { display:none !important; } }
</style>

<script>
document.querySelectorAll('.confirm-copy').forEach(function (btn) {
    btn.addEventListener('click', function () {
        navigator.clipboard.writeText(btn.dataset.copy).then(function () {
            btn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(function () { btn.innerHTML = '<i class="far fa-copy"></i>'; }, 1500);
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
