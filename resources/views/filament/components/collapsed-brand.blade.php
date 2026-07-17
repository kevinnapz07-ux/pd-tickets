<a
    class="pdug-admin-brand-collapsed"
    href="{{ route('filament.admin.pages.dashboard') }}"
    aria-label="{{ config('branding.admin_title') }} — {{ config('branding.admin_subtitle') }}"
    x-cloak
    x-show="! $store.sidebar.isOpen"
>
    <img src="{{ asset(config('branding.logo')) }}" alt="">
</a>

<style>
    .pdug-admin-brand-collapsed {
        display: none;
        width: 2.5rem;
        height: 2.5rem;
        flex: 0 0 2.5rem;
        align-items: center;
        justify-content: center;
        margin-inline: auto;
    }

    .pdug-admin-brand-collapsed img {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.5rem;
        object-fit: contain;
        box-shadow: 0 8px 20px rgb(37 99 235 / 24%);
    }

    @media (min-width: 64rem) {
        .pdug-admin-brand-collapsed {
            display: flex;
        }
    }
</style>
