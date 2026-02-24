<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IdentityMaster extends Model
{
    use HasFactory;

    protected $table = 'identity_master';

    protected $primaryKey = 'id';

    protected $fillable = [
        'citizen_id',
        'first_name',
        'last_name',
        'dob',
        'sex',
        'nationality',
        'image_path',
        'created_at',
        'updated_at',
        'active_version_id',
    ];

    protected $casts = [
        'dob' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(IdentityVersion::class, 'identity_id');
    }

    public function activeVersion(): HasOne
    {
        return $this->hasOne(IdentityVersion::class, 'id', 'active_version_id');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
