<?php

declare(strict_types=1);

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class OpenVoteRequest extends FormRequest
{
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
            'game_id' => ['required', 'integer', 'exists:games,id'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'law_ids' => ['required', 'array', 'size:3'],
            'law_ids.*' => ['integer', 'distinct', 'exists:laws,id'],
            'hours' => ['sometimes', 'integer', 'min:1', 'max:168'],
        ];
    }
}
