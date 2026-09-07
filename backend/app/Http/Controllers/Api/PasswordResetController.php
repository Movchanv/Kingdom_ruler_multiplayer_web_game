<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

final class PasswordResetController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $auth) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        /** @var array{email: string} $validated */
        $validated = $request->validated();

        $this->auth->sendPasswordResetLink($validated['email']);

        return $this->success(null, __('If an account matches this email, a reset link has been sent.'));
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $this->auth->resetPassword($request->toData());

        return $this->success(null, __('Your password has been reset.'));
    }
}
