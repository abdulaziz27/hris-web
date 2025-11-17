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
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('workdays_per_week')->default(5)->after('location_id')
                ->comment('Jumlah hari kerja per minggu: 5 (Senin-Jumat), 6 (Senin-Sabtu), 7 (Full). Default: 5');
            
            $table->integer('standard_workdays_per_month')->nullable()->after('workdays_per_week')
                ->comment('Hari kerja standar per bulan (opsional, untuk override). Jika null, akan dihitung dari workdays_per_week');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['workdays_per_week', 'standard_workdays_per_month']);
        });
    }
};
