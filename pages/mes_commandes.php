<?php
// ══════════════════════════════════════════
//  LUMOURA — Mes commandes
//  pages/mes_commandes.php
// ══════════════════════════════════════════
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Normaliser le statut (gère espaces, majuscules, variantes)
function normaliserStatut($s) {
    $s = strtolower(trim($s));
    $s = str_replace(' ', '_', $s);
    $map = [
        'en_attente'   => 'en_attente',
        'en attente'   => 'en_attente',
        'attente'      => 'en_attente',
        'pending'      => 'en_attente',
        'payee'        => 'payee',
        'payée'        => 'payee',
        'paye'         => 'payee',
        'paid'         => 'payee',
        'annulee'      => 'annulee',
        'annulée'      => 'annulee',
        'cancelled'    => 'annulee',
        'expediee'     => 'expediee',
        'expédiée'     => 'expediee',
        'shipped'      => 'expediee',
        'livree'       => 'livree',
        'livrée'       => 'livree',
        'delivered'    => 'livree',
    ];
    return $map[$s] ?? $s;
}

if (!isLoggedIn()) {
    header('Location: connexion.php?redirect=mes_commandes');
    exit();
}

// Récupérer toutes les commandes de l'utilisateur
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               COUNT(dc.id_detail) as nb_articles
        FROM commandes c
        LEFT JOIN details_commande dc ON dc.id_commande = c.id_commande
        WHERE c.id_utilisateur = ?
        GROUP BY c.id_commande
        ORDER BY c.date_commande DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $commandes = $stmt->fetchAll();
} catch(Exception $e) { $commandes = []; }

// Message flash
$flash_raw = $_SESSION['commande_message'] ?? null;
unset($_SESSION['commande_message']);
$flash = null;
$flash_type = 'success';
if ($flash_raw) {
    if (str_contains($flash_raw, '|')) {
        [$flash_type, $flash] = explode('|', $flash_raw, 2);
    } else {
        $flash = $flash_raw;
        $flash_type = str_contains($flash_raw, 'annulée') || str_contains($flash_raw, 'Erreur') ? 'error' : 'success';
    }
}

$pageTitle = "Mes commandes — Lumoura";
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

/* ── PAGE HEADER ── */
.page-header{
    background:var(--ink);padding:50px 60px;
    position:relative;overflow:hidden;
}
.page-header::after{
    content:'';position:absolute;bottom:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,transparent,var(--g1),transparent);
}
.page-header h1{
    font-family:'EB Garamond',serif;font-size:2.2rem;
    color:#fff;font-weight:400;margin-bottom:6px;
}
.page-header p{color:rgba(255,255,255,.35);font-size:.85rem;letter-spacing:.5px;}

/* ── WRAP ── */
.orders-wrap{max-width:1000px;margin:0 auto;padding:50px 60px 80px;}

/* ── FLASH ── */
.flash{
    padding:14px 20px;margin-bottom:24px;
    display:flex;align-items:center;gap:10px;font-size:.85rem;
}
.flash.success{background:rgba(39,174,96,.08);border-left:3px solid var(--green);color:var(--green);}
.flash.error{background:rgba(192,57,43,.08);border-left:3px solid var(--red);color:var(--red);}

/* ── VIDE ── */
.empty-state{
    text-align:center;padding:80px 20px;
    background:#fff;
}
.empty-state i{font-size:3rem;color:var(--stone);margin-bottom:20px;display:block;}
.empty-state h3{font-family:'EB Garamond',serif;font-size:1.5rem;margin-bottom:10px;color:var(--ink);}
.empty-state p{color:var(--muted);font-size:.88rem;margin-bottom:28px;}
.btn-shop{
    display:inline-flex;align-items:center;gap:10px;
    background:var(--g1);color:var(--ink);padding:14px 30px;
    font-family:'Cinzel',serif;font-size:.65rem;letter-spacing:2.5px;
    text-transform:uppercase;text-decoration:none;transition:background .3s;
}
.btn-shop:hover{background:var(--g2);}

/* ── FILTRES ── */
.filters{
    display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;
}
.filter-btn{
    padding:8px 18px;font-family:'Cinzel',serif;font-size:.55rem;
    letter-spacing:2px;text-transform:uppercase;border:1px solid var(--stone);
    background:transparent;cursor:pointer;transition:all .2s;color:var(--muted);
}
.filter-btn:hover,.filter-btn.active{
    background:var(--ink);color:var(--g1);border-color:var(--ink);
}

/* ── COMMANDE CARD ── */
.order-card{
    background:#fff;margin-bottom:2px;
    transition:box-shadow .2s;
}
.order-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.06);}

.order-card-head{
    padding:18px 26px;
    display:flex;align-items:center;gap:20px;
    border-bottom:1px solid var(--stone);
    cursor:pointer;
    user-select:none;
}
.order-card-head:hover{background:rgba(0,0,0,.01);}

.order-num{
    font-family:'Cinzel',serif;font-size:.65rem;
    letter-spacing:2px;text-transform:uppercase;color:var(--g3);
    min-width:160px;
}
.order-date{font-size:.8rem;color:var(--muted);flex:1;}
.order-montant{
    font-family:'EB Garamond',serif;font-size:1.1rem;
    color:var(--ink);min-width:90px;text-align:right;
}

/* Statut badge */
.statut-badge{
    display:inline-flex;align-items:center;gap:5px;
    padding:4px 12px;font-family:'Cinzel',serif;
    font-size:.5rem;letter-spacing:1.5px;text-transform:uppercase;
    min-width:110px;justify-content:center;
}
.statut-en_attente{background:rgba(212,168,67,.12);color:var(--g3);border:1px solid rgba(212,168,67,.25);}
.statut-payee{background:rgba(39,174,96,.1);color:var(--green);border:1px solid rgba(39,174,96,.25);}
.statut-annulee{background:rgba(192,57,43,.1);color:var(--red);border:1px solid rgba(192,57,43,.25);}
.statut-expediee{background:rgba(52,152,219,.1);color:#3498db;border:1px solid rgba(52,152,219,.25);}
.statut-livree{background:rgba(39,174,96,.1);color:var(--green);border:1px solid rgba(39,174,96,.25);}

.order-chevron{
    color:var(--muted);font-size:.8rem;
    transition:transform .3s;margin-left:8px;
}
.order-card.open .order-chevron{transform:rotate(180deg);}

/* Détails dépliables */
.order-card-body{
    display:none;padding:20px 26px;
    border-top:1px solid var(--stone);
    background:var(--smoke);
}
.order-card.open .order-card-body{display:block;}

.order-items-grid{display:flex;flex-direction:column;gap:0;margin-bottom:16px;}
.order-item-row{
    display:flex;align-items:center;gap:14px;
    padding:10px 0;border-bottom:1px solid rgba(0,0,0,.05);
}
.order-item-row:last-child{border-bottom:none;}
.order-thumb{width:48px;height:48px;object-fit:cover;border:1px solid var(--stone);}
.order-item-info{flex:1;}
.order-item-brand{font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:2px;text-transform:uppercase;color:var(--g1);}
.order-item-name{font-family:'EB Garamond',serif;font-size:.95rem;color:var(--ink);}
.order-item-qty{font-size:.72rem;color:var(--muted);}
.order-item-price{font-family:'EB Garamond',serif;font-size:.95rem;color:var(--ink);}

/* Actions bas de card */
.order-card-actions{
    display:flex;gap:10px;align-items:center;
    padding-top:16px;border-top:1px solid rgba(0,0,0,.05);
    margin-top:6px;
}
.btn-voir{
    display:inline-flex;align-items:center;gap:8px;
    color:var(--g3);font-family:'Cinzel',serif;font-size:.55rem;
    letter-spacing:2px;text-transform:uppercase;
    text-decoration:none;padding:8px 16px;
    border:1px solid rgba(212,168,67,.3);
    transition:all .2s;background:transparent;cursor:pointer;
}
.btn-voir:hover{background:var(--g1);color:var(--ink);border-color:var(--g1);}

.btn-annuler-sm{
    display:inline-flex;align-items:center;gap:8px;
    color:var(--red);font-family:'Cinzel',serif;font-size:.55rem;
    letter-spacing:2px;text-transform:uppercase;
    padding:8px 16px;border:1px solid rgba(192,57,43,.25);
    background:transparent;cursor:pointer;transition:all .2s;
}
.btn-annuler-sm:hover{background:var(--red);color:#fff;border-color:var(--red);}
.btn-annuler-sm:disabled{opacity:.3;cursor:not-allowed;}

/* ── STATS EN HAUT ── */
.stats-row{
    display:grid;grid-template-columns:repeat(4,1fr);gap:2px;
    margin-bottom:30px;
}
.stat-box{
    background:#fff;padding:20px 24px;text-align:center;
}
.stat-box .stat-num{
    font-family:'EB Garamond',serif;font-size:2rem;color:var(--g1);
    display:block;margin-bottom:4px;
}
.stat-box .stat-label{
    font-family:'Cinzel',serif;font-size:.5rem;
    letter-spacing:2px;text-transform:uppercase;color:var(--muted);
}

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

@media(max-width:768px){
    .orders-wrap,.breadcrumb,.page-header{padding-left:20px;padding-right:20px;}
    .order-card-head{flex-wrap:wrap;gap:10px;}
    .stats-row{grid-template-columns:repeat(2,1fr);}
    .order-num{min-width:auto;}
}
</style>

<div id="cursor"></div>
<div id="cursor-ring"></div>

<!-- Breadcrumb -->
<nav class="breadcrumb">
  <a href="../index.php">Accueil</a>
  <span class="breadcrumb-sep">›</span>
  <a href="mon_compte.php">Mon compte</a>
  <span class="breadcrumb-sep">›</span>
  <span class="breadcrumb-cur">Mes commandes</span>
</nav>

<!-- Header -->
<div class="page-header">
  <h1>Mes commandes</h1>
  <p><?=count($commandes)?> commande<?=count($commandes)>1?'s':''?> au total</p>
</div>

<div class="orders-wrap">

  <?php if($flash): ?>
  <div class="flash <?=htmlspecialchars($flash_type)?>">
    <i class="fas fa-<?=$flash_type==='error'?'times-circle':'check-circle'?>"></i>
    <?=htmlspecialchars($flash)?>
  </div>
  <?php endif; ?>

  <?php if(empty($commandes)): ?>
  <!-- État vide -->
  <div class="empty-state">
    <i class="fas fa-box-open"></i>
    <h3>Aucune commande</h3>
    <p>Vous n'avez pas encore passé de commande.<br>Explorez notre catalogue et découvrez nos bijoux d'exception.</p>
    <a href="catalogue.php" class="btn-shop">
      <i class="fas fa-gem"></i> Découvrir le catalogue
    </a>
  </div>

  <?php else: ?>

  <!-- Statistiques -->
  <?php
    $total_depense = array_sum(array_column($commandes, 'montant'));
    $nb_en_attente = count(array_filter($commandes, fn($c) => normaliserStatut($c['statut']) === 'en_attente'));
    $nb_annulees   = count(array_filter($commandes, fn($c) => normaliserStatut($c['statut']) === 'annulee'));
    $nb_livrees    = count(array_filter($commandes, fn($c) => in_array(normaliserStatut($c['statut']), ['livree','expediee','payee'])));
  ?>
  <div class="stats-row">
    <div class="stat-box">
      <span class="stat-num"><?=count($commandes)?></span>
      <span class="stat-label">Total commandes</span>
    </div>
    <div class="stat-box">
      <span class="stat-num" style="color:var(--g3);"><?=$nb_en_attente?></span>
      <span class="stat-label">En attente</span>
    </div>
    <div class="stat-box">
      <span class="stat-num" style="color:var(--green);"><?=$nb_livrees?></span>
      <span class="stat-label">Traitées</span>
    </div>
    <div class="stat-box">
      <span class="stat-num" style="font-size:1.4rem;"><?=number_format($total_depense,0,',',' ')?>€</span>
      <span class="stat-label">Total dépensé</span>
    </div>
  </div>

  <!-- Filtres -->
  <div class="filters">
    <button class="filter-btn active" onclick="filtrer('tous',this)">Toutes</button>
    <button class="filter-btn" onclick="filtrer('en_attente',this)">En attente</button>
    <button class="filter-btn" onclick="filtrer('payee',this)">Payées</button>
    <button class="filter-btn" onclick="filtrer('expediee',this)">Expédiées</button>
    <button class="filter-btn" onclick="filtrer('annulee',this)">Annulées</button>
  </div>

  <!-- Liste des commandes -->
  <?php foreach($commandes as $cmd): ?>
  <?php
    $peut_annuler = normaliserStatut($cmd['statut']) === 'en_attente';
    // Récupérer les articles de cette commande
    try {
        $stmtD = $pdo->prepare("
            SELECT dc.*, p.nom, p.image_url, p.marque 
            FROM details_commande dc
            JOIN produits p ON p.id_produit = dc.id_produit
            WHERE dc.id_commande = ?
        ");
        $stmtD->execute([$cmd['id_commande']]);
        $items = $stmtD->fetchAll();
    } catch(Exception $e) { $items = []; }
  ?>
  <div class="order-card" data-statut="<?=normaliserStatut($cmd['statut'])?>" id="card-<?=$cmd['id_commande']?>">

    <!-- En-tête cliquable -->
    <div class="order-card-head" onclick="toggleCard(<?=$cmd['id_commande']?>)">
      <div class="order-num"><?=htmlspecialchars($cmd['numero_commande'])?></div>
      <div class="order-date">
        <i class="far fa-calendar-alt" style="margin-right:5px;color:var(--muted);"></i>
        <?=date('d/m/Y à H\hi', strtotime($cmd['date_commande']))?>
      </div>
      <span class="statut-badge statut-<?=normaliserStatut($cmd['statut'])?>">
        <i class="fas fa-circle" style="font-size:.35rem;"></i>
        <?=ucfirst(str_replace('_',' ',normaliserStatut($cmd['statut'])))?>
      </span>
      <div class="order-montant"><?=number_format($cmd['montant'],2,',',' ')?>€</div>
      <i class="fas fa-chevron-down order-chevron"></i>
    </div>

    <!-- Corps dépliable -->
    <div class="order-card-body">
      <div class="order-items-grid">
        <?php foreach($items as $item): ?>
        <div class="order-item-row">
          <img class="order-thumb"
               src="<?=htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/48x48/1E1710/D4A843?text=✦')?>"
               alt="">
          <div class="order-item-info">
            <div class="order-item-brand"><?=htmlspecialchars($item['marque']??'')?></div>
            <div class="order-item-name"><?=htmlspecialchars($item['nom'])?></div>
            <div class="order-item-qty">× <?=$item['quantite']?></div>
          </div>
          <div class="order-item-price"><?=number_format($item['prix_unitaire']*$item['quantite'],2,',',' ')?>€</div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="order-card-actions">
        <a href="commande_confirmation.php?id=<?=$cmd['id_commande']?>" class="btn-voir">
          <i class="fas fa-eye"></i> Voir le détail
        </a>

        <?php if($peut_annuler): ?>
        <a href="paiement.php?id=<?=$cmd['id_commande']?>" class="btn-voir" style="background:var(--g1);color:var(--ink);border-color:var(--g1);">
          <i class="fas fa-lock"></i> Payer
        </a>
        <button class="btn-annuler-sm"
                onclick="ouvrirModal(<?=$cmd['id_commande']?>, '<?=htmlspecialchars($cmd['numero_commande'])?>')">
          <i class="fas fa-times-circle"></i> Annuler
        </button>
        <?php else: ?>
        <button class="btn-annuler-sm" disabled>
          <i class="fas fa-lock"></i>
          <?= normaliserStatut($cmd['statut']) === 'annulee' ? 'Déjà annulée' : 'Non annulable' ?>
        </button>
        <?php endif; ?>

        <span style="margin-left:auto;font-size:.75rem;color:var(--muted);">
          <?=$cmd['nb_articles']?> article<?=$cmd['nb_articles']>1?'s':''?>
          · Livraison <?=$cmd['frais_livraison']==0?'<span style="color:var(--green)">gratuite</span>':number_format($cmd['frais_livraison'],2,',',' ').'€'?>
        </span>
      </div>
    </div>

  </div>
  <?php endforeach; ?>

  <?php endif; ?>

</div>

<!-- ── MODAL ANNULATION ── -->
<div class="modal-overlay" id="modalAnnulation">
  <div class="modal">
    <div class="modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
    <h3>Annuler la commande ?</h3>
    <p id="modal-txt">Êtes-vous sûr de vouloir annuler cette commande ?<br>Cette action est <strong>irréversible</strong>.</p>
    <div class="modal-btns">
      <form method="POST" action="annuler_commande.php" style="flex:1;" id="formAnnulation">
        <input type="hidden" name="id_commande" id="modal-id-commande" value="">
        <button type="submit" name="confirmer_annulation" class="modal-btn-confirm" style="width:100%;">
          <i class="fas fa-trash-alt"></i> Oui, annuler
        </button>
      </form>
      <button class="modal-btn-cancel" onclick="fermerModal()">Non, garder</button>
    </div>
  </div>
</div>

<script>
// Curseur
const cur=document.getElementById('cursor'),ring=document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';});
(function r(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(r);})();
document.querySelectorAll('a,button').forEach(el=>{
  el.addEventListener('mouseenter',()=>document.body.classList.add('hovering'));
  el.addEventListener('mouseleave',()=>document.body.classList.remove('hovering'));
});

// Toggle card
function toggleCard(id) {
  const card = document.getElementById('card-' + id);
  card.classList.toggle('open');
}

// Filtres
function filtrer(statut, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.order-card').forEach(card => {
    if (statut === 'tous' || card.dataset.statut === statut) {
      card.style.display = '';
    } else {
      card.style.display = 'none';
    }
  });
}

// Modal annulation
function ouvrirModal(id, numero) {
  document.getElementById('modal-id-commande').value = id;
  document.getElementById('modal-txt').innerHTML =
    'Êtes-vous sûr de vouloir annuler la commande <strong>' + numero + '</strong> ?<br>Cette action est <strong>irréversible</strong>.';
  document.getElementById('modalAnnulation').classList.add('active');
}
function fermerModal() {
  document.getElementById('modalAnnulation').classList.remove('active');
}
document.getElementById('modalAnnulation').addEventListener('click', function(e){
  if(e.target === this) fermerModal();
});
document.addEventListener('keydown', e => { if(e.key==='Escape') fermerModal(); });
</script>

<?php include '../includes/footer.php'; ?>