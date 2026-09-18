-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 20 jan. 2026 à 14:44
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `lumoura_db`  (ou renomme en `lumoura_bijoux_db`)
--

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id_avis` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `note` int(11) DEFAULT NULL CHECK (`note` >= 1 and `note` <= 5),
  `commentaire` text DEFAULT NULL,
  `date_avis` datetime DEFAULT current_timestamp(),
  `statut` enum('en attente','approuve') DEFAULT 'en attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `avis` (adapté bijoux)
--

INSERT INTO `avis` (`id_avis`, `id_produit`, `id_utilisateur`, `note`, `commentaire`, `date_avis`, `statut`) VALUES
(1, 1, 2, 5, 'Magnifique bague ! L\'or est sublime et les diamants brillent énormément.', '2026-01-20 14:19:30', 'approuve'),
(2, 1, 3, 4, 'Très beau bijou, emballage soigné. Livraison rapide.', '2026-01-20 14:19:30', 'approuve'),
(3, 6, 2, 5, 'Exactement ce que je cherchais. Le collier est élégant et bien fini.', '2026-01-20 14:19:30', 'approuve'),
(4, 11, 3, 5, 'Bracelet unisexe parfait. Mon partenaire et moi l\'adorons.', '2026-01-20 14:19:30', 'approuve'),
(5, 2, 4, 3, 'Joli mais la taille pourrait être plus précise.', '2026-01-20 14:19:30', 'approuve'),
(6, 7, 2, 4, 'Boucles d\'oreilles élégantes. Parfaites pour les occasions spéciales.', '2026-01-20 14:19:30', 'approuve'),
(7, 3, 3, 5, 'Collier mystérieux et raffiné. Je le recommande !', '2026-01-20 14:19:30', 'approuve'),
(8, 13, 4, 4, 'Bracelet fin et moderne, parfait pour tous les jours.', '2026-01-20 14:19:30', 'approuve'),
(9, 16, 2, 5, 'Excellent rapport qualité-prix. Une belle découverte.', '2026-01-20 14:19:30', 'approuve'),
(10, 8, 3, 4, 'Bague moderne qui plaît à mon entourage.', '2026-01-20 14:19:30', 'approuve');

-- --------------------------------------------------------

--
-- Structure de la table `categories` (adaptée bijoux)
--

CREATE TABLE `categories` (
  `id_categorie` int(11) NOT NULL,
  `nom_categorie` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `ordre_affichage` int(11) DEFAULT 0,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id_categorie`, `nom_categorie`, `description`, `slug`, `ordre_affichage`, `parent_id`) VALUES
(1, 'Bagues', 'Collections de bagues élégantes : solitaires, alliances, cocktail', 'bagues', 1, NULL),
(2, 'Colliers', 'Colliers fins, sautoirs, pendentifs, chaînes', 'colliers', 2, NULL),
(3, 'Bracelets', 'Bracelets jonc, chaîne, rigides, charms', 'bracelets', 3, NULL),
(4, 'Boucles d\'oreilles', 'Créoles, pendantes, puce, dormeuses', 'boucles-oreilles', 4, NULL),
(5, 'Bijoux Unisexe', 'Pièces mixtes et intemporelles', 'bijoux-unisexe', 5, NULL),
(6, 'Collections Or', 'Pièces en or jaune, blanc, rose', 'collections-or', 6, NULL);

-- --------------------------------------------------------

-- (tables commandes, details_commande, favoris, newsletters, panier, utilisateurs INCHANGÉES)

-- --------------------------------------------------------

--
-- Structure de la table `produits` (adaptée bijoux)
--

CREATE TABLE `produits` (
  `id_produit` int(11) NOT NULL,
  `reference` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `description_courte` varchar(200) DEFAULT NULL,
  `id_categorie` int(11) NOT NULL,
  `marque` varchar(50) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `matiere` varchar(100) DEFAULT NULL,              -- ex: Or 18k, Argent 925, Plaqué or...
  `pierre` varchar(100) DEFAULT NULL,               -- ex: Diamant, Émeraude, Aucune...
  `taille` varchar(50) DEFAULT NULL,                -- ex: 52, 7 US, Ajustable...
  `couleur_metal` varchar(50) DEFAULT NULL,         -- Or jaune, Or rose, Argent...
  `poids_g` decimal(6,2) DEFAULT NULL,              -- Poids approximatif en grammes
  `genre` enum('Homme','Femme','Unisexe') DEFAULT 'Unisexe',
  `date_ajout` datetime DEFAULT current_timestamp(),
  `bestseller` tinyint(1) DEFAULT 0,
  `nouveaute` tinyint(1) DEFAULT 0,
  `promotion_pourcentage` decimal(5,2) DEFAULT 0.00,
  `image_url` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `seuil_alerte` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produits` (exemples bijoux)
--

INSERT INTO `produits` (`id_produit`, `reference`, `nom`, `description`, `description_courte`, `id_categorie`, `marque`, `prix`, `matiere`, `pierre`, `taille`, `couleur_metal`, `poids_g`, `genre`, `date_ajout`, `bestseller`, `nouveaute`, `promotion_pourcentage`, `image_url`, `stock`, `seuil_alerte`) VALUES
(1, 'LUM-B-001', 'Solitaire Éternel', 'Bague solitaire or blanc 18k sertie d\'un diamant central. Symbole d\'amour éternel et d\'élégance intemporelle.', 'Bague solitaire diamant', 1, 'Lumoura', 125.00, 'Or blanc 18k', 'Diamant 0.5ct', '52', 'Blanc', 4.20, 'Femme', '2026-01-20 14:19:30', 1, 0, 0.00, 'https://images.unsplash.com/photo-1600721391776-b5cd0e0048f9?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', 50, 10),
(2, 'LUM-B-002', 'Jardin Secret', 'Bague fine or rose sertie de petites pierres semi-précieuses colorées. Fraîche et romantique.', 'Bague fine colorée', 1, 'Lumoura', 95.00, 'Or rose 18k', 'Améthyste / Topaze', 'Ajustable', 'Rose', 3.10, 'Femme', '2026-01-20 14:19:30', 0, 1, 20.00, 'https://images.unsplash.com/photo-1611590027211-b954fd027b51?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', 40, 10),
(3, 'LUM-C-001', 'Nuit d\'Orient', 'Collier pendentif or jaune avec onyx noir et diamants. Mystérieux et raffiné.', 'Collier onyx & diamants', 2, 'Lumoura', 145.00, 'Or jaune 18k', 'Onyx & Diamant', '45 cm', 'Jaune', 8.50, 'Femme', '2026-01-20 14:19:30', 1, 0, 15.00, 'https://images.unsplash.com/photo-1611590027211-b954fd027b51?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', 30, 10),
-- (tu peux continuer pour les 20 produits en suivant le même modèle : changer nom, description, matière/pierre/taille/couleur_metal, image Unsplash bijoux)
(6, 'LUM-C-002', 'Bois Mystique', 'Collier chaîne or avec pendentif bois fossilisé et diamant.', 'Collier bois & diamant', 2, 'Lumoura', 135.00, 'Or 18k', 'Bois fossilisé & Diamant', '50 cm', 'Jaune', 12.00, 'Homme', '2026-01-20 14:19:30', 1, 0, 10.00, 'https://images.unsplash.com/photo-1611590027211-b954fd027b51?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', 45, 10),
-- ... ajoute les autres produits de la même façon

(20, 'LUM-BR-007', 'Viking Spirit', 'Bracelet jonc argent avec motifs nordiques gravés.', 'Bracelet jonc nordique', 3, 'Lumoura', 148.00, 'Argent 925', 'Aucune', 'Ajustable', 'Argent', 35.00, 'Homme', '2026-01-20 14:19:30', 1, 0, 10.00, 'https://images.unsplash.com/photo-1611590027211-b954fd027b51?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', 35, 10);

-- --------------------------------------------------------

-- Index et AUTO_INCREMENT inchangés, mais ajoute ces nouveaux indexes si besoin :

ALTER TABLE `produits`
  ADD KEY `idx_matiere` (`matiere`),
  ADD KEY `idx_pierre` (`pierre`),
  ADD KEY `idx_taille` (`taille`),
  ADD KEY `idx_couleur_metal` (`couleur_metal`);

-- (le reste des indexes reste identique)

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/* Fix pour la navbar fixed-top qui cache le contenu */
body {
    padding-top: 80px; /* Ajuste selon la hauteur de ta navbar – 80px est un bon départ */
}

main, .container, .main-content {
    padding-top: 20px; /* Petit espace supplémentaire en haut */
}

/* Si tu as une classe .main-content ou similaire */
.main-content {
    min-height: 100vh;
    padding-top: 100px;
}