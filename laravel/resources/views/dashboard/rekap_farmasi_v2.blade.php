@extends('layouts.app')

@section('content')
<section class="content">
    <!-- ===== CONTENT ===== -->
    <div class="page" style="max-width:1200px;margin:auto;">

        <h1>Rekap Farmasi EMS <span style="font-size:14px;color:#9ca3af;font-weight:normal;">V2 (Advanced)</span></h1>

        <div id="localClock" style="font-size:13px;color:#9ca3af;margin-bottom:6px;"></div>

        <p style="font-size:13px;color:#9ca3af;margin-bottom:16px;">
            Input penjualan Bandage / IFAKS / Painkiller dengan batas harian per konsumen.
            <strong>V2:</strong> Integrasi Identity OCR, Auto-merge konsumen, Validasi advanced.
        </p>

        <!-- NOTIFIKASI -->
        @if(!empty($flashMessages))
            @foreach($flashMessages as $m)
                <div class="alert alert-info">{{ $m }}</div>
            @endforeach
        @endif
        @if(!empty($flashWarnings))
            @foreach($flashWarnings as $w)
                <div class="alert alert-warning">{{ $w }}</div>
            @endforeach
        @endif
        @if(!empty($flashErrors))
            @foreach($flashErrors as $e)
                <div class="alert alert-error">{{ $e }}</div>
            @endforeach
        @endif

        @if($medicName)
            <!-- Card Input Transaksi -->
            <div class="card">
                <div class="card-header card-header-actions card-header-flex">
                    <div class="card-header-actions-title">
                        Input Transaksi Baru (V2)
                    </div>

                    <a href="{{ route('dashboard.rekap_farmasi') }}" class="btn-secondary btn-sm" title="Buka Rekap Farmasi Versi 1">
                        ⏪ Versi Lama
                    </a>
                </div>

                <p style="margin:4px 0 8px;">
                    Anda telah login sebagai
                    <strong>{{ $medicName }}</strong>
                    ({{ $medicJabatan }})
                </p>

                <form method="post" id="saleForm" action="{{ route('dashboard.rekap_farmasi_v2.store') }}">
                    @csrf
                    <!-- HIDDEN SYSTEM FIELDS -->
                    <input type="hidden" name="action" value="add_sale">
                    <input type="hidden" name="tx_token" value="{{ $txToken }}">
                    <input type="hidden" name="auto_merge" id="auto_merge" value="0">
                    <input type="hidden" name="merge_targets" id="merge_targets">
                    <input type="hidden" name="force_overlimit" id="force_overlimit" value="0">

                    <!-- 🔑 IDENTITAS (DARI OCR) -->
                    <input type="hidden" name="identity_id" id="identity_id">

                    <!-- KONSUMEN -->
                    <div class="row-form-2">
                        <div class="col">
                            <label>Identitas Konsumen</label>

                            <!-- Tombol OCR -->
                            <div style="margin-bottom:6px;">
                                <button type="button" class="btn-secondary" onclick="openIdentityScan()">
                                    📷 Scan Identitas (OCR)
                                </button>
                            </div>

                            <!-- Nama Konsumen (AUTO dari OCR) -->
                            <input type="text" name="consumer_name" id="consumerNameInput"
                                placeholder="Scan identitas terlebih dahulu" required readonly
                                style="background:#f9fafb;cursor:not-allowed;">

                            <small style="color:#92400e;">
                                Nama akan terisi otomatis dari hasil scan identitas (KTP / ID).
                                Input manual tidak diperbolehkan.
                            </small>
                        </div>

                        <!-- PAKET A / B -->
                        <div class="col">
                            <label>Paket A / B (Combo)</label>
                            <select name="package_main" id="pkg_main">
                                <option value="">-- Tidak Pakai Paket A/B --</option>
                                @foreach($paketAB as $pkg)
                                    <option value="{{ $pkg->id }}">
                                        {{ $pkg->name }}
                                        ({{ dollar((int)$pkg->price) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- PAKET SATUAN -->
                    <div class="row-form-2">
                        <div class="col">
                            <label>Paket Bandage</label>
                            <select name="package_bandage" id="pkg_bandage">
                                <option value="">-- Tidak pilih paket Bandage --</option>
                                @foreach($bandagePackages as $pkg)
                                    <option value="{{ $pkg->id }}">
                                        {{ $pkg->name }}
                                        ({{ dollar((int)$pkg->price) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col">
                            <label>Paket IFAKS</label>
                            <select name="package_ifaks" id="pkg_ifaks">
                                <option value="">-- Tidak pilih paket IFAKS --</option>
                                @foreach($ifaksPackages as $pkg)
                                    <option value="{{ $pkg->id }}">
                                        {{ $pkg->name }}
                                        ({{ dollar((int)$pkg->price) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col">
                            <label>Paket Painkiller</label>
                            <select name="package_painkiller" id="pkg_painkiller">
                                <option value="">-- Tidak pilih paket Painkiller --</option>
                                @foreach($painkillerPackages as $pkg)
                                    <option value="{{ $pkg->id }}">
                                        {{ $pkg->name }}
                                        ({{ dollar((int)$pkg->price) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- INFO TOTAL -->
                    <div class="total-item-info">
                        <strong>Total item terpilih:</strong>
                        Bandage (<span id="priceBandage">-</span>/pcs):
                        <span id="totalBandage">0</span>,
                        IFAKS (<span id="priceIfaks">-</span>/pcs):
                        <span id="totalIfaks">0</span>,
                        Painkiller (<span id="pricePainkiller">-</span>/pcs):
                        <span id="totalPainkiller">0</span>,
                        Bonus 40% (estimasi):
                        <span id="totalBonus">0</span>
                    </div>

                    <!-- WARNING LIMIT -->
                    <div id="limitWarning" style="margin-top:6px;font-size:13px;color:#f97316;display:none;">
                    </div>

                    <!-- TOTAL DISPLAY -->
                    <div class="total-display">
                        <div class="total-display-label">Total yang harus dibayar</div>
                        <div class="total-amount" id="totalPriceDisplay">$ 0</div>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" id="btnSubmit" class="btn-success" onclick="handleSaveClick();">
                            Simpan Transaksi
                        </button>

                        <button type="button" class="btn-secondary" onclick="clearFormInputs();">
                            Clear
                        </button>
                    </div>

                </form>
            </div>

            <!-- Card Filter & Transaksi -->
            <div class="card">
                <div class="card-header">Filter Tanggal & Transaksi</div>

                <!-- Form Filter (GET) -->
                <form method="get" style="margin-bottom:10px;">
                    <div class="row-form-2">
                        <div class="col">
                            <label>Rentang Tanggal</label>
                            <select name="range" id="rangeSelect">
                                <option value="today" {{ $range === 'today' ? 'selected' : '' }}>Hari ini</option>
                                <option value="yesterday" {{ $range === 'yesterday' ? 'selected' : '' }}>Kemarin</option>
                                <option value="last7" {{ $range === 'last7' ? 'selected' : '' }}>7 hari terakhir</option>
                                @foreach(['week1', 'week2', 'week3', 'week4'] as $wk)
                                    @if(isset($weeks[$wk]))
                                    <option value="{{ $wk }}" {{ $range === $wk ? 'selected' : '' }}>
                                        Minggu {{ substr($wk, 4) }} ({{ formatTanggalIndo($weeks[$wk]['start']) }})
                                    </option>
                                    @endif
                                @endforeach
                                <option value="custom" {{ $range === 'custom' ? 'selected' : '' }}>Custom (pilih tanggal)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row-form-2 {{ $range !== 'custom' ? 'hidden' : '' }}" id="customDateRow">
                        <div class="col">
                            <label>Dari tanggal</label>
                            <input type="date" name="from" value="{{ $fromDateInput }}">
                        </div>
                        <div class="col">
                            <label>Sampai tanggal</label>
                            <input type="date" name="to" value="{{ $toDateInput }}">
                        </div>
                    </div>

                    @if($showAll)
                        <input type="hidden" name="show_all" value="1">
                    @endif

                    <div style="margin-top:8px;">
                        <button type="submit" class="btn-secondary">Terapkan Filter</button>
                    </div>
                </form>

                <p style="font-size:13px;color:#9ca3af;margin-top:0;">
                    Rentang aktif: <strong>{{ $rangeLabel }}</strong>
                </p>
            </div>

            <!-- Rekapan Bonus Medis -->
            <div class="card">
                <h3 style="font-size:15px;margin:8px 0;">Rekapan Bonus Medis (berdasarkan filter tanggal)</h3>
                @if($singleMedicStats)
                    <div class="table-wrapper table-wrapper-sm" style="margin-bottom:12px;">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Medis</th>
                                    <th>Jabatan</th>
                                    <th>Total Transaksi</th>
                                    <th>Total Item</th>
                                    <th>Total Harga</th>
                                    <th>Bonus (40%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td>{{ $singleMedicStats->medic_name }}</td>
                                    <td>{{ $singleMedicStats->medic_jabatan }}</td>
                                    <td>{{ (int)$singleMedicStats->total_transaksi }}</td>
                                    <td>{{ (int)$singleMedicStats->total_item }}</td>
                                    <td>{{ dollar((int)$singleMedicStats->total_harga) }}</td>
                                    <td>{{ dollar(floor((int)$singleMedicStats->total_harga * 0.4)) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <p style="font-size:13px;color:#9ca3af;margin-bottom:12px;">
                        Belum ada data untuk petugas medis aktif pada rentang ini.
                    </p>
                @endif
            </div>

            <!-- Tabel Transaksi -->
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin:8px 0;">
                    <div>
                        <h3 style="font-size:15px;margin:0;">Transaksi (sesuai filter)</h3>
                        <div style="font-size:11px;color:#9ca3af;margin-top:2px;">
                            Mode: <strong>{{ $showAll ? 'Semua medis' : 'Medis aktif (' . $medicName . ')' }}</strong>
                        </div>
                    </div>
                    <form method="get" style="margin:0;display:flex;gap:6px;align-items:center;">
                        <input type="hidden" name="range" value="{{ $range }}">
                        <input type="hidden" name="from" value="{{ $fromDateInput }}">
                        <input type="hidden" name="to" value="{{ $toDateInput }}">
                        @if($showAll)
                            <button type="submit" class="btn-secondary">Kembali (Hanya Medis Aktif)</button>
                        @else
                            <input type="hidden" name="show_all" value="1">
                            <button type="submit" class="btn-secondary">Tampilkan Semua Data</button>
                        @endif
                    </form>
                </div>

                @if(!$filteredSales->isEmpty())
                    <form method="post" id="bulkDeleteForm" onsubmit="return confirmBulkDelete();">
                        @csrf
                        <input type="hidden" name="action" value="delete_selected">
                        <div class="table-wrapper">
                            <table id="salesTable">
                                <thead>
                                    <tr>
                                        <th style="width:32px;text-align:center;"><input type="checkbox" id="selectAll"></th>
                                        <th>Waktu</th>
                                        <th>Citizen ID</th>
                                        <th>Nama Konsumen</th>
                                        <th>Nama Medis</th>
                                        <th>Jabatan</th>
                                        <th>Paket</th>
                                        <th>Bandage</th>
                                        <th>IFAKS</th>
                                        <th>Painkiller</th>
                                        <th>Harga</th>
                                        <th>Bonus (40%)</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th colspan="6" style="text-align:right;">TOTAL</th>
                                        <th></th><th></th><th></th><th></th><th></th><th></th>
                                    </tr>
                                </tfoot>
                                <tbody>
                                    @foreach($filteredSales as $s)
                                        @php $bonus = floor(((int)$s->price) * 0.4) @endphp
                                        <tr>
                                            <td style="text-align:center;">
                                                @if($s->medic_name === $medicName)
                                                    <input type="checkbox" class="row-check" name="sale_ids[]" value="{{ $s->id }}">
                                                @else
                                                    <span style="font-size:11px;color:#6b7280;">-</span>
                                                @endif
                                            </td>
                                            <td>{{ formatTanggalID($s->created_at) }}</td>
                                            <td>
                                                @if(!empty($s->citizen_id))
                                                    <a href="#" class="identity-link" data-identity-id="{{ $s->identity_id }}">{{ $s->citizen_id }}</a>
                                                @else
                                                    <span style="color:#9ca3af;">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $s->consumer_name }}</td>
                                            <td>{{ $s->medic_name }}</td>
                                            <td>{{ $s->medic_jabatan }}</td>
                                            <td>{{ $s->package_name }}</td>
                                            <td>{{ (int)$s->qty_bandage }}</td>
                                            <td>{{ (int)$s->qty_ifaks }}</td>
                                            <td>{{ (int)$s->qty_painkiller }}</td>
                                            <td>{{ dollar((int)$s->price) }}</td>
                                            <td>{{ dollar($bonus) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                            <button type="submit" class="btn-danger" id="btnBulkDelete" disabled>Hapus Data Terpilih</button>
                        </div>
                    </form>
                @else
                    <p style="font-size:13px;color:#9ca3af;">Belum ada transaksi pada rentang ini.</p>
                @endif
            </div>
        @else
            <p style="font-size:13px;color:#9ca3af;">Set Petugas Medis Aktif terlebih dahulu.</p>
        @endif
    </div>

    <!-- Modals -->
    <div id="identityModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99999;padding:60px 16px 16px;overflow:auto;">
        <div style="background:#fff;max-width:900px;width:100%;margin:0 auto;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.3);display:flex;flex-direction:column;max-height:calc(100vh - 120px);">
            <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
                <strong style="font-size:16px;color:#0f172a;">📷 Scan Identitas</strong>
                <button onclick="closeIdentityScan()" style="background:#ef4444;color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;">✖ Tutup</button>
            </div>
            <iframe src="{{ route('dashboard.identity_test') }}" style="width:100%;height:calc(100vh - 180px);border:none;"></iframe>
        </div>
    </div>

    <div id="identityViewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99998;padding:80px 16px 16px;overflow:auto;">
        <div style="background:#fff;max-width:900px;width:100%;margin:0 auto;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,.3);display:flex;flex-direction:column;max-height:calc(100vh - 120px);">
            <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;background:#fff;">
                <strong style="font-size:16px;color:#0f172a;">📋 Data Konsumen</strong>
                <button onclick="closeIdentityViewModal()" style="background:#ef4444;color:#fff;border:none;padding:8px 16px;border-radius:8px;cursor:pointer;">✖ Tutup</button>
            </div>
            <div id="identityViewContent" style="padding:20px;"></div>
        </div>
    </div>

    <script>
        const DAILY_TOTALS = {!! json_encode($dailyTotalsJS) !!};
        const DAILY_DETAIL = {!! json_encode($dailyDetailJS) !!};
        const PACKAGES = {!! json_encode($packagesById) !!};
        const PRICE_PER_PCS = {!! json_encode($pricePerPcs) !!};
        const SHOULD_CLEAR_FORM = {{ $shouldClearForm ? 'true' : 'false' }};
        const MAX_BANDAGE = 30, MAX_IFAKS = 10, MAX_PAINKILLER = 10;
        const STORAGE_KEY = 'farmasi_ems_form_v2';
        const CONSUMER_STORAGE_KEY = 'farmasi_ems_consumer_v2';
        let IS_OVER_LIMIT = false, ALREADY_BOUGHT_TODAY = false;

        function formatDollar(n) { return '$ ' + Number(n).toLocaleString('id-ID'); }
        function saveFormState() {
            const data = {
                consumer_name: document.getElementById('consumerNameInput').value,
                pkg_main: document.getElementById('pkg_main').value,
                pkg_bandage: document.getElementById('pkg_bandage').value,
                pkg_ifaks: document.getElementById('pkg_ifaks').value,
                pkg_painkiller: document.getElementById('pkg_painkiller').value,
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        }
        function restoreFormState() {
            const raw = localStorage.getItem(STORAGE_KEY); if (!raw) return;
            const data = JSON.parse(raw);
            document.getElementById('pkg_main').value = data.pkg_main || '';
            document.getElementById('pkg_bandage').value = data.pkg_bandage || '';
            document.getElementById('pkg_ifaks').value = data.pkg_ifaks || '';
            document.getElementById('pkg_painkiller').value = data.pkg_painkiller || '';
        }
        function recalcTotals() {
            const ids = [document.getElementById('pkg_main').value, document.getElementById('pkg_bandage').value, document.getElementById('pkg_ifaks').value, document.getElementById('pkg_painkiller').value];
            let b=0, i=0, p=0, price=0;
            ids.forEach(id => { if(PACKAGES[id]) { b+=PACKAGES[id].bandage; i+=PACKAGES[id].ifaks; p+=PACKAGES[id].painkiller; price+=PACKAGES[id].price; }});
            IS_OVER_LIMIT = (b > MAX_BANDAGE || i > MAX_IFAKS || p > MAX_PAINKILLER);
            document.getElementById('totalBandage').textContent = b;
            document.getElementById('totalIfaks').textContent = i;
            document.getElementById('totalPainkiller').textContent = p;
            document.getElementById('totalPriceDisplay').textContent = formatDollar(price);
            document.getElementById('totalBonus').textContent = formatDollar(Math.floor(price * 0.4));
            
            const cname = document.getElementById('consumerNameInput').value.trim();
            const warningBox = document.getElementById('limitWarning');
            if (!cname) { warningBox.style.display = 'none'; return; }
            const detail = DAILY_DETAIL[cname.toLowerCase()] || [];
            if (detail.length > 0) {
                let html = `🚫 <strong>${cname}</strong> sudah transaksi hari ini.<br><ul>`;
                detail.forEach(d => { html += `<li>📦 ${d.package} (${d.time})</li>`; });
                html += '</ul>';
                warningBox.innerHTML = html; warningBox.style.display = 'block';
                document.getElementById('btnSubmit').disabled = true; ALREADY_BOUGHT_TODAY = true;
            } else {
                warningBox.style.display = 'none'; document.getElementById('btnSubmit').disabled = false; ALREADY_BOUGHT_TODAY = false;
            }
        }
        function openIdentityScan() { document.getElementById('identityModal').style.display = 'block'; }
        function closeIdentityScan() { document.getElementById('identityModal').style.display = 'none'; }
        window.addEventListener('message', e => {
            if (!e.data || !e.data.identity_id) return;
            document.getElementById('identity_id').value = e.data.identity_id;
            const name = ((e.data.first_name || '') + ' ' + (e.data.last_name || '')).trim();
            const input = document.getElementById('consumerNameInput');
            input.value = name; input.readOnly = true; input.style.background = '#f0fdf4';
            localStorage.setItem(CONSUMER_STORAGE_KEY, JSON.stringify({id: e.data.identity_id, name: name}));
            closeIdentityScan(); recalcTotals();
        });
        function clearFormInputs() {
            document.getElementById('saleForm').reset();
            const input = document.getElementById('consumerNameInput');
            input.value = ''; input.readOnly = true; input.style.background = '#f9fafb';
            document.getElementById('identity_id').value = '';
            localStorage.removeItem(STORAGE_KEY); localStorage.removeItem(CONSUMER_STORAGE_KEY);
            recalcTotals();
        }
        function handleSaveClick() {
            if (ALREADY_BOUGHT_TODAY) return alert('🚫 Sudah transaksi hari ini.');
            if (confirm(IS_OVER_LIMIT ? "⚠️ Melebihi batas harian. Tetap simpan?" : "Simpan transaksi?")) {
                document.getElementById('force_overlimit').value = IS_OVER_LIMIT ? '1' : '0';
                document.getElementById('btnSubmit').disabled = true;
                document.getElementById('saleForm').submit();
            }
        }
        document.addEventListener('DOMContentLoaded', () => {
            const savedConsumer = localStorage.getItem(CONSUMER_STORAGE_KEY);
            if (savedConsumer) {
                const data = JSON.parse(savedConsumer);
                document.getElementById('identity_id').value = data.id;
                const input = document.getElementById('consumerNameInput');
                input.value = data.name; input.readOnly = true; input.style.background = '#f0fdf4';
            }
            if (SHOULD_CLEAR_FORM) localStorage.removeItem(STORAGE_KEY); else restoreFormState();
            document.getElementById('priceBandage').textContent = formatDollar(PRICE_PER_PCS.bandage);
            document.getElementById('priceIfaks').textContent = formatDollar(PRICE_PER_PCS.ifaks);
            document.getElementById('pricePainkiller').textContent = formatDollar(PRICE_PER_PCS.painkiller);
            ['pkg_main', 'pkg_bandage', 'pkg_ifaks', 'pkg_painkiller'].forEach(id => {
                document.getElementById(id).addEventListener('change', () => { saveFormState(); recalcTotals(); });
            });
            recalcTotals();
            if (window.jQuery && $.fn.DataTable) {
                $('#salesTable').DataTable({
                    order: [[1, 'desc']], language: { url: "https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json" },
                    footerCallback: function() {
                        const api = this.api(); const intVal = i => typeof i === 'string' ? i.replace(/[^\d]/g, '')*1 : typeof i === 'number' ? i : 0;
                        [7,8,9].forEach(c => $(api.column(c).footer()).html(api.column(c, {search:'applied'}).data().reduce((a,b)=>intVal(a)+intVal(b),0)));
                        [10,11].forEach(c => $(api.column(c).footer()).html(formatDollar(api.column(c, {search:'applied'}).data().reduce((a,b)=>intVal(a)+intVal(b),0))));
                    }
                });
                $('#selectAll').on('click', function() { $('.row-check').prop('checked', this.checked); $('#btnBulkDelete').prop('disabled', !this.checked); });
                $(document).on('change', '.row-check', () => { $('#btnBulkDelete').prop('disabled', $('.row-check:checked').length === 0); });
            }
        });
        function openIdentityViewModal(id) {
            const modal = document.getElementById('identityViewModal');
            modal.style.display = 'flex';
            document.getElementById('identityViewContent').innerHTML = 'Memuat...';
            fetch('/ajax/get_identity_detail.php?id=' + id).then(r => r.text()).then(h => { document.getElementById('identityViewContent').innerHTML = h; });
        }
        document.addEventListener('click', e => {
            const link = e.target.closest('.identity-link');
            if (link) { e.preventDefault(); openIdentityViewModal(link.dataset.identityId); }
            if (e.target.id === 'identityViewModal') closeIdentityViewModal();
        });
        function closeIdentityViewModal() { document.getElementById('identityViewModal').style.display = 'none'; }
    </script>
</section>
@endsection
