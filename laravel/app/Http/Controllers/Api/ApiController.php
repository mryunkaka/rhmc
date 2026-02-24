<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    /**
     * Mirror legacy/api/sync_sales.php
     * GET /api/sync-sales
     */
    public function syncSales()
    {
        $rows = DB::table('sales')
            ->select([
                'id',
                'medic_user_id',
                'medic_name',
                'package_name',
                'price',
                'qty_bandage',
                'qty_ifaks',
                'qty_painkiller',
                'created_at',
                'tx_hash',
            ])
            ->where('synced_to_sheet', 0)
            ->orderBy('id', 'asc')
            ->limit(100)
            ->get();

        return response()->json($rows);
    }
}

