<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'country' => $this->country,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender?->value,
            'gender_label' => $this->gender?->label(),
            'role' => $this->role->value,
            'xp' => $this->xp,
            'title' => $this->title?->name,
            'email_verified' => $this->hasVerifiedEmail(),
            'created_at' => $this->created_at,
        ];
    }
}
