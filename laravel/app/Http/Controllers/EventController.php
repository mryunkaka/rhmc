<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\UserRh;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EventController extends Controller
{
    /**
     * Event Registration Page (Public)
     * Mirror of: legacy/dashboard/events.php
     */
    public function index(Request $request)
    {
        $eventId = (int)$request->get('id', 0);

        if ($eventId > 0) {
            $event = DB::table('events')->where('id', $eventId)->where('is_active', 1)->first();
        }
        else {
            $event = DB::table('events')
                ->where('is_active', 1)
                ->orderBy('tanggal_event', 'asc')
                ->first();
        }

        if (!$event) {
            return view('dashboard.events_no_active');
        }

        $eventId = (int)$event->id;

        // Stats
        $stat = DB::table('event_participants as ep')
            ->leftJoin('user_rh as u', 'u.id', '=', 'ep.user_id')
            ->select(
            DB::raw('COUNT(ep.id) AS total'),
            DB::raw("SUM(CASE WHEN u.jenis_kelamin = 'Laki-laki' THEN 1 ELSE 0 END) AS laki"),
            DB::raw("SUM(CASE WHEN u.jenis_kelamin = 'Perempuan' THEN 1 ELSE 0 END) AS perempuan")
        )
            ->where('ep.event_id', $eventId)
            ->first();

        return view('dashboard.events', [
            'event' => $event,
            'stat' => $stat,
            'eventId' => $eventId
        ]);
    }

    /**
     * Register for Event
     * Mirror of: legacy/dashboard/events.php (POST)
     */
    public function register(Request $request)
    {
        $eventId = (int)$request->input('event_id');
        $nama = trim($request->input('nama_lengkap'));
        $batch = trim($request->input('batch'));
        $gender = $request->input('jenis_kelamin');

        if ($nama === '' || $batch === '' || !in_array($gender, ['Laki-laki', 'Perempuan'], true)) {
            return redirect()->route('dashboard.events', ['id' => $eventId])
                ->with('flash_errors', ['Semua data wajib diisi.']);
        }

        try {
            DB::beginTransaction();

            $user = UserRh::where('full_name', $nama)->first();

            if (!$user) {
                $user = UserRh::create([
                    'full_name' => $nama,
                    'batch' => $batch,
                    'jenis_kelamin' => $gender,
                    'position' => 'Trainee',
                    'role' => 'Staff',
                    'pin' => Hash::make('0000'),
                    'is_active' => 1
                ]);
            }
            else {
                $user->update([
                    'batch' => $batch,
                    'jenis_kelamin' => $gender
                ]);
            }

            // Check if already registered
            $isRegistered = DB::table('event_participants')
                ->where('event_id', $eventId)
                ->where('user_id', $user->id)
                ->exists();

            if ($isRegistered) {
                DB::rollBack();
                return redirect()->route('dashboard.events', ['id' => $eventId])
                    ->with('flash_errors', ['Nama ini sudah terdaftar di event.']);
            }

            DB::table('event_participants')->insert([
                'event_id' => $eventId,
                'user_id' => $user->id
            ]);

            DB::commit();
            return redirect()->route('dashboard.events', ['id' => $eventId])
                ->with('flash_messages', ['Pendaftaran event berhasil!']);

        }
        catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('dashboard.events', ['id' => $eventId])
                ->with('flash_errors', ['Terjadi kesalahan sistem.']);
        }
    }

    /**
     * Manage Events Page
     * Mirror of: legacy/dashboard/event_manage.php
     */
    public function manage(Request $request)
    {
        $role = strtolower(session('user_rh')['role'] ?? '');
        if ($role === 'staff') {
            return redirect()->route('dashboard.events');
        }

        $events = DB::table('events as e')
            ->select('e.*')
            ->selectRaw('(SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) as total_peserta')
            ->orderBy('e.created_at', 'desc')
            ->get();

        return view('dashboard.event_manage', [
            'events' => $events,
            'pageTitle' => 'Manajemen Event'
        ]);
    }

    /**
     * Handle Event Actions (Insert/Update)
     * Mirror of: legacy/dashboard/event_action.php
     */
    public function handleAction(Request $request)
    {
        $role = strtolower(session('user_rh')['role'] ?? '');
        if ($role === 'staff') {
            return response('Akses ditolak', 403);
        }

        $request->validate([
            'nama_event' => 'required|string',
            'tanggal_event' => 'required|date',
        ]);

        $id = (int)$request->input('event_id');
        $data = [
            'nama_event' => trim($request->input('nama_event')),
            'tanggal_event' => $request->input('tanggal_event'),
            'lokasi' => trim($request->input('lokasi', '')),
            'keterangan' => trim($request->input('keterangan', '')),
            'is_active' => $request->has('is_active') ? 1 : 0,
        ];

        try {
            if ($id > 0) {
                DB::table('events')->where('id', $id)->update($data);
                $message = 'Event berhasil diperbarui.';
            } else {
                DB::table('events')->insert($data);
                $message = 'Event berhasil ditambahkan.';
            }
            return redirect()->route('dashboard.events.manage')->with('flash_messages', [$message]);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash_errors', ['Gagal menyimpan data event.'])->withInput();
        }
    }

    /**
     * Event Participants Page
     * Mirror of: legacy/dashboard/event_participants.php
     */
    public function participants(Request $request)
    {
        $role = strtolower(session('user_rh')['role'] ?? '');
        if ($role === 'staff') {
            return redirect()->route('dashboard.events');
        }

        $eventId = (int)$request->get('event_id');
        if (!$eventId) {
            return redirect()->route('dashboard.events.manage');
        }

        $event = DB::table('events')->where('id', $eventId)->first();
        if (!$event) {
            return redirect()->route('dashboard.events.manage');
        }

        $participants = DB::table('event_participants as ep')
            ->join('user_rh as u', 'u.id', '=', 'ep.user_id')
            ->select('u.id as user_id', 'u.full_name', 'u.position', 'u.batch', 'u.jenis_kelamin', 'u.citizen_id', 'u.no_hp_ic', 'ep.registered_at')
            ->where('ep.event_id', $eventId)
            ->orderBy('ep.registered_at', 'asc')
            ->get();

        $dbGroupsRaw = DB::table('event_groups as g')
            ->join('event_group_members as gm', 'gm.event_group_id', '=', 'g.id')
            ->join('user_rh as u', 'u.id', '=', 'gm.user_id')
            ->select('g.group_name', 'u.full_name', 'u.position', 'u.jenis_kelamin', 'u.batch')
            ->where('g.event_id', $eventId)
            ->orderBy('g.id')
            ->orderBy('u.full_name')
            ->get();

        $dbGroups = [];
        foreach ($dbGroupsRaw as $row) {
            $dbGroups[$row->group_name][] = $row;
        }

        return view('dashboard.event_participants', [
            'event' => $event,
            'participants' => $participants,
            'dbGroups' => $dbGroups,
            'eventId' => $eventId,
            'pageTitle' => 'Peserta Event'
        ]);
    }

    /**
     * Generate Groups
     * Mirror of: legacy/dashboard/event_participants.php (POST)
     */
    public function generateGroup(Request $request)
    {
        $eventId = (int)$request->input('event_id');
        $groupSize = max(2, (int)$request->input('group_size'));
        $maleNeed = max(0, (int)$request->input('male_count'));
        $femaleNeed = max(0, (int)$request->input('female_count'));

        if (($maleNeed + $femaleNeed) > $groupSize) {
            return redirect()->back()->with('flash_errors', ['Jumlah laki-laki + perempuan melebihi kapasitas kelompok.']);
        }

        $participants = DB::table('event_participants as ep')
            ->join('user_rh as u', 'u.id', '=', 'ep.user_id')
            ->select('u.id as user_id', 'u.full_name', 'u.position', 'u.batch', 'u.jenis_kelamin')
            ->where('ep.event_id', $eventId)
            ->get()
            ->toArray();

        // Conversion to array of objects for easier manipulation if needed, or keep as is
        $pool = $participants;
        shuffle($pool);

        $groups = [];
        $groupIndex = 1;

        while (!empty($pool)) {
            $group = [];
            $usedBatch = [];

            // 1. perempuan
            for ($i = 0; $i < $femaleNeed; $i++) {
                foreach ($pool as $k => $p) {
                    if ($p->jenis_kelamin === 'Perempuan') {
                        $group[] = $p;
                        $usedBatch[] = $p->batch;
                        unset($pool[$k]);
                        break;
                    }
                }
            }

            // 2. laki-laki
            for ($i = 0; $i < $maleNeed; $i++) {
                foreach ($pool as $k => $p) {
                    if ($p->jenis_kelamin === 'Laki-laki') {
                        $group[] = $p;
                        $usedBatch[] = $p->batch;
                        unset($pool[$k]);
                        break;
                    }
                }
            }

            // 3. beda batch
            foreach ($pool as $k => $p) {
                if (count($group) >= $groupSize) break;
                if (!in_array($p->batch, $usedBatch, true)) {
                    $group[] = $p;
                    $usedBatch[] = $p->batch;
                    unset($pool[$k]);
                }
            }

            // 4. fallback
            foreach ($pool as $k => $p) {
                if (count($group) >= $groupSize) break;
                $group[] = $p;
                unset($pool[$k]);
            }

            if (!empty($group)) {
                $groups['Kelompok ' . $groupIndex] = $group;
                $groupIndex++;
            }
        }

        DB::beginTransaction();
        try {
            $groupIds = DB::table('event_groups')->where('event_id', $eventId)->pluck('id')->toArray();
            if (!empty($groupIds)) {
                DB::table('event_group_members')->whereIn('event_group_id', $groupIds)->delete();
                DB::table('event_groups')->whereIn('id', $groupIds)->delete();
            }

            foreach ($groups as $groupName => $members) {
                $groupId = DB::table('event_groups')->insertGetId([
                    'event_id' => $eventId,
                    'group_name' => $groupName
                ]);

                foreach ($members as $m) {
                    DB::table('event_group_members')->insert([
                        'event_group_id' => $groupId,
                        'user_id' => $m->user_id
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('flash_messages', ['Kelompok berhasil digenerate ulang.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('flash_errors', ['Gagal menyimpan kelompok.']);
        }
    }
}
