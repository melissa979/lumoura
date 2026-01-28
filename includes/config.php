<?php
// Démarrer la session – UNIQUEMENT ICI
session_start();

// ===============================
// CONFIGURATION DE LA BASE DE DONNÉES
// ===============================
define('DB_HOST', 'localhost');
define('DB_NAME', 'lumoura_db');      // ← Vérifie que c'est bien écrit comme ça (pas DB_NANE ou autre faute)
define('DB_USER', 'root');
define('DB_PASS', '');

// ===============================
// CONFIGURATION DU SITE
// ===============================
define('SITE_NAME', 'Éclat d\'Or');
define('SITE_URL', 'http://localhost/lumoura/');
?>