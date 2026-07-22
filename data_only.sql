-- ═══════════════════════════════════════════════════════════
-- Vite & Gourmand — Données de départ uniquement
-- À importer APRÈS les migrations Doctrine
-- Export depuis la base de production (Clever Cloud)
-- ═══════════════════════════════════════════════════════════

SET FOREIGN_KEY_CHECKS = 0;

-- Rôles
INSERT INTO `role` (`id`, `libelle`) VALUES(1, 'administrateur');
INSERT INTO `role` (`id`, `libelle`) VALUES(2, 'employe');
INSERT INTO `role` (`id`, `libelle`) VALUES(3, 'utilisateur');

-- Thèmes
INSERT INTO `theme` (`id`, `libelle`) VALUES(1, 'Classique');
INSERT INTO `theme` (`id`, `libelle`) VALUES(2, 'Noël');
INSERT INTO `theme` (`id`, `libelle`) VALUES(3, 'Pâques');
INSERT INTO `theme` (`id`, `libelle`) VALUES(4, 'Événement');

-- Régimes
INSERT INTO `regime` (`id`, `libelle`) VALUES(1, 'Classique');
INSERT INTO `regime` (`id`, `libelle`) VALUES(2, 'Végétarien');
INSERT INTO `regime` (`id`, `libelle`) VALUES(3, 'Vegan');
INSERT INTO `regime` (`id`, `libelle`) VALUES(4, 'Sans gluten');

-- Allergènes
INSERT INTO `allergene` (`id`, `libelle`) VALUES(1, 'Gluten');
INSERT INTO `allergene` (`id`, `libelle`) VALUES(2, 'Lactose');
INSERT INTO `allergene` (`id`, `libelle`) VALUES(3, 'Oeufs');
INSERT INTO `allergene` (`id`, `libelle`) VALUES(4, 'Fruits de mer');
INSERT INTO `allergene` (`id`, `libelle`) VALUES(5, 'Crustacés');
INSERT INTO `allergene` (`id`, `libelle`) VALUES(6, 'Fruits à coque');
INSERT INTO `allergene` (`id`, `libelle`) VALUES(7, 'Alcool');
INSERT INTO `allergene` (`id`, `libelle`) VALUES(8, 'Moutarde');
INSERT INTO `allergene` (`id`, `libelle`) VALUES(9, 'Soja');
INSERT INTO `allergene` (`id`, `libelle`) VALUES(10, 'Arachides');
INSERT INTO `allergene` (`id`, `libelle`) VALUES(11, 'Céleri');
INSERT INTO `allergene` (`id`, `libelle`) VALUES(12, 'Céleri');

-- Utilisateurs — comptes de démonstration (mots de passe : voir README / manuel)
INSERT INTO `utilisateur` (`id`, `email`, `password`, `nom`, `prenom`, `telephone`, `adresse`, `actif`, `created_at`, `role_id`, `reset_token`, `reset_token_expires_at`, `pseudonyme`) VALUES(1, 'jose@vitegourmand.fr', '$2y$12$LHmVKFNMbFnbpRVBmNRbOeabrH3kG1QK6oxrRcyB7K9XFVJ4XGQC2', 'Vite', 'José', '05 56 00 00 01', '1 rue du Chef, 33000 Bordeaux', 1, '2026-06-01 09:43:19', 1, NULL, NULL, NULL);
INSERT INTO `utilisateur` (`id`, `email`, `password`, `nom`, `prenom`, `telephone`, `adresse`, `actif`, `created_at`, `role_id`, `reset_token`, `reset_token_expires_at`, `pseudonyme`) VALUES(2, 'admin@vitegourmand.fr', '$2y$12$UNA7Nll7zSSaFsyygWvRRurngbAqRUxHhupg22aTgI48kZl7NqL0C', 'Admin', 'Vite', NULL, NULL, 1, '2026-06-01 09:43:19', 1, NULL, NULL, NULL);
INSERT INTO `utilisateur` (`id`, `email`, `password`, `nom`, `prenom`, `telephone`, `adresse`, `actif`, `created_at`, `role_id`, `reset_token`, `reset_token_expires_at`, `pseudonyme`) VALUES(3, 'sophie@vitegourmand.fr', '$2y$12$LHmVKFNMbFnbpRVBmNRbOeabrH3kG1QK6oxrRcyB7K9XFVJ4XGQC2', 'Lambert', 'Sophie', '05 56 00 00 02', NULL, 1, '2026-06-01 09:43:19', 2, NULL, NULL, NULL);
INSERT INTO `utilisateur` (`id`, `email`, `password`, `nom`, `prenom`, `telephone`, `adresse`, `actif`, `created_at`, `role_id`, `reset_token`, `reset_token_expires_at`, `pseudonyme`) VALUES(4, 'employe@vitegourmand.fr', '$2y$12$Whb3XSXA.b5szqO.aUW0/.N3BJ9S26syV7.TmmwIHQcA2LQJ7qG8C', 'Employe', 'Test', NULL, NULL, 1, '2026-06-01 09:43:19', 2, NULL, NULL, NULL);
INSERT INTO `utilisateur` (`id`, `email`, `password`, `nom`, `prenom`, `telephone`, `adresse`, `actif`, `created_at`, `role_id`, `reset_token`, `reset_token_expires_at`, `pseudonyme`) VALUES(5, 'marie.dupont@email.com', '$2y$12$LHmVKFNMbFnbpRVBmNRbOeabrH3kG1QK6oxrRcyB7K9XFVJ4XGQC2', 'Dupont', 'Marie', '06 12 34 56 78', '12 rue des Fleurs, 33000 Bordeaux', 1, '2026-06-01 09:43:19', 3, NULL, NULL, NULL);
INSERT INTO `utilisateur` (`id`, `email`, `password`, `nom`, `prenom`, `telephone`, `adresse`, `actif`, `created_at`, `role_id`, `reset_token`, `reset_token_expires_at`, `pseudonyme`) VALUES(6, 'jean.martin@email.com', '$2y$12$LHmVKFNMbFnbpRVBmNRbOeabrH3kG1QK6oxrRcyB7K9XFVJ4XGQC2', 'Martin', 'Jean', '07 98 76 54 32', '8 allée des Roses, 33200 Bordeaux', 1, '2026-06-01 09:43:19', 3, NULL, NULL, NULL);
INSERT INTO `utilisateur` (`id`, `email`, `password`, `nom`, `prenom`, `telephone`, `adresse`, `actif`, `created_at`, `role_id`, `reset_token`, `reset_token_expires_at`, `pseudonyme`) VALUES(7, 'camille.d@email.com', '$2y$12$LHmVKFNMbFnbpRVBmNRbOeabrH3kG1QK6oxrRcyB7K9XFVJ4XGQC2', 'Dubois', 'Camille', '06 55 44 33 22', '3 place de la Victoire, 33000 Bordeaux', 1, '2026-06-01 09:43:19', 3, NULL, NULL, NULL);
INSERT INTO `utilisateur` (`id`, `email`, `password`, `nom`, `prenom`, `telephone`, `adresse`, `actif`, `created_at`, `role_id`, `reset_token`, `reset_token_expires_at`, `pseudonyme`) VALUES(15, 'client@vitegourmand.fr', '$2y$12$pUFE5YNSJTybfXl6jrZFoO1zUWUmLCeCwPMSz2NUKx3eH8.phgvL6', 'Client', 'Test', '0601020304', '12 rue de la paix', 1, '2026-07-03 09:28:13', 3, NULL, NULL, NULL);
INSERT INTO `utilisateur` (`id`, `email`, `password`, `nom`, `prenom`, `telephone`, `adresse`, `actif`, `created_at`, `role_id`, `reset_token`, `reset_token_expires_at`, `pseudonyme`) VALUES(37, 'marie.dupont24@email.com', '$2y$12$6xrdeqQWT2x91EPb9DOpLOoF6L3EhNDaxAf5zMP1NPpmEYRDJu7dW', 'Dupont', 'Marie', '0601020304', '12 rue de la paix', 1, '2026-07-20 16:37:49', 3, NULL, NULL, 'Marie D.');

-- Menus
INSERT INTO `menu` (`id`, `titre`, `nombre_personne_minimum`, `prix_par_personne`, `description`, `conditions`, `quantite_restante`, `actif`, `created_at`, `theme_id`, `regime_id`) VALUES(1, 'Menu Découverte', 2, 35, 'Une expérience gourmande autour des grands classiques recettes, mettant en valeur des produits de saison et des associations équilibrées.', 'Ce menu doit être commandé au minimum 48h avant la prestation. Conserver les produits frais entre 2°C et 4°C.', 4, 1, '2026-06-01 09:43:19', 1, 1);
INSERT INTO `menu` (`id`, `titre`, `nombre_personne_minimum`, `prix_par_personne`, `description`, `conditions`, `quantite_restante`, `actif`, `created_at`, `theme_id`, `regime_id`) VALUES(2, 'Menu Gastronomique', 2, 55, 'Un menu raffiné signé par le chef, alliant techniques gastronomiques, produits d\'exception et dressages élégants pour une expérience unique.', 'Ce menu doit être commandé au minimum 72h avant la prestation. Certains produits nécessitent une confirmation de disponibilité.', 3, 1, '2026-06-01 09:43:19', 4, 1);
INSERT INTO `menu` (`id`, `titre`, `nombre_personne_minimum`, `prix_par_personne`, `description`, `conditions`, `quantite_restante`, `actif`, `created_at`, `theme_id`, `regime_id`) VALUES(3, 'Menu Rapide', 1, 15, 'Une formule efficace et savoureuse pour la pause déjeuner, avec des plats généreux préparés rapidement à base de produits frais.', 'Commande possible jusqu\'à 2h avant la livraison. Menu disponible uniquement le midi (11h-14h).', 15, 1, '2026-06-01 09:43:19', 1, 1);
INSERT INTO `menu` (`id`, `titre`, `nombre_personne_minimum`, `prix_par_personne`, `description`, `conditions`, `quantite_restante`, `actif`, `created_at`, `theme_id`, `regime_id`) VALUES(4, 'Menu Vegan', 2, 28, 'Un voyage culinaire 100% végétal, pensé pour sublimer les saveurs naturelles. Des produits frais et de saison, soigneusement sélectionnés pour une expérience gourmande et respectueuse de l\'environnement.', 'Ce menu doit être commandé au minimum 48h avant la prestation. Produits 100% végétaux, sans viande, sans poisson, sans produits laitiers ni œufs.', 7, 1, '2026-06-04 10:46:25', 1, 3);

-- Images des menus (fichiers versionnés dans public/images/)
INSERT INTO `menu_image` (`id`, `url`, `alt`, `principale`, `menu_id`) VALUES(1, 'images/saumon.jpg', 'Menu Découverte', 1, 1);
INSERT INTO `menu_image` (`id`, `url`, `alt`, `principale`, `menu_id`) VALUES(2, 'images/gastro1.webp', 'Menu Gastronomique', 1, 2);
INSERT INTO `menu_image` (`id`, `url`, `alt`, `principale`, `menu_id`) VALUES(3, 'images/pizza.jpg', 'Menu Rapide', 1, 3);
INSERT INTO `menu_image` (`id`, `url`, `alt`, `principale`, `menu_id`) VALUES(4, 'images/salade.jpg', 'Menu Découverte 2', 0, 1);
INSERT INTO `menu_image` (`id`, `url`, `alt`, `principale`, `menu_id`) VALUES(7, 'images/vegan1.webp', 'Menu Vegan', 1, 4);
INSERT INTO `menu_image` (`id`, `url`, `alt`, `principale`, `menu_id`) VALUES(18, 'images/gastro2.webp', 'Menu Gastronomique', 0, 2);
INSERT INTO `menu_image` (`id`, `url`, `alt`, `principale`, `menu_id`) VALUES(19, 'images/vegan2.webp', 'Menu Vegan', 0, 4);
INSERT INTO `menu_image` (`id`, `url`, `alt`, `principale`, `menu_id`) VALUES(20, 'images/rapide1.webp', 'Menu Rapide', 0, 3);

-- Plats
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(1, 'entree', 'Velouté de potimarron aux châtaignes', 'Velouté onctueux de potimarron servi avec des châtaignes rôties');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(2, 'entree', 'Carpaccio de Saint-Jacques', 'Saint-Jacques à l\'huile de truffe et citron vert');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(3, 'entree', 'Huitres Gillardeau n°2', 'Huitres spéciales de la maison Gillardeau');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(4, 'entree', 'Foie gras poêlé', 'Foie gras poêlé, chutney de figues et pain d\'épices');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(5, 'entree', 'Soupe du jour', 'Soupe fraîche préparée selon les arrivages du marché');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(6, 'entree', 'Salade composée', 'Salade de saison avec vinaigrette maison');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(7, 'plat', 'Filet de bar rôti', 'Bar rôti, écrasé de pommes de terre à la truffe noire');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(8, 'plat', 'Suprême de volaille fermière', 'Volaille fermière en sauce morilles');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(9, 'plat', 'Homard bleu rôti', 'Homard bleu rôti, beurre coral line');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(10, 'plat', 'Carré d\'agneau de lait', 'Carré d\'agneau, purée de céleri-rave à la truffe');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(11, 'plat', 'Burger maison', 'Burger artisanal avec frites fraîches maison');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(12, 'plat', 'Quiche lorraine', 'Quiche lorraine traditionnelle et salade verte');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(13, 'dessert', 'Moelleux au chocolat', 'Moelleux chocolat noir, coeur coulant caramel beurre salé');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(14, 'dessert', 'Tarte fine aux pommes', 'Tarte fine aux pommes de Normandie, glace vanille Bourbon');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(15, 'dessert', 'Soufflé au Grand Marnier', 'Soufflé chaud au Grand Marnier');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(16, 'dessert', 'Dôme chocolat Guanaja', 'Dôme au chocolat Guanaja 70%, croustillant praliné');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(17, 'dessert', 'Crème brûlée', 'Crème brûlée à la vanille de Madagascar');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(18, 'dessert', 'Tarte du jour', 'Selon les arrivages et l\'inspiration du chef');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(19, 'entree', 'Velouté de courgettes au basilic', 'Velouté onctueux de courgettes fraîches, relevé d\'un pistou de basilic et d\'un filet d\'huile d\'olive vierge.');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(21, 'plat', 'Curry de pois chiches aux épices douces', 'Pois chiches mijotés dans une sauce au lait de coco, curcuma, gingembre frais et coriandre, servis avec du riz basmati.');
INSERT INTO `plat` (`id`, `type_plat`, `nom`, `description`) VALUES(22, 'dessert', 'Mousse au chocolat noir vegan', 'Mousse légère et aérienne à base de chocolat noir 70% et d\'aquafaba, sans produits laitiers ni œufs.');

-- Liaison menus <-> plats
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(1, 1);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(1, 2);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(1, 7);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(1, 8);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(1, 13);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(1, 14);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(2, 3);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(2, 4);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(2, 9);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(2, 10);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(2, 15);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(2, 16);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(3, 5);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(3, 6);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(3, 11);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(3, 12);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(3, 17);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(3, 18);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(4, 19);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(4, 21);
INSERT INTO `menu_plat` (`menu_id`, `plat_id`) VALUES(4, 22);

-- Allergènes des plats
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(1, 2);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(1, 3);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(2, 4);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(3, 4);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(3, 5);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(4, 1);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(4, 2);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(4, 7);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(7, 1);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(7, 2);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(8, 2);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(8, 3);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(9, 2);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(9, 5);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(10, 2);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(11, 1);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(11, 3);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(11, 8);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(12, 1);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(12, 2);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(12, 3);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(13, 2);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(13, 3);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(14, 2);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(14, 3);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(15, 2);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(15, 3);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(15, 7);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(16, 2);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(16, 3);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(16, 6);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(17, 2);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(17, 3);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(18, 1);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(18, 2);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(18, 3);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(19, 11);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(21, 8);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(21, 11);
INSERT INTO `plat_allergene` (`plat_id`, `allergene_id`) VALUES(22, 9);

-- Horaires
INSERT INTO `horaire` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `service`, `ferme`) VALUES(1, 1, '12:00', '14:00', 'midi', 0);
INSERT INTO `horaire` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `service`, `ferme`) VALUES(2, 1, '19:00', '22:00', 'soir', 0);
INSERT INTO `horaire` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `service`, `ferme`) VALUES(3, 2, '12:00', '14:00', 'midi', 0);
INSERT INTO `horaire` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `service`, `ferme`) VALUES(4, 2, '19:00', '22:00', 'soir', 0);
INSERT INTO `horaire` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `service`, `ferme`) VALUES(5, 3, '12:00', '14:00', 'midi', 0);
INSERT INTO `horaire` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `service`, `ferme`) VALUES(6, 3, '19:00', '22:00', 'soir', 0);
INSERT INTO `horaire` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `service`, `ferme`) VALUES(7, 4, '12:00', '14:00', 'midi', 0);
INSERT INTO `horaire` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `service`, `ferme`) VALUES(8, 4, '19:00', '22:00', 'soir', 0);
INSERT INTO `horaire` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `service`, `ferme`) VALUES(9, 5, '12:00', '14:00', 'midi', 0);
INSERT INTO `horaire` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `service`, `ferme`) VALUES(10, 5, '19:00', '22:00', 'soir', 0);
INSERT INTO `horaire` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `service`, `ferme`) VALUES(11, 6, '12:00', '22:00', 'jour', 0);
INSERT INTO `horaire` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `service`, `ferme`) VALUES(12, 7, '12:00', '22:00', 'jour', 0);

SET FOREIGN_KEY_CHECKS = 1;
