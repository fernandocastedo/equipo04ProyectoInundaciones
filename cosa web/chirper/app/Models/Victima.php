<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modelo Victima
 *
 * Representa a una persona afectada por una inundación.
 * Pertenece a una sola inundación (y por transitividad a un municipio/provincia).
 * El campo `carnet` es el documento de identidad de la víctima (no FK a users).
 * `registrado_por` referencia al carnet del usuario autoridad que creó el registro.
 */
class Victima extends Model
{
    use HasFactory;

    protected $table = 'victimas';

    // ── Constantes de estado ──────────────────────────────────────────────
    public const ESTADO_PERDIDO    = 'perdido';
    public const ESTADO_ENCONTRADO = 'encontrado';
    public const ESTADO_HERIDO     = 'herido';
    public const ESTADO_FALLECIDO  = 'fallecido';

    /** Lista de estados válidos para validaciones y vistas. */
    public const ESTADOS = [
        self::ESTADO_PERDIDO,
        self::ESTADO_ENCONTRADO,
        self::ESTADO_HERIDO,
        self::ESTADO_FALLECIDO,
    ];

    /** Etiquetas legibles para cada estado. */
    public const ESTADO_LABELS = [
        self::ESTADO_PERDIDO    => 'Perdido',
        self::ESTADO_ENCONTRADO => 'Encontrado',
        self::ESTADO_HERIDO     => 'Herido',
        self::ESTADO_FALLECIDO  => 'Fallecido',
    ];

    protected $fillable = [
        'inundacion_id',
        'carnet',
        'nombre_completo',
        'fecha_nacimiento',
        'estado',
        'foto_path',
        'descripcion',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    // ── Relaciones ────────────────────────────────────────────────────────

    /**
     * Inundación a la que pertenece esta víctima.
     */
    public function inundacion(): BelongsTo
    {
        return $this->belongsTo(Inundacion::class, 'inundacion_id');
    }

    /**
     * Usuario autoridad que registró la víctima.
     */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por', 'carnet');
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    /**
     * Filtra víctimas por estado.
     */
    public function scopeDeEstado(Builder $query, string $estado): Builder
    {
        return $query->where('estado', $estado);
    }

    /**
     * Filtra víctimas por nombre (búsqueda parcial, case-insensitive).
     */
    public function scopeBuscarNombre(Builder $query, string $nombre): Builder
    {
        return $query->whereRaw('LOWER(nombre_completo) LIKE ?', ['%' . mb_strtolower($nombre) . '%']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * Devuelve la etiqueta legible del estado actual.
     */
    public function estadoLabel(): string
    {
        return self::ESTADO_LABELS[$this->estado] ?? ucfirst($this->estado);
    }

    /**
     * Clases CSS Tailwind para el badge del estado.
     */
    public function estadoBadgeClass(): string
    {
        return match ($this->estado) {
            self::ESTADO_PERDIDO    => 'bg-yellow-100 text-yellow-800',
            self::ESTADO_ENCONTRADO => 'bg-green-100 text-green-800',
            self::ESTADO_HERIDO     => 'bg-orange-100 text-orange-800',
            self::ESTADO_FALLECIDO  => 'bg-red-100 text-red-800',
            default                 => 'bg-gray-100 text-gray-700',
        };
    }
}
