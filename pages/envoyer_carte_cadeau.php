<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadeaux.php');
    exit();
}

$prenom         = htmlspecialchars(trim($_POST['prenom'] ?? ''));
$email          = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$montant        = !empty($_POST['montant_custom']) ? intval($_POST['montant_custom']) : intval($_POST['montant'] ?? 0);
$message_client = htmlspecialchars(trim($_POST['message'] ?? ''));

if (empty($prenom) || !filter_var($email, FILTER_VALIDATE_EMAIL) || $montant < 50) {
    header('Location: cadeaux.php?carte_error=1#montants');
    exit();
}

$succes_bdd   = false;
$succes_email = false;

// 1. SAUVEGARDE EN BDD
try {
    $stmt = $pdo->prepare("
        INSERT INTO cartes_cadeaux (prenom, email, montant, message)
        VALUES (:prenom, :email, :montant, :message)
    ");
    $stmt->execute([
        ':prenom'  => $prenom,
        ':email'   => $email,
        ':montant' => $montant,
        ':message' => $message_client
    ]);
    $succes_bdd = true;
} catch (Exception $e) {
    $succes_bdd = false;
}

// 2. EMAIL AU MAGASIN
$to_magasin  = 'contact@eclatdor.fr';
$subject_mag = 'Nouvelle demande de carte cadeau - ' . $montant . ' EUR';
$body_mag    = "Bonjour,\n\nNouvelle demande de carte cadeau :\n\nPrenom : $prenom\nEmail : $email\nMontant : $montant EUR\nMessage : " . ($message_client ?: 'Aucun') . "\nDate : " . date('d/m/Y a H:i') . "\n\nMerci de contacter le client.\n\n-- Eclat d'Or";
$headers_mag  = "From: noreply@eclatdor.fr\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8\r\n";

// 3. EMAIL CLIENT
$subject_client = "Votre demande de carte cadeau Eclat d'Or";
$body_client    = "Bonjour $prenom,\n\nNous avons bien recu votre demande de carte cadeau de $montant EUR.\nNotre equipe vous contactera sous 24h a l'adresse $email.\n\nMerci,\nL'equipe Eclat d'Or\ncontact@eclatdor.fr | 01 23 45 67 89";
$headers_client = "From: contact@eclatdor.fr\r\nContent-Type: text/plain; charset=UTF-8\r\n";

$succes_email = mail($to_magasin, $subject_mag, $body_mag, $headers_mag);
mail($email, $subject_client, $body_client, $headers_client);

// REDIRECTION - succes si BDD OK (email peut echouer en local)
if ($succes_bdd || $succes_email) {
    header('Location: cadeaux.php?carte_success=1#montants');
} else {
    header('Location: cadeaux.php?carte_error=1#montants');
}
exit();