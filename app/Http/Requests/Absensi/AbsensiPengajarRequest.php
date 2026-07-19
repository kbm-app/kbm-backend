<?php

namespace App\Http\Requests\Absensi;

use Illuminate\Foundation\Http\FormRequest;

class AbsensiPengajarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role->value, ['super_admin', 'pengajar']);
    }

    public function rules(): array
    {
        return [
            'status'       => ['required', 'in:hadir,berhalangan,digantikan'],
            'pengganti_id' => ['nullable', 'integer', 'exists:pengajar,id', 'required_if:status,digantikan'],
            'keterangan'   => ['nullable', 'string', 'max:500'],
        ];
    }
}
