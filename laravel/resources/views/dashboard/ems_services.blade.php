@extends('layouts.app')

@section('content')

<section class="content">
    <div class="page" style="max-width:1200px;margin:auto">

        <h1>EMS Medical Service Entry</h1>
        <p class="text-muted">Based on Roxwood Hospital regulations</p>

        @if(session('flash_messages'))
            @foreach(session('flash_messages') as $m)
                <div class="alert alert-success">{{ $m }}</div>
            @endforeach
        @endif
	        @if(session('flash_errors'))
	            @foreach(session('flash_errors') as $e)
	                <div class="alert alert-danger">{{ $e }}</div>
	            @endforeach
	        @endif

        <!-- ================= FORM ================= -->
        <div class="card">
            <div class="card-header">Service Entry</div>

            <form method="POST" action="{{ route('dashboard.ems_services.store') }}" id="emsForm" class="form">
                @csrf
                <label>Service Type</label>
                <select name="service_type" id="serviceType" required>
                    <option value="">-- Select --</option>
                    <option value="Pingsan">Fainting Assistance</option>
                    <option value="Treatment">Treatment</option>
                    <option value="Surat">Letter Issuance</option>
                    <option value="Operasi">Surgery</option>
                    <option value="Rawat Inap">Inpatient Care</option>
                    <option value="Kematian">Death Care</option>
                    <option value="Plastik">Plastic Surgery</option>
                </select>

                <div id="detailSection" style="display:none">
                    <label>Service Detail</label>
                    <select name="service_detail" id="serviceDetail" disabled>
                        <option value="">-- Select a service type first --</option>
                    </select>
                    <small id="detailHint" class="text-muted">
                        Please select a service type first
                    </small>
                </div>

                <div id="operasiTingkatSection" style="display:none">
                    <label>Operation Severity</label>
                    <select name="operasi_tingkat" id="operasiTingkat">
                        <option value="">-- Select severity --</option>
                        <option value="Ringan">Minor</option>
                        <option value="Sedang">Moderate</option>
                        <option value="Berat">Severe</option>
                    </select>
                </div>

                <!-- ================= OBAT (KHUSUS PINGSAN & TREATMENT) ================= -->
                <div id="medicineSection" style="display:none">
                    <label style="margin-top:6px">
                        <input type="checkbox" id="isGunshot" name="is_gunshot" value="1">
                        Gunshot / bullet wound
                    </label>
                    <small class="text-muted">
                        If checked, medicine cost becomes <strong>$ {{ number_format($priceBleedingPeluru) }} / item</strong>
                    </small>
                    <hr>
                    <label>Wound Area / Medicine Used</label>
                    <div class="row-form-2">
                        <label><input type="checkbox" name="meds[]" class="med-check" data-med="Gauze" value="Head"> Head (Gauze)</label>
                        <label><input type="checkbox" name="meds[]" class="med-check" data-med="Gauze" value="Body"> Body (Gauze)</label>
                        <label><input type="checkbox" name="meds[]" class="med-check" data-med="Iodine" value="Left Arm"> Left Arm (Iodine)</label>
                        <label><input type="checkbox" name="meds[]" class="med-check" data-med="Iodine" value="Right Arm"> Right Arm (Iodine)</label>
                        <label><input type="checkbox" name="meds[]" class="med-check" data-med="Syringe" value="Left Leg"> Left Leg (Syringe)</label>
                        <label><input type="checkbox" name="meds[]" class="med-check" data-med="Syringe" value="Right Leg"> Right Leg (Syringe)</label>
                        <label><input type="checkbox" name="meds[]" class="med-check" data-med="Syringe" value="Left Foot"> Left Foot (Syringe)</label>
                        <label><input type="checkbox" name="meds[]" class="med-check" data-med="Syringe" value="Right Foot"> Right Foot (Syringe)</label>
                    </div>
                    <small class="text-muted">Each area uses 1 medicine (<strong>$ {{ number_format($priceBleedingNormal) }} / item</strong>)</small>
                </div>

                <div id="patientSection" style="display:none">
                    <label>Patient Name</label>
                    <input type="text" name="patient_name">
                </div>

                <div id="dpjpSection" style="display:none">
                    <label>DPJP / Attending Physician</label>
                    <select name="dpjp_name" id="dpjpName">
                        <option value="">-- Select name --</option>
                        @foreach($doctors as $d)
                            <option value="{{ $d->full_name }}">{{ $d->full_name }}</option>
                        @endforeach
                    </select>
                    <div id="teamInputsWrap"></div>
                </div>

                <!-- ================= PEMBAGIAN OPERASI ================= -->
                <div id="splitOperasi" style="display:none" class="card" style="margin-top:12px; border: 1px solid #e2e8f0;">
                    <div class="card-header" style="font-size: 13px;">Surgery Split</div>
                    <div class="row-form-2" style="padding: 10px;">
                        <div>
                            <strong>Billing</strong>
                            <div id="billingAmount" style="color: #64748b;">$0</div>
                        </div>
                        <div>
                            <strong>Cash</strong>
                            <div id="cashAmount" style="color: #64748b;">$0</div>
                        </div>
                    </div>
                    <hr>
                    <div class="row-form-2" style="padding: 10px;">
                        <div>
                            <strong>Doctor (50% of cash)</strong>
                            <div id="doctorShare" style="color: #16a34a; font-weight: bold;">$0</div>
                        </div>
                        <div>
                            <strong>Team (50% of cash)</strong>
                            <div id="teamPool" style="color: #2563eb; font-weight: bold;">$0</div>
                        </div>
                    </div>
                    <hr>
                    <div style="padding: 10px;">
                        <label>Team Count (excluding doctor)</label>
                        <input type="number" id="teamCount" name="team_count" min="1" value="1">
                        <small class="text-muted">Co-ass / Paramedic / others</small>
                        <div style="margin-top:8px">
                            <strong>Per team</strong>
                            <div id="teamPerPerson" style="color: #2563eb;">$0</div>
                        </div>
                    </div>
                </div>

                <div id="locationSection" style="display:none">
                    <label>Location Code</label>
                    <input type="text" name="location" id="location" inputmode="numeric" maxlength="4" pattern="[0-9]{1,4}">
                    <small class="text-muted">Max 4 digits</small>
                </div>

                <div id="qtySection" style="display:none">
                    <label>Quantity / Day</label>
                    <input type="number" name="qty" id="qty" value="1" min="1">
                </div>

	                <div id="paymentSection" style="display:none">
	                    <label>Payment Type</label>
	                    <select name="payment_type" id="paymentType" required>
	                        <option value="cash">Cash</option>
	                        <option value="billing">Billing</option>
	                    </select>
	                    <input type="hidden" id="paymentTypeShadow" name="payment_type" value="" disabled>
	                    <div id="paymentReadonly" class="text-muted" style="display:none; margin-top:6px;"></div>
	                </div>

                <input type="hidden" name="price" id="price" value="0">
                <input type="hidden" name="total" id="total" value="0">

                <div class="total-display" id="totalSection" style="display:none">
                    <div class="total-display-label">Total Cost</div>
                    <div class="total-amount" id="totalDisplay">$0</div>
                </div>

                <div style="margin-top: 15px; display:none" id="actionSection">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" onclick="clearEmsForm()">Clear</button>
                </div>
            </form>
        </div>

        <!-- ================= REKAP ================= -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">Filters & Summary</div>
            <form method="GET" style="margin-top: 10px;">
                <div class="row-form-2">
                    <div class="col">
                        <label>Date Range</label>
                        <select name="range" id="rangeSelect">
                            <option value="today" {{ $range === 'today' ? 'selected' : '' }}>Today</option>
                            <option value="yesterday" {{ $range === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                            <option value="last7" {{ $range === 'last7' ? 'selected' : '' }}>Last 7 days</option>
                            @foreach(['week1', 'week2', 'week3', 'week4'] as $wk)
                                <option value="{{ $wk }}" {{ $range === $wk ? 'selected' : '' }}>
                                    Week {{ substr($wk, 4) }} ({{ $weeks[$wk]['start']->format('d M') }} – {{ $weeks[$wk]['end']->format('d M') }})
                                </option>
                            @endforeach
                            <option value="custom" {{ $range === 'custom' ? 'selected' : '' }}>Custom</option>
                        </select>
                    </div>
                </div>

                <div class="row-form-2 {{ $range !== 'custom' ? 'hidden' : '' }}" id="customDateRow" style="margin-top: 10px;">
                    <div class="col">
                        <label>From</label>
                        <input type="date" name="from" value="{{ request('from') }}">
                    </div>
                    <div class="col">
                        <label>To</label>
                        <input type="date" name="to" value="{{ request('to') }}">
                    </div>
                </div>

                <button type="submit" class="btn btn-secondary" style="margin-top:10px">Apply Filter</button>
            </form>

            <p class="text-muted" style="font-size:12px; margin-top: 10px;">
                Active range: <strong>{{ $rangeLabel }}</strong>
            </p>

            <div style="margin-top: 20px;">
                <h4 style="font-size:14px; margin-bottom: 8px;">Medical Items Used</h4>
                <div class="table-wrapper">
                    <table class="table-custom">
                        <thead>
                            <tr><th>Item</th><th>Count</th></tr>
                        </thead>
                        <tbody>
                            @foreach(['Bandage' => 'bandage', 'P3K' => 'p3k', 'Gauze' => 'gauze', 'Iodine' => 'iodine', 'Syringe' => 'syringe'] as $label => $key)
                                <tr><td>{{ $label }}</td><td>{{ $rekapMedis[$key] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h4 style="font-size:14px; margin-top: 20px; margin-bottom: 8px;">Financial Summary</h4>
                <div class="table-wrapper">
                    <table class="table-custom">
                        <thead>
                            <tr><th>Type</th><th>Total</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Billing</td><td>{{ dollar($rekapMedis['billing']) }}</td></tr>
                            <tr><td>Cash</td><td>{{ dollar($rekapMedis['cash']) }}</td></tr>
                            <tr style="background:rgba(14,165,233,0.08); font-weight:700;">
                                <td style="color:#0284c7;">Total</td>
                                <td style="color:#0284c7;">{{ dollar($rekapMedis['total']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 20px;">
            <div class="card-header">EMS Transactions</div>
            <form id="bulkDeleteForm" method="POST" action="{{ route('dashboard.ems_services.destroy_bulk') }}">
                @csrf
                <div class="table-wrapper">
                    <table id="rekapTable" class="table-custom">
                        <thead>
                            <tr>
                                <th style="width:32px;text-align:center;"><input type="checkbox" id="checkAll"></th>
                                <th>Time</th>
                                <th>Service</th>
                                <th>Detail</th>
                                <th>Patient</th>
                                <th>Payment</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
	                            @foreach ($rows as $r)
	                                <tr>
	                                    <td style="text-align:center;"><input type="checkbox" class="row-check" name="ids[]" value="{{ $r->id }}"></td>
	                                    <td data-order="{{ $r->created_at ? \Illuminate\Support\Carbon::parse($r->created_at)->timestamp : 0 }}">
	                                        {{ $r->created_at ? \Illuminate\Support\Carbon::parse($r->created_at)->format('Y-m-d H:i') : '-' }}
	                                    </td>
	                                    <td>{{ $r->service_type }}</td>
	                                    <td>{{ $r->service_detail }}</td>
	                                    <td>{{ $r->patient_name ?? '-' }}</td>
	                                    <td>{{ $r->payment_type ? strtoupper($r->payment_type) : '-' }}</td>
	                                    <td data-order="{{ $r->total }}">{{ dollar($r->total) }}</td>
	                                </tr>
	                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" style="text-align:right;">TOTAL (this page):</th>
                                <th id="rekapTotalFooter">$0</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </form>
            <div style="margin-top: 15px;">
                <button type="submit" form="bulkDeleteForm" id="btnDeleteSelected" class="btn btn-danger" disabled>Delete Selected</button>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const EMS_STORAGE_KEY = 'ems_services_form_v10';
        let isRestoring = false;
        const SHOULD_CLEAR_FORM = @json($shouldClearForm ?? false);

        const el = id => document.getElementById(id);
        const hide = el => el.style.display = 'none';
        const show = el => el.style.display = 'block';

        const serviceType = el('serviceType');
        const serviceDetail = el('serviceDetail');
	        const operasiTingkat = el('operasiTingkat');
	        const qtyEl = el('qty');
	        const paymentType = el('paymentType');
	        const paymentShadow = el('paymentTypeShadow');
	        const paymentReadonly = el('paymentReadonly');
	        const locationInput = el('location');
	        const totalUI = el('totalDisplay');
	        const totalEl = el('total');
	        const priceEl = el('price');
	        const teamCountEl = el('teamCount');

	        const lockPayment = (value) => {
	            paymentType.value = value;
	            paymentType.dataset.locked = '1';
	            paymentType.dataset.lockedValue = value;
	            paymentType.disabled = true;
	            if (paymentShadow) {
	                paymentShadow.disabled = false;
	                paymentShadow.value = value;
	            }
	        };

	        const unlockPayment = () => {
	            paymentType.dataset.locked = '0';
	            paymentType.dataset.lockedValue = '';
	            paymentType.disabled = false;
	            if (paymentShadow) {
	                paymentShadow.disabled = true;
	                paymentShadow.value = '';
	            }
	        };

	        const syncPaymentShadow = () => {
	            if (!paymentShadow) return;
	            if (!paymentShadow.disabled) paymentShadow.value = paymentType.value;
	        };

        const totalSection = el('totalSection');
        const actionSection = el('actionSection');

        // Force English labels for any legacy/misencoded strings
        const btnDeleteSelected = el('btnDeleteSelected');
        if (btnDeleteSelected) btnDeleteSelected.textContent = 'Delete Selected';

	        const rangeSelect = el('rangeSelect');
	        if (rangeSelect) {
	            Array.from(rangeSelect.options).forEach(opt => {
	                opt.text = opt.text
	                    .replace('â€“', '-')
	                    .replace('–', '-');
	            });
	        }

	        // If there are flash messages, bring them into view (useful after bulk actions near the bottom)
	        const firstAlert = document.querySelector('.alert');
	        if (firstAlert) firstAlert.scrollIntoView({ behavior: 'smooth', block: 'start' });

        const section = id => el(id);

        /* ========= CORE CALCULATE ========= */
	        const calculate = () => {
	            const form = el('emsForm');
	            const data = new FormData(form);

            fetch("{{ route('dashboard.ems_services.preview') }}", {
                method: 'POST',
                body: data,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(r => r.json())
            .then(res => {
                if(res.success) {
                    priceEl.value = res.breakdown.base_price;
                    totalEl.value = res.total;

	                    // Plastic surgery breakdown (cash + billing)
	                    if (res.breakdown && res.breakdown.plastik) {
	                        const cash = parseInt(res.breakdown.plastik.cash || 0, 10);
	                        const billing = parseInt(res.breakdown.plastik.billing || 0, 10);
	                        if (billing > 0) {
	                            totalUI.textContent =
	                                `$${cash.toLocaleString()} (Cash) + ` +
	                                `$${billing.toLocaleString()} (Billing) = ` +
	                                `$${(cash + billing).toLocaleString()}`;
	                        } else {
	                            totalUI.textContent = `$${cash.toLocaleString()} (Cash)`;
	                        }
	                        return;
	                    }
                    let text = `$ ${res.total.toLocaleString()}`;
                    if (res.breakdown.medicine.count > 0) {
                        text += `<br><small>Medicine: ${res.breakdown.medicine.count} × $${res.breakdown.medicine.per_item} = $${res.breakdown.medicine.subtotal}</small>`;
                    }
                    totalUI.innerHTML = text;

                    if (serviceType.value === 'Operasi') updateSplitOperasi(res.total);
                } else {
                    totalUI.textContent = res.message || 'Error';
                }
	            });
	        };

	        if (paymentType) {
	            paymentType.addEventListener('change', () => {
	                syncPaymentShadow();
	                calculate();
	            });
	        }

        const updateSplitOperasi = (total) => {
            const splitBox = el('splitOperasi');
            if (!total || total <= 0) { hide(splitBox); return; }
            const billing = Math.floor(total / 2);
            const cash = total - billing;
            const doctor = Math.floor(cash * 0.5);
            const teamPool = cash - doctor;
            const teamCount = Math.max(parseInt(teamCountEl.value) || 1, 1);
            const perTeam = Math.floor(teamPool / teamCount);

            el('billingAmount').textContent = `$${billing.toLocaleString()}`;
            el('cashAmount').textContent = `$${cash.toLocaleString()}`;
            el('doctorShare').textContent = `$${doctor.toLocaleString()}`;
            el('teamPool').textContent = `$${teamPool.toLocaleString()}`;
            el('teamPerPerson').textContent = `$${perTeam.toLocaleString()}`;
            show(splitBox);
        };

        /* ========= FORM LOGIC ========= */
	        const resetUI = () => {
	            serviceDetail.disabled = true;
	            serviceDetail.innerHTML = '<option value="">-- Select a service type first --</option>';
	            el('detailHint').style.display = 'block';
	            ['detailSection', 'splitOperasi', 'patientSection', 'locationSection', 'qtySection', 'medicineSection', 'paymentSection', 'operasiTingkatSection', 'dpjpSection'].forEach(s => hide(el(s)));
	            priceEl.value = 0; totalEl.value = 0; totalUI.textContent = '$0';
	            hide(totalSection);
	            hide(actionSection);
	            unlockPayment();
	            paymentType.style.display = '';
	            if (paymentReadonly) {
	                paymentReadonly.style.display = 'none';
	                paymentReadonly.textContent = '';
	            }
	        };

	        const DETAIL_OPTIONS = {
	            Pingsan: [
	                { value: 'RS', label: 'Hospital (RS)' },
	                { value: 'Paleto', label: 'Paleto' },
	                { value: 'Gunung/Laut', label: 'Mountain / Sea' },
	                { value: 'Zona Perang', label: 'War Zone' },
	                { value: 'UFC', label: 'UFC' },
	            ],
	            Treatment: [
	                { value: 'RS', label: 'Hospital (RS)' },
	                { value: 'Luar', label: 'Outside' },
	            ],
	            Surat: [
	                { value: 'Kesehatan', label: 'Medical' },
	                { value: 'Psikologi', label: 'Psychology' },
	            ],
	            Operasi: [
	                { value: 'Besar', label: 'Major' },
	                { value: 'Kecil', label: 'Minor' },
	            ],
	            'Rawat Inap': [
	                { value: 'Reguler', label: 'Regular' },
	                { value: 'VIP', label: 'VIP' },
	            ],
	            Kematian: [
	                { value: 'Pemakaman', label: 'Burial' },
	                { value: 'Kremasi', label: 'Cremation' },
	            ],
	            Plastik: [
	                { value: 'Operasi Plastik', label: 'Plastic Surgery' },
	            ],
	        };

        serviceType.addEventListener('change', () => {
            resetUI();
            if (!isRestoring) document.querySelectorAll('.med-check').forEach(cb => cb.checked = false);
            if (!serviceType.value) return;

            const currentLocation = locationInput.value;

            serviceDetail.disabled = false;
            el('detailHint').style.display = 'none';
            show(totalSection);
            show(actionSection);
            show(el('detailSection'));
	            show(el('paymentSection'));

	            const type = serviceType.value;
	            (DETAIL_OPTIONS[type] || []).forEach(opt => serviceDetail.add(new Option(opt.label, opt.value)));

	            if (type === 'Operasi') {
	                show(el('patientSection')); show(el('dpjpSection')); show(el('operasiTingkatSection'));
	                locationInput.value = currentLocation || '4017';
	                lockPayment('billing');
	            } else if (type === 'Pingsan' || type === 'Treatment') {
	                locationInput.value = currentLocation;
	                lockPayment('cash');
	            } else if (type === 'Surat') {
	                show(el('patientSection'));
	                hide(el('locationSection'));
	                locationInput.value = currentLocation || '4017';
	                lockPayment('cash');
	            } else if (type === 'Rawat Inap') {
	                show(el('patientSection'));
	                show(el('qtySection'));
	                hide(el('locationSection'));
	                locationInput.value = currentLocation || '4017';
	                lockPayment('billing');
	            } else if (type === 'Kematian') {
	                show(el('patientSection')); show(el('locationSection')); locationInput.value = currentLocation;
	                unlockPayment();
            } else if (type === 'Plastik') {
                show(el('patientSection'));
                hide(el('detailSection'));
                hide(el('operasiTingkatSection'));
                hide(el('locationSection'));
                hide(el('qtySection'));
                serviceDetail.value = 'Operasi Plastik';
                locationInput.value = '4017';

                // Payment is Cash only for plastic surgery (read-only UI; follow ACTIVE regulation)
                lockPayment('cash');
                paymentType.style.display = 'none';
                if (paymentReadonly) {
                    paymentReadonly.textContent = 'Cash';
                    paymentReadonly.style.display = 'block';
                }

                calculate();
            }
        });

        serviceDetail.addEventListener('change', () => {
            const type = serviceType.value;
            const detail = serviceDetail.value;

            if (!detail) {
                hide(el('medicineSection'));
                return;
            }

            if (type === 'Pingsan' || type === 'Treatment') {
                show(el('medicineSection'));
            } else {
                hide(el('medicineSection'));
            }

            // Location logic (mirror legacy/ajax/ems_preview_price.php usage)
            if (type === 'Pingsan' || type === 'Treatment') {
                if (detail === 'RS') {
                    hide(el('locationSection'));
                    locationInput.value = '4017';
                } else {
                    show(el('locationSection'));
                }
            }

            calculate();
        });

        operasiTingkat.addEventListener('change', calculate);
        qtyEl.addEventListener('input', calculate);
        el('isGunshot').addEventListener('change', calculate);
        document.querySelectorAll('.med-check').forEach(cb => cb.addEventListener('change', calculate));

        teamCountEl.addEventListener('input', () => {
            const wrap = el('teamInputsWrap'); wrap.innerHTML = '';
            const count = Math.max(parseInt(teamCountEl.value) || 1, 1);
            const doctorsHtml = el('dpjpName').innerHTML;
            for (let i = 1; i <= count; i++) {
                wrap.innerHTML += `<label>Team Member ${i}</label><select name="team_names[]">${doctorsHtml}</select>`;
            }
            updateSplitOperasi(parseInt(totalEl.value));
        });

        /* ========= PERSISTENCE ========= */
        const saveForm = () => {
            if (isRestoring) return;
            const data = {};
            const fd = new FormData(el('emsForm'));
            fd.forEach((v, k) => {
                if (k.endsWith('[]')) {
                    if(!data[k]) data[k] = [];
                    data[k].push(v);
                } else {
                    data[k] = v;
                }
            });
            localStorage.setItem(EMS_STORAGE_KEY, JSON.stringify(data));
        };

        el('emsForm').addEventListener('input', saveForm);
        el('emsForm').addEventListener('change', saveForm);

        el('emsForm').addEventListener('submit', (e) => {
            if (!confirm('Are you sure you want to save this entry?')) {
                e.preventDefault();
                return;
            }

            // Ensure disabled fields are submitted
            serviceDetail.disabled = false;
        });

        window.clearEmsForm = () => {
            localStorage.removeItem(EMS_STORAGE_KEY);
            el('emsForm').reset();
            serviceType.dispatchEvent(new Event('change'));
        };

        /* ========= RESTORE (Crash/Refresh) ========= */
        const restoreForm = () => {
            const raw = localStorage.getItem(EMS_STORAGE_KEY);
            if (!raw) return;

            let saved = null;
            try {
                saved = JSON.parse(raw);
            } catch (e) {
                return;
            }
            if (!saved || typeof saved !== 'object') return;

            isRestoring = true;
            try {
                el('emsForm').reset();
                resetUI();

                const savedServiceType = saved['service_type'] ?? '';
                if (savedServiceType) {
                    serviceType.value = savedServiceType;
                    serviceType.dispatchEvent(new Event('change'));
                }

                // Restore simple scalar fields by name
                const setByName = (name, value) => {
                    const field = el('emsForm').querySelector(`[name="${CSS.escape(name)}"]`);
                    if (!field) return;
                    if (field.type === 'checkbox') {
                        field.checked = value === '1' || value === 1 || value === true;
                        return;
                    }
                    field.value = value ?? '';
                };

	                setByName('patient_name', saved['patient_name']);
	                setByName('location', saved['location']);
	                setByName('qty', saved['qty']);
	                if (paymentType.dataset.locked !== '1') {
	                    setByName('payment_type', saved['payment_type']);
	                }
	                syncPaymentShadow();
	                setByName('operasi_tingkat', saved['operasi_tingkat']);
	                setByName('dpjp_name', saved['dpjp_name']);
	                setByName('is_gunshot', saved['is_gunshot']);
	                setByName('team_count', saved['team_count']);

                // Team inputs are dynamic (Operasi)
                const teamCount = Math.max(parseInt(saved['team_count'] ?? teamCountEl.value ?? 1, 10) || 1, 1);
                if (teamCountEl) {
                    teamCountEl.value = String(teamCount);
                    teamCountEl.dispatchEvent(new Event('input'));
                }

                // Restore service detail AFTER options exist
                const savedServiceDetail = saved['service_detail'] ?? '';
                if (savedServiceDetail) {
                    serviceDetail.value = savedServiceDetail;
                    serviceDetail.dispatchEvent(new Event('change'));
                }

                // Restore meds[]
                const meds = saved['meds[]'];
                if (Array.isArray(meds)) {
                    document.querySelectorAll('input[name="meds[]"]').forEach(cb => {
                        cb.checked = meds.includes(cb.value);
                    });
                }

                // Restore team_names[]
                const teams = saved['team_names[]'];
                if (Array.isArray(teams)) {
                    const teamSelects = Array.from(el('emsForm').querySelectorAll('select[name="team_names[]"]'));
                    teamSelects.forEach((sel, idx) => {
                        if (teams[idx] !== undefined) sel.value = teams[idx];
                    });
                }

                // Restore computed fields (best-effort)
                setByName('price', saved['price']);
                setByName('total', saved['total']);

            } finally {
                isRestoring = false;
            }

            // Recalculate once UI is ready (except Plastik, which is fixed in UI)
            setTimeout(() => {
                if (serviceType.value === 'Plastik') return;
                if (!serviceType.value) return;
                if (serviceDetail.value) calculate();
            }, 50);
        };

        // Ensure initial state: only service type visible
        resetUI();

        if (SHOULD_CLEAR_FORM) {
            localStorage.removeItem(EMS_STORAGE_KEY);
            el('emsForm').reset();
            resetUI();
        } else {
            restoreForm();
        }

        /* ========= INIT DATATABLES ========= */
	        if (window.jQuery && jQuery.fn.DataTable) {
	            jQuery('#rekapTable').DataTable({
	                order: [[1, 'desc']],
	                pageLength: 25,
	                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/en-GB.json' },
	                footerCallback: function() {
	                    let total = this.api().column(6, {page:'current'}).nodes().reduce((a, b) => a + parseInt(b.getAttribute('data-order') || 0), 0);
	                    el('rekapTotalFooter').textContent = '$ ' + total.toLocaleString();
	                }
	            });
	        }

        el('checkAll').addEventListener('change', function() {
            document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
            el('btnDeleteSelected').disabled = !this.checked;
        });

        document.body.addEventListener('change', e => {
            if (e.target.classList.contains('row-check')) {
                el('btnDeleteSelected').disabled = document.querySelectorAll('.row-check:checked').length === 0;
            }
        });

        el('rangeSelect').addEventListener('change', function() {
            el('customDateRow').classList.toggle('hidden', this.value !== 'custom');
        });
    });
</script>

@endsection
