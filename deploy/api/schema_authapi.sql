-- =============================================================
-- Schema Auth API : authapialwaysdata_r401
-- Base de donnees pour l'API d'authentification
-- Actuellement le login est en dur (admin/admin),
-- cette base est prevue pour une future table utilisateur.
-- =============================================================

DROP DATABASE IF EXISTS authapialwaysdata_r401;
CREATE DATABASE authapialwaysdata_r401;
USE authapialwaysdata_r401;

-- Table utilisateur (pour usage futur, le login est actuellement en dur)
CREATE TABLE utilisateur (
    utilisateur_id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'admin',
    CONSTRAINT pk_utilisateur PRIMARY KEY (utilisateur_id)
);

-- Insertion de l'admin par defaut (mot de passe: admin, hashé avec bcrypt)
INSERT INTO utilisateur (username, password_hash, role)
VALUES ('admin', '$2y$10$YourBcryptHashHere', 'admin');

COMMIT;
