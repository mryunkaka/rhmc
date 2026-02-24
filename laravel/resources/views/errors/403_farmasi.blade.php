@extends('layouts.app')

@section('content')
<div class="card" style="max-width:600px;margin:80px auto;text-align:center;">
    <h3 style="margin-bottom:10px;">🚫 Akses Ditolak</h3>
    <p style="color:#6b7280;font-size:14px;">
        Akun dengan posisi <strong>Trainee</strong>
        tidak diperbolehkan mengakses
        <strong>Rekap Farmasi</strong>.
    </p>
    <a href="{{ route('dashboard') }}" class="btn-secondary" style="margin-top:12px;">
        Kembali ke Dashboard
    </a>
</div>
@endsection
