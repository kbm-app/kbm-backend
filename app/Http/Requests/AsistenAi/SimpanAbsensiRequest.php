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
            'pengajar.required'                => 'Pilih minimal satu pengajar yang bertugas.',
            'pengajar.min'                     => 'Pilih minimal satu pengajar yang bertugas.',
            'pengajar.*.pengganti_id.required_if' => 'Pilih pengajar pengganti.',
            'pengajar.*.pengganti_id.different'   => 'Pengganti tidak boleh pengajar yang sama.',
            'absensi.required'                 => 'Belum ada murid di daftar absensi.',
            'jam_selesai.required_if'          => 'Jam selesai wajib diisi bila sesi langsung ditutup.',
            'jam_selesai.after'                => 'Jam selesai harus setelah jam mulai.',
        ];
    }

    public function rules(): array
    {
        return [
            'kelas_id'    => ['required', 'integer', 'exists:kelas,id'],
            'program_id'  => ['required', 'integer', 'exists:program,id'],
            'jadwal_id'   => ['nullable', 'integer', 'exists:jadwal,id'],
            'tanggal'     => ['required', 'date', 'before_or_equal:today'],
            'jam_mulai'   => ['required', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'required_if:tutup_sesi,true', 'date_format:H:i', 'after:jam_mulai'],
            'materi'      => ['nullable', 'string', 'max:1000'],

            'pengajar'                => ['required', 'array', 'min:1'],
            'pengajar.*.pengajar_id'  => ['required', 'integer', 'distinct', 'exists:pengajar,id'],
            'pengajar.*.status'       => ['required', 'in:hadir,berhalangan,digantikan'],
            'pengajar.*.pengganti_id' => ['nullable', 'integer', 'exists:pengajar,id', 'required_if:pengajar.*.status,digantikan', 'different:pengajar.*.pengajar_id'],
            'pengajar.*.keterangan'   => ['nullable', 'string', 'max:500'],

            'absensi'              => ['required', 'array', 'min:1'],
            'absensi.*.murid_id'   => ['required', 'integer', 'distinct', 'exists:murid,id'],
            'absensi.*.status'     => ['required', 'in:hadir,izin,sakit,alpha,terlambat'],
            'absensi.*.keterangan' => ['nullable', 'string', 'max:500'],

            'tutup_sesi'       => ['boolean'],
            'kirim_notifikasi' => ['boolean'],
        ];
    }
}
