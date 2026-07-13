<?php

namespace App\Tests\Entity;

use App\Entity\Menu;
use PHPUnit\Framework\TestCase;

class MenuTest extends TestCase
{
    /**
     * Crée un menu de test : 20€/personne, minimum 4 personnes.
     * Le seuil de remise est donc 4 + 5 = 9 personnes.
     */
    private function creerMenu(): Menu
    {
        $menu = new Menu();
        $menu->setPrixParPersonne(20.0);
        $menu->setNombrePersonneMinimum(4);

        return $menu;
    }

    public function testPasDeRemiseEnDessousDuSeuil(): void
    {
        $menu = $this->creerMenu();

        // 8 personnes : en dessous du seuil de 9 → pas de remise
        $resultat = $menu->calculerPrix(8);

        $this->assertSame(0, $resultat['remise_pct']);
        $this->assertSame(20.0, $resultat['prix_unitaire']);
        $this->assertSame(160.0, $resultat['prix_total']); // 20 × 8
    }

    public function testRemiseAppliqueeExactementAuSeuil(): void
    {
        $menu = $this->creerMenu();

        // 9 personnes : exactement au seuil → remise de 10%
        $resultat = $menu->calculerPrix(9);

        $this->assertSame(10.0, $resultat['remise_pct']);
        $this->assertSame(18.0, $resultat['prix_unitaire']);  // 20 - 10%
        $this->assertSame(162.0, $resultat['prix_total']);    // 18 × 9
    }

    public function testRemiseAppliqueeAuDessusDuSeuil(): void
    {
        $menu = $this->creerMenu();

        // 12 personnes : au-dessus du seuil → remise de 10%
        $resultat = $menu->calculerPrix(12);

        $this->assertSame(10.0, $resultat['remise_pct']);
        $this->assertSame(18.0, $resultat['prix_unitaire']);
        $this->assertSame(216.0, $resultat['prix_total']);    // 18 × 12
    }

    public function testSeuilDeRemiseEstMinimumPlusCinq(): void
    {
        $menu = $this->creerMenu(); // minimum = 4

        $resultat = $menu->calculerPrix(1);

        $this->assertSame(9, $resultat['seuil_remise']); // 4 + 5
    }
}