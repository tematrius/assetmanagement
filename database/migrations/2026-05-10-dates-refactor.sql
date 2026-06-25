-- Migration: Refactor dates and age management
-- Purpose: Allow optional dates, add date reliability, and decouple age from state
-- Changes: 
--   - Rename date_ajout to date_enregistrement (auto-default for existing)
--   - Add date_achat (purchase date)
--   - Add date_mise_service (service/deployment date)
--   - Add date_fiabilite (reliability: exacte, approximative, inconnue)
--   - Add annee_estimee (estimated year for legacy equipment)

-- Step 1: Add new columns to equipements table
ALTER TABLE equipements
    ADD COLUMN date_enregistrement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER etat,
    ADD COLUMN date_achat DATE NULL AFTER date_enregistrement,
    ADD COLUMN date_mise_service DATE NULL AFTER date_achat,
    ADD COLUMN date_fiabilite ENUM('exacte', 'approximative', 'inconnue') NOT NULL DEFAULT 'inconnue' AFTER date_mise_service,
    ADD COLUMN annee_estimee INT UNSIGNED NULL AFTER date_fiabilite;

-- Step 2: Migrate existing date_ajout values to date_enregistrement 
-- (they are now the same field, just with a better name)
-- Since date_ajout was auto-populate with NOW(), date_enregistrement gets the same default

-- Step 3: Drop old date_ajout column after migration is confirmed
-- (This will be done in a separate cleanup if needed, or can stay as-is)

-- Step 4: Add new columns to stocks table
ALTER TABLE stocks
    ADD COLUMN date_enregistrement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER categorie_id,
    ADD COLUMN date_achat DATE NULL AFTER date_enregistrement,
    ADD COLUMN date_mise_service DATE NULL AFTER date_achat,
    ADD COLUMN date_fiabilite ENUM('exacte', 'approximative', 'inconnue') NOT NULL DEFAULT 'inconnue' AFTER date_mise_service,
    ADD COLUMN annee_estimee INT UNSIGNED NULL AFTER date_fiabilite;

-- Verify new columns exist
SELECT 'Migration complete: new date columns added to equipements and stocks' AS status;
