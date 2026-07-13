<?php

namespace App\DataFixtures;

use App\Entity\Allergene;
use App\Entity\Menu;
use App\Entity\Plat;
use App\Entity\Regime;
use App\Entity\Role;
use App\Entity\Theme;
use App\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // ─────────────────────────────────────────
        // RÔLES
        // ─────────────────────────────────────────
        $roleUser = new Role();
        $roleUser->setLibelle('utilisateur');
        $manager->persist($roleUser);

        $roleEmploye = new Role();
        $roleEmploye->setLibelle('employe');
        $manager->persist($roleEmploye);

        $roleAdmin = new Role();
        $roleAdmin->setLibelle('administrateur');
        $manager->persist($roleAdmin);

        // ─────────────────────────────────────────
        // UTILISATEURS
        // ─────────────────────────────────────────
        $admin = new Utilisateur();
        $admin->setEmail('admin@vitegourmand.fr');
        $admin->setNom('Martin');
        $admin->setPrenom('Julie');
        $admin->setRole($roleAdmin);
        $admin->setPassword($this->hasher->hashPassword($admin, 'Admin@1234'));
        $manager->persist($admin);

        $client = new Utilisateur();
        $client->setEmail('marie.dupont@email.com');
        $client->setNom('Dupont');
        $client->setPrenom('Marie');
        $client->setRole($roleUser);
        $client->setPassword($this->hasher->hashPassword($client, 'User@1234'));
        $manager->persist($client);

        // ─────────────────────────────────────────
        // THÈMES & RÉGIMES
        // ─────────────────────────────────────────
        $themeNoel = new Theme();
        $themeNoel->setLibelle('Noël');
        $manager->persist($themeNoel);

        $themePaques = new Theme();
        $themePaques->setLibelle('Pâques');
        $manager->persist($themePaques);

        $regimeClassique = new Regime();
        $regimeClassique->setLibelle('Classique');
        $manager->persist($regimeClassique);

        $regimeVegetarien = new Regime();
        $regimeVegetarien->setLibelle('Végétarien');
        $manager->persist($regimeVegetarien);

        // ─────────────────────────────────────────
        // ALLERGÈNES
        // ─────────────────────────────────────────
        $gluten = new Allergene();
        $gluten->setLibelle('Gluten');
        $manager->persist($gluten);

        $lait = new Allergene();
        $lait->setLibelle('Lait');
        $manager->persist($lait);

        // ─────────────────────────────────────────
        // PLATS
        // ─────────────────────────────────────────
        $entree = new Plat();
        $entree->setTypePlat('entree');
        $entree->setNom('Velouté de potimarron');
        $entree->setDescription('Velouté onctueux aux épices douces.');
        $entree->addAllergene($lait);
        $manager->persist($entree);

        $platPrincipal = new Plat();
        $platPrincipal->setTypePlat('plat');
        $platPrincipal->setNom('Saumon en croûte');
        $platPrincipal->setDescription('Saumon rôti en croûte d\'herbes.');
        $platPrincipal->addAllergene($gluten);
        $manager->persist($platPrincipal);

        $dessert = new Plat();
        $dessert->setTypePlat('dessert');
        $dessert->setNom('Bûche vanille');
        $dessert->setDescription('Bûche traditionnelle à la vanille.');
        $manager->persist($dessert);

        // ─────────────────────────────────────────
        // MENUS (conçus pour tester les filtres)
        // ─────────────────────────────────────────
        $menuDecouverte = new Menu();
        $menuDecouverte->setTitre('Menu Découverte');
        $menuDecouverte->setDescription('Les grands classiques revisités.');
        $menuDecouverte->setPrixParPersonne(32.0);
        $menuDecouverte->setNombrePersonneMinimum(2);
        $menuDecouverte->setQuantiteRestante(50);
        $menuDecouverte->setTheme($themeNoel);
        $menuDecouverte->setRegime($regimeClassique);
        $menuDecouverte->addPlat($entree);
        $menuDecouverte->addPlat($platPrincipal);
        $menuDecouverte->addPlat($dessert);
        $manager->persist($menuDecouverte);

        $menuGastronomique = new Menu();
        $menuGastronomique->setTitre('Menu Gastronomique');
        $menuGastronomique->setDescription('Un menu raffiné signé par le chef.');
        $menuGastronomique->setPrixParPersonne(55.0);
        $menuGastronomique->setNombrePersonneMinimum(4);
        $menuGastronomique->setQuantiteRestante(20);
        $menuGastronomique->setTheme($themeNoel);
        $menuGastronomique->setRegime($regimeClassique);
        $menuGastronomique->addPlat($platPrincipal);
        $manager->persist($menuGastronomique);

        $menuVegetarien = new Menu();
        $menuVegetarien->setTitre('Menu Végétarien');
        $menuVegetarien->setDescription('Une cuisine végétale et gourmande.');
        $menuVegetarien->setPrixParPersonne(28.0);
        $menuVegetarien->setNombrePersonneMinimum(6);
        $menuVegetarien->setQuantiteRestante(30);
        $menuVegetarien->setTheme($themePaques);
        $menuVegetarien->setRegime($regimeVegetarien);
        $menuVegetarien->addPlat($entree);
        $manager->persist($menuVegetarien);

        // Écrit tout en base d'un coup
        $manager->flush();
    }
}