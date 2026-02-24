@extends('layouts.app')

@section('content')
<section class="content">
    <div class="page" style="max-width:1200px;margin:auto;">
        <h1>Validasi User</h1>

        <p style="font-size:13px;color:#9ca3af;">
            Halaman ini digunakan untuk memverifikasi akun user baru
        </p>

        @if(session('success'))
            <div class="alert alert-success" style="margin-bottom:15px;padding:12px;background:#dcfce7;color:#166534;border-radius:8px;">
                <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                Daftar User Terdaftar
            </div>

            <div class="table-wrapper">
                <table id="validasiTable" class="table-custom">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Role</th>
                            <th>Jabatan</th>
                            <th>Status</th>
                            <th>Tanggal Daftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $i => $u)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ htmlspecialchars($u->full_name) }}</td>
                                <td>{{ htmlspecialchars($u->role) }}</td>
                                <td>{{ htmlspecialchars($u->position) }}</td>

                                <td>
                                    @if((int)$u->is_verified === 1)
                                        <div class="status-box verified">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Terverifikasi
                                        </div>
                                    @else
                                        <div class="status-box pending">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Belum Verifikasi
                                        </div>
                                    @endif
                                </td>

                                <td>{{ date('d-m-Y H:i', strtotime($u->created_at)) }}</td>

                                <td>
                                    @if((int)$u->is_verified === 0)
                                        <a href="{{ route('dashboard.validasi.approve', ['id' => $u->id]) }}"
                                            class="btn btn-sm btn-success"
                                            onclick="return confirm('Verifikasi user ini?')">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            Validasi
                                        </a>
                                    @else
                                        <a href="{{ route('dashboard.validasi.reject', ['id' => $u->id]) }}"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Batalkan verifikasi user ini?')">
                                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Batalkan
                                        </a>
                                    @endif
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
            jQuery('#validasiTable').DataTable({
                pageLength: 10,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        }
    });
</script>
@endsection
