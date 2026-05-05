<?php

namespace App\Http\Requests\Api;

use App\Models\Inundacion;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInundacionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->all() !== []) {
            return;
        }

        $decoded = json_decode((string) $this->getContent(), true);

        if (is_array($decoded)) {
            $this->merge($decoded);
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $baseRules = [
            'latitud'   => ['sometimes', 'numeric', 'between:-90,90'],
            'longitud'  => ['sometimes', 'numeric', 'between:-180,180'],
            'provincia' => ['sometimes', 'string', 'max:255'],
            'municipio' => ['sometimes', 'string', 'max:255'],
            'address'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            // intensidad_actual eliminado: se calcula dinámicamente.
        ];

        if ($this->user()?->isAuthority()) {
            // Estados normalizados según spec (Opción 2):
            //   'activa'    → sigue visible en el mapa (con validación de quórum)
            //   'terminada' → pasa al historial, no aparece en mapa activo
            //   'falsa'     → oculta del mapa para todos los ciudadanos
            $baseRules['estado'] = [
                'sometimes',
                'string',
                'in:' . implode(',', [
                    Inundacion::ESTADO_ACTIVA,
                    Inundacion::ESTADO_TERMINADA,
                    Inundacion::ESTADO_FALSA,
                ]),
            ];
        }

        return $baseRules;
    }
}
