<?php

namespace App\Service;

use App\DTO\User\LoginRequest;
use App\Entity\Session;
use App\Entity\Token;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use App\Exception\ApiException;

class AuthService
{
    public function __construct(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $userLogger
    ) {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
        $this->logger = $userLogger;
    }


    public function login(LoginRequest $dto): array
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $dto->email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $dto->password)) {
            $this->logger->warning('Неудачная попытка логина', ['email' => $dto->email]);
            throw new ApiException('Неверный логин или пароль', 401);
        }

        if (!$user->isActive()) {
            $this->logger->warning('Попытка логина заблокированного пользователя', ['email' => $dto->email]);
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

            $this->logger->info('Пользователь вошел в систему', [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'token' => $tokenValue
            ]);

            return [
                'token' => $tokenValue,
                'expires_at' => $expiresAt->format('c'),
            ];
        } catch (\Throwable $e) {
            $this->em->rollback();
            $this->logger->error('Ошибка при создании сессии/токена', [
                'email' => $dto->email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

}
