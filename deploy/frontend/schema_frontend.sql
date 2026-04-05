-- =============================================================
-- Schema Frontend : simsar_r401
-- Base de donnees pour le site frontend
-- Le frontend n'accede pas directement a la base de donnees,
-- il communique avec le backend via des appels HTTP (ApiClient).
-- Cette base est creee pour conformite avec l'architecture
-- Alwaysdata mais ne contient pas de tables.
-- =============================================================

DROP DATABASE IF EXISTS simsar_r401;
CREATE DATABASE simsar_r401;
USE simsar_r401;

-- Aucune table necessaire : le frontend utilise ApiClient.php
-- pour communiquer avec le backend via HTTP.

COMMIT;
