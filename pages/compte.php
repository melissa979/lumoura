<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "pages/connexion.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$email = 'Non défini';
$date_inscription = 'Non défini';
$nom_complet = $_SESSION['user_nom'] ?? 'Utilisateur';

try {
    $stmt = $pdo->prepare("SELECT email, DATE_FORMAT(date_inscription, '%d/%m/%Y') AS date_inscription_format, prenom, nom FROM utilisateurs WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $email            = $user['email'] ?? 'Non défini';
        $date_inscription = $user['date_inscription_format'] ?? 'Non défini';
        $prenom           = trim($user['prenom'] ?? '');
        $nom              = trim($user['nom'] ?? '');
        $nom_complet      = $prenom . ($prenom && $nom ? ' ' : '') . $nom ?: 'Utilisateur';
        $_SESSION['user_nom']   = $nom_complet;
        $_SESSION['user_email'] = $email;
    }
} catch (PDOException $e) {}

// ── COMMANDES ──
$nb_commandes_total    = 0;
$nb_commandes_en_cours = 0;
$derniere_commande     = null;
try {
    $stmt_cmd = $pdo->prepare("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN LOWER(TRIM(statut)) IN ('en attente','en_attente','pending') THEN 1 ELSE 0 END) as en_cours
        FROM commandes WHERE id_utilisateur = :uid
    ");
    $stmt_cmd->execute(['uid' => $user_id]);
    $cmd_stats             = $stmt_cmd->fetch(PDO::FETCH_ASSOC);
    $nb_commandes_total    = (int)($cmd_stats['total']    ?? 0);
    $nb_commandes_en_cours = (int)($cmd_stats['en_cours'] ?? 0);

    $stmt_last = $pdo->prepare("SELECT numero_commande, statut, montant, date_commande FROM commandes WHERE id_utilisateur = :uid ORDER BY date_commande DESC LIMIT 1");
    $stmt_last->execute(['uid' => $user_id]);
    $derniere_commande = $stmt_last->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── ADRESSES ──
// Colonnes réelles : id_adresse, id_utilisateur, complement_adresse
$has_adresses = false;
$adresses = [];
try {
    $stmt_adresses = $pdo->prepare("
        SELECT id_adresse, prenom, nom, adresse, complement_adresse,
               code_postal, ville, pays, telephone, est_principale
        FROM adresses
        WHERE id_utilisateur = :uid
        ORDER BY est_principale DESC, id_adresse DESC
    ");
    $stmt_adresses->execute(['uid' => $user_id]);
    $adresses     = $stmt_adresses->fetchAll(PDO::FETCH_ASSOC);
    $has_adresses = count($adresses) > 0;
} catch (PDOException $e) {}

// ── FAVORIS ──
$nb_favoris = 0;
try {
    $stmt_fav = $pdo->prepare("SELECT COUNT(*) FROM liste_envies WHERE id_utilisateur = :uid");
    $stmt_fav->execute(['uid' => $user_id]);
    $nb_favoris = (int)$stmt_fav->fetchColumn();
} catch (Exception $e) {}

function statutLabel($s) {
    $map = [
        'en attente' => ['En attente', '#D4A843'],
        'en_attente' => ['En attente', '#D4A843'],
        'payee'      => ['Payée',      '#27ae60'],
        'payée'      => ['Payée',      '#27ae60'],
        'expediee'   => ['Expédiée',   '#3498db'],
        'annulee'    => ['Annulée',    '#C0392B'],
        'annulée'    => ['Annulée',    '#C0392B'],
        'livree'     => ['Livrée',     '#27ae60'],
    ];
    return $map[strtolower(trim($s))] ?? [ucfirst($s), '#8A7D6A'];
}

$pageTitle = "Mon Compte - Lumoura";
include '../includes/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Didact+Gothic&family=EB+Garamond:ital,wght@0,400;0,500;0,700;1,400;1,600&family=Cinzel:wght@400;600;700;900&display=swap" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
<style>
:root{--g1:#D4A843;--g2:#F5D78E;--g3:#B8882C;--ink:#0D0A06;--smoke:#F8F5EF;--stone:#E8E0D0;--muted:#8A7D6A;--red:#C0392B;}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Didact Gothic',sans-serif;background:var(--smoke);color:var(--ink);overflow-x:hidden;cursor:none;}
#cursor{position:fixed;width:10px;height:10px;background:var(--g1);border-radius:50%;pointer-events:none;z-index:99999;transform:translate(-50%,-50%);transition:width .25s,height .25s;}
#cursor-ring{position:fixed;width:36px;height:36px;border:1px solid var(--g1);border-radius:50%;pointer-events:none;z-index:99998;transform:translate(-50%,-50%);transition:.3s;opacity:.6;}
body.hovering #cursor{width:20px;height:20px;background:var(--g2);}
body.hovering #cursor-ring{width:54px;height:54px;opacity:.4;}

.compte-hero{background:var(--ink);padding:100px 40px 80px;text-align:center;position:relative;overflow:hidden;}
.compte-hero::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(212,168,67,.08),transparent),radial-gradient(circle at 20% 50%,rgba(212,168,67,.12),transparent 50%);}
.compte-hero-content{position:relative;z-index:2;max-width:700px;margin:0 auto;}
.compte-tag{font-family:'Cinzel',serif;font-size:.6rem;letter-spacing:5px;text-transform:uppercase;color:var(--g2);margin-bottom:18px;}
.compte-hero h1{font-family:'EB Garamond',serif;font-size:clamp(2.5rem,5vw,4rem);color:#fff;font-weight:400;letter-spacing:2px;margin-bottom:16px;}
.compte-hero h1 em{color:var(--g1);font-style:italic;}
.compte-welcome{color:rgba(255,255,255,.7);font-size:1.05rem;}
.compte-welcome strong{color:var(--g2);}

.compte-main{padding:70px 40px 90px;max-width:1300px;margin:0 auto;}
.compte-grid{display:grid;grid-template-columns:1fr 1fr;gap:35px;}

.profil-card{background:#fff;border-radius:20px;padding:50px 45px;box-shadow:0 10px 40px rgba(0,0,0,.08);text-align:center;transition:all .4s;border:1px solid var(--stone);position:relative;overflow:hidden;}
.profil-card:hover{transform:translateY(-8px);box-shadow:0 20px 60px rgba(0,0,0,.15);border-color:var(--g1);}
.profil-icon{width:90px;height:90px;background:linear-gradient(135deg,var(--g1),var(--g2));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;}
.profil-icon i{font-size:2.5rem;color:var(--ink);}
.profil-name{font-family:'EB Garamond',serif;font-size:1.8rem;color:var(--ink);margin-bottom:18px;font-weight:600;}
.profil-info{margin-bottom:12px;font-size:.88rem;color:var(--muted);}
.profil-info strong{color:var(--ink);}
.profil-btns{display:flex;flex-direction:column;gap:12px;margin-top:22px;}
.btn-modifier{background:var(--ink);color:#fff;border:none;padding:14px 36px;font-family:'Cinzel',serif;font-size:.65rem;letter-spacing:2.5px;text-transform:uppercase;text-decoration:none;display:inline-block;transition:background .3s;}
.btn-modifier:hover{background:var(--g1);color:var(--ink);}
.btn-deconnexion{background:transparent;color:var(--red);border:1.5px solid var(--red);padding:12px 36px;font-family:'Cinzel',serif;font-size:.6rem;letter-spacing:2.5px;text-transform:uppercase;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:8px;transition:all .3s;}
.btn-deconnexion:hover{background:var(--red);color:#fff;}

.action-card{background:#fff;border-radius:18px;padding:42px 38px;box-shadow:0 8px 30px rgba(0,0,0,.06);transition:all .4s;border:1px solid var(--stone);display:flex;flex-direction:column;height:100%;}
.action-card:hover{transform:translateY(-6px);box-shadow:0 15px 45px rgba(0,0,0,.12);border-color:var(--g1);}
.action-icon{width:70px;height:70px;background:linear-gradient(135deg,rgba(212,168,67,.15),rgba(212,168,67,.25));border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:24px;transition:all .4s;}
.action-card:hover .action-icon{background:linear-gradient(135deg,var(--g1),var(--g2));transform:scale(1.08) rotate(3deg);}
.action-icon i{font-size:1.8rem;color:var(--g1);transition:color .4s;}
.action-card:hover .action-icon i{color:var(--ink);}
.action-title{font-family:'EB Garamond',serif;font-size:1.4rem;color:var(--ink);margin-bottom:14px;font-weight:600;}
.action-desc{font-size:.85rem;color:var(--muted);line-height:1.7;margin-bottom:16px;flex:1;}
.action-count{font-size:.9rem;color:var(--g1);font-weight:600;margin-bottom:16px;}

.last-order{background:var(--smoke);border:1px solid var(--stone);border-radius:10px;padding:16px 18px;margin-bottom:18px;}
.last-order-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;}
.last-order-num{font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2px;color:var(--g3);}
.last-order-date{font-size:.75rem;color:var(--muted);}
.last-order-bottom{display:flex;justify-content:space-between;align-items:center;}
.last-order-montant{font-family:'EB Garamond',serif;font-size:1.15rem;color:var(--ink);}
.statut-pill{display:inline-block;padding:3px 10px;border-radius:20px;font-family:'Cinzel',serif;font-size:.5rem;letter-spacing:1.5px;text-transform:uppercase;}

.btn-action{background:transparent;border:2px solid var(--stone);color:var(--ink);padding:12px 28px;font-family:'Cinzel',serif;font-size:.6rem;letter-spacing:2px;text-transform:uppercase;text-decoration:none;display:block;transition:all .3s;text-align:center;margin-top:auto;}
.btn-action:hover{background:var(--g1);border-color:var(--g1);color:var(--ink);transform:translateY(-2px);}
.btn-action-gold{background:var(--g1) !important;border-color:var(--g1) !important;color:var(--ink) !important;}
.btn-action-gold:hover{background:var(--g2) !important;border-color:var(--g2) !important;}

/* ── ADRESSES LISTE ── */
.adresses-list{margin:16px 0;max-height:320px;overflow-y:auto;padding-right:4px;}
.adresses-list::-webkit-scrollbar{width:4px;}
.adresses-list::-webkit-scrollbar-thumb{background:var(--g1);border-radius:2px;}

/* Carte adresse avec boutons */
.adresse-item{
  padding:16px;background:var(--smoke);border-radius:10px;
  margin-bottom:12px;border:1px solid var(--stone);
  font-size:.82rem;line-height:1.7;transition:all .3s;
}
.adresse-item:hover{background:#fff;border-color:var(--g1);}
.adresse-item.principale{border-left:3px solid var(--g1);}

.adresse-item-nom{
  font-family:'EB Garamond',serif;font-size:1rem;
  color:var(--ink);font-weight:600;margin-bottom:6px;
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;
}
.badge-principale{
  background:var(--g1);color:var(--ink);
  font-family:'Cinzel',serif;font-size:.45rem;
  letter-spacing:1.5px;padding:2px 8px;border-radius:10px;
  text-transform:uppercase;
}
.adresse-item-ligne{color:var(--muted);font-size:.82rem;line-height:1.7;}

/* Boutons Modifier / Supprimer directement sur la carte */
.adresse-item-actions{
  display:flex;gap:8px;margin-top:12px;
  padding-top:10px;border-top:1px solid var(--stone);
}
.btn-addr-edit{
  display:inline-flex;align-items:center;gap:5px;
  background:transparent;border:1px solid var(--g1);
  color:var(--g3);padding:6px 14px;
  font-family:'Cinzel',serif;font-size:.52rem;
  letter-spacing:1.5px;text-transform:uppercase;
  text-decoration:none;transition:all .3s;border-radius:2px;
}
.btn-addr-edit:hover{background:var(--g1);color:var(--ink);}

.btn-addr-del{
  display:inline-flex;align-items:center;gap:5px;
  background:transparent;border:1px solid rgba(192,57,43,.4);
  color:var(--red);padding:6px 14px;
  font-family:'Cinzel',serif;font-size:.52rem;
  letter-spacing:1.5px;text-transform:uppercase;
  text-decoration:none;transition:all .3s;border-radius:2px;
}
.btn-addr-del:hover{background:var(--red);color:#fff;border-color:var(--red);}

.no-data{text-align:center;padding:30px 20px;color:var(--muted);font-style:italic;font-size:.85rem;}
.no-data i{font-size:1.8rem;color:var(--stone);margin-bottom:10px;display:block;}

@media(max-width:900px){.compte-grid{grid-template-columns:1fr;}body{cursor:auto;}#cursor,#cursor-ring{display:none;}}
</style>

<div id="cursor"></div>
<div id="cursor-ring"></div>

<section class="compte-hero">
  <div class="compte-hero-content" data-aos="fade-up">
    <div class="compte-tag">Mon Espace Personnel</div>
    <h1>Mon <em>Compte</em></h1>
    <p class="compte-welcome">Bienvenue, <strong><?=htmlspecialchars($nom_complet)?></strong></p>
  </div>
</section>

<section class="compte-main">
  <div class="compte-grid">

    <!-- PROFIL -->
    <div class="profil-card" data-aos="fade-up">
      <div class="profil-icon"><i class="fas fa-user"></i></div>
      <h2 class="profil-name"><?=htmlspecialchars($nom_complet)?></h2>
      <div class="profil-info"><strong>Email :</strong> <?=htmlspecialchars($email)?></div>
      <div class="profil-info"><strong>Membre depuis :</strong> <?=htmlspecialchars($date_inscription)?></div>
      <div class="profil-btns">
        <a href="<?=SITE_URL?>pages/modifier_profil.php" class="btn-modifier">Modifier mon profil</a>
        <a href="<?=SITE_URL?>pages/deconnexion.php" class="btn-deconnexion">
          <i class="fas fa-sign-out-alt"></i> Se déconnecter
        </a>
      </div>
    </div>

    <!-- COMMANDES -->
    <div class="action-card" data-aos="fade-up" data-aos-delay="100">
      <div class="action-icon"><i class="fas fa-shopping-bag"></i></div>
      <h3 class="action-title">Mes Commandes</h3>
      <p class="action-desc">Suivez l'état de vos commandes, payez ou annulez une commande en attente.</p>

      <?php if($nb_commandes_total > 0): ?>
        <div class="action-count">
          <?=$nb_commandes_total?> commande<?=$nb_commandes_total>1?'s':''?> au total
          <?php if($nb_commandes_en_cours > 0): ?>
            &nbsp;·&nbsp;<span style="color:var(--g3);"><?=$nb_commandes_en_cours?> en attente</span>
          <?php endif; ?>
        </div>
        <?php if($derniere_commande): ?>
          <?php [$label, $color] = statutLabel($derniere_commande['statut']); ?>
          <div class="last-order">
            <div class="last-order-top">
              <span class="last-order-num"><?=htmlspecialchars($derniere_commande['numero_commande'])?></span>
              <span class="last-order-date"><?=date('d/m/Y', strtotime($derniere_commande['date_commande']))?></span>
            </div>
            <div class="last-order-bottom">
              <span class="last-order-montant"><?=number_format($derniere_commande['montant'],2,',',' ')?>€</span>
              <span class="statut-pill" style="background:<?=$color?>22;color:<?=$color?>;border:1px solid <?=$color?>44;">
                <?=$label?>
              </span>
            </div>
          </div>
        <?php endif; ?>
        <a href="<?=SITE_URL?>pages/mes_commandes.php" class="btn-action btn-action-gold">
          <i class="fas fa-list" style="margin-right:6px;"></i> Voir toutes mes commandes
        </a>
      <?php else: ?>
        <div class="no-data" style="padding:20px 0;">Aucune commande pour l'instant</div>
        <a href="<?=SITE_URL?>pages/catalogue.php" class="btn-action">
          <i class="fas fa-gem" style="margin-right:6px;"></i> Commencer à acheter
        </a>
      <?php endif; ?>
    </div>

  </div>

  <!-- DEUXIÈME LIGNE -->
  <div class="compte-grid" style="margin-top:35px;">

    <!-- ══════════════════════════════════
         ADRESSES — avec Modifier/Supprimer
    ══════════════════════════════════ -->
    <div class="action-card" data-aos="fade-up" data-aos-delay="200">
      <div class="action-icon"><i class="fas fa-map-marker-alt"></i></div>
      <h3 class="action-title">Mes Adresses</h3>
      <p class="action-desc">Gérez vos adresses de livraison et de facturation</p>

      <?php if($has_adresses): ?>
        <div class="action-count">
          <?=count($adresses)?> adresse<?=count($adresses)>1?'s':''?> enregistrée<?=count($adresses)>1?'s':''?>
        </div>
        <div class="adresses-list">
          <?php foreach($adresses as $addr): ?>
          <div class="adresse-item <?=$addr['est_principale']?'principale':''?>">

            <!-- Nom + badge -->
            <div class="adresse-item-nom">
              <?=htmlspecialchars(trim(($addr['prenom']??'').' '.($addr['nom']??'')))?>
              <?php if(!empty($addr['est_principale'])): ?>
                <span class="badge-principale"><i class="fas fa-star" style="font-size:.4rem;"></i> Principale</span>
              <?php endif; ?>
            </div>

            <!-- Lignes adresse -->
            <div class="adresse-item-ligne">
              <?=htmlspecialchars($addr['adresse']??'')?>
              <?php if(!empty($addr['complement_adresse'])): ?>
                , <?=htmlspecialchars($addr['complement_adresse'])?>
              <?php endif; ?>
              <br><?=htmlspecialchars(($addr['code_postal']??'').' '.($addr['ville']??''))?>
              — <?=htmlspecialchars($addr['pays']??'')?>
              <?php if(!empty($addr['telephone'])): ?>
                <br><i class="fas fa-phone" style="color:var(--g1);font-size:.7rem;margin-right:3px;"></i><?=htmlspecialchars($addr['telephone'])?>
              <?php endif; ?>
            </div>

            <!-- ✅ BOUTONS MODIFIER / SUPPRIMER -->
            <div class="adresse-item-actions">
              <a href="<?=SITE_URL?>pages/ajouter_adresse.php?modifier=<?=(int)$addr['id_adresse']?>"
                 class="btn-addr-edit">
                <i class="fas fa-pencil-alt"></i> Modifier
              </a>
              <a href="<?=SITE_URL?>pages/ajouter_adresse.php?supprimer=<?=(int)$addr['id_adresse']?>"
                 class="btn-addr-del"
                 onclick="return confirm('Supprimer cette adresse ?')">
                <i class="fas fa-trash-alt"></i> Supprimer
              </a>
            </div>

          </div>
          <?php endforeach; ?>
        </div>

      <?php else: ?>
        <div class="no-data">
          <i class="fas fa-map-marker-alt"></i>
          Aucune adresse enregistrée
        </div>
      <?php endif; ?>

      <a href="<?=SITE_URL?>pages/ajouter_adresse.php" class="btn-action" style="margin-top:16px;">
        <i class="fas fa-plus" style="margin-right:6px;"></i> Ajouter une adresse
      </a>
    </div>

    <!-- FAVORIS -->
    <div class="action-card" data-aos="fade-up" data-aos-delay="300">
      <div class="action-icon"><i class="fas fa-heart"></i></div>
      <h3 class="action-title">Ma Liste d'Envies</h3>
      <p class="action-desc">Retrouvez tous vos bijoux favoris et coups de cœur sauvegardés</p>
      <div class="action-count"><?=$nb_favoris?> article<?=$nb_favoris>1?'s':''?> sauvegardé<?=$nb_favoris>1?'s':''?></div>
      <a href="<?=SITE_URL?>pages/liste_envies.php" class="btn-action">Voir mes favoris</a>
    </div>

  </div>
</section>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
AOS.init({duration:800,once:true,easing:'ease-out-cubic',offset:50});
const cur=document.getElementById('cursor'),ring=document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';});
(function r(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(r);})();
document.querySelectorAll('a,button,.profil-card,.action-card,.adresse-item').forEach(el=>{
  el.addEventListener('mouseenter',()=>document.body.classList.add('hovering'));
  el.addEventListener('mouseleave',()=>document.body.classList.remove('hovering'));
});
</script>
<?php include '../includes/footer.php'; ?>