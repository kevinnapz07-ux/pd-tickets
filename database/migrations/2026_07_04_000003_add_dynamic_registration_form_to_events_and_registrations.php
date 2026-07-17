<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->json('registration_form_schema')->nullable()->after('registration_is_open');
        });

        Schema::table('registrations', function (Blueprint $table): void {
            $table->json('custom_fields')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('registration_form_schema');
        });

        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropColumn('custom_fields');
        });
    }
};
