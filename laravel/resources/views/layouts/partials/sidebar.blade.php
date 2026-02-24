@php
// ======================================================
// VARIABLES DARI CONTROLLER / VIEW
// ======================================================
// $currentPage - string (untuk active state, biasanya diambil dari Route::currentRouteName())
// Jika tidak dikirim dari controller, kita bisa ambil otomatis
$routeName = Route::currentRouteName();

// Fallback data from session
$sName = session('user_rh.name', 'Medic');
$sPos = session('user_rh.position', 'Staff');
$sRole = session('user_rh.role', 'Staff');

$dispName = $medicName ?? $sName;
$dispPos = $medicJabatan ?? ($medicPos ?? $sPos);
$dispRole = $medicRole ?? ($userRole ?? $sRole);

$dispInitials = $avatarInitials ?? initialsFromName($dispName);
$dispColor = $avatarColor ?? avatarColorFromName($dispName);

// ======================================================
// FUNGSI HELPER UNTUK ACTIVE STATE
// ======================================================
if (!function_exists('isActiveSidebar')) {
    function isActiveSidebar($currentRoute, $targetRoute)
    {
        return $currentRoute === $targetRoute ? 'active' : '';
    }
}
@endphp

<aside id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <div class="brand">
            <div class="avatar-logo" style="background: {{ $dispColor }};">
                {{ $dispInitials }}
            </div>
            <div class="brand-text">
                <strong>{{ $dispName }}</strong>
                <span>{{ $dispPos }}</span>
            </div>
        </div>
    </div>

    <nav class="sidebar-menu">
        <div class="menu-title">Umum</div>
        <a href="{{ route('dashboard') }}" class="{{ isActiveSidebar($routeName, 'dashboard') }}">
            <span class="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v4.875H18.75c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                </svg>
            </span>
            <span class="text">Dashboard</span>
        </a>

        <div class="menu-title">Operasional</div>
        <a href="{{ route('dashboard.events') }}" class="{{ isActiveSidebar($routeName, 'dashboard.events') }}">
            <span class="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
            </span>
            <span class="text">Event</span>
        </a>

        <div class="menu-title">Medis</div>
        <a href="{{ route('dashboard.ems_services') }}" class="{{ isActiveSidebar($routeName, 'dashboard.ems_services') }}">
            <span class="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </span>
            <span class="text">Layanan Medis</span>
        </a>

        @if(strtolower(trim($medicJabatan ?? '')) !== 'trainee')
            <a href="{{ route('dashboard.rekap_farmasi') }}" class="{{ isActiveSidebar($routeName, 'dashboard.rekap_farmasi') }}">
                <span class="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                </span>
                <span class="text">Rekap Farmasi</span>
            </a>
            <a href="{{ route('dashboard.rekap_farmasi_v2') }}" class="{{ isActiveSidebar($routeName, 'dashboard.rekap_farmasi_v2') }}">
                <span class="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v2m0-4v-2m0 4h.01M12 2.25h.01M12 2.25h.01M12 2.25h.01M12 2.25h.01M12 2.25h.01M12 2.25h.01M12 2.25h.01M7.5 12h9m-9 0h9m-9 0h9"/>
                    </svg>
                </span>
                <span class="text">Rekap Farmasi V2</span>
            </a>
        @endif

        <div class="menu-title">Keuangan & Operasional</div>
        <a href="{{ route('dashboard.reimbursement') }}" class="{{ isActiveSidebar($routeName, 'dashboard.reimbursement') }}">
            <span class="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </span>
            <span class="text">Reimbursement</span>
        </a>

        <a href="{{ route('dashboard.restaurant') }}" class="{{ isActiveSidebar($routeName, 'dashboard.restaurant') }}">
            <span class="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </span>
            <span class="text">Restaurant Consumption</span>
        </a>

        <a href="{{ route('dashboard.operasi_plastik') }}" class="{{ isActiveSidebar($routeName, 'dashboard.operasi_plastik') }}">
            <span class="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </span>
            <span class="text">Operasi Plastik</span>
        </a>

        <a href="{{ route('dashboard.konsumen') }}" class="{{ isActiveSidebar($routeName, 'dashboard.konsumen') }}">
            <span class="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </span>
            <span class="text">Konsumen</span>
        </a>

        <div class="menu-title">Laporan</div>
        <a href="{{ route('dashboard.ranking') }}" class="{{ isActiveSidebar($routeName, 'dashboard.ranking') }}">
            <span class="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </span>
            <span class="text">Ranking</span>
        </a>

        <div class="menu-title">Tools</div>
        <a href="{{ route('dashboard.identity_test') }}" class="{{ isActiveSidebar($routeName, 'dashboard.identity_test') }}">
            <span class="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z"/>
                </svg>
            </span>
            <span class="text">Identity Test</span>
        </a>

        <a href="{{ route('dashboard.absensi_ems') }}" class="{{ isActiveSidebar($routeName, 'dashboard.absensi_ems') }}">
            <span class="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <span class="text">Jumlah Jam Web</span>
        </a>

        <div class="menu-title">Penggajian</div>
        <a href="{{ route('dashboard.salary') }}" class="{{ isActiveSidebar($routeName, 'dashboard.salary') }}">
            <span class="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <span class="text">Gajian</span>
        </a>

        <!-- ============================= -->
        <!-- VALIDASI (MANAGER ONLY) -->
        <!-- ============================= -->
        @if(strtolower($dispRole ?? '') !== 'staff')
            <div class="menu-title">Manager</div>
            <a href="{{ route('dashboard.events.manage') }}" class="{{ isActiveSidebar($routeName, 'dashboard.events.manage') }}">
                <span class="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.967 0 016 18c1.097 0 2.099.196 3 .55a8.967 8.967 0 016-2.508 8.967 8.967 0 016 2.508c.901-.354 1.903-.55 3-.55a8.987 8.967 0 013 1.05V4.262c-.938-.332-1.948-.512-3-.512a8.967 8.967 0 00-6 2.292m0-2.292a8.967 8.967 0 010 18.045m0-18.045q-.457 0-1.045.51m1.045-.51q.457 0 1.045.51"/>
                    </svg>
                </span>
                <span class="text">Manajemen Event</span>
            </a>
            <a href="{{ route('dashboard.validasi') }}" class="{{ isActiveSidebar($routeName, 'dashboard.validasi') }}">
                <span class="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </span>
                <span class="text">Validasi</span>
            </a>
            <a href="{{ route('dashboard.regulasi') }}" class="{{ isActiveSidebar($routeName, 'dashboard.regulasi') }}">
                <span class="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
                <span class="text">Regulasi</span>
            </a>
        @endif

        @if(strtolower($dispRole ?? '') !== 'staff')
            <a href="{{ route('dashboard.manage_users') }}" class="{{ isActiveSidebar($routeName, 'dashboard.manage_users') }}">
                <span class="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </span>
                <span class="text">Manajemen User</span>
            </a>
        @endif

        <div class="menu-title">Pengaturan</div>
        <a href="{{ route('settings.akun') }}" class="{{ isActiveSidebar($routeName, 'settings.akun') }}">
            <span class="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </span>
            <span class="text">Setting Akun</span>
        </a>

        @if(strtolower($dispRole ?? '') !== 'staff')
            <a href="{{ route('settings.spreadsheet') }}" class="{{ isActiveSidebar($routeName, 'settings.spreadsheet') }}">
                <span class="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125h-7.5a1.125 1.125 0 01-1.125-1.125m0 0h4.5m-4.5 0a1.125 1.125 0 01-1.125-1.125V5.625m0 9.75c0 .621-.504 1.125-1.125 1.125m0 0h-4.5m4.5 0v-9.75m0 0a1.125 1.125 0 011.125-1.125h1.5a1.125 1.125 0 011.125 1.125m-4.5 0a1.125 1.125 0 00-1.125-1.125h-1.5a1.125 1.125 0 00-1.125 1.125m4.5 0v-9.75m0 0h-4.5"/>
                    </svg>
                </span>
                <span class="text">Setting Spreadsheet</span>
            </a>
        @endif

        @if(strtolower($medicRole ?? '') !== 'staff')
            <div class="menu-title">Rekrutmen</div>
            <a href="{{ route('dashboard.candidates') }}" class="{{ isActiveSidebar($routeName, 'dashboard.candidates') }}">
                <span class="icon">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </span>
                <span class="text">Calon Kandidat</span>
            </a>
        @endif

        <!-- LOGOUT -->
        <a href="{{ route('logout') }}"
            onclick="
                if (confirm('Yakin ingin logout?')) {
                    sessionStorage.removeItem('farmasi_activity_closed');
                    return true;
                }
                return false;
            "
            class="logout">
            <span class="icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </span>
            <span class="text">Logout</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        EMS © {{ date('Y') }}
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<main class="main-content">
