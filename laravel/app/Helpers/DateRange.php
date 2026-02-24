<?php

namespace App\Helpers;

use Carbon\Carbon;
use DateTime;

class DateRange
{
    /**
     * Date Range Helper
     * Mirror dari: legacy/config/date_range.php
     */
    public static function getRange($range = 'week4', $from = null, $to = null)
    {
        $now = Carbon::now('Asia/Jakarta');

        $validRanges = [
            'today',
            'yesterday',
            'last7',
            'week1',
            'week2',
            'week3',
            'week4',
            'custom'
        ];

        if (!in_array($range, $validRanges, true)) {
            $range = 'week4';
        }

        $mondayThisWeek = $now->copy()->startOfWeek()->setTime(0, 0, 0);

        $weeks = [
            'week1' => [
                'start' => $mondayThisWeek->copy()->subWeeks(3)->setTime(0, 0, 0),
                'end' => $mondayThisWeek->copy()->subWeeks(3)->endOfWeek()->setTime(23, 59, 59),
            ],
            'week2' => [
                'start' => $mondayThisWeek->copy()->subWeeks(2)->setTime(0, 0, 0),
                'end' => $mondayThisWeek->copy()->subWeeks(2)->endOfWeek()->setTime(23, 59, 59),
            ],
            'week3' => [
                'start' => $mondayThisWeek->copy()->subWeeks(1)->setTime(0, 0, 0),
                'end' => $mondayThisWeek->copy()->subWeeks(1)->endOfWeek()->setTime(23, 59, 59),
            ],
            'week4' => [
                'start' => $mondayThisWeek->copy()->setTime(0, 0, 0),
                'end' => $mondayThisWeek->copy()->endOfWeek()->setTime(23, 59, 59),
            ],
        ];

        $rangeStart = null;
        $rangeEnd = null;

        switch ($range) {
            case 'today':
                $rangeStart = $now->copy()->setTime(0, 0, 0);
                $rangeEnd = $now->copy()->setTime(23, 59, 59);
                break;

            case 'yesterday':
                $rangeStart = $now->copy()->subDay()->setTime(0, 0, 0);
                $rangeEnd = $now->copy()->subDay()->setTime(23, 59, 59);
                break;

            case 'last7':
                $rangeStart = $now->copy()->subDays(6)->setTime(0, 0, 0);
                $rangeEnd = $now->copy()->setTime(23, 59, 59);
                break;

            case 'week1':
            case 'week2':
            case 'week3':
            case 'week4':
                $rangeStart = $weeks[$range]['start'];
                $rangeEnd = $weeks[$range]['end'];
                break;

            case 'custom':
                if ($from && $to) {
                    $rangeStart = Carbon::parse($from . ' 00:00:00', 'Asia/Jakarta');
                    $rangeEnd = Carbon::parse($to . ' 23:59:59', 'Asia/Jakarta');
                }
                else {
                    $rangeStart = $weeks['week4']['start'];
                    $rangeEnd = $weeks['week4']['end'];
                }
                break;
        }

        return [
            'start' => $rangeStart->format('Y-m-d H:i:s'),
            'end' => $rangeEnd->format('Y-m-d H:i:s'),
            'label' => $rangeStart->format('d M Y') . ' – ' . $rangeEnd->format('d M Y'),
            'rangeStart' => $rangeStart->format('Y-m-d H:i:s'),
            'rangeEnd' => $rangeEnd->format('Y-m-d H:i:s'),
            'rangeLabel' => $rangeStart->format('d M Y') . ' – ' . $rangeEnd->format('d M Y'),
            'weeks' => $weeks
        ];
    }

    /**
     * Alias untuk getDateRangeData (compatibility dengan legacy)
     */
    public static function getDateRangeData($range = 'week4', $from = null, $to = null)
    {
        return self::getRange($range, $from, $to);
    }
}
