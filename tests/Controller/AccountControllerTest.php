<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountControllerTest extends WebTestCase
{
    private string $accessToken;

    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'admin@example.com',
                'password' => 'adminpass'
            ])
        );

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->accessToken = $data['data']['access_token'];
    }

    public function testGetProfile(): void
    {
        $this->client->request(
            'GET',
            '/api/me',
            [],
            [],
            [
                'HTTP_Authorization' => 'Bearer '.$this->accessToken
            ]
        );

        $this->assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('admin@example.com', $data['email']);
        $this->assertContains('ROLE_ADMIN', $data['roles']);
    }
}
