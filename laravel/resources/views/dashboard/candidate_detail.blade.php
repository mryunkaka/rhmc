@extends('layouts.app')

@section('content')
<section class="content">
    <div class="page" style="max-width:1100px;margin:auto;">

        <h1 class="gradient-text">Detail Kandidat</h1>

        <div class="card">
            <strong>{{ $candidate['ic_name'] }}</strong>
            <div style="color:#64748b;font-size:13px;">
                Status: {{ $candidate['status'] }} |
                Skor: {{ $result['score_total'] }} |
                Keputusan: {{ strtoupper($result['decision']) }}
            </div>
        </div>

        <!-- GRID ATAS -->
        <div class="candidate-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">

            <!-- CARD: GRAFIK -->
            <div class="card">
                <h3>Grafik Profil Kemampuan</h3>
                <div style="height:260px;">
                    <canvas id="radarChart"></canvas>
                </div>
                <div style="margin-top:10px;font-size:13px;color:#64748b;">
                    Grafik ini menunjukkan profil kemampuan kerja kandidat berdasarkan hasil AI assessment.
                </div>
            </div>

            <!-- CARD: JAWABAN -->
            <div class="card">
                <h3>Jawaban Kandidat</h3>
                <div class="table-wrapper candidate-answers" style="max-height: 350px; overflow-y: auto;">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th style="width:50px;">No</th>
                                <th>Pertanyaan</th>
                                <th style="width:90px;">Jawaban</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($questions as $no => $question)
                                <tr>
                                    <td>{{ $no }}</td>
                                    <td>{{ $question }}</td>
                                    <td>
                                        @php
                                        $ans = $answers[$no] ?? '-';
                                        @endphp
                                        @if ($ans === 'ya')
                                            <span style="color:#16a34a;font-weight:600;">YA</span>
                                        @elseif ($ans === 'tidak')
                                            <span style="color:#dc2626;font-weight:600;">TIDAK</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- CARD BAWAH (FULL WIDTH) -->
        <div class="card" style="margin-top:16px;">
            <h3>Ringkasan Calon Medis</h3>

            <div style="
                font-size:15px;
                line-height:1.7;
                color:#334155;
                background:#f8fafc;
                padding:16px;
                border-radius:10px;
                border-left:4px solid #2563eb;
            ">
                {!! nl2br(e($result['personality_summary'] ?? '-')) !!}
            </div>

            <div style="margin-top:10px;font-size:12px;color:#64748b;">
                Catatan: Ringkasan ini dihasilkan otomatis sebagai alat bantu HR dan
                <strong>bukan diagnosis psikologis</strong>.
            </div>
        </div>

        <!-- CARD: DOKUMEN PELAMAR -->
        <div class="card" style="margin-top:16px;">
            <h3>Dokumen Pelamar</h3>

            <table class="table-custom">
                <tbody>
                    @php
                    $documentLabels = [
                        'ktp_ic' => 'KTP',
                        'skb' => 'SKB',
                        'sim' => 'SIM',
                    ];
                    @endphp

                    @foreach ($documentLabels as $type => $label)
                        @php
                        $doc = $documents[$type] ?? null;
                        @endphp
                        <tr>
                            <td style="width:220px;"><strong>{{ $label }}</strong></td>
                            <td>
                                @if ($doc)
                                    <a href="{{ url($doc['file_path']) }}"
                                        target="_blank"
                                        class="btn btn-sm btn-primary">
                                        📄 Lihat Dokumen
                                    </a>

                                    @if ($doc['is_valid'] === '0')
                                        <span class="badge badge-danger">TIDAK VALID</span>
                                    @elseif ($doc['is_valid'] === '1')
                                        <span class="badge badge-success">VALID</span>
                                    @endif

                                    @if ($doc['validation_notes'])
                                        <div style="font-size:12px;color:#dc2626;margin-top:4px;">
                                            {{ $doc['validation_notes'] }}
                                        </div>
                                    @endif
                                @else
                                    <span style="color:#94a3b8;font-size:13px;">Tidak tersedia</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:10px;font-size:12px;color:#64748b;">
                Dokumen ditampilkan sesuai file yang diunggah pelamar.
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('radarChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'radar',
                data: {
                    labels: [
                        'Focus',
                        'Consistency',
                        'Social',
                        'Emotional',
                        'Obedience',
                        'Honesty'
                    ],
                    datasets: [{
                        label: 'Profil Kandidat',
                        data: [
                            {{ (int)$result['focus_score'] }},
                            {{ (int)$result['consistency_score'] }},
                            {{ (int)$result['social_score'] }},
                            {{ (int)$result['attitude_score'] }},
                            {{ (int)$result['loyalty_score'] }},
                            {{ (int)$result['honesty_score'] }}
                        ],
                        backgroundColor: 'rgba(37, 99, 235, 0.2)',
                        borderColor: 'rgba(37, 99, 235, 1)'
                    }]
                },
                options: {
                    scales: {
                        r: {
                            suggestedMin: 0,
                            suggestedMax: 100
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection
