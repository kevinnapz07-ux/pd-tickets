<nav class="admin-page-navigation" aria-label="Navigasi pengelolaan admin">
    <section class="admin-page-navigation-group" aria-labelledby="website-management-label">
        <div class="admin-page-navigation-heading">
            <span class="admin-navigation-icon" aria-hidden="true">W</span>
            <div>
                <strong id="website-management-label">Website Management</strong>
                <small>Dashboard, laporan, dan CMS</small>
            </div>
        </div>
        <div class="admin-page-navigation-links">
            <a href="{{ route('filament.admin.pages.dashboard') }}">Dashboard</a>
            <a href="{{ route('filament.admin.pages.laporan') }}">Laporan</a>
            <a href="{{ route('filament.admin.pages.pengaturan-website') }}">Pengaturan Website</a>
        </div>
    </section>

    <section class="admin-page-navigation-group" aria-labelledby="data-management-label">
        <div class="admin-page-navigation-heading">
            <span class="admin-navigation-icon is-filament" aria-hidden="true">D</span>
            <div>
                <strong id="data-management-label">Data Management</strong>
                <small>Resource Filament Admin</small>
            </div>
        </div>
        <div class="admin-page-navigation-links">
            <a href="{{ route('filament.admin.resources.events.index') }}">Kelola Event</a>
            <a href="{{ route('filament.admin.resources.users.index') }}">Kelola Pengguna</a>
            <a href="{{ route('filament.admin.resources.registrations.index') }}">Transaksi</a>
        </div>
    </section>
</nav>
