-- ITAM Database Schema
-- Target: MySQL 8+

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS itam_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE itam_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS valeurs_attributs;
DROP TABLE IF EXISTS attributions_quantite;
DROP TABLE IF EXISTS stock_etats;
DROP TABLE IF EXISTS stocks;
DROP TABLE IF EXISTS attributs;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS mouvements;
DROP TABLE IF EXISTS demandes;
DROP TABLE IF EXISTS equipements;
DROP TABLE IF EXISTS types_equipement;
DROP TABLE IF EXISTS utilisateurs;
DROP TABLE IF EXISTS utilisateur_accessoires;
DROP TABLE IF EXISTS utilisateur_audits;
DROP TABLE IF EXISTS users_system;
DROP TABLE IF EXISTS roles;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE users_system (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_system_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL UNIQUE,
    mode_gestion ENUM('unique', 'quantite') NOT NULL DEFAULT 'unique',
    normal_life_years INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE utilisateurs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    matricule VARCHAR(50) NOT NULL UNIQUE,
    telephone VARCHAR(40) NULL,
    direction VARCHAR(120) NULL,
    departement VARCHAR(120) NULL,
    service VARCHAR(120) NULL,
    site VARCHAR(120) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_utilisateurs_nom (nom),
    INDEX idx_utilisateurs_departement (departement),
    INDEX idx_utilisateurs_site (site)
) ENGINE=InnoDB;

INSERT INTO utilisateurs (nom, matricule, direction, departement, service, site) VALUES
('STOCK_IT', 'STOCK_IT', 'Dépôt IT', 'IT', 'Stock', 'Dépôt IT');

CREATE TABLE utilisateur_accessoires (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    accessoire_nom VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_accessoire_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_user_accessoire_user (utilisateur_id)
) ENGINE=InnoDB;

CREATE TABLE utilisateur_audits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    action VARCHAR(40) NOT NULL,
    details TEXT NOT NULL,
    actor_username VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_utilisateur_audits_user (utilisateur_id)
) ENGINE=InnoDB;

CREATE TABLE types_equipement (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE equipements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT UNSIGNED NULL,
    type_id INT UNSIGNED NULL,
    serial_number VARCHAR(120) NULL UNIQUE,
    hostname VARCHAR(120) NULL,
    marque VARCHAR(120) NULL,
    utilisateur_id INT UNSIGNED NULL,
    statut ENUM('disponible', 'attribue', 'maintenance', 'declasse', 'hors_service') NOT NULL DEFAULT 'disponible',
    etat ENUM('neuf', 'bon', 'moyen', 'mauvais', 'declasse') NOT NULL DEFAULT 'bon',
    etat_theorique ENUM('neuf', 'bon', 'moyen', 'mauvais', 'a_declasser') NOT NULL DEFAULT 'bon',
    date_enregistrement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_achat DATE NULL,
    date_mise_service DATE NULL,
    date_fiabilite ENUM('exacte', 'approximative', 'inconnue') NOT NULL DEFAULT 'inconnue',
    annee_estimee INT UNSIGNED NULL,
    archived_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipements_categorie FOREIGN KEY (categorie_id) REFERENCES categories(id),
    CONSTRAINT fk_equipements_type FOREIGN KEY (type_id) REFERENCES types_equipement(id),
    CONSTRAINT fk_equipements_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    INDEX idx_equipements_hostname (hostname),
    INDEX idx_equipements_statut (statut),
    INDEX idx_equipements_etat (etat),
    INDEX idx_equipements_etat_theorique (etat_theorique),
    INDEX idx_equipements_archived (archived_at)
) ENGINE=InnoDB;

CREATE TABLE attributs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'texte',
    required TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    categorie_id INT UNSIGNED NULL,
    type_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attributs_categorie FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_attributs_type FOREIGN KEY (type_id) REFERENCES types_equipement(id),
    UNIQUE KEY uq_attribut_categorie_nom (categorie_id, nom)
) ENGINE=InnoDB;

CREATE TABLE categorie_attribut_options (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attribut_id INT UNSIGNED NOT NULL,
    label VARCHAR(160) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categorie_attribut_options_attribut FOREIGN KEY (attribut_id) REFERENCES attributs(id) ON DELETE CASCADE,
    UNIQUE KEY uq_attr_option (attribut_id, label)
) ENGINE=InnoDB;

CREATE TABLE categorie_age_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT UNSIGNED NOT NULL,
    min_years DECIMAL(6,2) NULL,
    max_years DECIMAL(6,2) NULL,
    theoretical_state ENUM('neuf', 'bon', 'moyen', 'mauvais', 'declasse') NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categorie_age_rules_category FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category_age_rules_category (categorie_id)
) ENGINE=InnoDB;

CREATE TABLE valeurs_attributs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipement_id INT UNSIGNED NULL,
    stock_id INT UNSIGNED NULL,
    attribut_id INT UNSIGNED NOT NULL,
    valeur VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_valeurs_equipement FOREIGN KEY (equipement_id) REFERENCES equipements(id) ON DELETE CASCADE,
    CONSTRAINT fk_valeurs_attribut FOREIGN KEY (attribut_id) REFERENCES attributs(id) ON DELETE CASCADE,
    UNIQUE KEY uq_valeur_equipement_attribut (equipement_id, stock_id, attribut_id)
) ENGINE=InnoDB;

CREATE TABLE stocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT UNSIGNED NOT NULL,
    date_enregistrement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_achat DATE NULL,
    date_mise_service DATE NULL,
    date_fiabilite ENUM('exacte', 'approximative', 'inconnue') NOT NULL DEFAULT 'inconnue',
    annee_estimee INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_stocks_categorie FOREIGN KEY (categorie_id) REFERENCES categories(id),
    INDEX idx_stocks_categorie (categorie_id)
) ENGINE=InnoDB;

CREATE TABLE stock_etats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_id INT UNSIGNED NOT NULL,
    etat ENUM('neuf', 'bon', 'mauvais', 'declasse') NOT NULL,
    quantite INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_etats_stock FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE,
    UNIQUE KEY uq_stock_etat (stock_id, etat)
) ENGINE=InnoDB;

ALTER TABLE valeurs_attributs
    ADD CONSTRAINT fk_valeurs_stock FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE;

CREATE TABLE attributions_quantite (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    stock_id INT UNSIGNED NOT NULL,
    etat ENUM('neuf', 'bon', 'mauvais', 'declasse') NOT NULL,
    quantite INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_attributions_quantite_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    CONSTRAINT fk_attributions_quantite_stock FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE,
    INDEX idx_attributions_quantite_user (utilisateur_id),
    INDEX idx_attributions_quantite_stock (stock_id)
) ENGINE=InnoDB;

CREATE TABLE mouvements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipement_id INT UNSIGNED NULL,
    stock_id INT UNSIGNED NULL,
    type_mouvement ENUM('attribution', 'transfert', 'retour', 'maintenance', 'declassement') NOT NULL,
    utilisateur_source_id INT UNSIGNED NULL,
    utilisateur_destination_id INT UNSIGNED NULL,
    quantite INT UNSIGNED NULL,
    etat ENUM('neuf', 'bon', 'mauvais', 'declasse') NULL,
    source_type VARCHAR(30) NULL,
    source_label VARCHAR(160) NULL,
    destination_type VARCHAR(30) NULL,
    destination_label VARCHAR(160) NULL,
    date_mouvement DATETIME NOT NULL,
    commentaire TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mouvements_equipement FOREIGN KEY (equipement_id) REFERENCES equipements(id),
    CONSTRAINT fk_mouvements_stock FOREIGN KEY (stock_id) REFERENCES stocks(id),
    CONSTRAINT fk_mouvements_source FOREIGN KEY (utilisateur_source_id) REFERENCES utilisateurs(id),
    CONSTRAINT fk_mouvements_destination FOREIGN KEY (utilisateur_destination_id) REFERENCES utilisateurs(id),
    INDEX idx_mouvements_equipement_date (equipement_id, date_mouvement),
    INDEX idx_mouvements_stock_date (stock_id, date_mouvement),
    INDEX idx_mouvements_source (utilisateur_source_id),
    INDEX idx_mouvements_destination (utilisateur_destination_id)
) ENGINE=InnoDB;

CREATE TABLE demandes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    type_demande VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    statut ENUM('en_attente', 'validee', 'refusee') NOT NULL DEFAULT 'en_attente',
    date_demande DATETIME NOT NULL,
    demandeur_nom VARCHAR(150) NULL,
    demandeur_matricule VARCHAR(50) NULL,
    demandeur_statut VARCHAR(40) NULL,
    demandeur_direction VARCHAR(120) NULL,
    demandeur_departement VARCHAR(120) NULL,
    demandeur_service VARCHAR(120) NULL,
    demandeur_site VARCHAR(120) NULL,
    nature_demande VARCHAR(40) NULL,
    equipement_categorie VARCHAR(80) NULL,
    equipement_type_ordinateur VARCHAR(40) NULL,
    accessoires_json TEXT NULL,
    souris_type VARCHAR(20) NULL,
    nom_chef VARCHAR(150) NULL,
    nom_manager_validation VARCHAR(150) NULL,
    date_signature_demandeur DATE NULL,
    date_signature_chef DATE NULL,
    date_signature_manager DATE NULL,
    signed_file_path VARCHAR(255) NULL,
    validated_at DATETIME NULL,
    validateur_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_demandes_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    CONSTRAINT fk_demandes_validateur FOREIGN KEY (validateur_id) REFERENCES users_system(id),
    INDEX idx_demandes_statut (statut),
    INDEX idx_demandes_date (date_demande)
) ENGINE=InnoDB;

INSERT INTO roles (nom) VALUES
('Admin'),
('IT Agent');

-- Password: Admin@123
INSERT INTO users_system (username, password, role_id) VALUES
('admin', '$2y$10$ggs0bsCiXAbSz0B0xO3dX.qdjYrjAT.wDhwZyuiKDlmfQcbHLyP7i', 1);

INSERT INTO types_equipement (nom) VALUES
('Ordinateur Portable'),
('Ordinateur Bureau'),
('Ecran'),
('Imprimante'),
('Telephone IP');

INSERT INTO attributs (nom, type_id) VALUES
('RAM', 1),
('Stockage', 1),
('CPU', 1),
('RAM', 2),
('Stockage', 2),
('CPU', 2),
('Taille', 3),
('Resolution', 3),
('Type impression', 4),
('Vitesse ppm', 4),
('Extension', 5),
('Adresse MAC', 5);
