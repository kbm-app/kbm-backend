<?php

namespace App\Http\Requests\Kelas;

use Illuminate\Foundation\Http\FormRequest;

class StoreKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->value === 'super_admin';
    }

    public function rules(): array
    {
        return [
            'nama'             => ['required', 'string', 'min:2', 'max:100'],
            'deskripsi'        => ['nullable', 'string'],
            'rentang_usia_min' => ['nullable', 'integer', 'min:1'],
            'rentang_usia_max' => ['nullable', 'integer', 'min:1', 'gte:rentang_usia_min'],
            'kapasitas'        => ['nullable', 'integer', 'min:1'],
            'is_aktif'         => ['boolean'],
        ];
    }
}
