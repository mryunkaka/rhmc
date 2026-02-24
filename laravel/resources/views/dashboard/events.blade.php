<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Daftar Event</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- EMS CSS -->
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/components.css">
</head>

<body>

    <div class="page" style="max-width:560px;margin:auto;">

        <h1 class="gradient-text">Daftar Event</h1>
        <p class="text-muted">Pendaftaran terbuka tanpa login</p>

        @if(session('flash_messages'))
            @foreach(session('flash_messages') as $m)
                <div class="alert alert-success">{{ $m }}</div>
            @endforeach
        @endif

        @if(session('flash_errors'))
            @foreach(session('flash_errors') as $e)
                <div class="alert alert-error">{{ $e }}</div>
            @endforeach
        @endif

        <!-- INFO EVENT -->
        <div class="card">
            <div class="card-header">{{ $event->nama_event }}</div>

            @php
                $hariMap = [
                    'Sunday'    => 'Minggu',
                    'Monday'    => 'Senin',
                    'Tuesday'   => 'Selasa',
                    'Wednesday' => 'Rabu',
                    'Thursday'  => 'Kamis',
                    'Friday'    => 'Jumat',
                    'Saturday'  => 'Sabtu',
                ];

                $dt = new DateTime($event->tanggal_event);
                $hari = $hariMap[$dt->format('l')] ?? '';
            @endphp

            <p class="text-muted">
                {{ $hari }}, {{ $dt->format('d M Y') }}
                • {{ $event->lokasi ?? '-' }}
            </p>

            <div class="info-notice">
                <strong>Total Peserta:</strong> {{ (int)($stat->total ?? 0) }} orang<br>
                <strong>Laki-laki:</strong> {{ (int)($stat->laki ?? 0) }} orang<br>
                <strong>Perempuan:</strong> {{ (int)($stat->perempuan ?? 0) }} orang
            </div>
        </div>

        <!-- FORM -->
        <div class="card">
            <div class="card-header">Form Pendaftaran</div>

            <form method="POST" action="{{ route('dashboard.events.register') }}" class="form" autocomplete="off">
                @csrf
                <input type="hidden" name="event_id" value="{{ (int)$eventId }}">

                <!-- NAMA + AUTOCOMPLETE -->
                <div class="row-form-1" style="position:relative;">
                    <label>Nama Lengkap <span class="required">*</span></label>

                    <input
                        type="text"
                        name="nama_lengkap"
                        id="namaInput"
                        placeholder="Ketik nama…"
                        required>

                    <!-- DROPDOWN AUTOCOMPLETE -->
                    <div id="namaDropdown" class="consumer-search-dropdown hidden"></div>

                    <small class="hint-info">
                        Jika nama belum ada, akun akan dibuat otomatis
                    </small>
                </div>

                <!-- BATCH -->
                <div class="row-form-1">
                    <label>Batch <span class="required">*</span></label>
                    <input type="text" name="batch" id="batchInput" required>
                </div>

                <!-- JENIS KELAMIN -->
                <div class="row-form-1">
                    <label>Jenis Kelamin <span class="required">*</span></label>
                    <select name="jenis_kelamin" id="genderSelect" required>
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>

                <div class="form-submit-wrapper">
                    <button class="btn btn-success btn-submit">
                        Daftar Event
                    </button>
                </div>

            </form>

            <script>
                document.addEventListener('DOMContentLoaded', () => {

                    const inputNama = document.getElementById('namaInput');
                    const dropdown = document.getElementById('namaDropdown');
                    const batchInput = document.getElementById('batchInput');
                    const genderInput = document.getElementById('genderSelect');

                    let controller = null;

                    inputNama.addEventListener('input', () => {
                        const keyword = inputNama.value.trim();

                        batchInput.value = '';
                        genderInput.value = '';

                        if (keyword.length < 2) {
                            dropdown.classList.add('hidden');
                            dropdown.innerHTML = '';
                            return;
                        }

                        if (controller) controller.abort();
                        controller = new AbortController();

                        fetch('../ajax/search_user_rh.php?q=' + encodeURIComponent(keyword), {
                                signal: controller.signal
                            })
                            .then(res => res.json())
                            .then(data => {

                                dropdown.innerHTML = '';

                                if (!data.length) {
                                    dropdown.classList.add('hidden');
                                    return;
                                }

                                data.forEach(user => {
                                    const item = document.createElement('div');
                                    item.className = 'consumer-search-item';

                                    item.innerHTML = `
                    <div class="consumer-search-name">${user.full_name}</div>
                    <div class="consumer-search-meta">
                        <span>${user.position ?? '-'}</span>
                        <span class="dot">•</span>
                        <span>Batch ${user.batch ?? '-'}</span>
                    </div>
                `;

                                    item.addEventListener('click', () => {
                                        inputNama.value = user.full_name;
                                        batchInput.value = user.batch ?? '';
                                        genderInput.value = user.jenis_kelamin ?? '';

                                        dropdown.classList.add('hidden');
                                        dropdown.innerHTML = '';
                                    });

                                    dropdown.appendChild(item);
                                });

                                dropdown.classList.remove('hidden');
                            })
                            .catch(() => {});
                    });

                    document.addEventListener('click', (e) => {
                        if (!inputNama.contains(e.target) && !dropdown.contains(e.target)) {
                            dropdown.classList.add('hidden');
                        }
                    });

                });
            </script>


        </div>

    </div>

</body>

</html>
