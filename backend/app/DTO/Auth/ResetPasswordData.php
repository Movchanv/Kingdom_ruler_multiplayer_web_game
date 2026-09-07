<?php

declare(strict_types=1);

namespace App\DTO\Auth;

final readonly class ResetPasswordData
{
    public function __construct(
        public string $email,
        public string $token,
        public string $password,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            email: (string) $attributes['email'],
            token: (string) $attributes['token'],
            password: (string) $attributes['password'],
        );
    }
}
