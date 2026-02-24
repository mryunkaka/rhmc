<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RememberToken extends Model
{
    protected $table = 'remember_tokens';

    // Disable timestamps karena tabel hanya punya created_at
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'token_hash',
        'expired_at',
        'created_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Relasi ke user pemilik token
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserRh::class, 'user_id');
    }

    /**
     * Cek apakah token masih valid (belum expired)
     */
    public function isValid(): bool
    {
        return $this->expired_at->isFuture();
    }

    /**
     * Hapus semua token yang sudah expired untuk user ini
     */
    public static function deleteExpired(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('expired_at', '<', now())
            ->delete();
    }

    /**
     * Hapus semua token aktif untuk user ini (force login)
     */
    public static function deleteAllForUser(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('expired_at', '>', now())
            ->delete();
    }

    /**
     * Hitung jumlah token aktif untuk user
     */
    public static function countActiveForUser(int $userId): int
    {
        return self::where('user_id', $userId)
            ->where('expired_at', '>', now())
            ->count();
    }

    /**
     * Cek apakah token valid untuk user (berdasarkan token hash dari cookie)
     */
    public static function isTokenValid(int $userId, string $token): bool
    {
        $tokens = self::where('user_id', $userId)
            ->where('expired_at', '>', now())
            ->get();

        foreach ($tokens as $rememberToken) {
            if (password_verify($token, $rememberToken->token_hash)) {
                return true;
            }
        }

        return false;
    }
}
