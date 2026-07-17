<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
        });

        DB::table('registrations')
            ->whereNull('user_id')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $registration): void {
                $userId = User::where('email', $registration->email)->value('id');

                if ($userId) {
                    DB::table('registrations')
                        ->where('id', $registration->id)
                        ->update(['user_id' => $userId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
