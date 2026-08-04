<?php

namespace Modules\Kopia\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginKopiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if ($email && !str_contains($email, '@')) {
            $this->merge([
                'email' => $email . '@iniap.gob.ec',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
