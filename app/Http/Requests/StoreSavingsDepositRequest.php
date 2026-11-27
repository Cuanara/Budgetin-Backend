<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSavingsDepositRequest extends FormRequest
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
            'wishlist_id' => ['nullable', 'uuid', 'exists:wishlists,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'deposit_date'=> ['required', 'date'],
            'notes' =>['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'wishlist_id.uuid' => 'Format wishlist_id tidak valid.',
            'wishlist_id.exists' => 'Wishlist tidak ditemukan.',
            'amount.required' => 'Nominal setoran wajib diisi.',
            'amount.numeric' => 'Nominal setoran harus berupa angka.',
            'amount.min' => 'Nominal setoran minimal 1.',
            'deposit_date.required' => 'Tanggal setoran wajib diisi.',
            'deposit_date.date' => 'Format tanggal setoran tidak valid.',
            'notes.max' => 'Catatan maksimal 255 karakter.',
        ];
    }
}
