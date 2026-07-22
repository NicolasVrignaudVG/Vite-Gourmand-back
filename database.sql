-- ═══════════════════════════════════════════════════════════
-- Vite & Gourmand — Base de données relationnelle
-- Fichier : database.sql (structure + données de départ)
-- SGBD    : MySQL 8.0
--
-- Schéma conforme au modèle en production (18 tables :
-- 14 entités métier + 4 tables d'association).
-- Les tables techniques de Symfony (doctrine_migration_versions,
-- messenger_messages) sont créées par le framework et ne font pas
-- partie du modèle métier.
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
-- TABLE : role — rôles applicatifs (RBAC)
-- ─────────────────────────────────────────
CREATE TABLE role (
    id       INT          NOT NULL AUTO_INCREMENT,
    libelle  VARCHAR(50)  NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : utilisateur
-- ─────────────────────────────────────────
CREATE TABLE utilisateur (
    id                      INT           NOT NULL AUTO_INCREMENT,
    email                   VARCHAR(255)  NOT NULL,
    password                VARCHAR(255)  NOT NULL,              -- hashé bcrypt (cost 12)
    nom                     VARCHAR(100)  NOT NULL,
    prenom                  VARCHAR(100)  NOT NULL,
    telephone               VARCHAR(20)   DEFAULT NULL,
    adresse                 VARCHAR(255)  DEFAULT NULL,
    actif                   TINYINT       NOT NULL,              -- 1 actif / 0 désactivé
    created_at              DATETIME      NOT NULL,
    role_id                 INT           NOT NULL,
    reset_token             VARCHAR(64)   DEFAULT NULL,          -- réinitialisation mot de passe (usage unique)
    reset_token_expires_at  DATETIME      DEFAULT NULL,
    pseudonyme              VARCHAR(50)   DEFAULT NULL,          -- nom public affiché sur les avis (RGPD)
    PRIMARY KEY (id),
    UNIQUE KEY uniq_utilisateur_email (email),
    CONSTRAINT fk_utilisateur_role FOREIGN KEY (role_id) REFERENCES role (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : refresh_token — rotation des jetons JWT
-- ─────────────────────────────────────────
CREATE TABLE refresh_token (
    id              INT           NOT NULL AUTO_INCREMENT,
    token           VARCHAR(128)  NOT NULL,
    expires_at      DATETIME      NOT NULL,
    created_at      DATETIME      NOT NULL,
    ip_address      VARCHAR(45)   DEFAULT NULL,                  -- IPv4/IPv6
    revoked         TINYINT       DEFAULT NULL,                  -- révoqué à la rotation / déconnexion
    utilisateur_id  INT           NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_refresh_token (token),
    CONSTRAINT fk_token_utilisateur FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : theme / regime — référentiels des menus
-- ─────────────────────────────────────────
CREATE TABLE theme (
    id       INT          NOT NULL AUTO_INCREMENT,
    libelle  VARCHAR(50)  NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE regime (
    id       INT          NOT NULL AUTO_INCREMENT,
    libelle  VARCHAR(50)  NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : allergene
-- ─────────────────────────────────────────
CREATE TABLE allergene (
    id       INT           NOT NULL AUTO_INCREMENT,
    libelle  VARCHAR(100)  NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : plat
-- ─────────────────────────────────────────
CREATE TABLE plat (
    id           INT           NOT NULL AUTO_INCREMENT,
    type_plat    VARCHAR(20)   NOT NULL,                         -- entree / plat / dessert
    nom          VARCHAR(255)  NOT NULL,
    description  LONGTEXT,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : plat_allergene — association N-N
-- ─────────────────────────────────────────
CREATE TABLE plat_allergene (
    plat_id       INT NOT NULL,
    allergene_id  INT NOT NULL,
    PRIMARY KEY (plat_id, allergene_id),
    CONSTRAINT fk_pa_plat      FOREIGN KEY (plat_id)      REFERENCES plat (id)      ON DELETE CASCADE,
    CONSTRAINT fk_pa_allergene FOREIGN KEY (allergene_id) REFERENCES allergene (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : menu
-- ─────────────────────────────────────────
CREATE TABLE menu (
    id                       INT           NOT NULL AUTO_INCREMENT,
    titre                    VARCHAR(255)  NOT NULL,
    nombre_personne_minimum  INT           NOT NULL,
    prix_par_personne        DOUBLE        NOT NULL,
    description              VARCHAR(500)  DEFAULT NULL,
    conditions               LONGTEXT,                           -- délai de commande, conservation...
    quantite_restante        INT           NOT NULL,             -- stock de commandes possibles
    actif                    TINYINT       NOT NULL,             -- soft delete
    created_at               DATETIME      NOT NULL,
    theme_id                 INT           DEFAULT NULL,
    regime_id                INT           DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_menu_theme  FOREIGN KEY (theme_id)  REFERENCES theme (id),
    CONSTRAINT fk_menu_regime FOREIGN KEY (regime_id) REFERENCES regime (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : menu_image — galerie photos d'un menu
-- ─────────────────────────────────────────
CREATE TABLE menu_image (
    id          INT           NOT NULL AUTO_INCREMENT,
    url         VARCHAR(500)  NOT NULL,
    alt         VARCHAR(255)  DEFAULT NULL,                      -- texte alternatif (RGAA)
    principale  TINYINT       NOT NULL,                          -- 1 = image principale du menu
    menu_id     INT           NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_image_menu FOREIGN KEY (menu_id) REFERENCES menu (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : menu_plat — association N-N (composition des menus)
-- ─────────────────────────────────────────
CREATE TABLE menu_plat (
    menu_id  INT NOT NULL,
    plat_id  INT NOT NULL,
    PRIMARY KEY (menu_id, plat_id),
    CONSTRAINT fk_mp_menu FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE,
    CONSTRAINT fk_mp_plat FOREIGN KEY (plat_id) REFERENCES plat (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : commande
-- Statuts : en_attente, accepte, en_preparation, en_livraison,
--           livre, retour_materiel, terminee, annulee
-- ─────────────────────────────────────────
CREATE TABLE commande (
    id                 INT           NOT NULL AUTO_INCREMENT,
    numero_commande    VARCHAR(20)   NOT NULL,                   -- référence unique ex: VG-A1B2C3
    date_prestation    DATETIME      NOT NULL,
    adresse_livraison  VARCHAR(255)  NOT NULL,
    ville_livraison    VARCHAR(100)  NOT NULL,
    cp_livraison       VARCHAR(10)   NOT NULL,
    nombre_personnes   INT           NOT NULL,
    prix_menu          DOUBLE        NOT NULL,
    prix_livraison     DOUBLE        NOT NULL,                   -- 5 € + 0,59 €/km hors Bordeaux
    prix_total         DOUBLE        NOT NULL,
    remise             DOUBLE        NOT NULL,                   -- 10 % dès minimum + 5 personnes
    statut             VARCHAR(20)   NOT NULL,
    motif_annulation   LONGTEXT,                                 -- obligatoire à l'annulation employé
    mode_contact       VARCHAR(50)   DEFAULT NULL,
    pret_materiel      TINYINT       NOT NULL,
    created_at         DATETIME      NOT NULL,
    updated_at         DATETIME      NOT NULL,
    utilisateur_id     INT           NOT NULL,
    menu_id            INT           DEFAULT NULL,               -- héritage V1 (nullable) : les menus
                                                                 -- d'une commande sont désormais portés
                                                                 -- par la table commande_menu
    PRIMARY KEY (id),
    UNIQUE KEY uniq_commande_numero (numero_commande),
    CONSTRAINT fk_cmd_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id),
    CONSTRAINT fk_cmd_menu        FOREIGN KEY (menu_id)        REFERENCES menu (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : commande_menu — association porteuse d'attributs
-- (commandes multi-menus : nb de personnes, prix et remise PAR menu)
-- ─────────────────────────────────────────
CREATE TABLE commande_menu (
    id                INT     NOT NULL AUTO_INCREMENT,
    nombre_personnes  INT     NOT NULL,
    prix_total        DOUBLE  NOT NULL,
    remise            DOUBLE  NOT NULL,
    commande_id       INT     NOT NULL,
    menu_id           INT     NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_cm_commande FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE,
    CONSTRAINT fk_cm_menu     FOREIGN KEY (menu_id)     REFERENCES menu (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : commande_plat — plats choisis dans la commande
-- ─────────────────────────────────────────
CREATE TABLE commande_plat (
    id           INT NOT NULL AUTO_INCREMENT,
    commande_id  INT NOT NULL,
    plat_id      INT NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_cp_commande FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE,
    CONSTRAINT fk_cp_plat     FOREIGN KEY (plat_id)     REFERENCES plat (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : suivi_commande — historique des statuts
-- ─────────────────────────────────────────
CREATE TABLE suivi_commande (
    id           INT          NOT NULL AUTO_INCREMENT,
    statut       VARCHAR(50)  NOT NULL,
    commentaire  LONGTEXT,
    created_at   DATETIME     NOT NULL,
    commande_id  INT          NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_suivi_commande FOREIGN KEY (commande_id) REFERENCES commande (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : avis — soumis à modération avant publication
-- ─────────────────────────────────────────
CREATE TABLE avis (
    id              INT           NOT NULL AUTO_INCREMENT,
    note            SMALLINT      NOT NULL,                      -- 1 à 5
    description     VARCHAR(500)  DEFAULT NULL,
    statut          VARCHAR(20)   NOT NULL,                      -- en_attente / valide / refuse
    created_at      DATETIME      NOT NULL,
    utilisateur_id  INT           NOT NULL,
    commande_id     INT           NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_avis_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id),
    CONSTRAINT fk_avis_commande    FOREIGN KEY (commande_id)    REFERENCES commande (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : horaire — jours 1 (lundi) à 7 (dimanche)
-- ─────────────────────────────────────────
CREATE TABLE horaire (
    id               INT          NOT NULL AUTO_INCREMENT,
    jour             SMALLINT     NOT NULL,
    heure_ouverture  VARCHAR(5)   DEFAULT NULL,
    heure_fermeture  VARCHAR(5)   DEFAULT NULL,
    service          VARCHAR(10)  NOT NULL,                      -- midi / soir
    ferme            TINYINT      NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- TABLE : contact — messages du formulaire public
-- ─────────────────────────────────────────
CREATE TABLE contact (
    id           INT           NOT NULL AUTO_INCREMENT,
    email        VARCHAR(255)  NOT NULL,
    titre        VARCHAR(255)  NOT NULL,
    description  LONGTEXT      NOT NULL,
    traite       TINYINT       NOT NULL,
    created_at   DATETIME      NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════
-- DONNÉES DE DÉPART
-- (les tables commande, commande_menu, commande_plat,
--  suivi_commande, avis, contact et refresh_token sont
--  alimentées par l'application)
-- ═══════════════════════════════════════════════════════════

-- Rôles
INSERT INTO role (id, libelle) VALUES
    (1, 'administrateur'),
    (2, 'employe'),
    (3, 'utilisateur');

-- Thèmes
INSERT INTO theme (id, libelle) VALUES
    (1, 'Classique'),
    (2, 'Noël'),
    (3, 'Pâques'),
    (4, 'Événement');

-- Régimes
INSERT INTO regime (id, libelle) VALUES
    (1, 'Classique'),
    (2, 'Végétarien'),
    (3, 'Vegan'),
    (4, 'Sans gluten');

-- Allergènes
INSERT INTO allergene (id, libelle) VALUES
    (1, 'Gluten'),
    (2, 'Lactose'),
    (3, 'Oeufs'),
    (4, 'Fruits de mer'),
    (5, 'Crustacés'),
    (6, 'Fruits à coque'),
    (7, 'Alcool'),
    (8, 'Moutarde'),
    (9, 'Soja'),
    (10, 'Arachides'),
    (11, 'Céleri'),
    (12, 'Céleri');

-- Utilisateurs — comptes de démonstration (mots de passe : voir README / manuel)
INSERT INTO utilisateur (id, email, password, nom, prenom, telephone, adresse, actif, created_at, role_id, reset_token, reset_token_expires_at, pseudonyme) VALUES
    (1, 'jose@vitegourmand.fr', '$2y$12$LHmVKFNMbFnbpRVBmNRbOeabrH3kG1QK6oxrRcyB7K9XFVJ4XGQC2', 'Vite', 'José', '05 56 00 00 01', '1 rue du Chef, 33000 Bordeaux', 1, '2026-06-01 09:43:19', 1, NULL, NULL, NULL),
    (2, 'admin@vitegourmand.fr', '$2y$12$UNA7Nll7zSSaFsyygWvRRurngbAqRUxHhupg22aTgI48kZl7NqL0C', 'Admin', 'Vite', NULL, NULL, 1, '2026-06-01 09:43:19', 1, NULL, NULL, NULL),
    (3, 'sophie@vitegourmand.fr', '$2y$12$LHmVKFNMbFnbpRVBmNRbOeabrH3kG1QK6oxrRcyB7K9XFVJ4XGQC2', 'Lambert', 'Sophie', '05 56 00 00 02', NULL, 1, '2026-06-01 09:43:19', 2, NULL, NULL, NULL),
    (4, 'employe@vitegourmand.fr', '$2y$12$Whb3XSXA.b5szqO.aUW0/.N3BJ9S26syV7.TmmwIHQcA2LQJ7qG8C', 'Employe', 'Test', NULL, NULL, 1, '2026-06-01 09:43:19', 2, NULL, NULL, NULL),
    (5, 'marie.dupont@email.com', '$2y$12$LHmVKFNMbFnbpRVBmNRbOeabrH3kG1QK6oxrRcyB7K9XFVJ4XGQC2', 'Dupont', 'Marie', '06 12 34 56 78', '12 rue des Fleurs, 33000 Bordeaux', 1, '2026-06-01 09:43:19', 3, NULL, NULL, NULL),
    (6, 'jean.martin@email.com', '$2y$12$LHmVKFNMbFnbpRVBmNRbOeabrH3kG1QK6oxrRcyB7K9XFVJ4XGQC2', 'Martin', 'Jean', '07 98 76 54 32', '8 allée des Roses, 33200 Bordeaux', 1, '2026-06-01 09:43:19', 3, NULL, NULL, NULL),
    (7, 'camille.d@email.com', '$2y$12$LHmVKFNMbFnbpRVBmNRbOeabrH3kG1QK6oxrRcyB7K9XFVJ4XGQC2', 'Dubois', 'Camille', '06 55 44 33 22', '3 place de la Victoire, 33000 Bordeaux', 1, '2026-06-01 09:43:19', 3, NULL, NULL, NULL),
    (15, 'client@vitegourmand.fr', '$2y$12$pUFE5YNSJTybfXl6jrZFoO1zUWUmLCeCwPMSz2NUKx3eH8.phgvL6', 'Client', 'Test', '0601020304', '12 rue de la paix', 1, '2026-07-03 09:28:13', 3, NULL, NULL, NULL),
    (37, 'marie.dupont24@email.com', '$2y$12$6xrdeqQWT2x91EPb9DOpLOoF6L3EhNDaxAf5zMP1NPpmEYRDJu7dW', 'Dupont', 'Marie', '0601020304', '12 rue de la paix', 1, '2026-07-20 16:37:49', 3, NULL, NULL, 'Marie D.');

-- Menus
INSERT INTO menu (id, titre, nombre_personne_minimum, prix_par_personne, description, conditions, quantite_restante, actif, created_at, theme_id, regime_id) VALUES
    (1, 'Menu Découverte', 2, 35, 'Une expérience gourmande autour des grands classiques recettes, mettant en valeur des produits de saison et des associations équilibrées.', 'Ce menu doit être commandé au minimum 48h avant la prestation. Conserver les produits frais entre 2°C et 4°C.', 4, 1, '2026-06-01 09:43:19', 1, 1),
    (2, 'Menu Gastronomique', 2, 55, 'Un menu raffiné signé par le chef, alliant techniques gastronomiques, produits d\'exception et dressages élégants pour une expérience unique.', 'Ce menu doit être commandé au minimum 72h avant la prestation. Certains produits nécessitent une confirmation de disponibilité.', 3, 1, '2026-06-01 09:43:19', 4, 1),
    (3, 'Menu Rapide', 1, 15, 'Une formule efficace et savoureuse pour la pause déjeuner, avec des plats généreux préparés rapidement à base de produits frais.', 'Commande possible jusqu\'à 2h avant la livraison. Menu disponible uniquement le midi (11h-14h).', 15, 1, '2026-06-01 09:43:19', 1, 1),
    (4, 'Menu Vegan', 2, 28, 'Un voyage culinaire 100% végétal, pensé pour sublimer les saveurs naturelles. Des produits frais et de saison, soigneusement sélectionnés pour une expérience gourmande et respectueuse de l\'environnement.', 'Ce menu doit être commandé au minimum 48h avant la prestation. Produits 100% végétaux, sans viande, sans poisson, sans produits laitiers ni œufs.', 7, 1, '2026-06-04 10:46:25', 1, 3);

-- Images des menus (fichiers versionnés dans public/images/)
INSERT INTO menu_image (id, url, alt, principale, menu_id) VALUES
    (1, 'images/saumon.jpg', 'Menu Découverte', 1, 1),
    (2, 'images/gastro1.webp', 'Menu Gastronomique', 1, 2),
    (3, 'images/pizza.jpg', 'Menu Rapide', 1, 3),
    (4, 'images/salade.jpg', 'Menu Découverte 2', 0, 1),
    (7, 'images/vegan1.webp', 'Menu Vegan', 1, 4),
    (18, 'images/gastro2.webp', 'Menu Gastronomique', 0, 2),
    (19, 'images/vegan2.webp', 'Menu Vegan', 0, 4),
    (20, 'images/rapide1.webp', 'Menu Rapide', 0, 3);

-- Plats
INSERT INTO plat (id, type_plat, nom, description) VALUES
    (1, 'entree', 'Velouté de potimarron aux châtaignes', 'Velouté onctueux de potimarron servi avec des châtaignes rôties'),
    (2, 'entree', 'Carpaccio de Saint-Jacques', 'Saint-Jacques à l\'huile de truffe et citron vert'),
    (3, 'entree', 'Huitres Gillardeau n°2', 'Huitres spéciales de la maison Gillardeau'),
    (4, 'entree', 'Foie gras poêlé', 'Foie gras poêlé, chutney de figues et pain d\'épices'),
    (5, 'entree', 'Soupe du jour', 'Soupe fraîche préparée selon les arrivages du marché'),
    (6, 'entree', 'Salade composée', 'Salade de saison avec vinaigrette maison'),
    (7, 'plat', 'Filet de bar rôti', 'Bar rôti, écrasé de pommes de terre à la truffe noire'),
    (8, 'plat', 'Suprême de volaille fermière', 'Volaille fermière en sauce morilles'),
    (9, 'plat', 'Homard bleu rôti', 'Homard bleu rôti, beurre coral line'),
    (10, 'plat', 'Carré d\'agneau de lait', 'Carré d\'agneau, purée de céleri-rave à la truffe'),
    (11, 'plat', 'Burger maison', 'Burger artisanal avec frites fraîches maison'),
    (12, 'plat', 'Quiche lorraine', 'Quiche lorraine traditionnelle et salade verte'),
    (13, 'dessert', 'Moelleux au chocolat', 'Moelleux chocolat noir, coeur coulant caramel beurre salé'),
    (14, 'dessert', 'Tarte fine aux pommes', 'Tarte fine aux pommes de Normandie, glace vanille Bourbon'),
    (15, 'dessert', 'Soufflé au Grand Marnier', 'Soufflé chaud au Grand Marnier'),
    (16, 'dessert', 'Dôme chocolat Guanaja', 'Dôme au chocolat Guanaja 70%, croustillant praliné'),
    (17, 'dessert', 'Crème brûlée', 'Crème brûlée à la vanille de Madagascar'),
    (18, 'dessert', 'Tarte du jour', 'Selon les arrivages et l\'inspiration du chef'),
    (19, 'entree', 'Velouté de courgettes au basilic', 'Velouté onctueux de courgettes fraîches, relevé d\'un pistou de basilic et d\'un filet d\'huile d\'olive vierge.'),
    (21, 'plat', 'Curry de pois chiches aux épices douces', 'Pois chiches mijotés dans une sauce au lait de coco, curcuma, gingembre frais et coriandre, servis avec du riz basmati.'),
    (22, 'dessert', 'Mousse au chocolat noir vegan', 'Mousse légère et aérienne à base de chocolat noir 70% et d\'aquafaba, sans produits laitiers ni œufs.');

-- Liaison menus <-> plats
INSERT INTO menu_plat (menu_id, plat_id) VALUES
    (1, 1),
    (1, 2),
    (1, 7),
    (1, 8),
    (1, 13),
    (1, 14),
    (2, 3),
    (2, 4),
    (2, 9),
    (2, 10),
    (2, 15),
    (2, 16),
    (3, 5),
    (3, 6),
    (3, 11),
    (3, 12),
    (3, 17),
    (3, 18),
    (4, 19),
    (4, 21),
    (4, 22);

-- Allergènes des plats
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES
    (1, 2),
    (1, 3),
    (2, 4),
    (3, 4),
    (3, 5),
    (4, 1),
    (4, 2),
    (4, 7),
    (7, 1),
    (7, 2),
    (8, 2),
    (8, 3),
    (9, 2),
    (9, 5),
    (10, 2),
    (11, 1),
    (11, 3),
    (11, 8),
    (12, 1),
    (12, 2),
    (12, 3),
    (13, 2),
    (13, 3),
    (14, 2),
    (14, 3),
    (15, 2),
    (15, 3),
    (15, 7),
    (16, 2),
    (16, 3),
    (16, 6),
    (17, 2),
    (17, 3),
    (18, 1),
    (18, 2),
    (18, 3),
    (19, 11),
    (21, 8),
    (21, 11),
    (22, 9);

-- Horaires
INSERT INTO horaire (id, jour, heure_ouverture, heure_fermeture, service, ferme) VALUES
    (1, 1, '12:00', '14:00', 'midi', 0),
    (2, 1, '19:00', '22:00', 'soir', 0),
    (3, 2, '12:00', '14:00', 'midi', 0),
    (4, 2, '19:00', '22:00', 'soir', 0),
    (5, 3, '12:00', '14:00', 'midi', 0),
    (6, 3, '19:00', '22:00', 'soir', 0),
    (7, 4, '12:00', '14:00', 'midi', 0),
    (8, 4, '19:00', '22:00', 'soir', 0),
    (9, 5, '12:00', '14:00', 'midi', 0),
    (10, 5, '19:00', '22:00', 'soir', 0),
    (11, 6, '12:00', '22:00', 'jour', 0),
    (12, 7, '12:00', '22:00', 'jour', 0);

SET FOREIGN_KEY_CHECKS = 1;
