-- Migration utilisateur (base existante)
-- Objectif:
-- 1) Ajouter la table utilisateur_accessoires si absente
-- 2) Ajouter la table utilisateur_audits si absente
-- 3) Ajouter un index UNIQUE sur utilisateurs.matricule si possible

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS utilisateur_accessoires (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    accessoire_nom VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_accessoire_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_user_accessoire_user (utilisateur_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS utilisateur_audits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    action VARCHAR(40) NOT NULL,
    details TEXT NOT NULL,
    actor_username VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_utilisateur_audits_user (utilisateur_id)
) ENGINE=InnoDB;

-- Ajouter UNIQUE(matricule) uniquement si l'index n'existe pas et s'il n'y a pas de doublons.
SET @idx_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'utilisateurs'
      AND index_name = 'uk_utilisateurs_matricule'
);

SET @dupe_exists := (
    SELECT COUNT(*)
    FROM (
        SELECT matricule
        FROM utilisateurs
        GROUP BY matricule
        HAVING COUNT(*) > 1
    ) d
);

SET @sql := IF(
    @idx_exists = 0 AND @dupe_exists = 0,
    'ALTER TABLE utilisateurs ADD UNIQUE KEY uk_utilisateurs_matricule (matricule)',
    'SELECT "SKIP unique matricule (index deja present ou doublons detectes)" AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Metadonnees source/destination pour les mouvements
SET @col_source_type := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'mouvements' AND column_name = 'source_type'
);
SET @sql_source_type := IF(@col_source_type = 0,
    'ALTER TABLE mouvements ADD COLUMN source_type VARCHAR(30) NULL AFTER utilisateur_destination_id',
    'SELECT "SKIP source_type" AS info');
PREPARE stmt FROM @sql_source_type;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_source_label := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'mouvements' AND column_name = 'source_label'
);
SET @sql_source_label := IF(@col_source_label = 0,
    'ALTER TABLE mouvements ADD COLUMN source_label VARCHAR(160) NULL AFTER source_type',
    'SELECT "SKIP source_label" AS info');
PREPARE stmt FROM @sql_source_label;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_dest_type := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'mouvements' AND column_name = 'destination_type'
);
SET @sql_dest_type := IF(@col_dest_type = 0,
    'ALTER TABLE mouvements ADD COLUMN destination_type VARCHAR(30) NULL AFTER source_label',
    'SELECT "SKIP destination_type" AS info');
PREPARE stmt FROM @sql_dest_type;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_dest_label := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'mouvements' AND column_name = 'destination_label'
);
SET @sql_dest_label := IF(@col_dest_label = 0,
    'ALTER TABLE mouvements ADD COLUMN destination_label VARCHAR(160) NULL AFTER destination_type',
    'SELECT "SKIP destination_label" AS info');
PREPARE stmt FROM @sql_dest_label;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
