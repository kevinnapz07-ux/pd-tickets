<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('registration_section_title')->default('Data Registrasi')->after('announcement');
            $table->text('registration_section_description')->nullable()->after('registration_section_title');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'registration_section_title',
                'registration_section_description',
            ]);
        });
    }
};
