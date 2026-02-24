<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFarmasiStatus extends Model
{
    use HasFactory;

    protected $table = 'user_farmasi_status';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'status',
        'last_activity_at',
        'last_confirm_at',
        'auto_offline_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'last_confirm_at' => 'datetime',
        'auto_offline_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(UserRh::class, 'user_id');
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function markOnline(): void
    {
        $this->update([
            'status' => 'online',
            'last_activity_at' => now(),
            'last_confirm_at' => now(),
        ]);
    }

    public function markOffline(): void
    {
        $this->update([
            'status' => 'offline',
            'last_activity_at' => now(),
        ]);
    }
}
