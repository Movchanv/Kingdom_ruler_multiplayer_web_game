<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Auth\LoginData;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    /**
     * @return array{user: User, token: string}
     */
    public function login(LoginData $data): array
    {
        $user = $this->users->findByEmail($data->email);

        if ($user === null || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken($data->deviceName)->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }
}
