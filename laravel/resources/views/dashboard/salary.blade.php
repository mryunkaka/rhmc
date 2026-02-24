@extends('layouts.app')

@section('content')
<section class="content">
    <div class="page" style="max-width:1100px;margin:auto;">
        <h1>Rekap Gaji Mingguan</h1>

        <p class="text-muted">{{ $rangeLabel ?? '-' }}</p>

        @if (!$isStaff && request('range') !== 'all')
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    Filter Rentang Tanggal
                </div>

                <div class="card-body">
                    <form method="GET" id="filterForm" class="filter-bar">
                        <div class="filter-group">
                            <label>Rentang</label>
                            <select name="range" id="rangeSelect" class="form-control">
                                <option value="week1" {{ request('range') === 'week1' ? 'selected' : '' }}>3 Minggu Lalu</option>
                                <option value="week2" {{ request('range') === 'week2' ? 'selected' : '' }}>2 Minggu Lalu</option>
                                <option value="week3" {{ request('range') === 'week3' ? 'selected' : '' }}>Minggu Lalu</option>
                                <option value="week4" {{ request('range', 'week4') === 'week4' ? 'selected' : '' }}>Minggu Ini</option>
                                <option value="custom" {{ request('range') === 'custom' ? 'selected' : '' }}>Custom</option>
                            </select>
                        </div>

                        <div class="filter-group filter-custom" style="display:none;">
                            <label>Tanggal Awal</label>
                            <input type="date" name="from" value="{{ request('from') }}" class="form-control">
                        </div>

                        <div class="filter-group filter-custom" style="display:none;">
                            <label>Tanggal Akhir</label>
                            <input type="date" name="to" value="{{ request('to') }}" class="form-control">
                        </div>

                        <div class="filter-group" style="align-self:flex-end;">
                            <button type="submit" class="btn btn-primary">
                                Terapkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Ringkasan Gaji</div>

                <div class="card-body ringkasan-gaji-grid">
                    <div class="stat-box">
                        <small>Total Transaksi</small>
                        <h3>{{ (int)$rekap['total_transaksi'] }}</h3>
                    </div>

                    <div class="stat-box">
                        <small>Total Bonus</small>
                        <h3>{{ dollar($rekap['total_rupiah']) }}</h3>
                    </div>

                    <div class="stat-box highlight">
                        <small>Total Bonus (40%)</small>
                        <h3>{{ dollar($rekap['total_bonus']) }}</h3>
                    </div>

                    <div class="stat-box" style="background: linear-gradient(145deg, #15803d, #166534);">
                        <small>Sudah Dibayarkan</small>
                        <h3>{{ dollar($totalPaidBonus) }}</h3>
                    </div>

                    <div class="stat-box" style="background: linear-gradient(145deg, #f59e0b, #d97706);">
                        <small>Sisa Bonus</small>
                        <h3>{{ dollar($sisaBonus) }}</h3>
                    </div>
                </div>
            </div>
        @endif

        @if (session('generated'))
            <div class="alert alert-success" id="autoAlert">
                ✅ Generate gaji manual selesai.
                Periode baru dibuat: <strong>{{ (int)session('generated') }}</strong>
            </div>
        @elseif (session('msg') === 'nosales')
            <div class="alert alert-warning" id="autoAlert">
                ⚠️ Tidak ada data sales untuk dihitung.
            </div>
        @endif

        @if ($isStaff)
            <p class="text-muted">Menampilkan gaji Anda saja.</p>
        @endif

        <div class="card">
            <div class="card-header">Daftar Gaji</div>

            @if (in_array(strtolower($userRole), ['vice director', 'director'], true))
                <form action="{{ route('dashboard.salary.generate') }}" method="POST" style="margin-bottom:14px;">
                    @csrf
                    <button
                        type="submit"
                        class="btn btn-warning"
                        onclick="return confirm('Generate gaji mingguan sekarang? Digunakan jika otomatis generate bermasalah.')">
                        🔄 Generate Gaji Manual
                    </button>
                </form>
            @endif

            <div class="table-wrapper">
                <table id="salaryTable" class="table-custom">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Periode</th>
                            <th>Bonus</th>
                            <th>Status</th>
                            <th>Dibayar Oleh</th>
                            @if (!$isStaff)
                                <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($salary as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $row->medic_name }}</td>
                                <td>{{ $row->medic_jabatan }}</td>
                                <td>
                                    {{ date('d M Y', strtotime($row->period_start)) }}
                                    -
                                    {{ date('d M Y', strtotime($row->period_end)) }}
                                </td>
                                <td>$ {{ number_format($row->bonus_40) }}</td>

                                <td>
                                    @if ($row->status === 'paid')
                                        <div class="status-box verified">✔ Dibayar</div>
                                    @else
                                        <div class="status-box pending">⏳ Pending</div>
                                    @endif
                                </td>

                                <td>
                                    {{ $row->paid_by ?? '-' }}
                                    @if (!empty($row->paid_at))
                                        <div style="font-size:11px;color:#64748b;margin-top:2px;">
                                            {{ formatTanggalID($row->paid_at) }}
                                        </div>
                                    @endif
                                </td>

                                @if (!$isStaff)
                                    <td>
                                        @if ($row->status !== 'paid')
                                            <button type="button"
                                                class="btn btn-success btn-sm"
                                                onclick="openPayModal({{ $row->id }}, '{{ addslashes($row->medic_name) }}', {{ $row->bonus_40 }})">
                                                Bayar
                                            </button>
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="4" style="text-align:right;font-weight:600;">
                                TOTAL :
                            </th>
                            <th id="totalBonus">0</th>
                            <th colspan="{{ (!$isStaff) ? 3 : 2 }}"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL PEMBAYARAN GAJI -->
    <div id="payModal" class="modal-overlay" style="display:none;">
        <div class="modal-box">
            <h3>💰 Konfirmasi Pembayaran Gaji</h3>

            <form id="payForm" class="form" style="margin-top:16px;">
                @csrf
                <input type="hidden" id="paySalaryId" name="salary_id">

                <!-- Info Target Pembayaran -->
                <div style="background:#f8fafc;padding:12px;border-radius:10px;margin-bottom:14px;border:1px solid #e2e8f0;">
                    <div style="font-size:12px;color:#64748b;margin-bottom:4px;">Target Pembayaran:</div>
                    <div style="font-size:15px;font-weight:700;color:#0f172a;" id="payTargetName">-</div>
                    <div style="font-size:13px;color:#16a34a;margin-top:4px;font-weight:600;">
                        $<span id="payTargetBonus">0</span>
                    </div>
                </div>

                <!-- Pilihan Metode Pembayaran -->
                <div style="margin-bottom:14px;">
                    <label style="font-size:13px;font-weight:700;">Metode Pembayaran:</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px;">
                        <label class="pay-method-option" style="display:flex;align-items:center;padding:10px;border:2px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all 0.2s;margin:0;">
                            <input type="radio" name="pay_method" value="direct" checked style="margin-right:8px;">
                            <span style="font-size:13px;font-weight:600;">Langsung Dibayar</span>
                        </label>
                        <label class="pay-method-option" style="display:flex;align-items:center;padding:10px;border:2px solid #e2e8f0;border-radius:10px;cursor:pointer;transition:all 0.2s;margin:0;">
                            <input type="radio" name="pay_method" value="titip" style="margin-right:8px;">
                            <span style="font-size:13px;font-weight:600;">Titip ke:</span>
                        </label>
                    </div>
                </div>

                <!-- Input Titip ke Siapa (dengan autocomplete) -->
                <div id="titipSection" style="display:none;margin-bottom:14px;">
                    <label style="font-size:13px;font-weight:700;">Titip ke Siapa:</label>
                    <div style="position:relative;">
                        <input type="text" id="titipInput" name="titip_to_name"
                            placeholder="Ketik nama orang..."
                            autocomplete="off"
                            style="width:100%;padding:12px 14px;font-size:14px;border:1px solid #cbd5e1;border-radius:12px;">
                        <!-- DROPDOWN AUTOCOMPLETE -->
                        <div id="titipDropdown" class="consumer-search-dropdown hidden"></div>
                    </div>
                    <small style="color:#64748b;font-size:11px;display:block;margin-top:4px;">
                        💡 Jika nama belum ada, akun akan dibuat otomatis (seperti form event)
                    </small>
                </div>

                <!-- Actions -->
                <div class="modal-actions" style="margin-top:16px;">
                    <button type="button" onclick="closePayModal()" style="background:#e5e7eb;color:#334155;">Batal</button>
                    <button type="submit" class="btn-success" style="background:linear-gradient(135deg, #22c55e, #16a34a);color:#fff;">💰 Proses Pembayaran</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        /* Highlight radio button saat dipilih */
        input[type="radio"]:checked+span {
            color: #0369a1;
        }

        label.selected {
            border-color: #0ea5e9 !important;
            background: #f0f9ff;
        }

        /* Modal box animation */
        #payModal .modal-box {
            animation: slideUp 0.3s ease;
        }

        #payModal h3 {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</section>
@endsection

@push('scripts')
<script>
    let selectedUserId = null;

    // Buka modal pembayaran
    function openPayModal(id, medicName, bonus) {
        document.getElementById('paySalaryId').value = id;
        document.getElementById('payTargetName').textContent = medicName;
        document.getElementById('payTargetBonus').textContent = bonus.toLocaleString('id-ID');
        document.getElementById('payModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Reset form
        document.querySelector('input[name="pay_method"][value="direct"]').checked = true;
        document.getElementById('titipSection').style.display = 'none';
        document.getElementById('titipInput').value = '';
        document.getElementById('titipDropdown').classList.add('hidden');
        document.getElementById('titipDropdown').innerHTML = '';
        
        document.querySelectorAll('.pay-method-option').forEach(lbl => lbl.classList.remove('selected'));
        document.querySelector('input[name="pay_method"][value="direct"]').closest('label').classList.add('selected');
        
        selectedUserId = null;
    }

    // Tutup modal
    function closePayModal() {
        document.getElementById('payModal').style.display = 'none';
        document.body.style.overflow = '';
    }

    // Handle perubahan metode pembayaran
    document.querySelectorAll('input[name="pay_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const titipSection = document.getElementById('titipSection');
            document.querySelectorAll('.pay-method-option').forEach(lbl => lbl.classList.remove('selected'));
            this.closest('label').classList.add('selected');

            if (this.value === 'titip') {
                titipSection.style.display = 'block';
            } else {
                titipSection.style.display = 'none';
                selectedUserId = null;
            }
        });
    });

    // Autocomplete untuk "Titip ke Siapa"
    const titipInput = document.getElementById('titipInput');
    const titipDropdown = document.getElementById('titipDropdown');
    let titipController = null;

    titipInput.addEventListener('input', () => {
        const keyword = titipInput.value.trim();

        if (keyword.length < 2) {
            titipDropdown.classList.add('hidden');
            titipDropdown.innerHTML = '';
            return;
        }

        if (titipController) titipController.abort();
        titipController = new AbortController();

        fetch('/ajax/search_user_rh?q=' + encodeURIComponent(keyword), {
                signal: titipController.signal
            })
            .then(res => res.json())
            .then(data => {
                titipDropdown.innerHTML = '';

                if (!data.length) {
                    titipDropdown.classList.add('hidden');
                    return;
                }

                data.forEach(user => {
                    const item = document.createElement('div');
                    item.className = 'consumer-search-item';

                    const nameDiv = document.createElement('div');
                    nameDiv.className = 'consumer-search-name';
                    nameDiv.textContent = user.full_name;
                    item.appendChild(nameDiv);

                    const metaDiv = document.createElement('div');
                    metaDiv.className = 'consumer-search-meta';
                    metaDiv.innerHTML = `
                        <span>${user.position ?? '-'}</span>
                        <span class="dot">•</span>
                        <span>Batch ${user.batch ?? '-'}</span>
                    `;
                    item.appendChild(metaDiv);

                    item.addEventListener('click', () => {
                        titipInput.value = user.full_name;
                        selectedUserId = user.id;
                        titipDropdown.classList.add('hidden');
                        titipDropdown.innerHTML = '';
                    });

                    titipDropdown.appendChild(item);
                });

                titipDropdown.classList.remove('hidden');
            })
            .catch(error => {
                if (error.name !== 'AbortError') console.error('Error fetching user:', error);
            });
    });

    document.addEventListener('click', (e) => {
        if (!titipInput.contains(e.target) && !titipDropdown.contains(e.target)) {
            titipDropdown.classList.add('hidden');
        }
    });

    // Submit form
    document.getElementById('payForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const method = formData.get('pay_method');
        const salaryId = formData.get('salary_id');

        if (method === 'titip' && !selectedUserId && titipInput.value.trim() === '') {
            alert('Silakan pilih user terlebih dahulu dari dropdown pencarian.');
            return;
        }

        const submitData = {
            salary_id: salaryId,
            pay_method: method,
            titip_to: selectedUserId || null
        };

        fetch('{{ route("dashboard.salary.pay") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(submitData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    closePayModal();
                    location.reload();
                } else {
                    alert('❌ ' + (data.message || 'Terjadi kesalahan'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Terjadi kesalahan saat memproses pembayaran.');
            });
    });

    // range selector
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
        toggleCustom(); 
    });

    // DataTables
    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('#salaryTable').DataTable({
                order: [[3, 'desc']],
                pageLength: 10,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
                },
                footerCallback: function(row, data, start, end, display) {
                    const api = this.api();
                    const totalBonus = api
                        .column(4, { page: 'current' })
                        .data()
                        .reduce(function(a, b) {
                            const x = typeof a === 'string' ? a.replace(/[^0-9.-]+/g, '') : a;
                            const y = typeof b === 'string' ? b.replace(/[^0-9.-]+/g, '') : b;
                            return Number(x) + Number(y);
                        }, 0);
                    $('#totalBonus').html('$ ' + totalBonus.toLocaleString('id-ID'));
                }
            });
        }
    });

    // autoAlert
    $(document).ready(function() {
        const alertBox = document.getElementById('autoAlert');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.transition = 'opacity 0.4s ease, max-height 0.4s ease';
                alertBox.style.maxHeight = '0';
                alertBox.style.padding = '0';
                setTimeout(() => { alertBox.remove(); }, 500);
            }, 5000);
        }
    });
</script>
@endpush
