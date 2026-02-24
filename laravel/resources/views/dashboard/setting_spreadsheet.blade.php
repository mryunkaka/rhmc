@extends('layouts.app')

@section('content')
<section class="content">
    <div class="page" style="max-width:1200px;margin:auto;">
        <h1>Setting Spreadsheet</h1>

        <p style="font-size:13px;color:#9ca3af;">
            Digunakan untuk import data Google Sheets (CSV).
        </p>

        @if(session('flash_messages'))
            @foreach(session('flash_messages') as $m)
                <div class="alert alert-success">{{ $m }}</div>
            @endforeach
        @endif

        @if(session('flash_errors'))
            @foreach(session('flash_errors') as $e)
                <div class="alert alert-danger">{{ $e }}</div>
            @endforeach
        @endif

        <div class="card">
            <div class="card-header">Konfigurasi Google Spreadsheet</div>

            <form method="post" action="{{ route('settings.spreadsheet.action') }}">
                @csrf
                <div class="form-group">
                    <label>Spreadsheet ID</label>
                    <input type="text"
                        name="spreadsheet_id"
                        class="form-control"
                        value="{{ $sheetConfig['spreadsheet_id'] }}"
                        placeholder="contoh: 1300EqaCtHs8PrHKepzEQRk-ALwtfh1FcBAeaW95XKWU">
                </div>

                <div class="form-group">
                    <label>Sheet GID</label>
                    <input type="text"
                        name="sheet_gid"
                        class="form-control"
                        value="{{ $sheetConfig['sheet_gid'] }}"
                        placeholder="contoh: 1891016011">
                </div>

                <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="submit" class="btn-primary">
                        Simpan Konfigurasi
                    </button>
                </div>
            </form>

            @if ($currentCsvUrl)
                <hr>
                <div style="margin-top:16px;">
                    <small>CSV Aktif:</small><br>
                    <code style="word-break: break-all;">{{ $currentCsvUrl }}</code><br><br>
                    <div style="display:flex;gap:10px;">
                        <a href="{{ $sheetEditUrl }}" target="_blank" class="btn-secondary" style="text-decoration:none;">
                            Buka Spreadsheet
                        </a>
                        <form action="{{ route('settings.sync_sheet') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn-success">
                                🔄 Sync Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
