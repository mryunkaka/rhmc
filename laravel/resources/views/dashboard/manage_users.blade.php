@extends('layouts.app')

@section('content')

<section class="content">
    <div class="page" style="max-width:1100px;margin:auto;">

        <h1 class="gradient-text">Manajemen User</h1>
        <p class="text-muted">Kelola akun, jabatan, role, dan PIN pengguna</p>

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

        @if(session('flash_warnings'))
            @foreach(session('flash_warnings') as $w)
                <div class="alert alert-warning">{{ $w }}</div>
            @endforeach
        @endif

        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
                <span>Daftar User</span>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <input type="text"
                        id="searchUser"
                        placeholder="🔍 Cari nama..."
                        style="padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;min-width:250px;">

                    <button id="btnExportText" class="btn-secondary">
                        📄 Export Text
                    </button>

                    <button id="btnAddUser" class="btn-success">
                        ➕ Tambah Anggota
                    </button>
                </div>
            </div>

            <div class="table-wrapper">
                @foreach ($usersByBatch as $batchName => $batchUsers)
                    <div class="card" style="margin-bottom:20px;">
                        <div class="card-header">
                            {{ $batchName }}
                            <span style="font-size:12px;color:#64748b;">
                                ({{ count($batchUsers) }} user)
                            </span>
                        </div>

                        <div class="table-wrapper">
                            <table class="table-custom user-batch-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nama</th>
                                        <th>Jabatan</th>
                                        <th>Role</th>
                                        <th>Tanggal Join</th>
                                        <th>Dokumen</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($batchUsers as $i => $u)
                                        <tr data-search-name="{{ strtolower($u->full_name) }}">
                                            <td>{{ $i + 1 }}</td>
                                            <td>
                                                <strong>{{ $u->full_name }}</strong>

                                                @if (!empty($u->reactivated_at))
                                                    <div style="margin-top:4px;font-size:12px;color:#16a34a;">
                                                        🔄 Aktif kembali:
                                                        {{ \Carbon\Carbon::parse($u->reactivated_at)->format('d M Y') }}
                                                    </div>
                                                @endif

                                                @if ((int)$u->is_active === 0 && !empty($u->resigned_at))
                                                    <div style="margin-top:4px;font-size:12px;color:#64748b;">
                                                        📅 Resign: {{ \Carbon\Carbon::parse($u->resigned_at)->format('d M Y') }}
                                                    </div>
                                                @endif
                                            </td>

                                            <td>{{ $u->position }}</td>
                                            <td>{{ $u->role }}</td>
                                            <td>
                                                @if (!empty($u->tanggal_masuk))
                                                    <div>
                                                        {{ \Carbon\Carbon::parse($u->tanggal_masuk)->format('d M Y') }}
                                                    </div>
                                                    <small style="color:#64748b;">
                                                        {{ formatDurasiMedis($u->tanggal_masuk) }}
                                                    </small>
                                                @else
                                                    <span style="color:#9ca3af;">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                $docs = [
                                                    'KTP' => $u->file_ktp,
                                                    'SIM' => $u->file_sim,
                                                    'KTA' => $u->file_kta,
                                                    'SERTIFIKAT HELI' => $u->sertifikat_heli,
                                                    'SKB' => $u->file_skb,
                                                ];
                                                @endphp

                                                @foreach ($docs as $label => $path)
                                                    @if (!empty($path))
                                                        <a href="#"
                                                            class="doc-badge btn-preview-doc"
                                                            data-src="/{{ ltrim($path, '/') }}"
                                                            data-title="{{ $label }}"
                                                            title="Lihat {{ $label }}">
                                                            {{ $label }}
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </td>
                                            <td>
                                                <button
                                                    class="btn-secondary btn-edit-user"
                                                    data-id="{{ (int)$u->id }}"
                                                    data-name="{{ $u->full_name }}"
                                                    data-position="{{ $u->position }}"
                                                    data-role="{{ strtolower(trim($u->role)) }}"
                                                    data-batch="{{ (int)($u->batch ?? 0) }}"
                                                    data-kode="{{ $u->kode_nomor_induk_rs ?? '' }}">
                                                    Edit
                                                </button>

                                                @if ($u->is_active)
                                                    <button class="btn-resign btn-resign-user"
                                                        data-id="{{ (int)$u->id }}"
                                                        data-name="{{ $u->full_name }}">
                                                        Resign
                                                    </button>
                                                @else
                                                    <button class="btn-success btn-reactivate-user"
                                                        data-id="{{ (int)$u->id }}"
                                                        data-name="{{ $u->full_name }}">
                                                        Kembali
                                                    </button>
                                                @endif

                                                <button class="btn-danger btn-delete-user"
                                                    data-id="{{ (int)$u->id }}"
                                                    data-name="{{ $u->full_name }}">
                                                    Hapus
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

<!-- ======================================
     MODAL PREVIEW DOKUMEN
     ====================================== -->
<div id="docPreviewModal" class="modal-overlay" style="display:none;">
    <div class="modal-card" style="max-width:900px;">
        <div class="modal-header">
            <strong id="docPreviewTitle">📄 Preview Dokumen</strong>
            <div style="display:flex;gap:8px;align-items:center;">
                <button type="button" class="zoom-control-btn" id="docPrev" title="Sebelumnya">⬅️</button>
                <button type="button" class="zoom-control-btn" id="docNext" title="Berikutnya">➡️</button>
                <button type="button" class="zoom-control-btn" id="docZoomOut" title="Perkecil">➖</button>
                <button type="button" class="zoom-control-btn" id="docZoomIn" title="Perbesar">➕</button>
                <button type="button" class="zoom-control-btn" id="docZoomReset" title="Reset">🔄</button>
                <button type="button" onclick="closeDocModal()">✕</button>
            </div>
        </div>
        <div class="modal-body" style="background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:60vh;">
            <img id="docPreviewImage" src="" alt="Dokumen" style="max-width:100%;max-height:75vh;object-fit:contain;transition:transform 0.2s ease;">
        </div>
    </div>
</div>

<div id="resignModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Resign User</h3>
        <form method="POST" action="/dashboard/manage_users_action.php" class="form">
            @csrf
            <input type="hidden" name="action" value="resign">
            <input type="hidden" name="user_id" id="resignUserId">
            <p>Apakah Anda yakin ingin menonaktifkan <strong id="resignUserName"></strong>?</p>
            <label>Alasan Resign</label>
            <textarea name="resign_reason" required></textarea>
            <div class="modal-actions">
                <button type="button" class="btn-secondary btn-cancel">Batal</button>
                <button type="submit" class="btn-nonaktif">Nonaktifkan</button>
            </div>
        </form>
    </div>
</div>

<div id="reactivateModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Kembali Bekerja</h3>
        <form method="POST" action="/dashboard/manage_users_action.php" class="form">
            @csrf
            <input type="hidden" name="action" value="reactivate">
            <input type="hidden" name="user_id" id="reactivateUserId">
            <p>Aktifkan kembali <strong id="reactivateUserName"></strong>?</p>
            <label>Keterangan (opsional)</label>
            <textarea name="reactivate_note" placeholder="Contoh: Kontrak baru / dipanggil kembali"></textarea>
            <div class="modal-actions">
                <button type="button" class="btn-secondary btn-cancel">Batal</button>
                <button type="submit" class="btn-success">Aktifkan</button>
            </div>
        </form>
    </div>
</div>

<div id="editModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Edit User</h3>
        <form method="POST" action="/dashboard/manage_users_action.php" class="form">
            @csrf
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="user_id" id="editUserId">
            <label>Batch</label>
            <input type="number" name="batch" id="editBatch" min="1" max="26" placeholder="Contoh: 3">
            <label>Kode Medis / Nomor Induk RS</label>
            <div class="ems-kode-medis">
                <input type="text" id="editKodeMedis" readonly>
                <button type="button" id="btnDeleteKodeMedis" title="Hapus kode medis">🗑</button>
            </div>
            <small style="color:#c0392b;display:none;" id="kodeMedisWarning">Menghapus kode medis akan mengizinkan sistem membuat ulang kode baru.</small>
            <label>Nama</label>
            <input type="text" name="full_name" id="editName" required>
            <label>Jabatan</label>
            <select name="position" id="editPosition" required>
                <option value="Trainee">Trainee</option>
                <option value="Paramedic">Paramedic</option>
                <option value="(Co.Ast)">(Co.Ast)</option>
                <option value="Dokter Umum">Dokter Umum</option>
                <option value="Dokter Spesialis">Dokter Spesialis</option>
            </select>
            <label>Role</label>
            <select name="role" id="editRole" required>
                <option value="Staff">Staff</option>
                <option value="Staff Manager">Staff Manager</option>
                <option value="Manager">Manager</option>
                <option value="Vice Director">Vice Director</option>
                <option value="Director">Director</option>
            </select>
            <label>PIN Baru <small>(4 digit, kosongkan jika tidak ganti)</small></label>
            <input type="password" name="new_pin" inputmode="numeric" pattern="[0-9]{4}" maxlength="4">
            <div class="modal-actions">
                <button type="button" class="btn-secondary btn-cancel">Batal</button>
                <button type="submit" class="btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Hapus User</h3>
        <form method="POST" action="/dashboard/manage_users_action.php" class="form">
            @csrf
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" id="deleteUserId">
            <p style="color:#b91c1c;">⚠️ User <strong id="deleteUserName"></strong> akan dihapus permanen.<br>Tindakan ini <strong>tidak dapat dibatalkan</strong>.</p>
            <div class="modal-actions">
                <button type="button" class="btn-secondary btn-cancel">Batal</button>
                <button type="submit" class="btn-danger">Hapus Permanen</button>
            </div>
        </form>
    </div>
</div>

<div id="addUserModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>Tambah Anggota Baru</h3>
        <form method="POST" action="/dashboard/manage_users_action.php" class="form">
            @csrf
            <input type="hidden" name="action" value="add_user">
            <label>Nama Lengkap</label>
            <input type="text" name="full_name" required>
            <label>Jabatan</label>
            <select name="position" required>
                <option value="Trainee">Trainee</option>
                <option value="Paramedic">Paramedic</option>
                <option value="(Co.Ast)">(Co.Ast)</option>
                <option value="Dokter Umum">Dokter Umum</option>
                <option value="Dokter Spesialis">Dokter Spesialis</option>
            </select>
            <label>Role</label>
            <select name="role" required>
                <option value="Staff">Staff</option>
                <option value="Staff Manager">Staff Manager</option>
                <option value="Manager">Manager</option>
                <option value="Vice Director">Vice Director</option>
                <option value="Director">Director</option>
            </select>
            <label>Batch <small>(opsional)</small></label>
            <input type="number" name="batch" min="1" max="26" placeholder="Contoh: 3">
            <small style="color:#64748b;">PIN awal akan otomatis dibuat: <strong>0000</strong></small>
            <div class="modal-actions">
                <button type="button" class="btn-secondary btn-cancel">Batal</button>
                <button type="submit" class="btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleMap = {
            'staff': 'Staff',
            'staff manager': 'Staff Manager',
            'manager': 'Manager',
            'vice director': 'Vice Director',
            'director': 'Director'
        };

        // Modals
        const modals = {
            edit: document.getElementById('editModal'),
            resign: document.getElementById('resignModal'),
            reactivate: document.getElementById('reactivateModal'),
            delete: document.getElementById('deleteModal'),
            addUser: document.getElementById('addUserModal'),
            docPreview: document.getElementById('docPreviewModal')
        };

        // Edit
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-edit-user');
            if (!btn) return;
            document.getElementById('editUserId').value = btn.dataset.id;
            document.getElementById('editName').value = btn.dataset.name;
            document.getElementById('editPosition').value = btn.dataset.position;
            document.getElementById('editRole').value = roleMap[btn.dataset.role] || 'Staff';
            document.getElementById('editBatch').value = btn.dataset.batch || '';
            document.getElementById('editKodeMedis').value = btn.dataset.kode || '';
            document.getElementById('kodeMedisWarning').style.display = btn.dataset.kode ? 'block' : 'none';
            modals.edit.style.display = 'flex';
            document.body.classList.add('modal-open');
        });

        // Resign
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-resign-user');
            if (!btn) return;
            document.getElementById('resignUserId').value = btn.dataset.id;
            document.getElementById('resignUserName').innerText = btn.dataset.name;
            modals.resign.style.display = 'flex';
            document.body.classList.add('modal-open');
        });

        // Reactivate
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-reactivate-user');
            if (!btn) return;
            document.getElementById('reactivateUserId').value = btn.dataset.id;
            document.getElementById('reactivateUserName').innerText = btn.dataset.name;
            modals.reactivate.style.display = 'flex';
            document.body.classList.add('modal-open');
        });

        // Delete
        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-delete-user');
            if (!btn) return;
            document.getElementById('deleteUserId').value = btn.dataset.id;
            document.getElementById('deleteUserName').innerText = btn.dataset.name;
            modals.delete.style.display = 'flex';
            document.body.classList.add('modal-open');
        });

        // Add User
        const btnAddUser = document.getElementById('btnAddUser');
        if (btnAddUser) {
            btnAddUser.addEventListener('click', () => {
                modals.addUser.style.display = 'flex';
                document.body.classList.add('modal-open');
            });
        }

        // Close Modals
        document.body.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-overlay') || e.target.closest('.btn-cancel')) {
                Object.values(modals).forEach(m => m.style.display = 'none');
                document.body.classList.remove('modal-open');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                Object.values(modals).forEach(m => m.style.display = 'none');
                document.body.classList.remove('modal-open');
            }
        });

        // Search
        const searchInput = document.getElementById('searchUser');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const keyword = this.value.toLowerCase().trim();
                const cards = document.querySelectorAll('.table-wrapper > .card');
                cards.forEach(card => {
                    const rows = card.querySelectorAll('tbody tr');
                    let visibleCount = 0;
                    rows.forEach(row => {
                        const name = row.getAttribute('data-search-name');
                        if (keyword === '' || name.includes(keyword)) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    card.style.display = visibleCount === 0 ? 'none' : '';
                });
            });
        }

        // Doc Preview
        const img = document.getElementById('docPreviewImage');
        const title = document.getElementById('docPreviewTitle');
        const btnPrev = document.getElementById('docPrev');
        const btnNext = document.getElementById('docNext');
        let scale = 1;
        let docList = [];
        let currentIndex = 0;

        document.body.addEventListener('click', function(e) {
            const btn = e.target.closest('.doc-badge');
            if (!btn) return;
            e.preventDefault();
            const cell = btn.closest('td');
            const docs = cell.querySelectorAll('.doc-badge');
            docList = Array.from(docs).map(el => ({ src: el.dataset.src, title: el.dataset.title }));
            currentIndex = Array.from(docs).indexOf(btn);
            openDoc(currentIndex);
            modals.docPreview.style.display = 'flex';
            document.body.classList.add('modal-open');
        });

        function openDoc(index) {
            const doc = docList[index];
            if (!doc) return;
            img.src = doc.src;
            title.textContent = '📄 ' + doc.title;
            scale = 1;
            img.style.transform = 'scale(1)';
        }

        btnNext.onclick = () => { currentIndex = (currentIndex + 1) % docList.length; openDoc(currentIndex); };
        btnPrev.onclick = () => { currentIndex = (currentIndex - 1 + docList.length) % docList.length; openDoc(currentIndex); };
        document.getElementById('docZoomIn').onclick = () => { scale += 0.1; img.style.transform = `scale(${scale})`; };
        document.getElementById('docZoomOut').onclick = () => { scale = Math.max(0.3, scale - 0.1); img.style.transform = `scale(${scale})`; };
        document.getElementById('docZoomReset').onclick = () => { scale = 1; img.style.transform = 'scale(1)'; };

        window.closeDocModal = function() {
            modals.docPreview.style.display = 'none';
            img.src = '';
            document.body.classList.remove('modal-open');
        };

        // Export Text
        document.getElementById('btnExportText').addEventListener('click', function() {
            let output = '';
            document.querySelectorAll('.user-batch-table').forEach(table => {
                const batchCard = table.closest('.card');
                const batchTitle = batchCard.querySelector('.card-header')?.innerText || '';
                const rows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
                if (!rows.length) return;
                output += batchTitle.toUpperCase() + '\n';
                rows.forEach((row, i) => {
                    const name = row.querySelector('td:nth-child(2) strong')?.innerText || '';
                    const position = row.querySelector('td:nth-child(3)')?.innerText || '';
                    output += `${i + 1}. ${name} (${position})\n`;
                });
                output += '\n';
            });
            if (!output.trim()) return alert('Tidak ada data untuk diexport.');
            const blob = new Blob([output], { type: 'text/plain;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'daftar_medis.txt';
            a.click();
            URL.revokeObjectURL(url);
        });

        // Delete Kode Medis
        document.getElementById('btnDeleteKodeMedis').addEventListener('click', async function() {
            if (!confirm('Yakin ingin menghapus kode medis?')) return;
            const userId = document.getElementById('editUserId').value;
            const res = await fetch('/dashboard/manage_users_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ _token: '{{ csrf_token() }}', action: 'delete_kode_medis', user_id: userId })
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('editKodeMedis').value = '';
                document.getElementById('kodeMedisWarning').style.display = 'none';
            } else {
                alert(data.message || 'Gagal menghapus kode medis.');
            }
        });
    });
</script>

@endsection
