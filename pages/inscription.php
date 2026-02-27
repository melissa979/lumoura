<?php
// ═══════════════════════════════════════════════════════════
//  pages/inscription.php — Inscription avec vérification OTP
// ═══════════════════════════════════════════════════════════
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/otp.php';

if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$etape   = $_SESSION['otp_inscription_etape'] ?? 'formulaire'; // 'formulaire' | 'verification'
$erreur  = '';
$succes  = '';

// ════════════════════════════════════════
//  ÉTAPE 1 — Soumission du formulaire
// ════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'inscrire') {
        $nom    = trim($_POST['nom']    ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email  = trim($_POST['email']  ?? '');
        $mdp    = $_POST['mdp']         ?? '';
        $mdp2   = $_POST['mdp2']        ?? '';

        // Validations basiques
        if (!$nom || !$prenom || !$email || !$mdp) {
            $erreur = 'Tous les champs sont obligatoires.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = 'Adresse email invalide.';
        } elseif (strlen($mdp) < 8) {
            $erreur = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($mdp !== $mdp2) {
            $erreur = 'Les mots de passe ne correspondent pas.';
        } else {
            // Vérifier si l'email est déjà pris
       $stmt = $pdo->prepare("SELECT email FROM utilisateurs WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $erreur = 'Cette adresse email est déjà utilisée.';
            } else {
                // Stocker les données temporairement en session
                $_SESSION['otp_inscription_data'] = [
                    'nom'    => $nom,
                    'prenom' => $prenom,
                    'email'  => $email,
                    'mdp'    => password_hash($mdp, PASSWORD_DEFAULT),
                ];

                // Générer et envoyer l'OTP
                $code = otp_generer($pdo, $email, 'inscription');
                if (otp_envoyer($email, $code, 'inscription')) {
                    $_SESSION['otp_inscription_etape'] = 'verification';
                    $_SESSION['otp_inscription_email'] = $email;
                    $_SESSION['otp_sent_at']           = time();
                    $etape  = 'verification';
                    $succes = "Un code à 6 chiffres a été envoyé à <strong>{$email}</strong>.";
                } else {
                    $erreur = "Impossible d'envoyer l'email. Vérifiez la configuration SMTP.";
                }
            }
        }
    }

    // ════════════════════════════════════════
    //  ÉTAPE 2 — Vérification du code OTP
    // ════════════════════════════════════════
    elseif ($_POST['action'] === 'verifier_otp') {
        $email   = $_SESSION['otp_inscription_email'] ?? '';
        $code    = trim(implode('', $_POST['digit'] ?? []));
        $data    = $_SESSION['otp_inscription_data']  ?? null;
        $etape   = 'verification';

        if (!$email || !$data) {
            $erreur = 'Session expirée. Recommencez l\'inscription.';
            $etape  = 'formulaire';
            unset($_SESSION['otp_inscription_etape'], $_SESSION['otp_inscription_email'], $_SESSION['otp_inscription_data']);
        } else {
            $res = otp_verifier($pdo, $email, $code, 'inscription');
            if ($res['ok']) {
                // Créer le compte
                $stmt = $pdo->prepare("
                    INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, email_verifie, date_inscription)
                    VALUES (:nom, :prenom, :email, :mdp, 1, NOW())
                ");
                $stmt->execute([
                    ':nom'    => $data['nom'],
                    ':prenom' => $data['prenom'],
                    ':email'  => $data['email'],
                    ':mdp'    => $data['mdp'],
                ]);

                // Connecter l'utilisateur directement
                $_SESSION['user_id']   = $pdo->lastInsertId();
                $_SESSION['user_nom']  = $data['prenom'] . ' ' . $data['nom'];
                $_SESSION['user_email']= $data['email'];

                // Nettoyer la session OTP
                unset($_SESSION['otp_inscription_etape'], $_SESSION['otp_inscription_email'],
                      $_SESSION['otp_inscription_data'], $_SESSION['otp_sent_at']);

                header('Location: ../index.php?bienvenue=1');
                exit;
            } else {
                $erreur = $res['message'];
            }
        }
    }

    // ════════════════════════════════════════
    //  Renvoyer un code
    // ════════════════════════════════════════
    elseif ($_POST['action'] === 'renvoyer') {
        $email = $_SESSION['otp_inscription_email'] ?? '';
        $etape = 'verification';

        // Anti-spam : 30 secondes minimum entre deux envois
        $dernierEnvoi = $_SESSION['otp_sent_at'] ?? 0;
        if (time() - $dernierEnvoi < 30) {
            $erreur = 'Attendez ' . (30 - (time() - $dernierEnvoi)) . ' secondes avant de renvoyer.';
        } elseif ($email) {
            $code = otp_generer($pdo, $email, 'inscription');
            if (otp_envoyer($email, $code, 'inscription')) {
                $_SESSION['otp_sent_at'] = time();
                $succes = 'Un nouveau code a été envoyé.';
            } else {
                $erreur = "Échec de l'envoi. Réessayez.";
            }
        }
    }
}

$pageTitle = 'Inscription — Lumoura';
include '../includes/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Italiana&family=Lora:ital@0;1&family=Nunito:wght@300;400&display=swap" rel="stylesheet">

<style>
:root{--cream:#F7F2EA;--cream2:#EDE5D8;--parch:#D9CEBC;--warm:#C4A882;--terra:#8B6A4A;--deep:#3D2B1F;--blush:#D4A99A;--stone:#9E9488;--ink:#2C2118;--red:#C0392B;--green:#5A8A6A}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Nunito',sans-serif;font-weight:300;background:var(--cream);color:var(--ink);min-height:100vh;display:flex;flex-direction:column}

.auth-wrap{
  flex:1;display:flex;align-items:center;justify-content:center;
  padding:80px 20px;position:relative;overflow:hidden;
}

/* Blobs décoratifs */
.auth-wrap::before,.auth-wrap::after{
  content:'';position:absolute;border-radius:50%;filter:blur(80px);opacity:.15;pointer-events:none;
}
.auth-wrap::before{width:500px;height:500px;background:var(--blush);top:-100px;right:-100px}
.auth-wrap::after{width:400px;height:400px;background:var(--warm);bottom:-80px;left:-80px}

.auth-card{
  width:100%;max-width:480px;
  background:#fff;
  border-radius:4px;
  box-shadow:0 20px 60px rgba(61,43,31,.1);
  overflow:hidden;position:relative;z-index:2;
}

/* Header de la carte */
.auth-header{
  background:var(--deep);padding:36px 48px;text-align:center;
}
.auth-header-sub{font-size:10px;letter-spacing:5px;text-transform:uppercase;color:var(--warm);margin-bottom:6px}
.auth-header h1{font-family:'Italiana',serif;font-size:2rem;color:var(--cream);letter-spacing:2px}

/* Corps */
.auth-body{padding:40px 48px 48px}

/* Messages */
.msg{
  padding:12px 16px;border-radius:3px;
  font-size:.78rem;line-height:1.6;margin-bottom:24px;
}
.msg.err{background:rgba(192,57,43,.08);border-left:3px solid var(--red);color:var(--red)}
.msg.ok {background:rgba(90,138,106,.08);border-left:3px solid var(--green);color:var(--green)}

/* Labels & inputs */
.field{margin-bottom:20px}
.field label{
  display:block;font-size:.6rem;letter-spacing:3px;text-transform:uppercase;
  color:var(--stone);margin-bottom:8px;
}
.field input{
  width:100%;padding:13px 16px;
  border:1.5px solid var(--parch);background:var(--cream);
  color:var(--ink);font-family:'Nunito',sans-serif;font-size:.88rem;
  border-radius:2px;outline:none;
  transition:border-color .3s,box-shadow .3s;
}
.field input:focus{border-color:var(--warm);box-shadow:0 0 0 3px rgba(196,168,130,.15)}

.field-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}

/* Bouton principal */
.btn-submit{
  width:100%;padding:15px;
  background:var(--deep);border:none;
  color:var(--cream);font-family:'Nunito',sans-serif;
  font-size:.65rem;letter-spacing:4px;text-transform:uppercase;
  font-weight:400;cursor:pointer;border-radius:100px;
  position:relative;overflow:hidden;transition:transform .3s,box-shadow .3s;
  margin-top:8px;
}
.btn-submit::before{
  content:'';position:absolute;inset:0;border-radius:100px;
  background:linear-gradient(135deg,var(--terra),var(--blush));
  opacity:0;transition:opacity .4s;
}
.btn-submit:hover::before{opacity:1}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 14px 40px rgba(61,43,31,.2)}
.btn-submit span{position:relative;z-index:1}

/* Lien connexion */
.auth-footer{text-align:center;margin-top:28px;font-size:.78rem;color:var(--stone)}
.auth-footer a{color:var(--terra);text-decoration:none;border-bottom:1px solid transparent;transition:border-color .3s}
.auth-footer a:hover{border-color:var(--terra)}

/* ─── ÉTAPE OTP ─── */
.otp-email{
  text-align:center;font-size:.82rem;color:var(--stone);
  line-height:1.7;margin-bottom:32px;
}
.otp-email strong{color:var(--terra)}

/* Champs à 6 cases */
.otp-inputs{
  display:flex;justify-content:center;gap:10px;
  margin-bottom:32px;
}
.otp-inputs input{
  width:52px;height:62px;
  border:1.5px solid var(--parch);
  background:var(--cream);
  text-align:center;
  font-size:1.6rem;font-weight:400;
  color:var(--deep);font-family:'Italiana',serif;
  border-radius:4px;outline:none;
  caret-color:var(--warm);
  transition:border-color .3s,box-shadow .3s,transform .2s;
}
.otp-inputs input:focus{
  border-color:var(--warm);
  box-shadow:0 0 0 3px rgba(196,168,130,.15);
  transform:scale(1.05);
}
.otp-inputs input.filled{border-color:var(--terra);background:#fff}

/* Timer */
.otp-timer{
  text-align:center;font-size:.72rem;
  color:var(--stone);margin-bottom:20px;letter-spacing:1px;
}
.otp-timer #timerVal{color:var(--terra);font-weight:400}
.otp-timer.expired #timerVal{color:var(--red)}

/* Renvoyer */
.btn-resend{
  display:block;margin:0 auto 24px;
  background:none;border:1.5px solid var(--parch);
  color:var(--stone);font-family:'Nunito',sans-serif;
  font-size:.6rem;letter-spacing:3px;text-transform:uppercase;
  padding:10px 24px;border-radius:100px;cursor:pointer;
  transition:all .3s;
}
.btn-resend:not(:disabled):hover{border-color:var(--warm);color:var(--terra)}
.btn-resend:disabled{opacity:.35;cursor:not-allowed}

/* Changer email */
.btn-back{
  display:block;text-align:center;margin-top:16px;
  font-size:.68rem;color:var(--stone);cursor:pointer;
  text-decoration:none;background:none;border:none;
  transition:color .3s;
}
.btn-back:hover{color:var(--ink)}

@media(max-width:560px){
  .auth-body{padding:28px 24px 36px}
  .auth-header{padding:28px 24px}
  .otp-inputs input{width:42px;height:52px;font-size:1.3rem}
  .field-row{grid-template-columns:1fr}
}
</style>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-header-sub">Maison de Haute Joaillerie</div>
      <h1>LUMOURA</h1>
    </div>

    <div class="auth-body">

      <?php if ($erreur): ?>
        <div class="msg err"><?= $erreur ?></div>
      <?php endif; ?>
      <?php if ($succes): ?>
        <div class="msg ok"><?= $succes ?></div>
      <?php endif; ?>

      <!-- ══ ÉTAPE 1 : Formulaire ══ -->
      <?php if ($etape === 'formulaire'): ?>

        <form method="POST" novalidate>
          <input type="hidden" name="action" value="inscrire">

          <div class="field-row">
            <div class="field">
              <label>Prénom</label>
              <input type="text" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" placeholder="Marie" required>
            </div>
            <div class="field">
              <label>Nom</label>
              <input type="text" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" placeholder="Dupont" required>
            </div>
          </div>

          <div class="field">
            <label>Adresse email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="marie@exemple.com" required>
          </div>

          <div class="field">
            <label>Mot de passe</label>
            <input type="password" name="mdp" placeholder="8 caractères minimum" required>
          </div>

          <div class="field">
            <label>Confirmer le mot de passe</label>
            <input type="password" name="mdp2" placeholder="Répétez votre mot de passe" required>
          </div>

          <button type="submit" class="btn-submit"><span>Créer mon compte →</span></button>
        </form>

        <div class="auth-footer">
          Déjà un compte ? <a href="connexion.php">Se connecter</a>
        </div>

      <!-- ══ ÉTAPE 2 : Vérification OTP ══ -->
      <?php else: ?>
        <?php $email = htmlspecialchars($_SESSION['otp_inscription_email'] ?? ''); ?>

        <p class="otp-email">
          Un code de vérification à 6 chiffres a été envoyé à<br>
          <strong><?= $email ?></strong>
        </p>

        <form method="POST" id="otpForm" novalidate>
          <input type="hidden" name="action" value="verifier_otp">

          <div class="otp-inputs">
            <?php for ($i = 0; $i < 6; $i++): ?>
              <input type="text" name="digit[]" class="otp-digit"
                     maxlength="1" inputmode="numeric" pattern="[0-9]"
                     autocomplete="off" required>
            <?php endfor; ?>
          </div>

          <!-- Timer 1 minute -->
          <div class="otp-timer" id="otpTimer">
            Code valable encore <span id="timerVal">1:00</span>
          </div>

          <button type="submit" class="btn-submit" id="btnVerif"><span>Vérifier le code →</span></button>
        </form>

        <form method="POST" style="margin-top:20px">
          <input type="hidden" name="action" value="renvoyer">
          <button type="submit" class="btn-resend" id="btnResend" disabled>
            Renvoyer un code
          </button>
        </form>

        <form method="POST">
          <input type="hidden" name="action" value="reinitialiser">
          <button type="submit" class="btn-back">← Changer d'adresse email</button>
        </form>

        <script>
        // ── Focus auto & navigation entre cases ──
        const digits = document.querySelectorAll('.otp-digit');
        digits[0].focus();

        digits.forEach((inp, i) => {
          inp.addEventListener('input', e => {
            // Accepter seulement les chiffres
            inp.value = inp.value.replace(/\D/g, '').slice(-1);
            if (inp.value) {
              inp.classList.add('filled');
              if (i < 5) digits[i + 1].focus();
              else document.getElementById('btnVerif').focus();
            } else {
              inp.classList.remove('filled');
            }
            // Auto-submit si les 6 cases sont remplies
            const all = [...digits].every(d => d.value !== '');
            if (all) setTimeout(() => document.getElementById('otpForm').submit(), 300);
          });

          inp.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !inp.value && i > 0) {
              digits[i - 1].focus();
              digits[i - 1].value = '';
              digits[i - 1].classList.remove('filled');
            }
          });

          // Coller un code complet
          inp.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            [...pasted].forEach((ch, j) => {
              if (digits[j]) { digits[j].value = ch; digits[j].classList.add('filled'); }
            });
            if (pasted.length === 6) setTimeout(() => document.getElementById('otpForm').submit(), 300);
          });
        });

        // ── Timer 60 secondes ──
        const sentAt   = <?= (int)($_SESSION['otp_sent_at'] ?? time()) ?>;
        const expiry   = sentAt + 60;
        const timerEl  = document.getElementById('timerVal');
        const timerWrap= document.getElementById('otpTimer');
        const btnResend= document.getElementById('btnResend');

        function tick() {
          const left = Math.max(0, expiry - Math.floor(Date.now() / 1000));
          const m = Math.floor(left / 60), s = left % 60;
          timerEl.textContent = m + ':' + String(s).padStart(2, '0');

          if (left <= 0) {
            timerWrap.classList.add('expired');
            timerEl.textContent = 'Expiré';
            // Activer le bouton renvoyer après expiration
            setTimeout(() => { btnResend.disabled = false; }, 0);
          } else {
            setTimeout(tick, 1000);
          }
        }
        tick();

        // Activer le bouton renvoyer après 30s (anti-spam)
        const resendDelay = Math.max(0, sentAt + 30 - Math.floor(Date.now() / 1000));
        setTimeout(() => { btnResend.disabled = false; }, resendDelay * 1000);
        </script>

      <?php endif; ?>

    </div>
  </div>
</div>

<?php
// Action pour revenir au formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reinitialiser') {
    unset($_SESSION['otp_inscription_etape'], $_SESSION['otp_inscription_email'],
          $_SESSION['otp_inscription_data'], $_SESSION['otp_sent_at']);
    header('Location: inscription.php');
    exit;
}
include '../includes/footer.php';
?>