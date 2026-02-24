@extends('layouts.app')

@section('content')
<section class="content">
    <!-- ===== CONTENT ===== -->
    <div class="page" style="max-width:1200px;margin:auto;">

        <!-- NOTIFIKASI MENGAMBANG -->
        <div class="activity-feed-container">

            <audio id="activitySound" preload="auto">
                <source src="/assets/legacy/sound/activity.mp3" type="audio/mpeg">
            </audio>

            <div class="activity-feed-card">

                <!-- HEADER -->
                <div class="activity-feed-header">
                    <span class="activity-feed-title">📌 Activity</span>

                    <!-- 🔊 TOMBOL MUTE -->
                    <button
                        id="btnToggleActivitySound"
                        class="activity-sound-btn"
                        title="Matikan suara activity"
                        aria-label="Toggle suara activity">
                        🔊
                    </button>

                    <!-- ❌ TOMBOL CLOSE -->
                    <button
                        id="btnCloseActivity"
                        class="activity-feed-close"
                        title="Tutup Activity"
                        aria-label="Tutup Activity">
                        ✖
                    </button>
                </div>

                <!-- LIST -->
                <div class="activity-feed-list" id="activityFeedList"></div>

            </div>
        </div>

        <h1>Rekap Farmasi EMS</h1>

        <div id="localClock" style="font-size:13px;color:#9ca3af;margin-bottom:6px;"></div>

        <p style="font-size:13px;color:#9ca3af;margin-bottom:16px;">
            Input penjualan Bandage / IFAKS / Painkiller dengan batas harian per konsumen.
        </p>

        <!-- NOTIFIKASI -->
        @if(!empty($flashMessages))
            @foreach ($flashMessages as $m)
                <div class="alert alert-info">{{ $m }}</div>
            @endforeach
        @endif
        @if(!empty($flashWarnings))
            @foreach ($flashWarnings as $w)
                <div class="alert alert-warning">{{ $w }}</div>
            @endforeach
        @endif
        @if(!empty($flashErrors))
            @foreach ($flashErrors as $e)
                <div class="alert alert-error">{{ $e }}</div>
            @endforeach
        @endif

        @if ($medicName)
            <div class="card card-online-medics">
                <div class="card-header">
                    👨‍⚕️ Medis Online Hari Ini
                    <span id="totalMedicsBadge"
                        style="
                            margin-left:8px;
                            padding:2px 8px;
                            border-radius:999px;
                            background:#dcfce7;
                            color:#166534;
                            font-size:12px;
                            font-weight:700;
                        ">
                        0 orang
                    </span>
                    <small style="display:block;font-weight:500;color:#64748b;margin-top:4px;">
                        (prioritas penjualan paling sedikit di sortir paling atas)
                    </small>
                </div>

                <div class="online-medics-list" id="onlineMedicsContainer">
                    <!-- Dinamis via AJAX -->
                </div>
            </div>

            <!-- Card Input Transaksi -->
            <div class="card">
                <div class="card-header card-header-actions card-header-flex">
                    <div class="card-header-actions-title">
                        Input Transaksi Baru
                    </div>
                </div>

                <div class="medic-info">
                    <div class="medic-name">
                        Anda telah login sebagai
                        <strong>{{ $medicName }}</strong>
                        <span class="medic-role">({{ $medicJabatan }})</span>
                    </div>

                    <div class="medic-status">
                        <span id="farmasiStatusBadge"
                            data-status="offline"
                            class="status-badge status-offline"
                            style="cursor:pointer;"
                            title="Klik untuk ubah status">
                            <span class="dot"></span>
                            <span id="farmasiStatusText">OFFLINE</span>
                        </span>
                    </div>
                </div>

                <div id="cooldownNotice"
                    style="
                        display:none;
                        margin:10px 0;
                        padding:12px;
                        border-radius:10px;
                        background:#eff6ff;
                        border:1px solid #93c5fd;
                        font-size:14px;
                        color:#1e3a8a;
                    ">
                </div>

                <div id="fairnessNotice"
                    style="
                        display:none;
                        margin:10px 0;
                        padding:12px;
                        border-radius:10px;
                        background:#fff7ed;
                        border:1px solid #fdba74;
                        font-size:14px;
                        color:#9a3412;
                    ">
                </div>

                <div id="consumerNotice"
                    style="
                        display:none;
                        margin:10px 0;
                        padding:12px;
                        border-radius:10px;
                        background:#fef2f2;
                        border:1px solid #fecaca;
                        font-size:14px;
                        color:#7f1d1d;
                    ">
                </div>

                <form method="post" id="saleForm" action="{{ route('dashboard.rekap_farmasi.store') }}">
                    @csrf
                    <input type="hidden" name="auto_merge" id="auto_merge" value="0">
                    <input type="hidden" name="merge_targets" id="merge_targets">
                    <input type="hidden" name="action" value="add_sale">
                    <input type="hidden" name="tx_token" value="{{ session('tx_token') }}">
                    <input type="hidden" name="force_overlimit" id="force_overlimit" value="0">
                    
                    <div class="row-form-2">
                        <div class="col">
                            <label>Nama Konsumen</label>
                            <input type="text" name="consumer_name" id="consumerNameInput" list="consumer-list" required>
                            <div id="similarConsumerBox"
                                style="display:none;margin-top:6px;
                                background:#fff7ed;
                                border:1px solid #fdba74;
                                border-radius:10px;
                                padding:10px;
                                font-size:13px;">
                            </div>
                            <datalist id="consumer-list">
                                @foreach ($consumerNames as $cn)
                                    <option value="{{ $cn }}"></option>
                                @endforeach
                            </datalist>
                            <small>
                                Ketik nama sesuai KTP
                            </small>
                        </div>
                        <div class="col">
                            <label>Paket A / B (Combo)</label>
                            <select name="package_main" id="pkg_main">
                                <option value="">-- Tidak Pakai Paket A/B --</option>
                                @foreach ($paketAB as $pkg)
                                    <option value="{{ $pkg->id }}">
                                        {{ $pkg->name }} ({{ (int)$pkg->price }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row-form-2">
                        <div class="col">
                            <label>Paket Bandage</label>
                            <select name="package_bandage" id="pkg_bandage">
                                <option value="">-- Tidak pilih paket Bandage --</option>
                                @foreach ($bandagePkg as $pkg)
                                    <option value="{{ $pkg->id }}">
                                        {{ $pkg->name }} ({{ (int)$pkg->price }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <label>Paket IFAKS</label>
                            <select name="package_ifaks" id="pkg_ifaks">
                                <option value="">-- Tidak pilih paket IFAKS --</option>
                                @foreach ($ifaksPkg as $pkg)
                                    <option value="{{ $pkg->id }}">
                                        {{ $pkg->name }} ({{ (int)$pkg->price }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col">
                            <label>Paket Painkiller</label>
                            <select name="package_painkiller" id="pkg_painkiller">
                                <option value="">-- Tidak pilih paket Painkiller --</option>
                                @foreach ($painPkg as $pkg)
                                    <option value="{{ $pkg->id }}">
                                        {{ $pkg->name }} ({{ (int)$pkg->price }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="total-item-info">
                        <strong>Total item terpilih:</strong>
                        Bandage (<span id="priceBandage">-</span>/pcs):
                        <span id="totalBandage">0</span>,
                        IFAKS (<span id="priceIfaks">-</span>/pcs):
                        <span id="totalIfaks">0</span>,
                        Painkiller (<span id="pricePainkiller">-</span>/pcs):
                        <span id="totalPainkiller">0</span>,
                        Bonus 40% (estimasi): <span id="totalBonus">0</span>
                    </div>

                    <div class="total-display">
                        <div class="total-display-label">Total yang harus dibayar</div>
                        <div class="total-amount" id="totalPriceDisplay">$ 0</div>
                    </div>

                    <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="button" id="btnSubmit" class="btn-success" onclick="handleSaveClick();">
                            Simpan Transaksi
                        </button>
                        <button type="button" class="btn-secondary" onclick="clearFormInputs();">
                            Clear
                        </button>
                    </div>
                </form>
            </div>

            <!-- TOTAL TRANSAKSI HARI INI -->
            <div class="card">
                <h3 style="font-size:15px;margin:8px 0;">📊 Total Transaksi Hari Ini</h3>
                @if ($todayStats && $todayStats->total_transaksi > 0)
                    <div class="table-wrapper table-wrapper-sm">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Total Transaksi</th>
                                    <th>Total Harga</th>
                                    <th>Bonus (40%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ (int)$todayStats->total_transaksi }}</td>
                                    <td>{{ dollar((int)$todayStats->total_harga) }}</td>
                                    <td>{{ dollar((int)$todayStats->bonus_40) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <p style="font-size:13px;color:#9ca3af;">Belum ada transaksi hari ini.</p>
                @endif
            </div>

            <!-- Card Filter -->
            <div class="card">
                <div class="card-header">Filter Tanggal & Transaksi</div>
                <form method="get" style="margin-bottom:10px;">
                    <div class="row-form-2">
                        <div class="col">
                            <label>Rentang Tanggal</label>
                            <select name="range" id="rangeSelect">
                                <option value="today" {{ $range === 'today' ? 'selected' : '' }}>Hari ini</option>
                                <option value="yesterday" {{ $range === 'yesterday' ? 'selected' : '' }}>Kemarin</option>
                                <option value="last7" {{ $range === 'last7' ? 'selected' : '' }}>7 hari terakhir</option>
                                @foreach(['week1', 'week2', 'week3', 'week4'] as $wk)
                                    <option value="{{ $wk }}" {{ $range === $wk ? 'selected' : '' }}>
                                        Minggu {{ substr($wk, 4) }} ({{ formatTanggalIndo($weeks[$wk]['start']) }})
                                    </option>
                                @endforeach
                                <option value="custom" {{ $range === 'custom' ? 'selected' : '' }}>Custom</option>
                            </select>
                        </div>
                    </div>
                    <div class="row-form-2 {{ $range !== 'custom' ? 'hidden' : '' }}" id="customDateRow">
                        <div class="col">
                            <label>Dari tanggal</label>
                            <input type="date" name="from" value="{{ request('from') }}">
                        </div>
                        <div class="col">
                            <label>Sampai tanggal</label>
                            <input type="date" name="to" value="{{ request('to') }}">
                        </div>
                    </div>
                    <div style="margin-top:8px;">
                        <button type="submit" class="btn-secondary">Terapkan Filter</button>
                    </div>
                </form>
                <p style="font-size:13px;color:#9ca3af;margin-top:0;">Rentang aktif: <strong>{{ $rangeLabel }}</strong></p>
            </div>

            <!-- Rekapan Bonus -->
            <div class="card">
                <h3 style="font-size:15px;margin:8px 0;">Rekapan Bonus Medis (berdasarkan filter tanggal)</h3>
                @if ($singleMedicStats)
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
                                    <td>{{ dollar((int)$singleMedicStats->bonus_40) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @else
                    <p style="font-size:13px;color:#9ca3af;margin-bottom:12px;">Belum ada data untuk petugas medis aktif pada rentang ini.</p>
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
                        <input type="hidden" name="from" value="{{ request('from') }}">
                        <input type="hidden" name="to" value="{{ request('to') }}">
                        @if ($showAll)
                            <button type="submit" class="btn-secondary">Kembali (Hanya Medis Aktif)</button>
                        @else
                            <input type="hidden" name="show_all" value="1">
                            <button type="submit" class="btn-secondary">Tampilkan Semua Data</button>
                        @endif
                    </form>
                </div>

                <form method="post" action="{{ route('dashboard.rekap_farmasi.destroy_bulk') }}" id="bulkDeleteForm" onsubmit="return confirmBulkDelete();">
                    @csrf
                    <div class="table-wrapper">
                        <table id="salesTable">
                            <thead>
                                <tr>
                                    <th style="width:32px;text-align:center;"><input type="checkbox" id="selectAll"></th>
                                    <th>Waktu</th>
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
                                    <th></th><th></th><th></th><th></th><th></th>
                                </tr>
                            </tfoot>
                            <tbody>
                                @foreach ($filteredSales as $s)
                                    @php($bonus = (int)floor(((int)$s->price) * 0.4))
                                    <tr>
                                        <td style="text-align:center;">
                                            @if ($s->medic_user_id == session('user_rh.id'))
                                                <input type="checkbox" class="row-check" name="sale_ids[]" value="{{ $s->id }}">
                                            @else
                                                <span style="font-size:11px;color:#6b7280;">-</span>
                                            @endif
                                        </td>
                                        <td data-order="{{ strtotime($s->created_at) }}">{{ formatTanggalID($s->created_at) }}</td>
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
            </div>
        @endif
    </div>

    <!-- Modal Force Offline -->
    <div id="emsForceModal" class="ems-modal-overlay" style="display:none;">
        <div class="ems-modal-card">
            <h4>🛑 Force Offline Medis</h4>
            <p id="emsForceDesc"></p>
            <div style="text-align:left;margin-bottom:18px;">
                <label style="font-size:13px;font-weight:700;">Alasan Force Offline</label>
                <textarea id="emsForceReason" placeholder="Sudah tidak duty" style="width:100%;min-height:80px;margin-top:6px;padding:10px 12px;border-radius:12px;border:1px solid #cbd5e1;font-size:14px;"></textarea>
            </div>
            <div class="modal-actions force-offline-actions">
                <button type="button" class="ems-btn-cancel">Batal</button>
                <button type="button" class="ems-btn-confirm">Force Offline</button>
            </div>
        </div>
    </div>

    <script>
        // Global variables for mirrored JS
        const EXISTING_CONSUMERS = {!! json_encode($consumerNames) !!};
        const SHOULD_CLEAR_FORM = {{ $shouldClearForm ? 'true' : 'false' }};
        const MAX_BANDAGE = 30;
        const MAX_IFAKS = 10;
        const MAX_PAINKILLER = 10;
        const PACKAGES = {!! json_encode($packagesById) !!};
        const PRICE_PER_PCS = {!! json_encode($pricePerPcs) !!};
        const DAILY_TOTALS = {!! json_encode($dailyTotalsJS) !!};
        const DAILY_DETAIL = {!! json_encode($dailyDetailJS) !!};
        const STORAGE_KEY = 'farmasi_ems_form';
        
        let IS_OVER_LIMIT = false;
        let CONSUMER_LOCK = false;
        let LAST_CONSUMER_NAME = '';
        const FAIRNESS_STATE = { locked: false };

        function normalizeName(str) { return (str || '').toLowerCase().replace(/[^a-z\s]/g, '').replace(/\s+/g, ' ').trim(); }
        function escapeHtml(str) { return (str || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
        function formatDollar(num) { return '$ ' + Number(num || 0).toLocaleString('id-ID'); }

        function showFairnessNotice(html) {
            const box = document.getElementById('fairnessNotice');
            if (box) { box.innerHTML = html; box.style.display = 'block'; }
        }
        function clearFairnessNotice() {
            const box = document.getElementById('fairnessNotice');
            if (box) { box.style.display = 'none'; }
        }
        function showConsumerNotice(html) {
            const box = document.getElementById('consumerNotice');
            if (box) { box.innerHTML = html; box.style.display = 'block'; CONSUMER_LOCK = true; }
        }
        function clearConsumerNotice() {
            const box = document.getElementById('consumerNotice');
            if (box) { box.style.display = 'none'; CONSUMER_LOCK = false; }
        }

        function recalcTotals() {
            const ids = [];
            ['pkg_main', 'pkg_bandage', 'pkg_ifaks', 'pkg_painkiller'].forEach(id => {
                const el = document.getElementById(id);
                if (el && el.value) ids.push(el.value);
            });

            let b = 0, i = 0, p = 0, price = 0;
            ids.forEach(id => {
                const pkg = PACKAGES[id];
                if (pkg) { b += pkg.bandage; i += pkg.ifaks; p += pkg.painkiller; price += pkg.price; }
            });

            IS_OVER_LIMIT = (b > MAX_BANDAGE || i > MAX_IFAKS || p > MAX_PAINKILLER);
            document.getElementById('totalBandage').textContent = b;
            document.getElementById('totalIfaks').textContent = i;
            document.getElementById('totalPainkiller').textContent = p;
            document.getElementById('totalPriceDisplay').textContent = formatDollar(price);
            document.getElementById('totalBonus').textContent = formatDollar(Math.floor(price * 0.4));

            const cname = document.getElementById('consumerNameInput').value.trim();
            if (cname !== LAST_CONSUMER_NAME) { clearConsumerNotice(); LAST_CONSUMER_NAME = cname; }
            if (!cname || cname.length < 3) return;

            const detail = DAILY_DETAIL[cname.toLowerCase()] || [];
            if (detail.length > 0) {
                let html = `🚫 <strong>${escapeHtml(cname)}</strong> sudah transaksi hari ini.<br><ul>`;
                detail.forEach(d => { html += `<li>${escapeHtml(d.package)} (${d.time})</li>`; });
                html += '</ul>';
                showConsumerNotice(html);
            }
        }

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
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return;
            const data = JSON.parse(raw);
            document.getElementById('consumerNameInput').value = data.consumer_name || '';
            document.getElementById('pkg_main').value = data.pkg_main || '';
            document.getElementById('pkg_bandage').value = data.pkg_bandage || '';
            document.getElementById('pkg_ifaks').value = data.pkg_ifaks || '';
            document.getElementById('pkg_painkiller').value = data.pkg_painkiller || '';
        }

        function clearFormInputs() {
            document.getElementById('saleForm').reset();
            localStorage.removeItem(STORAGE_KEY);
            clearConsumerNotice();
            recalcTotals();
        }

        function formatConsumerName(name) {
            return (name || '').toLowerCase().replace(/\s+/g, ' ').trim().split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        }

        function handleSaveClick() {
            const input = document.getElementById('consumerNameInput');
            input.value = formatConsumerName(input.value);
            if (CONSUMER_LOCK) return alert('🚫 Konsumen sudah transaksi hari ini.');
            
            let msg = IS_OVER_LIMIT ? "⚠️ Melebihi batas harian. Tetap simpan?" : "Simpan transaksi ke database?";
            if (confirm(msg)) {
                document.getElementById('force_overlimit').value = IS_OVER_LIMIT ? '1' : '0';
                document.getElementById('btnSubmit').disabled = true;
                document.getElementById('saleForm').submit();
            }
        }

        function confirmBulkDelete() {
            const checked = document.querySelectorAll('.row-check:checked').length;
            if (!checked) return alert('Pilih data dulu.');
            return confirm(`Hapus ${checked} transaksi?`);
        }

        function updateLocalClock() {
            const now = new Date();
            document.getElementById('localClock').textContent = now.toLocaleDateString('id-ID', {weekday:'long', year:'numeric', month:'long', day:'numeric'}) + ' • ' + now.toLocaleTimeString('id-ID', {hour12:false});
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateLocalClock(); setInterval(updateLocalClock, 1000);
            if (SHOULD_CLEAR_FORM) localStorage.removeItem(STORAGE_KEY); else restoreFormState();
            
            document.getElementById('consumerNameInput').addEventListener('input', () => { saveFormState(); recalcTotals(); });
            ['pkg_main', 'pkg_bandage', 'pkg_ifaks', 'pkg_painkiller'].forEach(id => {
                document.getElementById(id).addEventListener('change', () => { saveFormState(); recalcTotals(); });
            });

            document.getElementById('rangeSelect').addEventListener('change', function() {
                document.getElementById('customDateRow').classList.toggle('hidden', this.value !== 'custom');
            });

            document.getElementById('priceBandage').textContent = formatDollar(PRICE_PER_PCS.bandage);
            document.getElementById('priceIfaks').textContent = formatDollar(PRICE_PER_PCS.ifaks);
            document.getElementById('pricePainkiller').textContent = formatDollar(PRICE_PER_PCS.painkiller);
            recalcTotals();

            if (window.jQuery && $.fn.DataTable) {
                const table = $('#salesTable').DataTable({
                    order: [[1, 'desc']],
                    language: { url: "https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json" },
                    footerCallback: function() {
                        const api = this.api();
                        const intVal = i => typeof i === 'string' ? i.replace(/[^\d]/g, '')*1 : typeof i === 'number' ? i : 0;
                        [6,7,8].forEach(col => $(api.column(col).footer()).html(api.column(col, {search:'applied'}).data().reduce((a,b)=>intVal(a)+intVal(b),0)));
                        [9,10].forEach(col => $(api.column(col).footer()).html(formatDollar(api.column(col, {search:'applied'}).data().reduce((a,b)=>intVal(a)+intVal(b),0))));
                    }
                });
                $('#selectAll').on('click', function() { $('.row-check').prop('checked', this.checked); $('#btnBulkDelete').prop('disabled', !this.checked); });
                $(document).on('change', '.row-check', () => { $('#btnBulkDelete').prop('disabled', $('.row-check:checked').length === 0); });
            }
        });
    </script>

    <script>
        // Realtime Polling Medics & Activities (Mirrored Logic)
        (function() {
            let lastDataHash = '';
            let baseTimestamp = {};

            async function fetchMedics() {
                try {
                    const res = await fetch('/actions/get_online_medics.php', { cache: 'no-store' });
                    const data = await res.json();
                    const hash = JSON.stringify(data.map(m => m.user_id + m.total_transaksi));
                    if (hash === lastDataHash) return;
                    lastDataHash = hash;
                    
                    const container = document.getElementById('onlineMedicsContainer');
                    container.innerHTML = '';
                    document.getElementById('totalMedicsBadge').textContent = data.length + ' orang';
                    
                    data.forEach(m => {
                        const row = document.createElement('div');
                        row.className = 'online-medic-row';
                        row.innerHTML = `
                            <div class="medic-main">
                                <strong>${escapeHtml(m.medic_name)}</strong>
                                <span class="weekly-badge">Minggu ini: ${m.weekly_transaksi} trx</span>
                                <span class="weekly-online" data-seconds="${m.weekly_online_seconds}" data-user-id="${m.user_id}">⏱️ Online: ${m.weekly_online_text}</span>
                                <div class="medic-role">${escapeHtml(m.medic_jabatan)}</div>
                                <button class="btn-force-offline" data-user-id="${m.user_id}" data-name="${escapeHtml(m.medic_name)}" data-jabatan="${escapeHtml(m.medic_jabatan)}">🛑 Force Offline</button>
                            </div>
                            <div class="medic-stats">
                                <div class="tx">${m.total_transaksi} trx</div>
                                <div class="amount">${formatDollar(m.total_pendapatan)}</div>
                                <div class="bonus" style="font-size:12px;color:#16a34a;">Bonus: ${formatDollar(m.bonus_40)}</div>
                            </div>`;
                        container.appendChild(row);
                    });
                } catch (e) {}
            }

            function updateDurations() {
                document.querySelectorAll('.weekly-online').forEach(span => {
                    const id = span.dataset.userId || 'unknown';
                    const baseSeconds = parseInt(span.dataset.seconds || 0, 10) || 0;

                    if (!baseTimestamp[id]) {
                        baseTimestamp[id] = { start: Date.now(), base: baseSeconds };
                    }

                    const elapsed = Math.floor((Date.now() - baseTimestamp[id].start) / 1000);
                    const total = baseTimestamp[id].base + elapsed;

                    const hours = Math.floor(total / 3600);
                    const minutes = Math.floor((total % 3600) / 60);
                    const seconds = total % 60;

                    span.textContent = `⏱️ Online: ${hours}j ${minutes}m ${seconds}d`;
                });
            }

            fetchMedics(); setInterval(fetchMedics, 2000); setInterval(updateDurations, 1000);
        })();

        // Activity Feed Polling
        (function() {
            const list = document.getElementById('activityFeedList');
            let lastHash = '';
            async function fetchActivities() {
                try {
                    const res = await fetch('/actions/get_activities.php', { cache: 'no-store', credentials: 'same-origin' });
                    const contentType = (res.headers.get('content-type') || '').toLowerCase();
                    if (!res.ok || !contentType.includes('application/json')) return;
                    const data = await res.json();
                    const hash = JSON.stringify(data.map(a => a.id));
                    if (hash === lastHash) return;
                    lastHash = hash;
                    list.innerHTML = '';
                    data.forEach(a => {
                        const item = document.createElement('div');
                        item.className = 'activity-feed-item';
                        item.innerHTML = `<div class="activity-icon type-${a.type}">${a.type==='transaction'?'💰':'📌'}</div>
                            <div class="activity-content">
                                <div class="activity-medic">${escapeHtml(a.medic_name)}</div>
                                <div class="activity-description">${escapeHtml(a.description)}</div>
                            </div>`;
                        list.appendChild(item);
                    });
                } catch (e) {}
            }
            fetchActivities(); setInterval(fetchActivities, 3000);
            
            document.getElementById('btnCloseActivity').onclick = () => {
                document.querySelector('.activity-feed-container').style.display = 'none';
                sessionStorage.setItem('farmasi_activity_closed', '1');
            };
            if (sessionStorage.getItem('farmasi_activity_closed') === '1') document.querySelector('.activity-feed-container').style.display = 'none';
        })();
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const audio = document.getElementById('activitySound');
            const btn = document.getElementById('btnToggleActivitySound');

            if (!audio || !btn) return;

            let muted = localStorage.getItem('activity_sound_muted') === '1';

            function syncUI() {
                audio.muted = muted;
                btn.textContent = muted ? '🔇' : '🔊';
                btn.classList.toggle('is-muted', muted);
                btn.title = muted ? 'Aktifkan suara activity' : 'Matikan suara activity';
            }

            syncUI();

            btn.addEventListener('click', () => {
                muted = !muted;
                localStorage.setItem('activity_sound_muted', muted ? '1' : '0');
                syncUI();
            });
        });
    </script>

    <script>
        // Status & Cooldown Polling
        (function() {
            async function checkStatus() {
                const res = await fetch('/actions/get_farmasi_status.php', { cache: 'no-store', credentials: 'same-origin' });
                const contentType = (res.headers.get('content-type') || '').toLowerCase();
                if (!res.ok || !contentType.includes('application/json')) return;
                const json = await res.json();
                const badge = document.getElementById('farmasiStatusBadge');
                const text = document.getElementById('farmasiStatusText');
                badge.className = 'status-badge status-' + json.status;
                badge.dataset.status = json.status;
                text.textContent = json.status.toUpperCase();
            }
            checkStatus(); setInterval(checkStatus, 5000);

            const badge = document.getElementById('farmasiStatusBadge');
            badge.onclick = async () => {
                const next = badge.dataset.status === 'online' ? 'offline' : 'online';
                if (confirm(`Yakin ingin ${next.toUpperCase()}?`)) {
                    await fetch('/actions/toggle_farmasi_status.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ status: next })
                    });
                    checkStatus();
                }
            };

            async function checkCooldown() {
                const res = await fetch('/actions/get_global_cooldown.php', { cache: 'no-store', credentials: 'same-origin' });
                const contentType = (res.headers.get('content-type') || '').toLowerCase();
                if (!res.ok || !contentType.includes('application/json')) return;
                const data = await res.json();
                const box = document.getElementById('cooldownNotice');
                const btn = document.getElementById('btnSubmit');
                if (data.active) {
                    box.innerHTML = `⏳ <strong>Cooldown</strong>: Tunggu ${data.remain} detik.`;
                    box.style.display = 'block'; btn.disabled = true;
                } else {
                    box.style.display = 'none'; btn.disabled = false;
                }
            }
            checkCooldown(); setInterval(checkCooldown, 2000);
        })();
    </script>

    <script>
        // Force Offline Modal
        (function() {
            const modal = document.getElementById('emsForceModal');
            const reasonInput = document.getElementById('emsForceReason');
            let targetId = null;

            document.addEventListener('click', e => {
                const btn = e.target.closest('.btn-force-offline');
                if (!btn) return;
                targetId = btn.dataset.userId;
                document.getElementById('emsForceDesc').innerHTML = `Medis: <strong>${btn.dataset.name}</strong>`;
                modal.style.display = 'flex';
            });

            modal.querySelector('.ems-btn-cancel').onclick = () => modal.style.display = 'none';
            modal.querySelector('.ems-btn-confirm').onclick = async () => {
                const reason = reasonInput.value.trim();
                if (reason.length < 5) return alert('Alasan min 5 karakter.');
                const res = await fetch('/actions/force_offline_medis.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ target_user_id: targetId, reason: reason })
                });
                const json = await res.json();
                if (json.success) { modal.style.display = 'none'; alert('Berhasil!'); }
            };
        })();
    </script>
</section>
@endsection
