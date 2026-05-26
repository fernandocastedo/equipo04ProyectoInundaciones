<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Inundacion;
use App\Services\GeoLocationService;
use Illuminate\Console\Command;

/**
 * BackfillInundacionMunicipios
 *
 * Recorre todas las inundaciones sin municipio_id asignado y resuelve el
 * municipio usando point-in-polygon sobre su centroide (latitud, longitud).
 *
 * Uso:
 *   php artisan floods:backfill-municipios
 *   php artisan floods:backfill-municipios --all   (sobrescribe aunque ya tengan municipio)
 */
final class BackfillInundacionMunicipios extends Command
{
    protected $signature = 'floods:backfill-municipios
                            {--all : Reprocesar también las inundaciones que ya tienen municipio_id}';

    protected $description = 'Asigna municipio_id a inundaciones usando point-in-polygon sobre su centroide.';

    public function handle(GeoLocationService $geo): int
    {
        $query = Inundacion::query();

        if (! $this->option('all')) {
            $query->whereNull('municipio_id');
        }

        $inundaciones = $query->get();
        $total        = $inundaciones->count();

        if ($total === 0) {
            $this->info('✔ No hay inundaciones pendientes de asignar municipio.');
            return self::SUCCESS;
        }

        $this->info("Procesando {$total} inundación(es)...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $asignadas  = 0;
        $noEncontradas = 0;

        foreach ($inundaciones as $inundacion) {
            $lat = (float) $inundacion->latitud;
            $lng = (float) $inundacion->longitud;

            if ($lat === 0.0 && $lng === 0.0) {
                $bar->advance();
                continue;
            }

            $municipio = $geo->findMunicipio($lat, $lng);

            if ($municipio === null) {
                $noEncontradas++;
                $this->newLine();
                $this->warn("  ⚠ Inundación #{$inundacion->id} ({$lat}, {$lng}): municipio no encontrado.");
            } else {
                $inundacion->municipio_id = $municipio->id;
                $inundacion->saveQuietly();
                $asignadas++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Resultado', 'Cantidad'],
            [
                ['Municipios asignados', $asignadas],
                ['No encontrados',       $noEncontradas],
                ['Total procesados',     $total],
            ]
        );

        return self::SUCCESS;
    }
}
