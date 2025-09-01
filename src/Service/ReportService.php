<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ReportService
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function getActiveUsersByRole(): array
    {
        $sql = 'SELECT * FROM active_users_by_role';
        $result = $this->em->getConnection()->fetchAllAssociative($sql);

        $this->logger->info('Сформирован отчет: активные пользователи по ролям', [
            'count' => count($result)
        ]);

        return $result;
    }

    public function getBlockedUsers(): array
    {
        $sql = 'SELECT * FROM blocked_users';
        $result = $this->em->getConnection()->fetchAllAssociative($sql);

        $this->logger->info('Сформирован отчет: заблокированные пользователи', [
            'count' => count($result)
        ]);

        return $result;
    }

    public function getActiveSessions(): array
    {
        $sql = 'SELECT * FROM active_sessions';
        $result = $this->em->getConnection()->fetchAllAssociative($sql);

        $this->logger->info('Сформирован отчет: активные сессии', [
            'count' => count($result)
        ]);

        return $result;
    }
}
