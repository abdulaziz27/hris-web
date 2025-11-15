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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('period'); // Awal bulan periode (2025-01-01)
            $table->integer('standard_workdays')->default(21); // Hari kerja standar bulan
            $table->integer('present_days')->default(0); // Total kehadiran (include weekend kerja)
            $table->integer('hk_review')->nullable(); // Manual review (default = present_days)
            $table->decimal('nilai_hk', 12, 2)->default(0); // Nilai HK = Basic Salary / Standard Workdays
            $table->decimal('basic_salary', 12, 2)->default(0); // Gaji pokok yang digunakan
            $table->decimal('estimated_salary', 12, 2)->default(0); // Estimasi = Nilai HK × Present Days
            $table->decimal('final_salary', 12, 2)->default(0); // Final = Nilai HK × HK Review
            $table->integer('selisih_hk')->default(0); // Selisih = HK Review - Standard Workdays
            $table->decimal('percentage', 5, 2)->default(0); // Persentase = (Present Days / Standard Workdays) × 100%
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Unique constraint: satu user hanya bisa punya satu payroll per periode
            $table->unique(['user_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
