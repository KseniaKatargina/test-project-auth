<?php

namespace App\Controller\Api;

use App\DTO\User\RegisterUserRequest;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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

    #[Route('', name: 'api_user_list', methods: ['GET'])]
    public function listUsers(Request $request): JsonResponse
    {
        $user = $request->attributes->get('user');

        if (!$user) {
            return $this->json(['status'=>'error','message'=>'Missing or invalid token'], 401);
        }

        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->json([
                'status'=>'error',
                'message'=>'Только ADMIN может просматривать пользователей'
            ], 403);
        }

        $users = $this->userService->getAllUsers();

        return $this->json([
            'status'=>'success',
            'data'=>$users
        ]);
    }

    #[Route('/{id}', name: 'api_user_view', methods: ['GET'])]
    public function viewUser(int $id, Request $request): JsonResponse
    {
        $currentUser = $request->attributes->get('user');
        $user = $this->userService->getUserOrFail($id, $currentUser);

        return $this->json(['status' => 'success', 'data' => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'isActive' => $user->isActive(),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'updatedAt' => $user->getUpdatedAt()->format('c'),
        ]]);
    }

    #[Route('/{id}', name: 'api_user_update', methods: ['PATCH'])]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        $currentUser = $request->attributes->get('user');
        $user = $this->userService->getUserOrFail($id, $currentUser);

        $data = json_decode($request->getContent(), true);
        $user = $this->userService->updateUser($user, $data);

        return $this->json(['status' => 'success', 'data' => [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'isActive' => $user->isActive(),
            'createdAt' => $user->getCreatedAt()->format('c'),
            'updatedAt' => $user->getUpdatedAt()->format('c'),
        ]]);
    }

    #[Route('/{id}/block', name: 'api_user_block', methods: ['POST'])]
    public function blockUser(int $id, Request $request): JsonResponse
    {
        $currentUser = $request->attributes->get('user');
        if (!in_array('ROLE_ADMIN', $currentUser->getRoles())) {
            throw new AccessDeniedException('Только ADMIN может блокировать');
        }

        $user = $this->userService->getUserById($id);
        if (!$user) {
            return $this->json(['status' => 'error', 'message' => 'Пользователь не найден'], 404);
        }

        $user = $this->userService->blockUser($user);

        return $this->json(['status' => 'success', 'message' => 'Пользователь заблокирован']);
    }
}
