@extends('layouts.app')

@section('content')

<section class="content">
    <div class="page" style="max-width:1200px;margin:auto;">

        <h1>Reimbursement</h1>
        <p class="text-muted">
            {{ $rangeLabel ?? '-' }}
        </p>

        <div class="card" style="margin-bottom:20px;">
            <div class="card-header">Filter Rentang Tanggal</div>
            <div class="card-body">
                <form method="get" id="filterForm" class="filter-bar">
                    <div class="filter-group">
                        <label>Rentang</label>
                        <select name="range" id="rangeSelect" class="form-control">
                            <option value="week1" {{ (request('range', 'week3') === 'week1') ? 'selected' : '' }}>
                                3 Minggu Lalu
                            </option>
                            <option value="week2" {{ (request('range', 'week3') === 'week2') ? 'selected' : '' }}>
                                2 Minggu Lalu
                            </option>
                            <option value="week3" {{ (request('range', 'week3') === 'week3') ? 'selected' : '' }}>
                                Minggu Lalu
                            </option>
                            <option value="week4" {{ (request('range', 'week3') === 'week4') ? 'selected' : '' }}>
                                Minggu Ini
                            </option>
                            <option value="custom" {{ (request('range', 'week3') === 'custom') ? 'selected' : '' }}>
                                Custom
                            </option>
                        </select>
                    </div>
                    <div class="filter-group filter-custom">
                        <label>Tanggal Awal</label>
                        <input type="date" name="from" value="{{ htmlspecialchars(request('from', '')) }}" class="form-control">
                    </div>
                    <div class="filter-group filter-custom">
                        <label>Tanggal Akhir</label>
                        <input type="date" name="to" value="{{ htmlspecialchars(request('to', '')) }}" class="form-control">
                    </div>
                    <div class="filter-group" style="align-self:flex-end;">
                        <button type="submit" class="btn btn-primary">Terapkan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"
                style="display:flex;justify-content:space-between;align-items:center;">
                <span>Daftar Reimbursement</span>
                <button id="btnAddReim" class="btn-success">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Input Reimbursement
                </button>
            </div>

            <div class="table-wrapper">
                <table id="reimTable" class="table-custom">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tanggal</th>
                            <th>Kode</th>
                            <th>Sumber</th>
                            <th>Diajukan Oleh</th>
                            <th>Status</th>
                            <th>Bukti</th>
                            <th>Total</th>
                            <th>Dibayar Oleh</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($rows as $i => $r)
                            <tr>
                                <!-- # -->
                                <td>{{ $i + 1 }}</td>

                                <!-- TANGGAL PENGAJUAN -->
                                <td>{{ date('d M Y H:i', strtotime($r->created_at)) }}</td>

                                <!-- KODE -->
                                <td>{{ htmlspecialchars($r->reimbursement_code) }}</td>

                                <!-- SUMBER -->
                                <td>
                                    <div>
                                        <strong>{{ ucfirst($r->billing_source_type) }} – {{ htmlspecialchars($r->billing_source_name) }}</strong>
                                    </div>
                                    @if(!empty($r->item_name))
                                        <small style="color:#64748b;">
                                            Item: {{ htmlspecialchars($r->item_name) }}
                                        </small>
                                    @endif
                                </td>

                                <!-- DIAJUKAN OLEH -->
                                <td>
                                    @if(!empty($r->created_by_name))
                                        {{ htmlspecialchars($r->created_by_name) }}
                                    @else
                                        <span style="color:#9ca3af;">-</span>
                                    @endif
                                </td>

                                <!-- STATUS -->
                                <td>
                                    <span class="badge-status badge-{{ htmlspecialchars($r->status) }}">
                                        {{ strtoupper($r->status) }}
                                    </span>
                                </td>

                                <!-- BUKTI -->
                                <td>
                                    @if(!empty($r->receipt_file))
                                        <a href="#"
                                            class="doc-badge btn-preview-doc"
                                            data-src="/{{ htmlspecialchars($r->receipt_file) }}"
                                            data-title="Bukti Pembayaran {{ htmlspecialchars($r->reimbursement_code) }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            Bukti
                                        </a>
                                    @else
                                        <span style="color:#9ca3af;">-</span>
                                    @endif
                                </td>

                                <!-- TOTAL -->
                                <td>{{ dollar((int)$r->total_amount) }}</td>

                                <!-- DIBAYAR OLEH (NAMA + WAKTU) -->
                                <td>
                                    @if(!empty($r->paid_by_name))
                                        <div style="display:flex;flex-direction:column;">
                                            <strong>{{ htmlspecialchars($r->paid_by_name) }}</strong>
                                            @if(!empty($r->paid_at))
                                                <small style="color:#64748b;">
                                                    {{ date('d M Y H:i', strtotime($r->paid_at)) }}
                                                </small>
                                            @endif
                                        </div>
                                    @else
                                        <span style="color:#9ca3af;">-</span>
                                    @endif
                                </td>

                                <!-- AKSI -->
                                <td style="white-space:nowrap;">
                                    @if($canPayReimbursement && $r->status === 'submitted')
                                        <button class="btn-success"
                                            onclick="payReimbursement('{{ htmlspecialchars($r->reimbursement_code) }}')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Dibayarkan
                                        </button>
                                    @endif

                                    @if($isDirector)
                                        <button class="btn-danger"
                                            onclick="deleteReimbursement('{{ htmlspecialchars($r->reimbursement_code) }}')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Hapus
                                        </button>
                                    @endif

                                    @if(($userRole === 'staff' || $r->status !== 'submitted') && !$isDirector)
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

<script>
    function deleteReimbursement(code) {
        if (!confirm('Yakin hapus reimbursement ini? Data akan hilang permanen!')) return;

        fetch('{{ route("dashboard.reimbursement.delete") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: 'code=' + encodeURIComponent(code)
        }).then(() => location.reload());
    }
</script>

<!-- =================================================
     MODAL INPUT REIMBURSEMENT
     ================================================= -->
<div id="reimModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">

        <h3>Input Reimbursement</h3>

        <form method="POST"
            action="{{ route('dashboard.reimbursement.store') }}"
            class="form"
            enctype="multipart/form-data">

            @csrf

            <input type="hidden"
                name="reimbursement_code"
                value="REIMB-{{ date('Ymd-His') }}">

            <label>Sumber Tagihan</label>
            <select name="billing_source_type" required>
                <option value="instansi">Instansi</option>
                <option value="restoran">Restoran</option>
                <option value="toko">Toko</option>
                <option value="vendor">Vendor</option>
                <option value="lainnya">Lainnya</option>
            </select>

            <label>Nama Sumber</label>
            <input type="text" name="billing_source_name" placeholder="Contoh : Up And Atom, Queen Beach, Goverment, Dll" required>

            <label>Nama Item</label>
            <input type="text" name="item_name" placeholder="Contoh : Makanan & Minuman, Surat Keramaian" required>

            <div class="row-form-2">
                <div>
                    <label>Qty</label>
                    <input type="number" name="qty" value="1" min="1" required>
                </div>
                <div>
                    <label>Harga</label>
                    <input type="number" name="price" min="0" required>
                </div>
            </div>

            <!-- FILE UPLOAD STYLE (SETTING_AKUN) -->
            <div class="doc-upload-wrapper">
                <div class="doc-upload-header">
                    <label class="doc-label">Bukti Pembayaran</label>
                    <span class="badge-muted-mini">PNG / JPG</span>
                </div>

                <div class="doc-upload-input">
                    <label for="receipt_file" class="file-upload-label">
                        <span class="file-icon">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                        </span>
                        <span class="file-text">
                            <strong>Pilih file</strong>
                            <small>PNG atau JPG</small>
                        </span>
                    </label>
                    <input type="file"
                        id="receipt_file"
                        name="receipt_file"
                        accept="image/png,image/jpeg"
                        style="display:none;">
                    <div class="file-selected-name" data-for="receipt_file"></div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary btn-cancel">Batal</button>
                <button type="submit" class="btn-success">Simpan</button>
            </div>

        </form>
    </div>
</div>

<script>
    /* ===============================
       TOGGLE CUSTOM DATE FIELDS
   =============================== */
    document.addEventListener('DOMContentLoaded', function() {
        const rangeSelect = document.getElementById('rangeSelect');
        const customFields = document.querySelectorAll('.filter-custom');

        function toggleCustom() {
            if (rangeSelect.value === 'custom') {
                customFields.forEach(el => el.style.display = 'block');
            } else {
                customFields.forEach(el => el.style.display = 'none');
            }
        }

        rangeSelect.addEventListener('change', toggleCustom);
        toggleCustom(); // initial load

        // Initial state based on current value
        if (rangeSelect.value !== 'custom') {
            customFields.forEach(el => el.style.display = 'none');
        }
    });
</script>

<script>
    /* ===============================
   DATATABLES
   =============================== */
    document.addEventListener('DOMContentLoaded', function() {
        if (window.jQuery && jQuery.fn.DataTable) {
            jQuery('#reimTable').DataTable({
                pageLength: 10,
                order: [
                    [5, 'desc']
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                }
            });
        }
    });

    /* ===============================
       FILE NAME DISPLAY (SETTING_AKUN STYLE)
       =============================== */
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const display = document.querySelector(
                    '.file-selected-name[data-for="' + this.id + '"]'
                );
                if (!display) return;

                if (this.files.length > 0) {
                    const f = this.files[0];
                    display.innerHTML = `
                    <span class="selected-file-info">
                        <strong>${f.name}</strong>
                        <small>${(f.size / 1024).toFixed(1)} KB</small>
                    </span>
                `;
                    display.style.display = 'flex';
                } else {
                    display.style.display = 'none';
                }
            });
        });
    });

    /* ===============================
       MODAL HANDLER
       =============================== */
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('reimModal');
        const btnOpen = document.getElementById('btnAddReim');

        btnOpen.addEventListener('click', () => {
            modal.style.display = 'flex';
            document.body.classList.add('modal-open');
        });

        document.body.addEventListener('click', function(e) {
            if (
                e.target.classList.contains('modal-overlay') ||
                e.target.closest('.btn-cancel')
            ) {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }
        });
    });

    /* ===============================
       PAY REIMBURSEMENT
       =============================== */
    function payReimbursement(code) {
        if (!confirm('Tandai reimbursement ini sebagai DIBAYARKAN?')) return;

        fetch('{{ route("dashboard.reimbursement.pay") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: 'code=' + encodeURIComponent(code)
        }).then(() => location.reload());
    }
</script>

<!-- ======================================
     MODAL PREVIEW BUKTI PEMBAYARAN
     ====================================== -->
<div id="docPreviewModal" class="modal-overlay" style="display:none;">
    <div class="modal-card" style="max-width:900px;">

        <!-- HEADER -->
        <div class="modal-header">
            <strong id="docPreviewTitle">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Bukti Pembayaran
            </strong>
            <div style="display:flex;gap:8px;align-items:center;">
                <button type="button" class="zoom-control-btn" id="docZoomOut">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                    </svg>
                </button>
                <button type="button" class="zoom-control-btn" id="docZoomIn">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
                <button type="button" class="zoom-control-btn" id="docZoomReset">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
                <button type="button" onclick="closeDocModal()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- BODY -->
        <div class="modal-body"
            style="background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:60vh;">
            <img id="docPreviewImage"
                src=""
                alt="Bukti Pembayaran"
                style="max-width:100%;max-height:75vh;object-fit:contain;transition:transform 0.2s ease;">
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('docPreviewModal');
        const img = document.getElementById('docPreviewImage');
        const title = document.getElementById('docPreviewTitle');

        let scale = 1;
        let currentSrc = '';

        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-preview-doc');
            if (!btn) return;

            e.preventDefault();

            currentSrc = btn.dataset.src;
            img.src = currentSrc;
            title.textContent = btn.dataset.title || 'Bukti Pembayaran';

            scale = 1;
            img.style.transform = 'scale(1)';

            modal.style.display = 'flex';
            document.body.classList.add('modal-open');
        });

        window.closeDocModal = function() {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            img.src = '';
            scale = 1;
        };

        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeDocModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                closeDocModal();
            }
        });

        document.getElementById('docZoomIn').onclick = () => {
            scale = Math.min(scale + 0.2, 3);
            img.style.transform = `scale(${scale})`;
        };
        document.getElementById('docZoomOut').onclick = () => {
            scale = Math.max(scale - 0.2, 0.5);
            img.style.transform = `scale(${scale})`;
        };
        document.getElementById('docZoomReset').onclick = () => {
            scale = 1;
            img.style.transform = 'scale(1)';
        };
    });
</script>

@endsection
