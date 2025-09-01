<?php

namespace App\DTO\User;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 50)]
    public string $password;

    #[Assert\Choice(choices: ['ROLE_ADMIN', 'ROLE_USER'], message: 'Invalid role')]
    public string $role = 'ROLE_USER';
}