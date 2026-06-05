<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_token')]
class RefreshToken
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 128, unique: true)]
    private string $token;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $utilisateur;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $expiresAt;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(nullable: true)]
    private ?bool $revoked = false;

    public function __construct(Utilisateur $utilisateur, string $ip = null)
    {
        $this->token       = bin2hex(random_bytes(64));
        $this->utilisateur = $utilisateur;
        $this->expiresAt   = new \DateTime('+30 days');
        $this->createdAt   = new \DateTimeImmutable();
        $this->ipAddress   = $ip;
        $this->revoked     = false;
    }

    public function getId(): ?int { return $this->id; }
    public function getToken(): string { return $this->token; }
    public function getUtilisateur(): Utilisateur { return $this->utilisateur; }
    public function getExpiresAt(): \DateTimeInterface { return $this->expiresAt; }
    public function isExpired(): bool { return $this->expiresAt < new \DateTime(); }
    public function isRevoked(): bool { return $this->revoked === true; }
    public function revoke(): void { $this->revoked = true; }
    public function isValid(): bool { return !$this->isExpired() && !$this->isRevoked(); }
}
