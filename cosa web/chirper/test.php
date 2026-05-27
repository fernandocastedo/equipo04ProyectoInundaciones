<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $html = view('livewire.reports-index', ['role' => 'authority', 'misReportes' => [], 'inundacionesActivas' => [], 'reportesPendientes' => [], 'reportesRechazados' => [], 'inundacionesTerminadas' => [], 'meta' => []])->render();
    file_put_contents('test.html', $html);
    echo "Saved blade HTML to test.html\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
