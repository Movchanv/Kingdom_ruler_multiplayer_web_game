<?php

declare(strict_types=1);

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class CastBallotRequest extends FormRequest
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
            'option_id' => ['required', 'integer', 'exists:law_vote_options,id'],
        ];
    }
}
