<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InundacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'latitud' => $this->latitud,
            'longitud' => $this->longitud,
            'address' => $this->reportes->first()?->address,
            'description' => $this->reportes->first()?->description,
            'intensidad_actual' => $this->intensidad_actual,
            'estado' => $this->estado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'citizen' => new UserResource($this->whenLoaded('citizen')),
            'responses' => AuthorityResponseResource::collection($this->whenLoaded('responses')),
        ];
    }
}
