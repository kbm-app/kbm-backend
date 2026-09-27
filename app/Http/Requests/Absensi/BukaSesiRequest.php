<?php

namespace App\Http\Requests\Absensi;

use App\Models\Kelas;
use Illuminate\Foundation\Http\FormRequest;

class BukaSesiRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (in_array($this->user()->role->value, ['super_admin', 'pengajar'])) {
            return true;
        }

        // Ketua kelas hanya boleh membuka sesi untuk kelasnya sendiri
        $kelas = Kelas::find($this->input('kelas_id'));
        return $kelas !== null && $kelas->bisaKelolaAbsensi($this->user());
    }

    public function rules(): array
    {
        return [
            'kelas_id'   => ['required', 'integer', 'exists:kelas,id'],
            'program_id' => ['required', 'integer', 'exists:program,id'],
            'pengajar_id' => ['required', 'integer', 'exists:pengajar,id'],
            'jadwal_id'  => ['nullable', 'integer', 'exists:jadwal,id'],
            'tanggal'    => ['required', 'date'],
            'jam_mulai'  => ['required', 'date_format:H:i'],
            'materi'     => ['nullable', 'string', 'max:1000'],
        ];
    }
}
