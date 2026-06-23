<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Player;
use App\Models\User;
use Illuminate\Support\Str;

final class AccountService
{
    /**
     * Respect ofRGPD art. 15 & 20
     *
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        $user->loadMissing('players.game', 'players.title');

        return [
            'account' => [
                'username' => $user->username,
                'email' => $user->email,
                'country' => $user->country,
                'date_of_birth' => $user->date_of_birth?->toDateString(),
                'gender' => $user->gender?->value,
                'role' => $user->role->value,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'terms_accepted_at' => $user->terms_accepted_at?->toIso8601String(),
                'privacy_policy_version' => $user->privacy_policy_version,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'players' => $user->players->map(fn (Player $player): array => [
                'game' => $player->game?->name,
                'display_name' => $player->display_name,
                'xp' => $player->xp,
                'title' => $player->title?->name,
                'created_at' => $player->created_at?->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * Respect of RGPD art. 17
     */
    public function anonymize(User $user): void
    {
        $user->players()->update(['display_name' => 'Ancien joueur']);

        $user->tokens()->delete();

        $user->forceFill([
            'username' => null,
            'email' => 'deleted_'.$user->id.'@anonymized.invalid',
            'password' => Str::random(40),
            'country' => null,
            'date_of_birth' => null,
            'gender' => null,
        ])->save();

        $user->delete();
    }
}
