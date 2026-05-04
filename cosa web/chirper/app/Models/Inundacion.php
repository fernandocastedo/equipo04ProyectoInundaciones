<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inundacion extends Model
{
    use HasFactory;

    protected $table = 'inundaciones';

    protected $fillable = [
        'validador_id',
        'latitud',
        'longitud',
        'address',
        'description',
        'intensidad_actual',
        'estado',
        'municipio_id',
        'puntos_quorum',
        'expira_at',
    ];

    protected $casts = [
        'latitud' => 'decimal:7',
        'longitud' => 'decimal:7',
        'expira_at' => 'datetime',
    ];

    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validador_id', 'carnet');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class, 'inundacion_id');
    }
}
