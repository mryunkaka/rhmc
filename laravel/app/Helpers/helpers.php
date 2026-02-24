<?php
/**
 * RHMC Helpers - Laravel Migration
 */

if (!function_exists('initialsFromName')) {
    function initialsFromName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }
        if (isset($parts[0]) && $parts[0] !== '') {
            return mb_strtoupper(mb_substr($parts[0], 0, 2));
        }
        return '??';
    }
}

if (!function_exists('avatarColorFromName')) {
    function avatarColorFromName(string $name): string
    {
        $hash = crc32(mb_strtolower(trim($name)));
        $hue  = $hash % 360;
        return "hsl($hue, 70%, 45%)";
    }
}

if (!function_exists('formatTanggalID')) {
    function formatTanggalID($datetime): string
    {
        if (!$datetime) return '-';
        $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        try {
            $dt = new \DateTime($datetime);
            $hari  = (int)$dt->format('j');
            $bulanTxt = $bulan[(int)$dt->format('n')];
            $tahun = $dt->format('Y');
            $jam   = $dt->format('H:i');
            return "{$hari} {$bulanTxt} {$tahun} {$jam}";
        } catch (\Exception $e) { return (string)$datetime; }
    }
}

if (!function_exists('formatTanggalIndo')) {
    function formatTanggalIndo($date): string
    {
        if (!$date) return '-';
        $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        try {
            $d = ($date instanceof \DateTime) ? $date : new \DateTime($date);
            $hari      = $d->format('d');
            $bulanNama = $bulan[(int)$d->format('n')];
            $tahun     = substr($d->format('Y'), 2);
            return "{$hari} {$bulanNama} {$tahun}";
        } catch (\Exception $e) { return (string)$date; }
    }
}

if (!function_exists('safeRegulation')) {
    function safeRegulation(string $code): int
    {
        $r = \App\Models\MedicalRegulation::where('code', $code)->where('is_active', 1)->first();
        if (!$r) throw new \Exception("Regulasi tidak ditemukan: {$code}");
        if ($r->price_type === 'RANGE') return random_int((int)$r->price_min, (int)$r->price_max);
        return (int)$r->price_min;
    }
}

if (!function_exists('dollar')) {
    function dollar($amount): string
    {
        return '$' . number_format((float)$amount, 0, ',', '.');
    }
}

if (!function_exists('formatDurasiMedis')) {
    function formatDurasiMedis(?string $tanggalMasuk): string
    {
        if (empty($tanggalMasuk)) return '-';
        try {
            $start = new \DateTime($tanggalMasuk);
            $now   = new \DateTime();
            if ($start > $now) return '-';
            $diff = $start->diff($now);
            if ($diff->y > 0) {
                return $diff->y . ' tahun' . ($diff->m > 0 ? ' ' . $diff->m . ' bulan' : '');
            }
            if ($diff->m > 0) {
                return $diff->m . ' bulan';
            }
            $days = (int)$diff->days;
            if ($days >= 7) {
                return floor($days / 7) . ' minggu';
            }
            return $days . ' hari';
        } catch (\Exception $e) { return '-'; }
    }
}

if (!function_exists('getDateRangeData')) {
    /**
     * Get Date Range Data
     * Mirror dari: legacy/config/date_range.php
     * @param string $range Range type: today, yesterday, last7, week1, week2, week3, week4, custom
     * @param string|null $from Custom from date (Y-m-d)
     * @param string|null $to Custom to date (Y-m-d)
     * @return array Array dengan keys: start, end, label, rangeStart, rangeEnd, rangeLabel, weeks
     */
    function getDateRangeData($range = 'week4', $from = null, $to = null): array
    {
        return \App\Helpers\DateRange::getRange($range, $from, $to);
    }
}

if (!function_exists('getServerTime')) {
    /**
     * Get Server Time
     * Mirror dari: legacy/config/server_time.php
     * @return string Current server time in Y-m-d H:i:s format
     */
    function getServerTime(): string
    {
        return \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
    }
}
