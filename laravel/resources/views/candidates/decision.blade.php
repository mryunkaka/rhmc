@extends('layouts.app')

@section('content')
<section class="content">
    <div class="page" style="max-width:1000px;margin:auto;">

        <h1 class="gradient-text">Keputusan Akhir Kandidat</h1>

        <div class="card">
            <strong>{{ $candidate->ic_name }}</strong>

            <div style="font-size:13px;color:#64748b;line-height:1.7;margin-top:6px;">
                <strong>Test (Psychotest)</strong><br>
                Skor: <strong>{{ $ai->score_total }}</strong>
                <span style="color:#94a3b8;">(Bobot 30%)</span><br>
                Rekomendasi: <strong>{{ strtoupper($aiRecommendation) }}</strong>

                <br><br>

                <strong>Interview HR & Recruitment</strong><br>
                Nilai Akhir: <strong>{{ $interviewResult->average_score ?? 0 }}</strong>
                <span style="color:#94a3b8;">(Bobot 60%)</span><br>
                Grade: {{ strtoupper(str_replace('_', ' ', $interviewResult->final_grade ?? '-')) }}<br>
                Confidence:
                <strong>{{ $interviewResult->ml_confidence ?? 0 }}%</strong>

                <span style="color:#94a3b8;">(Bobot 10%)</span>

                <hr style="margin:12px 0;border:none;border-top:1px dashed #e5e7eb;">

                <strong>Skor Gabungan Sistem</strong><br>
                <span style="font-size:18px;font-weight:800;color:#0f172a;">
                    {{ $combinedScore }}
                </span>
                <span style="font-size:12px;color:#94a3b8;">/ 100</span>

                <div style="margin-top:4px;font-size:12px;color:#64748b;">
                    (Interview 60% + Test 30% + Confidence 10%)
                </div>
            </div>
        </div>

        @if(!empty($mlFlags))
            <div class="card">
                <h3>Catatan Sistem (ML Insight)</h3>
                <ul>
                    @foreach ($mlFlags as $key => $val)
                        <li>{{ ucfirst(str_replace('_', ' ', $key)) }} :
                            <strong>{{ $val }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($existingDecision)
            <div class="card">
                <h3>Keputusan Telah Ditentukan</h3>
                <p>
                    <strong>Hasil Akhir:</strong>
                    <span class="badge badge-{{ $existingDecision->final_result === 'lolos' ? 'success' : 'danger' }}">
                        {{ strtoupper($existingDecision->final_result) }}
                    </span>
                </p>

                @if((int)$existingDecision->overridden === 1)
                    <div style="margin-top:10px;padding:10px;background:#fff7ed;border-left:4px solid #f97316;border-radius:6px;">
                        <strong>Override Keputusan Sistem</strong><br>
                        {!! nl2br(e($existingDecision->override_reason)) !!}
                    </div>
                @endif

                <small class="text-muted">
                    Diputuskan oleh {{ $existingDecision->decided_by }}
                    pada {{ date('d M Y H:i', strtotime($existingDecision->created_at)) }}
                </small>
            </div>
        @else
            @if(!$interviewResult || (int)$interviewResult->is_locked !== 1)
                <form action="{{ route('dashboard.candidates.lock_interview', ['id' => $candidate->id]) }}" method="post" class="card">
                    @csrf
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Kunci interview? Nilai HR tidak dapat diubah.')">
                        🔒 Kunci Interview
                    </button>
                </form>
            @else
                <form action="{{ route('dashboard.candidates.submit_decision', ['id' => $candidate->id]) }}" method="post" class="card">
                    @csrf
                    <h3>Form Keputusan Akhir</h3>

                    <label>Keputusan Sistem (Otomatis)</label>
                    <div style="margin-bottom:12px;">
                        <span class="badge badge-{{ $systemResult === 'lolos' ? 'success' : 'danger' }}">
                            {{ strtoupper($systemResult) }}
                        </span>
                    </div>

                    <input type="hidden" name="system_result" value="{{ $systemResult }}">

                    <label>
                        <input type="checkbox" name="override" id="overrideToggle">
                        Override Keputusan Sistem
                    </label>

                    <div id="overrideBox" style="display:none;margin-top:8px;">
                        <label>Alasan Override <span style="color:red">*</span></label>
                        <textarea name="override_reason" rows="3" style="width:100%;" class="input-custom"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top:14px;">
                        Simpan Keputusan Final
                    </button>
                </form>

                <script>
                    document.getElementById('overrideToggle')?.addEventListener('change', function() {
                        document.getElementById('overrideBox').style.display = this.checked ? 'block' : 'none';
                    });
                </script>
            @endif
        @endif
    </div>
</section>
@endsection
