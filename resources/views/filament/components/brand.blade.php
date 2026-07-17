<div class="pdug-admin-brand">
    <img class="pdug-admin-brand__mark" src="{{ asset(config('branding.logo')) }}" alt="">
    <span class="pdug-admin-brand__copy">
        <strong>{{ config('branding.admin_title') }}</strong>
        <small>{{ config('branding.admin_subtitle') }}</small>
    </span>
</div>

<style>
    .pdug-admin-brand {
        display: inline-flex;
        align-items: center;
        gap: 0.625rem;
        height: 2.5rem;
        width: min(15rem, calc(100vw - 6rem));
        max-width: 100%;
        color: currentColor;
    }

    .pdug-admin-brand__mark {
        display: block;
        flex: 0 0 2.5rem;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.5rem;
        object-fit: contain;
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.24);
    }

    .pdug-admin-brand__copy {
        display: grid;
        min-width: 0;
        line-height: 1.15;
    }

    .pdug-admin-brand__copy strong,
    .pdug-admin-brand__copy small {
        white-space: nowrap;
    }

    .pdug-admin-brand__copy strong {
        font-size: 0.9rem;
        font-weight: 800;
    }

    .pdug-admin-brand__copy small {
        margin-top: 0.2rem;
        color: #94a3b8;
        font-size: 0.6875rem;
        font-weight: 500;
    }

    .dark .pdug-admin-brand__copy small {
        color: #a8b6ca;
    }

    @media (max-width: 420px) {
        .pdug-admin-brand__copy small {
            display: none;
        }
    }

    .fi-global-search-field .fi-input-wrp {
        border-radius: 0.5rem;
        background: var(--gray-50) !important;
        box-shadow: 0 0 0 1px color-mix(in srgb, var(--gray-950) 12%, transparent) !important;
    }

    .fi-global-search-field .fi-input-wrp:focus-within {
        box-shadow: 0 0 0 1px color-mix(in srgb, var(--gray-950) 24%, transparent) !important;
    }

    .fi-global-search-field input {
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        color: var(--gray-950) !important;
        outline: none !important;
    }

    .fi-global-search-field input::placeholder {
        color: var(--gray-500) !important;
        opacity: 1;
    }

    .fi.dark .fi-global-search-field .fi-input-wrp {
        background: var(--gray-900) !important;
        box-shadow: 0 0 0 1px rgb(255 255 255 / 12%) !important;
    }

    .fi.dark .fi-global-search-field .fi-input-wrp:focus-within {
        box-shadow: 0 0 0 1px rgb(255 255 255 / 24%) !important;
    }

    .fi.dark .fi-global-search-field input {
        color: #fff !important;
    }

    .fi.dark .fi-global-search-field input::placeholder {
        color: var(--gray-500) !important;
    }
</style>
