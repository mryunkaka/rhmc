<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\MedicalApplicant;
use App\Models\AiTestResult;
use App\Models\InterviewCriterion;
use App\Models\ApplicantInterviewScore;
use App\Models\ApplicantInterviewResult;
use App\Models\ApplicantFinalDecision;

class CandidateController extends Controller
{
    public function index()
    {
        $user = Session::get('user_rh');
        $role = $user['role'] ?? '';

        // HARD GUARD
        if (strtolower($role) === 'staff') {
            return redirect()->route('dashboard.index');
        }

        $pageTitle = 'Calon Kandidat';

        // Mirror exact SQL
        $candidates = DB::select("
            SELECT 
                m.id,
                m.ic_name,
                m.created_at,
                m.status,
                m.rejection_stage,

                r.score_total AS ai_score,
                r.decision   AS ai_decision,

                ir.average_score   AS interview_score,
                ir.ml_confidence   AS confidence,
                ir.is_locked       AS interview_locked,

                fd.final_result,

                (
                    SELECT COUNT(DISTINCT s.hr_id)
                    FROM applicant_interview_scores s
                    WHERE s.applicant_id = m.id
                ) AS total_hr,

                (
                    SELECT GROUP_CONCAT(DISTINCT u.full_name ORDER BY u.full_name SEPARATOR ', ')
                    FROM applicant_interview_scores s
                    JOIN user_rh u ON u.id = s.hr_id
                    WHERE s.applicant_id = m.id
                ) AS interviewers

            FROM medical_applicants m
            LEFT JOIN ai_test_results r 
                ON r.applicant_id = m.id
            LEFT JOIN applicant_interview_results ir
                ON ir.applicant_id = m.id
            LEFT JOIN applicant_final_decisions fd
                ON fd.applicant_id = m.id

            ORDER BY m.created_at DESC
        ");

        // Convert to array of arrays to match legacy behavior in blade if needed
        $candidates = array_map(function ($value) {
            return (array)$value;
        }, $candidates);

        return view('dashboard.candidates', compact('candidates', 'pageTitle'));
    }

    public function aiDecision(Request $request)
    {
        $user = Session::get('user_rh');
        $applicantId = (int)$request->input('applicant_id');
        $decision = $request->input('ai_decision');

        if ($applicantId <= 0 || !in_array($decision, ['proceed', 'reject'])) {
            return abort(400, 'Invalid request');
        }

        if ($decision === 'proceed') {
            DB::table('medical_applicants')
                ->where('id', $applicantId)
                ->where('status', 'ai_completed')
                ->update(['status' => 'interview']);
        }

        if ($decision === 'reject') {
            DB::beginTransaction();
            try {
                DB::table('medical_applicants')
                    ->where('id', $applicantId)
                    ->where('status', 'ai_completed')
                    ->update([
                    'status' => 'rejected',
                    'rejection_stage' => 'ai'
                ]);

                DB::table('applicant_final_decisions')->insert([
                    'applicant_id' => $applicantId,
                    'system_result' => 'tidak_lolos',
                    'overridden' => 0,
                    'override_reason' => null,
                    'final_result' => 'tidak_lolos',
                    'decided_by' => $user['name'] ?? 'System (AI)',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();
            }
            catch (\Exception $e) {
                DB::rollBack();
                return abort(500, 'Gagal memproses penolakan AI');
            }
        }

        return redirect()->route('dashboard.candidates');
    }

    public function finishInterview(Request $request)
    {
        $applicantId = (int)$request->input('applicant_id');

        // 🔒 HARD CHECK JUMLAH HR
        $totalHr = DB::table('applicant_interview_scores')
            ->where('applicant_id', $applicantId)
            ->distinct()
            ->count('hr_id');

        if ($totalHr < 2) {
            return redirect()->route('dashboard.candidates')->with('error', 'min_hr');
        }

        DB::table('medical_applicants')
            ->where('id', $applicantId)
            ->where('status', 'interview')
            ->update(['status' => 'final_review']);

        return redirect()->route('dashboard.candidates')->with('interview_done', 1);
    }

    public function show(Request $request)
    {
        $id = (int)$request->query('id', 0);
        if ($id <= 0) {
            return redirect()->route('dashboard.candidates');
        }

        $candidate = (array)DB::table('medical_applicants')->where('id', $id)->first();
        $result = (array)DB::table('ai_test_results')->where('applicant_id', $id)->first();

        if (!$candidate || !$result) {
            return abort(404, 'Data kandidat tidak lengkap');
        }

        $answers = json_decode($result['answers_json'] ?? '[]', true) ?? [];
        $questions = $this->candidateQuestions();

        // Get Documents
        $documents = DB::table('applicant_documents')
            ->where('applicant_id', $id)
            ->whereIn('document_type', ['ktp_ic', 'skb', 'sim'])
            ->get()
            ->keyBy('document_type')
            ->toArray();

        // Convert documents objects to arrays
        $documents = array_map(function ($doc) {
            return (array)$doc;
        }, $documents);

        $pageTitle = 'Detail Kandidat';

        return view('dashboard.candidate_detail', compact('candidate', 'result', 'answers', 'questions', 'documents', 'pageTitle'));
    }

    private function candidateQuestions()
    {
        return [
            1 => 'Apakah Anda pernah menyesuaikan jawaban agar terlihat lebih baik?',
            2 => 'Apakah Anda merasa sulit fokus jika duty terlalu lama?',
            3 => 'Apakah Anda lebih memilih mengikuti SOP meski situasi menekan?',
            4 => 'Apakah Anda merasa tidak semua orang perlu tahu isi pikiran Anda?',
            5 => 'Apakah Anda pernah menangani kondisi darurat di mana keputusan harus diambil tanpa alat medis lengkap?',
            6 => 'Apakah Anda merasa stabilitas lingkungan kerja memengaruhi performa Anda?',
            7 => 'Apakah Anda sering berubah jam online karena faktor lain di luar pekerjaan ini?',
            8 => 'Apakah Anda percaya adab dan etika kerja sama pentingnya dengan skill?',
            9 => 'Apakah Anda lebih nyaman bekerja tanpa banyak berbicara?',
            10 => 'Apakah Anda pernah meninggalkan tugas karena kewajiban di tempat lain?',
            11 => 'Apakah dalam situasi kritis, keselamatan nyawa lebih utama dibanding prosedur administratif?',
            12 => 'Apakah Anda merasa cepat kehilangan semangat jika hasil tidak langsung terlihat?',
            13 => 'Apakah Anda jarang menunjukkan stres meskipun sedang tertekan?',
            14 => 'Apakah Anda merasa wajar untuk sering berpindah instansi dalam waktu singkat?',
            15 => 'Apakah Anda merasa aturan kerja bisa diabaikan dalam kondisi tertentu?',
            16 => 'Apakah Anda lebih memilih diam saat emosi meningkat?',
            17 => 'Apakah Anda terbiasa menyelesaikan tugas meski waktu duty sudah panjang?',
            18 => 'Apakah Anda merasa jawaban jujur tidak selalu aman?',
            19 => 'Apakah Anda yakin dapat memisahkan tanggung jawab antar instansi secara profesional?',
            20 => 'Apakah Anda pernah menyesal karena melanggar prinsip kerja sendiri?',
            21 => 'Apakah Anda memahami bahwa tidak semua kondisi medis memungkinkan pemeriksaan lengkap sebelum tindakan?',
            22 => 'Apakah Anda lebih memilih mengamati sebelum terlibat aktif?',
            23 => 'Apakah Anda merasa makna pekerjaan lebih penting daripada posisi?',
            24 => 'Apakah Anda cenderung menyimpan emosi daripada mengungkapkannya?',
            25 => 'Apakah Anda jarang meninggalkan tugas saat sudah mulai bertugas?',
            26 => 'Apakah Anda percaya kesan pertama sangat menentukan?',
            27 => 'Apakah Anda merasa sulit membagi fokus jika memiliki tanggung jawab di lebih dari satu instansi?',
            28 => 'Apakah Anda merasa prinsip kerja dapat berubah tergantung situasi?',
            29 => 'Apakah Anda membutuhkan waktu untuk beradaptasi dengan tekanan baru?',
            30 => 'Apakah Anda merasa tidak nyaman jika jadwal kerja terlalu berubah-ubah?',
            31 => 'Apakah pada kondisi pasien sekarat dengan dugaan patah tulang, tindakan stabilisasi lebih diprioritaskan daripada pemeriksaan lanjutan seperti MRI?',
            32 => 'Apakah Anda jarang memulai percakapan lebih dulu dalam tim?',
            33 => 'Apakah Anda merasa jadwal tetap justru membatasi fleksibilitas Anda?',
            34 => 'Apakah Anda pernah bergabung ke instansi hanya karena ajakan lingkungan?',
            35 => 'Apakah Anda merasa stamina kerja memengaruhi kualitas pelayanan?',
            36 => 'Apakah Anda cenderung bertahan lebih lama jika sudah merasa cocok di satu tempat?',
            37 => 'Apakah Anda memiliki kecenderungan memprioritaskan peran lain jika terjadi bentrok jadwal?',
            38 => 'Apakah Anda sering menilai diri sendiri secara diam-diam?',
            39 => 'Apakah Anda merasa sulit berkomitmen jika baru berada di suatu kota dalam waktu singkat?',
            40 => 'Apakah Anda jarang memulai interaksi kecuali diperlukan?',
            41 => 'Apakah menurut Anda pemeriksaan MRI selalu wajib sebelum tindakan medis darurat?',
            42 => 'Apakah Anda terbiasa menyesuaikan jadwal demi tanggung jawab pekerjaan?',
            43 => 'Apakah Anda memilih diam saat tidak setuju demi menjaga suasana?',
            44 => 'Apakah Anda merasa loyalitas perlu dibagi secara seimbang jika memiliki banyak peran?',
            45 => 'Apakah Anda tetap bertahan meski peran yang dijalani terasa berat?',
            46 => 'Apakah Anda lebih memilih patuh demi menjaga suasana kerja?',
            47 => 'Apakah Anda sering menghitung waktu untuk segera menyelesaikan duty?',
            48 => 'Apakah Anda merasa betah di satu lingkungan kerja setelah waktu tertentu?',
            49 => 'Apakah Anda menyesuaikan sikap saat berbicara dengan atasan?',
            50 => 'Apakah Anda merasa menahan emosi adalah bentuk kedewasaan?',
        ];
    }

    public function interview(Request $request)
    {
        $user = session('user_rh');
        $hrId = (int)($user['id'] ?? 0);

        if ($hrId <= 0) {
            return abort(403, 'Unauthorized');
        }

        $applicantId = (int)($request->query('id') ?? $request->input('applicant_id') ?? 0);
        if ($applicantId <= 0) {
            return redirect()->route('dashboard.candidates');
        }

        $candidate = DB::table('medical_applicants')->where('id', $applicantId)->first();
        if (!$candidate) {
            return abort(404, 'Kandidat tidak ditemukan');
        }

        if (!in_array($candidate->status, ['ai_completed', 'interview'], true)) {
            return abort(400, 'Status kandidat belum valid untuk interview');
        }

        $isLocked = (int)DB::table('applicant_interview_results')->where('applicant_id', $applicantId)->value('is_locked');
        if ($isLocked === 1) {
            return redirect()->route('dashboard.candidates')->with('error', 'interview_locked');
        }

        $criteria = DB::table('interview_criteria')->where('is_active', 1)->orderBy('id', 'asc')->get();
        $existingScores = DB::table('applicant_interview_scores')
            ->where('applicant_id', $applicantId)
            ->where('hr_id', $hrId)
            ->pluck('score', 'criteria_id')
            ->toArray();
        
        $existingNotes = DB::table('applicant_interview_scores')
            ->where('applicant_id', $applicantId)
            ->where('hr_id', $hrId)
            ->value('notes') ?? '';

        $pageTitle = 'Interview Kandidat';

        return view('dashboard.candidate_interview_multi', compact('candidate', 'applicantId', 'criteria', 'existingScores', 'existingNotes', 'pageTitle'));
    }

    public function submitInterview(Request $request)
    {
        $user = session('user_rh');
        $hrId = (int)($user['id'] ?? 0);
        $applicantId = (int)$request->input('applicant_id');

        $criteria = DB::table('interview_criteria')->where('is_active', 1)->get();
        $scores = $request->input('score', []);
        $notes = trim($request->input('notes', ''));

        DB::beginTransaction();
        try {
            foreach ($criteria as $c) {
                if (!isset($scores[$c->id])) {
                    throw new \Exception('Skor belum lengkap');
                }

                $score = (int)$scores[$c->id];
                if ($score < 1 || $score > 5) {
                    throw new \Exception('Nilai tidak valid');
                }

                DB::table('applicant_interview_scores')->updateOrInsert(
                    ['applicant_id' => $applicantId, 'hr_id' => $hrId, 'criteria_id' => $c->id],
                    ['score' => $score, 'notes' => $notes ?: null, 'created_at' => now()]
                );
            }

            DB::table('medical_applicants')
                ->where('id', $applicantId)
                ->whereIn('status', ['ai_completed', 'interview'])
                ->update(['status' => 'interview']);

            DB::commit();
            return redirect()->route('dashboard.candidates')->with('flash_messages', ['Penilaian interview berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors([$e->getMessage()]);
        }
    }

    public function decision(Request $request)
    {
        $user = session('user_rh');
        $role = strtolower($user['role'] ?? '');

        if ($role === 'staff') {
            return redirect()->route('dashboard.index');
        }

        $applicantId = (int)$request->query('id', 0);
        if ($applicantId <= 0) {
            return redirect()->route('dashboard.candidates');
        }

        $candidate = (array)DB::table('medical_applicants')->where('id', $applicantId)->first();
        if (!$candidate) {
            return abort(404, 'Kandidat tidak ditemukan');
        }

        $ai = (array)DB::table('ai_test_results')->where('applicant_id', $applicantId)->first();
        if (!$ai) {
            return abort(400, 'AI Test belum tersedia');
        }

        $aiRecommendation = $ai['decision'];
        $interviewResult = (array)DB::table('applicant_interview_results')->where('applicant_id', $applicantId)->first();

        $systemResult = 'tidak_lolos';
        $combinedScore = 0;
        $isLocked = (int)($interviewResult['is_locked'] ?? 0) === 1;

        if (!$isLocked) {
            // Fetch live temporary data
            try {
                $tempResult = $this->calculateHybridInterviewScore($applicantId);
                $interviewResult = [
                    'average_score' => $tempResult['final_score'],
                    'final_grade' => $tempResult['final_grade'],
                    'ml_confidence' => $tempResult['ml_confidence'],
                    'ml_flags' => json_encode($tempResult['ml_flags']),
                    'is_locked' => 0
                ];
            } catch (\Exception $e) {
                $interviewResult = [
                    'average_score' => 0,
                    'final_grade' => 'N/A',
                    'ml_confidence' => 0,
                    'ml_flags' => '[]',
                    'is_locked' => 0
                ];
            }
        }

        $interviewScore = (float)($interviewResult['average_score'] ?? 0);
        $aiScore = (float)($ai['score_total'] ?? 0);
        $confidence = (float)($interviewResult['ml_confidence'] ?? 0);

        $combinedScore = round(($interviewScore * 0.6) + ($aiScore * 0.3) + ($confidence * 0.1), 2);

        if ($isLocked && $combinedScore >= 70 && $aiRecommendation !== 'not_recommended') {
            $systemResult = 'lolos';
        }

        $mlFlags = json_decode($interviewResult['ml_flags'] ?? '[]', true);
        $existingDecision = (array)DB::table('applicant_final_decisions')->where('applicant_id', $applicantId)->first();
        $pageTitle = 'Keputusan Akhir';

        return view('dashboard.candidate_decision', compact(
            'candidate', 'ai', 'aiRecommendation', 'interviewResult', 'systemResult', 
            'combinedScore', 'mlFlags', 'existingDecision', 'applicantId', 'pageTitle'
        ));
    }

    public function getTempScore(Request $request)
    {
        $applicantId = (int)$request->query('id');
        try {
            $result = $this->calculateHybridInterviewScore($applicantId);
            $ai = DB::table('ai_test_results')->where('applicant_id', $applicantId)->first();
            
            $aiScore = (float)($ai->score_total ?? 0);
            $combinedScore = round(($result['final_score'] * 0.6) + ($aiScore * 0.3) + ($result['ml_confidence'] * 0.1), 2);

            return response()->json([
                'success' => true,
                'average_score' => $result['final_score'],
                'final_grade' => strtoupper(str_replace('_', ' ', $result['final_grade'])),
                'ml_confidence' => $result['ml_confidence'],
                'combined_score' => $combinedScore,
                'ml_flags' => $result['ml_flags']
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function lockInterview(Request $request)
    {
        $applicantId = (int)$request->input('applicant_id');
        
        try {
            $this->finalizeInterview($applicantId);
            return redirect()->route('dashboard.candidates.decision_page', ['id' => $applicantId])->with('flash_messages', ['Interview berhasil dikunci']);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash_errors', [$e->getMessage()]);
        }
    }

    public function submitDecision(Request $request)
    {
        $user = session('user_rh');
        $applicantId = (int)$request->input('applicant_id');
        
        $interviewResult = DB::table('applicant_interview_results')->where('applicant_id', $applicantId)->first();
        if (!$interviewResult || (int)$interviewResult->is_locked !== 1) {
            return redirect()->back()->with('flash_errors', ['Interview harus dikunci sebelum keputusan akhir.']);
        }

        $systemResultPost = $request->input('system_result');
        $override = $request->has('override');
        $reason = trim($request->input('override_reason', ''));

        if ($override && empty($reason)) {
            return redirect()->back()->with('flash_errors', ['Alasan override wajib diisi']);
        }

        $finalResult = $override ? ($systemResultPost === 'lolos' ? 'tidak_lolos' : 'lolos') : $systemResultPost;

        DB::beginTransaction();
        try {
            $exists = DB::table('applicant_final_decisions')->where('applicant_id', $applicantId)->exists();
            if ($exists) {
                throw new \Exception('Keputusan sudah dibuat oleh user lain.');
            }

            DB::table('applicant_final_decisions')->insert([
                'applicant_id' => $applicantId,
                'system_result' => $systemResultPost,
                'overridden' => $override ? 1 : 0,
                'override_reason' => $override ? $reason : null,
                'final_result' => $finalResult,
                'decided_by' => $user['name'] ?? 'Manager',
                'decided_at' => now(),
            ]);

            $newStatus = $finalResult === 'lolos' ? 'accepted' : 'rejected';
            DB::table('medical_applicants')->where('id', $applicantId)->update([
                'status' => $newStatus,
                'rejection_stage' => $newStatus === 'rejected' ? 'interview' : null
            ]);

            DB::commit();
            return redirect()->route('dashboard.candidates.detail', ['id' => $applicantId])->with('flash_messages', ['Keputusan akhir berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('flash_errors', [$e->getMessage()]);
        }
    }

    private function finalizeInterview(int $applicantId)
    {
        $result = $this->calculateHybridInterviewScore($applicantId);

        DB::beginTransaction();
        try {
            $totalHr = DB::table('applicant_interview_scores')
                ->where('applicant_id', $applicantId)
                ->distinct()
                ->count('hr_id');

            if ($totalHr < 2) {
                throw new \Exception('Interview harus dinilai minimal oleh 2 HR');
            }

            DB::table('applicant_interview_results')->updateOrInsert(
                ['applicant_id' => $applicantId],
                [
                    'total_hr' => $totalHr,
                    'average_score' => $result['final_score'],
                    'final_grade' => $result['final_grade'],
                    'ml_flags' => json_encode($result['ml_flags']),
                    'ml_confidence' => $result['ml_confidence'],
                    'is_locked' => 1,
                    'locked_at' => now(),
                ]
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function calculateHybridInterviewScore(int $applicantId): array
    {
        $rows = DB::table('applicant_interview_scores as s')
            ->join('interview_criteria as c', 'c.id', '=', 's.criteria_id')
            ->where('s.applicant_id', $applicantId)
            ->select('s.hr_id', 's.score', 'c.weight')
            ->get();

        if ($rows->isEmpty()) {
            throw new \Exception('Belum ada data interview');
        }

        $perHr = [];
        foreach ($rows as $r) {
            $hr = $r->hr_id;
            if (!isset($perHr[$hr])) {
                $perHr[$hr] = ['sum' => 0, 'weight' => 0];
            }
            $perHr[$hr]['sum'] += ($r->score * $r->weight);
            $perHr[$hr]['weight'] += $r->weight;
        }

        $hrAverages = [];
        foreach ($perHr as $hr => $v) {
            $hrAverages[$hr] = $v['sum'] / $v['weight'];
        }

        $baseScore = array_sum($hrAverages) / count($hrAverages);
        
        $variance = 0;
        foreach ($hrAverages as $avg) {
            $variance += pow($avg - $baseScore, 2);
        }
        $stdDev = sqrt($variance / count($hrAverages));

        $consistencyFactor = max(0.85, min(1.0, 1 - ($stdDev / 3)));
        $finalScore = round(($baseScore * 20) * $consistencyFactor, 2);

        if ($finalScore >= 85) { $grade = 'sangat_baik'; }
        elseif ($finalScore >= 70) { $grade = 'baik'; }
        elseif ($finalScore >= 55) { $grade = 'sedang'; }
        elseif ($finalScore >= 40) { $grade = 'buruk'; }
        else { $grade = 'sangat_buruk'; }

        $flags = [];
        if ($stdDev > 1.0) { $flags['score_variance'] = 'tinggi'; }
        elseif ($stdDev > 0.5) { $flags['score_variance'] = 'sedang'; }
        else { $flags['score_variance'] = 'rendah'; }

        return [
            'final_score' => $finalScore,
            'final_grade' => $grade,
            'ml_flags' => $flags,
            'ml_confidence' => round(100 * $consistencyFactor, 2)
        ];
    }
}
