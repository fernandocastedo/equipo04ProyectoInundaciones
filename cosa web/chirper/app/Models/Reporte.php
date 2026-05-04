<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reporte extends Model
{
    use HasFactory;

    protected $table = 'reportes';

    protected $fillable = [
        'user_uuid',
        'citizen_carnet',
        'inundacion_id',
        'lat_gps',
        'long_gps',
        'lat_reporte',
        'long_reporte',
        'intensidad_propuesta',
        'foto_path',
        'estado_validacion',
        'datos_clima_json',
    ];

    protected $casts = [
        'lat_gps' => 'decimal:7',
        'long_gps' => 'decimal:7',
        'lat_reporte' => 'decimal:7',
        'long_reporte' => 'decimal:7',
        'datos_clima_json' => 'array',
    ];

    public function inundacion(): BelongsTo
    {
        return $this->belongsTo(Inundacion::class, 'inundacion_id');
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(User::class, foreignKey: 'citizen_carnet', ownerKey: 'carnet');
    }
}
