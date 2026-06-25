-- Add required flag to category attributes
ALTER TABLE attributs
    ADD COLUMN required TINYINT(1) NOT NULL DEFAULT 0 AFTER type;
