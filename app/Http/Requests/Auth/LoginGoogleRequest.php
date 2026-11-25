<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginGoogleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'credential' => 'required|string',
            'remember' => 'sometimes|boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi',
            'string' => ':attribute harus berupa teks',
            'boolean' => ':attribute harus berupa true atau false',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'credential' => 'Kredensial Google',
            'remember' => 'Ingat saya',
        ];
    }

    public function credential(): string
    {
        return (string) $this->validated('credential');
    }

    /**
     * Apakah user memilih "ingat saya".
     * Default true: refresh token berlaku 30 hari, bukan 1 hari.
     */
    public function remember(): bool
    {
        return $this->boolean('remember', true);
    }
}
