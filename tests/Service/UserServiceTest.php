<?php

namespace App\Tests\Service;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserServiceTest extends KernelTestCase
{
    private UserService $userService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->userService = $container->get(UserService::class);
    }

    public function testGetAllUsers(): void
    {
        $users = $this->userService->getAllUsers();
        $this->assertIsArray($users);
        $this->assertNotEmpty($users);
    }
}
