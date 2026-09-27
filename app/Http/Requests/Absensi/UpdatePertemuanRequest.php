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
            'materi'  => ['nullable', 'string', 'max:1000'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
