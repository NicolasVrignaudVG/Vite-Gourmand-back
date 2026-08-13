<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }
    /**
     * Récupère les commandes d'un utilisateur avec toutes leurs relations
     * chargées en une seule requête, pour éviter le problème N+1 lors de
     * la sérialisation (menu, suivis, menus commandés et plats choisis).
     *
     * @return Commande[]
     */
    public function findByUtilisateurAvecDetails(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('m', 's', 'cm', 'cmm', 'cp', 'p')
            ->leftJoin('c.menu', 'm')
            ->leftJoin('c.suivis', 's')
            ->leftJoin('c.commandeMenus', 'cm')
            ->leftJoin('cm.menu', 'cmm')
            ->leftJoin('c.commandePlats', 'cp')
            ->leftJoin('cp.plat', 'p')
            ->where('c.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
