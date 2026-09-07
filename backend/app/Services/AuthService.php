<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Auth\LoginData;
use App\DTO\Auth\RegisterData;
use App\DTO\Auth\ResetPasswordData;
use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthService
{
    public const PRIVACY_POLICY_VERSION = '1.0';

    public function __construct(private readonly UserRepositoryInterface $users) {}

    /**
     * Create an account, trigger the email-verification mail and issue a token.
     *
     * @return array{user: User, token: string}
     */
    public function register(RegisterData $data): array
    {
        /** @var User $user */
        $user = $this->users->create([
            'username' => $data->username,
            'email' => $data->email,
            'password' => $data->password,
            'country' => $data->country,
            'date_of_birth' => $data->dateOfBirth,
            'gender' => $data->gender,
            'role' => UserRole::Player,
            'terms_accepted_at' => now(),
            'privacy_policy_version' => self::PRIVACY_POLICY_VERSION,
        ]);

        event(new Registered($user));

        $token = $user->createToken($data->deviceName)->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

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

        if ($user->isBanned()) {
            throw ValidationException::withMessages([
                'email' => [__('This account has been suspended.')],
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

    /**
     * Send a password-reset link. Always silent on unknown emails to avoid
     * account enumeration (the controller returns a generic message).
     */
    public function sendPasswordResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    /**
     * @throws ValidationException
     */
    public function resetPassword(ResetPasswordData $data): void
    {
        $status = Password::reset(
            [
                'email' => $data->email,
                'password' => $data->password,
                'token' => $data->token,
            ],
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }
    }
}
