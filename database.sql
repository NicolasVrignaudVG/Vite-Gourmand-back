-- ═══════════════════════════════════════════════════════════
-- Vite & Gourmand — Base de données relationnelle
-- Fichier : database.sql
-- SGBD    : MySQL / MariaDB
-- ═══════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

DROP DATABASE IF EXISTS vite_gourmand;
CREATE DATABASE vite_gourmand
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE vite_gourmand;

-- ─────────────────────────────────────────
-- TABLE : role
-- ─────────────────────────────────────────
CREATE TABLE role (
    role_id   INT          NOT NULL AUTO_INCREMENT,
    libelle   VARCHAR(50)  NOT NULL,
    PRIMARY KEY (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO role (libelle) VALUES
    ('administrateur'),
    ('employe'),
    ('utilisateur');

-- ─────────────────────────────────────────
-- TABLE : utilisateur
-- ─────────────────────────────────────────
CREATE TABLE utilisateur (
    utilisateur_id  INT           NOT NULL AUTO_INCREMENT,
    email           VARCHAR(255)  NOT NULL UNIQUE,
    password        VARCHAR(255)  NOT NULL,       -- hashé bcrypt
    nom             VARCHAR(100)  NOT NULL,
    prenom          VARCHAR(100)  NOT NULL,
    telephone       VARCHAR(20)   DEFAULT NULL,
    adresse         VARCHAR(255)  DEFAULT NULL,
    actif           TINYINT(1)    NOT NULL DEFAULT 1,
    role_id         INT           NOT NULL DEFAULT 3, -- utilisateur par défaut
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (utilisateur_id),
    CONSTRAINT fk_util_role FOREIGN KEY (role_id) REFERENCES role(role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mot de passe haché = "Admin@1234" pour admin, "Employe@1234" pour employés, "User@1234" pour clients
INSERT INTO utilisateur (email, password, nom, prenom, telephone, adresse, actif, role_id) VALUES
    ('admin@vitegourmand.fr',   '$2y$12$5S6p7slYIYUDeyKNjA1oz.gK6nG/1aU/MbsG3flUaiMOXQ5UrS6WC', 'Vite',    'José',   '05 56 00 00 01', '1 rue du Chef, 33000 Bordeaux', 1, 1),
    ('employe@vitegourmand.fr', '$2y$12$20pu06UXm8x2Vep2XjdSZOHn8MbB0//W/nsrf3RYADtnXyCr2J0Py', 'Lambert', 'Sophie', '05 56 00 00 02', NULL,                            1, 2),
    ('marc@vitegourmand.fr',    '$2y$12$20pu06UXm8x2Vep2XjdSZOHn8MbB0//W/nsrf3RYADtnXyCr2J0Py', 'Petit',   'Marc',   '05 56 00 00 03', NULL,                            0, 2),
    ('marie.dupont@email.com',  '$2y$12$6GtDvXpGIaopSTZ64.S7geaLPLoOHUR6YGEizuzr0Twe/RDLKA.w6', 'Dupont',  'Marie',  '06 12 34 56 78', '12 rue des Fleurs, 33000 Bordeaux', 1, 3),
    ('jean.martin@email.com',   '$2y$12$6GtDvXpGIaopSTZ64.S7geaLPLoOHUR6YGEizuzr0Twe/RDLKA.w6', 'Martin',  'Jean',   '07 98 76 54 32', '8 allée des Roses, 33200 Bordeaux', 1, 3),
    ('camille.d@email.com',     '$2y$12$6GtDvXpGIaopSTZ64.S7geaLPLoOHUR6YGEizuzr0Twe/RDLKA.w6', 'Dubois',  'Camille','06 55 44 33 22', '3 place de la Victoire, 33000 Bordeaux', 1, 3);

-- ─────────────────────────────────────────
-- TABLE : theme
-- ─────────────────────────────────────────
CREATE TABLE theme (
    theme_id  INT          NOT NULL AUTO_INCREMENT,
    libelle   VARCHAR(50)  NOT NULL,
    PRIMARY KEY (theme_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO theme (libelle) VALUES
    ('Classique'),
    ('Noël'),
    ('Pâques'),
    ('Événement');

-- ─────────────────────────────────────────
-- TABLE : regime
-- ─────────────────────────────────────────
CREATE TABLE regime (
    regime_id  INT          NOT NULL AUTO_INCREMENT,
    libelle    VARCHAR(50)  NOT NULL,
    PRIMARY KEY (regime_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO regime (libelle) VALUES
    ('Classique'),
    ('Végétarien'),
    ('Vegan'),
    ('Sans gluten');

-- ─────────────────────────────────────────
-- TABLE : allergene
-- ─────────────────────────────────────────
CREATE TABLE allergene (
    allergene_id  INT          NOT NULL AUTO_INCREMENT,
    libelle       VARCHAR(100) NOT NULL,
    PRIMARY KEY (allergene_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO allergene (libelle) VALUES
    ('Gluten'),
    ('Lactose'),
    ('Œufs'),
    ('Fruits de mer'),
    ('Crustacés'),
    ('Fruits à coque'),
    ('Alcool'),
    ('Moutarde'),
    ('Soja'),
    ('Arachides');

-- ─────────────────────────────────────────
-- TABLE : plat
-- ─────────────────────────────────────────
CREATE TABLE plat (
    plat_id     INT           NOT NULL AUTO_INCREMENT,
    type_plat   ENUM('entree','plat','dessert') NOT NULL,
    nom         VARCHAR(255)  NOT NULL,
    description TEXT          DEFAULT NULL,
    photo       BLOB          DEFAULT NULL,
    PRIMARY KEY (plat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO plat (type_plat, nom, description) VALUES
    -- Entrées
    ('entree', 'Velouté de potimarron aux châtaignes', 'Velouté onctueux de potimarron servi avec des châtaignes rôties'),
    ('entree', 'Carpaccio de Saint-Jacques', 'Saint-Jacques à l\'huile de truffe et citron vert'),
    ('entree', 'Huitres Gillardeau n°2', 'Huitres spéciales de la maison Gillardeau'),
    ('entree', 'Foie gras poêlé', 'Foie gras poêlé, chutney de figues et pain d\'épices'),
    ('entree', 'Soupe du jour', 'Soupe fraîche préparée selon les arrivages du marché'),
    ('entree', 'Salade composée', 'Salade de saison avec vinaigrette maison'),
    -- Plats
    ('plat', 'Filet de bar rôti', 'Bar rôti, écrasé de pommes de terre à la truffe noire'),
    ('plat', 'Suprême de volaille fermière', 'Volaille fermière en sauce morilles'),
    ('plat', 'Homard bleu rôti', 'Homard bleu rôti, beurre coral line'),
    ('plat', 'Carré d\'agneau de lait', 'Carré d\'agneau, purée de céleri-rave à la truffe'),
    ('plat', 'Burger maison', 'Burger artisanal avec frites fraîches maison'),
    ('plat', 'Quiche lorraine', 'Quiche lorraine traditionnelle et salade verte'),
    -- Desserts
    ('dessert', 'Moelleux au chocolat', 'Moelleux chocolat noir, cœur coulant caramel beurre salé'),
    ('dessert', 'Tarte fine aux pommes', 'Tarte fine aux pommes de Normandie, glace vanille Bourbon'),
    ('dessert', 'Soufflé au Grand Marnier', 'Soufflé chaud au Grand Marnier, servi immédiatement'),
    ('dessert', 'Dôme chocolat Guanaja', 'Dôme au chocolat Guanaja 70%, croustillant praliné'),
    ('dessert', 'Crème brûlée', 'Crème brûlée à la vanille de Madagascar'),
    ('dessert', 'Tarte du jour', 'Selon les arrivages et l\'inspiration du chef');

-- ─────────────────────────────────────────
-- TABLE : plat_allergene (liaison N-N)
-- ─────────────────────────────────────────
CREATE TABLE plat_allergene (
    plat_id      INT NOT NULL,
    allergene_id INT NOT NULL,
    PRIMARY KEY (plat_id, allergene_id),
    CONSTRAINT fk_pa_plat      FOREIGN KEY (plat_id)      REFERENCES plat(plat_id)          ON DELETE CASCADE,
    CONSTRAINT fk_pa_allergene FOREIGN KEY (allergene_id) REFERENCES allergene(allergene_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO plat_allergene (plat_id, allergene_id) VALUES
    (1, 2),(1, 3),          -- Velouté : lactose, oeufs
    (2, 4),                 -- Saint-Jacques : fruits de mer
    (3, 4),(3, 5),          -- Huitres : fruits de mer, crustacés
    (4, 1),(4, 2),(4, 7),   -- Foie gras : gluten, lactose, alcool
    (7, 2),(7, 1),          -- Bar rôti : lactose, gluten
    (8, 2),(8, 3),          -- Volaille : lactose, oeufs
    (9, 5),(9, 2),          -- Homard : crustacés, lactose
    (10, 2),                -- Agneau : lactose
    (11, 1),(11, 3),(11, 8),-- Burger : gluten, oeufs, moutarde
    (12, 1),(12, 2),(12, 3),-- Quiche : gluten, lactose, oeufs
    (13, 2),(13, 3),        -- Moelleux : lactose, oeufs
    (14, 2),(14, 3),        -- Tarte pomme : lactose, oeufs
    (15, 2),(15, 3),(15, 7),-- Soufflé : lactose, oeufs, alcool
    (16, 2),(16, 3),(16, 6),-- Dôme choco : lactose, oeufs, fruits à coque
    (17, 2),(17, 3),        -- Crème brûlée : lactose, oeufs
    (18, 1),(18, 2),(18, 3);-- Tarte du jour : gluten, lactose, oeufs

-- ─────────────────────────────────────────
-- TABLE : menu
-- ─────────────────────────────────────────
CREATE TABLE menu (
    menu_id           INT           NOT NULL AUTO_INCREMENT,
    titre             VARCHAR(255)  NOT NULL,
    nombre_personne_minimum INT     NOT NULL DEFAULT 1,
    prix_par_personne DOUBLE        NOT NULL,
    description       VARCHAR(500)  DEFAULT NULL,
    conditions        TEXT          DEFAULT NULL,
    quantite_restante INT           NOT NULL DEFAULT 0,
    theme_id          INT           DEFAULT NULL,
    regime_id         INT           DEFAULT NULL,
    actif             TINYINT(1)    NOT NULL DEFAULT 1,
    created_at        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (menu_id),
    CONSTRAINT fk_menu_theme  FOREIGN KEY (theme_id)  REFERENCES theme(theme_id),
    CONSTRAINT fk_menu_regime FOREIGN KEY (regime_id) REFERENCES regime(regime_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO menu (titre, nombre_personne_minimum, prix_par_personne, description, conditions, quantite_restante, theme_id, regime_id) VALUES
    (
        'Menu Découverte', 2, 35.00,
        'Une expérience gourmande autour des grands classiques recettes, mettant en valeur des produits de saison et des associations équilibrées.',
        'Ce menu doit être commandé au minimum 48h avant la prestation. Conserver les produits frais entre 2°C et 4°C.',
        8, 1, 1
    ),
    (
        'Menu Gastronomique', 2, 55.00,
        'Un menu raffiné signé par le chef, alliant techniques gastronomiques, produits d\'exception et dressages élégants pour une expérience unique.',
        'Ce menu doit être commandé au minimum 72h avant la prestation. Certains produits (homard, foie gras) nécessitent une confirmation de disponibilité.',
        5, 4, 1
    ),
    (
        'Menu Rapide', 1, 15.00,
        'Une formule efficace et savoureuse pour la pause déjeuner, avec des plats généreux préparés rapidement à base de produits frais.',
        'Commande possible jusqu\'à 2h avant la livraison. Menu disponible uniquement le midi (11h–14h).',
        20, 1, 1
    );

-- ─────────────────────────────────────────
-- TABLE : menu_plat (liaison N-N)
-- ─────────────────────────────────────────
CREATE TABLE menu_plat (
    menu_id INT NOT NULL,
    plat_id INT NOT NULL,
    PRIMARY KEY (menu_id, plat_id),
    CONSTRAINT fk_mp_menu FOREIGN KEY (menu_id) REFERENCES menu(menu_id) ON DELETE CASCADE,
    CONSTRAINT fk_mp_plat FOREIGN KEY (plat_id) REFERENCES plat(plat_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO menu_plat (menu_id, plat_id) VALUES
    -- Menu Découverte (id=1)
    (1, 1),(1, 2),          -- entrées
    (1, 7),(1, 8),          -- plats
    (1, 13),(1, 14),        -- desserts
    -- Menu Gastronomique (id=2)
    (2, 3),(2, 4),          -- entrées
    (2, 9),(2, 10),         -- plats
    (2, 15),(2, 16),        -- desserts
    -- Menu Rapide (id=3)
    (3, 5),(3, 6),          -- entrées
    (3, 11),(3, 12),        -- plats
    (3, 17),(3, 18);        -- desserts

-- ─────────────────────────────────────────
-- TABLE : menu_image
-- ─────────────────────────────────────────
CREATE TABLE menu_image (
    image_id    INT           NOT NULL AUTO_INCREMENT,
    menu_id     INT           NOT NULL,
    url         VARCHAR(500)  NOT NULL,
    alt         VARCHAR(255)  DEFAULT NULL,
    principale  TINYINT(1)    NOT NULL DEFAULT 0,
    PRIMARY KEY (image_id),
    CONSTRAINT fk_img_menu FOREIGN KEY (menu_id) REFERENCES menu(menu_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO menu_image (menu_id, url, alt, principale) VALUES
    (1, 'images/kifotofotografia-food-8151625.jpg',    'Menu Découverte',     1),
    (2, 'images/joannawielgosz-pasta-7209002-1.png',   'Menu Gastronomique',  1),
    (3, 'images/joannawielgosz-pasta-7209002-1-2.png', 'Menu Rapide',         1);

-- ─────────────────────────────────────────
-- TABLE : commande
-- ─────────────────────────────────────────
CREATE TABLE commande (
    commande_id      INT            NOT NULL AUTO_INCREMENT,
    numero_commande  VARCHAR(20)    NOT NULL UNIQUE,
    utilisateur_id   INT            NOT NULL,
    menu_id          INT            NOT NULL,
    date_prestation  DATETIME       NOT NULL,
    adresse_livraison VARCHAR(255)  NOT NULL,
    ville_livraison  VARCHAR(100)   NOT NULL,
    cp_livraison     VARCHAR(10)    NOT NULL,
    nombre_personnes INT            NOT NULL,
    prix_menu        DOUBLE         NOT NULL,
    prix_livraison   DOUBLE         NOT NULL DEFAULT 0.00,
    prix_total       DOUBLE         NOT NULL,
    remise           DOUBLE         NOT NULL DEFAULT 0.00,
    statut           ENUM('en_attente','accepte','en_preparation','en_livraison','livre','retour_materiel','terminee','annulee')
                                    NOT NULL DEFAULT 'en_attente',
    motif_annulation TEXT           DEFAULT NULL,
    mode_contact     VARCHAR(50)    DEFAULT NULL,
    pret_materiel    TINYINT(1)     NOT NULL DEFAULT 0,
    created_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (commande_id),
    CONSTRAINT fk_cmd_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id),
    CONSTRAINT fk_cmd_menu FOREIGN KEY (menu_id)        REFERENCES menu(menu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO commande (numero_commande, utilisateur_id, menu_id, date_prestation, adresse_livraison, ville_livraison, cp_livraison, nombre_personnes, prix_menu, prix_livraison, prix_total, remise, statut) VALUES
    ('VG-482910', 4, 1, '2025-05-12 19:00:00', '12 rue des Fleurs', 'Bordeaux', '33000', 4,  140.00, 0.00, 140.00, 0.00, 'en_attente'),
    ('VG-371204', 5, 2, '2025-04-05 12:30:00', '8 allée des Roses',  'Bordeaux', '33200', 6,  297.00, 0.00, 297.00, 33.00,'accepte'),
    ('VG-209841', 6, 3, '2025-02-14 20:00:00', '3 place de la Victoire', 'Bordeaux', '33000', 2, 30.00, 0.00, 30.00, 0.00, 'terminee');

-- ─────────────────────────────────────────
-- TABLE : suivi_commande
-- ─────────────────────────────────────────
CREATE TABLE suivi_commande (
    suivi_id      INT       NOT NULL AUTO_INCREMENT,
    commande_id   INT       NOT NULL,
    statut        VARCHAR(50) NOT NULL,
    commentaire   TEXT      DEFAULT NULL,
    created_at    DATETIME  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (suivi_id),
    CONSTRAINT fk_suivi_cmd FOREIGN KEY (commande_id) REFERENCES commande(commande_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO suivi_commande (commande_id, statut, commentaire, created_at) VALUES
    (2, 'en_attente',  'Commande reçue',         '2025-04-01 10:12:00'),
    (2, 'accepte',     'Validée par l\'équipe',   '2025-04-01 14:30:00'),
    (2, 'en_preparation', 'En préparation cuisine',  '2025-04-05 08:00:00'),
    (3, 'en_attente',  'Commande reçue',          '2025-02-12 10:00:00'),
    (3, 'accepte',     'Validée',                 '2025-02-12 14:00:00'),
    (3, 'en_preparation', 'En préparation',          '2025-02-14 09:00:00'),
    (3, 'en_livraison',   'En cours de livraison',   '2025-02-14 18:30:00'),
    (3, 'livre',       'Livraison effectuée',     '2025-02-14 19:45:00'),
    (3, 'terminee',    'Commande terminée',        '2025-02-14 20:00:00');

-- ─────────────────────────────────────────
-- TABLE : avis
-- ─────────────────────────────────────────
CREATE TABLE avis (
    avis_id       INT          NOT NULL AUTO_INCREMENT,
    utilisateur_id INT         NOT NULL,
    commande_id   INT          NOT NULL,
    note          TINYINT      NOT NULL CHECK (note BETWEEN 1 AND 5),
    description   VARCHAR(500) DEFAULT NULL,
    statut        ENUM('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (avis_id),
    CONSTRAINT fk_avis_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id),
    CONSTRAINT fk_avis_cmd  FOREIGN KEY (commande_id)    REFERENCES commande(commande_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO avis (utilisateur_id, commande_id, note, description, statut) VALUES
    (4, 1, 5, 'Cuisine excellente et produits très frais. Le service était rapide et le personnel accueillant.', 'valide'),
    (5, 2, 4, 'Très belle découverte. Ambiance chaleureuse, portions généreuses et plats délicieux.', 'en_attente'),
    (6, 3, 5, 'Restaurant élégant avec une superbe décoration. Les plats sont savoureux et très bien présentés.', 'valide');

-- ─────────────────────────────────────────
-- TABLE : horaire
-- ─────────────────────────────────────────
CREATE TABLE horaire (
    horaire_id      INT         NOT NULL AUTO_INCREMENT,
    jour            TINYINT     NOT NULL COMMENT '1=Lundi … 7=Dimanche',
    heure_ouverture VARCHAR(5)  DEFAULT NULL COMMENT 'Format HH:MM',
    heure_fermeture VARCHAR(5)  DEFAULT NULL COMMENT 'Format HH:MM',
    service         ENUM('midi','soir') NOT NULL DEFAULT 'midi',
    ferme           TINYINT(1)  NOT NULL DEFAULT 0,
    PRIMARY KEY (horaire_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO horaire (jour, heure_ouverture, heure_fermeture, service, ferme) VALUES
    (1, '12:00', '14:00', 'midi',  0),
    (1, '19:00', '22:00', 'soir',  0),
    (2, '12:00', '14:00', 'midi',  0),
    (2, '19:00', '22:00', 'soir',  0),
    (3, '12:00', '14:00', 'midi',  0),
    (3, '19:00', '22:00', 'soir',  0),
    (4, '12:00', '14:00', 'midi',  0),
    (4, '19:00', '22:00', 'soir',  0),
    (5, '12:00', '14:00', 'midi',  0),
    (5, '19:00', '22:00', 'soir',  0),
    (6, '12:00', '23:30', 'midi',  0),
    (6, NULL,    NULL,    'soir',  1),
    (7, '12:00', '23:30', 'midi',  0),
    (7, NULL,    NULL,    'soir',  1);

-- ─────────────────────────────────────────
-- TABLE : contact
-- ─────────────────────────────────────────
CREATE TABLE contact (
    contact_id  INT          NOT NULL AUTO_INCREMENT,
    email       VARCHAR(255) NOT NULL,
    titre       VARCHAR(255) NOT NULL,
    description TEXT         NOT NULL,
    traite      TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (contact_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
