<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Victima;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar la creación de una Víctima.
 * Solo las autoridades pueden crear víctimas; la autorización
 * se gestiona en el controlador mediante middleware.
 */
class StoreVictimaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización real se hace a nivel de ruta con EnsureApiAuthority.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'inundacion_id'    => ['required', 'integer', 'exists:inundaciones,id'],
            'carnet'           => ['nullable', 'string', 'max:20'],
            'nombre_completo'  => ['required', 'string', 'max:255'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'estado'           => ['required', 'string', 'in:' . implode(',', Victima::ESTADOS)],
            'foto'             => ['nullable', 'image', 'max:4096', 'mimes:jpg,jpeg,png,webp'],
            'descripcion'      => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'inundacion_id.required' => 'Debe seleccionar una inundación.',
            'inundacion_id.exists'   => 'La inundación seleccionada no existe.',
            'nombre_completo.required' => 'El nombre completo es obligatorio.',
            'estado.required'        => 'Debe indicar el estado de la víctima.',
            'estado.in'              => 'El estado debe ser: perdido, encontrado, herido o fallecido.',
            'foto.image'             => 'El archivo debe ser una imagen.',
            'foto.max'               => 'La imagen no puede superar los 4 MB.',
            'foto.mimes'             => 'La imagen debe ser JPG, PNG o WebP.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
        ];
    }
}
