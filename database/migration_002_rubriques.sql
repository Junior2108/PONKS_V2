-- =====================================================================
-- MIGRATION 002 : une œuvre peut apparaître dans PLUSIEURS rubriques
-- Exécuter UNE SEULE FOIS avec :  sudo mariadb < database/migration_002_rubriques.sql
--
-- Une "migration" = un script qui fait passer une base EXISTANTE (avec ses
-- données) d'une version de la structure à la suivante. Pour une installation
-- neuve, schema.sql contient déjà directement la structure finale.
-- =====================================================================

USE ponks;

-- 1. Nouvelles colonnes
ALTER TABLE works
    -- Sous-titre affiché à côté du titre, ex : "(self-portrait of Ponks)"
    ADD COLUMN subtitle      VARCHAR(200) NULL AFTER title,
    -- Lien YouTube de la vidéo de l'œuvre (films)
    ADD COLUMN video_url     VARCHAR(255) NULL AFTER description,
    -- Cases à cocher : dans quelles rubriques l'œuvre apparaît-elle ?
    -- (toutes les œuvres publiées apparaissent dans "works")
    ADD COLUMN in_public_art TINYINT(1) NOT NULL DEFAULT 0 AFTER video_url,
    ADD COLUMN in_films      TINYINT(1) NOT NULL DEFAULT 0 AFTER in_public_art,
    -- Page d'accueil : dans la galerie / en grand tout en haut
    ADD COLUMN on_home       TINYINT(1) NOT NULL DEFAULT 0 AFTER in_films,
    ADD COLUMN home_hero     TINYINT(1) NOT NULL DEFAULT 0 AFTER on_home;

-- 2. Reporter l'ancienne rubrique dans les nouvelles colonnes (on ne perd rien)
UPDATE works SET in_public_art = 1 WHERE category = 'public_art';
UPDATE works SET in_films      = 1 WHERE category = 'films';

-- 3. Supprimer l'ancienne colonne (et son index), en ajouter un adapté
ALTER TABLE works
    DROP INDEX idx_works_category,
    DROP COLUMN category,
    ADD INDEX idx_works_published (published, position);
