<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicOperasiPlastik extends Model
{
    use HasFactory;

    protected $table = 'medic_operasi_plastik';

    protected $fillable = [
        'id_user',
        'tanggal',
        'jenis_operasi',
        'alasan',
        'status',
        'approved_by',
        'approved_at',
        'id_penanggung_jawab',
        'created_at',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserRh::class, 'id_user');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(UserRh::class, 'approved_by');
    }

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(UserRh::class, 'id_penanggung_jawab');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function approve(int $approvedBy): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    public function reject(int $approvedBy): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }
}
