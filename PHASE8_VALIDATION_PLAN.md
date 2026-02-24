# PHASE 8 - VALIDATION PLAN (FINAL)

**Date:** 2026-02-23  
**Architecture Mode:** `MIRROR_TOTAL_LOCKED`  
**Goal:** memastikan output Laravel (2035) identik dengan Legacy (2036) secara visual & fungsional, tanpa perubahan DB/arsitektur.

---

## 1) Prasyarat

- Legacy berjalan: `http://localhost:2036`
- Laravel berjalan: `http://localhost:2035`
- `.env` Laravel terhubung ke database legacy (tanpa perubahan struktur)
- Tidak ada refactor/redesign/optimasi query (mirror total)

---

## 2) Pre-check (wajib)

1. Clear cache Laravel:
   - `php artisan optimize:clear`
   - `php artisan view:clear`
   - `php artisan route:clear`
2. Autoload refresh:
   - `composer dump-autoload`
3. Route sanity:
   - `php artisan route:list` (pastikan route utama terdaftar dan tidak bentrok)
4. Log sanity:
   - cek `storage/logs/laravel.log` (tidak ada error baru saat akses halaman utama)

---

## 3) Checklist Validasi (Manual)

### A. Authentication
- Login page: Laravel vs Legacy (tampilan identik)
- Login process: session set + redirect sesuai legacy
- Register: alur & validasi identik
- Logout: session cleared (mendukung metode sesuai implementasi saat ini)
- Session check: polling endpoint berjalan (AJAX)

### B. Dashboard & Modules
Verifikasi halaman berikut (Laravel vs Legacy) untuk:
- HTML output/struktur layout
- CSS/JS asset loading (urutan jQuery/DataTables/Chart.js)
- Query/hasil data
- Form submit/AJAX handlers
- Error states (empty data, invalid input)

**Dashboard Core**
- `/dashboard`
- `/dashboard/rekap-farmasi`
- `/dashboard/rekap-farmasi-v2`

**Simple Pages**
- `/dashboard/settings/akun`
- `/dashboard/ranking`
- `/dashboard/regulasi`
- `/dashboard/ems-services`
- `/dashboard/identity-test`

**Medium Complexity**
- `/dashboard/konsumen`
- `/dashboard/users`
- `/dashboard/absensi-ems`
- `/dashboard/operasi-plastik`
- `/dashboard/events`

**Recruitment**
- `/dashboard/candidates`
- Kandidat detail/interview/decision flow (fitur & state identik legacy)

**Payroll**
- `/dashboard/salary` (termasuk submit/generate sesuai legacy)

**Reimbursement & Restaurant**
- `/dashboard/reimbursement`
- `/dashboard/restaurant`
- `/dashboard/restaurant/settings` (jika digunakan di legacy)

**Validasi**
- `/dashboard/validasi`

**Settings & Tools**
- `/dashboard/settings/spreadsheet`
- POST `/dashboard/settings/sync-sheet`

### C. Public Pages (Recruitment)
- `/recruitment`
- POST `/recruitment/submit`
- `/recruitment/ai-test`
- POST `/recruitment/ai-test/submit`
- `/recruitment/done`

---

## 4) Acceptance Criteria (Definition of Done)

- Semua route legacy yang aktif punya mirror di Laravel
- Output visual identik (layout, ikon/emoji, spacing, tabel, chart)
- Output fungsional identik (submit, AJAX, export/import, status toggle)
- Tidak ada perubahan struktur database, query intent, atau arsitektur
- Tidak ada error baru di log saat validasi lengkap

---

## 5) Output Dokumentasi

Jika semua lolos:
- Update `PHASE8_TEST_RESULTS.md` (jumlah test + status)
- Update `PHASE_STATE.json` (`status: COMPLETED`, task terakhir, notes)

