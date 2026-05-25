<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="{{ asset('LOGO_SENGKUYUNG/logo2.jpeg') }}" width="200px">
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
            $isSuperAdmin = $user->hasAnyRole(['super-admin', 'superadmin'], 'web')
                || in_array($roleName, ['super-admin', 'superadmin'], true);
            $isAdminProv = $user->hasAnyRole(['admin', 'adminprov'], 'web')
                || in_array($roleName, ['admin', 'adminprov'], true);
            $isUppd = $user->hasRole('uppd');
            $isUptd = $user->hasRole('uptd') || $isUppd;
            $isKabkota = $user->hasRole('kabkota');
            $isKecamatan = $user->hasRole('kecamatan');
            $isKelurahan = $user->hasRole('kelurahan');
            $isWilayahLower = $isKecamatan || $isKelurahan;
            $isAdminScope = $isSuperAdmin || $isAdminProv;
            $canSeeDataTertagih = $isAdminScope || ($isUptd && !$isUppd);
            $canSeeVerifikasiD2d = $isAdminScope || $isUptd;
            $canSeePelaporan = $isAdminScope || $isKabkota || $isUppd || $isKecamatan;
            $canSeeRekap = $isAdminScope || ($isUptd && !$isUppd);
            $canSeeRekapPelaporanD2d = $isAdminScope || $isUppd;
        @endphp

        <!-- Dashboard -->
        <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
            </a>
        </li>

        @if ($isAdminScope)
            <li class="menu-item {{ request()->routeIs('user.index') ? 'active' : '' }}">
                <a href="{{ route('user.index') }}" class="menu-link">
                    {{-- <i class="menu-icon tf-icons bx bx-home-circle"></i> --}}
                    <i class="menu-icon tf-icons bx bx-lock-open-alt"></i>
                    <div data-i18n="Analytics">Users</div>
                </a>
            </li>

            @if ($isSuperAdmin)
            <li class="menu-item {{ request()->routeIs('cache-management.*') ? 'active open' : '' }}">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons bx bx-data"></i>
                    <div data-i18n="Analytics">Kelola Cache</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item {{ request()->routeIs('cache-management.scope') && request()->route('scope') === 'admin' ? 'active' : '' }}">
                        <a href="{{ route('cache-management.scope', ['scope' => 'admin']) }}" class="menu-link">
                            <div data-i18n="Analytics">Cache Admin</div>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('cache-management.scope') && request()->route('scope') === 'api' ? 'active' : '' }}">
                        <a href="{{ route('cache-management.scope', ['scope' => 'api']) }}" class="menu-link">
                            <div data-i18n="Analytics">Cache API</div>
                        </a>
                    </li>
                </ul>
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
            <li class="menu-item {{ request()->routeIs('data-tertagih-d2d.index') ? 'active' : '' }}">
                <a href="{{ route('data-tertagih-d2d.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-grid-alt"></i>
                    <div data-i18n="Analytics">Data Tertagih D2D</div>
                </a>
                 {{-- <a href="javascript:void(0);" class="menu-link" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                            <i class="menu-icon tf-icons bx bx-time-five"></i>
                            <div data-i18n="Analytics">Data Tertagih D2D</div>
                    </a> --}}
            </li>

            @endif

            <li class="menu-item {{ request()->routeIs('verifikasi.index') ? 'active' : '' }}">
                <a href="{{ route('verifikasi.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-check-shield"></i>
                    <div data-i18n="Analytics">Verifikasi</div>
                </a>
            </li>
            @if ($canSeeVerifikasiD2d)
            <li class="menu-item {{ request()->routeIs('verifikasi-d2d.index') ? 'active' : '' }}">
                <a href="{{ route('verifikasi-d2d.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-check-shield"></i>
                    <div data-i18n="Analytics">Verifikasi D2D</div>
                </a>

                {{-- <a href="javascript:void(0);" class="menu-link" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                            <i class="menu-icon tf-icons bx bx-time-five"></i>
                            <div data-i18n="Analytics">Verifikasi D2D</div>
                    </a> --}}
            </li>
            @endif

            @if ($canSeePelaporan)
                <li class="menu-item {{ request()->routeIs('pelaporan.index') ? 'active' : '' }}">
                    <a href="{{ route('pelaporan.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-file"></i>
                        <div data-i18n="Analytics">Pelaporan</div>
                    </a>
                </li>
            @endif

            @if ($canSeeRekap)
                <li class="menu-item {{ request()->routeIs('rekap.index') ? 'active' : '' }}">
                    <a href="{{ route('rekap.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                        <div data-i18n="Analytics">Rekap</div>
                    </a>
                </li>
            @endif

            @if ($canSeeRekapPelaporanD2d)
                <li class="menu-item {{ request()->routeIs('pelaporan-d2d.index') ? 'active' : '' }}">
                    <a href="{{ route('pelaporan-d2d.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-file"></i>
                        <div data-i18n="Analytics">Pelaporan D2D</div>
                    </a>

                    {{-- <a href="javascript:void(0);" class="menu-link" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                            <i class="menu-icon tf-icons bx bx-time-five"></i>
                            <div data-i18n="Analytics">Pelaporan D2D</div>
                        </a> --}}
                </li>
                <li class="menu-item {{ request()->routeIs('rekap-d2d.index') ? 'active' : '' }}">
                    <a href="{{ route('rekap-d2d.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                        <div data-i18n="Analytics">Rekap D2D</div>
                    </a>

                    {{-- <a href="javascript:void(0);" class="menu-link" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                            <i class="menu-icon tf-icons bx bx-time-five"></i>
                            <div data-i18n="Analytics">Rekap D2D</div>
                        </a> --}}
                </li>
            @endif

            <li class="menu-item {{ request()->routeIs('perbandingan-kode-wilayah.index') ? 'active' : '' }}" hidden> ? 'active' : '' }}" hidden>
                <a href="{{ route('perbandingan-kode-wilayah.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-git-compare"></i>
                    <div data-i18n="Analytics">Perbandingan Kode Wilayah</div>
                </a>
            </li>

            
        @else

            @if (!$isWilayahLower)
                @if ($isUptd)
                <li class="menu-item {{ request()->routeIs('perbandingan-kode-wilayah.index') ? 'active' : '' }}" hidden>
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
                <li class="menu-item {{ request()->routeIs('data-tertagih-d2d.index') ? 'active' : '' }}">
                    <a href="{{ route('data-tertagih-d2d.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-grid-alt"></i>
                        <div data-i18n="Analytics">Data Tertagih D2D</div>
                    </a>
                    {{-- <a href="javascript:void(0);" class="menu-link" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                            <i class="menu-icon tf-icons bx bx-time-five"></i>
                            <div data-i18n="Analytics">Data Tertagih D2D</div>
                    </a> --}}
                </li>

                @endif

                <li class="menu-item {{ request()->routeIs('verifikasi.index') ? 'active' : '' }}">
                    <a href="{{ route('verifikasi.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-check-shield"></i>
                        <div data-i18n="Analytics">Verifikasi</div>
                    </a>
                </li>
                @if ($canSeeVerifikasiD2d)
                <li class="menu-item {{ request()->routeIs('verifikasi-d2d.index') ? 'active' : '' }}">
                    <a href="{{ route('verifikasi-d2d.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-check-shield"></i>
                        <div data-i18n="Analytics">Verifikasi D2D</div>
                    </a>
                    {{-- <a href="javascript:void(0);" class="menu-link" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                            <i class="menu-icon tf-icons bx bx-time-five"></i>
                            <div data-i18n="Analytics">Verifikasi D2D</div>
                    </a> --}}
                </li>
                @endif

                @if ($canSeePelaporan)
                    <li class="menu-item {{ request()->routeIs('pelaporan.index') ? 'active' : '' }}">
                        <a href="{{ route('pelaporan.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-file"></i>
                            <div data-i18n="Analytics">Pelaporan</div>
                        </a>
                    </li>
                @endif

                @if ($canSeeRekap)
                    <li class="menu-item {{ request()->routeIs('rekap.index') ? 'active' : '' }}">
                        <a href="{{ route('rekap.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                            <div data-i18n="Analytics">Rekap</div>
                        </a>
                    </li>
                @endif

                @if ($canSeeRekapPelaporanD2d)
                    <li class="menu-item {{ request()->routeIs('pelaporan-d2d.index') ? 'active' : '' }}">
                        <a href="{{ route('pelaporan-d2d.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-file"></i>
                            <div data-i18n="Analytics">Pelaporan D2D</div>
                        </a>
                        {{-- <a href="javascript:void(0);" class="menu-link" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                            <i class="menu-icon tf-icons bx bx-time-five"></i>
                            <div data-i18n="Analytics">Pelaporan D2D</div>
                        </a> --}}
                    </li>
                    <li class="menu-item {{ request()->routeIs('rekap-d2d.index') ? 'active' : '' }}">
                        <a href="{{ route('rekap-d2d.index') }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                            <div data-i18n="Analytics">Rekap D2D</div>
                        </a>
                        {{-- <a href="javascript:void(0);" class="menu-link" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                            <i class="menu-icon tf-icons bx bx-time-five"></i>
                            <div data-i18n="Analytics">Rekap D2D</div>
                        </a> --}}
                    </li>
                @endif
            @endif

            @if ($isKecamatan && $canSeePelaporan)
                <li class="menu-item {{ request()->routeIs('pelaporan.index') ? 'active' : '' }}">
                    <a href="{{ route('pelaporan.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-file"></i>
                        <div data-i18n="Analytics">Pelaporan</div>
                    </a>
                </li>
            @endif

            @if ($isUptd || $isKabkota || $isKecamatan || $isKelurahan)
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

        {{-- <li class="menu-item mt-auto">
            <a href="javascript:void(0);" class="menu-link" data-bs-toggle="modal" data-bs-target="#comingSoonModal">
                <i class="menu-icon tf-icons bx bx-time-five"></i>
                <div data-i18n="Analytics">Fitur Baru</div>
            </a>
        </li> --}}

    </ul>
</aside>
<!-- / Menu -->
