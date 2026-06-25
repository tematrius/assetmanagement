CREATE TABLE IF NOT EXISTS valeurs_caracteristiques_stocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stock_quantitatif_id INT UNSIGNED NOT NULL,
    caracteristique_id INT UNSIGNED NOT NULL,
    valeur TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_valeurs_caracteristiques_stocks_stock FOREIGN KEY (stock_quantitatif_id) REFERENCES stocks_quantitatifs(id) ON DELETE CASCADE,
    CONSTRAINT fk_valeurs_caracteristiques_stocks_caracteristique FOREIGN KEY (caracteristique_id) REFERENCES caracteristiques_categories(id) ON DELETE CASCADE,
    UNIQUE KEY uq_valeur_caracteristique_stock (stock_quantitatif_id, caracteristique_id)
) ENGINE=InnoDB;
