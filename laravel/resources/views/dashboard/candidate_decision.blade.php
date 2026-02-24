@extends('layouts.app')

@section('content')
<section class="content">
    <div class="page" style="max-width:1000px;margin:auto;">

        <h1 class="gradient-text">Keputusan Akhir Kandidat</h1>

        <div class="card">
            <strong>{{ $candidate['ic_name'] }}</strong>

            <div style="font-size:13px;color:#64748b;line-height:1.7;margin-top:6px;">

                <strong>Test (Psychotest)</strong><br>
                Skor: <strong>{{ $ai['score_total'] ?? 0 }}</strong>
                <span style="color:#94a3b8;">(Bobot 30%)</span><br>
                Rekomendasi: <strong>{{ strtoupper($aiRecommendation) }}</strong>

                <br><br>

                <strong>Interview HR & Recruitment</strong><br>
                Nilai Akhir: <strong id="live-average-score">{{ $interviewResult['average_score'] ?? 0 }}</strong>
                <span style="color:#94a3b8;">(Bobot 60%)</span><br>
                Grade: <span id="live-final-grade" style="font-weight: bold;">{{ strtoupper(str_replace('_', ' ', $interviewResult['final_grade'] ?? '-')) }}</span><br>
                Confidence:
                <strong id="live-ml-confidence">{{ $interviewResult['ml_confidence'] ?? 0 }}</strong><strong>%</strong>

                <span class="ui-tooltip" style="position: relative; display: inline-block; cursor: help; background: #e2e8f0; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; line-height: 16px; text-align: center; margin-left: 4px;">?
                    <span class="ui-tooltip-text" style="visibility: hidden; width: 220px; background-color: #1e293b; color: #fff; text-align: center; border-radius: 6px; padding: 10px; position: absolute; z-index: 1; bottom: 125%; left: 50%; margin-left: -110px; opacity: 0; transition: opacity 0.3s; font-size: 11px;">
                        Confidence menunjukkan seberapa konsisten
                        penilaian antar HR terhadap kandidat ini.
                        <br><br>
                        Nilai tinggi berarti HR sepakat,
                        bukan berarti kandidat lebih percaya diri.
                    </span>
                </span>

                <style>
                    .ui-tooltip:hover .ui-tooltip-text {
                        visibility: visible;
                        opacity: 1;
                    }
                </style>

                <span style="color:#94a3b8;">(Bobot 10%)</span>

                <hr style="margin:12px 0;border:none;border-top:1px dashed #e5e7eb;">

                <strong>Skor Gabungan Sistem</strong><br>
                <span style="font-size:18px;font-weight:800;color:#0f172a;">
                    <span id="live-combined-score">{{ $combinedScore }}</span>
                </span>
                <span style="font-size:12px;color:#94a3b8;">/ 100</span>

                <div style="margin-top:4px;font-size:12px;color:#64748b;">
                    (Interview 60% + Test 30% + Confidence 10%)
                </div>

            </div>
        </div>

        <div id="live-ml-insight-container" @if(empty($mlFlags)) style="display:none;" @endif>
            <div class="card">
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Catatan Sistem (ML Insight)</h3>
                <ul style="padding-left: 20px;" id="live-ml-flags">
                    @foreach ($mlFlags as $key => $val)
                        <li>{{ ucfirst(str_replace('_', ' ', $key)) }} :
                            <strong>{{ $val }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        @if(!empty($existingDecision))

            <div class="card">
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Keputusan Telah Ditentukan</h3>

                <p>
                    <strong>Hasil Akhir:</strong>
                    @php
                        $badgeClass = $existingDecision['final_result'] === 'lolos' ? 'status-online' : 'status-offline';
                    @endphp
                    <span class="status-badge {{ $badgeClass }}">
                        {{ strtoupper($existingDecision['final_result']) }}
                    </span>
                </p>

                @if((int)$existingDecision['overridden'] === 1)
                    <div style="margin-top:10px;padding:10px;background:#fff7ed;border-left:4px solid #f97316;border-radius:6px;">
                        <strong>Override Keputusan Sistem</strong><br>
                        {{ $existingDecision['override_reason'] }}
                    </div>
                @endif

                <div style="margin-top: 15px;">
                    <small class="text-muted">
                        Diputuskan oleh {{ $existingDecision['decided_by'] }}
                        pada {{ \Carbon\Carbon::parse($existingDecision['created_at'])->format('d M Y H:i') }}
                    </small>
                </div>
            </div>

        @else

            @if(!$interviewResult || (int)($interviewResult['is_locked'] ?? 0) !== 1)
                <form method="POST" action="{{ route('dashboard.candidates.lock_interview') }}" class="card">
                    @csrf
                    <input type="hidden" name="applicant_id" value="{{ $applicantId }}">
                    <p class="text-muted" style="margin-bottom: 15px;">
                        Interview belum dikunci. Manajer harus mengunci interview sebelum membuat keputusan akhir.<br>
                        <small id="sync-status" style="color: #16a34a; font-weight: bold;"></small>
                    </p>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button type="submit"
                            name="lock_interview"
                            class="btn btn-warning"
                            style="cursor: pointer; position: relative; z-index: 10;"
                            onclick="return confirm('Kunci interview? Nilai HR tidak dapat diubah.')">
                            🔒 Kunci Interview
                        </button>
                        <a href="{{ route('dashboard.candidates') }}" class="btn btn-secondary">Kembali</a>
                    </div>
                </form>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const syncStatus = document.getElementById('sync-status');
                        function fetchLiveScores() {
                            fetch("{{ route('dashboard.candidates.get_temp_score', ['id' => $applicantId]) }}")
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        const avgScoreEl = document.getElementById('live-average-score');
                                        const gradeEl = document.getElementById('live-final-grade');
                                        const confidenceEl = document.getElementById('live-ml-confidence');
                                        const combinedScoreEl = document.getElementById('live-combined-score');
                                        
                                        if(avgScoreEl) avgScoreEl.textContent = data.average_score;
                                        if(gradeEl) gradeEl.textContent = data.final_grade;
                                        if(confidenceEl) confidenceEl.textContent = data.ml_confidence;
                                        if(combinedScoreEl) combinedScoreEl.textContent = data.combined_score;
                                        
                                        const flagsContainer = document.getElementById('live-ml-flags');
                                        const insightBox = document.getElementById('live-ml-insight-container');
                                        
                                        if (flagsContainer && data.ml_flags && Object.keys(data.ml_flags).length > 0) {
                                            flagsContainer.innerHTML = '';
                                            for (const [key, value] of Object.entries(data.ml_flags)) {
                                                const li = document.createElement('li');
                                                li.innerHTML = `${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())} : <strong>${value}</strong>`;
                                                flagsContainer.appendChild(li);
                                            }
                                            if(insightBox) insightBox.style.display = 'block';
                                        } else if(insightBox) {
                                            insightBox.style.display = 'none';
                                        }
                                        
                                        if(syncStatus) syncStatus.textContent = '⚡ Data sinkron (Live)';
                                    }
                                })
                                .catch(error => {
                                    console.error('Error fetching scores:', error);
                                    if(syncStatus) syncStatus.textContent = '❌ Gagal sinkron';
                                });
                        }

                        // Fetch immediately and then every 5 seconds
                        fetchLiveScores();
                        const interval = setInterval(fetchLiveScores, 5000);
                        
                        // Stop interval on form submit to prevent any weird state
                        const form = document.querySelector('form[action="{{ route("dashboard.candidates.lock_interview") }}"]');
                        if(form) {
                            form.addEventListener('submit', () => clearInterval(interval));
                        }
                    });
                </script>
            @else

                <form method="POST" action="{{ route('dashboard.candidates.submit_decision') }}" class="card">
                    @csrf
                    <input type="hidden" name="applicant_id" value="{{ $applicantId }}">
                    <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">Form Keputusan Akhir</h3>

                    <label style="display: block; font-weight: 600; margin-bottom: 5px;">Keputusan Sistem (Otomatis)</label>
                    <div style="margin-bottom:12px;">
                        @php
                            $badgeClass = $systemResult === 'lolos' ? 'status-online' : 'status-offline';
                        @endphp
                        <span class="status-badge {{ $badgeClass }}">
                            {{ strtoupper($systemResult) }}
                        </span>
                    </div>

                    <input type="hidden" name="system_result" value="{{ $systemResult }}">

                    <div style="margin-top: 20px;">
                        <label style="cursor: pointer;">
                            <input type="checkbox" name="override" id="overrideToggle">
                            Override Keputusan Sistem
                        </label>
                    </div>

                    <div id="overrideBox" style="display:none;margin-top:15px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 5px;">Alasan Override <span style="color:red">*</span></label>
                        <textarea name="override_reason" rows="3" class="form-control" style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0;"></textarea>
                    </div>

                    <div style="margin-top:24px; display: flex; align-items: center; gap: 10px;">
                        <button type="submit" 
                            name="submit_decision"
                            class="btn btn-primary"
                            style="cursor: pointer; position: relative; z-index: 10;">
                            Simpan Keputusan Final
                        </button>
                        <a href="{{ route('dashboard.candidates') }}" class="btn btn-secondary">Kembali</a>
                    </div>
                </form>

                <script>
                    document.getElementById('overrideToggle')?.addEventListener('change', function() {
                        document.getElementById('overrideBox').style.display =
                            this.checked ? 'block' : 'none';
                    });
                </script>

            @endif

        @endif

    </div>
</section>
@endsection
