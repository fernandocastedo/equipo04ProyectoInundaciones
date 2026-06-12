<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DanoMaterial extends Model
{
    use HasFactory;

    protected $table = 'danos_materiales';

    protected $fillable = [
        'inundacion_id',
        'tipo',
        'descripcion',
        'latitud',
        'longitud',
        'estado',
        'registrado_por',
    ];

    /**
     * Obtiene la inundación asociada al daño.
     */
    public function inundacion()
    {
        return $this->belongsTo(Inundacion::class);
    }

    /**
     * Obtiene el usuario/autoridad que registró este daño.
     */
    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por', 'carnet');
    }
}
