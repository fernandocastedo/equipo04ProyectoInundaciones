<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inundacion;
use App\Models\ClimaCache;
use App\Models\Reporte;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class UpdateInundacionesStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inundaciones:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el estado y expiración de inundaciones activas según el clima (Open-Meteo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando validación de inundaciones...');

        // Solo procesar inundaciones activas y que están próximas a expirar o ya expiradas
        // Para asegurar que el scheduler solo consume API cuando es necesario, el user dijo:
        // "no quisiera que se ejecute cada minuto sino cuando una inundación esté por expirar"
        // Tomaremos "por expirar" como expira_at <= now() + 2 hours
        $inundaciones = Inundacion::where('estado', 'activa')
            ->where('expira_at', '<=', now()->addHours(2))
            ->get();

        if ($inundaciones->isEmpty()) {
            $this->info('No hay inundaciones próximas a expirar.');
            return;
        }

        foreach ($inundaciones as $inundacion) {
            // Consultar Open-Meteo para las coordenadas exactas de la inundación
            $precipitacion = $this->fetchPrecipitation($inundacion->latitud, $inundacion->longitud);

            $this->info("Inundacion ID {$inundacion->id}: Lluvia actual = {$precipitacion}mm/h");

            if ($precipitacion > 0) {
                // Hay lluvia, extender el tiempo
                if ($precipitacion < 2) {
                    $inundacion->expira_at = Carbon::parse($inundacion->expira_at)->addMinutes(30);
                } elseif ($precipitacion <= 10) {
                    $inundacion->expira_at = Carbon::parse($inundacion->expira_at)->addHours(1);
                } elseif ($precipitacion <= 30) {
                    $inundacion->expira_at = Carbon::parse($inundacion->expira_at)->addHours(3);
                } else {
                    $inundacion->expira_at = Carbon::parse($inundacion->expira_at)->addHours(6);
                }
                
                $inundacion->save();
                $this->info(" -> Extendida hasta {$inundacion->expira_at}");
            } else {
                // No llueve (0 mm/h). Verificar si ya pasó el expira_at
                if (now()->greaterThan($inundacion->expira_at)) {
                    // Verificar regla de "No hay nuevos reportes aprobados en la última hora"
                    $recentReports = Reporte::where('inundacion_id', $inundacion->id)
                        ->where('estado_validacion', 'aprobada')
                        ->where('updated_at', '>=', now()->subHour())
                        ->exists();

                    if (!$recentReports) {
                        // Cumple las 3 reglas de finalización (Vaciado)
                        $inundacion->estado = 'finalizada';
                        $inundacion->save();
                        $this->info(" -> Inundación FINALIZADA.");
                    } else {
                        $this->info(" -> No llueve y expiró, pero hay reportes recientes. No finaliza.");
                    }
                } else {
                    $this->info(" -> No llueve, pero aún queda tiempo de drenaje (Expira: {$inundacion->expira_at}).");
                }
            }
        }

        $this->info('Validación completada.');
    }

    private function fetchPrecipitation($lat, $lng)
    {
        try {
            $response = Http::timeout(5)->get("https://api.open-meteo.com/v1/forecast", [
                'latitude' => $lat,
                'longitude' => $lng,
                'current' => 'precipitation',
                'timezone' => 'auto'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['current']['precipitation'] ?? 0;
            }
        } catch (\Exception $e) {
            $this->error("Error API Open-Meteo: " . $e->getMessage());
        }
        return 0;
    }
}
