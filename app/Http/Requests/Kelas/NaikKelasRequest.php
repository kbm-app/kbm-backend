<?php

namespace App\Http\Requests\Kelas;

use Illuminate\Foundation\Http\FormRequest;

class NaikKelasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->value === 'super_admin';
    }

    public function rules(): array
    {
        return [
            'kelas_tujuan_id' => ['required', 'integer', 'exists:kelas,id', 'different:kelas'],
            'murid_ids'       => ['required', 'array', 'min:1'],
            'murid_ids.*'     => ['integer', 'exists:murid,id'],
        ];
    }
}
