-- ITAM Database Schema V2
-- Target: MySQL 8+ / MariaDB 10.4+
-- Purpose: clean V2 architecture for the active project database.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS itam_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE itam_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS stock_etat_historique;
DROP TABLE IF EXISTS equipement_etat_historique;
DROP TABLE IF EXISTS utilisateur_audits;
DROP TABLE IF EXISTS utilisateur_accessoires;
DROP TABLE IF EXISTS attributions_quantite;
DROP TABLE IF EXISTS stock_etats;
DROP TABLE IF EXISTS stocks;
DROP TABLE IF EXISTS valeurs_attributs;
DROP TABLE IF EXISTS categorie_age_rules;
DROP TABLE IF EXISTS categorie_attribut_options;
DROP TABLE IF EXISTS attributs;
DROP TABLE IF EXISTS mouvements;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS validations_demandes;
DROP TABLE IF EXISTS demandes;
DROP TABLE IF EXISTS historique_equipements;
DROP TABLE IF EXISTS attributions;
DROP TABLE IF EXISTS stocks_quantitatifs;
DROP TABLE IF EXISTS valeurs_caracteristiques_stocks;
DROP TABLE IF EXISTS valeurs_caracteristiques_equipements;
DROP TABLE IF EXISTS regles_vie_categories;
DROP TABLE IF EXISTS options_listes;
DROP TABLE IF EXISTS caracteristiques_categories;
DROP TABLE IF EXISTS equipements;
DROP TABLE IF EXISTS types_equipement;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS categories_equipements;
DROP TABLE IF EXISTS validateurs_autorises;
DROP TABLE IF EXISTS users_system;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS utilisateurs;
DROP TABLE IF EXISTS fonctions_metier;
DROP TABLE IF EXISTS roles_systeme;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles_systeme (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE fonctions_metier (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    peut_valider TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE utilisateurs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom_complet VARCHAR(150) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    matricule VARCHAR(50) NULL UNIQUE,
    telephone VARCHAR(40) NULL,
    direction VARCHAR(120) NULL,
    departement VARCHAR(120) NULL,
    service VARCHAR(120) NULL,
    agence VARCHAR(120) NULL,
    role_systeme_id INT UNSIGNED NOT NULL,
    fonction_metier_id INT UNSIGNED NOT NULL,
    actif TINYINT(1) NOT NULL DEFAULT 1,
    doit_changer_mot_de_passe TINYINT(1) NOT NULL DEFAULT 1,
    mot_de_passe_genere_at TIMESTAMP NULL,
    mot_de_passe_change_at TIMESTAMP NULL,
    dernier_login TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_utilisateurs_role_systeme FOREIGN KEY (role_systeme_id) REFERENCES roles_systeme(id),
    CONSTRAINT fk_utilisateurs_fonction_metier FOREIGN KEY (fonction_metier_id) REFERENCES fonctions_metier(id),
    INDEX idx_utilisateurs_nom (nom_complet),
    INDEX idx_utilisateurs_matricule (matricule),
    INDEX idx_utilisateurs_direction (direction),
    INDEX idx_utilisateurs_departement (departement),
    INDEX idx_utilisateurs_service (service),
    INDEX idx_utilisateurs_agence (agence),
    INDEX idx_utilisateurs_actif (actif)
) ENGINE=InnoDB;

CREATE TABLE validateurs_autorises (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    validateur_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_validateurs_autorises_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    CONSTRAINT fk_validateurs_autorises_validateur FOREIGN KEY (validateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    UNIQUE KEY uq_validateur_autorise (utilisateur_id, validateur_id),
    INDEX idx_validateurs_autorises_validateur (validateur_id)
) ENGINE=InnoDB;

CREATE TABLE categories_equipements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    type_gestion ENUM('unique', 'quantite') NOT NULL,
    visible_dans_demandes TINYINT(1) NOT NULL DEFAULT 1,
    duree_vie_normale INT UNSIGNED NULL,
    seuil_neuf INT UNSIGNED NULL,
    seuil_bon INT UNSIGNED NULL,
    seuil_moyen INT UNSIGNED NULL,
    seuil_mauvais INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE caracteristiques_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT UNSIGNED NOT NULL,
    nom VARCHAR(120) NOT NULL,
    type_champ ENUM('texte', 'nombre', 'date', 'liste', 'textarea', 'boolean') NOT NULL DEFAULT 'texte',
    obligatoire TINYINT(1) NOT NULL DEFAULT 0,
    visible_dans_demandes TINYINT(1) NOT NULL DEFAULT 0,
    ordre_affichage INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_caracteristiques_categories_categorie FOREIGN KEY (categorie_id) REFERENCES categories_equipements(id) ON DELETE CASCADE,
    UNIQUE KEY uq_caracteristique_categorie_nom (categorie_id, nom),
    INDEX idx_caracteristiques_categories_ordre (categorie_id, ordre_affichage)
) ENGINE=InnoDB;

CREATE TABLE options_listes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    caracteristique_id INT UNSIGNED NOT NULL,
    valeur VARCHAR(150) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_options_listes_caracteristique FOREIGN KEY (caracteristique_id) REFERENCES caracteristiques_categories(id) ON DELETE CASCADE,
    UNIQUE KEY uq_option_liste (caracteristique_id, valeur)
) ENGINE=InnoDB;

CREATE TABLE regles_vie_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT UNSIGNED NOT NULL,
    age_min DECIMAL(6,2) NOT NULL DEFAULT 0,
    age_max DECIMAL(6,2) NULL,
    etat_theorique ENUM('neuf', 'bon', 'moyen', 'mauvais', 'declasse') NOT NULL,
    ordre_affichage INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_regles_vie_categories_categorie FOREIGN KEY (categorie_id) REFERENCES categories_equipements(id) ON DELETE CASCADE,
    INDEX idx_regles_vie_categories_ordre (categorie_id, ordre_affichage)
) ENGINE=InnoDB;

CREATE TABLE equipements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT UNSIGNED NOT NULL,
    code_inventaire VARCHAR(120) NULL UNIQUE,
    serial_number VARCHAR(150) NULL UNIQUE,
    designation VARCHAR(255) NULL,
    modele VARCHAR(255) NULL,
    marque VARCHAR(255) NULL,
    date_achat DATE NULL,
    date_mise_service DATE NULL,
    date_fiabilite ENUM('exacte', 'approximative', 'inconnue') NOT NULL DEFAULT 'inconnue',
    annee_estimee SMALLINT UNSIGNED NULL,
    statut ENUM('disponible', 'attribue', 'maintenance', 'declasse') NOT NULL DEFAULT 'disponible',
    etat ENUM('neuf', 'bon', 'moyen', 'mauvais', 'declasse') NOT NULL DEFAULT 'neuf',
    etat_manuel TINYINT(1) NOT NULL DEFAULT 0,
    commentaire_etat TEXT NULL,
    emplacement VARCHAR(255) NULL,
    fournisseur VARCHAR(255) NULL,
    facture_reference VARCHAR(255) NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipements_categorie_v2 FOREIGN KEY (categorie_id) REFERENCES categories_equipements(id),
    CONSTRAINT fk_equipements_created_by FOREIGN KEY (created_by) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    CONSTRAINT chk_equipement_dates CHECK (date_achat IS NULL OR date_mise_service IS NULL OR date_mise_service >= date_achat),
    INDEX idx_equipements_categorie (categorie_id),
    INDEX idx_equipements_statut (statut),
    INDEX idx_equipements_etat (etat),
    INDEX idx_equipements_marque (marque)
) ENGINE=InnoDB;

CREATE TABLE valeurs_caracteristiques_equipements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipement_id INT UNSIGNED NOT NULL,
    caracteristique_id INT UNSIGNED NOT NULL,
    valeur TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_valeurs_caracteristiques_equipement FOREIGN KEY (equipement_id) REFERENCES equipements(id) ON DELETE CASCADE,
    CONSTRAINT fk_valeurs_caracteristiques_caracteristique FOREIGN KEY (caracteristique_id) REFERENCES caracteristiques_categories(id) ON DELETE CASCADE,
    UNIQUE KEY uq_valeur_caracteristique_equipement (equipement_id, caracteristique_id)
) ENGINE=InnoDB;

CREATE TABLE stocks_quantitatifs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT UNSIGNED NOT NULL,
    designation VARCHAR(255) NOT NULL,
    quantite_totale INT UNSIGNED NOT NULL DEFAULT 0,
    quantite_disponible INT UNSIGNED NOT NULL DEFAULT 0,
    quantite_attribuee INT UNSIGNED NOT NULL DEFAULT 0,
    quantite_maintenance INT UNSIGNED NOT NULL DEFAULT 0,
    quantite_mauvais_etat INT UNSIGNED NOT NULL DEFAULT 0,
    quantite_declassee INT UNSIGNED NOT NULL DEFAULT 0,
    date_reception DATE NULL,
    emplacement VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_stocks_quantitatifs_categorie FOREIGN KEY (categorie_id) REFERENCES categories_equipements(id) ON DELETE CASCADE,
    INDEX idx_stocks_quantitatifs_categorie (categorie_id)
) ENGINE=InnoDB;

CREATE TABLE valeurs_caracteristiques_stocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_quantitatif_id INT UNSIGNED NOT NULL,
    caracteristique_id INT UNSIGNED NOT NULL,
    valeur TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_valeurs_caracteristiques_stocks_stock FOREIGN KEY (stock_quantitatif_id) REFERENCES stocks_quantitatifs(id) ON DELETE CASCADE,
    CONSTRAINT fk_valeurs_caracteristiques_stocks_caracteristique FOREIGN KEY (caracteristique_id) REFERENCES caracteristiques_categories(id) ON DELETE CASCADE,
    UNIQUE KEY uq_valeur_caracteristique_stock (stock_quantitatif_id, caracteristique_id)
) ENGINE=InnoDB;

CREATE TABLE attributions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    demande_id INT UNSIGNED NULL,
    equipement_id INT UNSIGNED NULL,
    stock_quantitatif_id INT UNSIGNED NULL,
    utilisateur_id INT UNSIGNED NOT NULL,
    quantite INT UNSIGNED NOT NULL DEFAULT 1,
    date_attribution TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_retour TIMESTAMP NULL,
    statut ENUM('active', 'terminee') NOT NULL DEFAULT 'active',
    commentaire TEXT NULL,
    attribue_par INT UNSIGNED NULL,
    CONSTRAINT fk_attributions_equipement FOREIGN KEY (equipement_id) REFERENCES equipements(id) ON DELETE CASCADE,
    CONSTRAINT fk_attributions_stock_quantitatif FOREIGN KEY (stock_quantitatif_id) REFERENCES stocks_quantitatifs(id) ON DELETE CASCADE,
    CONSTRAINT fk_attributions_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    CONSTRAINT fk_attributions_attribue_par FOREIGN KEY (attribue_par) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    CONSTRAINT chk_attribution_cible CHECK (
        (equipement_id IS NOT NULL AND stock_quantitatif_id IS NULL)
        OR (equipement_id IS NULL AND stock_quantitatif_id IS NOT NULL)
    ),
    INDEX idx_attributions_utilisateur_statut (utilisateur_id, statut),
    INDEX idx_attributions_equipement_statut (equipement_id, statut),
    INDEX idx_attributions_stock_statut (stock_quantitatif_id, statut),
    INDEX idx_attributions_demande (demande_id)
) ENGINE=InnoDB;

CREATE TABLE historique_equipements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipement_id INT UNSIGNED NULL,
    stock_quantitatif_id INT UNSIGNED NULL,
    type_operation ENUM('creation', 'attribution', 'transfert', 'maintenance', 'retour_stock', 'declassement', 'modification_etat') NOT NULL,
    utilisateur_source_id INT UNSIGNED NULL,
    utilisateur_destination_id INT UNSIGNED NULL,
    source_type ENUM('fournisseur', 'depot', 'warehouse', 'utilisateur', 'site', 'autre') NULL,
    source_label VARCHAR(255) NULL,
    destination_type ENUM('fournisseur', 'depot', 'warehouse', 'utilisateur', 'site', 'autre') NULL,
    destination_label VARCHAR(255) NULL,
    quantite INT UNSIGNED NOT NULL DEFAULT 1,
    commentaire TEXT NULL,
    effectue_par INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historique_equipement FOREIGN KEY (equipement_id) REFERENCES equipements(id) ON DELETE CASCADE,
    CONSTRAINT fk_historique_stock_quantitatif FOREIGN KEY (stock_quantitatif_id) REFERENCES stocks_quantitatifs(id) ON DELETE CASCADE,
    CONSTRAINT fk_historique_source FOREIGN KEY (utilisateur_source_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    CONSTRAINT fk_historique_destination FOREIGN KEY (utilisateur_destination_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    CONSTRAINT fk_historique_effectue_par FOREIGN KEY (effectue_par) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_historique_equipement_date (equipement_id, created_at),
    INDEX idx_historique_stock_date (stock_quantitatif_id, created_at),
    INDEX idx_historique_operation_date (type_operation, created_at)
) ENGINE=InnoDB;

CREATE TABLE demandes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    demandeur_id INT UNSIGNED NOT NULL,
    validateur_id INT UNSIGNED NOT NULL,
    categorie_id INT UNSIGNED NULL,
    type_demande ENUM('nouvel_equipement', 'remplacement', 'maintenance', 'accessoire') NOT NULL,
    justification TEXT NOT NULL,
    urgence ENUM('faible', 'normale', 'haute') NOT NULL DEFAULT 'normale',
    statut ENUM('brouillon', 'soumis', 'validation_responsable', 'validation_it', 'approuve', 'rejete', 'attribue', 'cloture') NOT NULL DEFAULT 'soumis',
    commentaire_validation TEXT NULL,
    date_validation_responsable TIMESTAMP NULL,
    date_validation_it TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_demandes_demandeur FOREIGN KEY (demandeur_id) REFERENCES utilisateurs(id),
    CONSTRAINT fk_demandes_validateur_v2 FOREIGN KEY (validateur_id) REFERENCES utilisateurs(id),
    CONSTRAINT fk_demandes_categorie FOREIGN KEY (categorie_id) REFERENCES categories_equipements(id) ON DELETE SET NULL,
    INDEX idx_demandes_demandeur (demandeur_id),
    INDEX idx_demandes_validateur_statut (validateur_id, statut),
    INDEX idx_demandes_statut_created (statut, created_at)
) ENGINE=InnoDB;

ALTER TABLE attributions
    ADD CONSTRAINT fk_attributions_demande
        FOREIGN KEY (demande_id) REFERENCES demandes(id) ON DELETE SET NULL;

CREATE TABLE validations_demandes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    demande_id INT UNSIGNED NOT NULL,
    validateur_id INT UNSIGNED NOT NULL,
    niveau ENUM('responsable', 'manager_it') NOT NULL,
    decision ENUM('approuve', 'rejete') NOT NULL,
    commentaire TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_validations_demandes_demande FOREIGN KEY (demande_id) REFERENCES demandes(id) ON DELETE CASCADE,
    CONSTRAINT fk_validations_demandes_validateur FOREIGN KEY (validateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    UNIQUE KEY uq_validation_demande_niveau (demande_id, niveau),
    INDEX idx_validations_demandes_validateur (validateur_id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'information',
    lien VARCHAR(255) NULL,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_notifications_utilisateur_lu (utilisateur_id, lu),
    INDEX idx_notifications_created (created_at)
) ENGINE=InnoDB;

INSERT INTO roles_systeme (nom, description) VALUES
('admin', 'Administrateur principal du systeme'),
('agent_it', 'Agent IT Asset Management'),
('utilisateur_standard', 'Utilisateur standard');

INSERT INTO fonctions_metier (nom, peut_valider) VALUES
('Directeur', 1),
('Manager IT', 1),
('Chef de departement', 1),
('Chef de service', 1),
('Employe', 0);

-- Password: Admin@123
INSERT INTO utilisateurs (
    nom_complet, username, email, mot_de_passe, matricule, direction, departement, service, agence,
    role_systeme_id, fonction_metier_id, doit_changer_mot_de_passe
) VALUES (
    'Administrateur ITAM',
    'admin',
    NULL,
    '$2y$10$ggs0bsCiXAbSz0B0xO3dX.qdjYrjAT.wDhwZyuiKDlmfQcbHLyP7i',
    'ADMIN',
    'IT',
    'IT Asset',
    'IT Asset Management',
    'Siege',
    (SELECT id FROM roles_systeme WHERE nom = 'admin'),
    (SELECT id FROM fonctions_metier WHERE nom = 'Manager IT'),
    0
);
