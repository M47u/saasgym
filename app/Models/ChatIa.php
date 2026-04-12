<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatIa extends Model
{
    use SoftDeletes;
    protected $table = 'chat_ia';

    protected $fillable = [
        'gimnasio_id',
        'socio_id',
        'mensaje_usuario',
        'respuesta_ia',
        'modelo_ia',
        'tokens_usados',
    ];

    public function gimnasio(): BelongsTo
    {
        return $this->belongsTo(Gimnasio::class);
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }
}
