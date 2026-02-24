@extends('layouts.app')

@section('styles')
<style>
    .radio-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px
    }

    .radio-group label {
        background: #f1f5f9;
        padding: 6px 10px;
        border-radius: 6px;
        cursor: pointer
    }
</style>
@endsection

@section('content')
<section class="content">
    <div class="page" style="max-width:1200px;margin:auto;">

        <h1 class="gradient-text">Peserta Event</h1>
        <p class="text-muted">{{ $event->nama_event }}</p>

        @if(session('flash_messages'))
            @foreach(session('flash_messages') as $m)
                <div class="alert alert-info">{{ $m }}</div>
            @endforeach
        @endif

        @if(session('flash_errors'))
            @foreach(session('flash_errors') as $e)
                <div class="alert alert-error">{{ $e }}</div>
            @endforeach
        @endif

        <div class="card">
            <div class="card-header">
                Daftar Peserta Pendaftaran
                <span style="float:right;font-weight:normal;">
                    Total: <strong>{{ count($participants) }}</strong> orang
                </span>
            </div>

            <div class="table-wrapper">
                <table class="table-custom datatable-peserta">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Batch</th>
                            <th>Jenis Kelamin</th>
                            <th>Citizen ID</th>
                            <th>No HP IC</th>
                            <th>Waktu Daftar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($participants as $i => $p)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td><strong>{{ $p->full_name }}</strong></td>
                                <td>{{ $p->position }}</td>
                                <td>{{ $p->batch }}</td>
                                <td>{{ $p->jenis_kelamin }}</td>
                                <td>{{ $p->citizen_id }}</td>
                                <td>{{ $p->no_hp_ic }}</td>
                                <td>{{ \Carbon\Carbon::parse($p->registered_at)->format('d M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Generate Kelompok</div>
            <div class="card-body">
                <div class="alert alert-info">
                    Pastikan daftar peserta sudah final sebelum generate kelompok.
                </div>
                <form method="POST" action="{{ route('dashboard.events.generate_group') }}">
                    @csrf
                    <input type="hidden" name="event_id" value="{{ $eventId }}">

                    <label>Jumlah Orang / Kelompok</label>
                    <input type="number" name="group_size" min="2" required>

                    <label>Laki-laki / Kelompok</label>
                    <div class="radio-group">
                        @for($i = 0; $i <= 10; $i++)
                            <label><input type="radio" name="male_count" value="{{ $i }}" {{ $i === 0 ? 'checked' : '' }}> {{ $i }}</label>
                        @endfor
                    </div>

                    <label>Perempuan / Kelompok</label>
                    <div class="radio-group">
                        @for($i = 0; $i <= 10; $i++)
                            <label><input type="radio" name="female_count" value="{{ $i }}" {{ $i === 0 ? 'checked' : '' }}> {{ $i }}</label>
                        @endfor
                    </div>

                    <button type="submit" name="generate_group" class="btn-success" style="margin-top:12px;">
                        🎲 Generate Kelompok
                    </button>

                </form>
            </div>
        </div>

        @if(!empty($dbGroups))
            <div style="margin:20px 0; display:flex; justify-content:flex-end;">
                <div style="background:#f8fafc; padding:8px 12px; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    <button id="btnExportGroupText"
                        class="btn-secondary"
                        style="display:flex;align-items:center;gap:6px;">
                        📄 Export Kelompok
                    </button>
                </div>
            </div>

            @foreach($dbGroups as $groupName => $members)
                <div class="card group-card" style="margin-top:20px;">
                    <div class="card-header group-header">{{ $groupName }}</div>
                    <div class="table-wrapper">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Jabatan</th>
                                    <th>Gender</th>
                                    <th>Batch</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($members as $i => $m)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td class="member-name"><strong>{{ $m->full_name }}</strong></td>
                                        <td>{{ $m->position }}</td>
                                        <td class="member-gender">{{ $m->jenis_kelamin }}</td>
                                        <td>{{ $m->batch }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        @endif

        <a href="{{ route('dashboard.events.manage') }}" class="btn-secondary" style="margin-top:16px;">⬅ Kembali</a>

    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.jQuery && $.fn.DataTable) {
            $('.datatable-peserta').DataTable({
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                ordering: true,
                searching: true,
                info: true,
                autoWidth: false,
                scrollX: true,
                order: [[7, 'desc']],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        }

        const btn = document.getElementById('btnExportGroupText');
        if (btn) {
            btn.addEventListener('click', function() {
                let output = '';
                const eventNameRaw = {!! json_encode($event->nama_event) !!};

                const safeEventName = eventNameRaw
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/gi, '_')
                    .replace(/^_|_$/g, '');

                const now = new Date();
                const pad = n => String(n).padStart(2, '0');
                const timestamp = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + '_' + pad(now.getHours()) + '-' + pad(now.getMinutes()) + '-' + pad(now.getSeconds());
                const filename = `kelompok_${safeEventName}_${timestamp}.txt`;

                output += `EVENT: ${eventNameRaw}
`;
                output += '==============================

';

                document.querySelectorAll('.group-card').forEach(card => {
                    const header = card.querySelector('.group-header');
                    const table = card.querySelector('table');
                    if (!header || !table) return;

                    const groupName = header.innerText.trim();
                    let rows = Array.from(table.querySelectorAll('tbody tr'));
                    if (!rows.length) return;

                    // Re-sort: Female first
                    const female = [];
                    const other = [];
                    rows.forEach(row => {
                        const gender = row.querySelector('.member-gender')?.innerText || '';
                        if (gender.toLowerCase() === 'perempuan') {
                            female.push(row);
                        } else {
                            other.push(row);
                        }
                    });
                    rows = [...female, ...other];

                    output += groupName.toUpperCase() + '
';
                    let no = 1;
                    rows.forEach(row => {
                        const name = row.querySelector('.member-name strong')?.innerText || '';
                        if (name) {
                            output += `${no}. ${name}
`;
                            no++;
                        }
                    });
                    output += '
';
                });

                if (!output.trim()) {
                    alert('Tidak ada data kelompok untuk diexport.');
                    return;
                }

                const blob = new Blob([output], { type: 'text/plain;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });
        }
    });
</script>
@endpush
