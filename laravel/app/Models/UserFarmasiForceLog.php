<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFarmasiForceLog extends Model
{
    use HasFactory;

    protected $table = 'user_farmasi_force_logs';

    protected $fillable = [
        'target_user_id',
        'forced_by_user_id',
        'reason',
        'forced_at',
    ];

    protected $casts = [
        'forced_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function targetUser()
    {
        return $this->belongsTo(UserRh::class, 'target_user_id');
    }

    public function forcedByUser()
    {
        return $this->belongsTo(UserRh::class, 'forced_by_user_id');
    }
}
