<?php

namespace App\Service;

use App\DTO\User\LoginRequest;
use App\Entity\Session;
use App\Entity\Token;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use App\Exception\ApiException;

class AuthService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function login(LoginRequest $dto): array
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $dto->email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            throw new ApiException('Неверный логин или пароль', 401);
        }

        if (!$user->isActive()) {
            throw new ApiException('Пользователь заблокирован', 403);
        }

        $this->em->beginTransaction();
        try {
            $tokenValue = bin2hex(random_bytes(32));
            $expiresAt = new \DateTimeImmutable('+1 hour');

            $token = (new Token())
                ->setAppUser($user)
                ->setValue($tokenValue)
                ->setType('access')
                ->setCreatedAt(new \DateTimeImmutable())
                ->setExpiresAt($expiresAt);

            $this->em->persist($token);

            $session = (new Session())
                ->setAppUser($user)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setToken($tokenValue)
                ->setExpiresAt($expiresAt);

            $this->em->persist($session);

            $this->em->flush();
            $this->em->commit();

            return [
                'token' => $tokenValue,
                'expires_at' => $expiresAt->format('c'),
            ];
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}
