<?php

namespace App\Http\Livewire;

use App\Models\EsquemaDte;
use App\Models\TipoDocumento;
use App\Models\dte;
use App\Services\EsquemaDteLector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class EsquemasDteController extends Component
{
    use WithFileUploads;

    public $archivo;
    public $pageTitle, $componentName;

    public $ruta;
    public $nombreArchivo;

    /** Comparacion entre lo cargado, lo guardado y lo que usa el generador. */
    public $comparacion = [];
    public $seleccion = [];
    public $aplicado = 0;

    /** Carga manual: un .json al que se le indica el tipo de documento. */
    public $manualArchivo;
    public $manualTipo = '';
    public $manualVersion = '';

    /** Version que declara el archivo, para contrastarla con la digitada. */
    public $manualVersionDetectada = null;
    public $manualNombreDetectado = null;

    public function mount()
    {
        $this->pageTitle = 'Esquemas JSON de Hacienda';
        $this->componentName = 'Catalogos DTE';
    }

    public function render()
    {
        $guardados = collect();
        $tipos = [];

        // Si todavia no se corrio la migracion de esquema_dtes, la pantalla debe
        // explicarlo, no mostrar la excepcion de tabla inexistente.
        try {
            $guardados = EsquemaDte::orderBy('tipoDte')->orderByDesc('version')->get();
            $tipos = $this->tiposDocumento();
        } catch (\Throwable $e) {
            $this->reportar($e, 'No se pudo leer los esquemas guardados. Verifique que la migracion esquema_dtes este aplicada');
        }

        return view('livewire.esquemas_dte.esquemas', [
            'guardados' => $guardados,
            'tipos' => $tipos,
        ])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    /**
     * Avisa con item-error y deja la traza en el log. Que el usuario no vea la
     * pantalla de excepcion no significa esconder el problema.
     */
    private function reportar(\Throwable $e, string $contexto): void
    {
        Log::error($contexto, [
            'componente' => static::class,
            'mensaje' => $e->getMessage(),
            'archivo' => $e->getFile() . ':' . $e->getLine(),
        ]);

        $this->emit('item-error', $contexto . ': ' . $e->getMessage());
    }

    /**
     * Opciones del select: el CAT-002 tal como esta en la base, mas los dos
     * eventos que no tienen codigo de CAT-002 pero si esquema propio.
     */
    private function tiposDocumento(): array
    {
        // El codigo se normaliza a 2 digitos ('3' -> '03'). En tipo_documentos
        // algunos estan guardados sin el cero adelante, pero la clave canonica es
        // la del CAT-002 con cero, que es la que consulta el generador.
        $tipos = TipoDocumento::where('status', 'Activo')
            ->orderBy('codigo')
            ->get(['codigo', 'valor'])
            ->mapWithKeys(function ($t) {
                $codigo = $this->normalizarTipo($t->codigo);

                return [$codigo => $codigo . ' - ' . $t->valor];
            })
            ->all();

        ksort($tipos);

        $tipos['INV'] = 'INV - Evento de Invalidacion';
        $tipos['CON'] = 'CON - Evento de Contingencia';

        return $tipos;
    }

    /**
     * Misma normalizacion que hace el generador antes de buscar la version:
     * los codigos numericos se rellenan a 2 digitos, las claves de evento no.
     */
    private function normalizarTipo(string $codigo): string
    {
        $codigo = trim($codigo);

        return ctype_digit($codigo) ? str_pad($codigo, 2, '0', STR_PAD_LEFT) : $codigo;
    }

    /** Al elegir el archivo se lee la version que declara, para precargarla. */
    public function updatedManualArchivo()
    {
        $this->manualVersionDetectada = null;
        $this->manualNombreDetectado = null;

        try {
            $contenido = file_get_contents($this->manualArchivo->getRealPath());
            $json = json_decode($contenido, true);

            if (!is_array($json)) {
                return;
            }

            $campo = $json['properties']['identificacion']['properties']['version'] ?? null;

            if (is_array($campo)) {
                $this->manualVersionDetectada = isset($campo['const'])
                    ? (int) $campo['const']
                    : (isset($campo['enum'][0]) ? (int) $campo['enum'][0] : null);
            }

            $this->manualNombreDetectado = $json['title'] ?? $json['description'] ?? null;

            if ($this->manualVersionDetectada !== null && $this->manualVersion === '') {
                $this->manualVersion = $this->manualVersionDetectada;
            }
        } catch (\Throwable $e) {
            // Si no se puede leer, se deja que el usuario digite la version.
        }
    }

    public function GuardarManual()
    {
        $this->validate(
            [
                'manualArchivo' => 'required|file|max:10240',
                'manualTipo' => 'required',
                'manualVersion' => 'required|integer|min:1|max:99',
            ],
            [
                'manualArchivo.required' => 'Seleccione el archivo .json del esquema.',
                'manualArchivo.max' => 'El archivo no debe pesar mas de 10 MB.',
                'manualTipo.required' => 'Seleccione el tipo de documento.',
                'manualVersion.required' => 'Indique la version del esquema.',
                'manualVersion.integer' => 'La version debe ser un numero.',
            ]
        );

        if (strtolower($this->manualArchivo->getClientOriginalExtension()) !== 'json') {
            $this->addError('manualArchivo', 'El archivo debe ser un .json.');
            return;
        }

        try {
            $contenido = file_get_contents($this->manualArchivo->getRealPath());

            if (!is_array(json_decode($contenido, true))) {
                $this->addError('manualArchivo', 'El archivo no es un JSON valido.');
                return;
            }

            $nombreArchivo = $this->manualArchivo->getClientOriginalName();
            $destino = 'esquemas-dte/' . $nombreArchivo;
            $version = (int) $this->manualVersion;
            $tipo = $this->normalizarTipo($this->manualTipo);

            DB::transaction(function () use ($contenido, $destino, $nombreArchivo, $version, $tipo) {
                Storage::put($destino, $contenido);

                EsquemaDte::where('tipoDte', $tipo)
                    ->where('status', 'Activo')
                    ->update(['status' => 'Desactivado']);

                EsquemaDte::withTrashed()->updateOrCreate(
                    ['tipoDte' => $tipo, 'version' => $version],
                    [
                        'nombre' => $this->manualNombreDetectado
                            ?? ($this->tiposDocumento()[$tipo] ?? $tipo),
                        'archivo' => $nombreArchivo,
                        'ruta' => $destino,
                        'hash' => hash('sha256', $contenido),
                        'status' => 'Activo',
                        'deleted_at' => null,
                    ]
                );
            });

            $aviso = '';

            if ($this->manualVersionDetectada !== null && $this->manualVersionDetectada !== $version) {
                $aviso = ' OJO: el archivo declara version ' . $this->manualVersionDetectada
                    . ' y se guardo como ' . $version . '.';
            }

            $this->reset(['manualArchivo', 'manualTipo', 'manualVersion',
                'manualVersionDetectada', 'manualNombreDetectado']);

            $this->emit('item-added', 'Esquema guardado.' . $aviso);
        } catch (\Throwable $e) {
            $this->emit('item-error', $e->getMessage());
        }
    }

    public function Analizar()
    {
        $this->validate(
            ['archivo' => 'required|file|max:20480'],
            [
                'archivo.required' => 'Seleccione el ZIP de esquemas o un archivo .json.',
                'archivo.max' => 'El archivo no debe pesar mas de 20 MB.',
            ]
        );

        $extension = strtolower($this->archivo->getClientOriginalExtension());

        if (!in_array($extension, ['zip', 'json'], true)) {
            $this->addError('archivo', 'El archivo debe ser el ZIP de esquemas o un .json.');
            return;
        }

        try {
            $this->nombreArchivo = $this->archivo->getClientOriginalName();
            $this->ruta = $this->archivo->store('esquemas-dte/subidas');

            $esquemas = app(EsquemaDteLector::class)->leer(Storage::path($this->ruta));

            $this->comparacion = array_map(fn ($e) => $this->comparar($e), $esquemas);

            $this->seleccion = collect($this->comparacion)
                ->filter(fn ($c) => $c['accion'] !== 'igual')
                ->pluck('tipoDte')
                ->all();

            $this->aplicado = 0;
        } catch (\Throwable $e) {
            $this->limpiar();
            $this->emit('item-error', $e->getMessage());
        }
    }

    public function Aplicar()
    {
        if (empty($this->seleccion)) {
            $this->emit('item-error', 'No hay esquemas seleccionados.');
            return;
        }

        try {
            $esquemas = app(EsquemaDteLector::class)->leer(Storage::path($this->ruta));
            $n = 0;

            DB::transaction(function () use ($esquemas, &$n) {
                foreach ($esquemas as $e) {
                    if (!in_array($e['tipoDte'], $this->seleccion, true)) {
                        continue;
                    }

                    $destino = 'esquemas-dte/' . $e['archivo'];
                    Storage::put($destino, $e['contenido']);

                    // Solo una version vigente por tipo; las anteriores quedan
                    // guardadas por si hay que volver atras.
                    EsquemaDte::where('tipoDte', $e['tipoDte'])
                        ->where('status', 'Activo')
                        ->update(['status' => 'Desactivado']);

                    EsquemaDte::withTrashed()->updateOrCreate(
                        ['tipoDte' => $e['tipoDte'], 'version' => $e['version']],
                        [
                            'nombre' => $e['nombre'],
                            'archivo' => $e['archivo'],
                            'ruta' => $destino,
                            'hash' => $e['hash'],
                            'status' => 'Activo',
                            'deleted_at' => null,
                        ]
                    );

                    $n++;
                }
            });

            $this->aplicado = $n;
            $esquemas = app(EsquemaDteLector::class)->leer(Storage::path($this->ruta));
            $this->comparacion = array_map(fn ($e) => $this->comparar($e), $esquemas);

            $this->emit('item-added', $n . ' esquema(s) guardado(s).');
        } catch (\Throwable $e) {
            $this->emit('item-error', $e->getMessage());
        }
    }

    /**
     * Deja vigente una version ya guardada. Es la palanca para volver atras si
     * Permite elegir explicitamente otra version sin tener que resubirla.
     */
    public function Activar($id)
    {
        try {
            $esquema = EsquemaDte::findOrFail($id);

            if ($esquema->status === 'Activo') {
                return;
            }

            DB::transaction(function () use ($esquema) {
                EsquemaDte::where('tipoDte', $esquema->tipoDte)
                    ->where('status', 'Activo')
                    ->update(['status' => 'Desactivado']);

                $esquema->status = 'Activo';
                $esquema->save();
            });

            $this->refrescarComparacion();

            $this->emit(
                'item-added',
                'El tipo ' . $esquema->tipoDte . ' queda con el esquema version ' . $esquema->version . ' vigente.'
            );
        } catch (\Throwable $e) {
            $this->emit('item-error', $e->getMessage());
        }
    }

    /**
     * Quita la vigencia sin activar otra. El generador vuelve a usar la version
     * de respaldo que trae el codigo para ese tipo de DTE.
     */
    public function Desactivar($id)
    {
        try {
            $esquema = EsquemaDte::findOrFail($id);
            $esquema->status = 'Desactivado';
            $esquema->save();

            $this->refrescarComparacion();

            $this->emit('item-added', 'Esquema desactivado para el tipo ' . $esquema->tipoDte . '.');
        } catch (\Throwable $e) {
            $this->emit('item-error', $e->getMessage());
        }
    }

    private function refrescarComparacion(): void
    {
        if (!count($this->comparacion) || !$this->ruta) {
            return;
        }

        try {
            $esquemas = app(EsquemaDteLector::class)->leer(Storage::path($this->ruta));
            $this->comparacion = array_map(fn ($e) => $this->comparar($e), $esquemas);
        } catch (\Throwable $e) {
            // La comparacion es informativa; si no se puede rehacer no importa.
        }
    }

    public function Cancelar()
    {
        try {
            $this->limpiar();
        } catch (\Throwable $e) {
            $this->reportar($e, 'No se pudo descartar el archivo');
        }

        $this->reset(['comparacion', 'seleccion', 'aplicado', 'nombreArchivo']);
    }

    private function comparar(array $e): array
    {
        $guardado = EsquemaDte::where('tipoDte', $e['tipoDte'])
            ->where('status', 'Activo')
            ->first();

        if (!$guardado) {
            $accion = 'nuevo';
        } elseif ((int) $guardado->version !== $e['version']) {
            $accion = 'cambia-version';
        } elseif ($guardado->hash !== $e['hash']) {
            $accion = 'cambia-contenido';
        } else {
            $accion = 'igual';
        }

        return [
            'tipoDte' => $e['tipoDte'],
            'nombre' => $e['nombre'],
            'archivo' => $e['archivo'],
            'versionArchivo' => $e['version'],
            'versionGuardada' => $guardado ? (int) $guardado->version : null,
            'versionSistema' => $this->versionSistema($e['tipoDte']),
            'accion' => $accion,
        ];
    }

    /**
     * Version con la que el sistema esta emitiendo HOY ese tipo de DTE: la del
     * ultimo DTE guardado (el POS fija dtes.version al crearlo). Si no coincide
     * con la del esquema, Hacienda rechazara el documento.
     */
    private function versionSistema(string $tipoDte): ?int
    {
        if (!ctype_digit($tipoDte)) {
            return null;
        }

        try {
            $version = dte::join('tipo_documentos as tdoc', 'tdoc.id', '=', 'dtes.tipoDte')
                ->whereIn('tdoc.codigo', array_unique([$tipoDte, (string) (int) $tipoDte]))
                ->orderByDesc('dtes.id')
                ->value('dtes.version');

            return $version !== null ? (int) $version : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function limpiar()
    {
        if ($this->ruta && Storage::exists($this->ruta)) {
            Storage::delete($this->ruta);
        }

        $this->ruta = null;
        $this->archivo = null;
    }
}
