<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserRh extends Model
{
    use HasFactory;

    protected $table = 'user_rh';

    protected $fillable = [
        'full_name',
        'citizen_id',
        'no_hp_ic',
        'jenis_kelamin',
        'pin',
        'api_token',
        'role',
        'batch',
        'kode_nomor_induk_rs',
        'position',
        'tanggal_masuk',
        'photo_profile',
        'file_ktp',
        'file_sim',
        'file_kta',
        'file_skb',
        'sertifikat_heli',
        'sertifikat_operasi',
        'dokumen_lainnya',
        'is_verified',
        'is_active',
        'resign_reason',
        'resigned_by',
        'resigned_at',
        'reactivated_at',
        'reactivated_by',
        'reactivated_note',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'resigned_at' => 'datetime',
        'reactivated_at' => 'datetime',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'pin_changed' => 'boolean',
    ];

    protected $hidden = [
        'pin',
        'api_token',
    ];

    // Relationships
    public function farmasiStatus(): HasOne
    {
        return $this->hasOne(UserFarmasiStatus::class, 'user_id');
    }

    public function farmasiSessions(): HasMany
    {
        return $this->hasMany(UserFarmasiSession::class, 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserFarmasiNotification::class, 'user_id');
    }

    public function inbox(): HasMany
    {
        return $this->hasMany(UserInbox::class, 'user_id');
    }

    public function rememberTokens(): HasMany
    {
        return $this->hasMany(RememberToken::class, 'user_id');
    }

    public function accountLogs(): HasMany
    {
        return $this->hasMany(AccountLog::class, 'user_id');
    }

    // Role helpers
    public function isDirector(): bool
    {
        return $this->role === 'Director';
    }

    public function isViceDirector(): bool
    {
        return $this->role === 'Vice Director';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['Manager', 'Vice Director', 'Director']);
    }

    public function isStaffManager(): bool
    {
        return in_array($this->role, ['Staff Manager', 'Manager', 'Vice Director', 'Director']);
    }

    public function isStaff(): bool
    {
        return $this->role === 'Staff';
    }

    public function canManage(UserRh $user): bool
    {
        $roleHierarchy = [
            'Staff' => 1,
            'Staff Manager' => 2,
            'Manager' => 3,
            'Vice Director' => 4,
            'Director' => 5,
        ];

        return ($roleHierarchy[$this->role] ?? 0) > ($roleHierarchy[$user->role] ?? 0);
    }
}
