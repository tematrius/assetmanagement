-- Dynamic category configuration: attribute options and aging policies

ALTER TABLE categories
    ADD COLUMN normal_life_years INT UNSIGNED NULL AFTER mode_gestion;

ALTER TABLE attributs
    ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER required;

CREATE TABLE IF NOT EXISTS categorie_attribut_options (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attribut_id INT UNSIGNED NOT NULL,
    label VARCHAR(160) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categorie_attribut_options_attribut FOREIGN KEY (attribut_id) REFERENCES attributs(id) ON DELETE CASCADE,
    UNIQUE KEY uq_attr_option (attribut_id, label)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorie_age_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT UNSIGNED NOT NULL,
    min_years DECIMAL(6,2) NULL,
    max_years DECIMAL(6,2) NULL,
    theoretical_state ENUM('neuf', 'bon', 'moyen', 'mauvais', 'declasse') NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categorie_age_rules_category FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category_age_rules_category (categorie_id)
) ENGINE=InnoDB;