@extends('layouts.app')

@section('content')
<section class="content">
    <div class="page" style="max-width:900px;margin:auto;">

        <h1 class="gradient-text">Interview Kandidat</h1>

        <div class="card">
            <strong>{{ $candidate->ic_name }}</strong><br>
            <small class="text-muted">
                HR: {{ session('user_rh')['name'] ?? '-' }}
            </small>
        </div>

        <form action="{{ route('dashboard.candidates.interview.submit', ['id' => $candidate->id]) }}" method="post" class="card">
            @csrf
            
            @foreach ($criteria as $c)
                <div style="margin-bottom:14px;">
                    <label>
                        <strong>{{ $c->label }}</strong><br>
                        <small class="text-muted">{{ $c->description }}</small>
                    </label>

                    <select name="score[{{ $c->id }}]" required style="margin-top:6px; width:100%;" class="input-custom">
                        <option value="">-- Pilih Nilai --</option>
                        @php
                            $options = [
                                1 => 'Sangat Buruk',
                                2 => 'Buruk',
                                3 => 'Sedang',
                                4 => 'Baik',
                                5 => 'Sangat Baik'
                            ];
                        @endphp
                        @foreach ($options as $v => $label)
                            <option value="{{ $v }}" {{ (($existingScores[$c->id] ?? '') == $v) ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach

            <label>Catatan Interview (Opsional)</label>
            <textarea name="notes" rows="4" placeholder="Catatan pribadi HR (tidak dilihat HR lain)" style="width:100%;" class="input-custom"></textarea>

            <button type="submit" class="btn btn-primary" style="margin-top:16px;">
                Simpan Penilaian Interview
            </button>
        </form>

    </div>
</section>
@endsection
