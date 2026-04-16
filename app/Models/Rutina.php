<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rutina extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'gimnasio_id',
        'entrenador_id',
        'nombre',
        'descripcion',
        'activa',
    ];

    protected $casts = [
        'activa'       => 'boolean',
    ];

    public function gimnasio(): BelongsTo
    {
        return $this->belongsTo(Gimnasio::class);
    }

    public function entrenador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entrenador_id');
    }

    public function ejercicios(): HasMany
    {
        return $this->hasMany(RutinaEjercicio::class)->orderBy('orden');
    }
}
