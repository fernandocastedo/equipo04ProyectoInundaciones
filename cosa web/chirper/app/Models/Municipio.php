<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipio extends Model
{
    use HasFactory;

    protected $table = 'municipios';

    protected $fillable = [
        'provincia_id',
        'nombre',
    ];

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    public function inundaciones(): HasMany
    {
        return $this->hasMany(Inundacion::class, 'municipio_id');
    }

    public function centrosAsistencia(): HasMany
    {
        return $this->hasMany(CentroAsistencia::class, 'municipio_id');
    }
}
