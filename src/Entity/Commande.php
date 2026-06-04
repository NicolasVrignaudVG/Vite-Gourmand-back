<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commande')]
class Commande
{
    public const STATUTS = [
        'en_attente',
        'accepte',
        'preparation',
        'livraison',
        'livre',
        'retour_materiel',
        'terminee',
        'annulee',
    ];

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups(['commande:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    #[Groups(['commande:read'])]
    private ?string $numeroCommande = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['commande:read'])]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['commande:read'])]
    private ?Menu $menu = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull]
    #[Groups(['commande:read'])]
    private ?\DateTimeInterface $datePrestation = null;

    #[ORM\Column(length: 255)]
    #[Groups(['commande:read'])]
    private ?string $adresseLivraison = null;

    #[ORM\Column(length: 100)]
    #[Groups(['commande:read'])]
    private ?string $villeLivraison = null;

    #[ORM\Column(length: 10)]
    #[Groups(['commande:read'])]
    private ?string $cpLivraison = null;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['commande:read'])]
    private int $nombrePersonnes = 1;

    #[ORM\Column]
    #[Groups(['commande:read'])]
    private float $prixMenu = 0.0;

    #[ORM\Column]
    #[Groups(['commande:read'])]
    private float $prixLivraison = 0.0;

    #[ORM\Column]
    #[Groups(['commande:read'])]
    private float $prixTotal = 0.0;

    #[ORM\Column]
    #[Groups(['commande:read'])]
    private float $remise = 0.0;

    #[ORM\Column(length: 20)]
    #[Groups(['commande:read'])]
    private string $statut = 'en_attente';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['commande:read'])]
    private ?string $motifAnnulation = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['commande:read'])]
    private ?string $modeContact = null;

    #[ORM\Column]
    #[Groups(['commande:read'])]
    private bool $pretMateriel = false;

    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: SuiviCommande::class, cascade: ['persist'])]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    #[Groups(['commande:read'])]
    private Collection $suivis;

    #[ORM\OneToMany(targetEntity: CommandePlat::class, mappedBy: 'commande', cascade: ['persist', 'remove'])]
    private Collection $commandePlats;

    #[ORM\OneToMany(targetEntity: CommandeMenu::class, mappedBy: 'commande', cascade: ['persist', 'remove'])]
    private Collection $commandeMenus;

    #[ORM\Column]
    #[Groups(['commande:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['commande:read'])]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->suivis         = new ArrayCollection();
        $this->commandePlats  = new ArrayCollection();
        $this->commandeMenus  = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
        $this->numeroCommande = 'VG-' . strtoupper(substr(uniqid(), -6));
    }

    public function canBeModified(): bool
    {
        return in_array($this->statut, ['en_attente']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->statut, ['en_attente']);
    }

    // Getters & Setters
    public function getId(): ?int { return $this->id; }
    public function getNumeroCommande(): ?string { return $this->numeroCommande; }
    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(?Utilisateur $u): static { $this->utilisateur = $u; return $this; }
    public function getMenu(): ?Menu { return $this->menu; }
    public function setMenu(?Menu $m): static { $this->menu = $m; return $this; }
    public function getDatePrestation(): ?\DateTimeInterface { return $this->datePrestation; }
    public function setDatePrestation(\DateTimeInterface $d): static { $this->datePrestation = $d; return $this; }
    public function getAdresseLivraison(): ?string { return $this->adresseLivraison; }
    public function setAdresseLivraison(string $a): static { $this->adresseLivraison = $a; return $this; }
    public function getVilleLivraison(): ?string { return $this->villeLivraison; }
    public function setVilleLivraison(string $v): static { $this->villeLivraison = $v; return $this; }
    public function getCpLivraison(): ?string { return $this->cpLivraison; }
    public function setCpLivraison(string $cp): static { $this->cpLivraison = $cp; return $this; }
    public function getNombrePersonnes(): int { return $this->nombrePersonnes; }
    public function setNombrePersonnes(int $n): static { $this->nombrePersonnes = $n; return $this; }
    public function getPrixMenu(): float { return $this->prixMenu; }
    public function setPrixMenu(float $p): static { $this->prixMenu = $p; return $this; }
    public function getPrixLivraison(): float { return $this->prixLivraison; }
    public function setPrixLivraison(float $p): static { $this->prixLivraison = $p; return $this; }
    public function getPrixTotal(): float { return $this->prixTotal; }
    public function setPrixTotal(float $p): static { $this->prixTotal = $p; return $this; }
    public function getRemise(): float { return $this->remise; }
    public function setRemise(float $r): static { $this->remise = $r; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $s): static { $this->statut = $s; $this->updatedAt = new \DateTime(); return $this; }
    public function getMotifAnnulation(): ?string { return $this->motifAnnulation; }
    public function setMotifAnnulation(?string $m): static { $this->motifAnnulation = $m; return $this; }
    public function getModeContact(): ?string { return $this->modeContact; }
    public function setModeContact(?string $m): static { $this->modeContact = $m; return $this; }
    public function isPretMateriel(): bool { return $this->pretMateriel; }
    public function setPretMateriel(bool $p): static { $this->pretMateriel = $p; return $this; }
    public function getSuivis(): Collection { return $this->suivis; }
    public function addSuivi(SuiviCommande $s): static { $this->suivis->add($s); return $this; }
    public function getCommandePlats(): Collection { return $this->commandePlats; }
    public function addCommandePlat(CommandePlat $cp): static { if (!$this->commandePlats->contains($cp)) { $this->commandePlats->add($cp); $cp->setCommande($this); } return $this; }
    public function getCommandeMenus(): Collection { return $this->commandeMenus; }
    public function addCommandeMenu(CommandeMenu $cm): static { if (!$this->commandeMenus->contains($cm)) { $this->commandeMenus->add($cm); $cm->setCommande($this); } return $this; }
    public function removeCommandeMenu(CommandeMenu $cm): static { $this->commandeMenus->removeElement($cm); return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
}
