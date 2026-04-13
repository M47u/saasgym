<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'gimnasio_id',
        'name',
        'email',
        'password',
        'rol',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo'   => 'boolean',
        ];
    }

    public function gimnasio(): BelongsTo
    {
        return $this->belongsTo(Gimnasio::class);
    }

    public function rutinas(): HasMany
    {
        return $this->hasMany(Rutina::class, 'entrenador_id');
    }

    // ─── Role helpers ───────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->rol === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function isEntrenador(): bool
    {
        return $this->rol === 'entrenador';
    }

    /** True for any user that belongs to a gimnasio (admin or entrenador). */
    public function isGimnasioUser(): bool
    {
        return $this->gimnasio_id !== null;
    }
}
