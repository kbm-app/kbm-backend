<?php

namespace App\Http\Requests\AsistenAi;

use Illuminate\Foundation\Http\FormRequest;

class SimpanAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->value === 'super_admin';
    }

    public function messages(): array
    {
        return [
            'kelas_id.required'                   => 'Pilih kelas terlebih dahulu.',
            'sesi.required_without'               => 'Daftar sesi absensi wajib diisi.',
            'pengajar.required'                   => 'Pilih minimal satu pengajar yang bertugas.',
            'pengajar.min'                        => 'Pilih minimal satu pengajar yang bertugas.',
            'pengajar.*.pengganti_id.required_if' => 'Pilih pengajar pengganti.',
            'pengajar.*.pengganti_id.different'   => 'Pengganti tidak boleh pengajar yang sama.',
            'absensi.required'                    => 'Belum ada murid di daftar absensi.',
            'jam_selesai.required_if'             => 'Jam selesai wajib diisi bila sesi langsung ditutup.',
            'jam_selesai.after'                   => 'Jam selesai harus setelah jam mulai.',
        ];
    }

    public function rules(): array
    {
        $hasMultiSesi = $this->has('sesi') && is_array($this->input('sesi'));

        $rules = [
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
        ];

        if ($hasMultiSesi) {
            $rules['sesi']                            = ['required', 'array', 'min:1'];
            $rules['sesi.*.tanggal']                  = ['required', 'date', 'before_or_equal:today'];
            $rules['sesi.*.program_id']                = ['required', 'integer', 'exists:program,id'];
            $rules['sesi.*.jadwal_id']                 = ['nullable', 'integer', 'exists:jadwal,id'];
            $rules['sesi.*.jam_mulai']                 = ['nullable', 'date_format:H:i'];
            $rules['sesi.*.jam_selesai']               = ['nullable', 'date_format:H:i'];
            $rules['sesi.*.materi']                    = ['nullable', 'string', 'max:1000'];
            $rules['sesi.*.pengajar']                  = ['required', 'array', 'min:1'];
            $rules['sesi.*.pengajar.*.pengajar_id']    = ['required', 'integer', 'exists:pengajar,id'];
            $rules['sesi.*.pengajar.*.status']         = ['required', 'in:hadir,berhalangan,digantikan'];
            $rules['sesi.*.pengajar.*.pengganti_id']   = ['nullable', 'integer', 'exists:pengajar,id'];
            $rules['sesi.*.pengajar.*.keterangan']     = ['nullable', 'string', 'max:500'];
            $rules['sesi.*.absensi']                   = ['required', 'array'];
            $rules['sesi.*.absensi.*.murid_id']        = ['required', 'integer', 'exists:murid,id'];
            $rules['sesi.*.absensi.*.status']          = ['required', 'in:hadir,izin,sakit,alpha,terlambat'];
            $rules['sesi.*.absensi.*.keterangan']      = ['nullable', 'string', 'max:500'];
            $rules['sesi.*.tutup_sesi']                = ['boolean'];
            $rules['sesi.*.kirim_notifikasi']          = ['boolean'];
        } else {
            // Mode single-sesi lama
            $rules['program_id']  = ['required', 'integer', 'exists:program,id'];
            $rules['jadwal_id']   = ['nullable', 'integer', 'exists:jadwal,id'];
            $rules['tanggal']     = ['required', 'date', 'before_or_equal:today'];
            $rules['jam_mulai']   = ['required', 'date_format:H:i'];
            $rules['jam_selesai'] = ['nullable', 'required_if:tutup_sesi,true', 'date_format:H:i'];
            $rules['materi']      = ['nullable', 'string', 'max:1000'];

            $rules['pengajar']                = ['required', 'array', 'min:1'];
            $rules['pengajar.*.pengajar_id']  = ['required', 'integer', 'distinct', 'exists:pengajar,id'];
            $rules['pengajar.*.status']       = ['required', 'in:hadir,berhalangan,digantikan'];
            $rules['pengajar.*.pengganti_id'] = ['nullable', 'integer', 'exists:pengajar,id', 'required_if:pengajar.*.status,digantikan', 'different:pengajar.*.pengajar_id'];
            $rules['pengajar.*.keterangan']   = ['nullable', 'string', 'max:500'];

            $rules['absensi']              = ['required', 'array', 'min:1'];
            $rules['absensi.*.murid_id']   = ['required', 'integer', 'distinct', 'exists:murid,id'];
            $rules['absensi.*.status']     = ['required', 'in:hadir,izin,sakit,alpha,terlambat'];
            $rules['absensi.*.keterangan'] = ['nullable', 'string', 'max:500'];

            $rules['tutup_sesi']       = ['boolean'];
            $rules['kirim_notifikasi'] = ['boolean'];
        }

        return $rules;
    }
}
