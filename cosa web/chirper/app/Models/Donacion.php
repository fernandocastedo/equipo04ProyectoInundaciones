<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donacion extends Model
{
    use HasFactory;

    protected $table = 'donaciones';

    protected $fillable = [
        'centro_id',
        'donor_carnet',
        'items_description',
        'is_anonymous',
        'status',
        'usage_details',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    public function centro()
    {
        return $this->belongsTo(CentroAsistencia::class, 'centro_id', 'id_centro');
    }

    public function donor()
    {
        return $this->belongsTo(User::class, 'donor_carnet', 'carnet');
    }
}
