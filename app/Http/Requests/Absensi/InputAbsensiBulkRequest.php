<?php

namespace App\Http\Requests\Absensi;

use Illuminate\Foundation\Http\FormRequest;

class InputAbsensiBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Batasan per kelas (termasuk ketua kelas) dicek oleh PertemuanPolicy di controller
        return in_array($this->user()->role->value, ['super_admin', 'pengajar', 'murid']);
    }

    public function rules(): array
    {
        return [
            'absensi'              => ['required', 'array', 'min:1'],
            'absensi.*.murid_id'   => ['required', 'integer', 'exists:murid,id'],
            'absensi.*.status'     => ['required', 'in:hadir,izin,sakit,alpha,terlambat'],
            'absensi.*.catatan'    => ['nullable', 'string', 'max:500'],
        ];
    }
}
