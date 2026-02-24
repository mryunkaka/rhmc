</main>

</div>

<script src="/assets/legacy/js/app.js"></script>

<!-- jQuery & DataTables -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function realtimeSessionCheck() {
        let invalidCount = 0;
        setInterval(async () => {
            try {
                const res = await fetch('{{ route("auth.check-session") }}', {
                    credentials: 'same-origin'
                });

                const contentType = (res.headers.get('content-type') || '').toLowerCase();
                if (!res.ok || !contentType.includes('application/json')) {
                    return;
                }

                const data = await res.json();
                if (!data.valid) {
                    invalidCount++;
                    if (invalidCount < 2) return;
                    document.body.innerHTML = `
                        <div style="
                            position:fixed;inset:0;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            background:rgba(0,0,0,.6);
                            color:#fff;
                            font-size:18px;
                            z-index:99999">
                            <div>
                                <p>Session Anda berakhir</p>
                                <p>Silakan login kembali...</p>
                            </div>
                        </div>`;
                    setTimeout(() => {
                        window.location.href = '{{ route("login") }}';
                    }, 1500);
                }
                else {
                    invalidCount = 0;
                }
            } catch (e) {
                console.error('Session check failed', e);
            }
        }, 5000);
    })();
</script>

<script>
    setInterval(function() {
        var elements = document.querySelectorAll('.realtime-duration');

        elements.forEach(function(el) {
            var timestamp = parseInt(el.getAttribute('data-start-timestamp'));


            if (!timestamp) {
                return;
            }

            var now = Math.floor(Date.now() / 1000);
            var elapsed = now - timestamp;


            if (elapsed < 0) elapsed = 0;

            var hours = Math.floor(elapsed / 3600);
            var minutes = Math.floor((elapsed % 3600) / 60);
            var seconds = elapsed % 60;

            var display =
                (hours < 10 ? '0' : '') + hours + ':' +
                (minutes < 10 ? '0' : '') + minutes + ':' +
                (seconds < 10 ? '0' : '') + seconds;


            el.textContent = display;
        });
    }, 1000);
</script>

</body>

</html>
