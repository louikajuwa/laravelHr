<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code')->nullable()->unique()->after('id');
            $table->foreignId('department_id')->nullable()->after('email')->constrained('departments')->nullOnDelete();
            $table->foreignId('job_position_id')->nullable()->after('department_id')->constrained('job_positions')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->after('job_position_id')->constrained('shifts')->nullOnDelete();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('employment_type')->default('full_time');
            $table->boolean('is_active')->default(true);
            $table->string('avatar_path')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('job_position_id');
            $table->dropConstrainedForeignId('shift_id');
            $table->dropColumn([
                'employee_code', 'phone', 'address', 'date_of_birth', 'gender',
                'hire_date', 'employment_type', 'is_active', 'avatar_path', 'deleted_at',
            ]);
        });
    }
};
