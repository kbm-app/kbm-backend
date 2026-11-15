<?php

namespace App\Http\Requests\Musyawarah;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTindakLanjutNotulensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->value === 'super_admin';
    }

    public function rules(): array
    {
        return [
            'tindak_lanjut'        => ['nullable', 'string', 'max:5000'],
            'status_tindak_lanjut' => ['required', 'in:open,selesai,ditunda'],
        ];
    }
}
