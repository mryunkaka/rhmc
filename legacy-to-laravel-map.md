# LEGACY TO LARAVEL MAPPING 1:1

**Tanggal:** 2026-02-22
**Metode:** MIRROR TOTAL - Setiap file legacy = 1 route Laravel

---

## ROOT ROUTE

```
legacy/index.php
  → Route   : GET /
  → Controller: RedirectController@index
  → Action  : redirect('/dashboard/rekap-farmasi')
```

---

## AUTHENTICATION ROUTES

```
legacy/auth/login.php
  → Route   : GET /login
  → Controller: AuthController@showLogin
  → Blade   : auth/login.blade.php

legacy/auth/login_process.php
  → Route   : POST /login
  → Controller: AuthController@login
  → Blade   : - (redirect after login)

legacy/auth/logout.php
  → Route   : POST /logout
  → Controller: AuthController@logout
  → Blade   : - (redirect)

legacy/auth/register_process.php
  → Route   : POST /register
  → Controller: AuthController@register
  → Blade   : - (redirect)

legacy/auth/check_session.php
  → Route   : GET /auth/check-session
  → Controller: AuthController@checkSession
  → Blade   : - (JSON response)
```

---

## DASHBOARD ROUTES

```
legacy/dashboard/index.php
  → Route   : GET /dashboard
  → Controller: DashboardController@index
  → Blade   : dashboard/index.blade.php

legacy/dashboard/rekap_farmasi.php
  → Route   : GET /dashboard/rekap-farmasi
  → Controller: DashboardController@rekapFarmasi
  → Blade   : dashboard/rekap_farmasi.blade.php

legacy/dashboard/rekap_farmasi_v2.php
  → Route   : GET /dashboard/rekap-farmasi-v2
  → Controller: DashboardController@rekapFarmasiV2
  → Blade   : dashboard/rekap_farmasi_v2.blade.php

legacy/dashboard/dashboard_data.php
  → Route   : - (tidak diakses langsung)
  → Controller: - (di-include di DashboardController)
  → Blade   : -
```

---

## ABSENSI ROUTES

```
legacy/dashboard/absensi_ems.php
  → Route   : GET /dashboard/absensi-ems
  → Controller: AbsensiController@index
  → Blade   : dashboard/absensi_ems.blade.php
```

---

## CANDIDATE/RECRUITMENT ROUTES

```
legacy/dashboard/candidates.php
  → Route   : GET /dashboard/candidates
  → Controller: CandidateController@index
  → Blade   : dashboard/candidates.blade.php

legacy/dashboard/candidate_detail.php
  → Route   : GET /dashboard/candidates/{id}
  → Controller: CandidateController@detail
  → Blade   : dashboard/candidate_detail.blade.php

legacy/dashboard/candidate_decision.php
  → Route   : POST /dashboard/candidates/{id}/decision
  → Controller: CandidateController@decision
  → Blade   : -

legacy/dashboard/candidate_interview_multi.php
  → Route   : GET /dashboard/candidates/interview-multi
  → Controller: CandidateController@interviewMulti
  → Blade   : dashboard/candidate_interview_multi.blade.php
```

---

## EVENT ROUTES

```
legacy/dashboard/events.php
  → Route   : GET /dashboard/events
  → Controller: EventController@index
  → Blade   : dashboard/events.blade.php

legacy/dashboard/event_manage.php
  → Route   : GET /dashboard/events/manage
  → Controller: EventController@manage
  → Blade   : dashboard/event_manage.blade.php

legacy/dashboard/event_action.php
  → Route   : POST /dashboard/events/action
  → Controller: EventController@action
  → Blade   : -

legacy/dashboard/event_participants.php
  → Route   : GET /dashboard/events/participants
  → Controller: EventController@participants
  → Blade   : dashboard/event_participants.blade.php
```

---

## EMS SERVICES ROUTES

```
legacy/dashboard/ems_services.php
  → Route   : GET /dashboard/ems-services
  → Controller: EmsServiceController@index
  → Blade   : dashboard/ems_services.blade.php
```

---

## SALARY/GAJI ROUTES

```
legacy/dashboard/gaji.php
  → Route   : GET /dashboard/gaji
  → Controller: GajiController@index
  → Blade   : dashboard/gaji.blade.php

legacy/dashboard/gaji_action.php
  → Route   : POST /dashboard/gaji/action
  → Controller: GajiController@action
  → Blade   : -

legacy/dashboard/gaji_generate_manual.php
  → Route   : GET /dashboard/gaji/generate-manual
  → Controller: GajiController@generateManual
  → Blade   : dashboard/gaji_generate_manual.blade.php

legacy/dashboard/gaji_pay_process.php
  → Route   : POST /dashboard/gaji/pay-process
  → Controller: GajiController@payProcess
  → Blade   : -

legacy/dashboard/rekap_gaji.php
  → Route   : GET /dashboard/rekap-gaji
  → Controller: GajiController@rekap
  → Blade   : dashboard/rekap_gaji.blade.php
```

---

## KONSUMEN ROUTES

```
legacy/dashboard/konsumen.php
  → Route   : GET /dashboard/konsumen
  → Controller: KonsumenController@index
  → Blade   : dashboard/konsumen.blade.php
```

---

## USER MANAGEMENT ROUTES

```
legacy/dashboard/manage_users.php
  → Route   : GET /dashboard/users
  → Controller: UserController@index
  → Blade   : dashboard/manage_users.blade.php

legacy/dashboard/manage_users_action.php
  → Route   : POST /dashboard/users/action
  → Controller: UserController@action
  → Blade   : -
```

---

## OPERASI PLASTIK ROUTES

```
legacy/dashboard/operasi_plastik.php
  → Route   : GET /dashboard/operasi-plastik
  → Controller: OperasiPlastikController@index
  → Blade   : dashboard/operasi_plastik.blade.php

legacy/dashboard/operasi_plastik_action.php
  → Route   : POST /dashboard/operasi-plastik/action
  → Controller: OperasiPlastikController@action
  → Blade   : -
```

---

## RANKING ROUTES

```
legacy/dashboard/ranking.php
  → Route   : GET /dashboard/ranking
  → Controller: RankingController@index
  → Blade   : dashboard/ranking.blade.php
```

---

## REGULASI ROUTES

```
legacy/dashboard/regulasi.php
  → Route   : GET /dashboard/regulasi
  → Controller: RegulasiController@index
  → Blade   : dashboard/regulasi.blade.php
```

---

## REIMBURSEMENT ROUTES

```
legacy/dashboard/reimbursement.php
  → Route   : GET /dashboard/reimbursement
  → Controller: ReimbursementController@index
  → Blade   : dashboard/reimbursement.blade.php

legacy/dashboard/reimbursement_action.php
  → Route   : POST /dashboard/reimbursement/action
  → Controller: ReimbursementController@action
  → Blade   : -

legacy/dashboard/reimbursement_delete.php
  → Route   : POST /dashboard/reimbursement/delete
  → Controller: ReimbursementController@delete
  → Blade   : -

legacy/dashboard/reimbursement_pay.php
  → Route   : POST /dashboard/reimbursement/pay
  → Controller: ReimbursementController@pay
  → Blade   : -
```

---

## RESTAURANT ROUTES

```
legacy/dashboard/restaurant_consumption.php
  → Route   : GET /dashboard/restaurant/consumption
  → Controller: RestaurantController@consumption
  → Blade   : dashboard/restaurant_consumption.blade.php

legacy/dashboard/restaurant_consumption_action.php
  → Route   : POST /dashboard/restaurant/consumption/action
  → Controller: RestaurantController@consumptionAction
  → Blade   : -

legacy/dashboard/restaurant_settings.php
  → Route   : GET /dashboard/restaurant/settings
  → Controller: RestaurantController@settings
  → Blade   : dashboard/restaurant_settings.blade.php

legacy/dashboard/restaurant_settings_action.php
  → Route   : POST /dashboard/restaurant/settings/action
  → Controller: RestaurantController@settingsAction
  → Blade   : -
```

---

## SETTINGS ROUTES

```
legacy/dashboard/setting_akun.php
  → Route   : GET /dashboard/settings/akun
  → Controller: SettingController@akun
  → Blade   : dashboard/setting_akun.blade.php

legacy/dashboard/setting_akun_action.php
  → Route   : POST /dashboard/settings/akun
  → Controller: SettingController@akunAction
  → Blade   : -

legacy/dashboard/setting_spreadsheet.php
  → Route   : GET /dashboard/settings/spreadsheet
  → Controller: SettingController@spreadsheet
  → Blade   : dashboard/setting_spreadsheet.blade.php

legacy/dashboard/sync_from_sheet.php
  → Route   : GET /dashboard/settings/sync-sheet
  → Controller: SettingController@syncSheet
  → Blade   : dashboard/sync_from_sheet.blade.php
```

---

## VALIDASI ROUTES

```
legacy/dashboard/validasi.php
  → Route   : GET /dashboard/validasi
  → Controller: ValidasiController@index
  → Blade   : dashboard/validasi.blade.php

legacy/dashboard/validasi_action.php
  → Route   : POST /dashboard/validasi/action
  → Controller: ValidasiController@action
  → Blade   : -
```

---

## REKAP DELETE BULK ROUTE

```
legacy/dashboard/rekap_delete_bulk.php
  → Route   : POST /dashboard/rekap/delete-bulk
  → Controller: RekapController@deleteBulk
  → Blade   : -
```

---

## IDENTITY TEST ROUTE

```
legacy/dashboard/identity_test.php
  → Route   : GET /dashboard/identity-test
  → Controller: IdentityTestController@index
  → Blade   : dashboard/identity_test.blade.php
```

---

## PUBLIC RECRUITMENT ROUTES (NO AUTH)

```
legacy/public/recruitment_form.php
  → Route   : GET /recruitment
  → Controller: PublicController@recruitmentForm
  → Blade   : public/recruitment_form.blade.php

legacy/public/recruitment_submit.php
  → Route   : POST /recruitment/submit
  → Controller: PublicController@recruitmentSubmit
  → Blade   : -

legacy/public/recruitment_done.php
  → Route   : GET /recruitment/done
  → Controller: PublicController@recruitmentDone
  → Blade   : public/recruitment_done.blade.php

legacy/public/ai_test.php
  → Route   : GET /ai-test
  → Controller: PublicController@aiTest
  → Blade   : public/ai_test.blade.php

legacy/public/ai_test_submit.php
  → Route   : POST /ai-test/submit
  → Controller: PublicController@aiTestSubmit
  → Blade   : -
```

---

## API ROUTES

```
legacy/api/sync_sales.php
  → Route   : POST /api/sync-sales
  → Controller: Api\ApiController@syncSales
  → Blade   : - (JSON response)
  → Middleware: api.auth
```

---

## ACTION/AJAX ROUTES

```
legacy/actions/check_farmasi_notif.php
  → Route   : GET /actions/check-farmasi-notif
  → Controller: ActionController@checkFarmasiNotif

legacy/actions/confirm_farmasi_online.php
  → Route   : POST /actions/confirm-farmasi-online
  → Controller: ActionController@confirmFarmasiOnline

legacy/actions/get_inbox.php
  → Route   : GET /actions/get-inbox
  → Controller: ActionController@getInbox

legacy/actions/heartbeat.php
  → Route   : POST /actions/heartbeat
  → Controller: ActionController@heartbeat

[... 23+ action files lainnya ...]
```

---

## BLADE PARTIALS (NO ROUTES)

```
legacy/partials/header.php
  → resources/views/layouts/partials/header.blade.php

legacy/partials/sidebar.php
  → resources/views/layouts/partials/sidebar.blade.php

legacy/partials/footer.php
  → resources/views/layouts/partials/footer.blade.php
```

---

## SUMMARY

- **Total Routes:** ~60 route
- **Controllers:** ~20 controller
- **Blade Files:** ~50 blade file
- **API Routes:** ~1 route (middleware api.auth)
- **Public Routes:** 5 routes (no auth)
- **Dashboard Routes:** ~42 routes (require auth)

---

## CONTROLLER LIST

1. RedirectController
2. AuthController
3. DashboardController
4. AbsensiController
5. CandidateController
6. EventController
7. EmsServiceController
8. GajiController
9. KonsumenController
10. UserController
11. OperasiPlastikController
12. RankingController
13. RegulasiController
14. ReimbursementController
15. RestaurantController
16. SettingController
17. ValidasiController
18. RekapController
19. IdentityTestController
20. PublicController
21. Api\ApiController
22. ActionController
