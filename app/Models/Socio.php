<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Socio extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'gimnasio_id',
        'plan_id',
        'nombre',
        'email',
        'telefono',
        'estado',
        'fecha_nacimiento',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function gimnasio(): BelongsTo
    {
        return $this->belongsTo(Gimnasio::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function ultimoPago(): HasOne
    {
        return $this->hasOne(Pago::class)->latestOfMany('fecha_pago');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    public function rutinas(): BelongsToMany
    {
        return $this->belongsToMany(Rutina::class, 'rutina_socio')->withTimestamps();
    }

    public function chatIa(): HasMany
    {
        return $this->hasMany(ChatIa::class);
    }
}
