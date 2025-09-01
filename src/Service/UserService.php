<?php

namespace App\Service;

use App\Dto\User\RegisterUserRequest;
use App\Entity\User;
use App\Exception\ApiException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function register(RegisterUserRequest $dto): User
    {
        $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $dto->email]);
        if ($existingUser) {
            throw new ApiException('Пользователь с таким email уже существует', 409);
        }
        $this->em->wrapInTransaction(function () use ($dto) {
            $user = new User();
            $user->setEmail($dto->email);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $dto->password)
            );
            $user->setRoles([$dto->role]);

            $this->em->persist($user);
            $this->em->flush();
        });

        return $this->em->getRepository(User::class)->findOneBy(['email' => $dto->email]);
    }

    public function getAllUsers(): array
    {
        $users = $this->em->getRepository(User::class)->findAll();

        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'isActive' => $user->isActive(),
                'createdAt' => $user->getCreatedAt()->format('c'),
                'updatedAt' => $user->getUpdatedAt()->format('c'),
            ];
        }

        return $result;
    }
}
