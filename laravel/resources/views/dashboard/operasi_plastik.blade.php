@extends('layouts.app')

@section('content')

<section class="content">
    <div class="page" style="max-width:900px;margin:auto;">

        <h1 class="gradient-text">Operasi Plastik</h1>

        <!-- FLASH MESSAGES -->
        @if(session('flash_messages'))
            @foreach(session('flash_messages') as $m)
                <div class="alert alert-info">{{ $m }}</div>
            @endforeach
        @endif

        @if(session('flash_errors'))
            @foreach(session('flash_errors') as $e)
                <div class="alert alert-error">{{ $e }}</div>
            @endforeach
        @endif

        <!-- FORM INPUT -->
        <div class="card">
            <div class="card-header" style="justify-content:space-between;">
                <span>Input Operasi Plastik</span>
                <div class="operasi-status">
                    @if (!$bolehInput)
                        <span class="badge-danger" style="padding:4px 12px;border-radius:20px;font-size:12px;">
                            ⏳ {{ $sisaHari }} hari lagi
                        </span>
                    @else
                        <span class="badge-success" style="padding:4px 12px;border-radius:20px;font-size:12px;background:#16a34a;color:white;">
                            ✅ Sudah bisa
                        </span>
                    @endif
                </div>
            </div>

            <div class="card-body">
                <form method="POST" action="/dashboard/operasi_plastik_action.php" class="form">
                    @csrf
                    <label>Nama Medis</label>
                    <input type="text" value="{{ $medicName }}" readonly style="background:#f1f5f9;cursor:not-allowed;">

                    <label>Tanggal</label>
                    <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required>

                    <label>Jenis Operasi Plastik</label>
                    <select name="jenis_operasi" required>
                        <option value="">-- Pilih Jenis Operasi --</option>
                        <option value="Rekonstruksi Wajah">Rekonstruksi Wajah</option>
                        <option value="Suntik Putih">Suntik Putih</option>
                    </select>

                    <div class="ems-form-group" style="position:relative;margin-bottom:15px;">
                        <label>Yang Menangani</label>
                        <input type="text" id="pjSearch" placeholder="Cari nama Yang Menangani..." autocomplete="off" required>
                        <input type="hidden" name="id_penanggung_jawab" id="pjId">

                        <div id="pjSuggestion" class="ems-suggestion-box" style="display:none;position:absolute;width:100%;z-index:100;background:white;border:1px solid #ddd;max-height:200px;overflow-y:auto;box-shadow:0 4px 6px -1px rgb(0 0 0 / 0.1);">
                            @foreach ($penanggungJawab as $pj)
                                <div class="medic-suggestion-item" data-id="{{ $pj->id }}" style="padding:8px 12px;cursor:pointer;border-bottom:1px solid #f1f5f9;">
                                    {{ $pj->full_name }}
                                    <small style="color:#64748b;">({{ $pj->position }})</small>
                                </div>
                            @endforeach
                        </div>
                        <small class="text-muted">Minimal jabatan (Co.Ast)</small>
                    </div>

                    <label>Alasan</label>
                    <textarea name="alasan" rows="4" required placeholder="Contoh: Kecelakaan / Kebutuhan Estetika"></textarea>

                    <div style="margin-top:20px;">
                        <button type="submit" class="btn-primary" {{ !$bolehInput ? 'disabled' : '' }}>
                            Simpan Data Operasi
                        </button>
                    </div>

                    @if (!$bolehInput)
                        <p style="color:#b91c1c;font-weight:600;margin-top:10px;font-size:14px;">
                            Anda harus menunggu {{ $sisaHari }} hari lagi untuk operasi plastik berikutnya.
                        </p>
                    @endif
                </form>
            </div>
        </div>

        <!-- RIWAYAT -->
        <div class="card">
            <div class="card-header">
                Riwayat Operasi Plastik
            </div>
            
            <div class="table-wrapper">
                <table id="operasiPlastikTable" class="table-custom">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Nama Medis</th>
                            <th>Jenis Operasi</th>
                            <th>Yang Menangani</th>
                            <th>Status</th>
                            <th>Approved By</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($riwayat as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($row->tanggal)->format('d M Y') }}</td>
                                <td><strong>{{ $row->user->full_name }}</strong></td>
                                <td>{{ $row->jenis_operasi }}</td>
                                <td>{{ $row->penanggungJawab->full_name ?? '-' }}</td>
                                <td>
                                    @if ($row->status === 'approved')
                                        <span class="status-badge status-online">✅ Approved</span>
                                    @elseif ($row->status === 'rejected')
                                        <span class="status-badge status-offline">❌ Rejected</span>
                                    @else
                                        <span class="status-badge status-pending" style="background:#fef3c7;color:#92400e;">⏳ Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row->approvedBy)
                                        <strong>{{ $row->approvedBy->full_name }}</strong>
                                        <div style="font-size:11px;color:#64748b;">
                                            {{ \Carbon\Carbon::parse($row->approved_at)->format('d M Y') }}
                                        </div>
                                    @else
                                        <span style="color:#9ca3af;">-</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $role = strtolower($userRole);
                                        $position = strtolower($medicPos);
                                        $bolehAlasan = $row->status === 'pending' && ($role === 'manager' || $role === 'director' || $role === 'vice director' || !in_array($position, ['trainee', 'paramedic']));
                                    @endphp

                                    @if ($bolehAlasan)
                                        <button type="button" class="btn-secondary btn-sm btn-alasan"
                                            data-id="{{ $row->id }}"
                                            data-name="{{ $row->user->full_name }}"
                                            data-reason="{{ $row->alasan }}">
                                            Alasan
                                        </button>
                                    @else
                                        <span style="color:#9ca3af;">-</span>
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

<!-- MODAL ALASAN -->
<div id="modalAlasan" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3 id="modalTitle">Alasan Operasi</h3>
        <div id="modalContent" style="margin-top:10px;white-space:pre-line;padding:15px;background:#f8fafc;border-radius:8px;"></div>
        <div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">
            <button id="btnApprove" class="btn-success">Approve</button>
            <button id="btnReject" class="btn-danger">Reject</button>
            <button class="btn-secondary" onclick="closeAlasanModal()">Batal</button>
        </div>
    </div>
</div>

<script>
    let currentOperasiId = null;

    function closeAlasanModal() {
        document.getElementById('modalAlasan').style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (window.jQuery && jQuery.fn.DataTable) {
            jQuery('#operasiPlastikTable').DataTable({
                pageLength: 10,
                order: [[1, 'desc']],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        }

        // Suggestions
        const pjSearch = document.getElementById('pjSearch');
        const pjBox = document.getElementById('pjSuggestion');
        const pjIdInput = document.getElementById('pjId');

        pjSearch.addEventListener('focus', () => pjBox.style.display = 'block');
        pjSearch.addEventListener('input', () => {
            const q = pjSearch.value.toLowerCase();
            pjBox.querySelectorAll('.medic-suggestion-item').forEach(item => {
                item.style.display = item.textContent.toLowerCase().includes(q) ? 'block' : 'none';
            });
        });

        pjBox.addEventListener('click', e => {
            const item = e.target.closest('.medic-suggestion-item');
            if (item) {
                pjSearch.value = item.childNodes[0].textContent.trim();
                pjIdInput.value = item.dataset.id;
                pjBox.style.display = 'none';
            }
        });

        document.addEventListener('click', e => {
            if (!pjBox.contains(e.target) && e.target !== pjSearch) pjBox.style.display = 'none';
        });

        // Alasan Modal
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-alasan');
            if (!btn) return;
            currentOperasiId = btn.dataset.id;
            document.getElementById('modalTitle').innerText = 'Alasan Operasi - ' + btn.dataset.name;
            document.getElementById('modalContent').innerText = btn.dataset.reason;
            document.getElementById('modalAlasan').style.display = 'flex';
            document.body.classList.add('modal-open');
        });

        const submitAction = (action) => {
            if (!currentOperasiId || !confirm('Yakin ingin ' + action.toUpperCase() + '?')) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/dashboard/operasi_plastik_action.php';
            form.innerHTML = `
                @csrf
                <input type="hidden" name="action" value="${action}">
                <input type="hidden" name="id" value="${currentOperasiId}">
            `;
            document.body.appendChild(form);
            form.submit();
        };

        document.getElementById('btnApprove').onclick = () => submitAction('approve');
        document.getElementById('btnReject').onclick = () => submitAction('reject');

        // Auto hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(a => {
                a.style.transition = 'opacity 0.5s ease';
                a.style.opacity = '0';
                setTimeout(() => a.remove(), 500);
            });
        }, 5000);
    });
</script>

@endsection
