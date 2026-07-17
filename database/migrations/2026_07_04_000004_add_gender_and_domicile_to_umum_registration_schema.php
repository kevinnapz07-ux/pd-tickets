<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('events')
            ->whereNotNull('registration_form_schema')
            ->orderBy('id')
            ->get(['id', 'registration_form_schema'])
            ->each(function (object $event): void {
                $schema = json_decode((string) $event->registration_form_schema, true);

                if (! is_array($schema)) {
                    return;
                }

                $schema = collect($schema)
                    ->map(function (array $category): array {
                        if (($category['key'] ?? null) !== 'umum') {
                            return $category;
                        }

                        $fields = collect($category['fields'] ?? ['name', 'email', 'phone']);

                        foreach (['gender', 'domicile'] as $field) {
                            if (! $fields->contains($field)) {
                                $fields->push($field);
                            }
                        }

                        $category['fields'] = $fields->unique()->values()->all();

                        return $category;
                    })
                    ->values()
                    ->all();

                DB::table('events')->where('id', $event->id)->update([
                    'registration_form_schema' => json_encode($schema),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('events')
            ->whereNotNull('registration_form_schema')
            ->orderBy('id')
            ->get(['id', 'registration_form_schema'])
            ->each(function (object $event): void {
                $schema = json_decode((string) $event->registration_form_schema, true);

                if (! is_array($schema)) {
                    return;
                }

                $schema = collect($schema)
                    ->map(function (array $category): array {
                        if (($category['key'] ?? null) !== 'umum') {
                            return $category;
                        }

                        $category['fields'] = collect($category['fields'] ?? [])
                            ->reject(fn (string $field): bool => in_array($field, ['gender', 'domicile'], true))
                            ->values()
                            ->all();

                        return $category;
                    })
                    ->values()
                    ->all();

                DB::table('events')->where('id', $event->id)->update([
                    'registration_form_schema' => json_encode($schema),
                ]);
            });
    }
};
