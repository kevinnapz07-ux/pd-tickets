<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('registrations', 'verification_token')) {
            Schema::table('registrations', function (Blueprint $table): void {
                $table->string('verification_token', 64)->nullable()->after('registration_code');
            });
        }

        if (! Schema::hasColumn('registrations', 'checked_in_by')) {
            Schema::table('registrations', function (Blueprint $table): void {
                $table->foreignId('checked_in_by')->nullable()->after('checked_in_at')->constrained('users')->nullOnDelete();
            });
        }

        DB::table('registrations')->whereNull('verification_token')->orderBy('id')->eachById(function ($registration): void {
            do {
                $token = Str::random(64);
            } while (DB::table('registrations')->where('verification_token', $token)->exists());

            DB::table('registrations')->where('id', $registration->id)->update(['verification_token' => $token]);
        });

        Schema::table('registrations', function (Blueprint $table): void {
            $table->unique('verification_token');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropForeign(['checked_in_by']);
            $table->dropUnique(['verification_token']);
            $table->dropColumn(['verification_token', 'checked_in_by']);
        });
    }
};
