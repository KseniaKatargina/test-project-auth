<?php

namespace App\Controller\Api;

use App\DTO\User\LoginRequest;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        ValidatorInterface $validator,
        AuthService $authService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $dto = new LoginRequest();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json([
                'status' => 'error',
                'message' => (string) $errors,
            ], 400);
        }

        $result = $authService->login($dto);

        return $this->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
