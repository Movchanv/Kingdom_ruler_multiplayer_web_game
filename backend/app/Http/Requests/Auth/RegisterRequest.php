<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DTO\Auth\RegisterData;
use App\Enums\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.now()->subYears(16)->toDateString()],
            'country' => ['nullable', 'string', 'size:2'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'terms_accepted' => ['required', 'accepted'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_of_birth.before_or_equal' => __('You must be at least 16 years old to register.'),
            'terms_accepted.accepted' => __('You must accept the terms of service to register.'),
        ];
    }

    public function toData(): RegisterData
    {
        return RegisterData::fromArray($this->validated());
    }
}
