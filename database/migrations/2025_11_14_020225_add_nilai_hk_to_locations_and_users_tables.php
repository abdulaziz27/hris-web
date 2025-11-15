<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add nilai_hk to locations table
        Schema::table('locations', function (Blueprint $table) {
            $table->decimal('nilai_hk', 12, 2)->nullable()->after('default_salary')
                ->comment('Nilai HK default untuk lokasi ini (rate bayaran per hari kerja)');
        });

        // Add nilai_hk to users table
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('nilai_hk', 12, 2)->nullable()->after('basic_salary')
                ->comment('Nilai HK khusus untuk user ini (jika ada, akan override nilai_hk dari location)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('nilai_hk');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nilai_hk');
        });
    }
};
