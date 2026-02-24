<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Medis – Roxwood Hospital</title>

    <link rel="stylesheet" href="{{ asset('assets/legacy/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/legacy/css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/legacy/css/login.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/legacy/css/responsive.css') }}">
</head>

<body>

    <div class="login-wrapper">
        <div class="login-card">

            <!-- BRAND -->
            <div class="brand">
                <img src="{{ asset('assets/legacy/logo.png') }}" alt="Roxwood Hospital">
                <h2>Roxwood Hospital</h2>
                <p>Pendaftaran Calon Medis</p>
            </div>

            @if(session('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- FORM -->
            <form action="{{ route('public.recruitment.submit') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <label>Nama IC</label>
                <input type="text" name="ic_name" value="{{ old('ic_name') }}" required>

                <label>Umur OOC</label>
                <input type="number" name="ooc_age" value="{{ old('ooc_age') }}" required>

                <label>Nomor Telepon IC</label>
                <input type="text" name="ic_phone" value="{{ old('ic_phone') }}" required>

                <!-- PENGALAMAN MEDIS -->
                <label>Pengalaman Medis di Server Lain</label>
                <small class="text-muted">
                    Sebutkan server dan posisi terakhir Anda.<br>
                    <strong>(Jika tidak ada, tulis “-”)</strong>
                </small>
                <textarea name="medical_experience" rows="3" required>{{ old('medical_experience') }}</textarea>

                <label>Sudah Berapa Lama di Kota IME</label>
                <input type="text" name="city_duration" value="{{ old('city_duration') }}" required>

                <label>Jam Biasanya Online</label>
                <input type="text" name="online_schedule" value="{{ old('online_schedule') }}" required>

                <!-- TANGGUNG JAWAB LAIN -->
                <label>Apakah Anda memiliki tanggung jawab di kota lain?</label>
                <small class="text-muted">
                    Contoh: EMS, Government, atau instansi lain.<br>
                    <strong>(Jika ada, jelaskan. Jika tidak, tulis “-”)</strong>
                </small>
                <textarea name="other_city_responsibility" rows="2" required>{{ old('other_city_responsibility') }}</textarea>

                <!-- MOTIVASI -->
                <label>Alasan Bergabung dengan Roxwood Hospital</label>
                <textarea name="motivation" rows="3" required>{{ old('motivation') }}</textarea>

                <!-- PRINSIP KERJA -->
                <label>Hal Terpenting dalam Bekerja di Rumah Sakit</label>
                <textarea name="work_principle" rows="3" required>{{ old('work_principle') }}</textarea>

                <!-- AKADEMI -->
                <label>Bersedia Mengikuti Medical Academy?</label>
                <select name="academy_ready" required>
                    <option value="">-- Pilih Jawaban --</option>
                    <option value="ya" {{ old('academy_ready') == 'ya' ? 'selected' : '' }}>Ya</option>
                    <option value="tidak" {{ old('academy_ready') == 'tidak' ? 'selected' : '' }}>Tidak</option>
                </select>

                <!-- KOMITMEN -->
                <label>Siap Mengikuti Aturan & Etika</label>
                <select name="rule_commitment" required>
                    <option value="">-- Pilih Jawaban --</option>
                    <option value="ya" {{ old('rule_commitment') == 'ya' ? 'selected' : '' }}>Ya</option>
                    <option value="tidak" {{ old('rule_commitment') == 'tidak' ? 'selected' : '' }}>Tidak</option>
                </select>

                <!-- DURASI DUTY -->
                <label>Di kisaran berapa lama Anda dapat duty di Roxwood Hospital.</label>
                <small class="text-muted">
                    Contoh: <em>2–4 jam per hari, fleksibel, atau jadwal tertentu</em>
                </small>
                <input type="text" name="duty_duration" value="{{ old('duty_duration') }}" required>

                <!-- DOKUMEN -->
                <label>KTP IC (JPG)</label>
                <input type="file" name="ktp_ic" accept="image/jpeg" required>

                <label>SIM (JPG) <small>(Opsional)</small></label>
                <input type="file" name="sim" accept="image/jpeg">

                <label>SKB (JPG)</label>
                <input type="file" name="skb" accept="image/jpeg" required>

                <button type="submit">
                    Kirim Pendaftaran
                </button>

            </form>

            <small class="text-muted" style="display:block;text-align:center;margin-top:10px;">
                Setelah mendaftar, silakan menunggu informasi lanjutan dari tim rekrutmen.
            </small>

        </div>
    </div>

</body>

</html>
