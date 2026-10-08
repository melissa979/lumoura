<?php
/**
 * Protection commune de toutes les pages admin.
 * Fichier : pages/admin/auth_admin.php
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '/lumoura';
$loginUrl = $siteUrl . '/pages/connexion.php';
$homeUrl = $siteUrl . '/index.php';

$userId = filter_var($_SESSION['user_id'] ?? null, FILTER_VALIDATE_INT);

if (!$userId || $userId <= 0) {
    header('Location: ' . $loginUrl);
    exit;
}

try {
    $stmt = $pdo->prepare(''
        . 'SELECT id_utilisateur, email, prenom, nom, role, statut '
        . 'FROM utilisateurs '
        . 'WHERE id_utilisateur = ? '
        . 'LIMIT 1'
    );
    $stmt->execute([$userId]);
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $adminUser = false;
}

if (!$adminUser || ($adminUser['role'] ?? '') !== 'admin' || ($adminUser['statut'] ?? 'actif') !== 'actif') {
    unset($_SESSION['user_id']);
    header('Location: ' . $homeUrl);
    exit;
}

// Variables disponibles dans toutes les pages admin :
// $adminUser, $siteUrl, $loginUrl et $homeUrl.
