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
}