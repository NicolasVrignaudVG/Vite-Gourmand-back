<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'commande_plat')]
class CommandePlat
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'commandePlats')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Commande $commande = null;

    #[ORM\ManyToOne(targetEntity: Plat::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Plat $plat = null;

    public function getId(): ?int { return $this->id; }
    public function getCommande(): ?Commande { return $this->commande; }
    public function setCommande(?Commande $c): static { $this->commande = $c; return $this; }
    public function getPlat(): ?Plat { return $this->plat; }
    public function setPlat(?Plat $p): static { $this->plat = $p; return $this; }
}
