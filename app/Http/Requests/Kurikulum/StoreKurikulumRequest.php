<?php

namespace App\Http\Requests\Kurikulum;

use Illuminate\Foundation\Http\FormRequest;

class StoreKurikulumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->value === 'super_admin';
    }

    public function rules(): array
    {
        return [
            'kelas_ids'    => ['required', 'array', 'min:1'],
            'kelas_ids.*'  => ['integer', 'distinct', 'exists:kelas,id'],
            'nama'         => ['required', 'string', 'max:200'],
            'tahun_ajaran' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'deskripsi'    => ['nullable', 'string'],
        ];
    }
}
