<?php

namespace App\Http\Requests\Absensi;

use Illuminate\Foundation\Http\FormRequest;

class AbsensiPengajarRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Batasan per kelas (termasuk ketua kelas) dicek oleh PertemuanPolicy di controller
        return in_array($this->user()->role->value, ['super_admin', 'pengajar', 'murid']);
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
