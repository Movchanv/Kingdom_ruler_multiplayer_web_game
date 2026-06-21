<?php

declare(strict_types=1);

namespace App\DTO\Auth;

final readonly class LoginData
{
    public function __construct(
        public string $email,
        public string $password,
        public string $deviceName = 'web',
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            email: (string) $attributes['email'],
            password: (string) $attributes['password'],
            deviceName: (string) ($attributes['device_name'] ?? 'web'),
        );
    }
}
