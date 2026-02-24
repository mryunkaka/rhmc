<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFarmasiSession extends Model
{
    use HasFactory;

    protected $table = 'user_farmasi_sessions';

    protected $fillable = [
        'user_id',
        'medic_name',
        'medic_jabatan',
        'session_start',
        'session_end',
        'duration_seconds',
        'end_reason',
        'ended_by_user_id',
    ];

    protected $casts = [
        'session_start' => 'datetime',
        'session_end' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(UserRh::class, 'user_id');
    }

    public function ender()
    {
        return $this->belongsTo(UserRh::class, 'ended_by_user_id');
    }

    public function isEnded(): bool
    {
        return $this->session_end !== null;
    }

    public function getDurationFormattedAttribute(): string
    {
        if ($this->duration_seconds === null) {
            return 'Active';
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%dh %dm %ds', $hours, $minutes, $seconds);
        }

        if ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $seconds);
        }

        return sprintf('%ds', $seconds);
    }
}
