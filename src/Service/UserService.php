<?php

namespace App\Service;

use App\Dto\User\RegisterUserRequest;
use App\Entity\User;
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
}
