<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\MedicalApplicant;
use App\Models\ApplicantDocument;
use App\Models\AiTestResult;

class PublicRecruitmentController extends Controller
{
    /**
     * Show recruitment form
     * GET /recruitment
     */
    public function showForm()
    {
        return view('public.recruitment.form');
    }

    /**
     * Handle recruitment submission
     * POST /recruitment/submit
     */
    public function submitForm(Request $request)
    {
        $request->validate([
            'ic_name' => 'required',
            'ic_phone' => 'required',
            'ooc_age' => 'required|numeric',
            'academy_ready' => 'required',
            'rule_commitment' => 'required',
            'ktp_ic' => 'required|image|mimes:jpeg,jpg',
            'skb' => 'required|image|mimes:jpeg,jpg',
            'sim' => 'nullable|image|mimes:jpeg,jpg',
        ]);

        $icName = trim($request->ic_name);
        $icPhone = trim($request->ic_phone);
        $folderName = $this->slugName($icName) . '_' . $icPhone;

        DB::beginTransaction();

        try {
            $applicant = MedicalApplicant::create([
                'ic_name' => $icName,
                'ooc_age' => $request->ooc_age,
                'ic_phone' => $icPhone,
                'medical_experience' => $request->medical_experience,
                'city_duration' => $request->city_duration,
                'online_schedule' => $request->online_schedule,
                'other_city_responsibility' => $request->other_city_responsibility,
                'motivation' => $request->motivation,
                'work_principle' => $request->work_principle,
                'academy_ready' => $request->academy_ready,
                'rule_commitment' => $request->rule_commitment,
                'duty_duration' => $request->duty_duration,
                'status' => 'ai_test'
            ]);

            $uploadDir = public_path('storage/applicants/' . $folderName);
            if (!File::exists($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }

            foreach (['ktp_ic', 'skb', 'sim'] as $docType) {
                if ($request->hasFile($docType)) {
                    $file = $request->file($docType);
                    $fileName = $docType . '.jpg';
                    $targetPath = $uploadDir . '/' . $fileName;

                    $this->compressJpegSmart($file->getPathname(), $targetPath);

                    ApplicantDocument::create([
                        'applicant_id' => $applicant->id,
                        'document_type' => $docType,
                        'file_path' => 'storage/applicants/' . $folderName . '/' . $fileName
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('public.recruitment.ai_test', ['applicant_id' => $applicant->id]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Show AI test page
     * GET /recruitment/ai-test
     */
    public function showAiTest(Request $request)
    {
        $applicantId = (int)$request->query('applicant_id', 0);
        if ($applicantId <= 0) {
            return redirect()->route('public.recruitment.form');
        }

        $applicant = MedicalApplicant::find($applicantId);
        if (!$applicant) {
            return redirect()->route('public.recruitment.form');
        }

        if ($applicant->status !== 'ai_test') {
            return redirect()->route('public.recruitment.done');
        }

        $alreadySubmitted = AiTestResult::where('applicant_id', $applicantId)->exists();
        if ($alreadySubmitted) {
            return redirect()->route('public.recruitment.done');
        }

        $questions = $this->getQuestions();

        return view('public.recruitment.ai_test', compact('applicant', 'applicantId', 'questions'));
    }

    /**
     * Handle AI test submission
     * POST /recruitment/ai-test/submit
     */
    public function submitAiTest(Request $request)
    {
        $applicantId = (int)$request->input('applicant_id');
        $applicant = MedicalApplicant::findOrFail($applicantId);

        // Check double submit
        if (AiTestResult::where('applicant_id', $applicantId)->exists()) {
            return redirect()->route('public.recruitment.done');
        }

        $answers = [];
        for ($i = 1; $i <= 50; $i++) {
            $answers[$i] = $request->input('q' . $i);
            if ($answers[$i] === null) {
                return abort(400, 'Pertanyaan tidak lengkap');
            }
        }

        $startTime = (int)$request->input('start_time', 0);
        $endTime = (int)$request->input('end_time', time());
        $duration = (int)$request->input('duration_seconds', max(0, $endTime - $startTime));

        // AI SCORING ENGINE (Mirrored logic)
        $traitItems = $this->getTraitItems();
        $scores = [];
        foreach ($traitItems as $trait => $items) {
            $scores[$trait] = $this->calculateTraitScore($answers, $items);
        }

        $biasFlags = $this->detectResponseBias($answers);
        $crossFlags = $this->crossValidateWithForm($scores, $applicant->toArray());
        $finalDecision = $this->makeFinalDecision($scores, $biasFlags, $crossFlags, $duration);
        $personalityNarrative = $this->generatePsychologicalNarrative($scores, $finalDecision);

        DB::beginTransaction();
        try {
            $totalScore = round($finalDecision['average_score'], 2);

            AiTestResult::create([
                'applicant_id' => $applicantId,
                'answers_json' => json_encode($answers, JSON_UNESCAPED_UNICODE),
                'duration_seconds' => $duration,
                'score_total' => $totalScore,
                'focus_score' => (int)$scores['focus']['score'],
                'consistency_score' => (int)$scores['consistency']['score'],
                'social_score' => (int)$scores['social']['score'],
                'attitude_score' => (int)$scores['emotional_stability']['score'],
                'loyalty_score' => (int)$scores['obedience']['score'],
                'honesty_score' => (int)$scores['honesty_humility']['score'],
                'risk_flags' => json_encode([
                    'bias' => $biasFlags,
                    'cross' => $crossFlags
                ], JSON_UNESCAPED_UNICODE),
                'personality_summary' => $personalityNarrative,
                'decision' => $finalDecision['decision']
            ]);

            $applicant->update(['status' => 'ai_completed']);

            DB::commit();
            return redirect()->route('public.recruitment.done');

        } catch (\Exception $e) {
            DB::rollBack();
            return abort(500, 'AI Test save failed: ' . $e->getMessage());
        }
    }

    /**
     * Show recruitment done page
     * GET /recruitment/done
     */
    public function showDone()
    {
        return view('public.recruitment.done');
    }

    /* ===============================
       PRIVATE HELPERS (MIRRORED)
       =============================== */

    private function compressJpegSmart(string $sourcePath, string $targetPath, int $maxWidth = 1200, int $targetSize = 300000, int $minQuality = 70): bool
    {
        $src = imagecreatefromstring(file_get_contents($sourcePath));
        if (!$src) return false;

        $w = imagesx($src);
        $h = imagesy($src);

        if ($w > $maxWidth) {
            $ratio = $maxWidth / $w;
            $nw = $maxWidth;
            $nh = (int)($h * $ratio);
            $dst = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
        } else {
            $dst = $src;
        }

        imageinterlace($dst, true);

        for ($q = 90; $q >= $minQuality; $q -= 5) {
            imagejpeg($dst, $targetPath, $q);
            if (filesize($targetPath) <= $targetSize) {
                imagedestroy($dst);
                return true;
            }
        }

        imagejpeg($dst, $targetPath, $minQuality);
        imagedestroy($dst);
        return true;
    }

    private function slugName(string $name): string
    {
        return Str::slug($name, '_');
    }

    private function getQuestions(): array
    {
        return [
            1  => 'Apakah Anda pernah menyesuaikan jawaban agar terlihat lebih baik?',
            2  => 'Apakah Anda merasa sulit fokus jika duty terlalu lama?',
            3  => 'Apakah Anda lebih memilih mengikuti SOP meski situasi menekan?',
            4  => 'Apakah Anda merasa tidak semua orang perlu tahu isi pikiran Anda?',
            5  => 'Apakah Anda pernah menangani kondisi darurat di mana keputusan harus diambil tanpa alat medis lengkap?',
            6  => 'Apakah Anda merasa stabilitas lingkungan kerja memengaruhi performa Anda?',
            7  => 'Apakah Anda sering berubah jam online karena faktor lain di luar pekerjaan ini?',
            8  => 'Apakah Anda percaya adab dan etika kerja sama pentingnya dengan skill?',
            9  => 'Apakah Anda lebih nyaman bekerja tanpa banyak berbicara?',
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

    private function getTraitItems(): array
    {
        return [
            'focus' => [
                2  => ['direction' => 'reverse', 'weight' => 1.0],
                17 => ['direction' => 'normal',  'weight' => 1.0],
                35 => ['direction' => 'normal',  'weight' => 1.0],
                47 => ['direction' => 'reverse', 'weight' => 1.0],
                6  => ['direction' => 'normal',  'weight' => 0.8],
                29 => ['direction' => 'normal',  'weight' => 0.8],
            ],
            'social' => [
                9  => ['direction' => 'reverse', 'weight' => 1.0],
                22 => ['direction' => 'reverse', 'weight' => 1.0],
                32 => ['direction' => 'reverse', 'weight' => 1.0],
                40 => ['direction' => 'reverse', 'weight' => 1.0],
                4  => ['direction' => 'reverse', 'weight' => 0.7],
            ],
            'obedience' => [
                3  => ['direction' => 'normal',  'weight' => 1.0],
                8  => ['direction' => 'normal',  'weight' => 1.0],
                15 => ['direction' => 'reverse', 'weight' => 1.0],
                28 => ['direction' => 'reverse', 'weight' => 1.0],
                26 => ['direction' => 'normal',  'weight' => 0.8],
                46 => ['direction' => 'normal',  'weight' => 0.8],
            ],
            'consistency' => [
                7  => ['direction' => 'reverse', 'weight' => 1.0],
                10 => ['direction' => 'reverse', 'weight' => 1.0],
                14 => ['direction' => 'reverse', 'weight' => 1.0],
                36 => ['direction' => 'normal',  'weight' => 1.0],
                45 => ['direction' => 'normal',  'weight' => 1.0],
                48 => ['direction' => 'normal',  'weight' => 1.0],
                39 => ['direction' => 'reverse', 'weight' => 0.8],
            ],
            'emotional_stability' => [
                13 => ['direction' => 'normal',  'weight' => 1.0],
                16 => ['direction' => 'normal',  'weight' => 1.0],
                24 => ['direction' => 'normal',  'weight' => 1.0],
                50 => ['direction' => 'normal',  'weight' => 1.0],
                12 => ['direction' => 'reverse', 'weight' => 0.9],
            ],
            'honesty_humility' => [
                15 => ['direction' => 'reverse', 'weight' => 1.0],
                28 => ['direction' => 'reverse', 'weight' => 1.0],
                4  => ['direction' => 'reverse', 'weight' => 0.8],
                19 => ['direction' => 'reverse', 'weight' => 0.8],
                37 => ['direction' => 'reverse', 'weight' => 0.8],
                44 => ['direction' => 'reverse', 'weight' => 0.8],
                23 => ['direction' => 'normal',  'weight' => 0.7],
                8  => ['direction' => 'normal',  'weight' => 0.7],
            ],
        ];
    }

    private function calculateTraitScore(array $answers, array $items): array
    {
        $raw = 0.0;
        $max = 0.0;
        $used = 0;

        foreach ($items as $q => $cfg) {
            if (!isset($answers[$q])) continue;

            $v = ($answers[$q] === 'ya') ? 1 : 0;
            if ($cfg['direction'] === 'reverse') {
                $v = 1 - $v;
            }

            $raw += $v * $cfg['weight'];
            $max += $cfg['weight'];
            $used++;
        }

        $score = $used > 0 ? ($raw / $max) * 100 : 50;

        return [
            'score'        => round($score, 2),
            'items_used'  => $used,
            'reliability' => $this->reliabilityLevel($used),
        ];
    }

    private function reliabilityLevel(int $n): string
    {
        if ($n >= 8) return 'good';
        if ($n >= 5) return 'acceptable';
        if ($n >= 3) return 'questionable';
        return 'poor';
    }

    private function detectResponseBias(array $answers): array
    {
        $flags = [];
        $counts = array_count_values($answers);
        $ya = $counts['ya'] ?? 0;
        $tidak = $counts['tidak'] ?? 0;
        $total = count($answers);

        if ($total > 0) {
            if ($ya / $total > 0.85) $flags[] = 'acquiescence_bias';
            if ($tidak / $total > 0.85) $flags[] = 'disacquiescence_bias';
        }

        $prev = null;
        $run = 1;
        $maxRun = 1;

        foreach ($answers as $a) {
            if ($a === $prev) {
                $run++;
                $maxRun = max($maxRun, $run);
            } else {
                $run = 1;
            }
            $prev = $a;
        }

        if ($maxRun >= 10) {
            $flags[] = 'pattern_answering';
        }

        return $flags;
    }

    private function crossValidateWithForm(array $scores, array $applicant): array
    {
        $flags = [];

        if (
            ($applicant['rule_commitment'] ?? '') === 'ya' &&
            ($scores['obedience']['score'] ?? 0) < 40
        ) {
            $flags[] = 'rule_commitment_mismatch';
        }

        if (
            trim($applicant['other_city_responsibility'] ?? '-') !== '-' &&
            ($scores['consistency']['score'] ?? 0) < 50
        ) {
            $flags[] = 'multi_responsibility_risk';
        }

        if (
            Str::contains(Str::lower($applicant['motivation'] ?? ''), 'jangka panjang') &&
            ($scores['consistency']['score'] ?? 0) < 50
        ) {
            $flags[] = 'motivation_behavior_mismatch';
        }

        return $flags;
    }

    private function makeFinalDecision(array $scores, array $biasFlags, array $crossFlags, int $durationSeconds): array
    {
        $avg = array_sum(array_column($scores, 'score')) / count($scores);

        $decision = 'consider';
        $confidence = 'medium';
        $reasons = [];

        if (
            $avg >= 65 &&
            ($scores['honesty_humility']['score'] ?? 0) >= 60 &&
            count($biasFlags) === 0 &&
            $durationSeconds >= 300 &&
            $durationSeconds <= 3600
        ) {
            $decision = 'recommended';
            $confidence = 'high';
            $reasons[] = 'Profil psikologis seimbang & integritas baik';
        }

        if (
            $avg < 40 ||
            ($scores['honesty_humility']['score'] ?? 0) < 40 ||
            count($biasFlags) >= 2 ||
            $durationSeconds < 180
        ) {
            $decision = 'not_recommended';
            $confidence = 'high';
            $reasons[] = 'Risiko integritas atau kualitas respon';
        }

        if (!$reasons) {
            $reasons[] = 'Perlu evaluasi lanjutan oleh HR';
        }

        return [
            'decision'        => $decision,
            'confidence'      => $confidence,
            'average_score'   => round($avg, 2),
            'honesty_score'   => $scores['honesty_humility']['score'] ?? null,
            'bias_flags'      => $biasFlags,
            'cross_flags'     => $crossFlags,
            'duration_minute' => round($durationSeconds / 60, 1),
        ];
    }

    private function generatePsychologicalNarrative(array $scores, array $finalDecision): string
    {
        $lines = [];
        $honesty = $scores['honesty_humility']['score'] ?? null;

        if ($honesty !== null) {
            if ($honesty >= 75) {
                $lines[] = 'Menunjukkan tingkat integritas pribadi yang tinggi, cenderung jujur, tidak manipulatif, dan menjaga etika kerja.';
            } elseif ($honesty >= 55) {
                $lines[] = 'Menunjukkan integritas kerja yang cukup baik, meskipun masih dipengaruhi oleh situasi tertentu.';
            } else {
                $lines[] = 'Menunjukkan indikasi risiko integritas, sehingga perlu pengawasan dan sistem kerja yang jelas.';
            }
        }

        if ($scores['focus']['score'] >= 65 && $scores['consistency']['score'] >= 65) {
            $lines[] = 'Memiliki fokus dan daya tahan kerja yang baik, cocok untuk tugas dengan durasi panjang dan tekanan operasional.';
        } elseif ($scores['focus']['score'] < 50) {
            $lines[] = 'Perlu dukungan strategi kerja untuk menjaga fokus dalam tugas jangka panjang.';
        }

        if ($scores['emotional_stability']['score'] >= 65) {
            $lines[] = 'Cenderung stabil secara emosional dan mampu mengelola tekanan kerja dengan cukup baik.';
        } elseif ($scores['emotional_stability']['score'] < 50) {
            $lines[] = 'Memerlukan lingkungan kerja yang suportif untuk menjaga kestabilan emosi.';
        }

        if ($scores['social']['score'] >= 65) {
            $lines[] = 'Memiliki kecenderungan komunikatif dan relatif mudah berinteraksi dengan tim.';
        } else {
            $lines[] = 'Cenderung bekerja dengan gaya observatif dan tidak terlalu ekspresif secara sosial.';
        }

        if ($finalDecision['decision'] === 'recommended') {
            $lines[] = 'Secara keseluruhan, profil psikologis mendukung untuk dipertimbangkan pada peran yang membutuhkan tanggung jawab dan kepercayaan.';
        } elseif ($finalDecision['decision'] === 'not_recommended') {
            $lines[] = 'Secara keseluruhan, profil psikologis menunjukkan beberapa risiko yang perlu dipertimbangkan secara serius.';
        } else {
            $lines[] = 'Profil psikologis menunjukkan kombinasi kekuatan dan area pengembangan yang perlu dievaluasi lebih lanjut.';
        }

        return implode(' ', $lines);
    }
}
