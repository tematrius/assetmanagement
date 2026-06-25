-- Migration: Refactor equipment state management
-- Purpose: Separate theoretical state (auto) from real state (manual) + add state history
-- Changes: 
--   - Add etat_theorique column to equipements
--   - Add moyen state to all state ENUMs
--   - Create equipement_etat_historique table for state change tracking
--   - Create stock_etat_historique table for stock state change tracking

-- Step 1: Add etat_theorique column to equipements
ALTER TABLE equipements
    ADD COLUMN etat_theorique ENUM('neuf', 'bon', 'moyen', 'mauvais', 'a_declasser') 
    NOT NULL DEFAULT 'bon' AFTER etat;

-- Step 2: Update equipements.etat ENUM to include 'moyen' and rename a_declasser
ALTER TABLE equipements
    MODIFY etat ENUM('neuf', 'bon', 'moyen', 'mauvais', 'declasse') NOT NULL DEFAULT 'bon';

-- Step 3: Add index on etat_theorique
ALTER TABLE equipements
    ADD INDEX idx_equipements_etat_theorique (etat_theorique);

-- Step 4: Update stock_etats ENUM to include 'moyen'
ALTER TABLE stock_etats
    MODIFY etat ENUM('neuf', 'bon', 'moyen', 'mauvais', 'declasse') NOT NULL;

-- Step 5: Update attributions_quantite ENUM to include 'moyen'
ALTER TABLE attributions_quantite
    MODIFY etat ENUM('neuf', 'bon', 'moyen', 'mauvais', 'declasse') NOT NULL;

-- Step 6: Update mouvements.etat ENUM to include 'moyen'
ALTER TABLE mouvements
    MODIFY etat ENUM('neuf', 'bon', 'moyen', 'mauvais', 'declasse') NULL;

-- Step 7: Create equipement_etat_historique table for state change tracking
CREATE TABLE IF NOT EXISTS equipement_etat_historique (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipement_id INT UNSIGNED NOT NULL,
    ancien_etat VARCHAR(20) NOT NULL,
    nouvel_etat VARCHAR(20) NOT NULL,
    agent_username VARCHAR(100) NOT NULL,
    commentaire TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_etat_hist_equipement FOREIGN KEY (equipement_id) REFERENCES equipements(id) ON DELETE CASCADE,
    INDEX idx_etat_hist_equipement (equipement_id),
    INDEX idx_etat_hist_created (created_at)
) ENGINE=InnoDB;

-- Step 8: Create stock_etat_historique table for stock state change tracking
CREATE TABLE IF NOT EXISTS stock_etat_historique (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_id INT UNSIGNED NOT NULL,
    ancien_etat VARCHAR(20) NOT NULL,
    nouvel_etat VARCHAR(20) NOT NULL,
    quantite INT UNSIGNED NOT NULL,
    agent_username VARCHAR(100) NOT NULL,
    commentaire TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_etat_hist FOREIGN KEY (stock_id) REFERENCES stocks(id) ON DELETE CASCADE,
    INDEX idx_stock_etat_hist_stock (stock_id),
    INDEX idx_stock_etat_hist_created (created_at)
) ENGINE=InnoDB;

-- Verify migration complete
SELECT 'Migration complete: State refactoring applied' AS status;
