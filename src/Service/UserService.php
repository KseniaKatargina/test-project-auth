<?php

namespace App\Service;

use App\Dto\User\RegisterUserRequest;
use App\Entity\User;
use App\Exception\ApiException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserService
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

    public function register(RegisterUserRequest $dto): User
    {
        $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $dto->email]);
        if ($existingUser) {
            $this->logger->warning('Попытка зарегистрировать уже существующий email', ['email' => $dto->email]);
            throw new ApiException('Пользователь с таким email уже существует', 409);
        }

        $this->em->wrapInTransaction(function () use ($dto) {
            $user = new User();
            $user->setEmail($dto->email);
            $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
            $user->setRoles([$dto->role]);

            $this->em->persist($user);
            $this->em->flush();

            $this->logger->info('Новый пользователь зарегистрирован', [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $dto->role
            ]);
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

    public function getUserById(int $id): ?User
    {
        return $this->em->getRepository(User::class)->find($id);
    }

    public function updateUser(User $user, array $data): User
    {
        $changes = [];
        if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
            $changes['email'] = ['old' => $user->getEmail(), 'new' => $data['email']];
            $user->setEmail($data['email']);
        }
        if (isset($data['roles']) && $data['roles'] !== $user->getRoles()) {
            $changes['roles'] = ['old' => $user->getRoles(), 'new' => $data['roles']];
            $user->setRoles($data['roles']);
        }
        if (isset($data['isActive']) && $data['isActive'] !== $user->isActive()) {
            $changes['isActive'] = ['old' => $user->isActive(), 'new' => (bool)$data['isActive']];
            $user->setIsActive((bool)$data['isActive']);
        }

        $this->em->flush();

        if (!empty($changes)) {
            $this->logger->info('Данные пользователя обновлены', [
                'id' => $user->getId(),
                'changes' => $changes
            ]);
        }

        return $user;
    }

    public function blockUser(User $user): User
    {
        $user->setIsActive(false);
        $this->em->flush();

        $this->logger->warning('Пользователь заблокирован', [
            'id' => $user->getId(),
            'email' => $user->getEmail()
        ]);

        return $user;
    }

    public function getUserOrFail(int $id, User $currentUser): User
    {
        $user = $this->getUserById($id);
        if (!$user) {
            throw new \RuntimeException('Пользователь не найден');
        }

        if (!in_array('ROLE_ADMIN', $currentUser->getRoles()) && $currentUser->getId() !== $user->getId()) {
            throw new AccessDeniedException('Нет доступа');
        }

        return $user;
    }

}
