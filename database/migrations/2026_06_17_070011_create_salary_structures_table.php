<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->string('pay_frequency')->default('monthly'); // monthly|biweekly
            $table->unsignedSmallInteger('working_days_per_month')->default(22);
            $table->decimal('overtime_rate_multiplier', 4, 2)->default(1.25);
            $table->decimal('late_deduction_per_minute', 8, 2)->default(0);
            $table->string('absence_deduction_mode')->default('daily_rate'); // daily_rate|fixed
            $table->decimal('perfect_attendance_bonus', 10, 2)->default(0);
            $table->json('allowances')->nullable(); // [{name, amount}]
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};
