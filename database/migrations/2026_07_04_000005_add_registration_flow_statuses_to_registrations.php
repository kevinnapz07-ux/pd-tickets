<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->string('registration_status')->nullable()->after('payment_status');
            $table->timestamp('checked_in_at')->nullable()->after('registration_status');
        });

        DB::table('registrations')
            ->where('payment_status', 'paid')
            ->update(['registration_status' => 'registered']);
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropColumn(['registration_status', 'checked_in_at']);
        });
    }
};
