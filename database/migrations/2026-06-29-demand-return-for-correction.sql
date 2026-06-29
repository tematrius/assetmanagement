ALTER TABLE demandes
    MODIFY statut ENUM('brouillon', 'soumis', 'validation_responsable', 'validation_it', 'correction_requise', 'approuve', 'rejete', 'attribue', 'cloture') NOT NULL DEFAULT 'soumis',
    ADD COLUMN IF NOT EXISTS correction_niveau ENUM('responsable', 'manager_it') NULL AFTER commentaire_validation;

ALTER TABLE validations_demandes
    ADD INDEX IF NOT EXISTS idx_validations_demandes_demande (demande_id);

ALTER TABLE validations_demandes
    DROP INDEX IF EXISTS uq_validation_demande_niveau,
    MODIFY decision ENUM('approuve', 'rejete', 'retour_correction', 'resoumis') NOT NULL;
