-- Migration: workflow demande enrichi (fiche complete + signatures)
-- Safe for existing databases (idempotent checks where possible)

USE itam_db;

ALTER TABLE demandes ADD COLUMN IF NOT EXISTS demandeur_nom VARCHAR(150) NULL AFTER date_demande;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS demandeur_matricule VARCHAR(50) NULL AFTER demandeur_nom;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS demandeur_statut VARCHAR(40) NULL AFTER demandeur_matricule;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS demandeur_direction VARCHAR(120) NULL AFTER demandeur_statut;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS demandeur_departement VARCHAR(120) NULL AFTER demandeur_direction;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS demandeur_service VARCHAR(120) NULL AFTER demandeur_departement;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS demandeur_site VARCHAR(120) NULL AFTER demandeur_service;

ALTER TABLE demandes ADD COLUMN IF NOT EXISTS nature_demande VARCHAR(40) NULL AFTER demandeur_site;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS equipement_categorie VARCHAR(80) NULL AFTER nature_demande;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS equipement_type_ordinateur VARCHAR(40) NULL AFTER equipement_categorie;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS accessoires_json TEXT NULL AFTER equipement_type_ordinateur;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS souris_type VARCHAR(20) NULL AFTER accessoires_json;

ALTER TABLE demandes ADD COLUMN IF NOT EXISTS nom_chef VARCHAR(150) NULL AFTER souris_type;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS nom_manager_validation VARCHAR(150) NULL AFTER nom_chef;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS date_signature_demandeur DATE NULL AFTER nom_manager_validation;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS date_signature_chef DATE NULL AFTER date_signature_demandeur;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS date_signature_manager DATE NULL AFTER date_signature_chef;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS signed_file_path VARCHAR(255) NULL AFTER date_signature_manager;
ALTER TABLE demandes ADD COLUMN IF NOT EXISTS validated_at DATETIME NULL AFTER signed_file_path;

UPDATE demandes d
JOIN utilisateurs u ON u.id = d.utilisateur_id
SET
    d.demandeur_nom = COALESCE(d.demandeur_nom, u.nom),
    d.demandeur_matricule = COALESCE(d.demandeur_matricule, u.matricule),
    d.demandeur_direction = COALESCE(d.demandeur_direction, u.direction),
    d.demandeur_departement = COALESCE(d.demandeur_departement, u.departement),
    d.demandeur_service = COALESCE(d.demandeur_service, u.service),
    d.demandeur_site = COALESCE(d.demandeur_site, u.site),
    d.nature_demande = COALESCE(d.nature_demande, 'nouveau_materiel');
