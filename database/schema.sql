-- =====================================================================
-- PONKS - Structure de la base de données
-- Exécuter avec :  sudo mariadb < database/schema.sql
--
-- ATTENTION : "IF NOT EXISTS" ne modifie JAMAIS une table déjà existante.
-- Pour repartir de zéro (perte des données !) :  DROP DATABASE ponks;
-- =====================================================================

-- Crée la base si elle n'existe pas encore.
-- utf8mb4 = le "vrai" UTF-8 : accents (FLÂNEUR), guillemets typographiques (2”19), emojis.
CREATE DATABASE IF NOT EXISTS ponks
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ponks;

-- ---------------------------------------------------------------------
-- Comptes administrateurs
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Sert d'identifiant de connexion. UNIQUE : un seul compte par email.
    email         VARCHAR(190) NOT NULL UNIQUE,

    -- JAMAIS le mot de passe en clair : uniquement son empreinte,
    -- calculée en PHP avec password_hash() et vérifiée avec password_verify().
    password_hash VARCHAR(255) NOT NULL,

    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Pages de texte simples : cv/bio, news, privacy policy...
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pages (
    -- Ici le slug EST la clé primaire : une page = un nom unique (ex : cv_bio)
    slug          VARCHAR(60) PRIMARY KEY,
    title         VARCHAR(200) NOT NULL,

    -- MEDIUMTEXT : jusqu'à 16 Mo de texte (TEXT s'arrête à 64 Ko)
    content       MEDIUMTEXT,

    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table des œuvres : une ligne = une œuvre = une page du site
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS works (
    -- Numéro unique attribué automatiquement par MySQL (1, 2, 3...)
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Identifiant lisible utilisé dans l'URL : oeuvre.php?slug=djerbism
    -- UNIQUE : deux œuvres ne peuvent pas avoir le même slug
    slug          VARCHAR(120) NOT NULL UNIQUE,

    title         VARCHAR(200) NOT NULL,

    -- Sous-titre affiché à côté du titre, ex : "(self-portrait of Ponks)"
    subtitle      VARCHAR(200),

    -- Colonne séparée (et pas seulement dans "info") pour pouvoir TRIER par année
    year          SMALLINT UNSIGNED,

    -- Les lignes du bloc "hero_work_info" (dimensions, durée, matériaux, lieu...),
    -- une par ligne. Leur nombre et leur nature varient selon l'œuvre.
    info          TEXT,

    -- La phrase d'accroche (p_title_description)
    intro         TEXT,

    -- Le texte long / poème. Les retours à la ligne seront convertis en <br>.
    description   TEXT,

    -- Lien YouTube de la vidéo de l'œuvre (films)
    video_url     VARCHAR(255),

    -- Dans quelles rubriques l'œuvre apparaît-elle ? (0 = non, 1 = oui)
    -- Toutes les œuvres publiées apparaissent dans "works" ; une même œuvre
    -- peut être à la fois dans public art ET dans films.
    in_public_art TINYINT(1) NOT NULL DEFAULT 0,
    in_films      TINYINT(1) NOT NULL DEFAULT 0,

    -- Page d'accueil : dans la galerie / en grand tout en haut (une seule)
    on_home       TINYINT(1) NOT NULL DEFAULT 0,
    home_hero     TINYINT(1) NOT NULL DEFAULT 0,

    -- Image affichée dans les galeries (works.html et bas des pages)
    thumbnail     VARCHAR(255),

    -- Ordre d'affichage dans la liste des œuvres (petit = en premier)
    position      INT NOT NULL DEFAULT 0,

    -- 1 = visible sur le site, 0 = brouillon visible uniquement dans l'admin
    published     TINYINT(1) NOT NULL DEFAULT 1,

    -- Dates remplies automatiquement par MySQL
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- INDEX : accélère la requête la plus fréquente du site,
    -- "toutes les œuvres publiées, dans l'ordre"
    INDEX idx_works_published (published, position)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Table des images : plusieurs lignes par œuvre
-- (créée APRÈS works, car elle y fait référence)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS work_images (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- À quelle œuvre appartient cette image ? (= works.id)
    work_id       INT UNSIGNED NOT NULL,

    -- Chemin de l'image en grand, ex : img/DJERBISM/gallery_img/djerbism1.jpg
    path          VARCHAR(255) NOT NULL,

    -- Courte description, utilisée dans l'attribut alt="" de l'image
    -- (lue par les lecteurs d'écran et par Google)
    caption       VARCHAR(255),

    -- Ordre dans la galerie : 0 = image principale affichée en grand
    position      INT NOT NULL DEFAULT 0,

    -- CLÉ ÉTRANGÈRE : MySQL refuse une image dont l'œuvre n'existe pas.
    -- ON DELETE CASCADE : supprimer une œuvre supprime aussi ses images.
    CONSTRAINT fk_work_images_work
        FOREIGN KEY (work_id) REFERENCES works(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
