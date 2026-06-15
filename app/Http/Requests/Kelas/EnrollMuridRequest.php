<?php

namespace App\Http\Requests\Kelas;

use Illuminate\Foundation\Http\FormRequest;

class EnrollMuridRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->value === 'super_admin';
    }

    public function rules(): array
    {
        return [
            'murid_id'      => ['required', 'integer', 'exists:murid,id'],
            'tahun_ajaran'  => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'tanggal_masuk' => ['required', 'date'],
        ];
    }
}
