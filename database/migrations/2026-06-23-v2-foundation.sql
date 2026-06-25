-- Migration: ITAM V2 foundation
-- Purpose: prepare the current database for the new IT Asset Management architecture.
-- This migration is intentionally non-destructive: it adds tables/columns without
-- renaming or dropping the current application tables.

USE itam_db;

CREATE TABLE IF NOT EXISTS roles_systeme (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fonctions_metier (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    peut_valider TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO roles_systeme (nom, description) VALUES
('admin', 'Administrateur principal du systeme'),
('agent_it', 'Agent IT Asset Management'),
('utilisateur_standard', 'Utilisateur standard')
ON DUPLICATE KEY UPDATE description = VALUES(description);

INSERT INTO fonctions_metier (nom, peut_valider) VALUES
('Directeur', 1),
('Chef de departement', 1),
('Chef de service', 1),
('Employe', 0)
ON DUPLICATE KEY UPDATE peut_valider = VALUES(peut_valider);

ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS nom_complet VARCHAR(150) NULL AFTER id;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS username VARCHAR(100) NULL AFTER nom_complet;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS email VARCHAR(150) NULL AFTER username;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS mot_de_passe VARCHAR(255) NULL AFTER email;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS agence VARCHAR(120) NULL AFTER site;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS role_systeme_id INT UNSIGNED NULL AFTER agence;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS fonction_metier_id INT UNSIGNED NULL AFTER role_systeme_id;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS actif TINYINT(1) NOT NULL DEFAULT 1 AFTER fonction_metier_id;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS dernier_login TIMESTAMP NULL AFTER actif;

UPDATE utilisateurs
SET
    nom_complet = COALESCE(nom_complet, nom),
    username = COALESCE(username, NULLIF(matricule, '')),
    agence = COALESCE(agence, site),
    role_systeme_id = COALESCE(role_systeme_id, (SELECT id FROM roles_systeme WHERE nom = 'utilisateur_standard')),
    fonction_metier_id = COALESCE(fonction_metier_id, (SELECT id FROM fonctions_metier WHERE nom = 'Employe'))
WHERE id > 0;

CREATE TABLE IF NOT EXISTS validateurs_autorises (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    validateur_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_validateurs_autorises_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    CONSTRAINT fk_validateurs_autorises_validateur FOREIGN KEY (validateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    UNIQUE KEY uq_validateur_autorise (utilisateur_id, validateur_id),
    INDEX idx_validateurs_autorises_validateur (validateur_id)
) ENGINE=InnoDB;

ALTER TABLE categories ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER nom;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS duree_vie_normale INT UNSIGNED NULL AFTER normal_life_years;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS seuil_neuf INT UNSIGNED NULL AFTER duree_vie_normale;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS seuil_bon INT UNSIGNED NULL AFTER seuil_neuf;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS seuil_moyen INT UNSIGNED NULL AFTER seuil_bon;
ALTER TABLE categories ADD COLUMN IF NOT EXISTS seuil_mauvais INT UNSIGNED NULL AFTER seuil_moyen;

UPDATE categories
SET duree_vie_normale = COALESCE(duree_vie_normale, normal_life_years)
WHERE normal_life_years IS NOT NULL;

ALTER TABLE equipements ADD COLUMN IF NOT EXISTS code_inventaire VARCHAR(120) NULL AFTER type_id;
ALTER TABLE equipements ADD COLUMN IF NOT EXISTS designation VARCHAR(255) NULL AFTER hostname;
ALTER TABLE equipements ADD COLUMN IF NOT EXISTS modele VARCHAR(255) NULL AFTER designation;
ALTER TABLE equipements ADD COLUMN IF NOT EXISTS etat_manuel TINYINT(1) NOT NULL DEFAULT 0 AFTER etat_theorique;
ALTER TABLE equipements ADD COLUMN IF NOT EXISTS commentaire_etat TEXT NULL AFTER etat_manuel;
ALTER TABLE equipements ADD COLUMN IF NOT EXISTS emplacement VARCHAR(255) NULL AFTER annee_estimee;
ALTER TABLE equipements ADD COLUMN IF NOT EXISTS fournisseur VARCHAR(255) NULL AFTER emplacement;
ALTER TABLE equipements ADD COLUMN IF NOT EXISTS facture_reference VARCHAR(255) NULL AFTER fournisseur;
ALTER TABLE equipements ADD COLUMN IF NOT EXISTS notes TEXT NULL AFTER facture_reference;
ALTER TABLE equipements ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL AFTER notes;

CREATE TABLE IF NOT EXISTS stocks_quantitatifs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT UNSIGNED NOT NULL,
    designation VARCHAR(255) NOT NULL,
    quantite_totale INT UNSIGNED NOT NULL DEFAULT 0,
    quantite_disponible INT UNSIGNED NOT NULL DEFAULT 0,
    quantite_attribuee INT UNSIGNED NOT NULL DEFAULT 0,
    quantite_maintenance INT UNSIGNED NOT NULL DEFAULT 0,
    quantite_mauvais_etat INT UNSIGNED NOT NULL DEFAULT 0,
    quantite_declassee INT UNSIGNED NOT NULL DEFAULT 0,
    emplacement VARCHAR(255) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_stocks_quantitatifs_categorie_current FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_stocks_quantitatifs_categorie (categorie_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attributions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipement_id INT UNSIGNED NULL,
    stock_quantitatif_id INT UNSIGNED NULL,
    utilisateur_id INT UNSIGNED NOT NULL,
    quantite INT UNSIGNED NOT NULL DEFAULT 1,
    date_attribution TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_retour TIMESTAMP NULL,
    statut ENUM('active', 'terminee') NOT NULL DEFAULT 'active',
    commentaire TEXT NULL,
    attribue_par INT UNSIGNED NULL,
    CONSTRAINT fk_attributions_equipement_current FOREIGN KEY (equipement_id) REFERENCES equipements(id) ON DELETE CASCADE,
    CONSTRAINT fk_attributions_stock_quantitatif_current FOREIGN KEY (stock_quantitatif_id) REFERENCES stocks_quantitatifs(id) ON DELETE CASCADE,
    CONSTRAINT fk_attributions_utilisateur_current FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    CONSTRAINT fk_attributions_attribue_par_current FOREIGN KEY (attribue_par) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_attributions_utilisateur_statut (utilisateur_id, statut)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS historique_equipements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipement_id INT UNSIGNED NULL,
    stock_quantitatif_id INT UNSIGNED NULL,
    type_operation ENUM('creation', 'attribution', 'transfert', 'maintenance', 'retour_stock', 'declassement', 'modification_etat') NOT NULL,
    utilisateur_source_id INT UNSIGNED NULL,
    utilisateur_destination_id INT UNSIGNED NULL,
    quantite INT UNSIGNED NOT NULL DEFAULT 1,
    commentaire TEXT NULL,
    effectue_par INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historique_equipement_current FOREIGN KEY (equipement_id) REFERENCES equipements(id) ON DELETE CASCADE,
    CONSTRAINT fk_historique_stock_quantitatif_current FOREIGN KEY (stock_quantitatif_id) REFERENCES stocks_quantitatifs(id) ON DELETE CASCADE,
    CONSTRAINT fk_historique_source_current FOREIGN KEY (utilisateur_source_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    CONSTRAINT fk_historique_destination_current FOREIGN KEY (utilisateur_destination_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    CONSTRAINT fk_historique_effectue_par_current FOREIGN KEY (effectue_par) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_historique_equipement_date (equipement_id, created_at),
    INDEX idx_historique_stock_date (stock_quantitatif_id, created_at)
) ENGINE=InnoDB;

ALTER TABLE demandes ADD COLUMN IF NOT EXISTS demandeur_id INT UNSIGNED NULL AFTER id;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS validateur_hierarchique_id INT UNSIGNED NULL AFTER demandeur_id;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS categorie_id INT UNSIGNED NULL AFTER validateur_hierarchique_id;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS justification TEXT NULL AFTER description;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS urgence ENUM('faible', 'normale', 'haute') NOT NULL DEFAULT 'normale' AFTER justification;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS commentaire_validation TEXT NULL AFTER urgence;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS date_validation_responsable TIMESTAMP NULL AFTER commentaire_validation;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS date_validation_it TIMESTAMP NULL AFTER date_validation_responsable;

UPDATE demandes
SET
    demandeur_id = COALESCE(demandeur_id, utilisateur_id),
    justification = COALESCE(justification, description)
WHERE id > 0;

CREATE TABLE IF NOT EXISTS validations_demandes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    demande_id INT UNSIGNED NOT NULL,
    validateur_id INT UNSIGNED NOT NULL,
    niveau ENUM('responsable', 'manager_it') NOT NULL,
    decision ENUM('approuve', 'rejete') NOT NULL,
    commentaire TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_validations_demandes_demande_current FOREIGN KEY (demande_id) REFERENCES demandes(id) ON DELETE CASCADE,
    CONSTRAINT fk_validations_demandes_validateur_current FOREIGN KEY (validateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    UNIQUE KEY uq_validation_demande_niveau (demande_id, niveau),
    INDEX idx_validations_demandes_validateur (validateur_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    titre VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_utilisateur_current FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_notifications_utilisateur_lu (utilisateur_id, lu),
    INDEX idx_notifications_created (created_at)
) ENGINE=InnoDB;

SELECT 'ITAM V2 foundation migration ready' AS status;
