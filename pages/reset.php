<?php
// ══════════════════════════════════════════
//  LUMOURA — Reset mot de passe / Créer admin
//  reset.php  (à la racine du projet)
//  ⚠️  SUPPRIMER CE FICHIER après utilisation !
// ══════════════════════════════════════════

// Connexion DB directe (sans passer par les includes)
$host = 'localhost';
$db   = 'lumoura_db';
$user = 'root';
$pass = '';  // ← Modifiez si vous avez un mot de passe MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_ok = true;
} catch(Exception $e) {
    $db_ok = false;
    $db_error = $e->getMessage();
}

$msg = '';
$msg_type = '';

// ── CHANGER MOT DE PASSE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_pwd'])) {
    $email       = trim($_POST['email'] ?? '');
    $new_pwd     = $_POST['new_pwd'] ?? '';
    $confirm_pwd = $_POST['confirm_pwd'] ?? '';

    if (empty($email) || empty($new_pwd)) {
        $msg = 'Email et mot de passe requis.'; $msg_type = 'error';
    } elseif ($new_pwd !== $confirm_pwd) {
        $msg = 'Les mots de passe ne correspondent pas.'; $msg_type = 'error';
    } elseif (strlen($new_pwd) < 6) {
        $msg = 'Le mot de passe doit contenir au moins 6 caractères.'; $msg_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user_found = $stmt->fetch();
            if (!$user_found) {
                $msg = "Aucun utilisateur trouvé avec l'email : $email"; $msg_type = 'error';
            } else {
                $hash = password_hash($new_pwd, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE email = ?")
                    ->execute([$hash, $email]);
                $msg = "✦ Mot de passe mis à jour pour $email"; $msg_type = 'ok';
            }
        } catch(Exception $e) {
            $msg = 'Erreur : ' . $e->getMessage(); $msg_type = 'error';
        }
    }
}

// ── CRÉER COMPTE ADMIN ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_admin'])) {
    $prenom  = trim($_POST['prenom'] ?? '');
    $nom     = trim($_POST['nom'] ?? '');
    $email   = trim($_POST['email_admin'] ?? '');
    $pwd     = $_POST['pwd_admin'] ?? '';
    $confirm = $_POST['confirm_admin'] ?? '';

    if (empty($email) || empty($pwd) || empty($prenom)) {
        $msg = 'Prénom, email et mot de passe requis.'; $msg_type = 'error';
    } elseif ($pwd !== $confirm) {
        $msg = 'Les mots de passe ne correspondent pas.'; $msg_type = 'error';
    } elseif (strlen($pwd) < 6) {
        $msg = 'Mot de passe trop court (min. 6 caractères).'; $msg_type = 'error';
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                // Mettre à jour le rôle si l'utilisateur existe
                $pdo->prepare("UPDATE utilisateurs SET role = 'admin', statut = 'actif' WHERE email = ?")
                    ->execute([$email]);
                $msg = "✦ Compte $email promu administrateur !"; $msg_type = 'ok';
            } else {
                // Créer un nouveau compte admin
                $hash = password_hash($pwd, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO utilisateurs (email, mot_de_passe, prenom, nom, role, statut, date_inscription) VALUES (?,?,?,?,'admin','actif',NOW())")
                    ->execute([$email, $hash, $prenom, $nom]);
                $msg = "✦ Compte admin créé : $email"; $msg_type = 'ok';
            }
        } catch(Exception $e) {
            $msg = 'Erreur : ' . $e->getMessage(); $msg_type = 'error';
        }
    }
}

// ── LISTE DES ADMINS ──
$admins = [];
if ($db_ok) {
    try {
        $admins = $pdo->query("SELECT id_utilisateur, email, prenom, nom, statut, date_inscription FROM utilisateurs WHERE role = 'admin' ORDER BY id_utilisateur")->fetchAll();
    } catch(Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset & Admin — Lumoura</title>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Cinzel:wght@400;600&family=Didact+Gothic&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--g1:#D4A843;--g2:#F5D78E;--g3:#B8882C;--ink:#0D0A06;--ink2:#1A140E;--smoke:#F8F5EF;--stone:#E8E0D0;--muted:#8A7D6A;--red:#C0392B;--green:#27AE60;}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Didact Gothic',sans-serif;background:var(--ink);color:rgba(255,255,255,.85);min-height:100vh;padding:40px 20px;}

.container{max-width:900px;margin:0 auto;}

/* Header */
.page-header{text-align:center;margin-bottom:40px;padding-bottom:30px;border-bottom:1px solid rgba(212,168,67,.15);}
.page-tag{font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:5px;text-transform:uppercase;color:var(--g1);margin-bottom:12px;}
.page-title{font-family:'EB Garamond',serif;font-size:2.5rem;color:#fff;font-weight:400;margin-bottom:8px;}
.warn-banner{
    display:flex;align-items:center;gap:10px;
    background:rgba(192,57,43,.15);border:1px solid rgba(192,57,43,.3);
    padding:12px 20px;margin-top:16px;font-size:.82rem;color:#e74c3c;
    justify-content:center;
}

/* DB status */
.db-status{
    display:flex;align-items:center;gap:8px;padding:10px 16px;
    margin-bottom:28px;font-size:.8rem;font-family:'Cinzel',serif;
    letter-spacing:1.5px;text-transform:uppercase;
}
.db-status.ok{background:rgba(39,174,96,.1);border:1px solid rgba(39,174,96,.2);color:#2ecc71;}
.db-status.err{background:rgba(192,57,43,.1);border:1px solid rgba(192,57,43,.2);color:#e74c3c;}

/* Message flash */
.flash{padding:14px 20px;margin-bottom:24px;display:flex;align-items:center;gap:10px;font-size:.85rem;}
.flash.ok{background:rgba(39,174,96,.1);border-left:3px solid var(--green);color:#2ecc71;}
.flash.error{background:rgba(192,57,43,.1);border-left:3px solid var(--red);color:#e74c3c;}

/* Grid */
.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;}

/* Panels */
.panel{background:#1A140E;padding:28px;}
.panel-head{
    font-family:'Cinzel',serif;font-size:.58rem;letter-spacing:4px;
    text-transform:uppercase;color:var(--g1);
    padding-bottom:14px;margin-bottom:22px;
    border-bottom:1px solid rgba(255,255,255,.05);
    display:flex;align-items:center;gap:8px;
}

.field{display:flex;flex-direction:column;gap:6px;margin-bottom:14px;}
.field label{font-family:'Cinzel',serif;font-size:.54rem;letter-spacing:2.5px;text-transform:uppercase;color:rgba(255,255,255,.35);}
.field input{
    background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);
    color:rgba(255,255,255,.85);padding:11px 14px;
    font-family:'Didact Gothic',sans-serif;font-size:.85rem;
    transition:border-color .25s;width:100%;
}
.field input:focus{outline:none;border-color:rgba(212,168,67,.45);}
.field input::placeholder{color:rgba(255,255,255,.15);}

.btn{
    width:100%;background:var(--g1);color:var(--ink);border:none;
    padding:13px;font-family:'Cinzel',serif;font-size:.62rem;
    letter-spacing:3px;text-transform:uppercase;cursor:pointer;
    transition:background .25s;display:flex;align-items:center;
    justify-content:center;gap:8px;margin-top:6px;
}
.btn:hover{background:var(--g2);}
.btn.danger{background:rgba(192,57,43,.2);color:var(--red);border:1px solid rgba(192,57,43,.3);}
.btn.danger:hover{background:var(--red);color:#fff;}

/* Admins table */
.panel-full{background:#1A140E;padding:28px;margin-bottom:24px;}
.admin-table{width:100%;border-collapse:collapse;margin-top:4px;}
.admin-table th{font-family:'Cinzel',serif;font-size:.52rem;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.25);padding:0 0 12px;text-align:left;border-bottom:1px solid rgba(255,255,255,.04);}
.admin-table td{padding:12px 0;border-bottom:1px solid rgba(255,255,255,.03);font-size:.82rem;color:rgba(255,255,255,.6);}
.admin-table tr:last-child td{border-bottom:none;}
.badge-admin{background:rgba(212,168,67,.12);color:var(--g1);font-family:'Cinzel',serif;font-size:.5rem;padding:2px 8px;letter-spacing:1.5px;}
.badge-actif{background:rgba(39,174,96,.1);color:#2ecc71;font-family:'Cinzel',serif;font-size:.5rem;padding:2px 8px;letter-spacing:1.5px;}

/* Footer */
.footer-note{
    text-align:center;padding:20px;
    font-family:'Cinzel',serif;font-size:.55rem;letter-spacing:3px;
    text-transform:uppercase;color:rgba(255,255,255,.15);
    border-top:1px solid rgba(255,255,255,.04);margin-top:12px;
}
.footer-note a{color:var(--g1);text-decoration:none;}

@media(max-width:700px){.grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<div class="container">

  <!-- Header -->
  <div class="page-header">
    <div class="page-tag">Lumoura Joaillerie</div>
    <h1 class="page-title">Gestion Accès Admin</h1>
    <div class="warn-banner">
      <i class="fas fa-exclamation-triangle"></i>
      Fichier sensible — <strong>Supprimez reset.php</strong> après utilisation !
    </div>
  </div>

  <!-- DB Status -->
  <?php if($db_ok): ?>
  <div class="db-status ok"><i class="fas fa-check-circle"></i> Connexion base de données OK — lumoura_db</div>
  <?php else: ?>
  <div class="db-status err"><i class="fas fa-times-circle"></i> Erreur DB : <?=htmlspecialchars($db_error??'')?></div>
  <p style="color:rgba(255,255,255,.4);font-size:.82rem;margin-bottom:20px;">Modifiez les variables <code style="color:var(--g1);">$host</code>, <code style="color:var(--g1);">$user</code>, <code style="color:var(--g1);">$pass</code> en haut du fichier.</p>
  <?php endif; ?>

  <!-- Flash message -->
  <?php if($msg): ?>
  <div class="flash <?=$msg_type?>">
    <i class="fas fa-<?=$msg_type==='ok'?'check-circle':'exclamation-circle'?>"></i>
    <?=htmlspecialchars($msg)?>
  </div>
  <?php endif; ?>

  <?php if($db_ok): ?>

  <!-- Admins existants -->
  <div class="panel-full">
    <div class="panel-head"><i class="fas fa-users-cog"></i> Comptes administrateurs actuels</div>
    <?php if($admins): ?>
    <table class="admin-table">
      <tr><th>#</th><th>Nom</th><th>Email</th><th>Statut</th><th>Inscrit le</th></tr>
      <?php foreach($admins as $a): ?>
      <tr>
        <td style="color:rgba(255,255,255,.25);"><?=$a['id_utilisateur']?></td>
        <td><?=htmlspecialchars($a['prenom'].' '.$a['nom'])?> <span class="badge-admin">ADMIN</span></td>
        <td style="font-family:monospace;font-size:.78rem;"><?=htmlspecialchars($a['email'])?></td>
        <td><span class="badge-actif"><?=htmlspecialchars($a['statut'])?></span></td>
        <td style="color:rgba(255,255,255,.25);font-size:.75rem;"><?=date('d/m/Y', strtotime($a['date_inscription']))?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
    <p style="color:rgba(255,255,255,.25);font-style:italic;">Aucun administrateur trouvé.</p>
    <?php endif; ?>
  </div>

  <div class="grid">

    <!-- Changer mot de passe -->
    <div class="panel">
      <div class="panel-head"><i class="fas fa-key"></i> Changer un mot de passe</div>
      <form method="POST">
        <div class="field">
          <label>Email du compte *</label>
          <input type="email" name="email" placeholder="admin@lumoura.fr" value="<?=htmlspecialchars($_POST['email']??'')?>" required>
        </div>
        <div class="field">
          <label>Nouveau mot de passe *</label>
          <input type="password" name="new_pwd" placeholder="Minimum 6 caractères" required>
        </div>
        <div class="field">
          <label>Confirmer le mot de passe *</label>
          <input type="password" name="confirm_pwd" placeholder="Répétez le mot de passe" required>
        </div>
        <button type="submit" name="action_pwd" class="btn">
          <i class="fas fa-lock"></i> Mettre à jour le mot de passe
        </button>
      </form>
    </div>

    <!-- Créer compte admin -->
    <div class="panel">
      <div class="panel-head"><i class="fas fa-user-shield"></i> Créer / Promouvoir admin</div>
      <p style="font-size:.78rem;color:rgba(255,255,255,.3);margin-bottom:16px;line-height:1.6;">
        Si l'email existe déjà, le compte sera promu admin.<br>Sinon, un nouveau compte admin sera créé.
      </p>
      <form method="POST">
        <div class="field">
          <label>Prénom *</label>
          <input type="text" name="prenom" placeholder="Ex: Admin" value="<?=htmlspecialchars($_POST['prenom']??'')?>" required>
        </div>
        <div class="field">
          <label>Nom</label>
          <input type="text" name="nom" placeholder="Ex: Système" value="<?=htmlspecialchars($_POST['nom']??'')?>">
        </div>
        <div class="field">
          <label>Email *</label>
          <input type="email" name="email_admin" placeholder="admin@lumoura.fr" value="<?=htmlspecialchars($_POST['email_admin']??'')?>" required>
        </div>
        <div class="field">
          <label>Mot de passe *</label>
          <input type="password" name="pwd_admin" placeholder="Minimum 6 caractères" required>
        </div>
        <div class="field">
          <label>Confirmer *</label>
          <input type="password" name="confirm_admin" placeholder="Répétez le mot de passe" required>
        </div>
        <button type="submit" name="action_admin" class="btn">
          <i class="fas fa-user-plus"></i> Créer / Promouvoir administrateur
        </button>
      </form>
    </div>

  </div>

  <!-- Accès rapide -->
  <div class="panel-full" style="text-align:center;">
    <div class="panel-head" style="justify-content:center;"><i class="fas fa-external-link-alt"></i> Accès rapide</div>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
      <a href="admin/index.php" style="display:inline-flex;align-items:center;gap:8px;background:rgba(212,168,67,.1);color:var(--g1);border:1px solid rgba(212,168,67,.2);padding:12px 24px;font-family:'Cinzel',serif;font-size:.6rem;letter-spacing:2px;text-transform:uppercase;text-decoration:none;transition:all .25s;">
        <i class="fas fa-tachometer-alt"></i> Panel Admin
      </a>
      <a href="pages/connexion.php" style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.04);color:rgba(255,255,255,.4);border:1px solid rgba(255,255,255,.08);padding:12px 24px;font-family:'Cinzel',serif;font-size:.6rem;letter-spacing:2px;text-transform:uppercase;text-decoration:none;transition:all .25s;">
        <i class="fas fa-sign-in-alt"></i> Page Connexion
      </a>
      <a href="index.php" style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.04);color:rgba(255,255,255,.4);border:1px solid rgba(255,255,255,.08);padding:12px 24px;font-family:'Cinzel',serif;font-size:.6rem;letter-spacing:2px;text-transform:uppercase;text-decoration:none;transition:all .25s;">
        <i class="fas fa-home"></i> Site principal
      </a>
    </div>
  </div>

  <?php endif; ?>

  <div class="footer-note">
    ⚠️ Supprimez ce fichier après utilisation : <a href="#">reset.php</a>
  </div>

</div>
</body>
</html>