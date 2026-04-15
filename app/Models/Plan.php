<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'planes';

    protected $fillable = [
        'gimnasio_id',
        'nombre',
        'descripcion',
        'precio_efectivo',
        'precio_digital',
        'activo',
    ];

    protected $casts = [
        'precio_efectivo' => 'decimal:2',
        'precio_digital'  => 'decimal:2',
        'activo'          => 'boolean',
    ];

    public function gimnasio(): BelongsTo
    {
        return $this->belongsTo(Gimnasio::class);
    }

    public function socios(): HasMany
    {
        return $this->hasMany(Socio::class);
    }
}
