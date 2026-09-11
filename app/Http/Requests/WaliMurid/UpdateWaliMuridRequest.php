<?php

namespace App\Http\Requests\WaliMurid;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWaliMuridRequest extends FormRequest
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
            'nama'       => ['required', 'string', 'max:100'],
            'hubungan'   => ['required', 'in:ayah,ibu,kakak,nenek,kakek,wali_lain'],
            'phones'     => ['required', 'array', 'min:1'],
            'phones.*'   => ['required', 'string', 'max:20'],
            'pekerjaan'  => ['nullable', 'string', 'max:100'],
            'is_primary' => ['boolean'],
        ];
    }
}
