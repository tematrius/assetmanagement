CREATE TABLE IF NOT EXISTS regles_vie_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT UNSIGNED NOT NULL,
    age_min DECIMAL(6,2) NOT NULL DEFAULT 0,
    age_max DECIMAL(6,2) NULL,
    etat_theorique ENUM('neuf', 'bon', 'moyen', 'mauvais', 'declasse') NOT NULL,
    ordre_affichage INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_regles_vie_categories_categorie FOREIGN KEY (categorie_id) REFERENCES categories_equipements(id) ON DELETE CASCADE,
    INDEX idx_regles_vie_categories_ordre (categorie_id, ordre_affichage)
) ENGINE=InnoDB;
