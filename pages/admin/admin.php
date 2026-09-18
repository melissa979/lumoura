<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/db.php';

// Protection : si pas connecté, on redirige vers la connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit;
}

$pageTitle = "Tableau de Bord Admin - Lumoura";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .admin-header {
            background: #1a1a1a;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-container {
            padding: 30px;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .card h3 { color: #c9a227; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th { background: #1a1a1a; color: white; }
    </style>
</head>
<body>

<div class="admin-header">
    <h2>🔧 Administration Lumoura Joaillerie</h2>
    <div>
        <span>Bienvenue, Admin</span> |
        <a href="deconnexion.php" style="color:#ff6b6b; margin-left:15px;">Déconnexion</a>
    </div>
</div>

<div class="admin-container">
    <h1>Tableau de Bord</h1>
    
    <div class="dashboard-grid">
        <div class="card">
            <h3>Produits</h3>
            <h2>5</h2>
            <a href="produit.php">Gérer les produits →</a>
        </div>
        <div class="card">
            <h3>Commandes</h3>
            <h2>1</h2>
            <a href="commande.php">Voir les commandes →</a>
        </div>
        <div class="card">
            <h3>Utilisateurs</h3>
            <h2>2</h2>
            <a href="#">Gérer les clients →</a>
        </div>
    </div>

    <h2 style="margin-top:40px;">Derniers Produits</h2>
    <?php
    try {
        $stmt = $pdo->query("SELECT * FROM produits ORDER BY id_produit DESC LIMIT 5");
        $produits = $stmt->fetchAll();
    } catch (Exception $e) {
        $produits = [];
    }
    ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prix</th>
            <th>Action</th>
        </tr>
        <?php foreach ($produits as $p): ?>
        <tr>
            <td><?= $p['id_produit'] ?></td>
            <td><?= htmlspecialchars($p['nom'] ?? 'N/A') ?></td>
            <td><?= number_format($p['prix'] ?? 0, 2) ?> €</td>
            <td><a href="#">Modifier</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>