<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gimnasio extends Model
{
    use HasFactory;
    protected $fillable = [
        'nombre',
        'email',
        'telefono',
        'direccion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'gimnasio_id');
    }

    public function socios(): HasMany
    {
        return $this->hasMany(Socio::class, 'gimnasio_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'gimnasio_id');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'gimnasio_id');
    }

    public function rutinas(): HasMany
    {
        return $this->hasMany(Rutina::class, 'gimnasio_id');
    }
}
