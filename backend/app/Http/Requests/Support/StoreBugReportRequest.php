<?php

declare(strict_types=1);

namespace App\Http\Requests\Support;

use App\Enums\BugSeverity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBugReportRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'severity' => ['required', Rule::enum(BugSeverity::class)],
            'scope' => ['required', Rule::in(['backend', 'frontend', 'realtime', 'other'])],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'page' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.min' => __('Merci de détailler les étapes, le résultat attendu et le résultat observé (20 caractères minimum).'),
        ];
    }
}
