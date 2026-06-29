<?php

namespace App\Http\Livewire;

use \Log;
use App\Models\ActividadEconomica;
use App\Models\AmbienteDestino;
use App\Models\Departamentos;
use App\Models\Distritos;
use App\Models\Medidas;
use App\Models\ModeloFacturacion;
use App\Models\precompra;
use App\Models\precompraDetalle;
use App\Models\Productos;
use App\Models\Proveedores;
use App\Models\TipoContigencia;
use App\Models\TipoDocumento;
use App\Models\TipoTransmision;
use App\Models\UnidadMedida;
use Exception;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class PrecompraController extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $pageTitle, $componentName, $selected_id, $archivo, $search, $tipo;
    private $pagination = 7;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Recepción de JSON de Compras';
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        if (strlen($this->search) > 0) {
            $data = precompra::with('Proveedores')
            ->withCount('detalles')
            ->where(function ($query) {
                $query->where('numeroControl', 'like', '%' . $this->search . '%')
                      ->orWhere('codigoGeneracion', 'like', '%' . $this->search . '%')
                      ->orWhereHas('Proveedores', function ($query) {
                          $query->where('nombre', 'like', '%' . $this->search . '%');
                      });
            })
            ->orderBy('fecEmi', 'desc')
            ->paginate($this->pagination);
        } else {
            $data = precompra::with('Proveedores')->withCount('detalles')->orderBy('fecEmi', 'desc')->paginate($this->pagination);
        }

        return view('livewire.precompra.precompra', ['data' => $data])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function Store()
    {
        $rules = [
            'archivo' => 'required|mimes:json|max:1024', // 1MB Max
            'tipo' => 'required'
        ];

        $messages = [
            'archivo.required' => 'Archivo JSON requerido',
            'archivo.mimes' => 'El archivo debe ser un JSON.',
            'archivo.max' => 'El tamaño máximo del archivo es 1MB.',
            'tipo.required' => 'Tipo de Json Requerido, seleccione una opcion diferente de Elegir',
        ];

        $this->validate($rules, $messages);

        try {
            $estado = $this->tipo == 'Gasto' ? 'Procesado' : 'Ingresado';

            $jsonContent = file_get_contents($this->archivo->getRealPath());
            //$data = json_decode($jsonContent, true);

            $jsonContent = file_get_contents($this->archivo->getRealPath());
            $jsonContent = mb_convert_encoding($jsonContent, 'UTF-8', 'UTF-8');
            $data = json_decode($jsonContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->emit('item-error', 'Error al decodificar JSON: ' . json_last_error_msg());

            }

            // Proceso de identificación
            if (!isset($data['identificacion']) || !isset($data['emisor']) || !isset($data['cuerpoDocumento'])) {
                $this->emit('item-error', 'El JSON no tiene la estructura esperada.');
                return;
            }



            $identificacion = $data['identificacion'];
            $sello = isset($data['responseMH']) ? ($data['responseMH']['selloRecibido'] ?? null) : (isset($data['respuestaHacienda']) ? ($data['respuestaHacienda']['selloRecibido'] ?? null) : null);

            // Verificar si ya existe un registro con el mismo codigoGeneracion
            $bus = precompra::where('codigoGeneracion', $identificacion['codigoGeneracion'])->first();

            if (!$bus) {
                $ambiente = AmbienteDestino::where('codigo', $identificacion['ambiente'])->first();
                $tipo = TipoDocumento::where('codigo', $identificacion['tipoDte'])->first();
                $tipoModelo = ModeloFacturacion::where('codigo', $identificacion['tipoModelo'])->first();
                $tipoOperacion = TipoTransmision::where('codigo', $identificacion['tipoOperacion'])->first();

                // Manejo del campo tipoContingencia
                $contingencia = TipoContigencia::where('codigo', $identificacion['tipoContingencia'] ?? null)->value('id') ?? 6;

                // Proceso para el emisor
                $emisor = $data['emisor'];
                $nrc = $emisor['nrc'];
                $nit = $emisor['nit'];
                $telefono = $emisor['telefono'];

                // Obtener el distrito y validar
                $distrito = Distritos::select('departamentos.id as departamento_id', 'departamentos.departamento as departamento_nombre', 'municipios.id as municipio_id', 'municipios.municipio as municipio_nombre', 'distritos.id as distrito_id', 'distritos.distrito as distrito_nombre')
                    ->where('distritos.codigo', $emisor['direccion']['municipio'])
                    ->join('municipios', 'distritos.municipio', '=', 'municipios.id')
                    ->join('departamentos', 'municipios.departamento', '=', 'departamentos.id')
                    ->where('departamentos.codigo', $emisor['direccion']['departamento'])
                    ->first();

                $codActividad = ltrim($emisor['codActividad'], '0');
                $acti = ActividadEconomica::where('codigo', $codActividad)->first();

                // Buscar el proveedor por NRC o NIT
                $prov = Proveedores::where('registro', $nrc)->orWhere('nit', $nit)->first();

                // Si no existe, creamos un nuevo proveedor
                if (!$prov) {
                    $prov = Proveedores::create([
                        'nombre' => $emisor['nombre'],
                        'tipoPersona' => 2,
                        'direccion' => $emisor['direccion']['complemento'],
                        'telefono' => $telefono,
                        'correo' => $emisor['correo'],
                        'registro' => $nrc,
                        'nit' => $nit,
                        'departamento' => $distrito->departamento_id,
                        'municipio' => $distrito->municipio_id,
                        'distrito' => $distrito->distrito_id,
                        'actividad' => $acti->id,
                        'desActividad' => $acti->valor,
                        'giro' => $emisor['descActividad'],
                    ]);
                }

                // Proceso de resumen de tributos
                $tributos = $data['resumen']['tributos'];
                $totalIva = $tributos[0]['valor'] ?? 0;

                // Crear el registro de precompra
                $agre = precompra::create([
                    'tipo' => $this->tipo,
                    'version' => $identificacion['version'],
                    'ambiente' => $ambiente->id ?? null,
                    'tipoDte' => $tipo->id ?? null,
                    'numeroControl' => $identificacion['numeroControl'],
                    'codigoGeneracion' => $identificacion['codigoGeneracion'],
                    'tipoModelo' => $tipoModelo->id ?? null,
                    'tipoOperacion' => $tipoOperacion->id ?? null,
                    'tipoContingencia' => $contingencia,
                    'fecEmi' => $identificacion['fecEmi'],
                    'horEmi' => $identificacion['horEmi'],
                    'tipoMoneda' => $identificacion['tipoMoneda'],
                    'documentoRelacionado' => isset($data['documentoRelacionado']) ? json_encode($data['documentoRelacionado']) : null,
                    'emisor' => $prov->id,
                    'receptor' => 1,
                    'sello' => $sello,
                    'totalNoSuj' => $data['resumen']['totalNoSuj'] ?? 0,
                    'totalExenta' => $data['resumen']['totalExenta'] ?? 0,
                    'totalGravada' => $data['resumen']['totalGravada'] ?? 0,
                    'subTotalVentas' => $data['resumen']['subTotalVentas'] ?? 0,
                    'descuNoSuj' => $data['resumen']['descuNoSuj'] ?? 0,
                    'descuExenta' => $data['resumen']['descuExenta'] ?? 0,
                    'descuGravada' => $data['resumen']['descuGravada'] ?? 0,
                    'porcentajeDescuento' => $data['resumen']['porcentajeDescuento'] ?? 0,
                    'totalDescu' => $data['resumen']['totalDescu'] ?? 0,
                    'totalIva' => $totalIva,
                    'subTotal' => $data['resumen']['subTotal'] ?? 0,
                    'ivaPerci1' => $data['resumen']['ivaPerci1'] ?? 0,
                    'ivaRete1' => $data['resumen']['ivaRete1'] ?? 0,
                    'reteRenta' => $data['resumen']['reteRenta'] ?? 0,
                    'montoTotalOperacion' => $data['resumen']['montoTotalOperacion'] ?? 0,
                    'totalNoGravado' => $data['resumen']['totalNoGravado'] ?? 0,
                    'totalPagar' => $data['resumen']['totalPagar'] ?? 0,
                    'estado' => $estado,
                    'jsonDte' => $jsonContent,
                ]);

                ////// Detalle del cuerpo del documento
                foreach ($data['cuerpoDocumento'] as $row) {
                    $prod = Productos::where('codebar1', $row['codigo'])
                        ->orWhere('codebar2', $row['codigo'])
                        ->orWhere('codebar3', $row['codigo'])
                        ->orWhere('codealternativo', $row['codigo'])
                        ->first();

                    if ($prod) {
                        $producto = $prod->id;
                        $estado1 = 'Validado';
                    } else {
                        $product = Productos::first(); // Si no se encuentra, toma el primer producto disponible
                        $producto = $product ? $product->id : null;
                        $estado1 = 'Pendiente';
                    }

                    // Verificación de la unidad de medida
                    $uni = UnidadMedida::where('codigo', $row['uniMedida'])->first();
                    $med = $uni ? Medidas::where('unidad', $uni->valor)->first() : null;
                    $medidaId = $med ? $med->id : null;

                    // Crear el detalle de la precompra
                    $detalle = precompraDetalle::create([
                        'precompra' => $agre->id,
                        'producto' => $producto,
                        'medida' => $medidaId,
                        'tipoItem' => $row['tipoItem'],
                        'codigo' => $row['codigo'],
                        'descripcion' => $row['descripcion'],
                        'cantidad' => $row['cantidad'],
                        'uniMedida' => $row['uniMedida'],
                        'precioUni' => $row['precioUni'],
                        'montoDescu' => $row['montoDescu'],
                        'ventaNoSuj' => $row['ventaNoSuj'],
                        'ventaExenta' => $row['ventaExenta'],
                        'ventaGravada' => $row['ventaGravada'],
                        'status' => $estado1,
                    ]);
                }

                $this->emit('item-added', 'Json Procesado exitosamente');

            } else {
                $this->emit('item-error', 'Esta factura ya fue ingresada');
            }
        } catch (Exception $e) {
            // Manejo de errores
            $this->emit('item-error', 'Se produjo un error al procesar el archivo JSON. ' . $e->getMessage());
            Log::error('Error al procesar JSON: ' . ($data['identificacion']['numeroControl'] ?? 'sin-control') . ' - Mensaje: ' . $e->getMessage());
        }
    }

    public function resetUI()
    {
        $this->archivo = '';
        $this->tipo = 'Elegir';
    }
}
