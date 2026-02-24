<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\MedicalRegulation;
use Illuminate\Support\Facades\DB;

class RegulasiController extends Controller
{
    /**
     * Display Regulasi EMS
     * Mirror dari legacy/dashboard/regulasi.php
     */
    public function index()
    {
        $user = session('user_rh');

        // ROLE GUARD (NON-STAFF) - Mirror legacy logic
        if (strtolower($user['role'] ?? '') === 'staff') {
            abort(403, 'Akses ditolak');
        }

        $packages = Package::orderBy('name')->get();
        $regs = MedicalRegulation::orderBy('category')->orderBy('code')->get();

        return view('dashboard.regulasi', [
            'pageTitle' => 'Regulasi EMS',
            'medicName' => $user['name'] ?? 'User',
            'medicPos' => $user['position'] ?? '-',
            'packages' => $packages,
            'regs' => $regs,
        ]);
    }

    /**
     * Handle Update Data via AJAX
     * Mirror dari legacy/dashboard/regulasi.php POST handler
     */
    public function update(Request $request)
    {
        $action = $request->get('action');

        try {
            /* ===== UPDATE PACKAGE ===== */
            if ($action === 'update_package') {
                Package::where('id', $request->get('id'))->update([
                    'name' => trim($request->get('name')),
                    'bandage_qty' => (int)$request->get('bandage_qty'),
                    'ifaks_qty' => (int)$request->get('ifaks_qty'),
                    'painkiller_qty' => (int)$request->get('painkiller_qty'),
                    'price' => (int)$request->get('price'),
                ]);

                return response()->json(['success' => true]);
            }

            /* ===== UPDATE MEDICAL REGULATION ===== */
            if ($action === 'update_regulation') {
                MedicalRegulation::where('id', $request->get('id'))->update([
                    'category' => trim($request->get('category')),
                    'name' => trim($request->get('name')),
                    'location' => $request->filled('location') ? trim($request->get('location')) : null,
                    'price_type' => $request->get('price_type'),
                    'price_min' => (int)$request->get('price_min'),
                    'price_max' => (int)$request->get('price_max'),
                    'payment_type' => $request->get('payment_type'),
                    'duration_minutes' => $request->filled('duration_minutes') ? (int)$request->get('duration_minutes') : null,
                    'notes' => $request->filled('notes') ? trim($request->get('notes')) : null,
                    'is_active' => $request->has('is_active') ? 1 : 0,
                ]);

                return response()->json(['success' => true]);
            }

            throw new \Exception('Aksi tidak valid');
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
