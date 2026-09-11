<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE wali_murid DROP CONSTRAINT wali_murid_hubungan_check');
        DB::statement("ALTER TABLE wali_murid ADD CONSTRAINT wali_murid_hubungan_check CHECK (hubungan::text = ANY (ARRAY['ayah','ibu','kakak','nenek','kakek','wali_lain']::text[]))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE wali_murid DROP CONSTRAINT wali_murid_hubungan_check');
        DB::statement("ALTER TABLE wali_murid ADD CONSTRAINT wali_murid_hubungan_check CHECK (hubungan::text = ANY (ARRAY['ayah','ibu','kakak','wali_lain']::text[]))");
    }
};
