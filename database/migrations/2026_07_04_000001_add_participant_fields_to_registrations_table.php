<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->string('participant_type')->default('umum')->after('email');
            $table->string('campus_area')->nullable()->after('student_id');
            $table->string('class_year')->nullable()->after('campus_area');
            $table->string('study_program')->nullable()->after('class_year');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropColumn([
                'participant_type',
                'campus_area',
                'class_year',
                'study_program',
            ]);
        });
    }
};
