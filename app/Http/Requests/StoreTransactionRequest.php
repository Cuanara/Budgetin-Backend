<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'type' => [
                'required',
                'in:income,expense',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'transaction_date' => [
                'required',
                'date',
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.integer' => 'Kategori harus berupa angka.',
            'category_id.exists' => 'Kategori tidak ditemukan.',
            'type.required' => 'Jenis transaksi wajib diisi.',
            'type.in' => 'Jenis transaksi harus income atau expense.',
            'amount.required' => 'Jumlah transaksi wajib diisi.',
            'amount.numeric' => 'Jumlah transaksi harus berupa angka.',
            'amount.min' => 'Jumlah transaksi minimal 0.01.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.date' => 'Format tanggal transaksi tidak valid.',
            'description.string' => 'Deskripsi harus berupa teks.',
        ];
    }
}
