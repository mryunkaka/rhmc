@extends('layouts.app')

@section('content')

<section class="content">
    <div class="page page-absensi-ems" style="max-width:1200px;margin:auto;">

        <h1 class="gradient-text">Jam Kerja EMS</h1>
        <p class="text-muted">
            {{ $rangeLabel }}
        </p>

        <!-- SUMMARY CARDS -->
        <div class="ems-summary-grid">
            <div class="stat-box">
                <small>Total Jam Kerja Minggu Ini</small>
                <h3>{{ $totalWeek }}</h3>
            </div>

            <div class="stat-box highlight">
                <small>Akumulasi Jam Pengguna Web</small>
                <h3>{{ $totalAll }}</h3>
            </div>
        </div>

        <!-- SESI HARI INI -->
        <div class="card">
            <div class="card-header" style="justify-content:space-between;">
                <span>⏱️ Sesi Hari Ini</span>
                <span class="weekly-badge">
                    {{ count($todaySessions) }} Sesi
                </span>
            </div>

            <div class="card-body">
                @if ($todaySessions->isEmpty())
                    <p style="font-size:13px;color:#64748b;padding:20px;text-align:center;">
                        Belum ada sesi hari ini.
                    </p>
                @else
                    <div class="table-wrapper-sm">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Mulai</th>
                                    <th>Selesai</th>
                                    <th>Durasi</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($todaySessions as $i => $s)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ \Carbon\Carbon::parse($s->session_start)->format('H:i:s') }}</td>
                                        <td>
                                            @if ($s->session_end)
                                                {{ \Carbon\Carbon::parse($s->session_end)->format('H:i:s') }}
                                            @else
                                                <span class="status-badge status-online"><span class="dot"></span> Aktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if (!$s->session_end)
                                                <span class="realtime-duration" data-start-timestamp="{{ $s->start_timestamp }}">
                                                    00:00:00
                                                </span>
                                            @else
                                                @php
                                                    $h = floor($s->duration_seconds / 3600);
                                                    $m = floor(($s->duration_seconds % 3600) / 60);
                                                    $sec = $s->duration_seconds % 60;
                                                    echo sprintf('%02d:%02d:%02d', $h, $m, $sec);
                                                @endphp
                                            @endif
                                        </td>
                                        <td>
                                            @if (!$s->session_end)
                                                <span class="status-badge status-online">
                                                    <span class="dot"></span> ONLINE
                                                </span>
                                            @else
                                                <span class="status-badge status-offline">
                                                    OFFLINE
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="card">
            <div class="card-body">
                <form method="GET" class="filter-bar" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                    <div class="filter-group">
                        <select name="range" id="rangeSelect" class="input-custom">
                            <option value="current_week" {{ $rangeType === 'current_week' ? 'selected' : '' }}>Minggu Ini</option>
                            <option value="last_week" {{ $rangeType === 'last_week' ? 'selected' : '' }}>Minggu Sebelumnya</option>
                            <option value="custom" {{ $rangeType === 'custom' ? 'selected' : '' }}>Custom</option>
                        </select>
                    </div>

                    <div class="filter-group filter-custom" style="{{ $rangeType === 'custom' ? '' : 'display:none;' }}">
                        <input type="date" name="start" value="{{ request('start') }}" class="input-custom">
                    </div>

                    <div class="filter-group filter-custom" style="{{ $rangeType === 'custom' ? '' : 'display:none;' }}">
                        <input type="date" name="end" value="{{ request('end') }}" class="input-custom">
                    </div>

                    <div class="filter-group">
                        <button class="btn-primary">Terapkan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- LEADERBOARD -->
        <div class="card">
            <div class="card-header">
                🏆 Leaderboard Pengguna Web Farmasi & Layanan Medis Mingguan
            </div>

            <div class="table-wrapper">
                <table id="leaderboardTable" class="table-custom">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Medis</th>
                            <th>Jabatan</th>
                            <th>Total Sesi</th>
                            <th>Total Jam Online</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leaderboard as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $row->medic_name }}</td>
                                <td>{{ $row->medic_jabatan }}</td>
                                <td>{{ $row->total_sesi }}</td>
                                <td>
                                    @php
                                        $h = floor($row->total_seconds / 3600);
                                        $m = floor(($row->total_seconds % 3600) / 60);
                                        $sec = $row->total_seconds % 60;
                                        echo sprintf('%02d:%02d:%02d', $h, $m, $sec);
                                    @endphp
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.jQuery && jQuery.fn.DataTable) {
            jQuery('#leaderboardTable').DataTable({
                order: [[4, 'desc']],
                pageLength: 10,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        }

        const rangeSelect = document.getElementById('rangeSelect');
        const customFields = document.querySelectorAll('.filter-custom');

        function toggleCustom() {
            customFields.forEach(el => {
                el.style.display = (rangeSelect.value === 'custom') ? 'block' : 'none';
            });
        }

        rangeSelect.addEventListener('change', toggleCustom);

        // Realtime Duration
        function updateDuration() {
            const now = Math.floor(Date.now() / 1000);
            document.querySelectorAll('.realtime-duration').forEach(el => {
                const start = parseInt(el.dataset.startTimestamp);
                const diff = now - start;
                if (diff < 0) return;
                
                const h = Math.floor(diff / 3600);
                const m = Math.floor((diff % 3600) / 60);
                const s = diff % 60;
                el.innerText = [h, m, s].map(v => v.toString().padStart(2, '0')).join(':');
            });
        }
        setInterval(updateDuration, 1000);
        updateDuration();
    });
</script>

@endsection
