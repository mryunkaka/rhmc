<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\IdentityMaster;
use App\Models\IdentityVersion;

class KonsumenController extends Controller
{
    /**
     * Data Konsumen Page
     * Mirror of: legacy/dashboard/konsumen.php
     */
    public function index(Request $request)
    {
        $user = session('user_rh');
        $userRole = strtolower(trim($user['role'] ?? ''));

        $q = trim($request->get('q', ''));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $sales = collect();

        if ($q !== '' || ($startDate && $endDate)) {
            $query = Sale::query()
                ->select(
                'sales.created_at',
                'sales.consumer_name',
                'sales.medic_name',
                'sales.medic_jabatan',
                'sales.qty_bandage',
                'sales.qty_ifaks',
                'sales.qty_painkiller',
                DB::raw('(sales.qty_bandage + sales.qty_ifaks + sales.qty_painkiller) AS total_item'),
                'sales.price',
                'sales.identity_id',
                'identity_master.citizen_id'
            )
                ->leftJoin('identity_master', 'identity_master.id', '=', 'sales.identity_id');

            if ($q !== '') {
                $query->where(function (\Illuminate\Database\Eloquent\Builder $query) use ($q) {
                    $query->where('sales.consumer_name', 'LIKE', "%$q%")
                        ->orWhere('identity_master.citizen_id', 'LIKE', "%$q%")
                        ->orWhere('sales.medic_name', 'LIKE', "%$q%");
                });
            }

            if ($startDate && $endDate) {
                $query->whereDate('sales.created_at', '>=', $startDate)
                    ->whereDate('sales.created_at', '<=', $endDate);
            }

            $sales = $query->orderBy('sales.created_at', 'desc')
                ->limit(200)
                ->get();
        }

        return view('dashboard.konsumen', [
            'pageTitle' => 'Data Konsumen',
            'medicName' => $user['name'] ?? 'User',
            'medicPos' => $user['position'] ?? '-',
            'userRole' => $userRole,
            'sales' => $sales,
            'q' => $q,
            'range' => $request->get('range', 'this_week')
        ]);
    }

    /**
     * Get Identity Detail (Ajax)
     * Mirror of: legacy/ajax/get_identity_detail.php
     */
    public function getIdentityDetail(Request $request)
    {
        $id = (int)$request->get('id');

        if ($id <= 0) {
            return '<p style="color:#ef4444;text-align:center;padding:20px;">Identity ID tidak valid.</p>';
        }

        $master = IdentityMaster::find($id);

        if (!$master) {
            return '<p style="color:#ef4444;text-align:center;padding:20px;">Data konsumen tidak ditemukan.</p>';
        }

        $versions = IdentityVersion::where('identity_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('dashboard.partials.identity_detail', [
            'master' => $master,
            'versions' => $versions
        ]);
    }

    /**
     * Import Sales from Excel
     * Mirror of: legacy/actions/import_sales_excel.php
     */
    public function importExcel(Request $request)
    {
        try {
            if (!$request->hasFile('excel_file')) {
                return response()->json(['success' => false, 'message' => 'File tidak ditemukan']);
            }

            $medicName = trim($request->medic_name);
            $medicPosition = trim($request->medic_position ?? '');
            $transactionDate = $request->transaction_date;
            $file = $request->file('excel_file');

            // Verify medic
            $medic = \App\Models\UserRh::where('full_name', $medicName)->where('is_active', 1)->first();
            if (!$medic) {
                return response()->json(['success' => false, 'message' => 'Medis tidak ditemukan']);
            }

            $medicUserId = $medic->id;
            $medicJabatan = !empty($medicPosition) ? $medicPosition : $medic->position;

            // Load Excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $imported = 0;
            DB::beginTransaction();

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                if (empty(trim($row[0] ?? '')) || empty(trim($row[1] ?? '')))
                    continue;

                $consumerName = trim($row[0] ?? '');
                $packageName = trim($row[1] ?? '');
                $citizenId = trim($row[2] ?? '');

                // Package lookup
                $package = \App\Models\Package::where('name', $packageName)->first();
                if (!$package)
                    continue;

                $qtyBandage = (int)$package->bandage_qty;
                $qtyIfak = (int)$package->ifaks_qty;
                $qtyPainkiller = (int)$package->painkiller_qty;
                $price = (int)$package->price;

                if ($qtyBandage + $qtyIfak + $qtyPainkiller === 0)
                    continue;

                // Identity lookup
                $identityId = null;
                if (!empty($citizenId)) {
                    $identity = \App\Models\IdentityMaster::where('citizen_id', $citizenId)->first();
                    if ($identity)
                        $identityId = $identity->id;
                }

                $txHash = hash('sha256', $medicName . $consumerName . $transactionDate . microtime(true) . $i);

                Sale::create([
                    'consumer_name' => $consumerName,
                    'medic_name' => $medicName,
                    'medic_user_id' => $medicUserId,
                    'medic_jabatan' => $medicJabatan,
                    'qty_bandage' => $qtyBandage,
                    'qty_ifaks' => $qtyIfak,
                    'qty_painkiller' => $qtyPainkiller,
                    'price' => $price,
                    'package_id' => $package->id,
                    'package_name' => $packageName,
                    'keterangan' => '',
                    'identity_id' => $identityId,
                    'tx_hash' => $txHash,
                    'created_at' => $transactionDate . ' ' . date('H:i:s')
                ]);

                $imported++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'imported' => $imported,
                'message' => "Berhasil import $imported transaksi"
            ]);

        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
