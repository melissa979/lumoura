<?php
// Inclure la config
require_once __DIR__ . '/config.php';

// ======================
// CONNEXION À LA BASE DE DONNÉES
// ======================
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="padding:20px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:5px; margin:20px;">
        <h3>❌ Erreur de connexion à la base de données</h3>
        <p><strong>Détails :</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
        <p>Vérifie que XAMPP est bien lancé et que la base <strong>lumoura_db</strong> existe.</p>
    </div>');
}
?>