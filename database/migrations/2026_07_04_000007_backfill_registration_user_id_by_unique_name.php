<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('registrations')
            ->whereNull('user_id')
            ->orderBy('id')
            ->lazyById()
            ->each(function (object $registration): void {
                $matchingUsers = User::where('name', $registration->name)->pluck('id');

                if ($matchingUsers->count() === 1) {
                    DB::table('registrations')
                        ->where('id', $registration->id)
                        ->update(['user_id' => $matchingUsers->first()]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
