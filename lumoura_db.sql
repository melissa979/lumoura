-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 08 oct. 2026 à 10:13
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
-- Base de données : `lumoura_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `adresses`
--

CREATE TABLE `adresses` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `complement_adresse` varchar(255) DEFAULT NULL,
  `code_postal` varchar(10) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `pays` varchar(100) DEFAULT 'France',
  `telephone` varchar(20) DEFAULT NULL,
  `zone_livraison` varchar(150) DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL,
  `delai` varchar(100) DEFAULT NULL,
  `statut` enum('actif','inactif') DEFAULT 'actif',
  `date_creation` datetime DEFAULT current_timestamp(),
  `est_principale` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `adresses`
--

INSERT INTO `adresses` (`id`, `id_utilisateur`, `prenom`, `nom`, `email`, `adresse`, `complement_adresse`, `code_postal`, `ville`, `pays`, `telephone`, `zone_livraison`, `prix`, `delai`, `statut`, `date_creation`, `est_principale`) VALUES
(3, 2, 'Melissa', 'SALHI', NULL, '1 t rue des carrières 95360 Montmagny', '1 t rue des carrières 95360 Montmagny', '95360', 'paris', 'France', '0673488391', NULL, NULL, NULL, 'actif', '2026-06-16 01:17:47', 1),
(4, 2, 'Melissa', 'SALHI', NULL, '1 t rue des carrières 95360 Montmagny', '15 rue fontaine a mullard', '7501', 'paris', 'France', '0673488391', NULL, NULL, NULL, 'actif', '2026-09-18 12:49:05', 0);

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id_avis` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `note` int(11) NOT NULL DEFAULT 5,
  `commentaire` text DEFAULT NULL,
  `date_avis` datetime DEFAULT current_timestamp(),
  `est_principal` tinyint(1) DEFAULT 0,
  `statut` enum('en attente','approuve') DEFAULT 'en attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `avis`
--

INSERT INTO `avis` (`id_avis`, `id_utilisateur`, `id_produit`, `note`, `commentaire`, `date_avis`, `est_principal`, `statut`) VALUES
(2, 2, 22, 2, 'normal', '2026-06-19 06:09:44', 0, 'approuve'),
(3, 2, 22, 4, 'j\'adore', '2026-06-19 06:12:57', 0, 'approuve');

-- --------------------------------------------------------

--
-- Structure de la table `cartes_cadeaux`
--

CREATE TABLE `cartes_cadeaux` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `montant` decimal(10,2) NOT NULL,
  `message` text DEFAULT NULL,
  `stackable` enum('en attente','traite') DEFAULT 'en attente',
  `date_demande` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categories`
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
(1, 'Femme', 'Bijoux pour femme', 'femme', 1, NULL),
(2, 'Homme', 'Bijoux pour homme', 'homme', 2, NULL),
(3, 'Unisexe', 'Bijoux unisexe', 'unisexe', 3, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id_commande` int(11) NOT NULL,
  `numero_commande` varchar(20) DEFAULT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `date_commande` datetime DEFAULT current_timestamp(),
  `statut` enum('en attente','payee','expediee','livree','annulee') DEFAULT 'en attente',
  `montant` decimal(10,2) DEFAULT 0.00,
  `frais_livraison` decimal(8,2) DEFAULT 0.00,
  `mode_livraison` varchar(50) DEFAULT NULL,
  `mode_paiement` varchar(50) DEFAULT NULL,
  `date_paiement` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id_commande`, `numero_commande`, `id_utilisateur`, `date_commande`, `statut`, `montant`, `frais_livraison`, `mode_livraison`, `mode_paiement`, `date_paiement`, `notes`) VALUES
(1, 'LUM-90FADB-2026', 2, '2026-06-15 22:35:21', 'livree', 125.00, 0.00, 'Colissimo', 'PayPal', NULL, NULL),
(2, 'LUM-980516-2026', 2, '2026-06-19 05:48:41', '', 125.00, 0.00, 'Colissimo', 'PayPal', NULL, NULL),
(3, 'LUM-D2A987-2026', 2, '2026-09-02 09:50:05', 'en attente', 237.00, 0.00, 'Colissimo', 'PayPal', NULL, NULL),
(4, 'LUM-3C8205-2026', 2, '2026-09-17 23:45:07', 'en attente', 130.00, 0.00, 'Colissimo', 'PayPal', NULL, NULL),
(5, 'LUM-A92FB4-2026', 2, '2026-09-18 12:43:38', 'en attente', 139.90, 9.90, 'Standard (3-5 jours)', 'Carte bancaire', NULL, NULL),
(6, 'LUM-ED97D6-2026', 2, '2026-09-18 12:45:02', 'en attente', 139.90, 9.90, 'Standard (3-5 jours)', 'PayPal', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `details_commande`
--

CREATE TABLE `details_commande` (
  `id_detail` int(11) NOT NULL,
  `id_commande` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `prix_unitaire` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `details_commande`
--

INSERT INTO `details_commande` (`id_detail`, `id_commande`, `id_produit`, `quantite`, `prix_unitaire`) VALUES
(1, 1, 1, 1, 125.00),
(2, 2, 1, 1, 125.00),
(3, 3, 36, 3, 79.00),
(4, 4, 33, 1, 130.00),
(5, 5, 33, 1, 130.00),
(6, 6, 33, 1, 130.00);

-- --------------------------------------------------------

--
-- Structure de la table `favori`
--

CREATE TABLE `favori` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_categorie` int(11) DEFAULT NULL,
  `id_produit` int(11) DEFAULT NULL,
  `date_ajout` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `favori`
--

INSERT INTO `favori` (`id`, `id_utilisateur`, `id_categorie`, `id_produit`, `date_ajout`) VALUES
(8, 2, 1, 1, '2026-06-16 09:00:12'),
(13, 2, 1, 22, '2026-09-18 12:36:39');

-- --------------------------------------------------------

--
-- Structure de la table `liste_envies`
--

CREATE TABLE `liste_envies` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `date_ajout` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `livraisons`
--

CREATE TABLE `livraisons` (
  `id_livreur` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `zone_livraison` varchar(150) DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL,
  `delai` varchar(100) DEFAULT NULL,
  `statut` enum('actif','inactif') DEFAULT 'actif',
  `date_inscription` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `newsletters`
--

CREATE TABLE `newsletters` (
  `id_newsletter` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `date_inscription` datetime DEFAULT current_timestamp(),
  `actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `code` char(6) NOT NULL,
  `type` enum('inscription','connexion') DEFAULT 'inscription',
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `panier`
--

CREATE TABLE `panier` (
  `id_panier` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `date_ajout` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `panier`
--

INSERT INTO `panier` (`id_panier`, `id_utilisateur`, `id_produit`, `quantite`, `date_ajout`) VALUES
(13, 2, 36, 1, '2026-09-19 23:22:28'),
(14, 2, 22, 1, '2026-10-07 14:03:57');

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id_produit` int(11) NOT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `description_courte` varchar(200) DEFAULT NULL,
  `id_categorie` int(11) DEFAULT NULL,
  `type_bijou` enum('Bague','Collier','Bracelet','Boucles d''oreilles','Montre','Pendentif','Autre') DEFAULT NULL,
  `genre` enum('Femme','Homme','Unisexe') DEFAULT NULL,
  `marque` varchar(50) DEFAULT NULL,
  `matiere` varchar(100) DEFAULT NULL,
  `pierre` varchar(255) DEFAULT NULL,
  `taille` varchar(50) DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL DEFAULT 0.00,
  `poids_grammes` decimal(8,2) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `collection` varchar(255) DEFAULT NULL,
  `promotion_pourcentage` decimal(5,2) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `image_hover_url` varchar(500) DEFAULT NULL,
  `bestseller` tinyint(1) DEFAULT 0,
  `nouveaute` tinyint(1) DEFAULT 0,
  `date_ajout` datetime DEFAULT current_timestamp(),
  `stock` int(11) DEFAULT 0,
  `seuil_alerte` int(11) DEFAULT 5,
  `en_promo` tinyint(1) NOT NULL DEFAULT 0,
  `prix_promo` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id_produit`, `reference`, `nom`, `description`, `description_courte`, `id_categorie`, `type_bijou`, `genre`, `marque`, `matiere`, `pierre`, `taille`, `prix`, `poids_grammes`, `dimensions`, `collection`, `promotion_pourcentage`, `image_url`, `image_hover_url`, `bestseller`, `nouveaute`, `date_ajout`, `stock`, `seuil_alerte`, `en_promo`, `prix_promo`) VALUES
(1, 'LUM-B-001', 'Solitaire Éternel', 'Bague solitaire or blanc 18k sertie d\'un diamant central de 0.5ct, monture 4 griffes classique', 'Bague solitaire diamant', 1, NULL, 'Femme', 'Éclat d\'Or', 'Or blanc 18k', 'Diamant 0.5ct', '52', 125.00, 4.20, NULL, NULL, NULL, '/lumoura/assets/images/produits/solitaire.jpg', '/lumoura/assets/images/produits/solitaire-porte.jpg', 1, 0, '2026-01-20 14:19:30', 10, 5, 0, NULL),
(21, 'LUM-BO-001', 'Spirale Dorée', 'Boucles d\'oreilles créoles torsadées en plaqué or, motif spirale élégant pour un look intemporel.', 'Créoles torsadées plaqué or', 3, 'Boucles d\'oreilles', 'Unisexe', 'Éclat d\'Or', 'Plaqué or 18k', 'Aucune', 'Ajustable', 39.00, 4.50, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2000.42.32.jpeg', NULL, 0, 1, '2026-06-19 02:05:32', 20, 5, 0, NULL),
(22, 'LUM-BG-002', 'Promesse Ovale', 'Bague solitaire en argent sertie d\'une pierre ovale étincelante, sublimée par deux pierres latérales.', 'Bague solitaire argent, pierre ovale', 1, 'Bague', 'Femme', 'Éclat d\'Or', 'Argent 925', 'Zircon ovale', '54', 89.00, 3.20, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2000.42.33%20(1).jpeg', NULL, 0, 1, '2026-06-19 02:05:32', 15, 5, 0, NULL),
(23, 'LUM-BG-003', 'Trio Royal', 'Ensemble de trois bagues empilables en plaqué or : solitaire, alliance pavée et anneau fin.', 'Set de 3 bagues empilables', 1, 'Bague', 'Femme', 'Éclat d\'Or', 'Plaqué or 18k', 'Zircons assortis', 'Ajustable', 120.00, 9.80, NULL, 'Trio', NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2000.42.33%20(2).jpeg', NULL, 1, 1, '2026-06-19 02:05:32', 10, 5, 0, NULL),
(24, 'LUM-BG-004', 'Éclat Solitaire', 'Bague solitaire en plaqué or sertie d\'une pierre ovale, rehaussée d\'un pavage de zircons sur l\'anneau.', 'Bague solitaire pavée, plaqué or', 1, 'Bague', 'Femme', 'Éclat d\'Or', 'Plaqué or 18k', 'Zircon ovale 1.5ct', '52', 145.00, 4.10, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2000.42.33%20(5).jpeg', '/lumoura/assets/images/produits/eclat-solitaire-porte.png', 1, 0, '2026-06-19 02:05:32', 8, 5, 0, NULL),
(25, 'LUM-BR-005', 'Maille Plate', 'Bracelet chaîne maille plate en plaqué or, fermoir mousqueton et chaînette d\'extension.', 'Bracelet maille plate plaqué or', 1, 'Bracelet', 'Femme', 'Éclat d\'Or', 'Plaqué or 18k', 'Aucune', 'Ajustable 16-19cm', 55.00, 6.30, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2000.42.33.jpeg', NULL, 0, 0, '2026-06-19 02:05:32', 18, 5, 0, NULL),
(26, 'LUM-BR-006', 'Constellation', 'Bracelet tennis en plaqué or serti d\'une ligne continue de zircons brillants.', 'Bracelet tennis pavé de zircons', 1, 'Bracelet', 'Femme', 'Éclat d\'Or', 'Plaqué or 18k', 'Zircons ronds', '18 cm', 110.00, 7.50, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2000.42.34%20(1).jpeg', NULL, 1, 0, '2026-06-19 02:05:32', 12, 5, 0, NULL),
(27, 'LUM-BG-007', 'Promesse Éternelle', 'Bague solitaire classique en plaqué or, présentée dans son écrin Lumoura signature.', 'Bague solitaire avec écrin Lumoura', 1, 'Bague', 'Femme', 'Éclat d\'Or', 'Plaqué or 18k', 'Zircon rond', '53', 99.00, 3.00, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2000.42.34%20(3).jpeg', NULL, 0, 1, '2026-06-19 02:05:32', 14, 5, 0, NULL),
(28, 'LUM-CO-008', 'Émeraude Claire', 'Collier fin en plaqué or avec pendentif rectangulaire serti d\'une pierre claire taille émeraude.', 'Collier pendentif rectangle facetté', 1, 'Collier', 'Femme', 'Éclat d\'Or', 'Plaqué or 18k', 'Zircon taille émeraude', '45 cm', 75.00, 2.80, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2000.42.34%20(4).jpeg', NULL, 0, 0, '2026-06-19 02:05:32', 16, 5, 0, NULL),
(29, 'LUM-BO-009', 'Anneau Lisse', 'Créoles épaisses en plaqué or au design minimaliste et lisse, parfaites au quotidien.', 'Créoles épaisses lisses, plaqué or', 3, 'Boucles d\'oreilles', 'Unisexe', 'Éclat d\'Or', 'Plaqué or 18k', 'Aucune', '25mm', 35.00, 5.20, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2000.42.34.jpeg', NULL, 0, 0, '2026-06-19 02:05:32', 22, 5, 0, NULL),
(30, 'LUM-BG-010', 'Lueur Dorée', 'Bague solitaire en plaqué or sertie d\'une pierre ovale, anneau fin et épuré.', 'Bague solitaire ovale, plaqué or', 1, 'Bague', 'Femme', 'Éclat d\'Or', 'Plaqué or 18k', 'Zircon ovale', '54', 135.00, 3.50, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2000.42.35%20(1).jpeg', NULL, 0, 1, '2026-06-19 02:05:32', 9, 5, 0, NULL),
(31, 'LUM-CO-011', 'Larme de Lumière', 'Collier fin en plaqué or avec pendentif en forme de goutte, serti d\'une pierre étincelante.', 'Collier pendentif goutte', 1, 'Collier', 'Femme', 'Éclat d\'Or', 'Plaqué or 18k', 'Zircon taille poire', '42 cm', 65.00, 2.50, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2000.42.35%20(2).jpeg', NULL, 0, 0, '2026-06-19 02:05:32', 17, 5, 0, NULL),
(32, 'LUM-BG-012', 'Alliance Carrée', 'Alliance en plaqué or entièrement pavée de zircons taille carrée, pour un éclat continu.', 'Alliance pavée de zircons carrés', 1, 'Bague', 'Femme', 'Éclat d\'Or', 'Plaqué or 18k', 'Zircons taille carrée', '52', 115.00, 3.80, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2000.42.35%20(3).jpeg', NULL, 1, 0, '2026-06-19 02:05:32', 11, 5, 0, NULL),
(33, 'LUM-BR-013', 'Trèfle Obsidienne', 'Bracelet en plaqué or composé de motifs trèfle sertis d\'onyx noir, relié par une fine chaîne. Élégance discrète pour homme.', 'Bracelet trèfles onyx, plaqué or', 2, 'Bracelet', 'Homme', 'Éclat d\'Or', 'Plaqué or 18k', 'Onyx noir', '19 cm', 130.00, 8.20, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2005.55.39%20(1).jpeg', NULL, 0, 1, '2026-06-19 06:52:40', 10, 5, 0, NULL),
(34, 'LUM-BG-014', 'Duo Stellaire', 'Ensemble de deux bagues en argent : une sertie d\'une ligne de zircons, l\'autre au design satiné minimaliste. Vendues en duo empilable.', 'Set de 2 bagues argent, design minimaliste', 2, 'Bague', 'Homme', 'Éclat d\'Or', 'Argent 925', 'Zircons', '58', 95.00, 12.50, NULL, 'Duo', NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2005.55.39%20(2).jpeg', NULL, 0, 0, '2026-06-19 06:52:40', 14, 5, 0, NULL),
(35, 'LUM-BR-015', 'Maille Argentée', 'Bracelet chaîne gourmette en argent massif, maillons plats polis pour un éclat soutenu. Fermoir mousqueton renforcé.', 'Bracelet gourmette argent massif', 2, 'Bracelet', 'Homme', 'Éclat d\'Or', 'Argent 925', 'Aucune', '20 cm', 65.00, 15.00, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2005.55.39%20(3).jpeg', NULL, 1, 0, '2026-06-19 06:52:40', 16, 5, 0, NULL),
(36, 'LUM-CO-016', 'Gourmette Duo', 'Collier chaîne gourmette épaisse, disponible en plaqué or ou argent, maillons massifs pour un style affirmé.', 'Collier gourmette épais, or ou argent', 2, 'Collier', 'Homme', 'Éclat d\'Or', 'Plaqué or 18k / Argent 925', 'Aucune', '55 cm', 79.00, 22.00, NULL, NULL, NULL, '/lumoura/assets/images/bg/WhatsApp%20Image%202026-06-19%20at%2005.55.39.jpeg', NULL, 1, 1, '2026-06-19 06:52:40', 13, 5, 0, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `stock_mouvements`
--

CREATE TABLE `stock_mouvements` (
  `id` int(11) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `type` enum('decrement','restitution') NOT NULL,
  `raison` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id_utilisateur` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `date_inscription` datetime DEFAULT current_timestamp(),
  `civilite` enum('M','Mme','Mlle') DEFAULT NULL,
  `newsletter` tinyint(1) DEFAULT 0,
  `statut` enum('actif','inactif') DEFAULT 'actif',
  `role` enum('client','admin') DEFAULT 'client',
  `adresse_livraison` text DEFAULT NULL,
  `code_postal_livraison` varchar(10) DEFAULT NULL,
  `ville_livraison` varchar(100) DEFAULT NULL,
  `adresse_facturation` text DEFAULT NULL,
  `code_postal_facturation` varchar(10) DEFAULT NULL,
  `ville_facturation` varchar(100) DEFAULT NULL,
  `email_verifie` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id_utilisateur`, `email`, `mot_de_passe`, `nom`, `prenom`, `telephone`, `date_inscription`, `civilite`, `newsletter`, `statut`, `role`, `adresse_livraison`, `code_postal_livraison`, `ville_livraison`, `adresse_facturation`, `code_postal_facturation`, `ville_facturation`, `email_verifie`) VALUES
(1, 'melissasalhi79@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'SALHI', 'Melissa', '0673488391', '2026-06-15 11:02:27', 'Mme', 1, 'actif', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, 0),
(2, 'salhimelissa2007@gmail.com', '$2y$10$47z6wZLTD9uFdupJFemcEO9wbIaKA6fIcfaUrLvOq/xhxC.mvM5LC', 'aaaa', 'aaaa', '0454454545', '2026-06-15 13:15:58', 'Mme', 1, 'actif', 'client', NULL, NULL, NULL, NULL, NULL, NULL, 0);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `adresses`
--
ALTER TABLE `adresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id_avis`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `id_produit` (`id_produit`);

--
-- Index pour la table `cartes_cadeaux`
--
ALTER TABLE `cartes_cadeaux`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id_categorie`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id_commande`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `details_commande`
--
ALTER TABLE `details_commande`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_commande` (`id_commande`),
  ADD KEY `id_produit` (`id_produit`);

--
-- Index pour la table `favori`
--
ALTER TABLE `favori`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `id_categorie` (`id_categorie`),
  ADD KEY `id_produit` (`id_produit`);

--
-- Index pour la table `liste_envies`
--
ALTER TABLE `liste_envies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `id_produit` (`id_produit`);

--
-- Index pour la table `livraisons`
--
ALTER TABLE `livraisons`
  ADD PRIMARY KEY (`id_livreur`);

--
-- Index pour la table `newsletters`
--
ALTER TABLE `newsletters`
  ADD PRIMARY KEY (`id_newsletter`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `panier`
--
ALTER TABLE `panier`
  ADD PRIMARY KEY (`id_panier`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `id_produit` (`id_produit`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id_produit`),
  ADD KEY `id_categorie` (`id_categorie`);

--
-- Index pour la table `stock_mouvements`
--
ALTER TABLE `stock_mouvements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_produit` (`id_produit`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `adresses`
--
ALTER TABLE `adresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id_avis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `cartes_cadeaux`
--
ALTER TABLE `cartes_cadeaux`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id_categorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id_commande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `details_commande`
--
ALTER TABLE `details_commande`
  MODIFY `id_detail` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `favori`
--
ALTER TABLE `favori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `liste_envies`
--
ALTER TABLE `liste_envies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `livraisons`
--
ALTER TABLE `livraisons`
  MODIFY `id_livreur` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `newsletters`
--
ALTER TABLE `newsletters`
  MODIFY `id_newsletter` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `panier`
--
ALTER TABLE `panier`
  MODIFY `id_panier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id_produit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT pour la table `stock_mouvements`
--
ALTER TABLE `stock_mouvements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `adresses`
--
ALTER TABLE `adresses`
  ADD CONSTRAINT `fk_adr_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `fk_avis_produit` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_avis_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `cartes_cadeaux`
--
ALTER TABLE `cartes_cadeaux`
  ADD CONSTRAINT `fk_cc_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id_categorie`) ON DELETE SET NULL;

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `fk_cmd_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE SET NULL;

--
-- Contraintes pour la table `details_commande`
--
ALTER TABLE `details_commande`
  ADD CONSTRAINT `fk_det_commande` FOREIGN KEY (`id_commande`) REFERENCES `commandes` (`id_commande`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_det_produit` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON DELETE CASCADE;

--
-- Contraintes pour la table `favori`
--
ALTER TABLE `favori`
  ADD CONSTRAINT `fk_fav_categorie` FOREIGN KEY (`id_categorie`) REFERENCES `categories` (`id_categorie`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_fav_produit` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fav_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `liste_envies`
--
ALTER TABLE `liste_envies`
  ADD CONSTRAINT `fk_lenv_produit` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_lenv_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD CONSTRAINT `fk_otp_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `panier`
--
ALTER TABLE `panier`
  ADD CONSTRAINT `fk_pan_produit` FOREIGN KEY (`id_produit`) REFERENCES `produits` (`id_produit`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pan_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `fk_prod_categorie` FOREIGN KEY (`id_categorie`) REFERENCES `categories` (`id_categorie`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
