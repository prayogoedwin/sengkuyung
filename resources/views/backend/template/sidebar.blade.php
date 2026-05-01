<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="{{ asset('LOGO_SENGKUYUNG/LOGO_SENGKUYUNG.png') }}" width="200px">
            </span>
            {{-- <span class=""><b>{{ config('app.name') }}</b></span> --}}
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        @php
            $user = Auth::user();
            $roleName = strtolower((string) optional($user->roles->first())->name);
            $isSuperAdmin = $user->hasRole('super-admin') || $user->hasRole('superadmin');
            $isAdminProv = $user->hasRole('admin') || $user->hasRole('adminprov');
            $isUptd = $user->hasRole('uptd');
            $isKabkota = $user->hasRole('kabkota');
            $isKecamatan = $user->hasRole('kecamatan');
            $isKelurahan = $user->hasRole('kelurahan');
            $isWilayahLower = $isKecamatan || $isKelurahan;
            $canSeeDataTertagih = $isSuperAdmin || $isAdminProv || $isUptd;
        @endphp

        <!-- Dashboard -->
        <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
            </a>
        </li>

        @if ($isSuperAdmin || $isAdminProv)
            <li class="menu-item {{ request()->routeIs('user.index') ? 'active' : '' }}">
                <a href="{{ route('user.index') }}" class="menu-link">
                    {{-- <i class="menu-icon tf-icons bx bx-home-circle"></i> --}}
                    <i class="menu-icon tf-icons bx bx-lock-open-alt"></i>
                    <div data-i18n="Analytics">Users</div>
                </a>
            </li>

            @if ($isSuperAdmin)
            <li class="menu-item {{ request()->routeIs('cache-management.index') ? 'active' : '' }}">
                <a href="{{ route('cache-management.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-data"></i>
                    <div data-i18n="Analytics">Kelola Cache</div>
                </a>
            </li>
            @endif

            <li class="menu-item {{ request()->routeIs('download.index') ? 'active' : '' }}" hidden>
                <a href="{{ route('download.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-download"></i>  {{-- Ikon Download --}}
                    <div data-i18n="Analytics">Download</div>
                </a>
            </li>
            
            @if ($canSeeDataTertagih)
            <li class="menu-item {{ request()->routeIs('data-tertagih.index') ? 'active' : '' }}">
                <a href="{{ route('data-tertagih.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-table"></i>
                    <div data-i18n="Analytics">Data Tertagih</div>
                </a>
            </li>
            @endif

            @if (!$isKabkota)
                <li class="menu-item {{ request()->routeIs('verifikasi.index') ? 'active' : '' }}">
                    <a href="{{ route('verifikasi.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-check-shield"></i>  {{-- Ikon Verifikasi --}}
                        <div data-i18n="Analytics">Verifikasi</div>
                    </a>
                </li>
            @endif

            @if ($isKabkota)
                <li class="menu-item {{ request()->routeIs('pelaporan.index') ? 'active' : '' }}">
                    <a href="{{ route('pelaporan.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-file"></i>  {{-- Ikon Pelaporan --}}
                        <div data-i18n="Analytics">Pelaporan</div>
                    </a>
                </li>
            @endif
            
            <li class="menu-item {{ request()->routeIs('pelaporan.index') ? 'active' : '' }}">
                <a href="{{ route('pelaporan.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-file"></i>  {{-- Ikon Pelaporan --}}
                    <div data-i18n="Analytics">Pelaporan</div>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('rekap.index') ? 'active' : '' }}">
                <a href="{{ route('rekap.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>  {{-- Ikon Rekap --}}
                    <div data-i18n="Analytics">Rekap</div>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('perbandingan-kode-wilayah.index') ? 'active' : '' }}">
                <a href="{{ route('perbandingan-kode-wilayah.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-git-compare"></i>
                    <div data-i18n="Analytics">Perbandingan Kode Wilayah</div>
                </a>
            </li>

            
        @else

            @if (!$isWilayahLower)
                @if ($isUptd)
                <li class="menu-item {{ request()->routeIs('perbandingan-kode-wilayah.index') ? 'active' : '' }}">
                    <a href="{{ route('perbandingan-kode-wilayah.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-git-compare"></i>
                        <div data-i18n="Analytics">Perbandingan Kode Wilayah</div>
                    </a>
                </li>
                @endif

                @if ($canSeeDataTertagih)
                <li class="menu-item {{ request()->routeIs('data-tertagih.index') ? 'active' : '' }}">
                    <a href="{{ route('data-tertagih.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-table"></i>
                        <div data-i18n="Analytics">Data Tertagih</div>
                    </a>
                </li>
                @endif

                @if (!$isKabkota)
                    <li class="menu-item {{ request()->routeIs('verifikasi.index') ? 'active' : '' }}">
                        <a href="{{ route('verifikasi.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-check-shield"></i>  {{-- Ikon Verifikasi --}}
                            <div data-i18n="Analytics">Verifikasi</div>
                        </a>
                    </li>
                @endif

                @if ($isKabkota)
                    <li class="menu-item {{ request()->routeIs('pelaporan.index') ? 'active' : '' }}">
                        <a href="{{ route('pelaporan.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-file"></i>  {{-- Ikon Pelaporan --}}
                            <div data-i18n="Analytics">Pelaporan</div>
                        </a>
                    </li>
                @endif
            @endif

            @if ($isKabkota || $isKecamatan || $isKelurahan)
            <li class="menu-item {{ request()->routeIs('user.index') ? 'active' : '' }}">
                <a href="{{ route('user.index') }}" class="menu-link">
                    {{-- <i class="menu-icon tf-icons bx bx-home-circle"></i> --}}
                    <i class="menu-icon tf-icons bx bx-lock-open-alt"></i>
                    <div data-i18n="Analytics">Users</div>
                </a>
            </li>
            @endif
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
