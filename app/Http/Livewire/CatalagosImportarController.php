<?php

namespace App\Http\Livewire;

use App\Services\CatalogoDteImportador;
use App\Services\CatalogoDteLector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pantalla para actualizar los catalogos DTE desde el Excel oficial de Hacienda.
 *
 * Flujo: subir -> Analizar (dry-run, no escribe) -> revisar detalle -> Aplicar
 * (pide confirmacion) -> Confirmar (escribe en una transaccion).
 */
class CatalagosImportarController extends Component
{
    use WithFileUploads;

    public $archivo;
    public $pageTitle, $componentName;

    /** Ruta relativa del Excel ya subido. El archivo se relee en cada paso. */
    public $ruta;
    public $nombreArchivo;

    /** Resumen por catalogo (sin las filas, para no inflar el payload de Livewire). */
    public $resumen = [];

    /** Catalogos marcados para aplicar. */
    public $seleccion = [];

    /** Catalogo cuyo detalle se esta viendo. */
    public $verDetalle = null;
    public $detalle = [];

    /** true mientras se muestra el aviso de confirmacion antes de escribir. */
    public $confirmando = false;

    /** Conteos de lo que realmente se aplico. */
    public $aplicado = [];

    public function mount()
    {
        $this->pageTitle = 'Actualizar desde Excel de Hacienda';
        $this->componentName = 'Catalogos DTE';
    }

    public function render()
    {
        return view('livewire.catalagos.importar')
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function Analizar()
    {
        $this->validate(
            ['archivo' => 'required|file|mimes:xlsx,xls|max:20480'],
            [
                'archivo.required' => 'Seleccione el archivo de catalogos.',
                'archivo.mimes' => 'El archivo debe ser un Excel (.xlsx).',
                'archivo.max' => 'El archivo no debe pesar mas de 20 MB.',
            ]
        );

        try {
            // Si habia un archivo analizado antes, se descarta para no acumularlos.
            if ($this->ruta && Storage::exists($this->ruta)) {
                Storage::delete($this->ruta);
            }

            $this->nombreArchivo = $this->archivo->getClientOriginalName();
            // Disco explicito: sin el, Livewire guarda en su disco temporal y
            // Storage::path() (disco por defecto) no encontraria el archivo si
            // livewire.temporary_file_upload.disk apunta a otro disco.
            $this->ruta = $this->archivo->store('catalogos-dte', config('filesystems.default'));

            $catalogos = app(CatalogoDteLector::class)->leer(Storage::path($this->ruta));
            $this->resumen = app(CatalogoDteImportador::class)->comparar($catalogos);

            // Se preseleccionan los catalogos que tienen algo que hacer, incluidos
            // departamentos, municipios y distritos: el Excel trae sus codigos
            // vigentes. Nada se escribe sin el paso de confirmacion.
            $this->seleccion = collect($this->resumen)
                ->filter(fn ($c) => empty($c['error'])
                    && ($c['nuevos'] || $c['actualizar'] || $c['retirar']))
                ->pluck('codigo')
                ->all();

            $this->aplicado = [];
            $this->verDetalle = null;
            $this->detalle = [];
            $this->confirmando = false;
        } catch (\Throwable $e) {
            $this->limpiarArchivo();
            $this->fallo('No se pudo analizar el archivo', $e);
        }
    }

    public function VerDetalle($codigo)
    {
        if ($this->verDetalle === $codigo) {
            $this->verDetalle = null;
            $this->detalle = [];
            return;
        }

        try {
            $catalogos = $this->leerArchivo();

            if (!isset($catalogos[$codigo])) {
                $this->emit('item-error', 'El catalogo ' . $codigo . ' ya no esta en el archivo.');
                return;
            }

            $this->verDetalle = $codigo;
            $this->detalle = app(CatalogoDteImportador::class)->detalle($codigo, $catalogos);
        } catch (\Throwable $e) {
            $this->fallo('No se pudo mostrar el detalle', $e);
        }
    }

    /** Primer paso: valida y pide confirmacion. No escribe nada. */
    public function Aplicar()
    {
        try {
            $this->seleccion = array_values(array_intersect(
                (array) $this->seleccion,
                collect($this->resumen)->filter(fn ($c) => empty($c['error']))->pluck('codigo')->all()
            ));

            if (empty($this->seleccion)) {
                $this->emit('item-error', 'No hay catalogos seleccionados.');
                return;
            }

            if (!$this->ruta || !Storage::exists($this->ruta)) {
                $this->emit('item-error', 'El archivo analizado ya no esta disponible. Vuelva a subirlo y analizarlo.');
                return;
            }

            $this->confirmando = true;
        } catch (\Throwable $e) {
            $this->fallo('No se pudo preparar la aplicacion', $e);
        }
    }

    public function CancelarAplicar()
    {
        $this->confirmando = false;
    }

    /** Segundo paso: aplica lo seleccionado en una sola transaccion. */
    public function ConfirmarAplicar()
    {
        if (!$this->confirmando) {
            return;
        }

        $this->confirmando = false;

        if (empty($this->seleccion)) {
            $this->emit('item-error', 'No hay catalogos seleccionados.');
            return;
        }

        try {
            $catalogos = $this->leerArchivo();
            $importador = app(CatalogoDteImportador::class);
            $this->aplicado = $importador->aplicar($catalogos, $this->seleccion);

            // Se vuelve a comparar para que la pantalla refleje el estado ya aplicado.
            $this->resumen = $importador->comparar($catalogos);
            $this->verDetalle = null;
            $this->detalle = [];

            $total = collect($this->aplicado)
                ->sum(fn ($n) => ($n['nuevos'] ?? 0) + ($n['actualizados'] ?? 0) + ($n['retirados'] ?? 0));
            $this->emit('item-added', 'Catalogos actualizados: ' . $total . ' registros afectados.');
        } catch (\Throwable $e) {
            $this->fallo('No se aplico ningun cambio', $e);
        }
    }

    public function Cancelar()
    {
        try {
            $this->limpiarArchivo();
        } catch (\Throwable $e) {
            $this->emit('item-error', 'No se pudo descartar el archivo: ' . $e->getMessage());
        }

        $this->reset(['resumen', 'seleccion', 'verDetalle', 'detalle', 'aplicado', 'nombreArchivo', 'confirmando']);
    }

    private function leerArchivo(): array
    {
        if (!$this->ruta || !Storage::exists($this->ruta)) {
            throw new \RuntimeException('El archivo analizado ya no esta disponible. Vuelva a subirlo y analizarlo.');
        }

        return app(CatalogoDteLector::class)->leer(Storage::path($this->ruta));
    }

    private function fallo(string $contexto, \Throwable $e): void
    {
        Log::error('Importar catalogos DTE: ' . $contexto, ['exception' => $e]);
        $this->emit('item-error', $contexto . ': ' . $e->getMessage());
    }

    private function limpiarArchivo()
    {
        if ($this->ruta && Storage::exists($this->ruta)) {
            Storage::delete($this->ruta);
        }

        $this->ruta = null;
        $this->archivo = null;
    }
}
