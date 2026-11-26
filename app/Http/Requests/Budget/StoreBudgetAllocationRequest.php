<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => [
                'nullable',
                'integer',
                'min:1',
                'max:12',
            ],
            'year' => [
                'nullable',
                'integer',
                'min:2000',
            ],
            'category_id' => [
                'nullable',
                'uuid',
                'exists:categories,id',
            ],
            'name' => [
                'nullable',
                'string',
                'max:100',
                'required_without:category_id',
            ],
            'percentage' => [
                'required',
                'numeric',
                'min:0.01',
                'max:100',
            ],
            'source_category_id' => [
                'nullable',
                'uuid',
                'exists:categories,id',
            ],
            'source_allocation_id' => [
                'nullable',
                'uuid',
                'exists:budget_allocations,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.exists'           => 'Kategori tidak ditemukan.',
            'name.required_without'        => 'Nama kategori wajib diisi jika category_id tidak diberikan.',
            'percentage.required'          => 'Persentase alokasi wajib diisi.',
            'percentage.numeric'           => 'Persentase harus berupa angka.',
            'percentage.min'               => 'Persentase minimal 0.01.',
            'percentage.max'               => 'Persentase maksimal 100.',
            'source_category_id.exists'    => 'Kategori sumber tidak ditemukan.',
            'source_allocation_id.exists'  => 'Alokasi sumber tidak ditemukan.',
        ];
    }
}

