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
        Schema::table('locations', function (Blueprint $table) {
            $table->json('weekend_pattern')->nullable()->after('timezone')
                ->comment('Array hari weekend untuk lokasi ini. Contoh: ["saturday", "sunday"] atau ["sunday"] atau [] (tidak ada weekend). Default: ["saturday", "sunday"]');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('weekend_pattern');
        });
    }
};
