<?php

namespace App\Console\Commands;

use App\Models\Feature;
use Illuminate\Console\Command;

class VersionRelease extends Command
{
    protected $signature = 'version:release
        {descripcion : Descripcion del cambio a registrar}
        {--prod : Marcar como liberado a produccion}';

    protected $description = 'Incrementa la version del sistema y registra el cambio en la tabla features';

    public function handle(): int
    {
        $descripcion = $this->argument('descripcion');

        // Obtener version actual
        $actual = config('version.current', '1.0.0');
        [$major, $minor, $patch] = array_map('intval', explode('.', $actual));

        // Incrementar version segun reglas
        $patch++;
        if ($patch > 99) {
            $patch = 0;
            $minor++;
        }
        if ($minor > 99) {
            $minor = 0;
            $major++;
        }

        $nueva = sprintf('%d.%d.%d', $major, $minor, $patch);

        // Actualizar config/version.php
        $configPath = config_path('version.php');
        $contenido = file_get_contents($configPath);
        $contenido = preg_replace(
            "/('current'\s*=>\s*')[^']+('),/",
            "'current' => '{$nueva}',",
            $contenido
        );
        file_put_contents($configPath, $contenido);

        // Actualizar .env si existe APP_VERSION
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            if (preg_match('/^APP_VERSION=/m', $env)) {
                $env = preg_replace('/^APP_VERSION=.*/m', "APP_VERSION={$nueva}", $env);
            } else {
                $env .= "\nAPP_VERSION={$nueva}\n";
            }
            file_put_contents($envPath, $env);
        }

        // Registrar en features
        Feature::create([
            'version'     => $nueva,
            'descripcion' => $descripcion,
            'activo'      => true,
            'produccion'  => (bool) $this->option('prod'),
        ]);

        $this->info("Version actualizada: {$actual} -> {$nueva}");
        $this->info("Cambio registrado en features: {$descripcion}");

        if ($this->option('prod')) {
            $this->info('Marcado como liberado a produccion.');
        }

        return self::SUCCESS;
    }
}
