<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialUbicacionVehiculo extends Model
{
    protected $table = 'historial_ubicacion_vehiculos';

    protected $fillable = [
        'vehiculo_id',
        'latitud',
        'longitud',
        'registrado_en',
    ];

    protected $casts = [
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'registrado_en' => 'datetime',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class);
    }
}
