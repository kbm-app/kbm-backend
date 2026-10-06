<?php

namespace App\Http\Requests\Absensi;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePertemuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Batasan per kelas (termasuk ketua kelas) dicek oleh PertemuanPolicy di controller
        return in_array($this->user()->role->value, ['super_admin', 'pengajar', 'murid']);
    }

    public function rules(): array
    {
        return [
            'materi'      => ['nullable', 'string', 'max:1000'],
            'catatan'     => ['nullable', 'string', 'max:1000'],
            // Jam sesi hanya dikoreksi super admin pada sesi yang sudah selesai (dicek di controller)
            'jam_mulai'   => ['sometimes', 'required', 'date_format:H:i'],
            'jam_selesai' => ['sometimes', 'required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'jam_mulai.date_format'   => 'Format jam mulai: HH:MM.',
            'jam_selesai.date_format' => 'Format jam selesai: HH:MM.',
        ];
    }
}
