<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProvinciasMunicipiosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = public_path('provincias-municipios-lista-oficial.json');
        
        if (!file_exists($jsonPath)) {
            $this->command->error("Archivo JSON no encontrado: " . $jsonPath);
            return;
        }

        $json = file_get_contents($jsonPath);
        $data = json_decode($json, true);

        if (!isset($data['provincias'])) {
            $this->command->error("El formato del JSON es inválido. No se encontró la clave 'provincias'.");
            return;
        }

        foreach ($data['provincias'] as $provData) {
            $provincia = \App\Models\Provincia::firstOrCreate([
                'nombre' => $provData['nombre']
            ]);

            foreach ($provData['municipios'] as $muniNombre) {
                \App\Models\Municipio::firstOrCreate([
                    'provincia_id' => $provincia->id,
                    'nombre' => $muniNombre
                ]);
            }
        }
        
        $this->command->info('Provincias y municipios cargados correctamente desde el JSON oficial.');
    }
}
