<?php

namespace App\Http\Requests\Jadwal;

use Illuminate\Foundation\Http\FormRequest;

class StoreJadwalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->value === 'super_admin';
    }

    public function rules(): array
    {
        return [
            'program_id'      => ['required', 'integer', 'exists:program,id'],
            'kelas_id'        => ['nullable', 'integer', 'exists:kelas,id'],
            'pengajar_id'     => ['nullable', 'integer', 'exists:pengajar,id'],
            'frekuensi'       => ['required', 'in:mingguan,bulanan'],
            'minggu_ke'       => ['nullable', 'integer', 'min:1', 'max:4', 'required_if:frekuensi,bulanan'],
            'hari'            => ['required', 'in:senin,selasa,rabu,kamis,jumat,sabtu,minggu'],
            'jam_mulai'       => ['required', 'date_format:H:i'],
            'jam_selesai'     => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'mulai_berlaku'   => ['required', 'date', 'after_or_equal:today'],
            'selesai_berlaku' => ['nullable', 'date', 'after_or_equal:mulai_berlaku'],
        ];
    }
}
