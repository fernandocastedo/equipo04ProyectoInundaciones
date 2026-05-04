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
        'citizen_carnet',
        'latitud',
        'longitud',
        'provincia',
        'municipio',
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

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(User::class, foreignKey: 'citizen_carnet', ownerKey: 'carnet');
    }


    public function reportes(): HasMany
    {
        return $this->hasMany(Reporte::class, 'inundacion_id');
    }
}
