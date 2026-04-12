<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RutinaEjercicio extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'rutina_id',
        'nombre',
        'grupo_muscular',
        'series',
        'repeticiones',
        'descanso_segundos',
        'notas',
        'orden',
    ];

    public function rutina(): BelongsTo
    {
        return $this->belongsTo(Rutina::class);
    }
}
