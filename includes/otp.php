<?php
// ═══════════════════════════════════════════════════════════
//  includes/otp.php — Gestion des codes OTP
//  Dépendance : PHPMailer (via Composer ou manuel)
// ═══════════════════════════════════════════════════════════

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ── Chargement de PHPMailer ──────────────────────────────
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';
}

// ── Configuration SMTP ───────────────────────────────────
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'salhimelissa2007@gmail.com');
define('SMTP_PASS',     'dwdo jgfs osqp nwll');
define('SMTP_FROM',     'salhimelissa2007@gmail.com');
define('SMTP_FROM_NAME','Lumoura Joaillerie');
define('OTP_EXPIRY',    1);   // durée en minutes

// ════════════════════════════════════════════════════════
//  Générer et enregistrer un code OTP
// ════════════════════════════════════════════════════════
function otp_generer(PDO $pdo, string $email, string $type): string
{
    $stmt = $pdo->prepare("
        UPDATE otp_codes SET used = 1
        WHERE email = :email AND type = :type AND used = 0
    ");
    $stmt->execute([':email' => $email, ':type' => $type]);

    $code    = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', time() + OTP_EXPIRY * 60);

    $stmt = $pdo->prepare("
        INSERT INTO otp_codes (email, code, type, expires_at)
        VALUES (:email, :code, :type, :expires)
    ");
    $stmt->execute([
        ':email'   => $email,
        ':code'    => $code,
        ':type'    => $type,
        ':expires' => $expires,
    ]);

    return $code;
}

// ════════════════════════════════════════════════════════
//  Envoyer le code par email
// ════════════════════════════════════════════════════════
function otp_envoyer(string $email, string $code, string $type): bool
{
    $sujet = $type === 'inscription'
        ? 'Confirmez votre inscription — Lumoura'
        : 'Votre code de connexion — Lumoura';

    $contexte = $type === 'inscription'
        ? 'Bienvenue chez <strong>Lumoura Joaillerie</strong>. Pour finaliser votre inscription, veuillez saisir le code ci-dessous.'
        : 'Une tentative de connexion a été détectée sur votre compte <strong>Lumoura</strong>. Saisissez ce code pour continuer.';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$sujet}</title>
</head>
<body style="margin:0;padding:0;background:#F7F2EA;font-family:'Georgia',serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F7F2EA;padding:40px 20px;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:4px;overflow:hidden;box-shadow:0 4px 24px rgba(61,43,31,.08);">

        <!-- Header -->
        <tr>
          <td style="background:#3D2B1F;padding:36px 48px;text-align:center;">
            <div style="font-size:11px;letter-spacing:6px;text-transform:uppercase;color:#C4A882;margin-bottom:6px;">Maison de Haute Joaillerie</div>
            <div style="font-family:'Georgia',serif;font-size:28px;color:#F7F2EA;letter-spacing:2px;">LUMOURA</div>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:48px 48px 32px;">
            <p style="font-size:14px;color:#8B6A4A;line-height:1.7;margin-bottom:32px;">{$contexte}</p>

            <!-- Code OTP -->
            <div style="text-align:center;margin:32px 0;">
              <div style="display:inline-block;background:#F7F2EA;border:1px solid #D9CEBC;border-radius:4px;padding:24px 48px;">
                <div style="font-size:11px;letter-spacing:4px;text-transform:uppercase;color:#9E9488;margin-bottom:10px;">Votre code</div>
                <div style="font-size:42px;letter-spacing:12px;color:#3D2B1F;font-weight:bold;font-family:'Courier New',monospace;">{$code}</div>
              </div>
            </div>

            <p style="font-size:12px;color:#9E9488;text-align:center;line-height:1.6;margin-top:24px;">
              Ce code est valable <strong>1 minute</strong>.<br>
              Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.
            </p>
          </td>
        </tr>

        <!-- Separator -->
        <tr>
          <td style="padding:0 48px;">
            <div style="height:1px;background:linear-gradient(90deg,transparent,#D9CEBC,transparent);"></div>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="padding:28px 48px;text-align:center;">
            <p style="font-size:11px;color:#C4A882;letter-spacing:2px;text-transform:uppercase;margin-bottom:6px;">Lumoura Joaillerie — Depuis 1920</p>
            <p style="font-size:11px;color:#C4BBAA;">Ne répondez pas à cet email, il est envoyé automatiquement.</p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>

</body>
</html>
HTML;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'salhimelissa2007@gmail.com';
        $mail->Password   = 'dwdo jgfs osqp nwll';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Fix SSL local XAMPP
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];

        // ✅ Headers anti-spam
        $mail->addCustomHeader('X-Priority',        '3');
        $mail->addCustomHeader('Importance',         'Normal');
        $mail->addCustomHeader('X-MSMail-Priority',  'Normal');
        $mail->addCustomHeader('X-Mailer',           'PHPMailer');

        $mail->setFrom('salhimelissa2007@gmail.com', 'Lumoura Joaillerie');
        $mail->addReplyTo('salhimelissa2007@gmail.com', 'Lumoura Joaillerie');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body    = $html;
        $mail->AltBody = "Votre code Lumoura : {$code} (valable 1 minute)";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP mail error: " . $e->getMessage());
        return false;
    }
}

// ════════════════════════════════════════════════════════
//  Vérifier un code OTP soumis par l'utilisateur
// ════════════════════════════════════════════════════════
function otp_verifier(PDO $pdo, string $email, string $code, string $type): array
{
    $stmt = $pdo->prepare("
        SELECT id, expires_at
        FROM otp_codes
        WHERE email    = :email
          AND type     = :type
          AND code     = :code
          AND used     = 0
          AND expires_at > NOW()
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':email' => $email,
        ':type'  => $type,
        ':code'  => $code,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $stmtExp = $pdo->prepare("
            SELECT id FROM otp_codes
            WHERE email = :email AND type = :type AND code = :code AND used = 0
            LIMIT 1
        ");
        $stmtExp->execute([':email'=>$email,':type'=>$type,':code'=>$code]);
        if ($stmtExp->fetch()) {
            return ['ok' => false, 'message' => 'Ce code a expiré. Veuillez en demander un nouveau.'];
        }
        return ['ok' => false, 'message' => 'Code incorrect. Vérifiez et réessayez.'];
    }

    $pdo->prepare("UPDATE otp_codes SET used = 1 WHERE id = :id")
        ->execute([':id' => $row['id']]);

    return ['ok' => true, 'message' => 'Code vérifié avec succès.'];
}

// ════════════════════════════════════════════════════════
//  Nettoyer les vieux codes
// ════════════════════════════════════════════════════════
function otp_nettoyer(PDO $pdo): void
{
    $pdo->exec("DELETE FROM otp_codes WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
}