<?php

namespace App\Controller\Api;

use App\Dto\User\RegisterUserRequest;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(private UserService $userService) {}

    #[Route('/register', name: 'api_user_register', methods: ['POST'])]
    public function register(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $dto = new RegisterUserRequest();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';
        $dto->role = $data['role'] ?? 'ROLE_USER';

        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json([
                'status' => 'error',
                'errors' => $messages,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = $this->userService->register($dto);

        return $this->json([
            'status' => 'success',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ]
        ], JsonResponse::HTTP_CREATED);
    }
}
