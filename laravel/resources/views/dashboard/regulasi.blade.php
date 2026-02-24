@extends('layouts.app')

@section('content')

<section class="content">
    <div class="page" style="max-width:1200px;margin:auto">

        <h1>Regulasi EMS</h1>
        <p class="text-muted">Manajemen paket & regulasi medis</p>

        <div id="ajaxAlert"></div>

        <!-- ================= PACKAGES ================= -->
        <div class="card">
            <div class="card-header">📦 Packages</div>

            <div class="table-wrapper">
                <table id="packageTable" class="table-custom">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Bandage</th>
                            <th>Ifaks</th>
                            <th>Painkiller</th>
                            <th>Harga</th>
                            <th width="80">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($packages as $p)
                            <tr
                                data-id="{{ $p->id }}"
                                data-name="{{ htmlspecialchars($p->name, ENT_QUOTES) }}"
                                data-bandage="{{ $p->bandage_qty }}"
                                data-ifaks="{{ $p->ifaks_qty }}"
                                data-painkiller="{{ $p->painkiller_qty }}"
                                data-price="{{ $p->price }}">
                                <td>{{ $p->name }}</td>
                                <td>{{ $p->bandage_qty }}</td>
                                <td>{{ $p->ifaks_qty }}</td>
                                <td>{{ $p->painkiller_qty }}</td>
                                <td>$ {{ number_format($p->price) }}</td>
                                <td><button class="btn-secondary btn-edit-package">Edit</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div id="regAlert"></div>

        <!-- ================= MEDICAL REGULATIONS ================= -->
        <div class="card">
            <div class="card-header">📜 Medical Regulations</div>

            <div class="table-wrapper">
                <table id="regTable" class="table-custom">
                    <thead>
                        <tr>
                            <th>Kategori</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Harga</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th width="80">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($regs as $r)
                            <tr
                                data-id="{{ $r->id }}"
                                data-category="{{ htmlspecialchars($r->category, ENT_QUOTES) }}"
                                data-name="{{ htmlspecialchars($r->name, ENT_QUOTES) }}"
                                data-location="{{ htmlspecialchars($r->location ?? '', ENT_QUOTES) }}"
                                data-price_type="{{ $r->price_type }}"
                                data-min="{{ $r->price_min }}"
                                data-max="{{ $r->price_max }}"
                                data-payment="{{ $r->payment_type }}"
                                data-duration="{{ $r->duration_minutes }}"
                                data-notes="{{ htmlspecialchars($r->notes ?? '', ENT_QUOTES) }}"
                                data-active="{{ $r->is_active }}">
                                <td>{{ $r->category }}</td>
                                <td>{{ $r->code }}</td>
                                <td>{{ $r->name }}</td>
                                <td>
                                    @if($r->price_type === 'FIXED')
                                        $ {{ number_format($r->price_min) }}
                                    @else
                                        $ {{ number_format($r->price_min) }} - $ {{ number_format($r->price_max) }}
                                    @endif
                                </td>
                                <td>{{ $r->payment_type }}</td>
                                <td>{{ $r->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                <td><button class="btn-secondary btn-edit-reg">Edit</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<!-- ===============================
     MODAL EDIT PACKAGE
     =============================== -->
<div id="editPackageModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Edit Package</h3>

        <form id="editPackageForm" class="form">
            @csrf
            <input type="hidden" name="action" value="update_package">
            <input type="hidden" name="id" id="pkgId">

            <label>Nama</label>
            <input type="text" name="name" id="pkgName" required>

            <label>Bandage</label>
            <input type="number" name="bandage_qty" id="pkgBandage" min="0" required>

            <label>Ifaks</label>
            <input type="number" name="ifaks_qty" id="pkgIfaks" min="0" required>

            <label>Painkiller</label>
            <input type="number" name="painkiller_qty" id="pkgPainkiller" min="0" required>

            <label>Harga</label>
            <input type="number" name="price" id="pkgPrice" min="0" required>

            <div class="modal-actions">
                <button type="button" class="btn-secondary btn-cancel">Batal</button>
                <button type="submit" class="btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ===============================
     MODAL EDIT REGULATION
     =============================== -->
<div id="editRegModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Edit Regulasi Medis</h3>

        <form id="editRegForm" class="form">
            @csrf
            <input type="hidden" name="action" value="update_regulation">
            <input type="hidden" name="id" id="regId">

            <label>Kategori</label>
            <input type="text" name="category" id="regCategory" required>

            <label>Nama</label>
            <input type="text" name="name" id="regName" required>

            <label>Lokasi</label>
            <input type="text" name="location" id="regLocation">

            <label>Price Type</label>
            <select name="price_type" id="regPriceType">
                <option value="FIXED">FIXED</option>
                <option value="RANGE">RANGE</option>
            </select>

            <label>Harga Min</label>
            <input type="number" name="price_min" id="regMin" min="0" required>

            <label>Harga Max</label>
            <input type="number" name="price_max" id="regMax" min="0" required>

            <label>Payment</label>
            <select name="payment_type" id="regPayment">
                <option value="CASH">CASH</option>
                <option value="INVOICE">INVOICE</option>
                <option value="BILLING">BILLING</option>
            </select>

            <label>Durasi (menit)</label>
            <input type="number" name="duration_minutes" id="regDuration">

            <label>Catatan</label>
            <textarea name="notes" id="regNotes"></textarea>

            <label>
                <input type="checkbox" name="is_active" id="regActive"> Aktif
            </label>

            <div class="modal-actions">
                <button type="button" class="btn-secondary btn-cancel">Batal</button>
                <button type="submit" class="btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        let activeRow = null;

        const packageTable = jQuery('#packageTable').DataTable({
            pageLength: 10,
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json' }
        });

        const regTable = jQuery('#regTable').DataTable({
            pageLength: 10,
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/id.json' }
        });

        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-edit-package');
            if (!btn) return;

            const row = btn.closest('tr');
            activeRow = packageTable.row(row);

            document.getElementById('pkgId').value = row.dataset.id;
            document.getElementById('pkgName').value = row.dataset.name;
            document.getElementById('pkgBandage').value = row.dataset.bandage;
            document.getElementById('pkgIfaks').value = row.dataset.ifaks;
            document.getElementById('pkgPainkiller').value = row.dataset.painkiller;
            document.getElementById('pkgPrice').value = row.dataset.price;

            document.getElementById('editPackageModal').style.display = 'flex';
            document.body.classList.add('modal-open');
        });

        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-edit-reg');
            if (!btn) return;

            const row = btn.closest('tr');
            activeRow = regTable.row(row);

            document.getElementById('regId').value = row.dataset.id;
            document.getElementById('regCategory').value = row.dataset.category;
            document.getElementById('regName').value = row.dataset.name;
            document.getElementById('regLocation').value = row.dataset.location || '';
            document.getElementById('regPriceType').value = row.dataset.price_type;
            document.getElementById('regMin').value = row.dataset.min;
            document.getElementById('regMax').value = row.dataset.max;
            document.getElementById('regPayment').value = row.dataset.payment;
            document.getElementById('regDuration').value = row.dataset.duration || '';
            document.getElementById('regNotes').value = row.dataset.notes || '';
            document.getElementById('regActive').checked = row.dataset.active === '1';

            document.getElementById('editRegModal').style.display = 'flex';
            document.body.classList.add('modal-open');
        });

        function closeModal() {
            document.getElementById('editPackageModal').style.display = 'none';
            document.getElementById('editRegModal').style.display = 'none';
            document.body.classList.remove('modal-open');
            activeRow = null;
        }

        document.body.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay') || e.target.closest('.btn-cancel')) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        document.getElementById('editPackageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('{{ route("dashboard.regulasi.update") }}', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(r => r.json())
            .then(r => {
                if (!r.success) { showAlert('error', '❌ ' + (r.message || 'Gagal menyimpan data')); return; }
                const node = activeRow.node();
                const name = document.getElementById('pkgName').value;
                const b = document.getElementById('pkgBandage').value;
                const i = document.getElementById('pkgIfaks').value;
                const p = document.getElementById('pkgPainkiller').value;
                const pr = document.getElementById('pkgPrice').value;

                node.dataset.name = name; node.dataset.bandage = b; node.dataset.ifaks = i; node.dataset.painkiller = p; node.dataset.price = pr;
                activeRow.data([name, b, i, p, '$ ' + Number(pr).toLocaleString(), '<button class="btn-secondary btn-edit-package">Edit</button>']).draw(false);
                showAlert('success', '✅ Data package berhasil diperbarui', 'ajaxAlert');
                closeModal();
            });
        });

        document.getElementById('editRegForm').addEventListener('submit', function(e) {
            e.preventDefault();
            fetch('{{ route("dashboard.regulasi.update") }}', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(r => r.json())
            .then(r => {
                if (!r.success) { showAlert('error', '❌ ' + (r.message || 'Gagal menyimpan data')); return; }
                const node = activeRow.node();
                const cur = activeRow.data();
                const cat = document.getElementById('regCategory').value;
                const name = document.getElementById('regName').value;
                const min = document.getElementById('regMin').value;
                const max = document.getElementById('regMax').value;
                const pt = document.getElementById('regPriceType').value;
                const pay = document.getElementById('regPayment').value;
                const act = document.getElementById('regActive').checked;

                node.dataset.category = cat; node.dataset.name = name; node.dataset.min = min; node.dataset.max = max; node.dataset.price_type = pt; node.dataset.payment = pay; node.dataset.active = act ? '1' : '0';
                const harga = pt === 'FIXED' ? '$ ' + Number(min).toLocaleString() : '$ ' + Number(min).toLocaleString() + ' - $ ' + Number(max).toLocaleString();
                activeRow.data([cat, cur[1], name, harga, pay, act ? 'Aktif' : 'Nonaktif', '<button class="btn-secondary btn-edit-reg">Edit</button>']).draw(false);
                showAlert('success', '✅ Data regulasi berhasil diperbarui', 'regAlert');
                closeModal();
            });
        });
    });

    function showAlert(type, message, target = 'ajaxAlert') {
        const box = document.getElementById(target); if (!box) return;
        box.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        setTimeout(() => { const alert = box.querySelector('.alert'); if (alert) { alert.style.opacity = '0'; setTimeout(() => alert.remove(), 600); } }, 5000);
    }
</script>

@endsection
