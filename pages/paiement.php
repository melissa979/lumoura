<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    header('Location: connexion.php');
    exit();
}

$id_commande = (int)($_GET['id'] ?? 0);
if (!$id_commande) {
    header('Location: mes-commandes.php');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM commandes WHERE id_commande = ? AND id_utilisateur = ?");
    $stmt->execute([$id_commande, $_SESSION['user_id']]);
    $commande = $stmt->fetch();
} catch(Exception $e) { $commande = null; }

if (!$commande) {
    header('Location: mes-commandes.php');
    exit();
}

// Récupérer les articles
try {
    $stmtD = $pdo->prepare("SELECT dc.*, p.nom, p.image_url, p.marque FROM details_commande dc JOIN produits p ON p.id_produit = dc.id_produit WHERE dc.id_commande = ?");
    $stmtD->execute([$id_commande]);
    $details = $stmtD->fetchAll();
} catch(Exception $e) { $details = []; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payer'])) {
    $num_carte = preg_replace('/\s/', '', $_POST['num_carte'] ?? '');
    $expiry    = trim($_POST['expiry'] ?? '');
    $cvv       = trim($_POST['cvv'] ?? '');
    $nom_carte = trim($_POST['nom_carte'] ?? '');

    if (!preg_match('/^\d{16}$/', $num_carte)) $errors[] = 'Numéro de carte invalide (16 chiffres)';
    if (!preg_match('/^\d{2}\/\d{2}$/', $expiry))  $errors[] = 'Date d\'expiration invalide (MM/AA)';
    if (!preg_match('/^\d{3,4}$/', $cvv))           $errors[] = 'CVV invalide';
    if (empty($nom_carte))                           $errors[] = 'Nom sur la carte requis';

    if (empty($errors)) {
        [$mm, $yy] = explode('/', $expiry);
        $exp = \DateTime::createFromFormat('m/y', $mm . '/' . $yy);
        if (!$exp || $exp < new \DateTime('first day of this month')) {
            $errors[] = 'Carte expirée';
        }
    }
    if ($num_carte === '4000000000000002') {
        $errors[] = 'Paiement refusé par votre banque.';
    }

    if (empty($errors)) {
        try {
            $pdo->prepare("UPDATE commandes SET statut = 'payee', date_paiement = NOW(), mode_paiement = 'carte' WHERE id_commande = ?")
                ->execute([$id_commande]);
            $_SESSION['commande_message'] = 'success|Paiement de ' . number_format($commande['montant'], 2, ',', ' ') . '€ effectué avec succès !';
            header('Location: commande_confirmation.php?id=' . $id_commande);
            exit();
        } catch(Exception $e) {
            $errors[] = 'Erreur : ' . $e->getMessage();
        }
    }
}

$pageTitle = "Paiement sécurisé — Lumoura";
include '../includes/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Cinzel:wght@400;600;700&family=Didact+Gothic&display=swap" rel="stylesheet">
<style>
:root{--g1:#D4A843;--g2:#F5D78E;--g3:#B8882C;--ink:#0D0A06;--smoke:#F8F5EF;--stone:#E8E0D0;--muted:#8A7D6A;--red:#C0392B;--green:#27ae60;}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Didact Gothic',sans-serif;background:var(--smoke);color:var(--ink);cursor:none;}
#cursor{position:fixed;width:10px;height:10px;background:var(--g1);border-radius:50%;pointer-events:none;z-index:99999;transform:translate(-50%,-50%);transition:width .25s,height .25s;}
#cursor-ring{position:fixed;width:36px;height:36px;border:1px solid var(--g1);border-radius:50%;pointer-events:none;z-index:99998;transform:translate(-50%,-50%);transition:.3s;opacity:.6;}
body.hovering #cursor{width:20px;height:20px;background:var(--g2);}
body.hovering #cursor-ring{width:54px;height:54px;opacity:.4;}

.breadcrumb{background:var(--ink);padding:13px 60px;display:flex;align-items:center;gap:10px;font-family:'Cinzel',serif;font-size:.56rem;letter-spacing:2.5px;text-transform:uppercase;}
.breadcrumb a{color:rgba(255,255,255,.35);text-decoration:none;transition:color .2s;}
.breadcrumb a:hover{color:var(--g1);}
.breadcrumb-sep{color:rgba(255,255,255,.15);}
.breadcrumb-cur{color:var(--g1);}

.pay-wrap{max-width:960px;margin:0 auto;padding:50px 40px 80px;display:grid;grid-template-columns:1fr 360px;gap:30px;align-items:start;}

/* Carte bancaire visuelle */
.card-visual{
    width:100%;max-width:340px;height:200px;
    background:linear-gradient(135deg,#1a1208 0%,#2d1f0a 50%,#1a1208 100%);
    border-radius:16px;padding:28px;position:relative;overflow:hidden;
    margin-bottom:32px;box-shadow:0 20px 60px rgba(0,0,0,.3);
}
.card-visual::before{
    content:'';position:absolute;top:-40px;right:-40px;
    width:200px;height:200px;border-radius:50%;
    background:radial-gradient(circle,rgba(212,168,67,.15),transparent 70%);
}
.card-chip{
    width:44px;height:34px;background:linear-gradient(135deg,var(--g2),var(--g1));
    border-radius:5px;margin-bottom:24px;
    display:grid;grid-template-columns:1fr 1fr;gap:2px;padding:5px;
}
.card-chip-line{background:rgba(0,0,0,.2);border-radius:1px;}
.card-number{
    font-family:'Courier New',monospace;font-size:1.1rem;
    color:rgba(255,255,255,.8);letter-spacing:3px;margin-bottom:20px;
}
.card-bottom{display:flex;justify-content:space-between;align-items:flex-end;}
.card-label{font-size:.5rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:3px;}
.card-value{font-family:'Cinzel',serif;font-size:.8rem;color:rgba(255,255,255,.75);text-transform:uppercase;}
.card-logo{position:absolute;top:24px;right:24px;font-size:1.5rem;color:var(--g1);opacity:.7;}

/* Formulaire */
.pay-panel{background:#fff;}
.pay-panel-head{padding:20px 26px;border-bottom:1px solid var(--stone);display:flex;align-items:center;gap:12px;}
.pay-panel-head i{color:var(--g1);}
.pay-panel-head h2{font-family:'EB Garamond',serif;font-size:1.3rem;font-weight:400;}
.pay-panel-body{padding:28px;}

.field{display:flex;flex-direction:column;gap:6px;margin-bottom:16px;}
.field label{font-family:'Cinzel',serif;font-size:.55rem;letter-spacing:2.5px;text-transform:uppercase;color:var(--muted);}
.field input{
    border:1px solid var(--stone);background:var(--smoke);
    padding:13px 16px;font-family:'Didact Gothic',sans-serif;
    font-size:.9rem;color:var(--ink);transition:border-color .25s,background .25s;
    width:100%;
}
.field input:focus{outline:none;border-color:var(--g1);background:#fff;}
.field input.error-field{border-color:var(--red);}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}

.input-icon{position:relative;}
.input-icon i{position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--muted);}
.input-icon input{padding-right:40px;}

.errors-block{background:rgba(192,57,43,.08);border-left:3px solid var(--red);padding:14px 20px;margin-bottom:20px;list-style:none;}
.errors-block li{color:var(--red);font-size:.82rem;margin-bottom:4px;display:flex;align-items:center;gap:8px;}

.secure-note{
    display:flex;align-items:center;gap:8px;
    font-size:.72rem;color:var(--muted);
    padding:12px 16px;background:var(--smoke);
    border:1px solid var(--stone);margin-bottom:16px;
}
.secure-note i{color:var(--g1);}

.test-cards{
    background:rgba(212,168,67,.06);border:1px solid rgba(212,168,67,.2);
    padding:14px 16px;margin-bottom:20px;
}
.test-cards p{font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:2px;text-transform:uppercase;color:var(--g3);margin-bottom:8px;}
.test-card-row{display:flex;justify-content:space-between;align-items:center;font-size:.75rem;color:var(--muted);margin-bottom:4px;}
.test-card-row span:last-child{font-family:'Courier New',monospace;color:var(--ink);}

.btn-pay{
    width:100%;background:var(--g1);color:var(--ink);border:none;
    padding:18px;font-family:'Cinzel',serif;font-size:.7rem;
    letter-spacing:3px;text-transform:uppercase;cursor:pointer;
    display:flex;align-items:center;justify-content:center;gap:12px;
    transition:background .3s;margin-top:4px;
}
.btn-pay:hover{background:var(--g2);}
.btn-pay:disabled{opacity:.5;cursor:not-allowed;}

/* Récap droite */
.recap{background:var(--ink);color:rgba(255,255,255,.8);}
.recap-head{padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06);}
.recap-head h3{font-family:'EB Garamond',serif;font-size:1.2rem;color:#fff;font-weight:400;}
.recap-num{font-family:'Cinzel',serif;font-size:.55rem;letter-spacing:2px;color:var(--g1);margin-top:4px;}
.recap-items{padding:14px 24px;}
.recap-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04);}
.recap-item:last-child{border-bottom:none;}
.recap-thumb{width:46px;height:46px;object-fit:cover;border:1px solid rgba(255,255,255,.08);}
.recap-item-info{flex:1;}
.recap-item-brand{font-family:'Cinzel',serif;font-size:.48rem;letter-spacing:2px;text-transform:uppercase;color:var(--g1);}
.recap-item-name{font-family:'EB Garamond',serif;font-size:.9rem;color:rgba(255,255,255,.8);}
.recap-item-qty{font-size:.7rem;color:rgba(255,255,255,.3);}
.recap-item-price{font-family:'EB Garamond',serif;font-size:.95rem;color:var(--g2);}
.recap-totals{padding:14px 24px;border-top:1px solid rgba(255,255,255,.06);}
.recap-line{display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:8px;}
.recap-line span:first-child{color:rgba(255,255,255,.35);}
.recap-total{display:flex;justify-content:space-between;align-items:baseline;padding-top:12px;border-top:1px solid rgba(255,255,255,.08);margin-top:4px;}
.recap-total span:first-child{font-family:'Cinzel',serif;font-size:.55rem;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,.35);}
.recap-total-price{font-family:'EB Garamond',serif;font-size:1.8rem;color:var(--g1);}

.btn-back{
    display:flex;align-items:center;justify-content:center;gap:8px;
    color:rgba(255,255,255,.25);font-size:.7rem;font-family:'Cinzel',serif;
    letter-spacing:1.5px;text-transform:uppercase;text-decoration:none;
    padding:14px 24px;border-top:1px solid rgba(255,255,255,.04);
    transition:color .2s;
}
.btn-back:hover{color:var(--g1);}

@media(max-width:768px){.pay-wrap{grid-template-columns:1fr;padding:20px;}.breadcrumb{padding:13px 20px;}}
</style>

<div id="cursor"></div><div id="cursor-ring"></div>

<nav class="breadcrumb">
  <a href="../index.php">Accueil</a><span class="breadcrumb-sep">›</span>
  <a href="mes-commandes.php">Mes commandes</a><span class="breadcrumb-sep">›</span>
  <span class="breadcrumb-cur">Paiement</span>
</nav>

<form method="POST" id="payForm">
<input type="hidden" name="id_commande" value="<?=$id_commande?>">
<div class="pay-wrap">

  <!-- GAUCHE -->
  <div>
    <!-- Carte visuelle animée -->
    <div class="card-visual" id="cardVisual">
      <div class="card-chip"><div class="card-chip-line"></div><div class="card-chip-line"></div><div class="card-chip-line"></div><div class="card-chip-line"></div></div>
      <div class="card-number" id="cvNum">•••• •••• •••• ••••</div>
      <div class="card-bottom">
        <div>
          <div class="card-label">Titulaire</div>
          <div class="card-value" id="cvName">VOTRE NOM</div>
        </div>
        <div>
          <div class="card-label">Expire</div>
          <div class="card-value" id="cvExp">MM/AA</div>
        </div>
      </div>
      <i class="fas fa-credit-card card-logo"></i>
    </div>

    <!-- Erreurs -->
    <?php if(!empty($errors)): ?>
    <ul class="errors-block">
      <?php foreach($errors as $e): ?><li><i class="fas fa-exclamation-circle"></i><?=htmlspecialchars($e)?></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <!-- Cartes de test -->
    <div class="test-cards">
      <p><i class="fas fa-flask" style="margin-right:5px;"></i> Cartes de test</p>
      <div class="test-card-row"><span>✅ Paiement accepté</span><span>4111 1111 1111 1111</span></div>
      <div class="test-card-row"><span>❌ Paiement refusé</span><span>4000 0000 0000 0002</span></div>
      <div class="test-card-row" style="margin-top:4px;font-size:.7rem;"><span>Date :</span><span>12/26</span></div>
      <div class="test-card-row"><span>CVV :</span><span>123</span></div>
    </div>

    <!-- Formulaire -->
    <div class="pay-panel">
      <div class="pay-panel-head">
        <i class="fas fa-lock"></i>
        <h2>Informations de paiement</h2>
      </div>
      <div class="pay-panel-body">
        <div class="secure-note">
          <i class="fas fa-shield-alt"></i>
          Paiement 100% sécurisé — Vos données sont cryptées SSL 256-bit
        </div>

        <div class="field">
          <label>Nom sur la carte *</label>
          <input type="text" name="nom_carte" id="nomCarte" placeholder="MARIE DUPONT"
                 value="<?=htmlspecialchars($_POST['nom_carte']??'')?>"
                 oninput="document.getElementById('cvName').textContent = this.value.toUpperCase() || 'VOTRE NOM'"
                 style="text-transform:uppercase;" required>
        </div>

        <div class="field">
          <label>Numéro de carte *</label>
          <div class="input-icon">
            <input type="text" name="num_carte" id="numCarte" placeholder="1234 5678 9012 3456"
                   maxlength="19" value="<?=htmlspecialchars($_POST['num_carte']??'')?>"
                   autocomplete="cc-number" required>
            <i class="far fa-credit-card" id="cardIcon"></i>
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label>Date d'expiration *</label>
            <input type="text" name="expiry" id="expiryInput" placeholder="MM/AA"
                   maxlength="5" value="<?=htmlspecialchars($_POST['expiry']??'')?>"
                   autocomplete="cc-exp" required>
          </div>
          <div class="field">
            <label>CVV *</label>
            <div class="input-icon">
              <input type="text" name="cvv" placeholder="123" maxlength="4"
                     value="<?=htmlspecialchars($_POST['cvv']??'')?>"
                     autocomplete="cc-csc" required>
              <i class="fas fa-question-circle" title="3 chiffres au dos de la carte"></i>
            </div>
          </div>
        </div>

        <button type="submit" name="payer" class="btn-pay" id="btnPay">
          <i class="fas fa-lock"></i>
          Payer <?=number_format($commande['montant'],2,',',' ')?>€
        </button>
      </div>
    </div>
  </div>

  <!-- DROITE -->
  <div>
    <div class="recap">
      <div class="recap-head">
        <h3>Votre commande</h3>
        <div class="recap-num"><?=htmlspecialchars($commande['numero_commande'])?></div>
      </div>
      <div class="recap-items">
        <?php foreach($details as $d): ?>
        <div class="recap-item">
          <img class="recap-thumb" src="<?=htmlspecialchars($d['image_url']??'https://via.placeholder.com/46')?>" alt="">
          <div class="recap-item-info">
            <div class="recap-item-brand"><?=htmlspecialchars($d['marque']??'')?></div>
            <div class="recap-item-name"><?=htmlspecialchars($d['nom'])?></div>
            <div class="recap-item-qty">× <?=$d['quantite']?></div>
          </div>
          <div class="recap-item-price"><?=number_format($d['prix_unitaire']*$d['quantite'],2,',',' ')?>€</div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="recap-totals">
        <div class="recap-line">
          <span>Sous-total</span>
          <span><?=number_format($commande['montant']-$commande['frais_livraison'],2,',',' ')?>€</span>
        </div>
        <div class="recap-line">
          <span>Livraison</span>
          <span><?=$commande['frais_livraison']==0?'<span style="color:#2ecc71">Gratuite</span>':number_format($commande['frais_livraison'],2,',',' ').'€'?></span>
        </div>
        <div class="recap-total">
          <span>Total TTC</span>
          <span class="recap-total-price"><?=number_format($commande['montant'],2,',',' ')?>€</span>
        </div>
      </div>
      <a href="mes-commandes.php" class="btn-back">
        <i class="fas fa-arrow-left"></i> Retour aux commandes
      </a>
    </div>
  </div>

</div>
</form>

<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';});
(function r(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(r);})();
document.querySelectorAll('a,button,input').forEach(el=>{
  el.addEventListener('mouseenter',()=>document.body.classList.add('hovering'));
  el.addEventListener('mouseleave',()=>document.body.classList.remove('hovering'));
});

// Format numéro de carte
document.getElementById('numCarte').addEventListener('input', function(){
  let v = this.value.replace(/\D/g,'').substring(0,16);
  this.value = v.match(/.{1,4}/g)?.join(' ') || v;
  // Afficher sur la carte visuelle
  let display = v.padEnd(16,'•');
  document.getElementById('cvNum').textContent =
    display.substring(0,4)+' '+display.substring(4,8)+' '+display.substring(8,12)+' '+display.substring(12,16);
  // Icône selon type
  const icon = document.getElementById('cardIcon');
  if(v.startsWith('4')) icon.className='fab fa-cc-visa';
  else if(v.startsWith('5')) icon.className='fab fa-cc-mastercard';
  else if(v.startsWith('3')) icon.className='fab fa-cc-amex';
  else icon.className='far fa-credit-card';
});

// Format expiration
document.getElementById('expiryInput').addEventListener('input', function(){
  let v = this.value.replace(/\D/g,'').substring(0,4);
  if(v.length>=2) v = v.substring(0,2)+'/'+v.substring(2);
  this.value = v;
  document.getElementById('cvExp').textContent = v || 'MM/AA';
});

// Animation bouton paiement
document.getElementById('payForm').addEventListener('submit', function(){
  const btn = document.getElementById('btnPay');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement en cours...';
});
</script>
<?php include '../includes/footer.php'; ?>