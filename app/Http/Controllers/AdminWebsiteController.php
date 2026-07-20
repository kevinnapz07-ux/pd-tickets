<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminWebsiteController extends Controller
{
    public function edit(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.website.edit', [
            'setting' => SiteSetting::current(),
            'events' => Event::orderBy('title')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'site_tagline' => ['required', 'string', 'max:120'],
            'hero_title' => ['required', 'string', 'max:120'],
            'hero_subtitle' => ['required', 'string'],

            'hero_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'about_image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'remove_hero_image' => ['nullable', 'boolean'],
            'remove_about_image' => ['nullable', 'boolean'],

            'contact_email' => ['required', 'email', 'max:120'],

            'contact_phone' => [
                'required',
                'string',
                'max:40',
                function (
                    string $attribute,
                    mixed $value,
                    \Closure $fail
                ): void {
                    if (! SiteSetting::whatsappNumber($value)) {
                        $fail(
                            'Masukkan nomor WhatsApp Indonesia yang valid, misalnya 082199773846 atau +62 821-9977-3846.'
                        );
                    }
                },
            ],

            'contact_address' => ['required', 'string', 'max:180'],
        ]);

        $setting = SiteSetting::current();

        /*
        |--------------------------------------------------------------------------
        | Banner halaman utama
        |--------------------------------------------------------------------------
        */

        if (
            $request->boolean('remove_hero_image')
            && $setting->hero_image
        ) {
            Storage::disk('public')->delete($setting->hero_image);
            $data['hero_image'] = null;
        }

        if ($request->hasFile('hero_image')) {
            if ($setting->hero_image) {
                Storage::disk('public')->delete($setting->hero_image);
            }

            $data['hero_image'] = $request
                ->file('hero_image')
                ->store('website-banners', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | Banner halaman About
        |--------------------------------------------------------------------------
        */

        if (
            $request->boolean('remove_about_image')
            && $setting->about_image
        ) {
            Storage::disk('public')->delete($setting->about_image);
            $data['about_image'] = null;
        }

        if ($request->hasFile('about_image')) {
            if ($setting->about_image) {
                Storage::disk('public')->delete($setting->about_image);
            }

            $data['about_image'] = $request
                ->file('about_image')
                ->store('website-banners', 'public');
        }

        $setting->update($data);

        $builderSchemas = $request->input(
            'event_registration_builder',
            []
        );

        $textSchemas = $request->input(
            'event_registration_schema',
            []
        );

        $eventIds = array_unique(
            array_merge(
                array_keys($builderSchemas),
                array_keys($textSchemas)
            )
        );

        foreach (Event::whereIn('id', $eventIds)->get() as $event) {
            $schema = array_key_exists(
                $event->id,
                $builderSchemas
            )
                ? Event::parseRegistrationSchemaBuilder(
                    $builderSchemas[$event->id]
                )
                : Event::parseRegistrationSchemaText(
                    $textSchemas[$event->id] ?? null
                );

            $event->update([
                'registration_form_schema' => $schema,
            ]);
        }

        return back()->with(
            'status',
            'Pengaturan website berhasil diperbarui.'
        );
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'admin',
            403
        );
    }
}