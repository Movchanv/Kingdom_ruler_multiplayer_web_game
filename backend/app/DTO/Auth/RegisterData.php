<?php

declare(strict_types=1);

namespace App\DTO\Auth;

final readonly class RegisterData
{
    public function __construct(
        public string $username,
        public string $email,
        public string $password,
        public string $dateOfBirth,
        public ?string $country = null,
        public ?string $gender = null,
        public string $deviceName = 'web',
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        return new self(
            username: (string) $attributes['username'],
            email: (string) $attributes['email'],
            password: (string) $attributes['password'],
            dateOfBirth: (string) $attributes['date_of_birth'],
            country: isset($attributes['country']) ? (string) $attributes['country'] : null,
            gender: isset($attributes['gender']) ? (string) $attributes['gender'] : null,
            deviceName: (string) ($attributes['device_name'] ?? 'web'),
        );
    }
}
