<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="index.html" class="app-brand-link">
            {{-- <span class="app-brand-logo demo">
                <img src="{{ asset('assets/logo.webp') }}" width="100px">
            </span> --}}
            <span class=""><b>{{ config('app.name') }}</b></span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
            </a>
        </li>

        @if (Auth::user()->roles[0]['name'] == 'super-admin')
            <li class="menu-item {{ request()->routeIs('user.index') ? 'active' : '' }}">
                <a href="{{ route('user.index') }}" class="menu-link">
                    {{-- <i class="menu-icon tf-icons bx bx-home-circle"></i> --}}
                    <i class="menu-icon tf-icons bx bx-lock-open-alt"></i>
                    <div data-i18n="Analytics">Users</div>
                </a>
            </li>

            
        @else
            <li class="menu-item {{ request()->routeIs('user.index') ? 'active' : '' }}">
                <a href="{{ route('user.index') }}" class="menu-link">
                    {{-- <i class="menu-icon tf-icons bx bx-home-circle"></i> --}}
                    <i class="menu-icon tf-icons bx bx-cog"></i>
                    <div data-i18n="Analytics">Mutasi</div>
                </a>
            </li>
        @endif



        <!-- Layouts -->
        <li {{-- class="menu-item {{ request()->routeIs('roles.*') || request()->routeIs('faq.*') || request()->routeIs('infografis.*') || request()->routeIs('galeri.*') || request()->routeIs('berita.*') ? 'active open' : '' }}"> --}} class="menu-item" hidden>
            <a href="" class="menu-link menu-toggle">
                <i class="menu-icon tf-icons bx bx-layout"></i>
                <div data-i18n="Layouts">Setting</div>
            </a>

            <ul class="menu-sub">
                {{-- <li class="menu-item {{ request()->routeIs('roles.index') ? 'active' : '' }}">
                    <a href="{{ route('roles.index') }}" class="menu-link">
                        <div data-i18n="Without menu">Roles</div>
                    </a>
                </li> --}}
                <li class="menu-item">
                    <a href="#" class="menu-link">
                        <div data-i18n="Without menu">Sample</div>
                    </a>
                </li>

            </ul>
        </li>



    </ul>
</aside>
<!-- / Menu -->
