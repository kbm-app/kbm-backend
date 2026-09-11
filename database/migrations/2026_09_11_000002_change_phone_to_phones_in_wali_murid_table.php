<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wali_murid', function (Blueprint $table) {
            $table->json('phones')->nullable()->after('phone');
        });

        DB::table('wali_murid')->select('id', 'phone')->orderBy('id')->each(function ($row) {
            DB::table('wali_murid')->where('id', $row->id)->update([
                'phones' => json_encode(array_values(array_filter([$row->phone]))),
            ]);
        });

        Schema::table('wali_murid', function (Blueprint $table) {
            $table->json('phones')->nullable(false)->change();
            $table->dropColumn('phone');
        });
    }

    public function down(): void
    {
        Schema::table('wali_murid', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('hubungan');
        });

        DB::table('wali_murid')->select('id', 'phones')->orderBy('id')->each(function ($row) {
            $phones = json_decode($row->phones, true) ?? [];
            DB::table('wali_murid')->where('id', $row->id)->update([
                'phone' => $phones[0] ?? '',
            ]);
        });

        Schema::table('wali_murid', function (Blueprint $table) {
            $table->string('phone', 20)->nullable(false)->change();
            $table->dropColumn('phones');
        });
    }
};
