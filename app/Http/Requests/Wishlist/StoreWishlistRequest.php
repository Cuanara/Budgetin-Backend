<?php

namespace App\Http\Requests\Wishlist;

use Illuminate\Foundation\Http\FormRequest;

class StoreWishlistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_name' => ['required', 'string', 'max:200'],
            'purchase_url' => ['nullable', 'url', 'max:2048'],
            'price' => ['required', 'numeric', 'min:1'],
            'delay_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'savings_goal_id' => ['nullable', 'uuid', 'exists:savings_goals,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_name.required' => 'Nama barang wajib diisi.',
            'item_name.max' => 'Nama barang maksimal 200 karakter.',
            'purchase_url.url' => 'Format link pembelian tidak valid.',
            'price.required' => 'Harga barang wajib diisi.',
            'price.numeric' => 'Harga barang harus berupa angka.',
            'price.min' => 'Harga barang minimal 1.',
            'delay_days.integer' => 'Masa tunda harus berupa angka bulat.',
            'delay_days.min' => 'Masa tunda minimal 1 hari.',
            'delay_days.max' => 'Masa tunda maksimal 365 hari.',
            'savings_goal_id.exists' => 'Target tabungan tidak ditemukan.',
        ];
    }
}

