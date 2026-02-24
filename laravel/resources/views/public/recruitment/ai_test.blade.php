<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pertanyaan – Roxwood Hospital</title>

    <link rel="stylesheet" href="{{ asset('assets/legacy/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/legacy/css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/legacy/css/login.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/legacy/css/responsive.css') }}">
</head>

<body>

    <div class="login-wrapper">
        <div class="login-card">

            <div class="brand">
                <h2>Form Pertanyaan</h2>
                <p>Halo, <strong>{{ $applicant->ic_name }}</strong></p>
                <p>Silakan jawab pertanyaan sederhana berikut ini</p>
            </div>

            <div class="alert alert-info">
                Jawab sesuai kondisi dan kebiasaan Anda.<br>
                Tidak ada jawaban benar atau salah.
            </div>

            <form action="{{ route('public.recruitment.ai_test.submit') }}" method="POST" id="aiTestForm">
                @csrf
                <input type="hidden" name="applicant_id" value="{{ $applicantId }}">
                <input type="hidden" name="start_time" id="start_time">
                <input type="hidden" name="end_time" id="end_time">
                <input type="hidden" name="duration_seconds" id="duration_seconds">

                @foreach ($questions as $no => $text)
                    <label class="ai-question">{{ $no }}. {{ $text }}</label>

                    <div class="ai-radio-group">
                        <label class="ai-radio">
                            <input type="radio" name="q{{ $no }}" value="ya" required>
                            <span>Ya</span>
                        </label>

                        <label class="ai-radio">
                            <input type="radio" name="q{{ $no }}" value="tidak" required>
                            <span>Tidak</span>
                        </label>
                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:18px;">
                    Kirim Jawaban
                </button>

            </form>

        </div>
    </div>

    <!-- MODAL -->
    <div class="modal-overlay" id="introModal">
        <div class="modal-box">
            <h3>Petunjuk Pengisian</h3>
            <ul>
                <li>Tidak ada jawaban benar atau salah</li>
                <li>Jawablah dengan jujur sesuai kondisi Anda</li>
                <li><strong>Kerjakan dengan tenang, tidak perlu terburu-buru</strong></li>
            </ul>
            <div class="modal-actions">
                <button type="button" class="btn btn-primary" id="btnStartTest">
                    Saya Mengerti & Mulai
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const STORAGE_KEY = 'ai_test_{{ $applicantId }}';
            const form = document.getElementById('aiTestForm');
            const modal = document.getElementById('introModal');
            const startBtn = document.getElementById('btnStartTest');

            let data = JSON.parse(localStorage.getItem(STORAGE_KEY)) || {
                startTime: null,
                answers: {}
            };

            /* ===== RESTORE DATA ===== */
            if (data.startTime) {
                document.getElementById('start_time').value = data.startTime;
                modal.style.display = 'none';
            } else {
                document.body.classList.add('modal-open');
            }

            // restore answers
            Object.keys(data.answers).forEach(name => {
                const input = document.querySelector(`input[name="${name}"][value="${data.answers[name]}"]`);
                if (input) input.checked = true;
            });

            /* ===== START TEST ===== */
            startBtn.addEventListener('click', () => {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');

                if (!data.startTime) {
                    data.startTime = Math.floor(Date.now() / 1000);
                    document.getElementById('start_time').value = data.startTime;
                    save();
                }
            });

            /* ===== SAVE ANSWERS ===== */
            form.addEventListener('change', e => {
                if (e.target.name.startsWith('q')) {
                    data.answers[e.target.name] = e.target.value;
                    save();
                }
            });

            /* ===== SUBMIT ===== */
            form.addEventListener('submit', e => {
                // Validasi semua pertanyaan sudah dijawab
                let allAnswered = true;
                for (let i = 1; i <= 50; i++) {
                    if (!data.answers['q' + i]) {
                        allAnswered = false;
                        break;
                    }
                }

                if (!allAnswered) {
                    e.preventDefault();
                    alert('Mohon jawab semua pertanyaan sebelum mengirim.');
                    return false;
                }

                const end = Math.floor(Date.now() / 1000);

                document.getElementById('end_time').value = end;
                document.getElementById('duration_seconds').value =
                    data.startTime ? (end - data.startTime) : 0;

                localStorage.removeItem(STORAGE_KEY);
            });

            function save() {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            }

        });
    </script>

</body>

</html>
