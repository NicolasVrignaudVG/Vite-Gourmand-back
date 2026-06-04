<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'commande_menu')]
class CommandeMenu
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'commandeMenus')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Commande $commande = null;

    #[ORM\ManyToOne(targetEntity: Menu::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Menu $menu = null;

    #[ORM\Column]
    private int $nombrePersonnes = 1;

    #[ORM\Column]
    private float $prixTotal = 0.0;

    #[ORM\Column]
    private float $remise = 0.0;

    public function getId(): ?int { return $this->id; }
    public function getCommande(): ?Commande { return $this->commande; }
    public function setCommande(?Commande $c): static { $this->commande = $c; return $this; }
    public function getMenu(): ?Menu { return $this->menu; }
    public function setMenu(?Menu $m): static { $this->menu = $m; return $this; }
    public function getNombrePersonnes(): int { return $this->nombrePersonnes; }
    public function setNombrePersonnes(int $n): static { $this->nombrePersonnes = $n; return $this; }
    public function getPrixTotal(): float { return $this->prixTotal; }
    public function setPrixTotal(float $p): static { $this->prixTotal = $p; return $this; }
    public function getRemise(): float { return $this->remise; }
    public function setRemise(float $r): static { $this->remise = $r; return $this; }
}
