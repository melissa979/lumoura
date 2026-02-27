<?php
// ═══════════════════════════════════════════════════════════
//  pages/connexion.php — Connexion avec 2FA OTP
// ═══════════════════════════════════════════════════════════
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/otp.php';

if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$etape  = $_SESSION['otp_connexion_etape'] ?? 'formulaire';
$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // ════════════════════════════════════════
    //  ÉTAPE 1 — Vérification email + mot de passe
    // ════════════════════════════════════════
    if ($_POST['action'] === 'connexion') {
        $email = trim($_POST['email'] ?? '');
        $mdp   = $_POST['mdp'] ?? '';

        if (!$email || !$mdp) {
            $erreur = 'Veuillez remplir tous les champs.';
        } else {
            // ✅ CORRECTION : ajout de "role" dans le SELECT
            $stmt = $pdo->prepare("SELECT id_utilisateur, nom, prenom, mot_de_passe, role FROM utilisateurs WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
                $erreur = 'Email ou mot de passe incorrect.';
            } else {
                $_SESSION['otp_connexion_user']  = $user;
                $_SESSION['otp_connexion_email']  = $email;
                $_SESSION['otp_connexion_etape']  = 'verification';
                $_SESSION['otp_sent_at_co']       = time();
                $etape = 'verification';

                $code = otp_generer($pdo, $email, 'connexion');
                if (otp_envoyer($email, $code, 'connexion')) {
                    $succes = "Un code de vérification a été envoyé à <strong>{$email}</strong>.";
                } else {
                    $erreur = "Impossible d'envoyer l'email. Réessayez.";
                    $etape  = 'formulaire';
                    unset($_SESSION['otp_connexion_user'], $_SESSION['otp_connexion_email'],
                          $_SESSION['otp_connexion_etape'], $_SESSION['otp_sent_at_co']);
                }
            }
        }
    }

    // ════════════════════════════════════════
    //  ÉTAPE 2 — Vérification du code OTP
    // ════════════════════════════════════════
    elseif ($_POST['action'] === 'verifier_otp') {
        $email = $_SESSION['otp_connexion_email'] ?? '';
        $user  = $_SESSION['otp_connexion_user']  ?? null;
        $code  = trim(implode('', $_POST['digit'] ?? []));
        $etape = 'verification';

        if (!$email || !$user) {
            $erreur = 'Session expirée. Reconnectez-vous.';
            $etape  = 'formulaire';
            unset($_SESSION['otp_connexion_etape'], $_SESSION['otp_connexion_email'],
                  $_SESSION['otp_connexion_user'], $_SESSION['otp_sent_at_co']);
        } else {
            $res = otp_verifier($pdo, $email, $code, 'connexion');
            if ($res['ok']) {
                // ✅ CORRECTION : ajout de $_SESSION['role']
                $_SESSION['user_id']    = $user['id_utilisateur'];
                $_SESSION['user_nom']   = ($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '');
                $_SESSION['user_email'] = $email;
                $_SESSION['role']       = $user['role'];

                unset($_SESSION['otp_connexion_etape'], $_SESSION['otp_connexion_email'],
                      $_SESSION['otp_connexion_user'], $_SESSION['otp_sent_at_co']);

                // ✅ CORRECTION : redirection admin automatique si role = admin
                if ($user['role'] === 'admin') {
                    header('Location: ../admin/index.php');
                    exit;
                }

                $redirect = $_SESSION['redirect_apres_connexion'] ?? '../index.php';
                unset($_SESSION['redirect_apres_connexion']);
                header('Location: ' . $redirect);
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
        $email = $_SESSION['otp_connexion_email'] ?? '';
        $etape = 'verification';

        $dernierEnvoi = $_SESSION['otp_sent_at_co'] ?? 0;
        if (time() - $dernierEnvoi < 30) {
            $erreur = 'Attendez ' . (30 - (time() - $dernierEnvoi)) . ' secondes avant de renvoyer.';
        } elseif ($email) {
            $code = otp_generer($pdo, $email, 'connexion');
            if (otp_envoyer($email, $code, 'connexion')) {
                $_SESSION['otp_sent_at_co'] = time();
                $succes = 'Un nouveau code a été envoyé.';
            } else {
                $erreur = "Échec de l'envoi. Réessayez.";
            }
        }
    }

    elseif ($_POST['action'] === 'reinitialiser') {
        unset($_SESSION['otp_connexion_etape'], $_SESSION['otp_connexion_email'],
              $_SESSION['otp_connexion_user'], $_SESSION['otp_sent_at_co']);
        header('Location: connexion.php');
        exit;
    }
}

$pageTitle = 'Connexion — Lumoura';
include '../includes/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Italiana&family=Nunito:wght@300;400&display=swap" rel="stylesheet">

<style>
:root{--cream:#F7F2EA;--cream2:#EDE5D8;--parch:#D9CEBC;--warm:#C4A882;--terra:#8B6A4A;--deep:#3D2B1F;--blush:#D4A99A;--stone:#9E9488;--ink:#2C2118;--red:#C0392B;--green:#5A8A6A}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Nunito',sans-serif;font-weight:300;background:var(--cream);color:var(--ink);min-height:100vh;display:flex;flex-direction:column}

.auth-wrap{flex:1;display:flex;align-items:center;justify-content:center;padding:80px 20px;position:relative;overflow:hidden}
.auth-wrap::before,.auth-wrap::after{content:'';position:absolute;border-radius:50%;filter:blur(80px);opacity:.15;pointer-events:none}
.auth-wrap::before{width:500px;height:500px;background:var(--blush);top:-100px;right:-100px}
.auth-wrap::after{width:400px;height:400px;background:var(--warm);bottom:-80px;left:-80px}

.auth-card{width:100%;max-width:460px;background:#fff;border-radius:4px;box-shadow:0 20px 60px rgba(61,43,31,.1);overflow:hidden;position:relative;z-index:2}

.auth-header{background:var(--deep);padding:36px 48px;text-align:center}
.auth-header-sub{font-size:10px;letter-spacing:5px;text-transform:uppercase;color:var(--warm);margin-bottom:6px}
.auth-header h1{font-family:'Italiana',serif;font-size:2rem;color:var(--cream);letter-spacing:2px}

.auth-body{padding:40px 48px 48px}

.msg{padding:12px 16px;border-radius:3px;font-size:.78rem;line-height:1.6;margin-bottom:24px}
.msg.err{background:rgba(192,57,43,.08);border-left:3px solid var(--red);color:var(--red)}
.msg.ok{background:rgba(90,138,106,.08);border-left:3px solid var(--green);color:var(--green)}

.field{margin-bottom:20px}
.field label{display:block;font-size:.6rem;letter-spacing:3px;text-transform:uppercase;color:var(--stone);margin-bottom:8px}
.field input{width:100%;padding:13px 16px;border:1.5px solid var(--parch);background:var(--cream);color:var(--ink);font-family:'Nunito',sans-serif;font-size:.88rem;border-radius:2px;outline:none;transition:border-color .3s,box-shadow .3s}
.field input:focus{border-color:var(--warm);box-shadow:0 0 0 3px rgba(196,168,130,.15)}

.btn-submit{width:100%;padding:15px;background:var(--deep);border:none;color:var(--cream);font-family:'Nunito',sans-serif;font-size:.65rem;letter-spacing:4px;text-transform:uppercase;font-weight:400;cursor:pointer;border-radius:100px;position:relative;overflow:hidden;transition:transform .3s,box-shadow .3s;margin-top:8px}
.btn-submit::before{content:'';position:absolute;inset:0;border-radius:100px;background:linear-gradient(135deg,var(--terra),var(--blush));opacity:0;transition:opacity .4s}
.btn-submit:hover::before{opacity:1}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 14px 40px rgba(61,43,31,.2)}
.btn-submit span{position:relative;z-index:1}

.auth-footer{text-align:center;margin-top:28px;font-size:.78rem;color:var(--stone)}
.auth-footer a{color:var(--terra);text-decoration:none;border-bottom:1px solid transparent;transition:border-color .3s}
.auth-footer a:hover{border-color:var(--terra)}

.otp-email{text-align:center;font-size:.82rem;color:var(--stone);line-height:1.7;margin-bottom:32px}
.otp-email strong{color:var(--terra)}
.otp-inputs{display:flex;justify-content:center;gap:10px;margin-bottom:32px}
.otp-inputs input{width:52px;height:62px;border:1.5px solid var(--parch);background:var(--cream);text-align:center;font-size:1.6rem;font-weight:400;color:var(--deep);font-family:'Italiana',serif;border-radius:4px;outline:none;caret-color:var(--warm);transition:border-color .3s,box-shadow .3s,transform .2s}
.otp-inputs input:focus{border-color:var(--warm);box-shadow:0 0 0 3px rgba(196,168,130,.15);transform:scale(1.05)}
.otp-inputs input.filled{border-color:var(--terra);background:#fff}
.otp-timer{text-align:center;font-size:.72rem;color:var(--stone);margin-bottom:20px;letter-spacing:1px}
.otp-timer #timerVal{color:var(--terra)}
.otp-timer.expired #timerVal{color:var(--red)}
.btn-resend{display:block;margin:0 auto 24px;background:none;border:1.5px solid var(--parch);color:var(--stone);font-family:'Nunito',sans-serif;font-size:.6rem;letter-spacing:3px;text-transform:uppercase;padding:10px 24px;border-radius:100px;cursor:pointer;transition:all .3s}
.btn-resend:not(:disabled):hover{border-color:var(--warm);color:var(--terra)}
.btn-resend:disabled{opacity:.35;cursor:not-allowed}
.btn-back{display:block;text-align:center;margin-top:16px;font-size:.68rem;color:var(--stone);cursor:pointer;background:none;border:none;transition:color .3s}
.btn-back:hover{color:var(--ink)}

@media(max-width:560px){
  .auth-body{padding:28px 24px 36px}
  .auth-header{padding:28px 24px}
  .otp-inputs input{width:42px;height:52px;font-size:1.3rem}
}
</style>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-header-sub">Maison de Haute Joaillerie</div>
      <h1>LUMOURA</h1>
    </div>
    <div class="auth-body">

      <?php if ($erreur): ?><div class="msg err"><?= $erreur ?></div><?php endif; ?>
      <?php if ($succes): ?><div class="msg ok"><?= $succes ?></div><?php endif; ?>

      <?php if ($etape === 'formulaire'): ?>

        <form method="POST" novalidate>
          <input type="hidden" name="action" value="connexion">
          <div class="field">
            <label>Adresse email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="marie@exemple.com" required autofocus>
          </div>
          <div class="field">
            <label>Mot de passe</label>
            <input type="password" name="mdp" placeholder="Votre mot de passe" required>
          </div>
          <button type="submit" class="btn-submit"><span>Continuer →</span></button>
        </form>

        <div class="auth-footer">
          Pas encore de compte ? <a href="inscription.php">S'inscrire</a>
        </div>

      <?php else: ?>
        <?php $email = htmlspecialchars($_SESSION['otp_connexion_email'] ?? ''); ?>

        <p class="otp-email">
          Pour sécuriser votre connexion, saisissez le code envoyé à<br>
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

          <div class="otp-timer" id="otpTimer">
            Code valable encore <span id="timerVal">1:00</span>
          </div>

          <button type="submit" class="btn-submit" id="btnVerif"><span>Vérifier →</span></button>
        </form>

        <form method="POST" style="margin-top:20px">
          <input type="hidden" name="action" value="renvoyer">
          <button type="submit" class="btn-resend" id="btnResend" disabled>Renvoyer un code</button>
        </form>

        <form method="POST">
          <input type="hidden" name="action" value="reinitialiser">
          <button type="submit" class="btn-back">← Revenir à la connexion</button>
        </form>

        <script>
        const digits = document.querySelectorAll('.otp-digit');
        digits[0].focus();
        digits.forEach((inp, i) => {
          inp.addEventListener('input', e => {
            inp.value = inp.value.replace(/\D/g,'').slice(-1);
            if (inp.value) { inp.classList.add('filled'); if(i<5) digits[i+1].focus(); }
            else inp.classList.remove('filled');
            if ([...digits].every(d=>d.value)) setTimeout(()=>document.getElementById('otpForm').submit(),300);
          });
          inp.addEventListener('keydown', e => {
            if(e.key==='Backspace'&&!inp.value&&i>0){digits[i-1].focus();digits[i-1].value='';digits[i-1].classList.remove('filled')}
          });
          inp.addEventListener('paste', e => {
            e.preventDefault();
            const p=(e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
            [...p].forEach((c,j)=>{if(digits[j]){digits[j].value=c;digits[j].classList.add('filled')}});
            if(p.length===6) setTimeout(()=>document.getElementById('otpForm').submit(),300);
          });
        });

        const sentAt=<?= (int)($_SESSION['otp_sent_at_co'] ?? time()) ?>;
        const expiry=sentAt+60;
        const timerEl=document.getElementById('timerVal');
        const timerWrap=document.getElementById('otpTimer');
        const btnResend=document.getElementById('btnResend');
        function tick(){
          const left=Math.max(0,expiry-Math.floor(Date.now()/1000));
          const m=Math.floor(left/60),s=left%60;
          timerEl.textContent=m+':'+String(s).padStart(2,'0');
          if(left<=0){timerWrap.classList.add('expired');timerEl.textContent='Expiré';btnResend.disabled=false}
          else setTimeout(tick,1000);
        }
        tick();
        const resendDelay=Math.max(0,sentAt+30-Math.floor(Date.now()/1000));
        setTimeout(()=>{btnResend.disabled=false},resendDelay*1000);
        </script>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>