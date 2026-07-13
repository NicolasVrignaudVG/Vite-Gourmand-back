<?php

namespace App\DataFixtures;

use App\Entity\Contact;
use App\Entity\Utilisateur;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

/**
 * Fixtures de volume générées avec Faker.
 *
 * Objectif : peupler une base de DÉVELOPPEMENT avec des données réalistes.
 * Ces données sont aléatoires à chaque exécution : elles ne doivent PAS être
 * utilisées comme base d'assertions dans les tests automatisés.
 *
 * Chargement ciblé :
 *   php bin/console doctrine:fixtures:load --group=faker --append
 */
class FakerFixtures extends Fixture implements FixtureGroupInterface
{
    private Generator $faker;

    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {
        $this->faker = Factory::create('fr_FR');
    }

    public static function getGroups(): array
    {
        return ['faker'];
    }

    public function load(ObjectManager $manager): void
    {
        // On réutilise le rôle "utilisateur" existant plutôt que d'en créer un doublon
        $roleUser = $manager->getRepository(Role::class)
            ->findOneBy(['libelle' => 'utilisateur']);

        if (!$roleUser) {
            $roleUser = new Role();
            $roleUser->setLibelle('utilisateur');
            $manager->persist($roleUser);
        }

        // ─────────────────────────────────────────
        // 20 clients réalistes
        // ─────────────────────────────────────────
        for ($i = 0; $i < 20; $i++) {
            $user = new Utilisateur();
            $user->setEmail($this->faker->unique()->safeEmail());
            $user->setNom($this->faker->lastName());
            $user->setPrenom($this->faker->firstName());
            $user->setTelephone($this->faker->phoneNumber());
            $user->setAdresse(
                $this->faker->streetAddress() . ', ' .
                $this->faker->postcode() . ' ' .
                $this->faker->city()
            );
            $user->setRole($roleUser);
            $user->setActif($this->faker->boolean(90)); // 90% de comptes actifs
            $user->setPassword($this->hasher->hashPassword($user, 'User@1234'));

            $manager->persist($user);
        }

        // ─────────────────────────────────────────
        // 15 messages de contact
        // ─────────────────────────────────────────
        for ($i = 0; $i < 15; $i++) {
            $contact = new Contact();
            $contact->setEmail($this->faker->safeEmail());
            $contact->setTitre($this->faker->sentence(4));
            $contact->setDescription($this->faker->paragraph(3));
            $contact->setTraite($this->faker->boolean(40)); // 40% déjà traités

            $manager->persist($contact);
        }

        $manager->flush();
    }
}