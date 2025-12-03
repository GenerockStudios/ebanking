# Client 
# Caissier
# Administrateur



-- ******************************************************
-- 1. Gestion des Utilisateurs et Sécurité (3 tables)
-- ******************************************************

-- 1/11 : Table des Rôles (RBAC)
CREATE TABLE Roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    nom_role VARCHAR(50) NOT NULL UNIQUE, -- Ex: 'Admin', 'Caissier'
    description TEXT
);

-- 2/11 : Table des Utilisateurs du Système
CREATE TABLE Utilisateurs (
    utilisateur_id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    identifiant VARCHAR(50) NOT NULL UNIQUE,
    mot_de_passe_hash VARCHAR(255) NOT NULL,
    nom_complet VARCHAR(100) NOT NULL,
    est_actif BOOLEAN DEFAULT TRUE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (role_id) REFERENCES Roles(role_id)
);

-- 3/11 : Table de Journalisation des Actions (Audit Trail)
CREATE TABLE Journal_Audit (
    log_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    date_heure TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utilisateur_id INT, -- Qui a effectué l'action
    type_action VARCHAR(50) NOT NULL,
    table_affectee VARCHAR(50),
    identifiant_element_affecte VARCHAR(50),
    details TEXT,
    
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateurs(utilisateur_id)
);

-- ******************************************************
-- 2. Gestion des Clients et des Comptes (3 tables)
-- ******************************************************

-- 4/11 : Table des Clients (KYC Basique)
CREATE TABLE Clients (
    client_id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE,
    adresse TEXT,
    telephone VARCHAR(20),
    email VARCHAR(100) UNIQUE,
    numero_identite VARCHAR(50) UNIQUE NOT NULL, -- CNI, Passeport
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5/11 : Table des Types de Comptes
CREATE TABLE Type_Comptes (
    type_compte_id INT PRIMARY KEY AUTO_INCREMENT,
    nom_type VARCHAR(50) NOT NULL UNIQUE, -- Ex: 'Courant', 'Épargne'
    taux_interet DECIMAL(5, 4) DEFAULT 0.0000
);

-- 6/11 : Table des Comptes Bancaires
CREATE TABLE Comptes (
    compte_id INT PRIMARY KEY AUTO_INCREMENT,
    numero_compte VARCHAR(20) NOT NULL UNIQUE,
    client_id INT NOT NULL,
    type_compte_id INT NOT NULL,
    solde DECIMAL(18, 2) DEFAULT 0.00, -- Solde actuel
    date_ouverture TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    est_actif BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (client_id) REFERENCES Clients(client_id),
    FOREIGN KEY (type_compte_id) REFERENCES Type_Comptes(type_compte_id)
);

-- ******************************************************
-- 3. Gestion des Opérations Courantes (2 tables)
-- ******************************************************

-- 7/11 : Table des Transactions (Historique des Mouvements)
CREATE TABLE Transactions (
    transaction_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    compte_source_id INT, -- NULL pour les dépôts
    compte_destination_id INT, -- NULL pour les retraits
    type_transaction VARCHAR(50) NOT NULL, -- Ex: 'DEPOT', 'RETRAIT', 'TRANSFERT_INT'
    montant DECIMAL(18, 2) NOT NULL,
    date_transaction TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    utilisateur_id INT NOT NULL, -- Caissier/Utilisateur ayant initié
    statut VARCHAR(20) NOT NULL DEFAULT 'COMPLETÉ',
    reference_externe VARCHAR(100),

    CHECK (compte_source_id IS NOT NULL OR compte_destination_id IS NOT NULL),
    
    FOREIGN KEY (compte_source_id) REFERENCES Comptes(compte_id),
    FOREIGN KEY (compte_destination_id) REFERENCES Comptes(compte_id),
    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateurs(utilisateur_id)
);

-- 8/11 : Table des Sessions/Journées de Caisse
CREATE TABLE Sessions_Caisse (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    utilisateur_id INT NOT NULL,
    date_ouverture DATE NOT NULL,
    heure_ouverture TIME NOT NULL,
    heure_fermeture TIME,
    solde_initial_caisse DECIMAL(18, 2) NOT NULL,
    solde_final_systeme DECIMAL(18, 2),
    solde_final_reel DECIMAL(18, 2),
    difference DECIMAL(18, 2),
    est_cloture BOOLEAN DEFAULT FALSE,
    date_cloture TIMESTAMP,

    FOREIGN KEY (utilisateur_id) REFERENCES Utilisateurs(utilisateur_id)
);

-- ******************************************************
-- 4. Tables Supplémentaires Critiques (3 tables)
-- ******************************************************

-- 9/11 : Documents_KYC (Conformité réglementaire)
CREATE TABLE Documents_KYC (
    document_id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    type_document VARCHAR(50) NOT NULL, -- Ex: 'CNI', 'Justificatif Domicile'
    reference_fichier VARCHAR(255) NOT NULL,
    date_expiration DATE,
    valide_par_utilisateur_id INT,
    date_validation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    est_valide BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (client_id) REFERENCES Clients(client_id),
    FOREIGN KEY (valide_par_utilisateur_id) REFERENCES Utilisateurs(utilisateur_id)
);

-- 10/11 : Plafonds_Comptes (Sécurité et gestion des risques)
CREATE TABLE Plafonds_Comptes (
    plafond_id INT PRIMARY KEY AUTO_INCREMENT,
    compte_id INT NOT NULL UNIQUE, -- Un seul ensemble de plafonds par compte
    plafond_retrait_journalier DECIMAL(18, 2) NOT NULL,
    plafond_depot_journalier DECIMAL(18, 2) NOT NULL,
    plafond_transfert_mensuel DECIMAL(18, 2) NOT NULL,
    date_mise_a_jour TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (compte_id) REFERENCES Comptes(compte_id)
);

-- 11/11 : Historique_Soldes (Performance et Reporting Audit)
CREATE TABLE Historique_Soldes (
    historique_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    compte_id INT NOT NULL,
    date_snapshot DATE NOT NULL,
    solde_final DECIMAL(18, 2) NOT NULL,
    
    UNIQUE (compte_id, date_snapshot),
    
    FOREIGN KEY (compte_id) REFERENCES Comptes(compte_id)
);




------------------------------------------
DONNEES DE BASES



-- Insertion des Rôles (selon les références dans le code PHP)
INSERT INTO Roles (role_id, nom_role, description) VALUES
(1, 'Admin', 'Accès complet au système et gestion des utilisateurs.'),
(2, 'Superviseur', 'Accès aux rapports et à la clôture de journée.'),
(3, 'Caissier', 'Accès aux opérations de guichet (Dépôt, Retrait, Transfert).');

-- Insertion des Types de Comptes
INSERT INTO Type_Comptes (nom_type, taux_interet) VALUES
('Compte Courant', 0.0000),
('Compte Épargne', 0.0150); -- Exemple de taux 1.5%


-- Création de l'Admin initial (identifiant: 'admin', Remplacez le HASH)
SET @admin_password_hash = '$2y$10$HXk5rIqQxVJlLHAYGR7yge5zHfJMwxghMp2Q11hwEZQNZMR58x.JS'; 

INSERT INTO Utilisateurs (role_id, identifiant, mot_de_passe_hash, nom_complet) VALUES
(1, 'admin', @admin_password_hash, 'Administrateur Principal');


ATTENTION : Remplacez le mot_de_passe_hash ci-dessous par un hash réel généré par PHP (Exemple: password_hash('MonMotDePasseSuperSécurisé!', PASSWORD_BCRYPT);). L'exemple ci-dessous est juste un placeholder.