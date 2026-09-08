<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\EventDifficulty;
use App\Enums\EventType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public const EFFECT_KEYS = [
        'gold',
        'food',
        'wood',
        'stone',
        'soldiers',
        'iron',
        'coal',
        'loyalty',
        'free_action',
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
            'type' => ['required', Rule::enum(EventType::class)],
            'difficulty' => ['required', Rule::enum(EventDifficulty::class)],
            'effects' => ['required', 'array', 'min:1'],
            'effects.*' => ['required', 'integer', 'between:-500,500', 'not_in:0'],
            'weight' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<string, mixed> $effects */
            $effects = $this->input('effects', []);

            if (! is_array($effects)) {
                return;
            }

            foreach (array_keys($effects) as $key) {
                if (! in_array($key, self::EFFECT_KEYS, true)) {
                    $validator->errors()->add(
                        "effects.{$key}",
                        __('Unknown effect target: :key.', ['key' => (string) $key]),
                    );
                }
            }
        });
    }
}
