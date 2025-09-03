<?php

namespace App\Controller\Api;

use App\Service\UserService;
use App\Service\SessionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/me')]
class AccountController extends AbstractController
{
    public function __construct(
        private UserService $userService,
        private SessionService $sessionService
    ) {}

    #[Route('', methods: ['GET'])]
    public function profile(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'isActive' => $user->isActive(),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'updatedAt' => $user->getUpdatedAt()->format('c'),
        ]);
    }

    #[Route('', methods: ['PATCH'])]
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $updatedUser = $this->userService->updateUser($user, $data);

        return $this->json([
            'id' => $updatedUser->getId(),
            'email' => $updatedUser->getEmail(),
            'roles' => $updatedUser->getRoles(),
            'isActive' => $updatedUser->isActive(),
        ]);
    }

    #[Route('/sessions', methods: ['GET'])]
    public function mySessions(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $sessions = $this->sessionService->getUserSessions($user);

        return $this->json($sessions);
    }

    #[Route('/sessions/{id}', methods: ['DELETE'])]
    public function revokeSession(Request $request, int $id): JsonResponse
    {
        $user = $request->attributes->get('user');
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $this->sessionService->terminateSession($user, $id);

        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->json(['error' => 'Токен не найден'], 400);
        }

        $tokenValue = substr($authHeader, 7);

        $this->sessionService->terminateByAccessToken($user, $tokenValue);

        return $this->json(['status' => 'logged_out']);
    }

}
