<?php
// Protection admin - inclure en haut de chaque page admin
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /lumoura/pages/connexion.php');
    exit;
}

// Vérifier le rôle admin en BDD
require_once dirname(__DIR__, 2) . '/includes/config.php';
require_once dirname(__DIR__, 2) . '/includes/db.php';

$stmt_role = $pdo->prepare("SELECT role FROM utilisateurs WHERE id_utilisateur = ?");
$stmt_role->execute([$_SESSION['user_id']]);
$user_role = $stmt_role->fetchColumn();

if ($user_role !== 'admin') {
    header('Location: /lumoura/index.php');
    exit;
}