<?php

namespace App\Http\Requests\Notification;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexNotificationRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'Jumlah data per halaman harus berupa angka',
            'per_page.min' => 'Jumlah data per halaman minimal 1',
            'per_page.max' => 'Jumlah data per halaman maksimal 100',
            'page.integer' => 'Halaman harus berupa angka',
            'page.min' => 'Halaman minimal 1',
        ];
    }
}
