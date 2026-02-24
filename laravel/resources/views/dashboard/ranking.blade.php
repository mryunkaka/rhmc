@extends('layouts.app')

@section('content')

<section class="content">
    <div class="page" style="max-width:1200px;margin:auto;">
        <h1>Ranking Medis</h1>

        <p style="font-size:13px;color:#9ca3af;">
            {{ $rangeLabel }}
        </p>

        <div class="card">
            <div class="card-header">
                Ranking Medis Berdasarkan Total Harga
            </div>

            <form method="GET" id="filterForm" class="filter-bar">
                <div class="filter-group">
                    <select name="range" id="rangeSelect" class="form-control">
                        <option value="current_week" {{ $range === 'current_week' ? 'selected' : '' }}>
                            Minggu Ini
                        </option>
                        <option value="last_week" {{ $range === 'last_week' ? 'selected' : '' }}>
                            Minggu Sebelumnya
                        </option>
                        <option value="custom" {{ $range === 'custom' ? 'selected' : '' }}>
                            Custom
                        </option>
                    </select>
                </div>

                <div class="filter-group filter-custom">
                    <input type="date" name="start" value="{{ $start }}" class="form-control">
                </div>

                <div class="filter-group filter-custom">
                    <input type="date" name="end" value="{{ $end }}" class="form-control">
                </div>

                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </div>
            </form>

            <div class="table-wrapper">
                <table id="rankingTable" class="table-custom">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Medis</th>
                            <th>Jabatan</th>
                            <th>Total Transaksi</th>
                            <th>Total Item</th>
                            <th>Total Harga</th>
                            <th>Bonus (40%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($medicRanking as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $row->medic_name }}</td>
                                <td>{{ $row->medic_jabatan }}</td>
                                <td>{{ (int)$row->total_transaksi }}</td>
                                <td>{{ (int)$row->total_item }}</td>
                                <td>{{ dollar($row->total_rupiah) }}</td>
                                <td>{{ dollar(floor($row->total_rupiah * 0.4)) }}</td>
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
            jQuery('#rankingTable').DataTable({
                order: [
                    [5, 'desc']
                ],
                pageLength: 10,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const rangeSelect = document.getElementById('rangeSelect');
        const customFields = document.querySelectorAll('.filter-custom');

        function toggleCustom() {
            if (rangeSelect.value === 'custom') {
                customFields.forEach(el => el.style.display = 'block');
            } else {
                customFields.forEach(el => el.style.display = 'none');
            }
        }

        rangeSelect.addEventListener('change', toggleCustom);
        toggleCustom(); // initial load
    });
</script>

@endsection
