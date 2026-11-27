<?php

namespace App\Http\Requests\Wishlist;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmPurchaseWishlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchased' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'purchased.required' => 'Status keputusan pembelian wajib diisi.',
            'purchased.boolean' => 'Status keputusan pembelian harus berupa boolean (true/false).',
        ];
    }
}

