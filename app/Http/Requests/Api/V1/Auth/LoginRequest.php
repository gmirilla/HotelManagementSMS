<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'mfa_code' => ['sometimes', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Rate-limit key: email + IP, matching the web login throttle (NFR-SEC-004).
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->string('email')) . '|' . $this->ip());
    }
}
