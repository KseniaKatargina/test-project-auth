<?php

namespace App\Service;

use App\Entity\Session;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SessionService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function getUserSessions(User $user): array
    {
        $sessions = $this->em->getRepository(Session::class)
            ->findBy(['appUser' => $user]);

        $result = [];
        foreach ($sessions as $session) {
            $result[] = [
                'id' => $session->getId(),
                'token' => $session->getToken(),
                'createdAt' => $session->getCreatedAt()->format('c'),
                'expiresAt' => $session->getExpiresAt()->format('c'),
            ];
        }
        return $result;
    }

    public function terminateSession(User $user, int $id): void
    {
        $session = $this->em->getRepository(Session::class)->find($id);
        if (!$session || $session->getAppUser()->getId() !== $user->getId()) {
            throw new \RuntimeException('Сессия не найдена или нет доступа');
        }

        $this->em->remove($session);
        $this->em->flush();
    }

    public function terminateByAccessToken(User $user, string $tokenValue): void
    {
        $session = $this->em->getRepository(Session::class)
            ->findOneBy(['appUser' => $user, 'token' => $tokenValue]);

        if ($session) {
            $this->em->remove($session);
            $this->em->flush();
        }
    }

}
