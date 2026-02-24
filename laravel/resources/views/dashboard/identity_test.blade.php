@extends('layouts.app')

@section('content')

<style>
    /* ===== SMOOTH SCROLL ===== */
    html {
        scroll-behavior: smooth;
    }

    /* ===== RESET & BASE ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        -webkit-overflow-scrolling: touch;
    }

    /* ===== BODY - SCROLLABLE CONTAINER ===== */
    /* Override content styling to match legacy feel */
    section.content {
        background: linear-gradient(135deg, #0c4a6e 0%, #075985 50%, #0369a1 100%);
        min-height: calc(100vh - 60px);
        padding: 20px 16px;
        overflow-y: auto;
    }

    /* ===== CONTAINER ===== */
    .ocr-container {
        width: 100%;
        max-width: 580px;
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
        margin: 0 auto 40px auto;
        position: relative;
    }

    /* ===== HEADER ===== */
    .ocr-header {
        text-align: center;
        margin-bottom: 24px;
    }

    .ocr-icon {
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #0ea5e9, #06b6d4);
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin-bottom: 12px;
        box-shadow: 0 10px 30px rgba(14, 165, 233, 0.35);
    }

    .ocr-title {
        font-size: 22px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 6px;
        letter-spacing: -0.5px;
    }

    .ocr-subtitle {
        font-size: 14px;
        color: #64748b;
        font-weight: 400;
    }

    /* ===== UPLOAD AREA ===== */
    .upload-area {
        position: relative;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border: 2px solid #0ea5e9;
        border-radius: 12px;
        padding: 0;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 20px;
        display: block;
    }

    .upload-area:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(14, 165, 233, 0.2);
        border-color: #0284c7;
    }

    .upload-area.active {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        border-color: #0284c7;
    }

    .upload-content {
        padding: 24px 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .upload-icon {
        font-size: 40px;
        margin-bottom: 6px;
    }

    .upload-text-wrapper {
        text-align: center;
    }

    .upload-text {
        font-size: 15px;
        font-weight: 600;
        color: #0369a1;
        margin-bottom: 4px;
        display: block;
    }

    .upload-hint {
        font-size: 12px;
        color: #0284c7;
        font-weight: 500;
    }

    .upload-button {
        margin-top: 4px;
        padding: 10px 20px;
        background: linear-gradient(135deg, #0ea5e9, #06b6d4);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 3px 10px rgba(14, 165, 233, 0.25);
    }

    #img {
        position: absolute;
        width: 0;
        height: 0;
        opacity: 0;
        pointer-events: none;
    }

    /* ===== LOADING ===== */
    .loading-area {
        display: none;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border-left: 4px solid #f59e0b;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 20px;
    }

    .loading-area.show {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .loading-spinner {
        width: 22px;
        height: 22px;
        border: 3px solid #f59e0b;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
        flex-shrink: 0;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* ===== ERROR / INFO AREAS ===== */
    .error-area, .info-area {
        display: none;
        border-radius: 10px;
        padding: 14px 16px;
        margin-bottom: 20px;
        border-left: 4px solid;
    }

    .error-area.show, .info-area.show { display: block; }

    .error-area { background: linear-gradient(135deg, #fee2e2, #fecaca); border-color: #ef4444; }
    .info-area { background: linear-gradient(135deg, #d1fae5, #a7f3d0); border-color: #10b981; }

    .preview-area { display: none; margin-bottom: 20px; text-align: center; }
    .preview-area.show { display: block; }
    #preview { max-width: 100%; max-height: 280px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); border: 3px solid #e2e8f0; }

    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 6px; }
    .form-input { width: 100%; padding: 12px 14px; font-size: 14px; font-weight: 500; color: #0f172a; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 10px; transition: all 0.2s ease; }
    .form-input:focus { outline: none; border-color: #0ea5e9; background: #ffffff; box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1); }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }

    .save-button { width: 100%; padding: 14px; background: linear-gradient(135deg, #10b981, #059669); color: #ffffff; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); margin-top: 20px; }
    .reset-button { width: 100%; padding: 12px; background: linear-gradient(135deg, #64748b, #475569); color: #ffffff; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 3px 12px rgba(100, 116, 139, 0.3); margin-top: 10px; }

    @media (max-width: 640px) {
        .form-row { grid-template-columns: 1fr; }
        .ocr-container { padding: 20px; margin: 10px auto; }
    }
</style>

<section class="content">
    <div class="ocr-container">
        <!-- Header -->
        <div class="ocr-header">
            <div class="ocr-icon">📷</div>
            <h1 class="ocr-title">Identity OCR Scanner</h1>
            <p class="ocr-subtitle">Scan your identity document automatically</p>
        </div>

        <!-- Upload Area -->
        <label for="img" class="upload-area" id="uploadArea">
            <div class="upload-content">
                <div class="upload-icon">📸</div>
                <div class="upload-text-wrapper">
                    <span class="upload-text">Click to capture or upload</span>
                    <div class="upload-hint">Supports JPG, PNG • Max 5MB</div>
                </div>
                <button type="button" class="upload-button">Choose File / Take Photo</button>
            </div>
            <input type="file" id="img" accept="image/jpeg,image/png" capture="environment">
        </label>

        <!-- Status Areas -->
        <div class="loading-area" id="loading">
            <div class="loading-spinner"></div>
            <span class="loading-text">Scanning identity document...</span>
        </div>

        <div class="error-area" id="error">
            <div class="error-title"><span>❌</span> <span>Scan Failed</span></div>
            <div class="error-message" id="errorMessage"></div>
        </div>

        <div class="info-area" id="info">
            <div class="info-title"><span>✅</span> <span>Scan Successful</span></div>
            <div class="info-item" id="infoMessage"></div>
        </div>

        <!-- Preview -->
        <div class="preview-area" id="previewArea">
            <div class="preview-label">Scanned Image</div>
            <img id="preview" alt="Preview">
        </div>

        <!-- Form Results -->
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-input" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-input" readonly>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Date of Birth</label>
                <input type="text" name="dob" class="form-input" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Sex</label>
                <input type="text" name="sex" class="form-input" readonly>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Nationality</label>
            <input type="text" name="nationality" class="form-input" readonly>
        </div>

        <div class="form-group">
            <label class="form-label">Citizen ID</label>
            <input type="text" name="citizen_id" class="form-input" readonly>
        </div>

        <div class="form-group">
            <label class="form-label">Alasan</label>
            <select name="change_reason" class="form-input" id="changeReason">
                <option value="Daftar Baru" selected>Daftar Baru</option>
                <option value="Oplas">Oplas</option>
                <option value="Karena Keinginan Sendiri">Karena Keinginan Sendiri</option>
                <option value="Lainnya">Lainnya</option>
                <option value="custom">Custom (Isi Manual)</option>
            </select>
        </div>

        <div class="form-group" id="customReasonGroup" style="display: none;">
            <label class="form-label">Alasan Custom</label>
            <input type="text" name="custom_reason" class="form-input" id="customReasonInput" placeholder="Tulis alasan Anda...">
        </div>

        <input type="hidden" name="temp_file" id="tempFile">

        <button type="button" id="saveBtn" class="save-button" style="display: none;">
            💾 Simpan Data
        </button>

        <button type="button" id="resetBtn" class="reset-button" style="display: none;">
            🔄 Scan Ulang
        </button>
    </div>
</section>

<script>
    const STORAGE_KEY = 'ocr_temp_data';

    function saveToLocalStorage(imageData, ocrData = null) {
        try {
            const data = { image: imageData, ocr: ocrData, timestamp: Date.now() };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch (e) { console.error(e); }
    }

    function loadFromLocalStorage() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return null;
            const data = JSON.parse(raw);
            if (Date.now() - data.timestamp > 3600000) { localStorage.removeItem(STORAGE_KEY); return null; }
            return data;
        } catch (e) { return null; }
    }

    function clearLocalStorage() { localStorage.removeItem(STORAGE_KEY); }

    const uploadArea = document.getElementById('uploadArea');
    const imgInput = document.getElementById('img');
    const loading = document.getElementById('loading');
    const errorArea = document.getElementById('error');
    const errorMessage = document.getElementById('errorMessage');
    const infoArea = document.getElementById('info');
    const infoMessage = document.getElementById('infoMessage');
    const previewArea = document.getElementById('previewArea');
    const preview = document.getElementById('preview');
    const saveBtn = document.getElementById('saveBtn');
    const resetBtn = document.getElementById('resetBtn');
    const changeReason = document.getElementById('changeReason');
    const customReasonGroup = document.getElementById('customReasonGroup');
    const customReasonInput = document.getElementById('customReasonInput');
    const tempFileInput = document.getElementById('tempFile');

    // CSRF Utility
    function getCSRF() { return '{{ csrf_token() }}'; }

    imgInput.addEventListener('change', handleUpload);

    document.querySelector('.upload-button').addEventListener('click', (e) => {
        e.preventDefault();
        imgInput.click();
    });

    changeReason.addEventListener('change', function() {
        if (this.value === 'custom') {
            customReasonGroup.style.display = 'block';
        } else {
            customReasonGroup.style.display = 'none';
            customReasonInput.value = '';
        }
    });

    function handleUpload() {
        if (!imgInput.files.length) return;
        const file = imgInput.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            const imageData = e.target.result;
            saveToLocalStorage(imageData);
            preview.src = imageData;
            previewArea.classList.add('show');
            performOCR(file);
        };
        reader.readAsDataURL(file);
    }

    function performOCR(file) {
        loading.classList.add('show');
        errorArea.classList.remove('show');
        infoArea.classList.remove('show');
        saveBtn.style.display = 'none';
        resetBtn.style.display = 'none';

        const fd = new FormData();
        fd.append('image', file);
        fd.append('_token', getCSRF());

        fetch('{{ route("dashboard.identity_test.ocr") }}', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                loading.classList.remove('show');
                if (d.error) {
                    showOCRError(d.error);
                    return;
                }

                // Fill Form
                document.querySelector('[name="first_name"]').value = d.first_name || '';
                document.querySelector('[name="last_name"]').value = d.last_name || '';
                document.querySelector('[name="dob"]').value = d.dob || '';
                document.querySelector('[name="sex"]').value = d.sex || '';
                document.querySelector('[name="nationality"]').value = d.nationality || '';
                document.querySelector('[name="citizen_id"]').value = d.citizen_id || '';
                tempFileInput.value = d.temp_file || '';

                // Save to localStorage
                const stored = loadFromLocalStorage();
                saveToLocalStorage(stored ? stored.image : null, d);

                // Check Existence
                checkDatabase();
            })
            .catch(err => {
                loading.classList.remove('show');
                showOCRError(err.message);
            });
    }

    function showOCRError(msg) {
        errorArea.classList.add('show');
        errorMessage.innerHTML = `<strong>❌ OCR Gagal:</strong><br>${msg}<br><br><button onclick="fillManually()" class="upload-button">✍️ Isi Manual</button>`;
        resetBtn.style.display = 'block';
    }

    function fillManually() {
        showEditForm('Mode isi manual diaktifkan');
        // Handle manual upload for temp file if needed
        const stored = loadFromLocalStorage();
        if (stored && stored.image && !tempFileInput.value) {
            fetch(stored.image).then(r => r.blob()).then(blob => {
                const fd = new FormData();
                fd.append('image', blob, 'manual.jpg');
                fd.append('_token', getCSRF());
                fetch('{{ route("dashboard.identity_test.save_base64") }}', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(d => { if(d.temp_file) tempFileInput.value = d.temp_file; });
            });
        }
    }

    function checkDatabase() {
        const citizenId = document.querySelector('[name="citizen_id"]').value;
        if (!citizenId) {
            showEditForm('Citizen ID tidak terbaca, silakan isi manual');
            return;
        }

        const fd = new FormData();
        fd.append('citizen_id', citizenId);
        fd.append('first_name', document.querySelector('[name="first_name"]').value);
        fd.append('last_name', document.querySelector('[name="last_name"]').value);
        fd.append('_token', getCSRF());

        fetch('{{ route("dashboard.identity_test.check") }}', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.auto_close) {
                    infoArea.classList.add('show');
                    infoMessage.innerHTML = `✅ ${d.message}`;
                    resetBtn.style.display = 'block';
                } else {
                    showEditForm(d.message || 'Verifikasi data lalu simpan');
                }
            });
    }

    function showEditForm(msg) {
        infoArea.classList.add('show');
        infoMessage.innerHTML = `⚠️ ${msg}`;
        saveBtn.style.display = 'block';
        resetBtn.style.display = 'block';
        document.querySelectorAll('.form-input').forEach(input => {
            if (input.name !== 'change_reason' && input.name !== 'custom_reason') {
                input.removeAttribute('readonly');
            }
        });
    }

    saveBtn.addEventListener('click', function() {
        const citizenId = document.querySelector('[name="citizen_id"]').value.trim();
        if (!citizenId) { alert('❌ Citizen ID wajib diisi'); return; }

        saveBtn.disabled = true;
        saveBtn.textContent = '⏳ Menyimpan...';

        let reason = changeReason.value;
        if (reason === 'custom') reason = customReasonInput.value;

        const fd = new FormData();
        fd.append('citizen_id', citizenId);
        fd.append('first_name', document.querySelector('[name="first_name"]').value);
        fd.append('last_name', document.querySelector('[name="last_name"]').value);
        fd.append('dob', document.querySelector('[name="dob"]').value);
        fd.append('sex', document.querySelector('[name="sex"]').value);
        fd.append('nationality', document.querySelector('[name="nationality"]').value);
        fd.append('change_reason', reason);
        fd.append('temp_file', tempFileInput.value);
        fd.append('_token', getCSRF());

        fetch('{{ route("dashboard.identity_test.save") }}', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.error) {
                    alert('❌ ' + d.error);
                    saveBtn.disabled = false;
                    saveBtn.textContent = '💾 Simpan Data';
                    return;
                }
                alert('✅ Data berhasil disimpan!');
                clearLocalStorage();
                location.reload();
            })
            .catch(err => {
                alert('❌ Error: ' + err.message);
                saveBtn.disabled = false;
            });
    });

    resetBtn.addEventListener('click', function() {
        if (confirm('Yakin ingin scan ulang?')) {
            clearLocalStorage();
            location.reload();
        }
    });

    window.addEventListener('DOMContentLoaded', () => {
        const stored = loadFromLocalStorage();
        if (stored && stored.image) {
            preview.src = stored.image;
            previewArea.classList.add('show');
            if (stored.ocr && !stored.ocr.error) {
                const d = stored.ocr;
                document.querySelector('[name="first_name"]').value = d.first_name || '';
                document.querySelector('[name="last_name"]').value = d.last_name || '';
                document.querySelector('[name="dob"]').value = d.dob || '';
                document.querySelector('[name="sex"]').value = d.sex || '';
                document.querySelector('[name="nationality"]').value = d.nationality || '';
                document.querySelector('[name="citizen_id"]').value = d.citizen_id || '';
                tempFileInput.value = d.temp_file || '';
                showEditForm('Sesi dipulihkan');
            }
        }
    });
</script>

@endsection
