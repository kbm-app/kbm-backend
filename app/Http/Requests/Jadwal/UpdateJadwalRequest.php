<?php

namespace App\Http\Requests\Jadwal;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJadwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->value === 'super_admin';
    }

    public function rules(): array
    {
        return [
            'program_id'      => ['sometimes', 'integer', 'exists:program,id'],
            'kelas_id'        => ['nullable', 'integer', 'exists:kelas,id'],
            'pengajar_id'     => ['nullable', 'integer', 'exists:pengajar,id'],
            'frekuensi'       => ['sometimes', 'in:mingguan,bulanan'],
            'minggu_ke'       => ['nullable', 'integer', 'min:1', 'max:4'],
            'hari'            => ['sometimes', 'in:senin,selasa,rabu,kamis,jumat,sabtu,minggu'],
            'jam_mulai'       => ['sometimes', 'date_format:H:i'],
            'jam_selesai'     => ['sometimes', 'date_format:H:i', 'after:jam_mulai'],
            'mulai_berlaku'   => ['sometimes', 'date'],
            'selesai_berlaku' => ['nullable', 'date'],
        ];
    }
}
