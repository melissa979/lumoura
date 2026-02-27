<?php
// Démarrer la session seulement si pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'lumoura_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_NAME', 'Éclat d\'Or');
define('SITE_URL', 'http://localhost/lumoura/');
?>