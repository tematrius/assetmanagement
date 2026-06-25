ALTER TABLE historique_equipements
    ADD COLUMN IF NOT EXISTS source_type ENUM('fournisseur', 'depot', 'warehouse', 'utilisateur', 'site', 'autre') NULL AFTER utilisateur_destination_id,
    ADD COLUMN IF NOT EXISTS source_label VARCHAR(255) NULL AFTER source_type,
    ADD COLUMN IF NOT EXISTS destination_type ENUM('fournisseur', 'depot', 'warehouse', 'utilisateur', 'site', 'autre') NULL AFTER source_label,
    ADD COLUMN IF NOT EXISTS destination_label VARCHAR(255) NULL AFTER destination_type;

UPDATE historique_equipements
SET source_type = CASE WHEN utilisateur_source_id IS NULL THEN 'depot' ELSE 'utilisateur' END,
    source_label = CASE WHEN utilisateur_source_id IS NULL THEN 'Depot IT Central' ELSE NULL END,
    destination_type = CASE
        WHEN utilisateur_destination_id IS NOT NULL THEN 'utilisateur'
        WHEN type_operation = 'maintenance' THEN 'warehouse'
        ELSE 'depot'
    END,
    destination_label = CASE
        WHEN utilisateur_destination_id IS NOT NULL THEN NULL
        WHEN type_operation = 'maintenance' THEN 'Warehouse IT'
        WHEN type_operation = 'declassement' THEN 'Declasse'
        ELSE 'Depot IT Central'
    END
WHERE source_type IS NULL OR destination_type IS NULL;
