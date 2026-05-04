<?php

namespace App\Http\Requests\Api;

use App\Models\User;
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
            'latitud' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitud' => ['sometimes', 'numeric', 'between:-180,180'],
            'provincia' => ['sometimes', 'string', 'max:255'],
            'municipio' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'intensidad_actual' => ['sometimes', 'string', 'in:baja,media,alta'],
        ];

        if ($this->user()?->isAuthority()) {
            $baseRules['estado'] = ['sometimes', 'string', 'in:activa,finalizada,falso_reporte'];
        }

        return $baseRules;
    }
}
