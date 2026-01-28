<?php
// ===============================
// CONNEXION À LA BASE DE DONNÉES
// ===============================
// PAS DE session_start() ici ! (déjà fait dans config.php)

try {
    // Tentative de connexion à la base de données MySQL
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Connexion réussie → rien à afficher
} catch (PDOException $e) {
    die('<div style="
        padding: 20px; 
        background: #f8d7da; 
        color: #721c24; 
        border: 1px solid #f5c6cb; 
        border-radius: 5px;">
        <h3>Erreur de connexion à la base de données pour Éclat d\'Or</h3>
        <p>Détails : ' . $e->getMessage() . '</p>
        <p>Vérifiez vos paramètres de connexion dans config.php</p>
    </div>');
}
?>