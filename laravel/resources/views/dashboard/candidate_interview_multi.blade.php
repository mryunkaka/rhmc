@extends('layouts.app')

@section('content')
<section class="content">
    <div class="page" style="max-width:900px;margin:auto;">

        <h1 class="gradient-text">Interview Kandidat</h1>

        <div class="card">
            <strong>{{ $candidate->ic_name }}</strong><br>
            <small class="text-muted">
                HR: {{ session('user_rh.name', '-') }}
            </small>
        </div>

        @if($errors->any())
            <div class="alert alert-error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('dashboard.candidates.interview_multi.store') }}" class="card">
            @csrf
            <input type="hidden" name="applicant_id" value="{{ $applicantId }}">

            @foreach ($criteria as $c)
                <div style="margin-bottom:14px;">
                    <label>
                        <strong>{{ $c->label }}</strong><br>
                        <small class="text-muted">{{ $c->description }}</small>
                    </label>

                    <select
                        name="score[{{ $c->id }}]"
                        required
                        class="form-select"
                        style="margin-top:6px; width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #e2e8f0;">
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
                            <option value="{{ $v }}"
                                {{ (($existingScores[$c->id] ?? '') == $v) ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach

            <div style="margin-top:20px;">
                <label>Catatan Interview (Opsional)</label>
                <textarea
                    name="notes"
                    rows="4"
                    class="form-control"
                    style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; margin-top: 6px;"
                    placeholder="Catatan pribadi HR (tidak dilihat HR lain)">{{ $existingNotes }}</textarea>
            </div>

            <div style="margin-top:24px;">
                <button type="submit" class="btn btn-primary">
                    Simpan Penilaian Interview
                </button>
                <a href="{{ route('dashboard.candidates') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </form>

    </div>
</section>
@endsection
