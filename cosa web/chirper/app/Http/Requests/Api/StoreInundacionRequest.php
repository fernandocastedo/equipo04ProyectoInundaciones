<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreInundacionRequest extends FormRequest
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
        return [
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
            'provincia' => ['required', 'string', 'max:255'],
            'municipio' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'intensidad_actual' => ['required', 'string', 'in:baja,media,alta'],
        ];
    }
}
