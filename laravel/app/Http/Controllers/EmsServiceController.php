<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmsSale;
use App\Models\UserRh;
use App\Models\MedicalRegulation;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\DateRange;

class EmsServiceController extends Controller
{
    private function regulationMinActiveNullable(string $code): ?int
    {
        $active = MedicalRegulation::where('code', $code)->where('is_active', 1)->first();
        return $active ? (int)$active->price_min : null;
    }

    private function regulationMinActiveOrFail(string $code): int
    {
        $value = $this->regulationMinActiveNullable($code);
        if ($value === null) {
            throw new \Exception("Active regulation not found: {$code}");
        }
        return $value;
    }

    private function regulationMin(string $code): int
    {
        $active = MedicalRegulation::where('code', $code)->where('is_active', 1)->first();
        $any = $active ?: MedicalRegulation::where('code', $code)->first();
        if (!$any) {
            throw new \Exception("Regulation not found: {$code}");
        }
        return (int)$any->price_min;
    }

    private function computeEmsTotal(string $serviceType, string $serviceDetail, ?string $operasiTingkat, int $qty, bool $isGunshot, array $meds): array
    {
        $total = 0;
        $price = 0;
        $medPrice = 0;
        $extra = [];

        switch ($serviceType) {
            case 'Pingsan':
                $map = [
                    'RS' => 'PP_RS', 'Paleto' => 'PP_PALETO',
                    'Gunung/Laut' => 'PP_GUNUNG', 'Zona Perang' => 'PP_PERANG', 'UFC' => 'PP_UFC'
                ];

                $price = safeRegulation($map[$serviceDetail] ?? '');
                $medPrice = safeRegulation($isGunshot ? 'BLEEDING_PELURU' : 'BLEEDING_OBAT');
                $total = $price + (count($meds) * $medPrice);
                break;

            case 'Treatment':
                $price = safeRegulation($serviceDetail === 'RS' ? 'TR_RS' : 'TR_LUAR');
                $medPrice = safeRegulation($isGunshot ? 'BLEEDING_PELURU' : 'BLEEDING_OBAT');
                $total = $price + (count($meds) * $medPrice);
                break;

            case 'Surat':
                $price = safeRegulation($serviceDetail === 'Kesehatan' ? 'SK_KES' : 'SK_PSI');
                $total = $price;
                break;

            case 'Operasi':
                if (!$operasiTingkat) {
                    throw new \Exception('Operation severity is required.');
                }

                $code = $serviceDetail === 'Besar' ? 'OP_BESAR' : 'OP_KECIL';
                $reg = MedicalRegulation::where('code', $code)->where('is_active', 1)->first();
                if (!$reg) {
                    throw new \Exception('Operation regulation not found.');
                }

                $min = (int)$reg->price_min;
                $max = (int)$reg->price_max;
                $step = floor(($max - $min) / 3);

                switch ($operasiTingkat) {
                    case 'Ringan':
                        $price = random_int($min, $min + $step);
                        break;
                    case 'Sedang':
                        $price = random_int($min + $step + 1, $min + ($step * 2));
                        break;
                    case 'Berat':
                        $price = random_int($min + ($step * 2) + 1, $max);
                        break;
                    default:
                        throw new \Exception('Invalid operation severity.');
                }

                $total = $price;
                break;

            case 'Rawat Inap':
                $perHari = safeRegulation($serviceDetail === 'Reguler' ? 'RI_REG' : 'RI_VIP');
                $price = $perHari;
                $total = $perHari * max($qty, 1);
                break;

            case 'Kematian':
                $price = safeRegulation($serviceDetail === 'Pemakaman' ? 'PEMAKAMAN' : 'KREMASI');
                $total = $price;
                break;

            case 'Plastik':
                // Plastic surgery should follow ACTIVE regulations only
                $cash = $this->regulationMinActiveOrFail('OP_PL_CASH');
                $billing = $this->regulationMinActiveNullable('OP_PL_BILL') ?? 0;
                $price = $cash + $billing;
                $total = $price;
                if ($billing > 0) {
                    $extra['plastik'] = [
                        'cash' => $cash,
                        'billing' => $billing,
                    ];
                }
                break;

            default:
                throw new \Exception('Invalid service type.');
        }

        return [
            'price' => (int)$price,
            'total' => (int)$total,
            'med_price' => (int)$medPrice,
            'extra' => $extra,
        ];
    }

    /**
     * Display Input Layanan Medis EMS
     * Mirror dari legacy/dashboard/ems_services.php
     */
    public function index(Request $request)
    {
        $user = session('user_rh');
        $medicName = $user['name'];

        $shouldClearForm = (bool)session('clear_form', false);
        session()->forget('clear_form');

        // ======================================================
        // DATE RANGE LOGIC (Using Helper)
        // ======================================================
        $dateRange = DateRange::getRange($request->get('range', 'week4'), $request->get('from'), $request->get('to'));
        $rangeStart = $dateRange['start'];
        $rangeEnd = $dateRange['end'];

        // ======================================================
        // LOAD CONFIG/REGULATION
        // ======================================================
        try {
            $priceBleedingNormal = safeRegulation('BLEEDING_OBAT');
            $priceBleedingPeluru = safeRegulation('BLEEDING_PELURU');
        }
        catch (\Exception $e) {
            $priceBleedingNormal = 0;
            $priceBleedingPeluru = 0;
        }

        // ======================================================
        // LOAD DATA REKAP
        // ======================================================
        $rows = EmsSale::where('medic_name', $medicName)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->orderBy('created_at', 'desc')
            ->get();

        // Rekapan Medis Logic
        $rekapMedis = [
            'bandage' => 0,
            'p3k' => 0,
            'gauze' => 0,
            'iodine' => 0,
            'syringe' => 0,
            'billing' => 0,
            'cash' => 0,
            'total' => 0,
        ];

        foreach ($rows as $r) {
            $usage = strtolower($r->medicine_usage ?? '');

            if (str_contains($usage, 'bandage'))
                $rekapMedis['bandage']++;
            if (str_contains($usage, 'p3k'))
                $rekapMedis['p3k']++;
            if (str_contains($usage, 'gauze'))
                $rekapMedis['gauze']++;
            if (str_contains($usage, 'iodine'))
                $rekapMedis['iodine']++;
            if (str_contains($usage, 'syringe'))
                $rekapMedis['syringe']++;

            if ($r->payment_type === 'billing') {
                $rekapMedis['billing'] += (int)$r->total;
            }
            if ($r->payment_type === 'cash') {
                $rekapMedis['cash'] += (int)$r->total;
            }
            $rekapMedis['total'] += (int)$r->total;
        }

        $doctors = UserRh::orderBy('full_name')->get(['full_name']);

        return view('dashboard.ems_services', [
            'pageTitle' => 'Input Layanan Medis EMS',
            'medicName' => $medicName,
            'medicJabatan' => $user['position'] ?? '-',
            'priceBleedingNormal' => $priceBleedingNormal,
            'priceBleedingPeluru' => $priceBleedingPeluru,
            'shouldClearForm' => $shouldClearForm,
            'rangeLabel' => $dateRange['rangeLabel'],
            'range' => $request->get('range', 'week4'),
            'weeks' => $dateRange['weeks'],
            'rows' => $rows,
            'rekapMedis' => $rekapMedis,
            'doctors' => $doctors,
        ]);
    }

    /**
     * Preview Price via AJAX
     * Mirror dari legacy/ajax/ems_preview_price.php
     */
    public function previewPrice(Request $request)
    {
        $serviceType = $request->get('service_type', '');
        $serviceDetail = $request->get('service_detail', '');
        $operasiTingkat = $request->get('operasi_tingkat');
        $qty = (int)($request->get('qty', 1));
        $isGunshot = ($request->get('is_gunshot') === '1');
        $meds = $request->get('meds', []);

        try {
            $computed = $this->computeEmsTotal(
                $serviceType,
                $serviceDetail,
                $operasiTingkat,
                $qty,
                $isGunshot,
                is_array($meds) ? $meds : []
            );

            return response()->json([
                'success' => true,
                'total' => $computed['total'],
                'breakdown' => [
                    'base_price' => $computed['price'],
                    'medicine' => [
                        'count' => is_array($meds) ? count($meds) : 0,
                        'per_item' => $computed['med_price'],
                        'type' => $isGunshot ? 'PELURU' : 'NORMAL',
                        'subtotal' => (is_array($meds) ? count($meds) : 0) * $computed['med_price']
                    ],
                    'plastik' => $computed['extra']['plastik'] ?? null,
                ]
            ]);
        }
        catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Store new EMS Service record(s)
     * Mirror dari legacy/dashboard/ems_services.php POST action
     */
    public function store(Request $request)
    {
        $user = session('user_rh');
        $medicName = $user['name'];
        $medicJabatan = $user['position'];

        $serviceType = (string)$request->get('service_type', '');
        $serviceDetail = (string)$request->get('service_detail', '');
        $operasiTingkat = $request->get('operasi_tingkat');
        $patientName = trim($request->get('patient_name', ''));
        $location = $request->get('location');
        $qty = (int)$request->get('qty', 1);
        $paymentType = (string)$request->get('payment_type', '');
        $isGunshot = $request->get('is_gunshot') == '1';
        $meds = $request->get('meds', []);
        $dpjpName = $request->get('dpjp_name');
        $teamNames = $request->get('team_names', []);

        $errors = [];

        if ($serviceType === '') {
            $errors[] = 'Service type is required.';
        }

        if ($serviceType === 'Operasi' && empty($dpjpName)) {
            $errors[] = 'DPJP is required.';
        }

        if ($serviceType !== 'Plastik' && empty($serviceDetail)) {
            $errors[] = 'Service detail is required.';
        }

        if (!empty($errors)) {
            return redirect()->back()->with('flash_errors', $errors)->withInput();
        }

        // Fallback rules (mirror legacy)
        if ($paymentType === '' || $paymentType === null) {
            if (in_array($serviceType, ['Pingsan', 'Treatment', 'Surat'], true)) {
                $paymentType = 'cash';
            } elseif (in_array($serviceType, ['Operasi', 'Rawat Inap'], true)) {
                $paymentType = 'billing';
            }
        }

        if ($location === '' || $location === null) {
            if (in_array($serviceType, ['Pingsan', 'Treatment', 'Surat', 'Operasi', 'Rawat Inap', 'Plastik'], true)) {
                $location = '4017';
            }
        }

        if ($qty <= 0) {
            $qty = 1;
        }

        // Compute totals server-side (mirror legacy)
        $computed = $this->computeEmsTotal(
            $serviceType,
            $serviceDetail,
            $operasiTingkat,
            $qty,
            (bool)$isGunshot,
            is_array($meds) ? $meds : []
        );
        $price = $computed['price'];
        $total = $computed['total'];

        if ($total <= 0) {
            return redirect()->back()->with('flash_errors', ['Total cost could not be calculated.'])->withInput();
        }

        return DB::transaction(function () use ($serviceType, $serviceDetail, $operasiTingkat, $patientName, $location, $qty, $paymentType, $isGunshot, $meds, $dpjpName, $teamNames, $price, $total, $medicName, $medicJabatan) {

            // Build Medicine Usage string
            $medicineUsage = '';
            $medicineMap = [
                'Head' => 'Gauze', 'Body' => 'Gauze',
                'Left Arm' => 'Iodine', 'Right Arm' => 'Iodine',
                'Left Leg' => 'Syringe', 'Right Leg' => 'Syringe',
                'Left Foot' => 'Syringe', 'Right Foot' => 'Syringe',
            ];

            if ($serviceType === 'Treatment') {
                $medicineUsage = 'Bandage 1 pcs' . ($isGunshot ? ' (Luka Tembak)' : '');
            }
            elseif ($serviceType === 'Pingsan') {
                $medicineUsage = 'P3K' . (!empty($meds) ? ' + Obat' : '') . ($isGunshot ? ' (Luka Tembak)' : '');
            }

            if (!empty($meds)) {
                $list = [];
                foreach ($meds as $area) {
                    if (isset($medicineMap[$area])) {
                        $list[] = $area . ' (' . $medicineMap[$area] . ')';
                    }
                }
                if (!empty($list)) {
                    $medicineUsage .= ($medicineUsage ? ', ' : '') . implode(', ', $list) . ($isGunshot ? ' [Peluru]' : '');
                }
            }

            if ($serviceType === 'Operasi') {
                $billing = intdiv($total, 2);
                $cash = $total - $billing;
                $doctorShare = intdiv($cash, 2);
                $teamPool = $cash - $doctorShare;

                $teamNamesFiltered = array_values(array_filter($teamNames));
                $teamCount = count($teamNamesFiltered);
                $perTeam = $teamCount > 0 ? intdiv($teamPool, $teamCount) : 0;

                EmsSale::create([
                    'service_type' => 'Operasi', 'service_detail' => $serviceDetail, 'operasi_tingkat' => $operasiTingkat,
                    'patient_name' => $patientName ?: null, 'location' => $location, 'qty' => 1, 'payment_type' => 'billing',
                    'price' => $billing, 'total' => $billing, 'medic_name' => $dpjpName, 'medic_jabatan' => 'DPJP', 'medicine_usage' => 'Billing Operasi'
                ]);

                EmsSale::create([
                    'service_type' => 'Operasi', 'service_detail' => $serviceDetail, 'operasi_tingkat' => $operasiTingkat,
                    'patient_name' => $patientName ?: null, 'location' => $location, 'qty' => 1, 'payment_type' => 'cash',
                    'price' => $doctorShare, 'total' => $doctorShare, 'medic_name' => $dpjpName, 'medic_jabatan' => 'DPJP', 'medicine_usage' => 'Jasa Dokter Operasi'
                ]);

                foreach ($teamNamesFiltered as $name) {
                    EmsSale::create([
                        'service_type' => 'Operasi', 'service_detail' => $serviceDetail, 'operasi_tingkat' => $operasiTingkat,
                        'patient_name' => $patientName ?: null, 'location' => $location, 'qty' => 1, 'payment_type' => 'cash',
                        'price' => $perTeam, 'total' => $perTeam, 'medic_name' => $name, 'medic_jabatan' => 'Tim Operasi', 'medicine_usage' => 'Jasa Tim Operasi'
                    ]);
                }

                return redirect()->route('dashboard.ems_services')
                    ->with('flash_messages', ['Operation saved (Billing + Cash + Team).'])
                    ->with('clear_form', true);
            }

            if ($serviceType === 'Plastik') {
                $priceCash = $this->regulationMinActiveOrFail('OP_PL_CASH');
                $priceBilling = $this->regulationMinActiveNullable('OP_PL_BILL') ?? 0;

                EmsSale::create([
                    'service_type' => 'Plastik', 'service_detail' => 'Operasi Plastik',
                    'patient_name' => $patientName ?: null, 'location' => '4017', 'qty' => 1,
                    'payment_type' => 'cash', 'price' => $priceCash, 'total' => $priceCash,
                    'medic_name' => $medicName, 'medic_jabatan' => $medicJabatan
                ]);

                if ($priceBilling > 0) {
                    EmsSale::create([
                        'service_type' => 'Plastik', 'service_detail' => 'Operasi Plastik',
                        'patient_name' => $patientName ?: null, 'location' => '4017', 'qty' => 1,
                        'payment_type' => 'billing', 'price' => $priceBilling, 'total' => $priceBilling,
                        'medic_name' => $medicName, 'medic_jabatan' => $medicJabatan
                    ]);
                }

                return redirect()->route('dashboard.ems_services')
                    ->with('flash_messages', [$priceBilling > 0 ? 'Plastic surgery saved (Cash + Billing).' : 'Plastic surgery saved (Cash).'])
                    ->with('clear_form', true);
            }

            EmsSale::create([
                'service_type' => $serviceType, 'service_detail' => $serviceDetail, 'operasi_tingkat' => $operasiTingkat,
                'patient_name' => $patientName ?: null, 'location' => $location, 'qty' => $qty, 'payment_type' => $paymentType,
                'price' => $price, 'total' => $total, 'medic_name' => $medicName, 'medic_jabatan' => $medicJabatan, 'medicine_usage' => $medicineUsage
            ]);

            return redirect()->route('dashboard.ems_services')
                ->with('flash_messages', ['Saved successfully.'])
                ->with('clear_form', true);
        });
    }

    /**
     * Delete Bulk via POST
     * Mirror dari legacy/dashboard/rekap_delete_bulk.php
     */
    public function destroyBulk(Request $request)
    {
        $ids = $request->get('ids', []);
        if (empty($ids)) {
            return redirect()->back()->with('flash_errors', ['No rows selected.']);
        }

        EmsSale::whereIn('id', $ids)->delete();

        return redirect()->route('dashboard.ems_services')->with('flash_messages', [count($ids) . ' row(s) deleted successfully.']);
    }
}
