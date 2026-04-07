<aside class="sidebar">
    <div class="sidebar-top">
        <div class="sidebar-header">
            <div>
                <p class="eyebrow">Time Schedule</p>
                <h2>Dashboard</h2>
            </div>

            <button
                type="button"
                class="sidebar-toggle"
                data-sidebar-toggle
                aria-expanded="false"
                aria-controls="mobile-nav"
                aria-label="Open menu"
            >
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <nav class="nav-list" id="mobile-nav" data-sidebar-nav>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Overview / Resumen / جائزہ</a>
            <a href="{{ route('backups.index') }}" class="{{ request()->routeIs('backups.*') ? 'active' : '' }}">Backup database / Respaldo BD / بیک اپ</a>
            <a href="{{ route('payments.index') }}" class="{{ request()->routeIs('payments.*') ? 'active' : '' }}">Payments / Pagos / ادائیگیاں</a>
            <a href="{{ route('projects.index') }}" class="{{ request()->routeIs('projects.*') ? 'active' : '' }}">Projects / Proyectos / منصوبے</a>
            <a href="{{ route('workers.index') }}" class="{{ request()->routeIs('workers.*') ? 'active' : '' }}">Workers / Trabajadores / کارکن</a>

            <form method="POST" action="{{ route('logout') }}" class="sidebar-logout-mobile">
                @csrf
                <button type="submit" class="button button-secondary button-block">Logout / Cerrar sesión / لاگ آؤٹ</button>
            </form>
        </nav>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="sidebar-logout-desktop">
        @csrf
        <button type="submit" class="button button-secondary button-block">Logout / Cerrar sesión / لاگ آؤٹ</button>
    </form>
</aside>
