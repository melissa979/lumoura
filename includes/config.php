<?php
// Démarrer la session une seule fois, en sécurité
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===============================
// CONFIGURATION DE LA BASE DE DONNÉES
// ===============================
define('DB_HOST', 'localhost');
define('DB_NAME', 'lumoura_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ===============================
// CONFIGURATION DU SITE
// ===============================
define('SITE_NAME', 'Éclat d\'Or');
define('SITE_URL', 'http://localhost/lumoura/');
?>