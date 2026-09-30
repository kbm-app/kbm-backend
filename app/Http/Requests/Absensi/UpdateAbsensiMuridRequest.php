<?php

namespace App\Http\Requests\Absensi;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAbsensiMuridRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->value === 'super_admin';
    }

    public function rules(): array
    {
        return [
            'status'  => ['required', 'in:hadir,izin,sakit,alpha,terlambat'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ];
    }
}
