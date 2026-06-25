ALTER TABLE categories_equipements
    ADD COLUMN IF NOT EXISTS visible_dans_demandes TINYINT(1) NOT NULL DEFAULT 1 AFTER type_gestion;
