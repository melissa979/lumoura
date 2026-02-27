<?php
// ══════════════════════════════════════════
//  LUMOURA — Confirmation de commande
//  pages/commande_confirmation.php
// ══════════════════════════════════════════
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

function normaliserStatut($s) {
    $s = strtolower(trim($s));
    $s = str_replace(' ', '_', $s);
    $map = [
        'en_attente'=>'en_attente','en attente'=>'en_attente','attente'=>'en_attente',
        'payee'=>'payee','payée'=>'payee','paid'=>'payee',
        'annulee'=>'annulee','annulée'=>'annulee','cancelled'=>'annulee',
        'expediee'=>'expediee','expédiée'=>'expediee',
        'livree'=>'livree','livrée'=>'livree',
    ];
    return $map[$s] ?? $s;
}

if (!isLoggedIn()) {
    header('Location: connexion.php');
    exit();
}

// Récupérer le message de confirmation
$message = $_SESSION['commande_message'] ?? null;
unset($_SESSION['commande_message']);

$commande = null;

// Priorité 1 : ?id= dans l'URL (après annulation)
if (!empty($_GET['id'])) {
    $id_get = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM commandes WHERE id_commande = ? AND id_utilisateur = ?");
        $stmt->execute([$id_get, $_SESSION['user_id']]);
        $commande = $stmt->fetch();
    } catch(Exception $e) { $commande = null; }
}

// Priorité 2 : extraire depuis le message de session
if (!$commande && $message && preg_match('/#(CMD-[A-Z0-9\-]+)/', $message, $m)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM commandes WHERE numero_commande = ? AND id_utilisateur = ?");
        $stmt->execute([$m[1], $_SESSION['user_id']]);
        $commande = $stmt->fetch();
    } catch(Exception $e) { $commande = null; }
}

// Priorité 3 : dernière commande de l'utilisateur
if (!$commande) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM commandes WHERE id_utilisateur = ? ORDER BY date_commande DESC LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $commande = $stmt->fetch();
    } catch(Exception $e) { $commande = null; }
}

if (!$commande) {
    header('Location: index.php');
    exit();
}

// Récupérer les détails de la commande
try {
    $stmtD = $pdo->prepare("
        SELECT dc.*, p.nom, p.image_url, p.marque 
        FROM details_commande dc
        JOIN produits p ON p.id_produit = dc.id_produit
        WHERE dc.id_commande = ?
    ");
    $stmtD->execute([$commande['id_commande']]);
    $details = $stmtD->fetchAll();
} catch(Exception $e) { $details = []; }

// Peut-on annuler ? Seulement si statut = 'en_attente'
$peut_annuler = normaliserStatut($commande['statut']) === 'en_attente';

$pageTitle = "Confirmation de commande — Lumoura";
include '../includes/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Cinzel:wght@400;600;700&family=Didact+Gothic&display=swap" rel="stylesheet">

<style>
:root{--g1:#D4A843;--g2:#F5D78E;--g3:#B8882C;--ink:#0D0A06;--smoke:#F8F5EF;--stone:#E8E0D0;--muted:#8A7D6A;--red:#C0392B;--green:#27ae60;}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Didact Gothic',sans-serif;background:var(--smoke);color:var(--ink);cursor:none;}

#cursor{position:fixed;width:10px;height:10px;background:var(--g1);border-radius:50%;pointer-events:none;z-index:99999;transform:translate(-50%,-50%);transition:width .25s,height .25s;}
#cursor-ring{position:fixed;width:36px;height:36px;border:1px solid var(--g1);border-radius:50%;pointer-events:none;z-index:99998;transform:translate(-50%,-50%);transition:width .3s,height .3s,opacity .3s;opacity:.6;}
body.hovering #cursor{width:20px;height:20px;background:var(--g2);}
body.hovering #cursor-ring{width:54px;height:54px;opacity:.4;}

/* ── BREADCRUMB ── */
.breadcrumb{background:var(--ink);padding:13px 60px;display:flex;align-items:center;gap:10px;font-family:'Cinzel',serif;font-size:.56rem;letter-spacing:2.5px;text-transform:uppercase;}
.breadcrumb a{color:rgba(255,255,255,.35);text-decoration:none;transition:color .2s;}
.breadcrumb a:hover{color:var(--g1);}
.breadcrumb-sep{color:rgba(255,255,255,.15);}
.breadcrumb-cur{color:var(--g1);}

/* ── ÉTAPES ── */
.steps-bar{background:#fff;border-bottom:1px solid var(--stone);padding:20px 60px;display:flex;align-items:center;justify-content:center;}
.step{display:flex;align-items:center;gap:10px;font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2px;text-transform:uppercase;color:rgba(0,0,0,.25);}
.step.done{color:var(--g1);}
.step-num{width:28px;height:28px;border-radius:50%;border:1px solid currentColor;display:flex;align-items:center;justify-content:center;font-size:.65rem;flex-shrink:0;}
.step.done .step-num{background:var(--g1);border-color:var(--g1);color:var(--ink);}
.step-line{width:60px;height:1px;background:var(--stone);margin:0 12px;}

/* ── HERO SUCCÈS ── */
.confirm-hero{
    background:var(--ink);
    padding:60px 20px;
    text-align:center;
    position:relative;
    overflow:hidden;
}
.confirm-hero::before{
    content:'';position:absolute;inset:0;
    background:radial-gradient(ellipse at center, rgba(212,168,67,.12) 0%, transparent 70%);
}
.confirm-icon{
    width:80px;height:80px;border-radius:50%;
    background:linear-gradient(135deg,var(--g1),var(--g2));
    display:flex;align-items:center;justify-content:center;
    margin:0 auto 24px;
    font-size:2rem;color:var(--ink);
    position:relative;
    box-shadow:0 0 40px rgba(212,168,67,.4);
    animation: pulse 2s ease-in-out infinite;
}
@keyframes pulse {
    0%,100%{box-shadow:0 0 40px rgba(212,168,67,.4);}
    50%{box-shadow:0 0 60px rgba(212,168,67,.7);}
}
.confirm-hero h1{
    font-family:'EB Garamond',serif;font-size:2.2rem;
    color:#fff;font-weight:400;margin-bottom:10px;position:relative;
}
.confirm-hero p{
    color:rgba(255,255,255,.45);font-size:.88rem;
    letter-spacing:.5px;position:relative;
}
.confirm-num{
    display:inline-block;margin-top:16px;
    font-family:'Cinzel',serif;font-size:.7rem;
    letter-spacing:3px;text-transform:uppercase;
    color:var(--g1);background:rgba(212,168,67,.1);
    padding:8px 20px;border:1px solid rgba(212,168,67,.3);
    position:relative;
}

/* ── WRAP ── */
.confirm-wrap{
    max-width:900px;margin:0 auto;
    padding:50px 60px 80px;
    display:grid;grid-template-columns:1fr 340px;
    gap:30px;align-items:start;
}

/* ── PANELS ── */
.panel{background:#fff;margin-bottom:2px;}
.panel-head{padding:18px 26px;border-bottom:1px solid var(--stone);display:flex;align-items:center;gap:12px;}
.panel-head i{color:var(--g1);}
.panel-head h2{font-family:'EB Garamond',serif;font-size:1.2rem;font-weight:400;}
.panel-body{padding:24px 26px;}

/* ── DÉTAILS COMMANDE ── */
.order-item{
    display:flex;align-items:center;gap:14px;
    padding:12px 0;border-bottom:1px solid var(--stone);
}
.order-item:last-child{border-bottom:none;}
.order-thumb{width:55px;height:55px;object-fit:cover;border:1px solid var(--stone);}
.order-item-info{flex:1;}
.order-item-brand{font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:2px;text-transform:uppercase;color:var(--g1);margin-bottom:2px;}
.order-item-name{font-family:'EB Garamond',serif;font-size:1rem;color:var(--ink);}
.order-item-qty{font-size:.75rem;color:var(--muted);margin-top:2px;}
.order-item-price{font-family:'EB Garamond',serif;font-size:1.05rem;color:var(--ink);font-weight:500;}

/* ── RECAP DROITE ── */
.recap-box{background:var(--ink);color:rgba(255,255,255,.8);position:sticky;top:20px;}
.recap-box-head{padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06);}
.recap-box-head h3{font-family:'EB Garamond',serif;font-size:1.2rem;color:#fff;font-weight:400;}
.recap-lines{padding:16px 24px;}
.recap-line{display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:10px;}
.recap-line span:first-child{color:rgba(255,255,255,.4);}
.recap-line.total-line{
    border-top:1px solid rgba(255,255,255,.08);
    padding-top:14px;margin-top:4px;
}
.recap-line.total-line span:first-child{font-family:'Cinzel',serif;font-size:.6rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.4);}
.recap-line.total-line span:last-child{font-family:'EB Garamond',serif;font-size:1.6rem;color:var(--g1);}

/* ── STATUT ── */
.statut-badge{
    display:inline-flex;align-items:center;gap:6px;
    padding:5px 14px;font-family:'Cinzel',serif;
    font-size:.55rem;letter-spacing:2px;text-transform:uppercase;
}
.statut-en_attente{background:rgba(212,168,67,.15);color:var(--g3);border:1px solid rgba(212,168,67,.3);}
.statut-payee{background:rgba(39,174,96,.1);color:var(--green);border:1px solid rgba(39,174,96,.3);}
.statut-annulee{background:rgba(192,57,43,.1);color:var(--red);border:1px solid rgba(192,57,43,.3);}
.statut-expediee{background:rgba(52,152,219,.1);color:#3498db;border:1px solid rgba(52,152,219,.3);}

/* ── INFO ROW ── */
.info-row{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--stone);font-size:.85rem;}
.info-row:last-child{border-bottom:none;}
.info-row .label{color:var(--muted);font-family:'Cinzel',serif;font-size:.55rem;letter-spacing:1.5px;text-transform:uppercase;}
.info-row .value{color:var(--ink);font-weight:500;}

/* ── BOUTONS ── */
.btn-group{display:flex;flex-direction:column;gap:10px;padding:20px 24px;border-top:1px solid rgba(255,255,255,.06);}

.btn-continuer{
    display:flex;align-items:center;justify-content:center;gap:10px;
    background:var(--g1);color:var(--ink);
    padding:14px;font-family:'Cinzel',serif;
    font-size:.65rem;letter-spacing:2.5px;text-transform:uppercase;
    text-decoration:none;transition:background .3s;border:none;cursor:pointer;width:100%;
}
.btn-continuer:hover{background:var(--g2);}

.btn-annuler{
    display:flex;align-items:center;justify-content:center;gap:10px;
    background:transparent;color:rgba(255,255,255,.4);
    padding:12px;font-family:'Cinzel',serif;
    font-size:.6rem;letter-spacing:2px;text-transform:uppercase;
    border:1px solid rgba(255,255,255,.1);cursor:pointer;
    transition:all .3s;width:100%;
}
.btn-annuler:hover{background:rgba(192,57,43,.15);color:var(--red);border-color:rgba(192,57,43,.3);}
.btn-annuler:disabled{opacity:.3;cursor:not-allowed;}

.btn-commandes{
    display:flex;align-items:center;justify-content:center;gap:8px;
    color:rgba(255,255,255,.3);font-size:.72rem;text-decoration:none;
    padding:8px;transition:color .2s;
}
.btn-commandes:hover{color:var(--g1);}

/* ── MODAL ANNULATION ── */
.modal-overlay{
    position:fixed;inset:0;background:rgba(0,0,0,.7);
    z-index:9999;display:none;align-items:center;justify-content:center;
    backdrop-filter:blur(4px);
}
.modal-overlay.active{display:flex;}
.modal{
    background:#fff;max-width:420px;width:90%;
    padding:40px;text-align:center;
    animation:modalIn .3s ease;
}
@keyframes modalIn{from{transform:scale(.9);opacity:0;}to{transform:scale(1);opacity:1;}}
.modal-icon{font-size:2.5rem;color:var(--red);margin-bottom:16px;}
.modal h3{font-family:'EB Garamond',serif;font-size:1.5rem;margin-bottom:10px;}
.modal p{color:var(--muted);font-size:.85rem;line-height:1.6;margin-bottom:28px;}
.modal-btns{display:flex;gap:12px;}
.modal-btn-confirm{
    flex:1;background:var(--red);color:#fff;border:none;
    padding:14px;font-family:'Cinzel',serif;font-size:.6rem;
    letter-spacing:2px;text-transform:uppercase;cursor:pointer;transition:opacity .2s;
}
.modal-btn-confirm:hover{opacity:.85;}
.modal-btn-cancel{
    flex:1;background:var(--smoke);color:var(--ink);border:1px solid var(--stone);
    padding:14px;font-family:'Cinzel',serif;font-size:.6rem;
    letter-spacing:2px;text-transform:uppercase;cursor:pointer;transition:background .2s;
}
.modal-btn-cancel:hover{background:var(--stone);}

/* ── ALERTE ANNULÉE ── */
.alert-annulee{
    background:rgba(192,57,43,.08);border-left:3px solid var(--red);
    padding:14px 20px;margin-bottom:20px;
    display:flex;align-items:center;gap:10px;
    font-size:.85rem;color:var(--red);
}

@media(max-width:768px){
    .confirm-wrap{grid-template-columns:1fr;padding:30px;}
    .recap-box{position:static;}
    .breadcrumb,.steps-bar{padding-left:20px;padding-right:20px;}
}
</style>

<div id="cursor"></div>
<div id="cursor-ring"></div>

<!-- Breadcrumb -->
<nav class="breadcrumb">
  <a href="../index.php">Accueil</a>
  <span class="breadcrumb-sep">›</span>
  <a href="panier.php">Mon panier</a>
  <span class="breadcrumb-sep">›</span>
  <span class="breadcrumb-cur">Confirmation</span>
</nav>

<!-- Étapes -->
<div class="steps-bar">
  <div class="step done">
    <div class="step-num"><i class="fas fa-check"></i></div>
    <span>Panier</span>
  </div>
  <div class="step-line"></div>
  <div class="step done">
    <div class="step-num"><i class="fas fa-check"></i></div>
    <span>Livraison & Paiement</span>
  </div>
  <div class="step-line"></div>
  <div class="step done">
    <div class="step-num"><i class="fas fa-check"></i></div>
    <span>Confirmation</span>
  </div>
</div>

<!-- Hero -->
<div class="confirm-hero">
  <?php if(normaliserStatut($commande['statut']) === 'annulee'): ?>
    <div class="confirm-icon" style="background:linear-gradient(135deg,var(--red),#e74c3c);animation:none;">
      <i class="fas fa-times"></i>
    </div>
    <h1>Commande annulée</h1>
    <p>Votre commande a été annulée avec succès.</p>
  <?php else: ?>
    <div class="confirm-icon">
      <i class="fas fa-check"></i>
    </div>
    <h1>Commande confirmée !</h1>
    <p>Merci pour votre achat. Votre commande a bien été enregistrée.</p>
  <?php endif; ?>
  <div class="confirm-num"><?= htmlspecialchars($commande['numero_commande']) ?></div>
</div>

<!-- Contenu -->
<div class="confirm-wrap">

  <!-- COLONNE GAUCHE -->
  <div>

    <?php if(normaliserStatut($commande['statut']) === 'annulee'): ?>
    <div class="alert-annulee">
      <i class="fas fa-ban"></i>
      Cette commande a été annulée. Aucun montant ne sera débité.
    </div>
    <?php endif; ?>

    <!-- Détails des articles -->
    <div class="panel">
      <div class="panel-head">
        <i class="fas fa-box-open"></i>
        <h2>Articles commandés</h2>
      </div>
      <div class="panel-body">
        <?php if(empty($details)): ?>
          <p style="color:var(--muted);font-size:.85rem;">Aucun article trouvé.</p>
        <?php else: ?>
          <?php foreach($details as $d): ?>
          <div class="order-item">
            <img class="order-thumb"
                 src="<?=htmlspecialchars($d['image_url'] ?: 'https://via.placeholder.com/55x55/1E1710/D4A843?text=✦')?>"
                 alt="">
            <div class="order-item-info">
              <div class="order-item-brand"><?=htmlspecialchars($d['marque']??'')?></div>
              <div class="order-item-name"><?=htmlspecialchars($d['nom'])?></div>
              <div class="order-item-qty">× <?=$d['quantite']?></div>
            </div>
            <div class="order-item-price"><?=number_format($d['prix_unitaire']*$d['quantite'],2,',',' ')?>€</div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Informations commande -->
    <div class="panel">
      <div class="panel-head">
        <i class="fas fa-info-circle"></i>
        <h2>Informations</h2>
      </div>
      <div class="panel-body">
        <div class="info-row">
          <span class="label">Numéro</span>
          <span class="value"><?=htmlspecialchars($commande['numero_commande'])?></span>
        </div>
        <div class="info-row">
          <span class="label">Date</span>
          <span class="value"><?=date('d/m/Y à H\hi', strtotime($commande['date_commande']))?></span>
        </div>
        <div class="info-row">
          <span class="label">Statut</span>
          <span class="value">
            <span class="statut-badge statut-<?=htmlspecialchars($commande['statut'])?>">
              <i class="fas fa-circle" style="font-size:.4rem;"></i>
              <?=ucfirst(str_replace('_',' ',$commande['statut']))?>
            </span>
          </span>
        </div>
        <div class="info-row">
          <span class="label">Mode de paiement</span>
          <span class="value"><?=htmlspecialchars(ucfirst($commande['mode_paiement'] ?? 'carte'))?></span>
        </div>
        <div class="info-row">
          <span class="label">Livraison</span>
          <span class="value">
            <?= $commande['frais_livraison'] == 0 ? '<span style="color:var(--green)">Gratuite</span>' : number_format($commande['frais_livraison'],2,',',' ').'€' ?>
          </span>
        </div>
        <?php if($commande['notes']): ?>
        <div class="info-row">
          <span class="label">Note</span>
          <span class="value"><?=htmlspecialchars($commande['notes'])?></span>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- COLONNE DROITE -->
  <div>
    <div class="recap-box">
      <div class="recap-box-head">
        <h3>Récapitulatif</h3>
      </div>
      <div class="recap-lines">
        <div class="recap-line">
          <span>Sous-total</span>
          <span><?=number_format($commande['montant'] - $commande['frais_livraison'], 2, ',', ' ')?>€</span>
        </div>
        <div class="recap-line">
          <span>Livraison</span>
          <span><?= $commande['frais_livraison'] == 0 ? 'Gratuite' : number_format($commande['frais_livraison'],2,',',' ').'€' ?></span>
        </div>
        <div class="recap-line total-line">
          <span>Total TTC</span>
          <span><?=number_format($commande['montant'],2,',',' ')?>€</span>
        </div>
      </div>

      <div class="btn-group">
        <?php if($peut_annuler): ?>
        <a href="paiement.php?id=<?=$commande['id_commande']?>" class="btn-continuer">
          <i class="fas fa-lock"></i>
          Payer ma commande
        </a>
        <?php else: ?>
        <a href="../index.php" class="btn-continuer">
          <i class="fas fa-gem"></i>
          Continuer mes achats
        </a>
        <?php endif; ?>

        <?php if($peut_annuler): ?>
        <button class="btn-annuler" onclick="ouvrirModalAnnulation()">
          <i class="fas fa-times-circle"></i>
          Annuler cette commande
        </button>
        <?php else: ?>
        <button class="btn-annuler" disabled title="Cette commande ne peut plus être annulée">
          <i class="fas fa-lock"></i>
          Annulation impossible
        </button>
        <?php endif; ?>

        <a href="mes_commandes.php" class="btn-commandes">
          <i class="fas fa-list"></i> Voir toutes mes commandes
        </a>
      </div>
    </div>
  </div>

</div>

<!-- ── MODAL CONFIRMATION ANNULATION ── -->
<div class="modal-overlay" id="modalAnnulation">
  <div class="modal">
    <div class="modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
    <h3>Annuler la commande ?</h3>
    <p>
      Êtes-vous sûr de vouloir annuler la commande
      <strong><?=htmlspecialchars($commande['numero_commande'])?></strong> ?
      <br><br>Cette action est <strong>irréversible</strong>.
    </p>
    <div class="modal-btns">
      <form method="POST" action="annuler_commande.php" style="flex:1;">
        <input type="hidden" name="id_commande" value="<?=$commande['id_commande']?>">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? bin2hex(random_bytes(16)))?>">
        <button type="submit" name="confirmer_annulation" class="modal-btn-confirm" style="width:100%;">
          <i class="fas fa-trash-alt"></i> Oui, annuler
        </button>
      </form>
      <button class="modal-btn-cancel" onclick="fermerModal()">
        Non, garder
      </button>
    </div>
  </div>
</div>

<script>
// Curseur
const cur=document.getElementById('cursor'),ring=document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';});
(function r(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(r);})();
document.querySelectorAll('a,button,input').forEach(el=>{
  el.addEventListener('mouseenter',()=>document.body.classList.add('hovering'));
  el.addEventListener('mouseleave',()=>document.body.classList.remove('hovering'));
});

// Modal annulation
function ouvrirModalAnnulation(){
  document.getElementById('modalAnnulation').classList.add('active');
}
function fermerModal(){
  document.getElementById('modalAnnulation').classList.remove('active');
}
// Fermer en cliquant dehors
document.getElementById('modalAnnulation').addEventListener('click', function(e){
  if(e.target === this) fermerModal();
});
// Fermer avec Échap
document.addEventListener('keydown', e => { if(e.key==='Escape') fermerModal(); });
</script>

<?php include '../includes/footer.php'; ?>