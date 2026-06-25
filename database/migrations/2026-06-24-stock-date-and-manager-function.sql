USE itam_db;

INSERT INTO fonctions_metier (nom, peut_valider)
VALUES ('Manager IT', 1)
ON DUPLICATE KEY UPDATE peut_valider = VALUES(peut_valider);

ALTER TABLE stocks_quantitatifs
    ADD COLUMN IF NOT EXISTS date_reception DATE NULL AFTER quantite_declassee;

UPDATE utilisateurs
SET fonction_metier_id = (SELECT id FROM fonctions_metier WHERE nom = 'Manager IT' LIMIT 1)
WHERE username = 'admin';
