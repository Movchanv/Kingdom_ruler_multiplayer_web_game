<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLawRequest extends FormRequest
{
    /** Effets de loi autorisés (bonus % de production ou rente journalière). */
    public const BONUS_KEYS = [
        'gold_bonus_pct',
        'food_bonus_pct',
        'wood_bonus_pct',
        'stone_bonus_pct',
        'soldiers_bonus_pct',
        'gold_per_day',
        'food_per_day',
        'loyalty_per_day',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'bonus_key' => ['required', Rule::in(self::BONUS_KEYS)],
            'bonus_value' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
