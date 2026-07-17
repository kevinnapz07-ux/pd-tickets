@vite(['resources/css/app.css', 'resources/js/app.js'])

<x-filament-panels::page>
    <div class="filament-custom-page">
        @include('admin.website.content')
    </div>
</x-filament-panels::page>
