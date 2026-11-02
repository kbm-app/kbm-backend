<?php

namespace App\Http\Requests\AsistenAi;

use Illuminate\Foundation\Http\FormRequest;

class EkstrakAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->value === 'super_admin';
    }

    public function messages(): array
    {
        return [
            'teks.required_without' => 'Tempel teks laporan atau unggah foto lembar absen.',
            'gambar.max'            => 'Maksimal 4 foto sekali proses.',
            'gambar.*.image'        => 'File harus berupa gambar.',
            'gambar.*.mimes'        => 'Format foto harus JPG, PNG, atau WEBP.',
            'gambar.*.max'          => 'Ukuran foto maksimal 5 MB.',
        ];
    }

    public function rules(): array
    {
        return [
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'teks'     => ['nullable', 'required_without:gambar', 'string', 'max:10000'],
            'gambar'   => ['nullable', 'array', 'max:4'],
            'gambar.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'provider'  => ['nullable', 'string', 'in:openai,anthropic'],
            'model'     => ['nullable', 'string', 'max:100'],
            'instruksi' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
