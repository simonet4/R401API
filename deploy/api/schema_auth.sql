-- Schema pour la base de données d'authentification (API auth)
-- A exécuter sur la base MySQL du compte Alwaysdata "api"

CREATE TABLE IF NOT EXISTS utilisateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'joueur'
);

-- Utilisateur admin par défaut (mot de passe : admin)
-- En production, modifiez le mot de passe ou utilisez un hash bcrypt :
--   INSERT INTO utilisateur (username, password_hash, role)
--   VALUES ('admin', '$2y$10$HASH_ICI', 'admin');
INSERT INTO utilisateur (username, password_hash, role)
VALUES ('admin', 'admin', 'admin')
ON DUPLICATE KEY UPDATE username = username;
