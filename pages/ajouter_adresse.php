<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "pages/connexion.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$erreurs = [];
$success = false;

// ══════════════════════════════════════════
//  SUPPRESSION
// ══════════════════════════════════════════
if (isset($_GET['supprimer']) && is_numeric($_GET['supprimer'])) {
    $id_suppr = (int)$_GET['supprimer'];
    try {
        $pdo->prepare("DELETE FROM adresses WHERE id_adresse = ? AND id_utilisateur = ?")
            ->execute([$id_suppr, $user_id]);
        header("Location: ajouter_adresse.php?deleted=1");
        exit();
    } catch (Exception $e) {
        $erreurs[] = "Erreur lors de la suppression.";
    }
}

// ══════════════════════════════════════════
//  CHARGEMENT POUR MODIFICATION
// ══════════════════════════════════════════
$adresse_edit = null;
if (isset($_GET['modifier']) && is_numeric($_GET['modifier'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM adresses WHERE id_adresse = ? AND id_utilisateur = ?");
        $stmt->execute([(int)$_GET['modifier'], $user_id]);
        $adresse_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// ══════════════════════════════════════════
//  TRAITEMENT FORMULAIRE
// ══════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom      = trim($_POST['prenom'] ?? '');
    $nom         = trim($_POST['nom'] ?? '');
    $adresse     = trim($_POST['adresse'] ?? '');
    $complement  = trim($_POST['complement'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $ville       = trim($_POST['ville'] ?? '');
    $pays        = trim($_POST['pays'] ?? 'France');
    $telephone   = trim($_POST['telephone'] ?? '');
    $principale  = isset($_POST['principale']) ? 1 : 0;
    $id_modif    = (int)($_POST['id_modif'] ?? 0);

    if (empty($prenom))      $erreurs[] = "Le prénom est obligatoire";
    if (empty($nom))         $erreurs[] = "Le nom est obligatoire";
    if (empty($adresse))     $erreurs[] = "L'adresse est obligatoire";
    if (empty($code_postal)) $erreurs[] = "Le code postal est obligatoire";
    if (empty($ville))       $erreurs[] = "La ville est obligatoire";

    if (empty($erreurs)) {
        try {
            // Retirer le statut principal des autres si nécessaire
            if ($principale) {
                $pdo->prepare("UPDATE adresses SET est_principale = 0 WHERE id_utilisateur = ?")
                    ->execute([$user_id]);
            }

            if ($id_modif > 0) {
                // MODIFICATION
                $stmt = $pdo->prepare("
                    UPDATE adresses SET
                        prenom = ?, nom = ?, adresse = ?, complement_adresse = ?,
                        code_postal = ?, ville = ?, pays = ?, telephone = ?, est_principale = ?
                    WHERE id_adresse = ? AND id_utilisateur = ?
                ");
                $stmt->execute([
                    $prenom, $nom, $adresse, $complement,
                    $code_postal, $ville, $pays, $telephone, $principale,
                    $id_modif, $user_id
                ]);
            } else {
                // AJOUT — sans date_creation pour compatibilité maximale
                $stmt = $pdo->prepare("
                    INSERT INTO adresses
                        (id_utilisateur, prenom, nom, adresse, complement_adresse,
                         code_postal, ville, pays, telephone, est_principale)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id, $prenom, $nom, $adresse, $complement,
                    $code_postal, $ville, $pays, $telephone, $principale
                ]);
            }

            $success     = true;
            $adresse_edit = null;
            $_POST        = []; // Vider les champs après succès

        } catch (Exception $e) {
            $erreurs[] = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}

// ══════════════════════════════════════════
//  CHARGER LES ADRESSES — TOUJOURS APRÈS LE POST
//  → La nouvelle adresse s'affiche immédiatement
// ══════════════════════════════════════════
$mes_adresses = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM adresses
        WHERE id_utilisateur = ?
        ORDER BY est_principale DESC, id_adresse DESC
    ");
    $stmt->execute([$user_id]);
    $mes_adresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$pageTitle = "Mes Adresses - Lumoura";
include '../includes/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Didact+Gothic&family=EB+Garamond:ital,wght@0,400;0,500;0,700;1,400;1,600&family=Cinzel:wght@400;600;700;900&display=swap" rel="stylesheet">

<style>
:root {
  --g1:#D4A843; --g2:#F5D78E; --g3:#B8882C;
  --ink:#0D0A06; --smoke:#F8F5EF; --stone:#E8E0D0; --muted:#8A7D6A;
  --red:#C0392B; --green:#27AE60;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Didact Gothic',sans-serif;background:var(--smoke);color:var(--ink);}

#cursor{position:fixed;width:10px;height:10px;background:var(--g1);border-radius:50%;pointer-events:none;z-index:99999;transform:translate(-50%,-50%);transition:width .25s,height .25s;}
#cursor-ring{position:fixed;width:36px;height:36px;border:1px solid var(--g1);border-radius:50%;pointer-events:none;z-index:99998;transform:translate(-50%,-50%);transition:.3s;opacity:.6;}
body.hovering #cursor{width:20px;height:20px;background:var(--g2);}
body.hovering #cursor-ring{width:54px;height:54px;opacity:.4;}

.page-hero{background:var(--ink);padding:100px 20px 60px;text-align:center;position:relative;overflow:hidden;}
.page-hero::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(212,168,67,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(212,168,67,.04) 1px,transparent 1px);background-size:50px 50px;z-index:1;}
.page-hero-content{position:relative;z-index:2;}
.page-hero-tag{font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:5px;text-transform:uppercase;color:var(--g2);display:flex;align-items:center;justify-content:center;gap:14px;margin-bottom:18px;}
.page-hero-tag::before,.page-hero-tag::after{content:'';width:40px;height:1px;background:var(--g1);}
.page-hero h1{font-family:'EB Garamond',serif;font-size:clamp(2.5rem,5vw,3.8rem);font-weight:400;color:#fff;letter-spacing:1px;}
.page-hero h1 em{font-style:italic;color:var(--g2);}

.page-container{max-width:1100px;margin:60px auto;padding:0 20px 80px;display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:start;}

/* Formulaire */
.form-card{background:#fff;border:1px solid var(--stone);padding:40px;position:relative;}
.form-card::before{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:120px;height:3px;background:linear-gradient(90deg,transparent,var(--g1),transparent);}
.form-card.edit-mode{border-color:var(--g1);}
.form-card h2{font-family:'EB Garamond',serif;font-size:1.6rem;font-weight:400;color:var(--ink);margin-bottom:28px;padding-bottom:16px;border-bottom:1px solid var(--stone);}
.form-card h2 i{color:var(--g1);margin-right:10px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;}
.form-row.full{grid-template-columns:1fr;}
.form-row.split-3{grid-template-columns:1fr 2fr;}
.form-group{display:flex;flex-direction:column;}
.form-group label{font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;font-weight:600;}
.form-control{padding:13px 16px;border:1px solid var(--stone);background:var(--smoke);font-family:'Didact Gothic',sans-serif;font-size:.9rem;color:var(--ink);transition:all .3s;}
.form-control:focus{outline:none;border-color:var(--g1);background:#fff;box-shadow:0 0 0 3px rgba(212,168,67,.1);}
.form-check{display:flex;align-items:center;gap:12px;padding:14px;background:var(--smoke);border-left:3px solid var(--g1);margin-bottom:20px;}
.form-check input[type="checkbox"]{width:18px;height:18px;accent-color:var(--g1);cursor:pointer;}
.form-check label{font-size:.88rem;color:var(--ink);cursor:pointer;}

/* Alertes */
.alert{padding:16px 20px;margin-bottom:22px;display:flex;align-items:center;gap:12px;font-size:.82rem;}
.alert-success{background:rgba(39,174,96,.1);border-left:3px solid var(--green);color:var(--green);font-family:'Cinzel',serif;letter-spacing:1px;font-size:.72rem;}
.alert-danger{background:rgba(192,57,43,.08);border-left:3px solid var(--red);color:var(--red);}
.alert ul{list-style:none;padding:0;margin:0;}
.alert li{margin-bottom:4px;}
.edit-banner{background:rgba(212,168,67,.1);border-left:3px solid var(--g1);padding:12px 18px;margin-bottom:20px;font-size:.82rem;color:var(--g3);display:flex;align-items:center;gap:10px;}

/* Boutons */
.btn-submit{width:100%;background:var(--g1);color:var(--ink);border:none;padding:15px;font-family:'Cinzel',serif;font-size:.68rem;letter-spacing:3px;text-transform:uppercase;font-weight:600;cursor:pointer;transition:background .3s;position:relative;overflow:hidden;}
.btn-submit::before{content:'';position:absolute;inset:0;background:var(--g2);transform:scaleX(0);transform-origin:right;transition:transform .35s cubic-bezier(.77,0,.18,1);z-index:0;}
.btn-submit:hover::before{transform:scaleX(1);transform-origin:left;}
.btn-submit span{position:relative;z-index:1;}
.btn-annuler-edit{display:inline-flex;align-items:center;gap:7px;background:transparent;border:1.5px solid var(--stone);color:var(--muted);padding:9px 18px;font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2px;text-transform:uppercase;text-decoration:none;transition:all .3s;}
.btn-annuler-edit:hover{border-color:var(--red);color:var(--red);}
.btn-retour{display:inline-flex;align-items:center;gap:8px;background:var(--ink);color:#fff;padding:12px 24px;font-family:'Cinzel',serif;font-size:.6rem;letter-spacing:2px;text-transform:uppercase;text-decoration:none;margin-top:20px;transition:background .3s;}
.btn-retour:hover{background:var(--g1);color:var(--ink);}

/* Liste adresses */
.adresses-section{display:flex;flex-direction:column;}
.adresses-section h2{font-family:'EB Garamond',serif;font-size:1.6rem;font-weight:400;color:var(--ink);margin-bottom:20px;display:flex;align-items:center;gap:12px;}
.adresses-section h2 .count{font-family:'Cinzel',serif;font-size:.65rem;letter-spacing:2px;background:var(--g1);color:var(--ink);padding:4px 12px;}

.adresse-card{background:#fff;border:1px solid var(--stone);padding:24px 28px;margin-bottom:16px;position:relative;transition:all .3s;}
.adresse-card:hover{border-color:var(--g1);box-shadow:0 8px 25px rgba(0,0,0,.08);}
.adresse-card.principale{border-left:4px solid var(--g1);}
.badge-princ{display:inline-block;background:var(--g1);color:var(--ink);font-family:'Cinzel',serif;font-size:.48rem;letter-spacing:1.5px;padding:3px 10px;text-transform:uppercase;margin-bottom:12px;}
.adresse-nom{font-family:'EB Garamond',serif;font-size:1.15rem;color:var(--ink);font-weight:600;margin-bottom:8px;}
.adresse-ligne{font-size:.85rem;color:var(--muted);line-height:1.8;}
.adresse-tel{font-size:.8rem;color:var(--muted);margin-top:6px;}
.adresse-tel i{color:var(--g1);margin-right:4px;}
.adresse-actions{display:flex;gap:10px;margin-top:18px;padding-top:16px;border-top:1px solid var(--stone);}
.btn-modifier-addr{display:inline-flex;align-items:center;gap:7px;background:transparent;border:1.5px solid var(--g1);color:var(--g3);padding:9px 18px;font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2px;text-transform:uppercase;text-decoration:none;transition:all .3s;}
.btn-modifier-addr:hover{background:var(--g1);color:var(--ink);}
.btn-supprimer-addr{display:inline-flex;align-items:center;gap:7px;background:transparent;border:1.5px solid rgba(192,57,43,.4);color:var(--red);padding:9px 18px;font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:2px;text-transform:uppercase;cursor:pointer;transition:all .3s;}
.btn-supprimer-addr:hover{background:var(--red);color:#fff;border-color:var(--red);}

.no-adresse{background:#fff;border:1px dashed var(--stone);padding:40px;text-align:center;color:var(--muted);font-style:italic;font-size:.9rem;}
.no-adresse i{font-size:2rem;color:var(--stone);margin-bottom:14px;display:block;}

/* Modal suppression */
.confirm-delete{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center;}
.confirm-delete.show{display:flex;}
.confirm-box{background:#fff;padding:40px;max-width:420px;width:90%;text-align:center;}
.confirm-box h3{font-family:'EB Garamond',serif;font-size:1.5rem;color:var(--ink);margin-bottom:14px;}
.confirm-box p{color:var(--muted);font-size:.88rem;margin-bottom:28px;line-height:1.7;}
.confirm-btns{display:flex;gap:12px;justify-content:center;}
.btn-confirm-yes{background:var(--red);color:#fff;border:none;padding:12px 28px;font-family:'Cinzel',serif;font-size:.62rem;letter-spacing:2px;text-transform:uppercase;cursor:pointer;text-decoration:none;display:inline-block;}
.btn-confirm-no{background:transparent;color:var(--ink);border:1.5px solid var(--stone);padding:12px 28px;font-family:'Cinzel',serif;font-size:.62rem;letter-spacing:2px;text-transform:uppercase;cursor:pointer;}

@media(max-width:900px){.page-container{grid-template-columns:1fr;}body{cursor:auto;}#cursor,#cursor-ring{display:none;}}
@media(max-width:600px){.form-row,.form-row.split-3{grid-template-columns:1fr;}.form-card{padding:28px 20px;}}
</style>

<div id="cursor"></div>
<div id="cursor-ring"></div>

<section class="page-hero">
  <div class="page-hero-content">
    <div class="page-hero-tag">Mon Compte</div>
    <h1>Mes <em>Adresses</em></h1>
  </div>
</section>

<div class="page-container">

  <!-- FORMULAIRE -->
  <div>
    <div class="form-card <?= $adresse_edit ? 'edit-mode' : '' ?>">

      <?php if ($adresse_edit): ?>
        <div class="edit-banner"><i class="fas fa-pencil-alt"></i> Modification d'une adresse existante</div>
        <h2><i class="fas fa-pencil-alt"></i>Modifier l'adresse</h2>
      <?php else: ?>
        <h2><i class="fas fa-plus-circle"></i>Ajouter une adresse</h2>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle"></i>
          Adresse enregistrée avec succès !
        </div>
      <?php endif; ?>

      <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">
          <i class="fas fa-trash-alt"></i>
          Adresse supprimée avec succès.
        </div>
      <?php endif; ?>

      <?php if (!empty($erreurs)): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle"></i>
          <ul><?php foreach ($erreurs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="ajouter_adresse.php">
        <input type="hidden" name="id_modif" value="<?= $adresse_edit ? (int)$adresse_edit['id_adresse'] : 0 ?>">

        <div class="form-row">
          <div class="form-group">
            <label>Prénom *</label>
            <input type="text" name="prenom" class="form-control" required
                   value="<?= htmlspecialchars($adresse_edit['prenom'] ?? $_POST['prenom'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Nom *</label>
            <input type="text" name="nom" class="form-control" required
                   value="<?= htmlspecialchars($adresse_edit['nom'] ?? $_POST['nom'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row full">
          <div class="form-group">
            <label>Adresse *</label>
            <input type="text" name="adresse" class="form-control" required
                   value="<?= htmlspecialchars($adresse_edit['adresse'] ?? $_POST['adresse'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row full">
          <div class="form-group">
            <label>Complément d'adresse</label>
            <input type="text" name="complement" class="form-control"
                   placeholder="Appartement, étage..."
                   value="<?= htmlspecialchars($adresse_edit['complement_adresse'] ?? $_POST['complement'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row split-3">
          <div class="form-group">
            <label>Code postal *</label>
            <input type="text" name="code_postal" class="form-control" required
                   value="<?= htmlspecialchars($adresse_edit['code_postal'] ?? $_POST['code_postal'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Ville *</label>
            <input type="text" name="ville" class="form-control" required
                   value="<?= htmlspecialchars($adresse_edit['ville'] ?? $_POST['ville'] ?? '') ?>">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Pays</label>
            <input type="text" name="pays" class="form-control"
                   value="<?= htmlspecialchars($adresse_edit['pays'] ?? $_POST['pays'] ?? 'France') ?>">
          </div>
          <div class="form-group">
            <label>Téléphone</label>
            <input type="tel" name="telephone" class="form-control" placeholder="+33 6 00 00 00 00"
                   value="<?= htmlspecialchars($adresse_edit['telephone'] ?? $_POST['telephone'] ?? '') ?>">
          </div>
        </div>

        <div class="form-check">
          <input type="checkbox" name="principale" id="principale"
                 <?= !empty($adresse_edit['est_principale']) || isset($_POST['principale']) ? 'checked' : '' ?>>
          <label for="principale">Définir comme adresse principale</label>
        </div>

        <div style="display:flex;gap:10px;align-items:center;">
          <button type="submit" class="btn-submit" style="flex:1;">
            <span><i class="fas fa-save" style="margin-right:8px;"></i>
              <?= $adresse_edit ? 'Enregistrer les modifications' : 'Ajouter cette adresse' ?>
            </span>
          </button>
          <?php if ($adresse_edit): ?>
            <a href="ajouter_adresse.php" class="btn-annuler-edit"><i class="fas fa-times"></i> Annuler</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <a href="<?= SITE_URL ?>pages/compte.php" class="btn-retour">
      <i class="fas fa-arrow-left"></i> Retour à mon compte
    </a>
  </div>

  <!-- LISTE DES ADRESSES -->
  <div class="adresses-section">
    <h2>
      Mes adresses enregistrées
      <span class="count"><?= count($mes_adresses) ?></span>
    </h2>

    <?php if (empty($mes_adresses)): ?>
      <div class="no-adresse">
        <i class="fas fa-map-marker-alt"></i>
        Vous n'avez pas encore d'adresse enregistrée.<br>
        Ajoutez-en une avec le formulaire ci-contre.
      </div>
    <?php else: ?>
      <?php foreach ($mes_adresses as $addr): ?>
        <div class="adresse-card <?= $addr['est_principale'] ? 'principale' : '' ?>">

          <?php if ($addr['est_principale']): ?>
            <span class="badge-princ"><i class="fas fa-star" style="margin-right:4px;"></i>Adresse principale</span>
          <?php endif; ?>

          <div class="adresse-nom">
            <?= htmlspecialchars(trim($addr['prenom'] . ' ' . $addr['nom'])) ?>
          </div>
          <div class="adresse-ligne">
            <?= htmlspecialchars($addr['adresse']) ?>
            <?php if (!empty($addr['complement_adresse'])): ?><br><?= htmlspecialchars($addr['complement_adresse']) ?><?php endif; ?>
            <br><?= htmlspecialchars($addr['code_postal'] . ' ' . $addr['ville']) ?>
            <br><?= htmlspecialchars($addr['pays']) ?>
          </div>
          <?php if (!empty($addr['telephone'])): ?>
            <div class="adresse-tel"><i class="fas fa-phone"></i><?= htmlspecialchars($addr['telephone']) ?></div>
          <?php endif; ?>

          <div class="adresse-actions">
            <a href="ajouter_adresse.php?modifier=<?= $addr['id_adresse'] ?>" class="btn-modifier-addr">
              <i class="fas fa-pencil-alt"></i> Modifier
            </a>
            <button class="btn-supprimer-addr" onclick="ouvrirConfirmation(<?= $addr['id_adresse'] ?>)">
              <i class="fas fa-trash-alt"></i> Supprimer
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<!-- MODAL CONFIRMATION -->
<div class="confirm-delete" id="confirmDelete">
  <div class="confirm-box">
    <h3>Supprimer cette adresse ?</h3>
    <p>Cette action est irréversible. Voulez-vous vraiment supprimer cette adresse ?</p>
    <div class="confirm-btns">
      <a href="#" id="btnOuiSupprimer" class="btn-confirm-yes"><i class="fas fa-trash-alt"></i> Oui, supprimer</a>
      <button class="btn-confirm-no" onclick="fermerConfirmation()">Annuler</button>
    </div>
  </div>
</div>

<script>
const cur=document.getElementById('cursor'),ring=document.getElementById('cursor-ring');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cur.style.left=mx+'px';cur.style.top=my+'px';});
(function r(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;ring.style.left=rx+'px';ring.style.top=ry+'px';requestAnimationFrame(r);})();
document.querySelectorAll('a,button,input,.adresse-card').forEach(el=>{
  el.addEventListener('mouseenter',()=>document.body.classList.add('hovering'));
  el.addEventListener('mouseleave',()=>document.body.classList.remove('hovering'));
});

function ouvrirConfirmation(id) {
  document.getElementById('btnOuiSupprimer').href = 'ajouter_adresse.php?supprimer=' + id;
  document.getElementById('confirmDelete').classList.add('show');
}
function fermerConfirmation() {
  document.getElementById('confirmDelete').classList.remove('show');
}
document.getElementById('confirmDelete').addEventListener('click', function(e){
  if(e.target===this) fermerConfirmation();
});

<?php if($adresse_edit): ?>
  document.querySelector('.form-card').scrollIntoView({behavior:'smooth',block:'start'});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>