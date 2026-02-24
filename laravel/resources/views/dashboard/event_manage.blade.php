@extends('layouts.app')

@section('content')
<section class="content">
    <div class="page" style="max-width:1100px;margin:auto;">

        <h1 class="gradient-text">Manajemen Event</h1>
        <p class="text-muted">Tambah, edit, dan kelola event</p>

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
            <div class="card-header" style="display:flex;justify-content:space-between;">
                <span>Daftar Event</span>
                <button class="btn-success" id="btnAddEvent">➕ Tambah Event</button>
            </div>

            <div class="table-wrapper">
                <table class="table-custom datatable-event">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Event</th>
                            <th>Tanggal</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                            <th>Peserta</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($events as $i => $e)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <a href="{{ route('dashboard.events.participants', ['event_id' => $e->id]) }}"
                                        style="font-weight:bold;color:#2563eb;text-decoration:none;">
                                        {{ $e->nama_event }}
                                    </a>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($e->tanggal_event)->isoFormat('dddd, D MMM YYYY') }}</td>
                                <td>{{ $e->lokasi ?? '-' }}</td>
                                <td>
                                    @if($e->is_active)
                                        <span class="badge-success">Aktif</span>
                                    @else
                                        <span class="badge-muted">Nonaktif</span>
                                    @endif
                                </td>
                                <td>{{ (int)$e->total_peserta }}</td>
                                <td>
                                    <div style="display:flex;gap:6px;">
                                        <button class="btn-secondary btn-edit"
                                            data-id="{{ $e->id }}"
                                            data-nama="{{ $e->nama_event }}"
                                            data-tanggal="{{ $e->tanggal_event }}"
                                            data-lokasi="{{ $e->lokasi }}"
                                            data-ket="{{ $e->keterangan }}"
                                            data-active="{{ $e->is_active }}">
                                            Edit
                                        </button>

                                        {{-- Delete functionality can be added here if needed, legacy had a button but no explicit delete action in event_action.php --}}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<div id="eventModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3 id="modalTitle">Tambah Event</h3>

        <form method="POST" action="{{ route('dashboard.events.action') }}" class="form">
            @csrf
            <input type="hidden" name="event_id" id="eventId">

            <label>Nama Event</label>
            <input type="text" name="nama_event" id="eventNama" required>

            <label>Tanggal Event</label>
            <input type="date" name="tanggal_event" id="eventTanggal" required>

            <label>Lokasi</label>
            <input type="text" name="lokasi" id="eventLokasi">

            <label>Keterangan</label>
            <textarea name="keterangan" id="eventKet"></textarea>

            <label>
                <input type="checkbox" name="is_active" id="eventActive" value="1" checked>
                Event Aktif
            </label>

            <div class="modal-actions">
                <button type="button" class="btn-secondary btn-cancel">Batal</button>
                <button type="submit" class="btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('eventModal');

        document.getElementById('btnAddEvent').onclick = () => {
            modal.querySelector('form').reset();
            document.getElementById('eventId').value = '';
            document.getElementById('modalTitle').innerText = 'Tambah Event';
            modal.style.display = 'flex';
        };

        document.body.addEventListener('click', function(e) {
            if (e.target.closest('.btn-edit')) {
                const b = e.target.closest('.btn-edit');
                document.getElementById('modalTitle').innerText = 'Edit Event';
                document.getElementById('eventId').value = b.dataset.id;
                document.getElementById('eventNama').value = b.dataset.nama;
                document.getElementById('eventTanggal').value = b.dataset.tanggal;
                document.getElementById('eventLokasi').value = b.dataset.lokasi;
                document.getElementById('eventKet').value = b.dataset.ket;
                document.getElementById('eventActive').checked = b.dataset.active == 1;
                modal.style.display = 'flex';
            }

            if (e.target.closest('.btn-cancel') || e.target === modal) {
                modal.style.display = 'none';
            }
        });

        if (window.jQuery && jQuery.fn.DataTable) {
            jQuery('.datatable-event').DataTable({
                pageLength: 10,
                order: [[2, 'desc']],
                scrollX: true,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        }
    });
</script>
@endpush
@endsection
