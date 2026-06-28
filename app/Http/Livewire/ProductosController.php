<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Categorias;
use App\Models\Familias;
use App\Models\Medidas;
use App\Models\Productos;
use App\Models\Descuentos;
use App\Models\Empresa;
use App\Models\Empresas;
use App\Models\imagenes;
use App\Models\Inventarios;
use App\Models\Kardex;
use App\Models\Sucursales;
use App\Models\UnidadMedida;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use DB;
use Exception;
use Illuminate\Validation\Rule;

class ProductosController extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $search, $selected_id, $pageTitle, $componentName, $codebar1, $codebar2, $codebar3, $codealternativo, $nombreProducto, $medida, $categoria, $familia, $proveedor1, $proveedor2, $proveedor3, $activo, $exento, $caja, $fraccionario, $imagenes, $inicio, $fin, $descuento, $medidaMH, $medidasMH, $contenedor, $maximo, $minimo;

    private $pagination = 10;
    //public $existenciaGolbal = 0;

    public function mount()
    {
        $this->pageTitle = 'Nuevo';
        $this->componentName = 'Producto';
        $this->categoria = 'Elegir';
        $this->medida = 'Elegir';
        $this->familia = 'Elegir';
        $this->medidasMH = UnidadMedida::all();

        
    }

    public function paginationView()
    {
        return 'vendor.livewire.bootstrap';
    }

    public function render()
    {
        

        return view('livewire.productos.productos', [
            'medidas' => Medidas::orderBy('unidad', 'ASC')->get(), 
            'categorias' => Categorias::orderBy('categoria', 'ASC')->get(), 
            'familias' => Familias::orderBy('id', 'ASC')->get()
            ])
        ->extends('layouts.theme.app')
        ->section('content');
    }

    public function Store()
    {

        $rules = [
            'nombreProducto' => [
                'required',
                'min:3',
                Rule::unique('productos')
            ->where(function ($query) {
                return $query->whereNull('deleted_at');
            })
            ],
            'medida' => 'not_in:Elegir',
            'categoria' => 'not_in:Elegir',
            'familia' => 'not_in:Elegir',
            'medidaMH' => 'required|not_in:Elegir',
        ];
        $messages = [
            'nombreProducto.required' => 'Nombre del producto requerido',
            'nombreProducto.unique' => 'Ya existe el nombre del Producto',
            'nombreProducto.min' => 'El nombre del producto tiene que tener al menos 3 caracteres',

            'medida.not_in' => 'Elige un nombre de medida diferente de Elegir',
            'categoria.not_in' => 'Elige un nombre de Categoria diferente de Elegir',
            'familia.not_in' => 'Elige una familia diferente de Elegir',
            'medidaMH.required' => 'La unidad de medida MH es requerida',
            'medidaMH.not_in' => 'Elige una unidad de medida MH diferente de Elegir',
            //'codebar1.numeric' => 'El código de barras debe ser numérico.',
            //'codebar2.numeric' => 'El código de barras debe ser numérico.',
            //'codeProduto.numeric' => 'El código de barras debe ser numérico.',
            //'codealternativo.numeric' => 'El código de barras debe ser numérico.',
            //'descuento.numeric' => 'El dato del descuento tiene que ser numérico.'
        ];

        $this->validate($rules, $messages);

        DB::beginTransaction();

        try
        {
            $prod = Productos::create([
                'codebar1' => $this->codebar1,
                'codebar2' => $this->codebar2,
                'codebar3' => $this->codebar3,
                'codealternativo' => $this->codealternativo,
                'nombreProducto' => $this->nombreProducto,
                'categoria' => $this->categoria,
                'familia' => $this->familia,
                'medida' => $this->medida,
                'proveedor1' => $this->proveedor1,
                'proveedor2' => $this->proveedor2,
                'proveedor3' => $this->proveedor3,
                'activo' => $this->activo,
                'exento' => $this->exento,
                'caja' => $this->caja,
                'fraccionario' => $this->fraccionario,
                'medidamh' => $this->medidaMH,
                'contenedor' => $this->contenedor,
                'maximo' => $this->maximo,
                'minimo' => $this->minimo
            ]);

            if($prod)
            {
                //if( !empty($this->descuento))
                //{
                    //$descuen = Descuentos::create([
                    //    'producto' => $prod->id,
                    //    'inicio' => $this->inicio,
                    //    'fin' => $this->fin,
                    //    'descuento' => $this->descuento
                    //]);
                //}
                $sucursales = Sucursales::all();
                foreach ($sucursales as $sucursal)
                {
                    $empresaId = $sucursal->empresa ?? $sucursal->Rempresa?->id;

                    if (empty($empresaId)) {
                        throw new Exception("La sucursal '{$sucursal->nombre}' no tiene una empresa asignada.");
                    }

                    $inventario = Inventarios::create([
                        'producto' => $prod->id,
                        'empresa' => $empresaId,
                        'sucursal' => $sucursal->id,
                        'existencia'=> 0.00
                    ]);

                    $fechaActual = date('Y-m-d'); // Obtiene la fecha actual en formato 'YYYY-mm-dd'
                    $horaActual = date('H:i:s');

                    $kardex = Kardex::create([
                        'producto' => $prod->id,
                        'inventario' => $inventario->id,
                        'descripcion' => 'Ingreso de Nuevo Producto',
                        'fecha' => $fechaActual ,
                        'hora' => $horaActual,
                        'ingresoCantidad' => 0.00,
                        'ingresoValor' => 0.00,
                        'egresoCantidad' => 0.00,
                        'egresoValor' => 0.00,
                        'saldoCantidad' => 0.00,
                        'saldoValor' => 0.00
                    ]);
                }

            }
            DB::commit();

            if (!empty($this->imagenes))
            {
                foreach ($this->imagenes as $img)
                {
                    $customFileName = uniqid() . '_.' . $img->extension();

                    // Cargar la imagen y redimensionarla
                    $image = Image::make($img)->resize(800, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })->encode();

                    // Guardar la imagen en el sistema de archivos
                    Storage::put('public/productos/' . $customFileName, $image);

                    // Crear el registro de galería en la base de datos
                    imagenes::create([
                            'producto' => $this->selected_id,
                            'imagen' => $customFileName
                    ]);
                }
            }

            $this->emit('item-added', 'Producto registrado con exito');
            return redirect()->route('editarProduct', ['id' => $prod->id]);
        }
        catch(Exception $e)
        {
            DB::rollback();
            $this->emit('sale-error', $e->getMessage());
        }
    }

    public function resetUI()
    {
        $this->codebar1 = '';
        $this->codebar2 = '';
        $this->codebar3 = '';
        $this->codealternativo = '';
        $this->nombreProducto = '';
        $this->medida = 'Elegir';
        $this->categoria = 'Elegir';
        $this->familia = 'Elegir';
        $this->activo = '';
        $this->exento = '';
        $this->caja = '';
        $this->fraccionario = '';
        $this->inicio = '';
        $this->fin = '';
        $this->descuento = '';
        $this->imagenes = null;
        $this->search = '';
        $this->selected_id = 0;
        $this->medidaMH = 'Elegir';
        $this->contenedor = '';
        $this->maximo = '';
        $this->minimo = '';
        $this->resetValidation();
        $this->resetPage();
    }

    ///simulador del symlik o enlace simbolico para el storage/////
    public function renderImagen($filename)
    {
        $path = 'public/productos/' . $filename;
        if (!Storage::exists($path)) {
            abort(404);
        }
        return response()->file(storage_path("app/{$path}"));
    }

}
