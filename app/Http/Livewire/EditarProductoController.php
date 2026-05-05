<?php

namespace App\Http\Livewire;

use App\Models\Categorias;
use App\Models\Descuentos;
use App\Models\Familias;
use App\Models\imagenes;
use App\Models\Medidas;
use App\Models\Parametros;
use App\Models\Precios;
use App\Models\Productos;
use App\Models\sucursal_precio;
use App\Models\Sucursales;
use App\Models\UnidadMedida;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditarProductoController extends Component
{
    public $medidas, $categorias, $familias, $selected_id, $codebar1, $codebar2, $codebar3, $codealternativo, $nombreProducto, $medida, $categoria, $familia, $proveedor1, $proveedor2, $proveedor3, $activo, $exento, $caja, $fraccionario, $imagenes, $idDescuento, $galerias, $mensajeError, $medidaMH, $medidasMH, $familiaAnte, $contenedor, $maximo, $minimo;

    ///precios///////
    public $precios,
        $costoSU = [],
        $cantidadU = [],
        $costoIVAU = [],
        $utilidadU = [],
        $precioVentaUniU = [],
        $precioVentaU = [],
        $codebarPU = [],
        $codebarP,
        $unidadP,
        $cantidadP,
        $costoS,
        $costoIVA,
        $utilidad,
        $precioVenta,
        $precioVentaUni,
        $escala = false,
        $escalaU = [],
        $unidadPU = [],
        $cantidadPU = [],
        $sucursal,
        $sucursales = [],
        $sucursalU = [];

    //descuentos/////
    public $descuentos,
        $preciosDescuento,
        $inicio,
        $fin,
        $descuento,
        $precioDes,
        $precioDesUp = [],
        $inicioU = [],
        $finU = [],
        $descuentoU = [],
        $fechaInicio,
        $fechaFin,
        $hoy,
        $familiaD,
        $valor,
        $valorU = [];
    public $sucursalesPrecios = [], $sp = [], $precios_id;

    public $activeTab = 'navs-pills-within-card-producto';

    public $search = '';
    public $page = 1;

    use WithFileUploads;

    public function mount($id)
    {
        /*$this->search = $search;
        $this->page = $page;

        session([
            'producto_seleccionado' => $id,
            'producto_search' => $search,
            'producto_pagina' => $page,
        ]);*/
        $this->cantidadP = 0;
        $this->costoS = 0;
        $this->costoIVA = 0;
        $this->utilidad = 0;
        $this->precioVentaUni = 0;
        $this->precioVenta = 0;
        $this->mensajeError = null;
        $this->escala = false;

        $this->selected_id = $id;
        $this->medidas = Medidas::orderBy('unidad', 'ASC')->get();
        $this->categorias = Categorias::orderBy('categoria', 'ASC')->get();
        $this->familias = Familias::orderBy('id', 'ASC')->get();
        $producto = Productos::find($id);
        $this->codebar1 = $producto->codebar1;
        $this->codebar2 = $producto->codebar2;
        $this->codebar3 = $producto->codebar3;
        $this->codealternativo = $producto->codealternativo;
        $this->nombreProducto = $producto->nombreProducto;
        $this->medida = $producto->medida;
        $this->categoria = $producto->categoria;
        $this->familia = $producto->familia;
        $this->proveedor1 = $producto->proveedor1;
        $this->proveedor2 = $producto->proveedor2;
        $this->proveedor3 = $producto->proveedor3;
        $this->activo = (bool) $producto->activo;
        $this->exento = (bool) $producto->exento;
        $this->caja = (bool) $producto->caja;
        $this->fraccionario = (bool) $producto->fraccionario;
        $this->medidasMH = UnidadMedida::all();
        $this->medidaMH = $producto->medidamh;
        $this->familiaAnte = $producto->familia;
        $this->contenedor = $producto->contenedor;
        $this->maximo = $producto->maximo;
        $this->minimo = $producto->minimo;

        $this->preciosDescuento = Precios::where('producto', $this->selected_id)->get();
        $this->descuentos = Descuentos::where('producto', $this->selected_id)->get();

        foreach ($this->descuentos as $des) {
            $this->precioDesUp[$des->id] = $des->precio;
            $this->inicioU[$des->id] = Carbon::parse($des->inicio)->format('Y-m-d');
            $this->finU[$des->id] = $des->fin;
            $this->descuentoU[$des->id] = $des->descuento;
            $this->valorU[$des->id] = $des->valor;
        }

        // Evitar que se seleccione un código no válido (0 o vacío)
        if ($producto->codebar3 && $producto->codebar3 != '0') {
            $this->codebarP = $producto->codebar3;
        } elseif ($producto->codebar1 && $producto->codebar1 != '0') {
            $this->codebarP = $producto->codebar1;
        } elseif ($producto->codebar2 && $producto->codebar2 != '0') {
            $this->codebarP = $producto->codebar2;
        } elseif ($producto->codealternativo && $producto->codealternativo != '0') {
            $this->codebarP = $producto->codebarP = $producto->codealternativo;
        } else {
            $this->codebarP = '';
        }

        $this->unidadP = $producto->medida;

        $this->precios = Precios::where('producto', $this->selected_id)
            ->orderBy('cantidad', 'desc')
            ->orderBy('presentacion', 'asc')
            ->get();

        $this->sucursales = Sucursales::all();

        foreach ($this->precios as $p) {
            $this->cantidadU[$p->id] = number_format($p->cantidad, 0);
            $this->costoSU[$p->id] = number_format($p->costosiva, 4);
            $this->costoIVAU[$p->id] = number_format($p->costociva, 4);
            $this->utilidadU[$p->id] = number_format($p->utilidad, 2);
            $this->precioVentaUniU[$p->id] = number_format($p->pventasiva, 4);
            $this->precioVentaU[$p->id] = number_format($p->pvventa, 4);
            $this->escalaU[$p->id] = $p->escala == 'Si' ? true : false;
            $this->codebarPU[$p->$id] = $p->codebar;
            $this->unidadPU[$p->id] = $p->medida;
            $this->cantidadPU[$p->id] = $p->cantidad;
            $this->sucursalU[$p->id] = $p->sucursal;
        }
    }

    public function render()
    {
        return view('livewire.productos.editarProdut')->extends('layouts.theme.app')->section('content');
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function CrearDes()
    {
        $rules = [
            'precioDes' => 'required',
            'inicio' => 'required|date|after_or_equal:today',
            'fin' => 'required|date|after:inicio',
            'descuento' => 'required|numeric|between:0,99.99',
        ];

        $messages = [
            'precioDes.required' => 'El campo precio a descuento es obligatorio.',
            'inicio.required' => 'El campo inicio es obligatorio.',
            'inicio.date' => 'El campo inicio debe ser una fecha válida.',
            'inicio.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy.',
            'fin.required' => 'El campo fin es obligatorio.',
            'fin.date' => 'El campo fin debe ser una fecha válida.',
            'fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'descuento.required' => 'El campo descuento es obligatorio.',
            'descuento.numeric' => 'El campo descuento debe ser un número.',
            'descuento.between' => 'El campo descuento debe estar entre 0 y 99.99.',
        ];

        if ($this->familiaD) {
            if ($this->familiaD <> 1) {
                $prod = Productos::find($this->selected_id);
                $productos = Productos::where('familia', $prod->familia)->get();

                foreach ($productos as $p) {
                    $agreDes = Descuentos::create([
                        'producto' => $p->id,
                        'precio' => $this->precioDes,
                        'inicio' => $this->inicio,
                        'fin' => $this->fin,
                        'descuento' => $this->descuento,
                    ]);
                }
            } else {
                $agreDes = Descuentos::create([
                    'producto' => $this->selected_id,
                    'precio' => $this->precioDes,
                    'inicio' => $this->inicio,
                    'fin' => $this->fin,
                    'descuento' => $this->descuento,
                ]);
            }
        } else {
            $agreDes = Descuentos::create([
                'producto' => $this->selected_id,
                'precio' => $this->precioDes,
                'inicio' => $this->inicio,
                'fin' => $this->fin,
                'descuento' => $this->descuento,
            ]);
        }

        //$this->familiaD;



        //$this->emit('item-added', 'Descuento agregado');
        $this->setActiveTab('navs-pills-within-card-descuento');
        $this->resetDescuentos();
        $this->mount($this->selected_id);
    }

    public function resetDescuentos()
    {
        $this->precioDes = '';
        $this->inicio = '';
        $this->fin = '';
        $this->descuento = '';
    }

    public function UpdateDes(Descuentos $des)
    {
        $descu = Descuentos::find($des->id);
        $descu->update([
            'inicio' => $this->inicioU[$des->id],
            'fin' => $this->finU[$des->id],
            'descuento' => $this->descuentoU[$des->id],
            'valor' => $this->valorU[$des->id],
        ]);
        //$this->emit('item-updated', 'Se Actualizo el Descuento');
        $this->setActiveTab('navs-pills-within-card-descuento');
        $this->mount($this->selected_id);
    }

    public function Update()
    {
        $rules = [
            //'nombreProducto' => 'required|min:3|unique:productos,nombreProducto,' . $this->selected_id,
            'medida' => 'required|not_in:Elegir',
            'categoria' => 'required|not_in:Elegir',
            'familia' => 'required|not_in:Elegir',
            'codebar1' => 'nullable|unique:productos,codebar1,' . $this->selected_id . ',id,codebar1,' . ($this->codebar1 != 0 ? '0' : 'null'),
            'codebar2' => 'nullable|unique:productos,codebar2,' . $this->selected_id . ',id,codebar2,' . ($this->codebar2 != 0 ? '0' : 'null'),
            'codebar3' => 'nullable|unique:productos,codebar3,' . $this->selected_id . ',id,codebar3,' . ($this->codebar3 != 0 ? '0' : 'null'),
            'codealternativo' => 'nullable|unique:productos,codealternativo,' . $this->selected_id . ',id,codealternativo,' . ($this->codealternativo != 0 ? '0' : 'null'),
        ];

        $messages = [
            //'nombreProducto.required' => 'Nombre del producto requerido',
            //'nombreProducto.unique' => 'Ya existe el nombre del Producto',
            //'//nombreProducto.min' => 'El nombre del producto tiene que tener al menos 3 caracteres',
            'medida.required' => 'Elige un nombre de medida diferente de Elegir',
            'categoria.required' => 'Elige un nombre de Categoria diferente de Elegir',
            'familia.required' => 'Elige una familia diferente de Elegir',
            'codebar1.unique' => 'El código de barras ya está registrado en la base de datos.',
            'codebar2.unique' => 'El código de barras ya está registrado en la base de datos.',
            'codebar3.unique' => 'El código de barras ya está registrado en la base de datos.',
            'codealternativo.unique' => 'El código de barras ya está registrado en la base de datos.',
        ];

        $this->validate($rules, $messages);

        $product = Productos::find($this->selected_id);

        $codAnte = $product->codebar3;

        $familiaAnte = $product->familia;

        $product->update([
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

        if ($codAnte != $this->codebar3) {
            Precios::where('producto', $this->selected_id)
                //->where('codebar', $codAnte)
                ->update(['codebar' => $this->codebar3]);
        }

        if ($this->familia == 1) {
            // Cambiar directamente la familia en los precios del producto actual
            $preciosAnteriores = Precios::where('producto', $this->selected_id)
                ->where('familia', $familiaAnte)
                ->get();

            if (!$preciosAnteriores->isEmpty()) {
                foreach ($preciosAnteriores as $precio) {
                    $precio->familia = $this->familia; // Cambiar a familia 1
                    $precio->save();
                }
            }
        } else {
            if ($this->familia != $familiaAnte) {
                // Obtener los precios asociados al producto actual y a la familia anterior
                $preciosAnteriores = Precios::where('producto', $this->selected_id)
                    ->where('familia', $familiaAnte)
                    ->get();

                if (!$preciosAnteriores->isEmpty()) {
                    // Actualizar los precios del producto actual con la nueva familia
                    foreach ($preciosAnteriores as $precio) {
                        $precio->familia = $this->familia;
                        $precio->save();
                    }

                    // Verificar si hay precios asociados a la nueva familia
                    $productosConFamilia = Productos::where('familia', $this->familia)->pluck('id');

                    $preciosFamilia = Precios::whereIn('producto', $productosConFamilia)->get();

                    if (!$preciosFamilia->isEmpty()) {
                        foreach ($preciosFamilia as $precioFamilia) {
                            Precios::updateOrCreate(
                                [
                                    'producto' => $this->selected_id,
                                    'familia' => $this->familia,
                                    'medida' => $precioFamilia->medida,
                                    'codebar' => $this->codebar3,
                                    'cantidad' => $precioFamilia->cantidad,
                                ],
                                [
                                    'presentacion' => $precioFamilia->presentacion,
                                    'costosiva' => $precioFamilia->costosiva,
                                    'costociva' => $precioFamilia->costociva,
                                    'utilidad' => $precioFamilia->utilidad,
                                    'pventasiva' => $precioFamilia->pventasiva,
                                    'pvventa' => $precioFamilia->pvventa,
                                    'precio' => $precioFamilia->precio,
                                ]
                            );
                        }
                    }
                }
            }
        }

        if (!empty($this->imagenes)) {
            foreach ($this->imagenes as $img) {
                $customFileName = uniqid() . '_.' . $img->extension();

                // Cargar la imagen y redimensionarla
                $image = Image::make($img)
                    ->resize(800, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->encode();

                // Guardar la imagen en el sistema de archivos
                Storage::put('public/productos/' . $customFileName, $image);

                // Crear el registro de galería en la base de datos
                imagenes::create([
                    'producto' => $this->selected_id,
                    'imagen' => $customFileName,
                ]);
            }
        }
        //$this->resetUI();
        //$this->emit('item-updated', 'Producto Actualizado');
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

    public function actualizarCantidad()
    {
        if (!is_numeric($this->cantidadP)) {
            $this->mensajeError = 'Ingrese solo valores numéricos para la cantidad.';
        } else {
            $ultimoPrecio = $this->obtenerUltimoPrecioRegistrado($this->selected_id);
            if ($ultimoPrecio) {
                if ($this->cantidadP > $ultimoPrecio->cantidad) {
                    $this->costoS = number_format($ultimoPrecio->costosiva * $this->cantidadP, 4);
                    $this->costoIVA = $this->costoS * 1.13;
                    $this->utilidad = $ultimoPrecio->utilidad;
                    //$this->actualizarUtilidad();
                    //$this->precioVentaUni  = $this->precioVenta /$this->cantidadP;
                } else {
                    if ($this->unidadP == 22) {
                        $this->costoS = number_format($ultimoPrecio->costosiva * $this->cantidadP, 4);
                        $this->costoIVA = $this->costoS * 1.13;
                        $this->utilidad = $ultimoPrecio->utilidad;
                        $this->precioVentaUni  = $this->precioVenta * $this->cantidadP;
                    } else {
                        $this->costoS = number_format($ultimoPrecio->costosiva / $this->cantidadP, 4);
                        $this->costoIVA = $this->costoS * 1.13;
                        $this->utilidad = $ultimoPrecio->utilidad;
                        $this->precioVentaUni  = $this->precioVenta / $this->cantidadP;
                    }
                }
                $this->actualizarPrecios();
            }
        }
    }

    public function actualizarPrecios()
    {
        if (is_numeric($this->utilidad) && is_numeric($this->cantidadP) && $this->cantidadP > 0) {
            $this->precioVentaUni = round((($this->costoS * 1.13) * (1 + $this->utilidad / 100)) / $this->cantidadP, 4);
            $this->precioVenta = round($this->precioVentaUni * $this->cantidadP, 4);
        } else {
            $this->precioVentaUni = 0;
            $this->precioVenta = 0;
        }
    }

    public function actualizarCostoConIva()
    {
        if (!is_numeric($this->costoS)) {
            $this->mensajeError = 'Ingrese solo valores numéricos para el costo sin IVA.';
            $this->costoIVA = null;
        } else {
            $this->costoIVA = number_format($this->costoS * 1.13, 4);
            $this->actualizarPrecios();
        }
    }

    public function actualizarCostoSinIva()
    {
        if (!is_numeric($this->costoIVA)) {
            $this->mensajeError = 'Ingrese solo valores numéricos para el costo con IVA.';
            $this->costoS = null;
        } else {
            $this->costoS = number_format($this->costoIVA / 1.13, 4);
            $this->actualizarPrecios();
        }
    }

    public function actualizarPrecioVenta()
    {
        if (!is_numeric($this->utilidad)) {
            $this->mensajeError = 'Ingrese solo valores numéricos para la utilidad.';
            $this->precioVenta = null;
        } else {
            $this->precioVentaUni = number_format(($this->costoS * 1.13) * (1 + $this->utilidad / 100), 4);
            $this->precioVenta = number_format(($this->precioVentaUni * 1), 4);
            $this->actualizarPrecios();
        }
    }

    public function actualizarUtilidad()
    {
        if (!is_numeric($this->precioVentaUni)) {
            $this->mensajeError = 'Ingrese solo valores numéricos para el precio de venta sin IVA.';
            $this->utilidad = null;
        } else {
            $this->utilidad = number_format(((($this->precioVentaUni / 1) / $this->costoS - 1) * 100) /  $this->cantidadP, 4);
            $this->precioVenta = number_format($this->precioVentaUni * $this->cantidadP, 2);
            //$this->actualizarPrecios();
        }
    }

    public function actualizarUtilidad2()
    {
        if (!is_numeric($this->precioVenta)) {
            $this->mensajeError = 'Ingrese solo valores numéricos para el precio de venta con IVA.';
            $this->utilidad = null;
        } else {
            $this->precioVentaUni = number_format(($this->precioVenta * 1) / 1.13, 4);
            $this->utilidad = number_format((($this->precioVentaUni * 1) / $this->costoS - 1) * 100, 2);
            $this->actualizarPrecios();
        }
    }

    public function StorePrecios()
    {
        $rules = [
            'codebarP' => 'required',
            'unidadP' => 'required',
            'cantidadP' => 'required|numeric',
            'costoS' => 'required|numeric',
            'costoIVA' => 'required|numeric',
            'utilidad' => 'required|numeric',
            'precioVentaUni' => 'required|numeric',
            'precioVenta' => 'required|numeric',
        ];

        // Definir los mensajes de validación personalizados (opcional)
        $messages = [
            'codebarP.required' => 'El campo Código de Barra es obligatorio.',
            'unidadP.required' => 'El campo Presentación es obligatorio.',
            'cantidadP.required' => 'El campo Cantidad es obligatorio.',
            'cantidadP.numeric' => 'El campo Cantidad debe ser numérico.',
            'costoS.required' => 'El campo C.S/IVA es obligatorio.',
            'costoS.numeric' => 'El campo C.S/IVA debe ser numérico.',
            'costoIVA.required' => 'El campo C/IVA es obligatorio.',
            'costoIVA.numeric' => 'El campo C/IVA debe ser numérico.',
            'utilidad.required' => 'El campo % es obligatorio.',
            'utilidad.numeric' => 'El campo % debe ser numérico.',
            'precioVentaUni.required' => 'El campo PV/S.IVA es obligatorio.',
            'precioVentaUni.numeric' => 'El campo PV/S.IVA debe ser numérico.',
            'precioVenta.required' => 'El campo PV es obligatorio.',
            'precioVenta.numeric' => 'El campo PV debe ser numérico.',
        ];

        // Validar los datos
        $this->validate($rules, $messages);

        // Obtener el producto seleccionado
        $producto = Productos::where('id', $this->selected_id)->first();

        if (!$producto) {
            $this->emit('item-error', 'Producto no encontrado.');
            return;
        }

        $escala = $this->escala ? 'Si' : 'No';
        $familia = $producto->familia;
        $medida  = Medidas::find($this->unidadP);

        // Datos comunes para insertar
        $data = [
            'familia' => $familia,
            'medida' => $this->unidadP,
            'presentacion' => $medida->unidad,
            'cantidad' => $this->cantidadP,
            'costosiva' => $this->costoS,
            'costociva' => $this->costoIVA,
            'utilidad' => $this->utilidad,
            'pventasiva' => $this->precioVentaUni,
            'pvventa' => $this->precioVenta,
            'escala' => $escala
        ];

        // guardaremos aquí todos los IDs de precios creados/asegurados
        $precioIdsAsegurados = [];

        DB::transaction(function () use ($familia, $producto, $data, &$precioIdsAsegurados) {

            if ((int)$familia === 1) {

                $dataTmp = $data;
                $dataTmp['producto'] = $producto->id;
                $dataTmp['codebar']  = $producto->codebar ?: ($producto->codebar2 ?: ($producto->codebar3 ?: $producto->codealternativo));
                // Verificar si ya existe un precio para el producto seleccionado
                $precio = Precios::where('producto', $dataTmp['producto'])
                    ->where('medida', $dataTmp['medida'])
                    ->where('cantidad', $dataTmp['cantidad'])
                    ->where('costosiva', $dataTmp['costosiva'])
                    ->where('costociva', $dataTmp['costociva'])
                    ->where('pvventa', $dataTmp['pvventa'])
                    ->first();
                if (!$precio) {
                    $precio = Precios::create($dataTmp);
                }

                $precioIdsAsegurados[] = $precio->id;
            } else {
                // Obtener todos los productos que pertenecen a la misma familia
                $productosDeFamilia = Productos::where('familia', $familia)->get();

                foreach ($productosDeFamilia as $productoDeFamilia) {
                    // Verificar si ya existe un precio para este producto con la misma medida y familia
                    $precioExistente = Precios::where('producto', $productoDeFamilia->id)
                        ->where('medida', $data['medida'])
                        ->where('cantidad', $data['cantidad'])
                        ->where('costosiva', $data['costosiva'])
                        ->where('costociva', $data['costociva'])
                        ->where('pvventa', $data['pvventa'])
                        ->first();

                    if ($precioExistente) {
                        $precioIdsAsegurados[] = $precioExistente->id;
                        continue;
                    }


                    // 2) ¿Existe con misma medida/cantidad/familia pero con diferencias? => actualizar
                    $precioActualizar = Precios::where('producto', $productoDeFamilia->id)
                        ->where('medida', $data['medida'])
                        ->where('cantidad', $data['cantidad'])
                        ->where('familia', $familia)
                        ->first();

                    if ($precioActualizar) {
                        $precioActualizar->update([
                            'cantidad'     => $data['cantidad'],
                            'costosiva'    => $data['costosiva'],
                            'costociva'    => $data['costociva'],
                            'utilidad'     => $data['utilidad'],
                            'pventasiva'   => $data['pventasiva'],
                            'pvventa'      => $data['pvventa'],
                            'presentacion' => $data['presentacion'],
                        ]);
                        $precioIdsAsegurados[] = $precioActualizar->id;
                    } else {
                        // 3) Crear nuevo
                        $dataTmp = $data;
                        $dataTmp['producto'] = $productoDeFamilia->id;
                        $dataTmp['codebar']  = $productoDeFamilia->codebar ?: ($productoDeFamilia->codebar2 ?: ($productoDeFamilia->codebar3 ?: $productoDeFamilia->codealternativo));
                        $nuevo = Precios::create($dataTmp);
                        $precioIdsAsegurados[] = $nuevo->id;
                    }
                }
            }
        });

        $this->attachPrecioToAllSucursales($precioIdsAsegurados);

        // Emitir un evento para indicar que los precios se han guardado correctamente
        //$this->emit('item-added', 'Precios guardados exitosamente.');
        //$this->setActiveTab('navs-pills-within-card-precios');
        $this->resetPrecios();
        $this->mount($this->selected_id);
    }

    protected function attachPrecioToAllSucursales(array $precioIds): void
    {
        if (empty($precioIds)) return;

        $sucursales = Sucursales::pluck('id');
        foreach ($precioIds as $precioId) {
            foreach ($sucursales as $sucursalId) {

                $exists = sucursal_precio::where('precio', $precioId)
                    ->where('sucursal', $sucursalId)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$exists) {
                    // Intentar setear sincro_id si existe esa columna
                    $payload = [
                        'precio'   => $precioId,
                        'sucursal' => $sucursalId,
                        'activo'   => 1,
                    ];

                    // Si tu modelo tiene fillable 'sincro_id' y existe la columna, esto funcionará;
                    // si no existe, no pasa nada.
                    if (Schema::hasColumn((new sucursal_precio)->getTable(), 'sincro_id')) {
                        $payload['sincro_id'] = (string) Str::uuid();
                    }

                    sucursal_precio::create($payload);
                }
            }
        }
    }

    public function resetPrecios()
    {
        $this->codebarP = '';
        $this->unidadP = '';
        $this->cantidadP = '';
        $this->costoS = '';
        $this->costoIVA = '';
        $this->utilidad = '';
        $this->precioVentaUni = '';
        $this->precioVenta = '';
    }

    public function actualizarCostoConIvaU($id)
    {
        $pre = Precios::find($id);
        if (!is_numeric($this->costoSU[$id])) {
            $this->mensajeError = 'Ingrese solo valores numéricos para el costo sin IVA.';
            $this->costoIVAU[$id] = null;
        } else {

            $pre = Precios::find($id);

            $costoXUni = number_format($this->costoSU[$id] / $pre->cantidad, 4);

            $precios = Precios::where('producto', $this->selected_id)->get();

            foreach ($precios as $p) {
                $p->costosiva = $costoXUni * $p->cantidad;
                $p->costociva = $p->costosiva * 1.13;
                $p->pventasiva = ($p->costociva) * (1 + $p->utilidad / 100) / $p->cantidad;
                $p->pvventa = $p->pventasiva * $p->cantidad;
                $p->save();
            }

            $this->mount($this->selected_id);
            //$this->emit('item-updated', 'Costos sin IVA actualizados.');
        }
    }

    public function actualizarCostoSinIvaU($id)
    {
        if (!is_numeric($this->costoIVAU[$id])) {
            $this->mensajeError = 'Ingrese solo valores numéricos para el costo con IVA.';
            $this->costoSU[$id] = null;
        } else {

            $pre = Precios::find($id);

            $costoXUni = number_format($this->costoIVAU[$id] / $pre->cantidad, 4);

            $precios = Precios::where('producto', $this->selected_id)->get();

            foreach ($precios as $p) {
                $p->costosiva = ($costoXUni / 1.13) * $p->cantidad;
                $p->costociva = $costoXUni *  $p->cantidad;
                $p->pventasiva = ($p->costociva) * (1 + $p->utilidad / 100) / $p->cantidad;
                $p->pvventa = $p->pventasiva * $p->cantidad;
                $p->save();
            }

            $this->mount($this->selected_id);
            //$this->emit('item-updated', 'Costos con IVA actualizados.');
        }
    }

    public function actualizarPrecioVentaU($id)
    {
        $pre = Precios::find($id);
        if (!is_numeric($this->utilidadU[$id])) {
            $this->mensajeError = 'Ingrese solo valores numéricos para la utilidad.';
            $this->precioVentaU[$id] = null;
        } else {
            $this->precioVentaUniU[$id] = number_format(($this->costoSU[$id] * 1.13) * (1 + $this->utilidadU[$id] / 100) / $pre->cantidad, 4);
            $this->precioVentaU[$id] = number_format($this->precioVentaUniU[$id] * $pre->cantidad, 4);

            $pre->utilidad = $this->utilidadU[$id];
            $pre->pventasiva = $this->precioVentaUniU[$id];
            $pre->pvventa = $this->precioVentaU[$id];;
            $pre->save();
            //$this->actualizarPreciosU($id);
            $this->mount($this->selected_id);
        }
    }

    public function actualizarUtilidadU($id)
    {
        $precio = Precios::find($id);
        /*if (!is_numeric($this->precioVentaUniU[$id])) {
            $this->mensajeError = 'Ingrese solo valores numéricos para el precio de venta sin IVA.';
            $this->utilidadU[$id] = null;
        } else {
            $this->utilidadU[$id] = number_format((($this->precioVentaUniU[$id] / $precio->cantidad) / $this->costoSU[$id] - 1) * 100, 2);
            $this->precioVentaU[$id] = number_format($this->precioVentaUniU[$id] * 1.13, 4);
            $this->actualizarPreciosU($id);
        } */
        if (!is_numeric($this->precioVentaU[$id])) {
            $this->mensajeError = 'Ingrese solo valores numéricos para el precio de venta con IVA.';
            $this->utilidadU[$id] = null;
        } else {
            // Calcular el precio de venta unitario sin IVA
            //$this->precioVentaUniU[$id] = number_format($this->precioVentaU[$id] / $precio->cantidad, 4);

            // Ajustar el margen (utilidad) basado en el precio ingresado manualmente
            $this->utilidadU[$id] = number_format((($this->precioVentaUniU[$id] / 1) / $this->costoSU[$id] - 1) * 100, 2);

            // Aquí no recalculamos el precio nuevamente para mantener el valor ingresado
        }
    }

    public function actualizarUtilidad2U($id)
    {
        $pre = precios::find($id);
        if (!is_numeric($this->precioVentaU[$id])) {
            $this->mensajeError = 'Ingrese solo valores numéricos para el precio de venta con IVA.';
            $this->utilidadU[$id] = null;
        } else {
            // Calcular el precio de venta unitario sin IVA
            $this->precioVentaUniU[$id] = number_format($this->precioVentaU[$id] / $pre->cantidad, 4);

            // Ajustar el margen (utilidad) basado en el precio ingresado manualmente
            $this->utilidadU[$id] = number_format((($this->precioVentaUniU[$id] / ($this->costoIVAU[$id] / $pre->cantidad)) - 1) * 100, 2);

            //$this->utilidadU[$id] = number_format((($this->precioVentaUniU[$id] / 1) / $this->costoSU[$id] - 1) * 100, 2);

            $pre->utilidad = $this->utilidadU[$id];
            $pre->pventasiva = $this->precioVentaUniU[$id];
            $pre->pvventa = $this->precioVentaU[$id];
            $pre->save();
            $this->mount($this->selected_id);
        }
    }

    public function actualizarPreciosU($id)
    {
        $precio = Precios::find($id);
        if (is_numeric($this->utilidadU[$id])) {
            $this->precioVentaUniU[$id] = number_format(($this->costoSU[$id] * 1.13) * (1 + $this->utilidadU[$id] / 100) / $precio->cantidad, 4);
            $this->precioVentaU[$id] = number_format($this->precioVentaUniU[$id] * $precio->cantidad, 4);
        }
    }

    public function actualizarPrecioVentaTotalU($id)
    {
        $precio = Precios::find($id);
        if (is_numeric($precio->cantidad) && is_numeric($this->precioVentaUniU[$id])) {
            $this->precioVentaU[$id] = number_format($this->precioVentaUniU[$id] * $precio->cantidad * 1.13, 4);
        } else {
            $this->precioVentaU[$id] = null;
        }
    }

    public function ElimPreU($id)
    {
        $precio = Precios::find($id);
        $precio->delete();

        //$this->emit('item-added', 'Precio Eliminado del producto exitosamente.');
        //$this->setActiveTab('navs-pills-within-card-precios');
        $this->mount($this->selected_id);
    }

    public function UpdatePrecio($id)
    {
        $precio = Precios::find($id);

        if ($this->escalaU[$id] == true) {
            $escala = 'Si';
        } else {
            $escala = 'No';
        }

        if ($precio->familia == 1) {
            $precio->costosiva = $this->costoSU[$id];
            $precio->costociva = $this->costoIVAU[$id];
            $precio->utilidad = $this->utilidadU[$id];
            $precio->pventasiva = $this->precioVentaUniU[$id];
            $precio->pvventa = $this->precioVentaU[$id];
            $precio->escala = $escala;
            $precio->save();
        } else {
            $familiaProductos = Productos::where('familia', $precio->familia)->pluck('id');

            foreach ($familiaProductos as $productoId) {

                $preciosFamilia = Precios::where('producto', $productoId)->where('medida', $precio->medida)->where('cantidad', $this->cantidadU[$id])->get();

                foreach ($preciosFamilia as $precioFamilia) {
                    $precioFamilia->costosiva = $this->costoSU[$id];
                    $precioFamilia->costociva = $this->costoIVAU[$id];
                    $precioFamilia->utilidad = $this->utilidadU[$id];
                    $precioFamilia->pventasiva = $this->precioVentaUniU[$id];
                    $precioFamilia->pvventa = $this->precioVentaU[$id];
                    $precioFamilia->escala = $escala;
                    $precioFamilia->save();
                }
            }
        }
        //$this->emit('item-added', 'Precio Actualizado exitosamente.');
        //$this->setActiveTab('navs-pills-within-card-precios');
        $this->mount($this->selected_id);
    }

    public function obtenerUltimoPrecioRegistrado($productoId)
    {
        return Precios::where('producto', $productoId)->orderBy('cantidad', 'asc')->first();
    }

    private function duplicarPreciosDeFamilia($preciosFamilia)
    {
        foreach ($preciosFamilia as $precio) {
            Precios::create([
                'producto' => $this->selected_id,
                'familia' => $this->familia,
                'medida' => $precio->medida,
                'codebar' => $this->codebar3,
                'cantidad' => $precio->cantidad,
                'costosiva' => $precio->costosiva,
                'costociva' => $precio->costociva,
                'utilidad' => $precio->utilidad,
                'pventasiva' => $precio->pventasiva,
                'pvventa' => $precio->pvventa,
                'presentacion' => $precio->presentacion,
            ]);
        }
    }

    public function UpdateCodebar($id)
    {
        $precio = Precios::find($id);

        $precio->codebar = $this->codebarPU[$id];
        $precio->save();
    }

    public function UpdateUnidad($id)
    {
        //dd($id);
        $precio = Precios::find($id);
        $uni = Medidas::find($this->unidadPU[$id]);

        if ($this->unidadPU[$id] == 22) {
            $prod = Productos::find($precio->producto);
            $canti = 1 / $prod->contenedor;

            $precio->cantidad = $canti;
        }

        $precio->medida = $this->unidadPU[$id];
        $precio->presentacion = $uni->unidad;
        $precio->save();

        //$this->setActiveTab('navs-pills-within-card-precios');
        //$this->emit('item-updated', 'Se actualizo la medida');
        $this->mount($this->selected_id);
    }

    public function actualizarCantidadU($id)
    {
        $precio = Precios::find($id);
        $costo = $precio->costosiva / $this->cantidadPU[$id];
        $cantiAnte = $precio->cantidad;
        $nuewCanti = $this->cantidadPU[$id];

        $precio->cantidad = $nuewCanti;
        $precio->costosiva = $costo  * $nuewCanti;
        $precio->costociva = ($costo * 1.13) * $nuewCanti;
        $precio->pventasiva = $precio->pventasiva;
        $precio->pvventa = $precio->pvventa;
        $precio->save();

        $this->mount($this->selected_id);
        //$this->setActiveTab('navs-pills-within-card-precios');
        //$this->emit('item-updated', 'Se Actualizo la cantidad');
    }

    public function CargaSucursales($id)
    {
        $this->precios_id = $id;
        $con = sucursal_precio::where('precio', $id)->get();

        if ($con->isEmpty()) {
            // Obtener todas las sucursales
            $sucursales = Sucursales::all();

            // Insertar todos los precios con sus respectivas sucursales
            foreach ($sucursales as $sucursal) {
                sucursal_precio::create([
                    'precio' => $id,
                    'sucursal' => $sucursal->id,
                    'activo' => 1,
                ]);
            }
        } else {
            // Verificar si hay menos de 15 registros
            if ($con->count() < 15) {
                // Obtener todas las sucursales
                $sucursales = Sucursales::all();
                $sucursalesExistentes = $con->pluck('sucursal')->toArray();
                // Insertar las sucursales faltantes
                foreach ($sucursales as $sucursal) {
                    if (!in_array($sucursal->id, $sucursalesExistentes)) {
                        sucursal_precio::create([
                            'precio' => $id,
                            'sucursal' => $sucursal->id,
                            'activo' => 1,
                        ]);
                    }
                }
            }
        }

        $this->sucursalesPrecios = sucursal_precio::with('Rsucursal:id,nombre')->where('precio', $this->precios_id)->get();

        foreach ($this->sucursalesPrecios as $ps) {
            $this->sp[$ps->id] = $ps->activo == 1;
        }
        $this->emit('show-sucursalP', 'show modal');
    }

    public function updateSucursal($id)
    {
        //dd($this->sp[$id]);
        $sp = sucursal_precio::find($id);
        $activo = $this->sp[$id] ? 1 : 0;
        $sp->activo = $activo;
        $sp->save();

        $this->sucursalesPrecios = sucursal_precio::with('Rsucursal:id,nombre')->where('precio', $this->precios_id)->get();
    }

    public function calcularNuevoPrecio()
    {
        if (empty($this->precioDes)) {
            $this->emit('item-error', 'Seleccione un precio para aplicar descuento');
            return;
        }
        $precio = Precios::find($this->precioDes);

        $this->valor = number_format($precio->pvventa - ($precio->pvventa * ($this->descuento / 100)), 2);
    }

    public function calcularDescuento()
    {
        //dd('entro');
        if (empty($this->precioDes) || empty($this->valor)) {
            $this->emit('item-error', 'Seleccione un precio y un valor para calcular el descuento');
            return;
        }

        $precio = Precios::find($this->precioDes);
        $this->descuento = number_format(((1 - ($this->valor / $precio->pvventa)) * 100), 2);
    }

    public function calcularNuevoPrecioU($id)
    {
        $precio = Precios::find($this->precioDesUp[$id]);

        $this->valorU[$id] = number_format($precio->pvventa - ($precio->pvventa * ($this->descuentoU[$id] / 100)), 2);
    }

    public function calcularDescuentoU($id)
    {
        $precio = Precios::find($this->precioDesUp[$id]);
        $this->descuentoU[$id] = number_format(((1 - ($this->valorU[$id] / $precio->pvventa)) * 100), 2);
    }

    public function actualizarUnidad()
    {
        if ($this->unidadP == 22) {
            $this->cantidadP = number_format(1 / $this->contenedor, 2);
            $this->costoS = number_format($this->costoS / $this->contenedor, 4);

            $this->actualizarCantidad();
        } else {
            $this->cantidadP = 1;
            $this->actualizarCantidad();
        }
    }

    protected $listeners = ['volverAListaConSeleccion' => 'seleccionarProducto'];

    public function seleccionarProducto()
    {
        // Guardar en sesión el estado actual
        session()->put('producto_search', request()->query('search', ''));
        session()->put('producto_pagina', request()->query('page', 1));
        session()->put('producto_seleccionado', $this->selected_id);

        return redirect()->route('productoAdmin', [
            'search' => request()->query('search', ''),
            'page' => request()->query('page', 1),
        ]);
    }

    //protected $queryString = ['volverAListaConSeleccion'];

    public function volverAListaConSeleccion()
    {
        session()->put('producto_search', $this->search);
        session()->put('producto_pagina', $this->page);
        session()->put('producto_seleccionado', $this->producto_id); // o selected_id

        return redirect()->route('productoAdmin', [
            'search' => $this->search,
            'page' => $this->page
        ]);
    }

    public function updateEscala($id)
    {
        $precio = Precios::find($id);
        if ($this->escalaU[$id] == true) {
            $precio->escala = 'Si';
        } else {
            $precio->escala = 'No';
        }
        $precio->save();

        $this->mount($this->selected_id);
    }

    function ajustarDecimal($numero, $decimales = 2)
    {
        $factor = pow(10, $decimales);
        $numero *= $factor;

        $entero = floor($numero);
        $decimal = $numero - $entero;

        // Solo redondea si el decimal es > 0.5 (estrictamente mayor)
        if ($decimal > 0.5) {
            $entero++;
        }

        // Devuelve el resultado como float
        return $entero / $factor;
    }
}
