@php
    $user = session('user_rh');
    $medicName    = $user['name'] ?? 'User';
    $medicJabatan = $user['position'] ?? '-';
    $medicRole    = $user['role'] ?? null;

    $dispInitials = initialsFromName($medicName);
    $dispColor    = avatarColorFromName($medicName);

    $userId = $user['id'] ?? 0;
    // Notification logic
    $notif = null;
    if ($userId) {
        try {
            $notif = \DB::table('user_farmasi_notifications')
                ->select('id', 'message')
                ->where('user_id', $userId)
                ->where('type', 'check_online')
                ->where('is_read', 0)
                ->orderBy('created_at', 'desc')
                ->first();
        } catch (\Throwable $e) {}
    }

    $pushConfig = [
        'public_key' => env('VAPID_PUBLIC_KEY', ''),
    ];
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ htmlspecialchars($pageTitle ?? 'Farmasi EMS') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="/assets/legacy/logo.png">
    <link rel="apple-touch-icon" href="/assets/legacy/logo.png">

    <!-- MODERN EMS CSS -->
    <link rel="stylesheet" href="/assets/legacy/css/app.css">
    <link rel="stylesheet" href="/assets/legacy/css/layout.css">
    <link rel="stylesheet" href="/assets/legacy/css/components.css">
    <link rel="stylesheet" href="/assets/legacy/css/responsive.css">

    <!-- DataTables (boleh, aman) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <link rel="stylesheet"
        href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <!-- jQuery (load FIRST - BEFORE DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables Core (load AFTER jQuery) -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <!-- DataTables Buttons (load AFTER DataTables Core) -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

</head>

<body>
    <audio id="inboxSound" preload="auto">
        <source src="/assets/legacy/sound/notification.mp3" type="audio/mpeg">
    </audio>

    <div class="ems-app">
        <header class="topbar">
            <!-- KIRI -->
            <button id="menuToggle" class="menu-btn">☰</button>

            <div class="topbar-brand">
                <img src="/assets/legacy/logo.png" alt="EMS Logo" class="topbar-logo">
                <div class="topbar-text">
                    <div class="topbar-title">Roxwood Hospital</div>
                    <div class="topbar-subtitle">Emergency Medical System</div>
                </div>
            </div>

            <!-- KANAN (INBOX) -->
            <div class="topbar-actions">
                <div class="notif-wrapper">
                    <button id="enableNotif" class="notif-btn" title="Aktifkan Notifikasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="notif-indicator hidden"></span>
                    </button>
                </div>

                <div class="inbox-wrapper">
                    <button id="inboxBtn" class="inbox-btn">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <span id="inboxBadge" class="inbox-badge">0</span>
                    </button>

                    <div id="inboxDropdown" class="inbox-dropdown hidden">
                        <div class="inbox-header">Inbox</div>
                        <ul id="inboxList"></ul>
                    </div>
                </div>
            </div>

        </header>

        <div id="inboxModal" class="hidden inbox-modal-overlay">
            <div class="inbox-modal-box">
                <h3 id="modalTitle"></h3>
                <p id="modalMessage"></p>

                <div class="inbox-modal-actions">
                    <button onclick="closeInboxModal()">Cancel</button>
                    <button onclick="deleteInbox()" class="btn-danger">Hapus</button>
                </div>
            </div>
        </div>

        <script>
            let notifTimer = null;
            let offlineTimer = null;
            let deadlineTime = null;

            let onlineModalActive = false;

            // =========================
            // UTIL FORMAT WAKTU
            // =========================
            function formatTime(ms) {
                const total = Math.max(0, Math.floor(ms / 1000));
                const m = String(Math.floor(total / 60)).padStart(2, '0');
                const s = String(total % 60).padStart(2, '0');
                return `${m}:${s}`;
            }

            // =========================
            // START COUNTDOWN DARI DEADLINE NYATA
            // =========================
            function startCountdownFromSeconds(seconds) {
                const el = document.getElementById('countdown');
                if (!el || seconds <= 0) {
                    el.textContent = '00:00';
                    return;
                }

                let remaining = seconds;

                if (offlineTimer) clearInterval(offlineTimer);

                el.textContent = formatTime(remaining * 1000);

                offlineTimer = setInterval(() => {
                    remaining--;

                    if (remaining <= 0) {
                        clearInterval(offlineTimer);
                        el.textContent = '00:00';
                        return;
                    }

                    el.textContent = formatTime(remaining * 1000);
                }, 1000);
            }

            // =========================
            // TAMPILKAN MODAL
            // =========================
            function showOnlineModal(message, remainingSeconds) {

                // Jika modal sudah aktif → jangan buat ulang
                if (onlineModalActive) return;

                onlineModalActive = true;

                const modal = document.createElement('div');
                modal.id = 'onlineModal';
                modal.style = `
                    position:fixed;
                    inset:0;
                    background:rgba(0,0,0,.55);
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    z-index:999999;
                `;

                modal.innerHTML = `
                    <div style="
                        background:#fff;
                        padding:20px;
                        border-radius:12px;
                        max-width:360px;
                        width:90%;
                        text-align:center;
                        box-shadow:0 20px 40px rgba(0,0,0,.3);
                    ">
                        <h3>⏱️ Konfirmasi Status Farmasi</h3>
                        <p>${message}</p>
                        <p style="font-size:13px;color:#6b7280">
                            Akan otomatis offline dalam
                            <strong><span id="countdown">--:--</span></strong>
                        </p>

                        <div style="display:flex;gap:10px;justify-content:center;margin-top:16px;">
                            <button onclick="confirmOnline()" class="btn-success">
                                Ya, masih online
                            </button>
                            <button onclick="setOffline()" class="btn-danger">
                                Saya tidak tersedia
                            </button>
                        </div>
                    </div>
                `;

                document.body.appendChild(modal);

                // START COUNTDOWN (INI YANG SEBELUMNYA ERROR)
                startCountdownFromSeconds(remainingSeconds);
            }


            // =========================
            // KONFIRMASI ONLINE
            // =========================
            function confirmOnline() {
                fetch('/actions/confirm_farmasi_online.php', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    })
                    .then(() => {
                        if (offlineTimer) clearInterval(offlineTimer);
                        removeModal();
                    });
            }

            function setOffline(auto = false) {
                if (!auto) {
                    if (!confirm('Anda akan diset OFFLINE dan tidak menerima transaksi farmasi. Lanjutkan?')) {
                        return;
                    }
                }

                fetch('/actions/set_farmasi_offline.php', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    })
                    .then(() => {
                        if (offlineTimer) clearInterval(offlineTimer);
                        removeModal();
                    });
            }

            // =========================
            // HAPUS MODAL
            // =========================
            function removeModal() {
                const modal = document.getElementById('onlineModal');
                if (modal) modal.remove();

                onlineModalActive = false;

                if (offlineTimer) {
                    clearInterval(offlineTimer);
                    offlineTimer = null;
                }
            }

            // =========================
            // CEK NOTIF + DEADLINE NYATA
            // =========================
            async function checkFarmasiNotif() {
                try {
                    const notifRes = await fetch('/actions/check_farmasi_notif.php', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        cache: 'no-store'
                    });
                    const notif = await notifRes.json();

                    /*
                    |-------------------------------------------------
                    | JIKA USER SUDAH OFFLINE → PAKSA HAPUS MODAL
                    |-------------------------------------------------
                    */
                    if (notif.status && notif.status === 'offline') {
                        removeModal();
                        return;
                    }

                    if (!notif.has_notif) {
                        return;
                    }

                    const dlRes = await fetch('/actions/get_farmasi_deadline.php', {
                        cache: 'no-store'
                    });
                    const dl = await dlRes.json();

                    if (dl.active && dl.remaining && !onlineModalActive) {
                        showOnlineModal(notif.message, dl.remaining);
                    }

                } catch (e) {
                    console.error('Farmasi notif error', e);
                }
            }

            // POLLING AMAN
            notifTimer = setInterval(checkFarmasiNotif, 5000);
        </script>
        <script>
            /* ======================================================
   HEARTBEAT — GLOBAL ACTIVITY TRACKER
   ====================================================== */

            let heartbeatTimer = null;

            /**
             * Kirim heartbeat ke server
             * HANYA update last_activity_at jika status ONLINE
             */
            async function sendHeartbeat() {
                try {
                    const res = await fetch('/actions/heartbeat.php', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        cache: 'no-store'
                    });

                    const data = await res.json();

                    // JIKA USER SUDAH OFFLINE
                    if (!data.active) {
                        stopHeartbeat();

                        // HAPUS MODAL KONFIRMASI JIKA MASIH ADA
                        removeModal();
                    }

                } catch (e) {
                    console.warn('Heartbeat gagal', e);
                }
            }

            /**
             * Mulai heartbeat
             */
            function startHeartbeat() {
                if (heartbeatTimer) return;

                // setiap 20 detik (aman & ringan)
                heartbeatTimer = setInterval(sendHeartbeat, 15000);
            }

            /**
             * Hentikan heartbeat
             */
            function stopHeartbeat() {
                if (heartbeatTimer) {
                    clearInterval(heartbeatTimer);
                    heartbeatTimer = null;
                }
            }

            /* ======================================================
               AUTO START HEARTBEAT SAAT PAGE LOAD
               ====================================================== */
            document.addEventListener('DOMContentLoaded', () => {
                startHeartbeat();
            });
        </script>
        <script>
            const inboxBtn = document.getElementById('inboxBtn');
            const inboxDropdown = document.getElementById('inboxDropdown');
            const inboxList = document.getElementById('inboxList');
            const inboxBadge = document.getElementById('inboxBadge');

            inboxBtn.addEventListener('click', () => {
                inboxDropdown.classList.toggle('hidden');
                loadInbox();
            });

            async function loadInbox() {
                try {
                    const res = await fetch('/actions/get_inbox.php', {
                        cache: 'no-store',
                        credentials: 'same-origin'
                    });

                    const contentType = (res.headers.get('content-type') || '').toLowerCase();
                    if (!res.ok || !contentType.includes('application/json')) {
                        return;
                    }

                    const data = await res.json();

                    inboxList.innerHTML = '';
                    inboxBadge.textContent = data.unread;

                    if (data.items.length === 0) {
                        inboxList.innerHTML = '<li style="padding:12px;color:#888">Tidak ada inbox</li>';
                        return;
                    }

                    data.items.forEach(item => {
                        const li = document.createElement('li');
                        li.className = 'inbox-item ' + (item.is_read ? 'read' : 'unread');
                        li.innerHTML = `
            <div>${item.title}</div>
            <small>${item.created_at_label}</small>

        `;
                        li.onclick = () => openInboxModal(item);
                        inboxList.appendChild(li);
                    });
                } catch (e) {
                    console.warn('Load inbox error', e);
                }
            }
        </script>
        <script>
            let currentInboxId = null;

            function openInboxModal(item) {
                currentInboxId = item.id;

                document.getElementById('modalTitle').textContent = item.title;
                document.getElementById('modalMessage').innerHTML = item.message;
                document.getElementById('inboxModal').classList.remove('hidden');

                fetch('/actions/read_inbox.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new URLSearchParams({
                        id: item.id
                    })
                });

                loadInbox(); // refresh badge
            }

            function closeInboxModal() {
                document.getElementById('inboxModal').classList.add('hidden');
            }

            function deleteInbox() {
                if (!currentInboxId) return;

                fetch('/actions/delete_inbox.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: new URLSearchParams({
                        id: currentInboxId
                    })
                }).then(() => {
                    closeInboxModal();
                    loadInbox();
                });
            }
        </script>
        <script>
            /* ======================================================
   INBOX AUTO POLLING (REALTIME LIGHT)
   ====================================================== */

            let inboxPollingTimer = null;
            let lastUnreadCount = 0;

            /**
             * Polling inbox (ambil data terbaru)
             */
             async function pollInbox() {
                 try {
                     const res = await fetch('/actions/get_inbox.php', {
                        cache: 'no-store',
                        credentials: 'same-origin'
                     });

                    const contentType = (res.headers.get('content-type') || '').toLowerCase();
                    if (!res.ok || !contentType.includes('application/json')) return;

                     const data = await res.json();

                    // Update badge
                    inboxBadge.textContent = data.unread;
                    inboxBadge.style.display = data.unread > 0 ? 'inline-block' : 'none';

                    // Jika ada inbox BARU
                    if (data.unread > lastUnreadCount) {
                        onNewInbox(data.unread - lastUnreadCount);
                    }

                    lastUnreadCount = data.unread;

                    // Jika dropdown sedang terbuka → refresh list
                    if (!inboxDropdown.classList.contains('hidden')) {
                        renderInboxList(data.items);
                    }

                } catch (e) {
                    console.warn('Inbox polling error', e);
                }
            }

            /**
             * Render inbox list (dipakai polling & click)
             */
            function renderInboxList(items) {
                inboxList.innerHTML = '';

                if (!items || items.length === 0) {
                    inboxList.innerHTML =
                        '<li style="padding:12px;color:#888">Tidak ada inbox</li>';
                    return;
                }

                items.forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'inbox-item ' + (item.is_read ? 'read' : 'unread');
                    li.innerHTML = `
            <div>${item.title}</div>
            <small>${item.created_at_label}</small>
        `;
                    li.onclick = () => openInboxModal(item);
                    inboxList.appendChild(li);
                });
            }

            /**
             * Event jika inbox baru masuk
             */
            function onNewInbox(count) {
                // Badge pulse
                inboxBadge.classList.add('pulse');
                setTimeout(() => inboxBadge.classList.remove('pulse'), 800);

                // Bunyi notif (optional)
                playInboxSound();
            }

            /**
             * Sound notif (safe)
             */
            function playInboxSound() {
                const audio = document.getElementById('inboxSound');
                if (audio) {
                    audio.currentTime = 0;
                    audio.play().catch(() => {});
                }
            }

            /**
             * Start polling
             */
            function startInboxPolling() {
                if (inboxPollingTimer) return;

                pollInbox(); // langsung sekali saat load
                inboxPollingTimer = setInterval(pollInbox, 10000); // 10 detik
            }

            /**
             * Stop polling (optional)
             */
            function stopInboxPolling() {
                if (inboxPollingTimer) {
                    clearInterval(inboxPollingTimer);
                    inboxPollingTimer = null;
                }
            }

            /* AUTO START */
            document.addEventListener('DOMContentLoaded', startInboxPolling);
        </script>
        <script>
            const PUSH_PUBLIC_KEY = '{{ htmlspecialchars($pushConfig['public_key']) }}';
            const CSRF_TOKEN = '{{ csrf_token() }}';
            window.CSRF_TOKEN = CSRF_TOKEN;
        </script>

        <script src="/public/push-subscribe.js"></script>

        <script>
            document.addEventListener("DOMContentLoaded", async () => {
                if (!("serviceWorker" in navigator)) return;

                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();

                if (sub) {
                    const indicator = document.querySelector('.notif-indicator');
                    if (indicator) indicator.classList.remove('hidden');
                }
            });

            document.addEventListener('DOMContentLoaded', () => {
                const btn = document.getElementById('enableNotif');
                if (!btn) {
                    console.error('Tombol enableNotif tidak ditemukan');
                    return;
                }

                btn.addEventListener('click', () => {
                    console.log('Enable notif diklik');
                    initPush();
                });
            });
        </script>
