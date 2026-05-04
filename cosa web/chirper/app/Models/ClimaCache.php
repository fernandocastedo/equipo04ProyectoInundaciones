<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClimaCache extends Model
{
    use HasFactory;

    protected $table = 'clima_cache';
    public $timestamps = false;

    protected $fillable = [
        'municipio_id',
        'precipitacion_mm',
        'last_check',
    ];

    protected $casts = [
        'last_check' => 'datetime',
    ];
}
