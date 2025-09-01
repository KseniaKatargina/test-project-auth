<?php

namespace App\Controller\Api;

use App\Service\ReportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api/reports')]
class ReportController extends AbstractController
{
    private ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    #[Route('/active-users', name: 'api_report_active_users', methods: ['GET'])]
    public function activeUsersReport(Request $request): JsonResponse
    {
        $currentUser = $request->attributes->get('user');
        if (!in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw new AccessDeniedException('Только ADMIN может получать отчеты');
        }

        $rows = $this->reportService->getActiveUsersByRole();

        return $this->json([
            'status' => 'success',
            'data' => $rows,
        ]);
    }

    #[Route('/blocked-users', name: 'api_report_blocked_users', methods: ['GET'])]
    public function blockedUsersReport(Request $request): JsonResponse
    {
        $currentUser = $request->attributes->get('user');
        if (!in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw new AccessDeniedException('Только ADMIN может получать отчеты');
        }

        $rows = $this->reportService->getBlockedUsers();

        return $this->json([
            'status' => 'success',
            'data' => $rows,
        ]);
    }

    #[Route('/active-sessions', name: 'api_report_active_sessions', methods: ['GET'])]
    public function activeSessionsReport(Request $request): JsonResponse
    {
        $currentUser = $request->attributes->get('user');
        if (!in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw new AccessDeniedException('Только ADMIN может получать отчеты');
        }

        $rows = $this->reportService->getActiveSessions();

        return $this->json([
            'status' => 'success',
            'data' => $rows,
        ]);
    }
}
