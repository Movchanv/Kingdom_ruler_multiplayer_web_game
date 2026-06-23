<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountService;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AccountController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AccountService $account) {}

    public function export(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success($this->account->export($user));
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->account->anonymize($user);

        return $this->success(null, __('Your account and personal data have been deleted.'));
    }

    public function consent(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success([
            'accepted_version' => $user->privacy_policy_version,
            'accepted_at' => $user->terms_accepted_at?->toIso8601String(),
            'current_version' => AuthService::PRIVACY_POLICY_VERSION,
            'up_to_date' => $user->privacy_policy_version === AuthService::PRIVACY_POLICY_VERSION,
        ]);
    }

    public function acceptConsent(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->forceFill([
            'terms_accepted_at' => now(),
            'privacy_policy_version' => AuthService::PRIVACY_POLICY_VERSION,
        ])->save();

        return $this->success(null, __('Consent updated.'));
    }
}
