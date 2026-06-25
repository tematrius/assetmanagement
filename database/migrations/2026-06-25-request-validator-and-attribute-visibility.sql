ALTER TABLE caracteristiques_categories
    ADD COLUMN IF NOT EXISTS visible_dans_demandes TINYINT(1) NOT NULL DEFAULT 0 AFTER obligatoire;

UPDATE caracteristiques_categories
SET visible_dans_demandes = 1
WHERE type_champ = 'liste';
