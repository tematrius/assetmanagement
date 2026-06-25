ALTER TABLE attributions
    ADD COLUMN IF NOT EXISTS demande_id INT UNSIGNED NULL AFTER id,
    ADD CONSTRAINT fk_attributions_demande
        FOREIGN KEY (demande_id) REFERENCES demandes(id) ON DELETE SET NULL,
    ADD INDEX idx_attributions_demande (demande_id);
