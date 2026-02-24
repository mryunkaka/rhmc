# 🔥 MASTER PLAN - RHMC MIGRATION

```
ARCHITECTURE MODE: MIRROR_TOTAL_LOCKED
STATUS: IMMUTABLE – TIDAK BOLEH DIUBAH SAMPAI MIGRASI 100% SELESAI
```

---

## 📌 DEFINISI MIRROR TOTAL

Laravel hanya menjadi MVC wrapper dari legacy PHP Native. Tidak lebih, tidak kurang.

**Yang dilakukan:**
- Copy paste logic PHP → Controller
- Copy paste HTML → Blade
- Copy paste CSS/JS → Public assets
- Mapping URL legacy → Route Laravel

**Yang TIDAK dilakukan:**
- ❌ Refactor kode
- ❌ Redesign UI
- ❌ Optimasi query
- ❌ Perubahan arsitektur
- ❌ Service layer / Repository pattern
- ❌ Mengubah nama variabel/fungsi
- ❌ Mengubah struktur database

---

## 🎯 TUJUAN PROJECT

**Nama:** Roxwood Health Medical Center (RHMC)
**Migrasi:** PHP Native → Laravel 12
**Metode:** MIRROR TOTAL

---

## 📁 PATH KONFIGURASI

| Item | Path | Port |
|------|------|------|
| Legacy (PHP Native) | `D:\Project\Web\rhmc\legacy` | 2036 |
| Laravel 12 | `D:\Project\Web\rhmc\laravel` | 2035 |
| Document Root Laravel | `D:\Project\Web\rhmc\laravel\public` | - |

---

## 🔒 IMMUTABLE ARCHITECTURE RULES

Aturan berikut BERLAKU SELAMANYA dan TIDAK BOLEH DILANGGAR:

1. Planning ini terkunci sejak dibuat
2. Tidak boleh improvisasi
3. Tidak boleh mengubah pendekatan migrasi
4. Tidak boleh membuat arsitektur baru
5. Tidak boleh refactor atau redesign
6. Tidak boleh menambah layer baru (service, repository, dll)
7. Tidak boleh mengubah query SQL
8. Tidak boleh mengubah struktur database
9. Tidak boleh mengganti/drop tabel atau kolom
10. Tidak boleh membuat planning baru jika terjadi crash

---

## 📋 DAFTAR PHASE

```
Phase 0 – Verifikasi Environment
Phase 1 – Full Legacy Scan
Phase 2 – File Mapping 1:1
Phase 3 – Install Laravel 12
Phase 4 – Copy Legacy Assets
Phase 5 – Database Mirror Per Table
Phase 6 – Dashboard Migration
Phase 7 – All Page Migration
Phase 8 – Final Validation
```

---

## 🔴 CRASH PROTOCOL

Jika terjadi crash/limit/disconnect:

1. Baca MASTER_PLAN.md (file ini)
2. Baca PHASE_STATE.json
3. Baca TODO.md
4. Lanjut dari task terakhir yang belum selesai
5. **DILARANG membuat planning baru**

---

## ✅ DEFINISI SELESAI

Migrasi selesai jika:
- [ ] Semua halaman legacy memiliki versi Laravel
- [ ] Output visual & fungsional identik
- [ ] Database tidak berubah
- [ ] Legacy (2036) masih berjalan normal
- [ ] Laravel (2035) berjalan stabil
- [ ] Tidak ada fitur hilang

---

```
ARCHITECTURE_MODE: MIRROR_TOTAL_LOCKED
DOKUMEN INI IMMUTABLE
DISET: 2026-02-22
```
