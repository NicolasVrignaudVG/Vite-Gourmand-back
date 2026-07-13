<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MenuControllerTest extends WebTestCase
{
    public function testListeDesMenusEstPublique(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/menus');

        // La route est publique : elle doit répondre 200 sans authentification
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }

    public function testListeDesMenusRetourneUnTableauJson(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/menus');

        $donnees = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($donnees);
    }

    public function testMenuInexistantRetourne404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/menus/999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreerUnMenuSansAuthentificationEstRefuse(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/menus', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['titre' => 'Menu test'])
        );

        // Route protégée par ROLE_EMPLOYE : doit refuser un anonyme
        $this->assertResponseStatusCodeSame(401);
    }

    public function testListeRetourneTousLesMenusActifs(): void
    {
    $client = static::createClient();

    $client->request('GET', '/api/menus');

    $menus = json_decode($client->getResponse()->getContent(), true);

    // Les fixtures créent 3 menus actifs
    $this->assertCount(3, $menus);
    }

    public function testFiltreParPrixMax(): void
    {
    $client = static::createClient();

    // prix_max=40 : seuls Découverte (32€) et Végétarien (28€) passent
    $client->request('GET', '/api/menus?prix_max=40');

    $menus = json_decode($client->getResponse()->getContent(), true);

    $this->assertCount(2, $menus);
    }

    public function testFiltreParRegime(): void
    {
    $client = static::createClient();

    $client->request('GET', '/api/menus?regime=Végétarien');

    $menus = json_decode($client->getResponse()->getContent(), true);

    $this->assertCount(1, $menus);
    $this->assertSame('Menu Végétarien', $menus[0]['titre']);
    }

public function testFiltreParNombreDePersonnes(): void
    {
    $client = static::createClient();

    // personnes_min=3 : seul Découverte (minimum 2) accepte un groupe de 3
    $client->request('GET', '/api/menus?personnes_min=3');

    $menus = json_decode($client->getResponse()->getContent(), true);

    $this->assertCount(1, $menus);
    $this->assertSame('Menu Découverte', $menus[0]['titre']);
    }
}