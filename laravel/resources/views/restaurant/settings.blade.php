@extends('layouts.app')

@section('content')
<section class="content">
    <div class="page" style="max-width:1000px;margin:auto;">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <div>
                <h1>Restaurant Settings</h1>
                <p class="text-muted">Kelola daftar restoran dan harga per paket</p>
            </div>
            <a href="{{ route('dashboard.restaurant') }}" class="btn btn-secondary">
                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Konsumsi
            </a>
        </div>

        <!-- FLASH MESSAGES -->
        @if(session('success'))
            <div class="alert alert-success" style="margin-bottom:15px;padding:12px;background:#dcfce7;color:#166534;border-radius:8px;">
                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error" style="margin-bottom:15px;padding:12px;background:#fee2e2;color:#991b1b;border-radius:8px;">
                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- FORM TAMBAH RESTORAN -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">
                <span>Tambah Restoran Baru</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('dashboard.restaurant.settings.store') }}" class="form">
                    @csrf

                    <div class="row-form-2">
                        <div>
                            <label>Nama Restoran</label>
                            <input type="text" name="restaurant_name" required placeholder="Contoh: Up And Atom">
                        </div>
                        <div>
                            <label>Harga per Paket ($)</label>
                            <input type="number" name="price_per_packet" step="0.01" min="0" required placeholder="400">
                        </div>
                    </div>

                    <div class="row-form-2">
                        <div>
                            <label>Pajak (%)</label>
                            <input type="number" name="tax_percentage" step="0.01" min="0" max="100" value="5" required>
                        </div>
                        <div style="display:flex;align-items:flex-end;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" name="is_active" value="1" checked>
                                <span>Aktif</span>
                            </label>
                        </div>
                    </div>

                    <div style="margin-top:10px;">
                        <button type="submit" class="btn btn-success">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tambah Restoran
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- DAFTAR RESTORAN -->
        <div class="card">
            <div class="card-header">
                Daftar Restoran
            </div>

            <div class="table-wrapper">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Restoran</th>
                            <th>Harga/Paket</th>
                            <th>Pajak</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($restaurants as $i => $r)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <strong>{{ htmlspecialchars($r->restaurant_name) }}</strong>
                                </td>
                                <td>
                                    <span style="color:#0369a1;font-weight:600;">
                                        ${{ number_format($r->price_per_packet, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td>{{ number_format($r->tax_percentage, 0) }}%</td>
                                <td>
                                    @if($r->is_active)
                                        <span class="badge-status badge-approved">AKTIF</span>
                                    @else
                                        <span class="badge-status badge-cancelled">NON-AKTIF</span>
                                    @endif
                                </td>
                                <td>
                                    <small style="color:#64748b;">
                                        {{ date('d M Y', strtotime($r->created_at)) }}
                                    </small>
                                </td>
                                <td style="white-space:nowrap;">
                                    <button class="btn-secondary btn-sm"
                                        onclick="editRestaurant({{ $r->id }}, '{{ htmlspecialchars($r->restaurant_name, ENT_QUOTES) }}', {{ $r->price_per_packet }}, {{ $r->tax_percentage }}, {{ $r->is_active }})">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Edit
                                    </button>
                                    @if($r->is_active)
                                        <button class="btn-warning btn-sm"
                                            onclick="toggleStatus({{ $r->id }}, 0)">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                            Nonaktifkan
                                        </button>
                                    @else
                                        <button class="btn-success btn-sm"
                                            onclick="toggleStatus({{ $r->id }}, 1)">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                            </svg>
                                            Aktifkan
                                        </button>
                                    @endif
                                    <button class="btn-danger btn-sm"
                                        onclick="deleteRestaurant({{ $r->id }})">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<!-- =================================================
     MODAL EDIT RESTORAN
     ================================================= -->
<div id="editModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Edit Restoran</h3>

        <form method="POST" action="{{ route('dashboard.restaurant.settings.update') }}" class="form">
            @csrf
            <input type="hidden" name="id" id="editId">

            <label>Nama Restoran</label>
            <input type="text" name="restaurant_name" id="editName" required>

            <div class="row-form-2">
                <div>
                    <label>Harga per Paket ($)</label>
                    <input type="number" name="price_per_packet" id="editPrice" step="0.01" min="0" required>
                </div>
                <div>
                    <label>Pajak (%)</label>
                    <input type="number" name="tax_percentage" id="editTax" step="0.01" min="0" max="100" required>
                </div>
            </div>

            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" name="is_active" id="editActive" value="1">
                <span>Aktif</span>
            </label>

            <div class="modal-actions">
                <button type="button" class="btn-secondary btn-cancel">Batal</button>
                <button type="submit" class="btn-success">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function editRestaurant(id, name, price, tax, active) {
        document.getElementById('editId').value = id;
        document.getElementById('editName').value = name;
        document.getElementById('editPrice').value = price;
        document.getElementById('editTax').value = tax;
        document.getElementById('editActive').checked = active === 1;

        document.getElementById('editModal').style.display = 'flex';
        document.body.classList.add('modal-open');
    }

    function toggleStatus(id, status) {
        const action = status === 1 ? 'aktifkan' : 'nonaktifkan';
        if (!confirm('Yakin ingin ' + action + ' restoran ini?')) return;

        const formData = new FormData();
        formData.append('id', id);
        formData.append('is_active', status);
        formData.append('_token', '{{ csrf_token() }}');

        fetch('{{ route('dashboard.restaurant.settings.toggle') }}', {
            method: 'POST',
            body: formData
        }).then(() => location.reload());
    }

    function deleteRestaurant(id) {
        if (!confirm('Yakin ingin menghapus restoran ini? Data tidak bisa dikembalikan!')) return;

        const formData = new FormData();
        formData.append('id', id);
        formData.append('_token', '{{ csrf_token() }}');

        fetch('{{ route('dashboard.restaurant.settings.delete') }}', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ Restoran berhasil dihapus!');
                location.reload();
            } else {
                alert('❌ ' + (data.message || 'Gagal menghapus restoran'));
            }
        })
        .catch(err => {
            alert('❌ Terjadi kesalahan: ' + err.message);
        });
    }

    // Modal handler
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('editModal');

        document.body.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay') || e.target.closest('.btn-cancel')) {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }
        });
    });
</script>
@endsection
