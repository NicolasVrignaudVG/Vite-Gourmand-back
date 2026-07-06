-- ═══════════════════════════════════════════════════════════
-- Vite & Gourmand — Données de départ uniquement
-- À importer APRÈS les migrations Doctrine
-- ═══════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;

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
    (3, 'Œufs'),
    (4, 'Fruits de mer'),
    (5, 'Crustacés'),
    (6, 'Fruits à coque'),
    (7, 'Alcool'),
    (8, 'Moutarde'),
    (9, 'Soja'),
    (10, 'Arachides');

-- Utilisateurs (mots de passe : Admin@1234 / Employe@1234 / User@1234, hashés bcrypt)
INSERT INTO utilisateur (id, email, password, nom, prenom, telephone, adresse, actif, role_id, created_at) VALUES
    (1, 'admin@vitegourmand.fr',   '$2y$12$5S6p7slYIYUDeyKNjA1oz.gK6nG/1aU/MbsG3flUaiMOXQ5UrS6WC', 'Vite',   'José',   '05 56 00 00 01', '1 rue du Chef, 33000 Bordeaux',          1, 1, NOW()),
    (2, 'employe@vitegourmand.fr', '$2y$12$20pu06UXm8x2Vep2XjdSZOHn8MbB0//W/nsrf3RYADtnXyCr2J0Py', 'Lambert','Sophie', '05 56 00 00 02', NULL,                                     1, 2, NOW()),
    (3, 'marc@vitegourmand.fr',    '$2y$12$20pu06UXm8x2Vep2XjdSZOHn8MbB0//W/nsrf3RYADtnXyCr2J0Py', 'Petit',  'Marc',   '05 56 00 00 03', NULL,                                     0, 2, NOW()),
    (4, 'marie.dupont@email.com', '$2y$12$6GtDvXpGIaopSTZ64.S7geaLPLoOHUR6YGEizuzr0Twe/RDLKA.w6', 'Dupont', 'Marie',  '06 12 34 56 78', '12 rue des Fleurs, 33000 Bordeaux',      1, 3, NOW()),
    (5, 'jean.martin@email.com',  '$2y$12$6GtDvXpGIaopSTZ64.S7geaLPLoOHUR6YGEizuzr0Twe/RDLKA.w6', 'Martin', 'Jean',   '07 98 76 54 32', '8 allée des Roses, 33200 Bordeaux',      1, 3, NOW()),
    (6, 'camille.d@email.com',    '$2y$12$6GtDvXpGIaopSTZ64.S7geaLPLoOHUR6YGEizuzr0Twe/RDLKA.w6', 'Dubois', 'Camille','06 55 44 33 22', '3 place de la Victoire, 33000 Bordeaux', 1, 3, NOW());

-- Menus
INSERT INTO menu (id, titre, nombre_personne_minimum, prix_par_personne, description, conditions, quantite_restante, theme_id, regime_id, actif, created_at) VALUES
    (1, 'Menu Découverte',    2, 35.00, 'Une expérience gourmande autour des grands classiques recettes, mettant en valeur des produits de saison et des associations équilibrées.', 'Ce menu doit être commandé au minimum 48h avant la prestation. Conserver les produits frais entre 2°C et 4°C.', 8,  1, 1, 1, NOW()),
    (2, 'Menu Gastronomique', 2, 55.00, 'Un menu raffiné signé par le chef, alliant techniques gastronomiques, produits d''exception et dressages élégants pour une expérience unique.', 'Ce menu doit être commandé au minimum 72h avant la prestation. Certains produits nécessitent une confirmation de disponibilité.', 5,  4, 1, 1, NOW()),
    (3, 'Menu Rapide',        1, 15.00, 'Une formule efficace et savoureuse pour la pause déjeuner, avec des plats généreux préparés rapidement à base de produits frais.', 'Commande possible jusqu''à 2h avant la livraison. Menu disponible uniquement le midi (11h-14h).', 20, 1, 1, 1, NOW());

-- Images des menus
INSERT INTO menu_image (id, menu_id, url, alt, principale) VALUES
    (1, 1, 'images/kifotofotografia-food-8151625.jpg',    'Menu Découverte',    1),
    (2, 2, 'images/joannawielgosz-pasta-7209002-1.png',   'Menu Gastronomique', 1),
    (3, 3, 'images/joannawielgosz-pasta-7209002-1-2.png', 'Menu Rapide',        1);

-- Plats
INSERT INTO plat (id, type_plat, nom, description) VALUES
    (1,  'entree',  'Velouté de potimarron aux châtaignes', 'Velouté onctueux de potimarron servi avec des châtaignes rôties'),
    (2,  'entree',  'Carpaccio de Saint-Jacques',           'Saint-Jacques à l''huile de truffe et citron vert'),
    (3,  'entree',  'Huitres Gillardeau n°2',               'Huitres spéciales de la maison Gillardeau'),
    (4,  'entree',  'Foie gras poêlé',                      'Foie gras poêlé, chutney de figues et pain d''épices'),
    (5,  'entree',  'Soupe du jour',                        'Soupe fraîche préparée selon les arrivages du marché'),
    (6,  'entree',  'Salade composée',                      'Salade de saison avec vinaigrette maison'),
    (7,  'plat',    'Filet de bar rôti',                    'Bar rôti, écrasé de pommes de terre à la truffe noire'),
    (8,  'plat',    'Suprême de volaille fermière',          'Volaille fermière en sauce morilles'),
    (9,  'plat',    'Homard bleu rôti',                     'Homard bleu rôti, beurre coral line'),
    (10, 'plat',    'Carré d''agneau de lait',              'Carré d''agneau, purée de céleri-rave à la truffe'),
    (11, 'plat',    'Burger maison',                        'Burger artisanal avec frites fraîches maison'),
    (12, 'plat',    'Quiche lorraine',                      'Quiche lorraine traditionnelle et salade verte'),
    (13, 'dessert', 'Moelleux au chocolat',                 'Moelleux chocolat noir, cœur coulant caramel beurre salé'),
    (14, 'dessert', 'Tarte fine aux pommes',                'Tarte fine aux pommes de Normandie, glace vanille Bourbon'),
    (15, 'dessert', 'Soufflé au Grand Marnier',             'Soufflé chaud au Grand Marnier'),
    (16, 'dessert', 'Dôme chocolat Guanaja',                'Dôme au chocolat Guanaja 70%, croustillant praliné'),
    (17, 'dessert', 'Crème brûlée',                         'Crème brûlée à la vanille de Madagascar'),
    (18, 'dessert', 'Tarte du jour',                        'Selon les arrivages et l''inspiration du chef');

-- Liaison menus <-> plats
INSERT INTO menu_plat (menu_id, plat_id) VALUES
    (1,1),(1,2),(1,7),(1,8),(1,13),(1,14),
    (2,3),(2,4),(2,9),(2,10),(2,15),(2,16),
    (3,5),(3,6),(3,11),(3,12),(3,17),(3,18);

-- Allergènes des plats
INSERT INTO plat_allergene (plat_id, allergene_id) VALUES
    (1,2),(1,3),(2,4),(3,4),(3,5),(4,1),(4,2),(4,7),
    (7,2),(7,1),(8,2),(8,3),(9,5),(9,2),(10,2),
    (11,1),(11,3),(11,8),(12,1),(12,2),(12,3),
    (13,2),(13,3),(14,2),(14,3),(15,2),(15,3),(15,7),
    (16,2),(16,3),(16,6),(17,2),(17,3),(18,1),(18,2),(18,3);

-- Horaires
INSERT INTO horaire (id, jour, heure_ouverture, heure_fermeture, service, ferme) VALUES
    (1, 1, '12:00', '14:00', 'midi', 0), (2, 1, '19:00', '22:00', 'soir', 0),
    (3, 2, '12:00', '14:00', 'midi', 0), (4, 2, '19:00', '22:00', 'soir', 0),
    (5, 3, '12:00', '14:00', 'midi', 0), (6, 3, '19:00', '22:00', 'soir', 0),
    (7, 4, '12:00', '14:00', 'midi', 0), (8, 4, '19:00', '22:00', 'soir', 0),
    (9, 5, '12:00', '14:00', 'midi', 0), (10,5, '19:00', '22:00', 'soir', 0),
    (11,6, '12:00', '23:30', 'midi', 0), (12,6, NULL,    NULL,    'soir', 1),
    (13,7, '12:00', '23:30', 'midi', 0), (14,7, NULL,    NULL,    'soir', 1);

SET FOREIGN_KEY_CHECKS = 1;
