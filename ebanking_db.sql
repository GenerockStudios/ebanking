-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2026 at 04:49 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ebanking`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `client_id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `numero_identite` varchar(50) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`client_id`, `nom`, `prenom`, `date_naissance`, `adresse`, `telephone`, `email`, `numero_identite`, `date_creation`) VALUES
(7, 'Jean Eric', 'Tsala Ntonga', '2025-12-27', 'Mimboman rue 4569', '671194872', 'generockstudios8@gmail.com', 'CEUIOJNIIOJJJKIOP0', '2025-12-03 20:42:33'),
(8, 'Beyala', 'Odile', '1985-06-15', 'Bastos, Yaoundé', '699123456', 'odile.beyala@email.com', 'CNI008756', '2026-01-05 08:00:00'),
(9, 'Ndi', 'Samuel', '1978-11-22', 'Bonanjo, Douala', '677889900', 'samuel.ndi@email.com', 'PASSPORT1234', '2026-01-12 09:15:00'),
(10, 'Tamo', 'Fatima', '1990-03-10', 'Hippodrome, Yaoundé', '655443322', 'fatima.tamo@email.com', 'CNI009876', '2026-01-20 10:30:00'),
(11, 'Essono', 'Pierre', '1982-09-05', 'Mvog-Mbi, Yaoundé', '691234567', 'pierre.essono@email.com', 'CNI012345', '2026-02-01 13:00:00'),
(12, 'Mvondo', 'Suzanne', '1975-12-30', 'Bonabéri, Douala', '698765432', 'suzanne.mvondo@email.com', 'PASSPORT5678', '2026-02-08 07:45:00'),
(13, 'Onguene', 'René', '1988-07-19', 'Nlongkak, Yaoundé', '677112233', 'rene.onguene@email.com', 'CNI045678', '2026-02-14 08:30:00'),
(14, 'Abena', 'Christine', '1995-02-28', 'Deido, Douala', '699887766', 'christine.abena@email.com', 'CNI034567', '2026-02-22 09:00:00'),
(15, 'Mbarga', 'Alain', '1980-10-12', 'Ekoudou, Yaoundé', '655998877', 'alain.mbarga@email.com', 'PASSPORT91011', '2026-03-01 10:15:00'),
(16, 'Ndongo', 'Brigitte', '1992-04-25', 'Bonamoussadi, Douala', '691223344', 'brigitte.ndongo@email.com', 'CNI023456', '2026-03-05 12:20:00'),
(17, 'Zambo', 'Gaston', '1970-08-08', 'Mendong, Yaoundé', '677334455', 'gaston.zambo@email.com', 'CNI056789', '2026-03-10 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `comptes`
--

CREATE TABLE `comptes` (
  `compte_id` int(11) NOT NULL,
  `numero_compte` varchar(20) NOT NULL,
  `client_id` int(11) NOT NULL,
  `type_compte_id` int(11) NOT NULL,
  `solde` decimal(18,2) DEFAULT 0.00,
  `date_ouverture` timestamp NOT NULL DEFAULT current_timestamp(),
  `est_actif` tinyint(1) DEFAULT 1,
  `est_suspendu` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Compte actif, 1=Compte suspendu (retraits bloques)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comptes`
--

INSERT INTO `comptes` (`compte_id`, `numero_compte`, `client_id`, `type_compte_id`, `solde`, `date_ouverture`, `est_actif`, `est_suspendu`) VALUES
(1, '435994933896', 7, 1, 100000.00, '2025-12-03 20:42:33', 1, 0),
(2, '123456789012', 8, 2, 250000.00, '2026-01-06 08:00:00', 1, 0),
(3, '234567890123', 9, 1, 50000.50, '2026-01-13 09:15:00', 1, 0),
(4, '345678901234', 10, 4, 1250000.00, '2026-01-21 10:30:00', 1, 0),
(5, '456789012345', 11, 5, 800000.00, '2026-02-02 13:00:00', 1, 0),
(6, '567890123456', 12, 3, 30000.00, '2026-02-09 07:45:00', 1, 0),
(7, '678901234567', 13, 2, 150000.00, '2026-02-15 08:30:00', 1, 0),
(8, '789012345678', 14, 6, 20000.00, '2026-02-23 09:00:00', 1, 0),
(9, '890123456789', 15, 7, 10000.00, '2026-03-02 10:15:00', 1, 0),
(10, '901234567890', 16, 8, 750000.00, '2026-03-06 12:20:00', 1, 0),
(11, '012345678901', 17, 9, 5000.00, '2026-03-11 14:00:00', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `documents_kyc`
--

CREATE TABLE `documents_kyc` (
  `document_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `type_document` varchar(50) NOT NULL,
  `reference_fichier` varchar(255) NOT NULL,
  `date_expiration` date DEFAULT NULL,
  `valide_par_utilisateur_id` int(11) DEFAULT NULL,
  `date_validation` timestamp NOT NULL DEFAULT current_timestamp(),
  `est_valide` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents_kyc`
--

INSERT INTO `documents_kyc` (`document_id`, `client_id`, `type_document`, `reference_fichier`, `date_expiration`, `valide_par_utilisateur_id`, `date_validation`, `est_valide`) VALUES
(1, 7, 'CNI', '/docs/cni_jean_eric.pdf', '2030-12-31', 1, '2025-12-04 09:00:00', 1),
(2, 8, 'PASSEPORT', '/docs/passeport_odile.pdf', '2028-06-15', 1, '2026-01-06 08:30:00', 1),
(3, 9, 'CNI', '/docs/cni_samuel.pdf', '2029-11-22', 2, '2026-01-14 10:00:00', 1),
(4, 10, 'PERMIS', '/docs/permis_fatima.pdf', '2027-03-10', 2, '2026-01-22 09:45:00', 1),
(5, 11, 'CNI', '/docs/cni_pierre.pdf', '2030-09-05', 3, '2026-02-03 13:30:00', 1),
(6, 12, 'PASSEPORT', '/docs/passeport_suzanne.pdf', '2026-12-30', 3, '2026-02-10 08:15:00', 1),
(7, 13, 'CNI', '/docs/cni_rene.pdf', '2028-07-19', 4, '2026-02-16 07:00:00', 1),
(8, 14, 'CNI', '/docs/cni_christine.pdf', '2029-02-28', 4, '2026-02-24 10:20:00', 1),
(9, 15, 'PASSEPORT', '/docs/passeport_alain.pdf', '2027-10-12', 5, '2026-03-03 12:40:00', 1),
(10, 16, 'CNI', '/docs/cni_brigitte.pdf', '2030-04-25', 5, '2026-03-07 13:10:00', 1),
(11, 17, 'PERMIS', '/docs/permis_gaston.pdf', '2026-08-08', 6, '2026-03-12 15:00:00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `historique_soldes`
--

CREATE TABLE `historique_soldes` (
  `historique_id` bigint(20) NOT NULL,
  `compte_id` int(11) NOT NULL,
  `date_snapshot` date NOT NULL,
  `solde_final` decimal(18,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `historique_soldes`
--

INSERT INTO `historique_soldes` (`historique_id`, `compte_id`, `date_snapshot`, `solde_final`) VALUES
(1, 1, '2025-12-31', 100000.00),
(2, 2, '2026-01-31', 250000.00),
(3, 3, '2026-01-31', 50000.50),
(4, 4, '2026-02-28', 1250000.00),
(5, 5, '2026-02-28', 800000.00),
(6, 6, '2026-02-28', 30000.00),
(7, 7, '2026-02-28', 150000.00),
(8, 8, '2026-02-28', 20000.00),
(9, 9, '2026-03-15', 10000.00),
(10, 10, '2026-03-15', 750000.00),
(11, 11, '2026-03-15', 5000.00);

-- --------------------------------------------------------

--
-- Table structure for table `journal_audit`
--

CREATE TABLE `journal_audit` (
  `log_id` bigint(20) NOT NULL,
  `date_heure` timestamp NOT NULL DEFAULT current_timestamp(),
  `utilisateur_id` int(11) DEFAULT NULL,
  `type_action` varchar(50) NOT NULL,
  `table_affectee` varchar(50) DEFAULT NULL,
  `identifiant_element_affecte` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `journal_audit`
--

INSERT INTO `journal_audit` (`log_id`, `date_heure`, `utilisateur_id`, `type_action`, `table_affectee`, `identifiant_element_affecte`, `details`) VALUES
(1, '2025-12-03 18:39:02', 1, 'CLIENT_CREATE_SUCCESS', 'Clients', '1', 'Création client réussie. ID: 1'),
(2, '2025-12-03 20:26:29', 1, 'CLIENT_CREATE_FAILURE', 'Clients', NULL, 'Échec création client. Identité: CEUIOJNIIOJJJKIOIO'),
(3, '2025-12-03 20:26:58', 1, 'CLIENT_CREATE_FAILURE', 'Clients', NULL, 'Échec création client. Identité: CEUIOJNIIOJJJKIOIO'),
(4, '2025-12-03 20:38:17', 1, 'CLIENT_CREATE_SUCCESS', 'Clients', '4', 'Création client réussie. ID: 4'),
(5, '2025-12-03 20:42:13', 1, 'CLIENT_CREATE_FAILURE', 'Clients', NULL, 'Échec création client. Identité: CEUIOJNIIOJJJKIOIO'),
(6, '2025-12-03 20:42:23', 1, 'CLIENT_CREATE_FAILURE', 'Clients', NULL, 'Échec création client. Identité: CEUIOJNIIOJJJKIOIO'),
(7, '2025-12-03 20:42:33', 1, 'CLIENT_CREATE_SUCCESS', 'Clients', '7', 'Création client réussie. ID: 7'),
(8, '2025-12-03 20:42:33', 1, 'COMPTE_OPEN_SUCCESS', 'Comptes', '435994933896', 'Ouverture compte 435994933896 pour client ID 7'),
(9, '2025-12-06 08:07:11', 1, 'DEPOT_START', 'Transactions', '1', 'Tentative DEPOT de 100000. Source: 1. Dest: N/A. Ref: TXN-20251206090711-3828'),
(10, '2025-12-06 08:07:11', 1, 'DEPOT_SUCCESS', 'Transactions', '1', 'Dépôt de 100000 sur compte ID: 1.'),
(11, '2025-12-06 08:10:47', 1, 'RETRAIT_START', 'Transactions', '1', 'Tentative RETRAIT de 19999. Source: 1. Dest: N/A. Ref: TXN-20251206091047-4554'),
(12, '2025-12-06 08:10:47', 1, 'RETRAIT_FAILURE', 'Transactions', '1', 'Echec: Plafonds non définis pour le compte ID: 1'),
(13, '2026-01-06 08:00:00', 5, 'CLIENT_CREATE_SUCCESS', 'clients', '8', 'Création client réussie. ID: 8'),
(14, '2026-01-06 08:05:00', 5, 'COMPTE_OPEN_SUCCESS', 'comptes', '2', 'Ouverture compte 123456789012 pour client ID 8'),
(15, '2026-01-13 09:15:00', 5, 'COMPTE_OPEN_SUCCESS', 'comptes', '3', 'Ouverture compte 234567890123 pour client ID 9'),
(16, '2026-01-21 10:30:00', 5, 'COMPTE_OPEN_SUCCESS', 'comptes', '4', 'Ouverture compte 345678901234 pour client ID 10'),
(17, '2026-02-02 13:00:00', 5, 'COMPTE_OPEN_SUCCESS', 'comptes', '5', 'Ouverture compte 456789012345 pour client ID 11'),
(18, '2026-02-09 07:45:00', 5, 'COMPTE_OPEN_SUCCESS', 'comptes', '6', 'Ouverture compte 567890123456 pour client ID 12'),
(19, '2026-02-10 08:30:00', 3, 'SESSION_CAISSE_OPEN', 'sessions_caisse', '1', 'Ouverture de session caisse pour utilisateur 3'),
(20, '2026-02-10 16:00:00', 3, 'SESSION_CAISSE_CLOSE', 'sessions_caisse', '1', 'Clôture de session caisse pour utilisateur 3'),
(21, '2026-03-01 07:00:00', 2, 'UTILISATEUR_CREATE', 'utilisateurs', '10', 'Création utilisateur stagiaire1'),
(22, '2026-03-05 12:20:00', 5, 'CLIENT_CREATE_SUCCESS', 'clients', '16', 'Création client réussie. ID: 16'),
(23, '2026-03-05 12:25:00', 5, 'COMPTE_OPEN_SUCCESS', 'comptes', '10', 'Ouverture compte 901234567890 pour client ID 16'),
(24, '2026-03-10 14:00:00', 5, 'CLIENT_CREATE_SUCCESS', 'clients', '17', 'Création client réussie. ID: 17'),
(25, '2026-03-10 14:05:00', 5, 'COMPTE_OPEN_SUCCESS', 'comptes', '11', 'Ouverture compte 012345678901 pour client ID 17'),
(26, '2026-03-19 22:52:48', 1, 'CLOTURE_JOURNEE', 'Historique_Soldes', NULL, 'Clôture journalière effectuée. Dépôts: ');

-- --------------------------------------------------------

--
-- Table structure for table `plafonds_comptes`
--

CREATE TABLE `plafonds_comptes` (
  `plafond_id` int(11) NOT NULL,
  `compte_id` int(11) NOT NULL,
  `plafond_retrait_journalier` decimal(18,2) NOT NULL,
  `plafond_depot_journalier` decimal(18,2) NOT NULL,
  `plafond_transfert_mensuel` decimal(18,2) NOT NULL,
  `date_mise_a_jour` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plafonds_comptes`
--

INSERT INTO `plafonds_comptes` (`plafond_id`, `compte_id`, `plafond_retrait_journalier`, `plafond_depot_journalier`, `plafond_transfert_mensuel`, `date_mise_a_jour`) VALUES
(1, 1, 500000.00, 1000000.00, 2000000.00, '2025-12-04 09:00:00'),
(2, 2, 300000.00, 500000.00, 1500000.00, '2026-01-06 08:00:00'),
(3, 3, 200000.00, 400000.00, 1000000.00, '2026-01-13 09:15:00'),
(4, 4, 1000000.00, 2000000.00, 5000000.00, '2026-01-21 10:30:00'),
(5, 5, 500000.00, 1000000.00, 3000000.00, '2026-02-02 13:00:00'),
(6, 6, 100000.00, 200000.00, 500000.00, '2026-02-09 07:45:00'),
(7, 7, 250000.00, 500000.00, 1200000.00, '2026-02-15 08:30:00'),
(8, 8, 50000.00, 100000.00, 200000.00, '2026-02-23 09:00:00'),
(9, 9, 50000.00, 100000.00, 150000.00, '2026-03-02 10:15:00'),
(10, 10, 500000.00, 800000.00, 2000000.00, '2026-03-06 12:20:00'),
(11, 11, 20000.00, 50000.00, 100000.00, '2026-03-11 14:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `nom_role` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `nom_role`, `description`) VALUES
(1, 'Admin', 'Accès complet au système et gestion des utilisateurs.'),
(2, 'Superviseur', 'Accès aux rapports et à la clôture de journée.'),
(3, 'Caissier', 'Accès aux opérations de guichet (Dépôt, Retrait, Transfert).'),
(4, 'Directeur', 'Responsable de l’agence, valide les opérations exceptionnelles.'),
(5, 'Auditeur', 'Consulte les journaux et effectue des contrôles.'),
(6, 'Comptable', 'Gère la comptabilité et les rapports financiers.'),
(7, 'Conseiller Clientèle', 'Accueil et conseil des clients, ouverture de comptes.'),
(8, 'Chef d’agence', 'Supervise l’ensemble des activités de l’agence.'),
(9, 'Technicien IT', 'Gère le système informatique et les accès.'),
(10, 'Stagiaire', 'En formation, accès limité en lecture seule.');

-- --------------------------------------------------------

--
-- Table structure for table `sessions_caisse`
--

CREATE TABLE `sessions_caisse` (
  `session_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `date_ouverture` date NOT NULL,
  `heure_ouverture` time NOT NULL,
  `heure_fermeture` time DEFAULT NULL,
  `solde_initial_caisse` decimal(18,2) NOT NULL,
  `solde_final_systeme` decimal(18,2) DEFAULT NULL,
  `solde_final_reel` decimal(18,2) DEFAULT NULL,
  `difference` decimal(18,2) DEFAULT NULL,
  `est_cloture` tinyint(1) DEFAULT 0,
  `date_cloture` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sessions_caisse`
--

INSERT INTO `sessions_caisse` (`session_id`, `utilisateur_id`, `date_ouverture`, `heure_ouverture`, `heure_fermeture`, `solde_initial_caisse`, `solde_final_systeme`, `solde_final_reel`, `difference`, `est_cloture`, `date_cloture`) VALUES
(1, 3, '2026-02-10', '08:00:00', '17:00:00', 500000.00, 785000.00, 785000.00, 0.00, 1, '2026-02-10 16:00:00'),
(2, 8, '2026-02-20', '09:00:00', '18:00:00', 600000.00, 950000.00, 950000.00, 0.00, 1, '2026-02-20 17:00:00'),
(3, 9, '2026-02-25', '10:00:00', '19:00:00', 400000.00, 720000.00, 720000.00, 0.00, 1, '2026-02-25 18:00:00'),
(4, 3, '2026-03-01', '08:00:00', '17:00:00', 500000.00, 530000.00, 530000.00, 0.00, 1, '2026-03-01 16:00:00'),
(5, 8, '2026-03-02', '09:00:00', '18:00:00', 600000.00, 800000.00, 800000.00, 0.00, 1, '2026-03-02 17:00:00'),
(6, 9, '2026-03-03', '10:00:00', '19:00:00', 400000.00, 650000.00, 650000.00, 0.00, 1, '2026-03-03 18:00:00'),
(7, 3, '2026-03-08', '08:00:00', '17:00:00', 500000.00, 820000.00, 820000.00, 0.00, 1, '2026-03-08 16:00:00'),
(8, 8, '2026-03-09', '09:00:00', '18:00:00', 600000.00, 910000.00, 910000.00, 0.00, 1, '2026-03-09 17:00:00'),
(9, 9, '2026-03-10', '10:00:00', '19:00:00', 400000.00, 680000.00, 680000.00, 0.00, 1, '2026-03-10 18:00:00'),
(10, 3, '2026-03-15', '08:00:00', NULL, 500000.00, NULL, NULL, NULL, 0, '2026-03-15 07:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `transaction_id` bigint(20) NOT NULL,
  `compte_source_id` int(11) DEFAULT NULL,
  `compte_destination_id` int(11) DEFAULT NULL,
  `type_transaction` varchar(50) NOT NULL,
  `montant` decimal(18,2) NOT NULL,
  `date_transaction` timestamp NOT NULL DEFAULT current_timestamp(),
  `utilisateur_id` int(11) NOT NULL,
  `statut` varchar(20) NOT NULL DEFAULT 'COMPLETÉ',
  `reference_externe` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`transaction_id`, `compte_source_id`, `compte_destination_id`, `type_transaction`, `montant`, `date_transaction`, `utilisateur_id`, `statut`, `reference_externe`) VALUES
(1, NULL, 1, 'DEPOT', 100000.00, '2025-12-06 08:07:11', 1, 'COMPLETÉ', 'TXN-20251206090711-3828'),
(2, NULL, 2, 'DEPOT', 50000.00, '2026-01-07 08:30:00', 3, 'COMPLETÉ', 'TXN-20260107093000-001'),
(3, 2, NULL, 'RETRAIT', 20000.00, '2026-01-10 13:15:00', 3, 'COMPLETÉ', 'TXN-20260110141500-002'),
(4, 1, 3, 'TRANSFERT', 15000.00, '2026-01-20 10:00:00', 3, 'COMPLETÉ', 'TXN-20260120110000-003'),
(5, 3, NULL, 'RETRAIT', 5000.00, '2026-02-01 09:30:00', 8, 'COMPLETÉ', 'TXN-20260201103000-004'),
(6, 4, NULL, 'DEPOT', 200000.00, '2026-02-05 15:20:00', 8, 'COMPLETÉ', 'TXN-20260205162000-005'),
(7, 5, 6, 'TRANSFERT', 100000.00, '2026-02-15 08:45:00', 8, 'COMPLETÉ', 'TXN-20260215094500-006'),
(8, 7, NULL, 'RETRAIT', 30000.00, '2026-02-20 11:00:00', 9, 'COMPLETÉ', 'TXN-20260220120000-007'),
(9, NULL, 8, 'DEPOT', 15000.00, '2026-02-25 14:10:00', 9, 'COMPLETÉ', 'TXN-20260225151000-008'),
(10, 9, NULL, 'DEPOT', 5000.00, '2026-03-04 07:30:00', 9, 'COMPLETÉ', 'TXN-20260304083000-009'),
(11, 10, 11, 'TRANSFERT', 25000.00, '2026-03-12 12:20:00', 3, 'COMPLETÉ', 'TXN-20260312132000-010'),
(12, 11, NULL, 'RETRAIT', 2000.00, '2026-03-16 16:45:00', 3, 'COMPLETÉ', 'TXN-20260316174500-011');

-- --------------------------------------------------------

--
-- Table structure for table `type_comptes`
--

CREATE TABLE `type_comptes` (
  `type_compte_id` int(11) NOT NULL,
  `nom_type` varchar(50) NOT NULL,
  `taux_interet` decimal(5,4) DEFAULT 0.0000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `type_comptes`
--

INSERT INTO `type_comptes` (`type_compte_id`, `nom_type`, `taux_interet`) VALUES
(1, 'Compte Courant', 0.0000),
(2, 'Compte Épargne', 0.0150),
(3, 'Compte Bloqué', 0.0250),
(4, 'Compte Joint', 0.0100),
(5, 'Compte Professionnel', 0.0050),
(6, 'Compte Jeune', 0.0200),
(7, 'Compte Étudiant', 0.0180),
(8, 'Compte Épargne Logement', 0.0220),
(9, 'Compte Titres', 0.0000),
(10, 'Compte Devises (USD)', 0.0010);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `utilisateur_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `identifiant` varchar(50) NOT NULL,
  `mot_de_passe_hash` varchar(255) NOT NULL,
  `nom_complet` varchar(100) NOT NULL,
  `est_actif` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`utilisateur_id`, `role_id`, `identifiant`, `mot_de_passe_hash`, `nom_complet`, `est_actif`, `date_creation`) VALUES
(1, 1, 'admin', '$2y$10$HXk5rIqQxVJlLHAYGR7yge5zHfJMwxghMp2Q11hwEZQNZMR58x.JS', 'Administrateur Principal', 1, '2025-11-07 19:11:30'),
(2, 4, 'directeur', '$2y$10$HXk5rIqQxVJlLHAYGR7yge5zHfJMwxghMp2Q11hwEZQNZMR58x.JS', 'Mballa Jean', 1, '2026-01-10 07:00:00'),
(3, 5, 'auditeur1', '$2y$10$HXk5rIqQxVJlLHAYGR7yge5zHfJMwxghMp2Q11hwEZQNZMR58x.JS', 'Ngo Bilong Claire', 1, '2026-01-15 08:30:00'),
(4, 6, 'comptable', '$2y$10$HXk5rIqQxVJlLHAYGR7yge5zHfJMwxghMp2Q11hwEZQNZMR58x.JS', 'Tchouamou Eric', 1, '2026-02-01 09:00:00'),
(5, 7, 'conseiller1', '$2y$10$HXk5rIqQxVJlLHAYGR7yge5zHfJMwxghMp2Q11hwEZQNZMR58x.JS', 'Amougou Marie', 1, '2026-02-05 10:15:00'),
(6, 8, 'chefagence', '$2y$10$HXk5rIqQxVJlLHAYGR7yge5zHfJMwxghMp2Q11hwEZQNZMR58x.JS', 'Essomba Paul', 1, '2026-02-10 07:45:00'),
(7, 9, 'tech1', '$2y$10$HXk5rIqQxVJlLHAYGR7yge5zHfJMwxghMp2Q11hwEZQNZMR58x.JS', 'Zoa Alain', 1, '2026-02-15 13:00:00'),
(8, 3, 'caissier2', '$2y$10$HXk5rIqQxVJlLHAYGR7yge5zHfJMwxghMp2Q11hwEZQNZMR58x.JS', 'Mekongo Sandrine', 1, '2026-02-20 08:00:00'),
(9, 3, 'caissier3', '$2y$10$HXk5rIqQxVJlLHAYGR7yge5zHfJMwxghMp2Q11hwEZQNZMR58x.JS', 'Nkoa Joseph', 1, '2026-02-25 09:30:00'),
(10, 10, 'stagiaire1', '$2y$10$HXk5rIqQxVJlLHAYGR7yge5zHfJMwxghMp2Q11hwEZQNZMR58x.JS', 'Atangana Luc', 1, '2026-03-01 07:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`),
  ADD UNIQUE KEY `numero_identite` (`numero_identite`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `comptes`
--
ALTER TABLE `comptes`
  ADD PRIMARY KEY (`compte_id`),
  ADD UNIQUE KEY `numero_compte` (`numero_compte`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `type_compte_id` (`type_compte_id`),
  ADD KEY `idx_comptes_suspendu` (`est_suspendu`);

--
-- Indexes for table `documents_kyc`
--
ALTER TABLE `documents_kyc`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `valide_par_utilisateur_id` (`valide_par_utilisateur_id`);

--
-- Indexes for table `historique_soldes`
--
ALTER TABLE `historique_soldes`
  ADD PRIMARY KEY (`historique_id`),
  ADD UNIQUE KEY `compte_id` (`compte_id`,`date_snapshot`);

--
-- Indexes for table `journal_audit`
--
ALTER TABLE `journal_audit`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Indexes for table `plafonds_comptes`
--
ALTER TABLE `plafonds_comptes`
  ADD PRIMARY KEY (`plafond_id`),
  ADD UNIQUE KEY `compte_id` (`compte_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `nom_role` (`nom_role`);

--
-- Indexes for table `sessions_caisse`
--
ALTER TABLE `sessions_caisse`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `compte_source_id` (`compte_source_id`),
  ADD KEY `compte_destination_id` (`compte_destination_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Indexes for table `type_comptes`
--
ALTER TABLE `type_comptes`
  ADD PRIMARY KEY (`type_compte_id`),
  ADD UNIQUE KEY `nom_type` (`nom_type`);

--
-- Indexes for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`utilisateur_id`),
  ADD UNIQUE KEY `identifiant` (`identifiant`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `comptes`
--
ALTER TABLE `comptes`
  MODIFY `compte_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `documents_kyc`
--
ALTER TABLE `documents_kyc`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `historique_soldes`
--
ALTER TABLE `historique_soldes`
  MODIFY `historique_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `journal_audit`
--
ALTER TABLE `journal_audit`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `plafonds_comptes`
--
ALTER TABLE `plafonds_comptes`
  MODIFY `plafond_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sessions_caisse`
--
ALTER TABLE `sessions_caisse`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `transaction_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `type_comptes`
--
ALTER TABLE `type_comptes`
  MODIFY `type_compte_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `utilisateur_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comptes`
--
ALTER TABLE `comptes`
  ADD CONSTRAINT `comptes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  ADD CONSTRAINT `comptes_ibfk_2` FOREIGN KEY (`type_compte_id`) REFERENCES `type_comptes` (`type_compte_id`);

--
-- Constraints for table `documents_kyc`
--
ALTER TABLE `documents_kyc`
  ADD CONSTRAINT `documents_kyc_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  ADD CONSTRAINT `documents_kyc_ibfk_2` FOREIGN KEY (`valide_par_utilisateur_id`) REFERENCES `utilisateurs` (`utilisateur_id`);

--
-- Constraints for table `historique_soldes`
--
ALTER TABLE `historique_soldes`
  ADD CONSTRAINT `historique_soldes_ibfk_1` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`compte_id`);

--
-- Constraints for table `journal_audit`
--
ALTER TABLE `journal_audit`
  ADD CONSTRAINT `journal_audit_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`utilisateur_id`);

--
-- Constraints for table `plafonds_comptes`
--
ALTER TABLE `plafonds_comptes`
  ADD CONSTRAINT `plafonds_comptes_ibfk_1` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`compte_id`);

--
-- Constraints for table `sessions_caisse`
--
ALTER TABLE `sessions_caisse`
  ADD CONSTRAINT `sessions_caisse_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`utilisateur_id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`compte_source_id`) REFERENCES `comptes` (`compte_id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`compte_destination_id`) REFERENCES `comptes` (`compte_id`),
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`utilisateur_id`);

--
-- Constraints for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
