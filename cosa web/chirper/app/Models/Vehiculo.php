<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $fillable = [
        'placa',
        'tipo',
        'estado',
        'capacidad',
        'latitud',
        'longitud',
        'ultima_ubicacion_at',
        'encargado_carnet',
        'centro_asistencia_id',
        'en_ruta',
    ];

    protected $casts = [
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'ultima_ubicacion_at' => 'datetime',
        'en_ruta' => 'boolean',
    ];

    public function encargado()
    {
        return $this->belongsTo(User::class, 'encargado_carnet', 'carnet');
    }

    public function centroAsistencia()
    {
        return $this->belongsTo(CentroAsistencia::class, 'centro_asistencia_id', 'id_centro');
    }

    public function historialUbicaciones()
    {
        return $this->hasMany(HistorialUbicacionVehiculo::class);
    }
}
