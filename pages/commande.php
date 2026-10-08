<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: connexion.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$errors = [];
$success = false;
$command_created = false;

// Récupérer les produits du panier
try {
    $stmt = $pdo->prepare(''
        . 'SELECT pa.id_produit, pa.quantite, '
        . 'p.nom, p.prix, p.promotion_pourcentage, p.stock '
        . 'FROM panier pa '
        . 'INNER JOIN produits p ON pa.id_produit = p.id_produit '
        . 'WHERE pa.id_utilisateur = ? '
        . 'ORDER BY pa.date_ajout DESC'
    );
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $errors[] = 'Erreur lors du chargement du panier.';
    $cart_items = [];
}

// Si le panier est vide, rediriger vers le catalogue
if (empty($cart_items)) {
    header('Location: panier.php');
    exit();
}

// Calculer le total du panier
$total_cart = 0.0;
$items_count = 0;

foreach ($cart_items as &$item) {
    $prix_base = (float) $item['prix'];
    $promo = (float) ($item['promotion_pourcentage'] ?? 0);
    $prix_final = $promo > 0 ? $prix_base * (1 - $promo / 100) : $prix_base;
    
    $item['prix_final'] = $prix_final;
    $item['sous_total'] = $prix_final * (int) $item['quantite'];
    $total_cart += $item['sous_total'];
    $items_count += (int) $item['quantite'];
}
unset($item);

// Récupérer les adresses de l'utilisateur
$addresses = [];
try {
    $stmt = $pdo->prepare('SELECT id, prenom, nom, adresse, code_postal, ville, telephone, est_principale FROM adresses WHERE id_utilisateur = ? AND statut = "actif" ORDER BY est_principale DESC, date_creation DESC');
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $errors[] = 'Erreur lors du chargement des adresses.';
}

// Traiter l'ajout d'une nouvelle adresse
$new_address_submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_new_address'])) {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $complement = trim($_POST['complement'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    
    if (empty($prenom) || empty($nom) || empty($adresse) || empty($code_postal) || empty($ville)) {
        $errors[] = 'Tous les champs obligatoires doivent être remplis.';
    } elseif (!preg_match('/^\d{5}$/', $code_postal)) {
        $errors[] = 'Le code postal doit être un nombre à 5 chiffres.';
    } else {
        try {
            $stmt = $pdo->prepare(''
                . 'INSERT INTO adresses (id_utilisateur, prenom, nom, adresse, complement_adresse, code_postal, ville, telephone, statut, est_principale) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, "actif", ?)'
            );
            $est_principale = (int) (empty($addresses) ? 1 : ($_POST['est_principale'] ?? 0));
            $stmt->execute([$user_id, $prenom, $nom, $adresse, $complement, $code_postal, $ville, $telephone, $est_principale]);
            $new_address_id = $pdo->lastInsertId();
            
            // Recharger les adresses
            $stmt = $pdo->prepare('SELECT id, prenom, nom, adresse, code_postal, ville, telephone, est_principale FROM adresses WHERE id_utilisateur = ? AND statut = "actif" ORDER BY est_principale DESC, date_creation DESC');
            $stmt->execute([$user_id]);
            $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $new_address_submitted = true;
        } catch (Throwable $e) {
            $errors[] = 'Erreur lors de l\'ajout de l\'adresse.';
        }
    }
}

// Traiter la création de la commande
$command_error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $address_id = filter_input(INPUT_POST, 'address_id', FILTER_VALIDATE_INT);
    $delivery_mode = trim($_POST['delivery_mode'] ?? 'standard');
    
    if (!$address_id || !in_array($delivery_mode, ['standard', 'express', 'premium'], true)) {
        $errors[] = 'Adresse ou mode de livraison invalide.';
        $command_error = true;
    } else {
        try {
            // Vérifier que l'adresse appartient à l'utilisateur
            $stmt = $pdo->prepare('SELECT id FROM adresses WHERE id = ? AND id_utilisateur = ?');
            $stmt->execute([$address_id, $user_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Adresse non valide.');
            }
            
            $pdo->beginTransaction();

            // Vérifier le stock de tous les produits (verrouillage pendant la transaction)
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare('SELECT stock FROM produits WHERE id_produit = ? FOR UPDATE');
                $stmt->execute([(int) $item['id_produit']]);
                $product_stock = $stmt->fetchColumn();
                
                if ($product_stock === false || (int) $product_stock < (int) $item['quantite']) {
                    throw new Exception('Stock insuffisant pour ' . htmlspecialchars($item['nom']));
                }
            }
            
            // Calculer les frais de livraison
            $shipping_fees = [
                'standard' => 9.90,
                'express' => 14.90,
                'premium' => 24.90
            ];
            $frais_livraison = (float) $shipping_fees[$delivery_mode];
            
            // Appliquer la livraison gratuite si panier >= 150 €
            if ($total_cart >= 150.0) {
                $frais_livraison = 0.0;
            }
            
            $montant_total = $total_cart + $frais_livraison;
            
            // Créer la commande
            $numero_commande = 'LUM-' . strtoupper(bin2hex(random_bytes(3))) . '-' . date('Y');
            
            // Adresse enregistrée dans les notes de la commande
            $stmt = $pdo->prepare('SELECT prenom, nom, adresse, complement_adresse, code_postal, ville, telephone FROM adresses WHERE id = ? AND id_utilisateur = ?');
            $stmt->execute([$address_id, $user_id]);
            $addr = $stmt->fetch(PDO::FETCH_ASSOC);
            $notes = 'Livraison : ' . trim($addr['prenom'] . ' ' . $addr['nom']) . ', '
                . $addr['adresse']
                . (!empty($addr['complement_adresse']) ? ', ' . $addr['complement_adresse'] : '')
                . ', ' . $addr['code_postal'] . ' ' . $addr['ville']
                . (!empty($addr['telephone']) ? ' - Tél. ' . $addr['telephone'] : '');

            $stmt = $pdo->prepare(''
                . 'INSERT INTO commandes (id_utilisateur, numero_commande, montant, frais_livraison, mode_livraison, mode_paiement, statut, notes) '
                . 'VALUES (?, ?, ?, ?, ?, ?, "en attente", ?)'
            );
            $stmt->execute([$user_id, $numero_commande, $montant_total, $frais_livraison, $delivery_mode, 'carte', $notes]);
            $order_id = $pdo->lastInsertId();
            
            // Créer les lignes de commande et mettre à jour le stock
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare(''
                    . 'INSERT INTO details_commande (id_commande, id_produit, quantite, prix_unitaire) '
                    . 'VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$order_id, (int) $item['id_produit'], (int) $item['quantite'], (float) $item['prix_final']]);
                
                // Diminuer le stock
                $stmt = $pdo->prepare('UPDATE produits SET stock = stock - ? WHERE id_produit = ?');
                $stmt->execute([(int) $item['quantite'], (int) $item['id_produit']]);
            }
            
            // Vider le panier
            $stmt = $pdo->prepare('DELETE FROM panier WHERE id_utilisateur = ?');
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            
            // Rediriger vers la confirmation
            header('Location: confirmation_commande.php?order=' . $order_id);
            exit();
            
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e->getMessage() ?: 'Erreur lors de la création de la commande.';
            $command_error = true;
        }
    }
}

// Mode de livraison choisi dans le panier (GET) ou après un envoi (POST)
$selectedDelivery = $_POST['delivery_mode'] ?? $_GET['mode_livraison'] ?? 'standard';
if (!in_array($selectedDelivery, ['standard', 'express', 'premium'], true)) {
    $selectedDelivery = 'standard';
}

$pageTitle = 'Commande - Éclat d\'Or';
include '../includes/header.php';
?>

<div class="premium-checkout-page">
    <section class="premium-checkout-hero">
        <div class="premium-checkout-hero-content">
            <span class="premium-checkout-eyebrow"><i class="fas fa-lock"></i> Paiement sécurisé</span>
            <h1>Finalisez votre commande</h1>
            <p>Dernière étape avant de rejoindre l'univers Éclat d'Or.</p>
        </div>
        <div class="premium-checkout-icon"><i class="fas fa-crown"></i></div>
    </section>

    <?php if (!empty($errors)): ?>
        <div class="premium-checkout-alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="premium-checkout-layout">
        <main class="premium-checkout-main">
            <!-- Résumé du panier -->
            <section class="premium-checkout-section">
                <div class="premium-checkout-section-header">
                    <span class="premium-checkout-eyebrow">Votre panier</span>
                    <h2>Articles sélectionnés</h2>
                </div>

                <div class="premium-checkout-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="premium-checkout-item">
                            <div class="premium-checkout-item-info">
                                <strong><?= htmlspecialchars($item['nom']) ?></strong>
                                <span><?= (int) $item['quantite'] ?> <?= (int) $item['quantite'] > 1 ? 'unité' : 'unité' ?>s</span>
                            </div>
                            <div class="premium-checkout-item-price">
                                <?= number_format($item['sous_total'], 2, ',', ' ') ?> €
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="premium-checkout-summary">
                    <div><span>Sous-total</span><strong><?= number_format($total_cart, 2, ',', ' ') ?> €</strong></div>
                    <div id="shippingLine"><span>Livraison</span><strong id="shippingPrice">9,90 €</strong></div>
                    <div class="premium-checkout-total"><span>Total</span><strong id="totalPrice"><?= number_format($total_cart + 9.90, 2, ',', ' ') ?> €</strong></div>
                </div>
            </section>

            <!-- Sélection d'adresse -->
            <section class="premium-checkout-section">
                <div class="premium-checkout-section-header">
                    <span class="premium-checkout-eyebrow">Livraison</span>
                    <h2>Adresse de livraison</h2>
                </div>

                <form method="POST" class="premium-checkout-form" id="checkoutForm">
                    <?php if (!empty($addresses)): ?>
                        <div class="premium-checkout-addresses">
                            <?php foreach ($addresses as $addr): ?>
                                <label class="premium-checkout-address-card">
                                    <input type="radio" name="address_id" value="<?= (int) $addr['id'] ?>" <?= (int) $addr['est_principale'] ? 'checked' : '' ?>>
                                    <span class="premium-checkout-address-radio"></span>
                                    <span class="premium-checkout-address-content">
                                        <strong><?= htmlspecialchars($addr['prenom'] . ' ' . $addr['nom']) ?></strong>
                                        <span><?= htmlspecialchars($addr['adresse']) ?></span>
                                        <span><?= htmlspecialchars($addr['code_postal'] . ' ' . $addr['ville']) ?></span>
                                        <?php if ($addr['telephone']): ?>
                                            <span><?= htmlspecialchars($addr['telephone']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Formulaire nouvelle adresse (optionnel) -->
                    <div class="premium-checkout-add-address" id="newAddressForm" style="display: none;">
                        <h3>Nouvelle adresse</h3>
                        <div class="premium-checkout-form-group-row">
                            <input type="text" name="prenom" placeholder="Prénom" class="premium-checkout-input" maxlength="100">
                            <input type="text" name="nom" placeholder="Nom" class="premium-checkout-input" maxlength="100">
                        </div>
                        <input type="text" name="adresse" placeholder="Adresse" class="premium-checkout-input premium-checkout-input-full" maxlength="255">
                        <input type="text" name="complement" placeholder="Complément d'adresse (optionnel)" class="premium-checkout-input premium-checkout-input-full" maxlength="255">
                        <div class="premium-checkout-form-group-row">
                            <input type="text" name="code_postal" placeholder="Code postal" class="premium-checkout-input" maxlength="10" pattern="\d{5}">
                            <input type="text" name="ville" placeholder="Ville" class="premium-checkout-input" maxlength="100">
                        </div>
                        <input type="tel" name="telephone" placeholder="Téléphone" class="premium-checkout-input premium-checkout-input-full" maxlength="20">
                        <label class="premium-checkout-checkbox">
                            <input type="checkbox" name="est_principale" value="1">
                            Utiliser cette adresse par défaut
                        </label>
                        <button type="submit" name="add_new_address" class="premium-checkout-button">Ajouter cette adresse</button>
                        <button type="button" class="premium-checkout-button-secondary" onclick="document.getElementById('newAddressForm').style.display='none';">Annuler</button>
                    </div>

                    <button type="button" class="premium-checkout-add-button" onclick="document.getElementById('newAddressForm').style.display='block';">
                        <i class="fas fa-plus"></i> Ajouter une nouvelle adresse
                    </button>

                    <!-- Sélection mode de livraison -->
                    <div class="premium-checkout-shipping-modes" style="margin-top: 30px;">
                        <h3>Mode de livraison</h3>
                        <label class="premium-checkout-shipping-option">
                            <input type="radio" name="delivery_mode" value="standard" <?= $selectedDelivery === 'standard' ? 'checked' : '' ?> data-price="9.90">
                            <span class="premium-checkout-shipping-radio"></span>
                            <span class="premium-checkout-shipping-content">
                                <strong>Standard <small>3 à 5 jours</small></strong>
                                <b id="priceStandard">9,90 €</b>
                            </span>
                        </label>
                        <label class="premium-checkout-shipping-option">
                            <input type="radio" name="delivery_mode" value="express" <?= $selectedDelivery === 'express' ? 'checked' : '' ?> data-price="14.90">
                            <span class="premium-checkout-shipping-radio"></span>
                            <span class="premium-checkout-shipping-content">
                                <strong>Express <small>48h</small></strong>
                                <b>14,90 €</b>
                            </span>
                        </label>
                        <label class="premium-checkout-shipping-option">
                            <input type="radio" name="delivery_mode" value="premium" <?= $selectedDelivery === 'premium' ? 'checked' : '' ?> data-price="24.90">
                            <span class="premium-checkout-shipping-radio"></span>
                            <span class="premium-checkout-shipping-content">
                                <strong>Premium <small>24h chrono</small></strong>
                                <b>24,90 €</b>
                            </span>
                        </label>
                    </div>

                    <!-- Validation -->
                    <button type="submit" name="create_order" class="premium-checkout-submit">
                        <i class="fas fa-lock"></i> Procéder au paiement
                    </button>
                </form>
            </section>

            <!-- Sécurité -->
            <section class="premium-checkout-security">
                <i class="fas fa-shield-alt"></i>
                <span><strong>Paiement sécurisé</strong> - Vos données sont protégées par chiffrement SSL.</span>
            </section>
        </main>

        <!-- Récapitulatif sticky -->
        <aside class="premium-checkout-sidebar">
            <div class="premium-checkout-summary-card">
                <h3>Votre commande</h3>
                <div class="premium-checkout-summary-line">
                    <span><?= $items_count ?> article<?= $items_count > 1 ? 's' : '' ?></span>
                    <strong><?= number_format($total_cart, 2, ',', ' ') ?> €</strong>
                </div>
                <div class="premium-checkout-summary-line">
                    <span>Livraison</span>
                    <strong id="sidebarShipping">9,90 €</strong>
                </div>
                <div class="premium-checkout-summary-line premium-checkout-summary-total">
                    <span>Total</span>
                    <strong id="sidebarTotal"><?= number_format($total_cart + 9.90, 2, ',', ' ') ?> €</strong>
                </div>
                <div class="premium-checkout-trust">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-paypal"></i>
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
:root {
    --checkout-gold: #d4af37;
    --checkout-gold-dark: #a98216;
    --checkout-brown: #3d2b28;
    --checkout-cream: #fbf3ea;
    --checkout-line: #eadfd3;
}

.premium-checkout-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px 24px 90px;
    color: var(--checkout-brown);
}

.premium-checkout-hero {
    min-height: 240px;
    margin: 70px 0 30px;
    padding: 48px 60px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    border-radius: 28px;
    background: linear-gradient(115deg, #f1e8e1 0%, #b9a49a 42%, #5a3825 100%);
    box-shadow: 0 24px 50px rgba(61,43,40,.14);
}

.premium-checkout-hero-content { position: relative; z-index: 2; max-width: 650px; }
.premium-checkout-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #fff4d5;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
}
.premium-checkout-hero h1 { margin: 12px 0 8px; color: #fff; font-size: clamp(2.2rem, 4vw, 3.8rem); letter-spacing: -1px; }
.premium-checkout-hero p { max-width: 560px; margin: 0; color: rgba(255,255,255,.84); font-size: 16px; }
.premium-checkout-icon { position: absolute; right: 80px; top: 50%; transform: translateY(-50%); width: 110px; height: 110px; display: grid; place-items: center; border: 1px solid rgba(255,255,255,.35); border-radius: 50%; background: rgba(255,255,255,.12); color: #f5d56c; font-size: 42px; }

.premium-checkout-alert { display: flex; gap: 15px; padding: 16px 20px; border-radius: 14px; margin-bottom: 25px; }
.alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }
.premium-checkout-alert i { flex-shrink: 0; margin-top: 2px; }
.premium-checkout-alert p { margin: 0 0 8px; font-size: 14px; }
.premium-checkout-alert p:last-child { margin-bottom: 0; }

.premium-checkout-layout { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 28px; align-items: start; }
.premium-checkout-main { }
.premium-checkout-section { padding: 24px; border: 1px solid var(--checkout-line); border-radius: 20px; background: white; margin-bottom: 25px; box-shadow: 0 10px 24px rgba(61,43,40,.06); }
.premium-checkout-section-header { margin-bottom: 20px; }
.premium-checkout-section-header h2 { margin: 6px 0 0; color: var(--checkout-brown); font-family: 'Playfair Display', serif; font-size: 26px; }

.premium-checkout-items { margin-bottom: 20px; }
.premium-checkout-item { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid var(--checkout-line); }
.premium-checkout-item:last-child { border-bottom: none; }
.premium-checkout-item-info { min-width: 0; }
.premium-checkout-item-info strong { display: block; font-size: 15px; }
.premium-checkout-item-info span { display: block; color: #888; font-size: 13px; margin-top: 3px; }
.premium-checkout-item-price { font-weight: 700; color: var(--checkout-gold-dark); white-space: nowrap; }

.premium-checkout-summary { border-top: 2px solid var(--checkout-line); padding-top: 15px; }
.premium-checkout-summary > div { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
.premium-checkout-summary strong { color: var(--checkout-brown); }
.premium-checkout-total { border-top: 1px solid var(--checkout-line); padding-top: 10px; font-size: 18px; font-weight: 800; }
.premium-checkout-total strong { color: var(--checkout-gold-dark); font-size: 22px; }

.premium-checkout-form { }
.premium-checkout-addresses { display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; }
.premium-checkout-address-card { display: flex; align-items: flex-start; gap: 12px; padding: 16px; border: 1px solid var(--checkout-line); border-radius: 14px; cursor: pointer; transition: all .2s ease; }
.premium-checkout-address-card:hover { border-color: var(--checkout-gold); background: #fffaf0; }
.premium-checkout-address-card input { position: absolute; opacity: 0; }
.premium-checkout-address-radio { width: 20px; height: 20px; flex-shrink: 0; border: 2px solid #d7c8b9; border-radius: 50%; margin-top: 2px; }
.premium-checkout-address-card input:checked + .premium-checkout-address-radio { border: 6px solid var(--checkout-gold); }
.premium-checkout-address-content { display: flex; flex-direction: column; gap: 4px; }
.premium-checkout-address-content strong { font-size: 15px; }
.premium-checkout-address-content span { font-size: 13px; color: #888; }

.premium-checkout-add-button { padding: 10px 16px; border: 2px dashed var(--checkout-gold); background: transparent; color: var(--checkout-gold-dark); border-radius: 12px; font-size: 13px; font-weight: 800; cursor: pointer; transition: all .2s ease; margin: 20px 0; }
.premium-checkout-add-button:hover { background: var(--checkout-cream); }

.premium-checkout-add-address { padding: 20px; border: 1px dashed var(--checkout-line); border-radius: 14px; background: var(--checkout-cream); margin-bottom: 20px; }
.premium-checkout-add-address h3 { margin-top: 0; font-size: 16px; }
.premium-checkout-form-group-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.premium-checkout-input { width: 100%; padding: 10px 14px; border: 1px solid var(--checkout-line); border-radius: 10px; font-size: 14px; }
.premium-checkout-input-full { grid-column: 1 / -1; }
.premium-checkout-input:focus { outline: 0; border-color: var(--checkout-gold); box-shadow: 0 0 0 3px rgba(212,175,55,.15); }
.premium-checkout-checkbox { display: flex; align-items: center; gap: 8px; margin: 12px 0; font-size: 13px; cursor: pointer; }
.premium-checkout-checkbox input { width: 18px; height: 18px; cursor: pointer; }
.premium-checkout-button { width: 100%; padding: 11px; border: 0; border-radius: 10px; background: var(--checkout-gold); color: white; font-size: 13px; font-weight: 800; cursor: pointer; margin-top: 10px; }
.premium-checkout-button:hover { background: var(--checkout-gold-dark); }
.premium-checkout-button-secondary { width: 100%; padding: 11px; border: 1px solid var(--checkout-line); background: white; color: var(--checkout-brown); border-radius: 10px; font-size: 13px; font-weight: 800; cursor: pointer; margin-top: 8px; }
.premium-checkout-button-secondary:hover { background: var(--checkout-cream); }

.premium-checkout-shipping-modes { }
.premium-checkout-shipping-modes h3 { font-size: 15px; font-weight: 700; margin: 0 0 12px; }
.premium-checkout-shipping-option { display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--checkout-line); border-radius: 12px; cursor: pointer; transition: all .2s ease; margin-bottom: 10px; }
.premium-checkout-shipping-option:hover, .premium-checkout-shipping-option.is-selected { border-color: var(--checkout-gold); background: #fffaf0; }
.premium-checkout-shipping-option input { position: absolute; opacity: 0; }
.premium-checkout-shipping-radio { width: 18px; height: 18px; flex-shrink: 0; border: 2px solid #d7c8b9; border-radius: 50%; }
.premium-checkout-shipping-option input:checked + .premium-checkout-shipping-radio { border: 5px solid var(--checkout-gold); }
.premium-checkout-shipping-content { flex: 1; display: flex; justify-content: space-between; align-items: center; }
.premium-checkout-shipping-content strong { display: flex; flex-direction: column; gap: 2px; font-size: 13px; }
.premium-checkout-shipping-content small { color: #888; font-size: 11px; font-weight: 400; }
.premium-checkout-shipping-content b { font-size: 12px; }

.premium-checkout-submit { width: 100%; padding: 15px; border: 0; border-radius: 12px; background: linear-gradient(135deg, var(--checkout-brown), #624436); color: white; font-size: 14px; font-weight: 800; cursor: pointer; margin-top: 20px; box-shadow: 0 12px 22px rgba(61,43,40,.2); transition: transform .2s ease, box-shadow .2s ease; }
.premium-checkout-submit:hover { transform: translateY(-3px); box-shadow: 0 16px 28px rgba(61,43,40,.28); }

.premium-checkout-security { display: flex; align-items: center; gap: 10px; padding: 14px 16px; border-radius: 12px; background: #effcf5; border: 1px solid #a7e8c8; color: #12633f; font-size: 13px; margin-top: 20px; }
.premium-checkout-security i { color: #1ea968; }

.premium-checkout-sidebar { position: sticky; top: 25px; }
.premium-checkout-summary-card { padding: 20px; border: 1px solid var(--checkout-line); border-radius: 18px; background: white; box-shadow: 0 18px 40px rgba(61,43,40,.1); }
.premium-checkout-summary-card h3 { margin: 0 0 15px; font-family: 'Playfair Display', serif; font-size: 22px; }
.premium-checkout-summary-line { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; font-size: 14px; color: #888; }
.premium-checkout-summary-line strong { color: var(--checkout-brown); }
.premium-checkout-summary-total { border-top: 2px solid var(--checkout-line); padding-top: 12px; font-size: 18px; font-weight: 800; color: var(--checkout-brown); }
.premium-checkout-summary-total strong { color: var(--checkout-gold-dark); font-size: 22px; }
.premium-checkout-trust { display: flex; justify-content: center; gap: 12px; margin-top: 16px; color: #b8b2ad; font-size: 28px; }

@media (max-width: 900px) {
    .premium-checkout-layout { grid-template-columns: 1fr; }
    .premium-checkout-sidebar { position: static; }
}

@media (max-width: 680px) {
    .premium-checkout-page { padding: 10px 14px 60px; }
    .premium-checkout-hero { min-height: 220px; margin: 55px 0 20px; padding: 36px 24px; }
    .premium-checkout-hero h1 { font-size: 2.2rem; }
    .premium-checkout-icon { display: none; }
    .premium-checkout-form-group-row { grid-template-columns: 1fr; }
}
</style>

<script>
const subtotal = <?= json_encode($total_cart, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const freeShippingThreshold = 150.0;

function euros(value) {
    return Number(value).toLocaleString('fr-FR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }) + ' €';
}

function updateTotals() {
    const deliveryMode = document.querySelector('input[name="delivery_mode"]:checked');
    if (!deliveryMode) return;

    const shippingPrices = {
        'standard': 9.90,
        'express': 14.90,
        'premium': 24.90
    };

    let shipping = shippingPrices[deliveryMode.value] || 9.90;

    // Livraison gratuite si panier >= 150 €
    if (subtotal >= freeShippingThreshold) {
        shipping = 0;
    }

    const total = subtotal + shipping;

    document.getElementById('shippingPrice').textContent = shipping > 0 ? euros(shipping) : 'Gratuite';
    document.getElementById('sidebarShipping').textContent = shipping > 0 ? euros(shipping) : 'Gratuite';
    document.getElementById('totalPrice').textContent = euros(total);
    document.getElementById('sidebarTotal').textContent = euros(total);

    // Update shipping option styles
    document.querySelectorAll('.premium-checkout-shipping-option').forEach(option => {
        const radio = option.querySelector('input[type="radio"]');
        option.classList.toggle('is-selected', radio && radio.checked);
    });
}

document.querySelectorAll('input[name="delivery_mode"]').forEach(radio => {
    radio.addEventListener('change', updateTotals);
});

// Select first address by default
document.addEventListener('DOMContentLoaded', function() {
    const firstAddress = document.querySelector('input[name="address_id"]');
    if (firstAddress) firstAddress.checked = true;
    updateTotals();
});
</script>

<?php include '../includes/footer.php'; ?>
