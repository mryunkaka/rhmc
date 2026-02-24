<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\RegulasiController;
use App\Http\Controllers\EmsServiceController;
use App\Http\Controllers\RekapFarmasiController;
use App\Http\Controllers\IdentityTestController;
use App\Http\Controllers\KonsumenController;
use App\Http\Controllers\ManageUserController;
use App\Http\Controllers\AbsensiEmsController;
use App\Http\Controllers\OperasiPlastikController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\ValidasiController;
use App\Http\Controllers\PublicRecruitmentController;

/* |-------------------------------------------------------------------------- | Web Routes |-------------------------------------------------------------------------- | | Here is where you can register web routes for your application. These | routes are loaded by the AuthController and will be assigned to the "web" | middleware group. Make something great! | */

// Auth Routes (mirror from legacy/auth/)
Route::get('/login', [AuthController::class , 'showLogin'])->name('login');
Route::post('/login', [AuthController::class , 'login'])->name('login.process');
Route::post('/register', [AuthController::class , 'register'])->name('register.process');
Route::match (['get', 'post'], '/logout', [AuthController::class , 'logout'])->name('logout');
Route::get('/auth/check-session', [AuthController::class , 'checkSession'])->name('auth.check-session');

// ============================================================
// PUBLIC ACTION ROUTES (mirror legacy behavior)
// ============================================================
// Legacy scripts return JSON defaults even when not logged in (session empty),
// so these endpoints are intentionally NOT protected by auth middleware.
Route::get('/actions/get_farmasi_status.php', [RekapFarmasiController::class, 'getStatus']);
Route::get('/actions/get_inbox.php', [ActionController::class , 'getInbox']);
Route::get('/actions/get_activities.php', [RekapFarmasiController::class, 'getActivities']);
Route::get('/actions/get_global_cooldown.php', [RekapFarmasiController::class, 'getGlobalCooldown']);

// Root route - mirror legacy/index.php
Route::get('/', function () {
    return redirect('/dashboard/rekap-farmasi');
});

// Public Recruitment Routes
Route::get('/recruitment', [PublicRecruitmentController::class, 'showForm'])->name('public.recruitment.form');
Route::post('/recruitment/submit', [PublicRecruitmentController::class, 'submitForm'])->name('public.recruitment.submit');
Route::get('/recruitment/ai-test', [PublicRecruitmentController::class, 'showAiTest'])->name('public.recruitment.ai_test');
Route::post('/recruitment/ai-test/submit', [PublicRecruitmentController::class, 'submitAiTest'])->name('public.recruitment.ai_test.submit');
Route::get('/recruitment/done', [PublicRecruitmentController::class, 'showDone'])->name('public.recruitment.done');

// Public Routes (No Auth)
Route::get('/dashboard/events', [EventController::class , 'index'])->name('dashboard.events');
Route::post('/dashboard/events/register', [EventController::class , 'register'])->name('dashboard.events.register');
Route::get('/ajax/search_user_rh', [ActionController::class , 'searchUserRh']);
Route::get('/ajax/search_user_rh.php', [ActionController::class , 'searchUserRh']);

// Dashboard Routes (mirror from legacy/dashboard/)
Route::get('/dashboard', [DashboardController::class , 'index'])
    ->name('dashboard')
    ->middleware('auth.user_rh');

// ============================================================
// SETTING ROUTES (mirror from legacy/dashboard/setting_*)
// ============================================================
Route::middleware(['auth.user_rh'])->group(function () {
    Route::get('/dashboard/settings/akun', [SettingController::class , 'akun'])
        ->name('settings.akun');
    Route::post('/dashboard/settings/akun', [SettingController::class , 'akunAction'])
        ->name('settings.akun.action');

    // Spreadsheet Settings
    Route::get('/dashboard/settings/spreadsheet', [SettingController::class , 'spreadsheet'])
        ->name('settings.spreadsheet');
    Route::post('/dashboard/settings/spreadsheet', [SettingController::class , 'spreadsheetAction'])
        ->name('settings.spreadsheet.action');
    Route::post('/dashboard/settings/sync-sheet', [SettingController::class , 'syncSheet'])
        ->name('settings.sync_sheet');

    // Ranking
    Route::get('/dashboard/ranking', [RankingController::class , 'index'])
        ->name('dashboard.ranking');

    // Regulasi
    Route::get('/dashboard/regulasi', [RegulasiController::class , 'index'])
        ->name('dashboard.regulasi');
    Route::post('/dashboard/regulasi/update', [RegulasiController::class , 'update'])
        ->name('dashboard.regulasi.update');

    // EMS Services
    Route::get('/dashboard/ems-services', [EmsServiceController::class , 'index'])
        ->name('dashboard.ems_services');
    Route::post('/dashboard/ems-services', [EmsServiceController::class , 'store'])
        ->name('dashboard.ems_services.store');
    Route::post('/dashboard/ems-services/preview', [EmsServiceController::class , 'previewPrice'])
        ->name('dashboard.ems_services.preview');
    Route::post('/dashboard/ems-services/delete-bulk', [EmsServiceController::class , 'destroyBulk'])
        ->name('dashboard.ems_services.destroy_bulk');

    // Rekap Farmasi
    Route::get('/dashboard/rekap-farmasi', [RekapFarmasiController::class , 'index'])
        ->name('dashboard.rekap_farmasi');
    Route::post('/dashboard/rekap-farmasi', [RekapFarmasiController::class , 'store'])
        ->name('dashboard.rekap_farmasi.store');
    Route::get('/dashboard/rekap-farmasi/activities', [RekapFarmasiController::class , 'getActivities'])
        ->name('dashboard.rekap_farmasi.activities');
    Route::get('/dashboard/rekap-farmasi/online-medics', [RekapFarmasiController::class , 'getOnlineMedics'])
        ->name('dashboard.rekap_farmasi.online_medics');
    Route::post('/dashboard/rekap-farmasi/toggle-status', [RekapFarmasiController::class , 'toggleStatus'])
        ->name('dashboard.rekap_farmasi.toggle_status');
    Route::post('/dashboard/rekap-farmasi/force-offline', [RekapFarmasiController::class , 'forceOffline'])
        ->name('dashboard.rekap_farmasi.force_offline');
    Route::post('/dashboard/rekap-farmasi/delete-bulk', [RekapFarmasiController::class , 'destroyBulk'])
        ->name('dashboard.rekap_farmasi.destroy_bulk');

    // New AJAX Routes for Rekap Farmasi
    Route::get('/dashboard/rekap-farmasi/status', [RekapFarmasiController::class, 'getStatus'])
        ->name('dashboard.rekap_farmasi.get_status');
    Route::get('/dashboard/rekap-farmasi/cooldown', [RekapFarmasiController::class, 'getGlobalCooldown'])
        ->name('dashboard.rekap_farmasi.get_cooldown');
    Route::get('/dashboard/rekap-farmasi/fairness', [RekapFarmasiController::class, 'getFairnessStatus'])
        ->name('dashboard.rekap_farmasi.get_fairness');
    Route::post('/dashboard/rekap-farmasi/koreksi-nama', [RekapFarmasiController::class, 'koreksiNamaKonsumen'])
        ->name('dashboard.rekap_farmasi.koreksi_nama');

    // Rekap Farmasi V2 (Advanced)
    Route::get('/dashboard/rekap-farmasi-v2', [RekapFarmasiController::class , 'indexV2'])
        ->name('dashboard.rekap_farmasi_v2');
    Route::post('/dashboard/rekap-farmasi-v2', [RekapFarmasiController::class , 'storeV2'])
        ->name('dashboard.rekap_farmasi_v2.store');
    Route::post('/dashboard/rekap-farmasi-v2/delete-bulk', [RekapFarmasiController::class , 'destroyBulkV2'])
        ->name('dashboard.rekap_farmasi_v2.destroy_bulk');

    // Identity Test
    Route::get('/dashboard/identity-test', [IdentityTestController::class , 'index'])
        ->name('dashboard.identity_test');
    Route::post('/dashboard/identity-test/ocr', [IdentityTestController::class , 'ocrAjax'])
        ->name('dashboard.identity_test.ocr');
    Route::post('/dashboard/identity-test/save-base64', [IdentityTestController::class , 'saveBase64'])
        ->name('dashboard.identity_test.save_base64');
    Route::post('/dashboard/identity-test/save', [IdentityTestController::class , 'saveIdentity'])
        ->name('dashboard.identity_test.save');
    Route::post('/dashboard/identity-test/check', [IdentityTestController::class , 'checkIdentity'])
        ->name('dashboard.identity_test.check');
    Route::post('/dashboard/identity-test/delete-temp', [IdentityTestController::class , 'deleteTemp'])
        ->name('dashboard.identity_test.delete_temp');
});

// ============================================================
// ACTION ROUTES (mirror from legacy/actions/)
// ============================================================
// Semua action routes memerlukan auth
Route::middleware(['auth.user_rh'])->group(function () {

    // Action Routes Compatibility (keep .php for AJAX calls if they are hardcoded in legacy JS)
    Route::post('/actions/check_farmasi_notif.php', [ActionController::class , 'checkFarmasiNotif']);
    Route::post('/actions/confirm_farmasi_online.php', [ActionController::class , 'confirmFarmasiOnline']);
    Route::post('/actions/set_farmasi_offline.php', [ActionController::class , 'setFarmasiOffline']);
    Route::get('/actions/get_farmasi_deadline.php', [ActionController::class , 'getFarmasiDeadline']);
    Route::post('/actions/heartbeat.php', [ActionController::class , 'heartbeat']);
    Route::post('/actions/ping_farmasi_activity.php', [ActionController::class , 'pingFarmasiActivity']);
    Route::get('/actions/search_medic.php', [ActionController::class , 'searchMedic']);
    Route::post('/actions/read_inbox.php', [ActionController::class , 'readInbox']);
    Route::post('/actions/delete_inbox.php', [ActionController::class , 'deleteInbox']);
    Route::post('/actions/save_push_subscription.php', [ActionController::class , 'savePushSubscription']);

    // Compatibility Routes for Rekap Farmasi AJAX
    Route::get('/actions/get_online_medics.php', [RekapFarmasiController::class, 'getOnlineMedics']);
    Route::post('/actions/toggle_farmasi_status.php', [RekapFarmasiController::class, 'toggleStatus']);
    Route::post('/actions/force_offline_medis.php', [RekapFarmasiController::class, 'forceOffline']);
    Route::get('/actions/get_fairness_status.php', [RekapFarmasiController::class, 'getFairnessStatus']);
    Route::post('/actions/koreksi_nama_konsumen.php', [RekapFarmasiController::class, 'koreksiNamaKonsumen']);

    // Standard Dashboard Routes (Clean URLs)
    Route::get('/dashboard/konsumen', [KonsumenController::class , 'index'])->name('dashboard.konsumen');
    Route::get('/dashboard/users', [ManageUserController::class , 'index'])->name('dashboard.manage_users');
    Route::get('/dashboard/absensi-ems', [AbsensiEmsController::class , 'index'])->name('dashboard.absensi_ems');
    Route::get('/dashboard/operasi-plastik', [OperasiPlastikController::class , 'index'])->name('dashboard.operasi_plastik');

    // Event Management
    Route::get('/dashboard/events/manage', [EventController::class, 'manage'])->name('dashboard.events.manage');
    Route::post('/dashboard/events/action', [EventController::class, 'handleAction'])->name('dashboard.events.action');
    Route::get('/dashboard/events/participants', [EventController::class, 'participants'])->name('dashboard.events.participants');
    Route::post('/dashboard/events/generate-group', [EventController::class, 'generateGroup'])->name('dashboard.events.generate_group');

    // AJAX Routes (Can be clean too)
    Route::get('/ajax/get_identity_detail', [KonsumenController::class , 'getIdentityDetail']);
    Route::get('/ajax/get_identity_detail.php', [KonsumenController::class , 'getIdentityDetail']);
    Route::post('/actions/import_sales_excel', [KonsumenController::class , 'importExcel']);
    Route::post('/dashboard/manage_users_action', [ManageUserController::class , 'handleAction']);
    Route::post('/dashboard/operasi_plastik_action', [OperasiPlastikController::class , 'handleAction']);
    // /ajax/search_user_rh is public (mirrors legacy/ajax/search_user_rh.php)

    // Candidates / Recruitment
    Route::get('/dashboard/candidates', [CandidateController::class , 'index'])->name('dashboard.candidates');
    Route::post('/dashboard/candidates/ai-decision', [CandidateController::class , 'aiDecision'])->name('dashboard.candidates.ai-decision');
    Route::post('/dashboard/candidates/finish-interview', [CandidateController::class , 'finishInterview'])->name('dashboard.candidates.finish-interview');

    // Detail
    Route::get('/dashboard/candidates/show', [CandidateController::class , 'show'])->name('dashboard.candidates.detail');

    Route::get('/dashboard/candidates/interview', [CandidateController::class , 'interview'])->name('dashboard.candidates.interview_multi');
    Route::post('/dashboard/candidates/interview', [CandidateController::class , 'submitInterview'])->name('dashboard.candidates.interview_multi.store');

    Route::get('/dashboard/candidates/decision', [CandidateController::class , 'decision'])->name('dashboard.candidates.decision_page');
    Route::get('/dashboard/candidates/get-temp-score', [CandidateController::class , 'getTempScore'])->name('dashboard.candidates.get_temp_score');
    Route::post('/dashboard/candidates/lock-interview', [CandidateController::class , 'lockInterview'])->name('dashboard.candidates.lock_interview');
    Route::post('/dashboard/candidates/submit-decision', [CandidateController::class , 'submitDecision'])->name('dashboard.candidates.submit_decision');

    // Payroll / Salary
    Route::get('/dashboard/salary', [SalaryController::class , 'index'])->name('dashboard.salary');
    Route::post('/dashboard/salary/pay', [SalaryController::class , 'payProcess'])->name('dashboard.salary.pay');
    Route::post('/dashboard/salary/generate', [SalaryController::class , 'generateManual'])->name('dashboard.salary.generate');

    // Reimbursement
    Route::get('/dashboard/reimbursement', [ReimbursementController::class , 'index'])->name('dashboard.reimbursement');
    Route::post('/dashboard/reimbursement', [ReimbursementController::class , 'store'])->name('dashboard.reimbursement.store');
    Route::post('/dashboard/reimbursement/pay', [ReimbursementController::class , 'pay'])->name('dashboard.reimbursement.pay');
    Route::post('/dashboard/reimbursement/delete', [ReimbursementController::class , 'delete'])->name('dashboard.reimbursement.delete');

    // Restaurant Consumption
    Route::get('/dashboard/restaurant', [RestaurantController::class , 'index'])->name('dashboard.restaurant');
    Route::post('/dashboard/restaurant', [RestaurantController::class , 'store'])->name('dashboard.restaurant.store');
    Route::post('/dashboard/restaurant/approve', [RestaurantController::class , 'approve'])->name('dashboard.restaurant.approve');
    Route::post('/dashboard/restaurant/paid', [RestaurantController::class , 'paid'])->name('dashboard.restaurant.paid');
    Route::post('/dashboard/restaurant/delete', [RestaurantController::class , 'delete'])->name('dashboard.restaurant.delete');
    Route::get('/dashboard/restaurant/settings', [RestaurantController::class , 'settings'])->name('dashboard.restaurant.settings');
    Route::post('/dashboard/restaurant/settings', [RestaurantController::class , 'storeSettings'])->name('dashboard.restaurant.settings.store');
    Route::post('/dashboard/restaurant/settings/update', [RestaurantController::class , 'updateSettings'])->name('dashboard.restaurant.settings.update');
    Route::post('/dashboard/restaurant/settings/toggle', [RestaurantController::class , 'toggleStatus'])->name('dashboard.restaurant.settings.toggle');
    Route::post('/dashboard/restaurant/settings/delete', [RestaurantController::class , 'deleteSettings'])->name('dashboard.restaurant.settings.delete');

    // Validasi (Manager only)
    Route::get('/dashboard/validasi', [ValidasiController::class , 'index'])->name('dashboard.validasi');
    Route::get('/dashboard/validasi/approve', [ValidasiController::class , 'approve'])->name('dashboard.validasi.approve');
    Route::get('/dashboard/validasi/reject', [ValidasiController::class , 'reject'])->name('dashboard.validasi.reject');
});
