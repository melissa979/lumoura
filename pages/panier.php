<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: connexion.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ CORRIGÉ : on récupère le mode de livraison choisi dans le panier (GET au chargement,
// puis renvoyé en POST via le champ caché ci-dessous lors de la validation)
$modeLivraisonChoisi = $_POST['mode_livraison'] ?? $_GET['mode_livraison'] ?? 'standard';

// Récupérer le panier depuis la BDD
$cart_items    = [];
$cart_subtotal = 0;

try {
    $stmt = $pdo->prepare("
        SELECT p.*, pan.quantite
        FROM panier pan
        JOIN produits p ON p.id_produit = pan.id_produit
        WHERE pan.id_utilisateur = ?
        ORDER BY pan.date_ajout DESC
    ");
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $price    = (float)$row['prix'];
        $discount = (float)($row['promotion_pourcentage'] ?? 0);
        if ($discount > 0) $price = $price * (1 - $discount / 100);
        $total          = $price * $row['quantite'];
        $cart_subtotal += $total;
        $cart_items[]   = ['product' => $row, 'quantity' => $row['quantite'], 'price' => $price, 'total' => $total];
    }
} catch (PDOException $e) {
    die('Erreur chargement panier');
}

if (empty($cart_items)) {
    header('Location: panier.php');
    exit();
}

// ✅ CORRIGÉ : mêmes règles de tarifs que dans panier.php, appliquées selon le mode choisi
$fraisParMode = [
    'standard' => $cart_subtotal >= 150 ? 0 : 9.90,
    'express'  => 14.90,
    'premium'  => 24.90,
];
$nomModeAffiche = [
    'standard' => 'Standard (3-5 jours)',
    'express'  => 'Express (48h)',
    'premium'  => 'Premium (24h chrono)',
];

if (!array_key_exists($modeLivraisonChoisi, $fraisParMode)) {
    $modeLivraisonChoisi = 'standard';
}

$shipping   = $fraisParMode[$modeLivraisonChoisi];
$cart_total = $cart_subtotal + $shipping;

// Récupérer infos utilisateur
$userStmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

// Traitement de la commande
$success_message = '';
$error_message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['passer_commande'])) {
    $adresse       = trim($_POST['adresse']      ?? '');
    $code_postal   = trim($_POST['code_postal']  ?? '');
    $ville         = trim($_POST['ville']        ?? '');
    $mode_paiement = trim($_POST['mode_paiement'] ?? '');

    if (empty($adresse) || empty($code_postal) || empty($ville) || empty($mode_paiement)) {
        $error_message = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        try {
            $pdo->beginTransaction();

            $numero = 'LUM-' . strtoupper(substr(uniqid(), -6)) . '-' . date('Y');

            $cmdStmt = $pdo->prepare("
                INSERT INTO commandes (numero_commande, id_utilisateur, date_commande, statut, montant, frais_livraison, mode_livraison, mode_paiement)
                VALUES (?, ?, NOW(), 'en attente', ?, ?, ?, ?)
            ");
            $cmdStmt->execute([$numero, $user_id, $cart_total, $shipping, $nomModeAffiche[$modeLivraisonChoisi], $mode_paiement]);
            $commande_id = $pdo->lastInsertId();

            $detailStmt = $pdo->prepare("
                INSERT INTO details_commande (id_commande, id_produit, quantite, prix_unitaire)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cart_items as $item) {
                $detailStmt->execute([$commande_id, $item['product']['id_produit'], $item['quantity'], $item['price']]);
            }

            $pdo->prepare("DELETE FROM panier WHERE id_utilisateur = ?")->execute([$user_id]);
            $pdo->commit();
            $success_message = $numero;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error_message = 'Erreur lors de la commande : ' . $e->getMessage();
        }
    }
}

$pageTitle = "Passer ma commande - Éclat d'Or";
include '../includes/header.php';
?>

<div class="commande-page">

<?php if ($success_message): ?>
    <div class="commande-success">
        <div class="success-icon"><i class="fas fa-check-circle"></i></div>
        <h1>Commande confirmée !</h1>
        <p class="success-ref">Référence : <strong><?= htmlspecialchars($success_message) ?></strong></p>
        <p>Merci pour votre achat. Vous recevrez un email de confirmation.</p>
        <div class="success-actions">
            <a href="compte.php?page=orders" class="btn-gold">
                <i class="fas fa-shopping-bag"></i> Voir mes commandes
            </a>
            <a href="catalogue.php" class="btn-outline">
                <i class="fas fa-store"></i> Continuer mes achats
            </a>
        </div>
    </div>

<?php else: ?>

    <div class="commande-header">
        <h1><i class="fas fa-lock"></i> Finaliser ma commande</h1>
        <div class="commande-steps">
            <span class="step active"><i class="fas fa-shopping-bag"></i> Panier</span>
            <span class="step-arrow">→</span>
            <span class="step active"><i class="fas fa-map-marker-alt"></i> Livraison</span>
            <span class="step-arrow">→</span>
            <span class="step"><i class="fas fa-credit-card"></i> Paiement</span>
            <span class="step-arrow">→</span>
            <span class="step"><i class="fas fa-check"></i> Confirmation</span>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert-error-box">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="commande-form">
        <!-- ✅ CORRIGÉ : on transmet le mode de livraison choisi dans le panier -->
        <input type="hidden" name="mode_livraison" value="<?= htmlspecialchars($modeLivraisonChoisi) ?>">

        <div class="commande-layout">

            <div class="commande-left">

                <div class="commande-section">
                    <h2><i class="fas fa-map-marker-alt"></i> Adresse de livraison</h2>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Prénom *</label>
                            <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required class="form-input">
                        </div>
                        <div class="form-group">
                            <label>Nom *</label>
                            <input type="text" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Adresse *</label>
                        <div class="adresse-wrapper">
                            <input type="text" name="adresse" id="adresse" placeholder="123 rue de la Paix"
                                   required class="form-input"
                                   value="<?= htmlspecialchars($user['adresse_livraison'] ?? '') ?>"
                                   autocomplete="off">
                        </div>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Code postal *</label>
                            <input type="text" name="code_postal" id="code_postal" placeholder="75008"
                                   required class="form-input"
                                   value="<?= htmlspecialchars($user['code_postal_livraison'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Ville *</label>
                            <input type="text" name="ville" id="ville" placeholder="Paris"
                                   required class="form-input"
                                   value="<?= htmlspecialchars($user['ville_livraison'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="tel" name="telephone" class="form-input"
                               value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"
                               placeholder="06 12 34 56 78">
                    </div>
                </div>

                <div class="commande-section">
                    <h2><i class="fas fa-credit-card"></i> Mode de paiement</h2>

                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="mode_paiement" value="Carte bancaire" checked>
                            <div class="payment-card">
                                <i class="fas fa-credit-card"></i>
                                <span>Carte bancaire</span>
                                <div class="card-logos">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" height="20">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" height="20">
                                </div>
                            </div>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="mode_paiement" value="PayPal">
                            <div class="payment-card">
                                <i class="fab fa-paypal"></i>
                                <span>PayPal</span>
                            </div>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="mode_paiement" value="Virement bancaire">
                            <div class="payment-card">
                                <i class="fas fa-university"></i>
                                <span>Virement bancaire</span>
                            </div>
                        </label>
                    </div>

                    <div class="card-fields" id="cardFields">
                        <div class="form-group">
                            <label>Numéro de carte</label>
                            <input type="text" class="form-input" placeholder="1234 5678 9012 3456" maxlength="19">
                        </div>
                        <div class="form-row-2">
                            <div class="form-group">
                                <label>Date d'expiration</label>
                                <input type="text" class="form-input" placeholder="MM/AA" maxlength="5">
                            </div>
                            <div class="form-group">
                                <label>CVV</label>
                                <input type="text" class="form-input" placeholder="123" maxlength="3">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Titulaire de la carte</label>
                            <input type="text" class="form-input" placeholder="NOM PRÉNOM">
                        </div>
                    </div>
                </div>

            </div>

            <div class="commande-right">
                <div class="commande-recap">
                    <h2><i class="fas fa-shopping-bag"></i> Récapitulatif</h2>

                    <div class="recap-items">
                        <?php foreach ($cart_items as $item):
                            $img = $item['product']['image_url'] ?: 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?auto=format&fit=crop&w=200&q=80';
                        ?>
                        <div class="recap-item">
                            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($item['product']['nom']) ?>">
                            <div class="recap-item-info">
                                <span class="recap-name"><?= htmlspecialchars($item['product']['nom']) ?></span>
                                <span class="recap-qty">× <?= $item['quantity'] ?></span>
                            </div>
                            <span class="recap-price"><?= formatPrice($item['total']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="recap-totals">
                        <div class="recap-row">
                            <span>Sous-total</span>
                            <span><?= formatPrice($cart_subtotal) ?></span>
                        </div>
                        <div class="recap-row">
                            <span>Livraison (<?= htmlspecialchars($nomModeAffiche[$modeLivraisonChoisi]) ?>)</span>
                            <span><?= $shipping == 0 ? '<span style="color:#d4af37">Gratuite</span>' : formatPrice($shipping) ?></span>
                        </div>
                        <div class="recap-row recap-total">
                            <span>Total TTC</span>
                            <span><?= formatPrice($cart_total) ?></span>
                        </div>
                    </div>

                    <div class="recap-garanties">
                        <p><i class="fas fa-shield-alt"></i> Paiement 100% sécurisé</p>
                        <p><i class="fas fa-undo"></i> Retour gratuit 30 jours</p>
                        <p><i class="fas fa-gem"></i> Certificat d'authenticité</p>
                    </div>

                    <button type="submit" name="passer_commande" class="btn-commander">
                        <i class="fas fa-lock"></i> Confirmer ma commande
                        <span><?= formatPrice($cart_total) ?></span>
                    </button>

                    <p class="recap-back">
                        <a href="panier.php"><i class="fas fa-arrow-left"></i> Retour au panier</a>
                    </p>
                </div>
            </div>

        </div>
    </form>

<?php endif; ?>
</div>

<style>
:root { --gold:#d4af37; --gold-dark:#b8972e; --cream:#fdfbf7; --brown:#3d2b1f; --gray:#f5f5f0; }
.commande-page { max-width:1100px; margin:40px auto; padding:0 20px 60px; }
.commande-header { text-align:center; margin-bottom:40px; }
.commande-header h1 { font-size:2rem; color:var(--brown); margin-bottom:20px; }
.commande-steps { display:flex; align-items:center; justify-content:center; gap:10px; flex-wrap:wrap; }
.step { padding:8px 18px; border-radius:20px; font-size:14px; background:#eee; color:#999; }
.step.active { background:var(--gold); color:white; font-weight:600; }
.step-arrow { color:#ccc; font-size:18px; }
.commande-layout { display:grid; grid-template-columns:1fr 380px; gap:30px; align-items:start; }
.commande-section { background:white; border-radius:12px; padding:30px; margin-bottom:20px; box-shadow:0 2px 15px rgba(0,0,0,0.06); border:1px solid #f0ebe3; }
.commande-section h2 { font-size:1.2rem; color:var(--brown); margin-bottom:25px; padding-bottom:12px; border-bottom:2px solid var(--gold); display:flex; align-items:center; gap:10px; }
.form-group { margin-bottom:18px; }
.form-group label { display:block; font-size:13px; font-weight:600; color:#555; margin-bottom:6px; }
.form-input { width:100%; padding:12px 15px; border:1px solid #ddd; border-radius:8px; font-size:15px; transition:all 0.2s; background:#fafafa; }
.form-input:focus { border-color:var(--gold); outline:none; box-shadow:0 0 0 3px rgba(212,175,55,0.15); background:white; }
.form-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:15px; }
.payment-options { display:flex; gap:12px; margin-bottom:25px; flex-wrap:wrap; }
.payment-option { cursor:pointer; flex:1; min-width:120px; }
.payment-option input { display:none; }
.payment-card { border:2px solid #ddd; border-radius:10px; padding:15px; text-align:center; transition:all 0.2s; background:#fafafa; }
.payment-card i { font-size:1.5rem; color:#999; display:block; margin-bottom:8px; }
.payment-card span { font-size:13px; font-weight:600; color:#555; }
.payment-card .card-logos { margin-top:8px; display:flex; gap:5px; justify-content:center; }
.payment-option input:checked + .payment-card { border-color:var(--gold); background:#fffbf0; }
.payment-option input:checked + .payment-card i { color:var(--gold); }
.card-fields { margin-top:15px; padding-top:15px; border-top:1px solid #f0ebe3; }
.commande-recap { background:white; border-radius:12px; padding:25px; box-shadow:0 2px 15px rgba(0,0,0,0.06); border:1px solid #f0ebe3; position:sticky; top:20px; }
.commande-recap h2 { font-size:1.1rem; color:var(--brown); margin-bottom:20px; padding-bottom:12px; border-bottom:2px solid var(--gold); display:flex; align-items:center; gap:8px; }
.recap-items { margin-bottom:20px; }
.recap-item { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid #f5f5f0; }
.recap-item img { width:55px; height:55px; object-fit:cover; border-radius:8px; }
.recap-item-info { flex:1; }
.recap-name { font-size:13px; font-weight:600; color:var(--brown); display:block; }
.recap-qty { font-size:12px; color:#999; }
.recap-price { font-size:14px; font-weight:700; color:var(--gold); white-space:nowrap; }
.recap-totals { margin:20px 0; }
.recap-row { display:flex; justify-content:space-between; padding:8px 0; font-size:14px; color:#555; border-bottom:1px solid #f5f5f0; }
.recap-total { font-size:18px; font-weight:700; color:var(--brown); border-top:2px solid var(--gold); border-bottom:none; padding-top:15px; margin-top:5px; }
.recap-garanties { margin:15px 0; padding:15px; background:var(--cream); border-radius:8px; }
.recap-garanties p { font-size:13px; color:#666; margin-bottom:6px; display:flex; align-items:center; gap:8px; }
.recap-garanties i { color:var(--gold); }
.btn-commander { width:100%; padding:16px; background:var(--gold); color:white; border:none; border-radius:10px; font-size:16px; font-weight:700; cursor:pointer; transition:all 0.3s; display:flex; align-items:center; justify-content:space-between; margin-top:15px; }
.btn-commander:hover { background:var(--gold-dark); transform:translateY(-2px); box-shadow:0 8px 25px rgba(212,175,55,0.3); }
.recap-back { text-align:center; margin-top:15px; }
.recap-back a { color:var(--gold); font-size:14px; }
.alert-error-box { background:#ffebee; border:1px solid #ef9a9a; border-radius:8px; padding:15px 20px; color:#c62828; margin-bottom:25px; display:flex; align-items:center; gap:10px; }
.commande-success { text-align:center; padding:80px 20px; background:white; border-radius:16px; box-shadow:0 4px 30px rgba(0,0,0,0.08); border:1px solid #f0ebe3; }
.success-icon i { font-size:5rem; color:#27ae60; }
.commande-success h1 { font-size:2.2rem; color:var(--brown); margin:20px 0 10px; }
.success-ref { font-size:1.1rem; color:#555; margin-bottom:10px; }
.success-ref strong { color:var(--gold); }
.success-actions { display:flex; gap:15px; justify-content:center; margin-top:30px; flex-wrap:wrap; }
.btn-gold { padding:14px 28px; background:var(--gold); color:white; border-radius:8px; text-decoration:none; font-weight:600; transition:all 0.2s; }
.btn-gold:hover { background:var(--gold-dark); }
.btn-outline { padding:14px 28px; border:2px solid var(--gold); color:var(--gold); border-radius:8px; text-decoration:none; font-weight:600; transition:all 0.2s; }
.btn-outline:hover { background:var(--gold); color:white; }

.adresse-wrapper { position:relative; }
.adresse-suggestions {
    position:absolute; top:100%; left:0; right:0;
    background:#fff; border:1px solid #e0d5c5;
    border-top:none; border-radius:0 0 10px 10px;
    box-shadow:0 8px 24px rgba(61,43,31,.13);
    z-index:9999; max-height:250px; overflow-y:auto;
}
.adresse-item {
    padding:11px 16px; cursor:pointer; font-size:14px;
    color:#3d2b1f; border-bottom:1px solid #f5f0ea;
    display:flex; align-items:center; gap:10px; transition:background .15s;
}
.adresse-item:last-child { border-bottom:none; }
.adresse-item:hover, .adresse-item.actif { background:#fff9ec; }
.adresse-item i { color:#d4af37; flex-shrink:0; }
.adresse-item-text strong { display:block; font-weight:600; }
.adresse-item-text span { font-size:12px; color:#999; }

@media(max-width:768px) {
    .commande-layout { grid-template-columns:1fr; }
    .form-row-2 { grid-template-columns:1fr; }
    .payment-options { flex-direction:column; }
}
</style>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
document.querySelectorAll('input[name="mode_paiement"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('cardFields').style.display =
            this.value === 'Carte bancaire' ? 'block' : 'none';
    });
});

$(document).ready(function () {
    const $input = $('#adresse');
    const $wrap  = $input.closest('.adresse-wrapper');
    let timer = null, $liste = null, idx = -1;

    function fermer() {
        if ($liste) { $liste.remove(); $liste = null; }
        idx = -1;
    }

    function remplir(f) {
        const p = f.properties;
        $input.val(p.name || '');
        $('#code_postal').val(p.postcode || '');
        $('#ville').val(p.city || '');
        fermer();
    }

    function afficher(features) {
        fermer();
        if (!features.length) return;
        $liste = $('<div class="adresse-suggestions"></div>');
        $.each(features, function(i, f) {
            const p = f.properties;
            const $item = $(`
                <div class="adresse-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="adresse-item-text">
                        <strong>${p.name || ''}</strong>
                        <span>${p.postcode || ''} ${p.city || ''}</span>
                    </div>
                </div>`);
            $item.on('click', function() { remplir(f); });
            $liste.append($item);
        });
        $wrap.append($liste);
    }

    $input.on('input', function() {
        clearTimeout(timer);
        const val = $(this).val().trim();
        if (val.length < 3) { fermer(); return; }
        timer = setTimeout(function() {
            $.getJSON('https://api-adresse.data.gouv.fr/search/',
                { q: val, limit: 6, autocomplete: 1 },
                function(data) { afficher(data.features || []); }
            );
        }, 300);
    });

    $input.on('keydown', function(e) {
        if (!$liste) return;
        const $items = $liste.find('.adresse-item');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            idx = Math.min(idx + 1, $items.length - 1);
            $items.removeClass('actif').eq(idx).addClass('actif');
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            idx = Math.max(idx - 1, 0);
            $items.removeClass('actif').eq(idx).addClass('actif');
        } else if (e.key === 'Enter' && idx >= 0) {
            e.preventDefault();
            $items.eq(idx).trigger('click');
        } else if (e.key === 'Escape') {
            fermer();
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.adresse-wrapper').length) fermer();
    });
});
</script>

<?php include '../includes/footer.php'; ?>