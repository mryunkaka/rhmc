@extends('layouts.app')

@section('content')
<section class="content">
    <div class="page" style="max-width:1100px;margin:auto;">

        <h1 class="gradient-text">Daftar Calon Kandidat</h1>
        <p class="text-muted">Monitoring hasil rekrutmen dan penilaian AI</p>

        @if(request()->get('error') == 'min_hr')
            <div class="alert alert-danger" style="margin-bottom:20px; padding:10px; background:rgba(239,68,68,0.1); color:#991b1b; border:1px solid #ef4444; border-radius:8px;">
                <strong>⛔ Stop!</strong> Interview belum dapat diselesaikan. Minimal diperlukan 2 HR untuk memberikan penilaian.
            </div>
        @endif

        @if(session('interview_done'))
            <div class="alert alert-success" style="margin-bottom:20px; padding:10px; background:rgba(34,197,94,0.1); color:#166534; border:1px solid #22c55e; border-radius:8px;">
                ✅ Status kandidat berhasil diupdate ke <strong>Final Review</strong>.
            </div>
        @endif

        <div class="card">
            <div class="card-header">Calon Kandidat</div>

            <div class="table-wrapper">
                <table id="candidateTable" class="table-custom">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Status</th>
                            <th>Skor Tes</th>
                            <th>Skor Interview HR</th>
                            <th>Confidence</th>
                            <th>Skor Gabungan</th>
                            <th>Interviewer</th>
                            <th>Hasil Akhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($candidates as $i => $c)
                            @php
                            $interviewScore = (float)($c['interview_score'] ?? 0);
                            $aiScore        = (float)($c['ai_score'] ?? 0);
                            $confidence     = (float)($c['confidence'] ?? 0);

                            $combinedScore = '-';

                            if ((int)($c['interview_locked'] ?? 0) === 1) {
                                $combinedScore = round(
                                    ($interviewScore * 0.6) +
                                        ($aiScore * 0.3) +
                                        ($confidence * 0.1),
                                    2
                                );
                            }
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <strong>
                                        <a href="{{ route('dashboard.candidates.detail', ['id' => $c['id']]) }}">
                                            {{ $c['ic_name'] }}
                                        </a>
                                    </strong>
                                    <div style="font-size:12px;color:#64748b;">
                                        Daftar: {{ date('d M Y', strtotime($c['created_at'])) }}
                                    </div>
                                </td>
                                <td>
                                    @switch ($c['status'])
                                        @case('ai_completed')
                                            <span class="status-box pending">
                                                <span class="icon">⏳</span>
                                                PENDING
                                            </span>
                                            @break

                                        @case('interview')
                                            <span class="status-box pending">
                                                <span class="icon">🎤</span>
                                                INTERVIEW
                                            </span>
                                            @break

                                        @case('final_review')
                                            <span class="status-box pending">
                                                <span class="icon">🧠</span>
                                                FINAL REVIEW
                                            </span>
                                            @break

                                        @case('accepted')
                                            <span class="status-box verified">
                                                <span class="icon">✅</span>
                                                DITERIMA
                                            </span>
                                            @break

                                        @case('rejected')
                                            <span class="status-box verified" style="background:rgba(239,68,68,.14);color:#991b1b;border-color:rgba(239,68,68,.4)">
                                                <span class="icon">❌</span>
                                                DITOLAK
                                            </span>
                                            @break

                                        @default
                                            <span class="status-box">
                                                {{ strtoupper($c['status']) }}
                                            </span>
                                    @endswitch
                                </td>

                                <td>{{ $aiScore ?: '-' }}</td>

                                <td>{{ $interviewScore ?: '-' }}</td>

                                <td>
                                    {{ $confidence ? $confidence . '%' : '-' }}
                                </td>

                                <td>
                                    <strong>{{ $combinedScore }}</strong>
                                </td>

                                <td style="font-size:12px;color:#334155;line-height:1.4;">
                                    @if ($c['interviewers'])
                                        {{ $c['interviewers'] }}
                                        @if ((int)$c['total_hr'] > 1)
                                            <div style="font-size:11px;color:#64748b;">
                                                ({{ (int)$c['total_hr'] }} Orang)
                                            </div>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($c['final_result'])
                                        <span class="badge badge-{{ $c['final_result'] === 'lolos' ? 'success' : 'danger' }}">
                                            {{ strtoupper($c['final_result']) }}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">
                                            {{ strtoupper($c['ai_decision'] ?? '-') }}
                                        </span>
                                    @endif
                                </td>

                                <td style="white-space:nowrap;">

                                    @if ($c['status'] === 'ai_completed')
                                        <!-- AI SELESAI: PILIHAN LANJUT ATAU TOLAK -->
                                        <form method="post" action="{{ route('dashboard.candidates.ai-decision') }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="ai_decision" value="proceed">
                                            <input type="hidden" name="applicant_id" value="{{ (int)$c['id'] }}">
                                            <button type="submit"
                                                class="btn btn-primary"
                                                style="margin-right:4px;"
                                                onclick="return confirm('Lanjutkan ke tahap wawancara?')">
                                                ➡️ Lanjut Wawancara
                                            </button>
                                        </form>

                                        <form method="post" action="{{ route('dashboard.candidates.ai-decision') }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="ai_decision" value="reject">
                                            <input type="hidden" name="applicant_id" value="{{ (int)$c['id'] }}">
                                            <button type="submit"
                                                class="btn btn-danger"
                                                onclick="return confirm('Tolak kandidat tanpa proses wawancara?')">
                                                ❌ Tidak Diterima
                                            </button>
                                        </form>
                                    @endif

                                    @if (in_array($c['status'], ['interview'], true))
                                        <!-- INTERVIEW MULTI-HR -->
                                        <a href="{{ route('dashboard.candidates.interview_multi', ['id' => $c['id']]) }}"
                                            class="btn btn-primary"
                                            style="margin-right:4px;">
                                            Interview
                                        </a>
                                    @endif

                                    @if ($c['status'] === 'interview')
                                        <!-- SELESAI INTERVIEW -->
                                        <form method="post" action="{{ route('dashboard.candidates.finish-interview') }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="finish_interview" value="1">
                                            <input type="hidden" name="applicant_id" value="{{ (int)$c['id'] }}">
                                            <button type="submit"
                                                class="btn-warning btn-finish-interview"
                                                style="margin-right:4px;"
                                                data-total-hr="{{ (int)$c['total_hr'] }}">
                                                Selesai
                                            </button>
                                        </form>
                                    @endif

                                    @if ($c['status'] === 'final_review' || in_array($c['status'], ['accepted', 'rejected'], true))
                                        <!-- DECISION (MANAGER) -->
                                        <a href="{{ route('dashboard.candidates.decision_page', ['id' => $c['id']]) }}"
                                            class="btn btn-success"
                                            style="margin-right:4px;">
                                            Decision
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('submit', function(e) {
            const form = e.target;
            const button = form.querySelector('.btn-finish-interview');

            if (!button) return; // bukan form "Selesai"

            const totalHr = parseInt(button.dataset.totalHr || '0', 10);

            if (totalHr < 2) {
                e.preventDefault(); // ⛔ STOP TOTAL

                alert(
                    '⛔ Interview belum dapat diselesaikan.\n\n' +
                    'Penilaian baru diberikan oleh ' + totalHr + ' HR.\n' +
                    'Minimal diperlukan 2 HR.\n\n' +
                    'Silakan tunggu HR lain memberikan penilaian.'
                );

                return false;
            }

            // HR sudah cukup → minta konfirmasi
            if (!confirm('Tandai interview selesai?')) {
                e.preventDefault();
                return false;
            }

        }, true); // ⬅️ CAPTURE MODE (PENTING!)
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.jQuery && jQuery.fn.DataTable) {
            jQuery('#candidateTable').DataTable({
                pageLength: 10,
                scrollX: true, // ⬅️ INI PENTING
                autoWidth: false, // ⬅️ BIAR CSS YANG NGATUR
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        }
    });
</script>
@endpush
@endsection
