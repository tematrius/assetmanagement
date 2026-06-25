-- Restore fields used by V2 equipment age calculations.
ALTER TABLE equipements
    ADD COLUMN IF NOT EXISTS date_fiabilite ENUM('exacte', 'approximative', 'inconnue')
        NOT NULL DEFAULT 'inconnue' AFTER date_mise_service,
    ADD COLUMN IF NOT EXISTS annee_estimee SMALLINT UNSIGNED NULL AFTER date_fiabilite;

UPDATE equipements
SET date_fiabilite = CASE
    WHEN date_mise_service IS NOT NULL OR date_achat IS NOT NULL THEN 'exacte'
    WHEN annee_estimee IS NOT NULL THEN 'approximative'
    ELSE 'inconnue'
END;
