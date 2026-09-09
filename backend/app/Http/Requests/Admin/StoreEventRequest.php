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
            'icon' => ['nullable', 'string', 'max:8'],

            'effects' => ['nullable', 'array'],
            'effects.*' => ['required', 'integer', 'between:-500,500', 'not_in:0'],

            'requirement' => ['nullable', 'array'],
            'requirement.*' => ['required', 'integer', 'min:1', 'max:100000'],
            'success_effects' => ['nullable', 'array'],
            'success_effects.*' => ['required', 'integer', 'between:-500,500', 'not_in:0'],
            'failure_effects' => ['nullable', 'array'],
            'failure_effects.*' => ['required', 'integer', 'between:-500,500', 'not_in:0'],

            'delay_min_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'delay_max_minutes' => ['nullable', 'integer', 'min:0', 'max:10080', 'gte:delay_min_minutes'],

            'weight' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach (['effects', 'requirement', 'success_effects', 'failure_effects'] as $field) {
                $values = $this->input($field, []);

                if (! is_array($values)) {
                    continue;
                }

                foreach (array_keys($values) as $key) {
                    if (! in_array($key, self::EFFECT_KEYS, true)) {
                        $validator->errors()->add(
                            "{$field}.{$key}",
                            __('Unknown effect target: :key.', ['key' => (string) $key]),
                        );
                    }
                }
            }

            $hasOutcome = filled($this->input('effects'))
                || filled($this->input('success_effects'))
                || filled($this->input('failure_effects'));

            if (! $hasOutcome) {
                $validator->errors()->add('effects', __('An event needs at least one effect.'));
            }
        });
    }
}
