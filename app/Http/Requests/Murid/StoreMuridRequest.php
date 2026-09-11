<?php

namespace App\Http\Requests\Murid;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMuridRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role->value === 'super_admin';
    }

    public function rules(): array
    {
        return [
            'nama'              => ['required', 'string', 'max:100'],
            'tempat_lahir'      => ['nullable', 'string', 'max:100'],
            'jenis_kelamin'     => ['required', 'in:L,P'],
            'tanggal_lahir'     => ['required', 'date'],
            'alamat'            => ['nullable', 'string'],
            'tanggal_masuk'     => ['nullable', 'date'],
            'status'            => ['in:aktif,nonaktif,alumni,pindah'],
            'foto'              => ['nullable', 'image', 'max:2048'],
            'wali'              => ['nullable', 'array'],
            'wali.*.nama'       => ['required_with:wali', 'string', 'max:100'],
            'wali.*.hubungan'   => ['required_with:wali', 'in:ayah,ibu,kakak,nenek,kakek,wali_lain'],
            'wali.*.phones'     => ['required_with:wali', 'array', 'min:1'],
            'wali.*.phones.*'   => ['required', 'string', 'max:20'],
            'wali.*.pekerjaan'  => ['nullable', 'string', 'max:100'],
            'wali.*.is_primary' => ['boolean'],
        ];
    }
}
