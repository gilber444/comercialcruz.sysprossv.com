<?php

namespace App\Http\Livewire;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Component;
use App\Models\Retenciones;
use App\Models\RetencionesRenta;
use App\Models\DetallePlanilla as DetallePlanilladb;
use App\Models\Planilla;
use Carbon\Carbon;
class DetallePlanillaController extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search, $records, $selected_id,$pageTitle, $modalAction, $componentName, $codigo, 
    $valor, $status, $pagination = 10, $fechaplanilla_inicio, $detalle='', $detalle_planilla, $numeroplanilla, $diasTrabajados= [], $tipo_pago,
    $horasExtrasD=[], $horasExtrasN=[], $porcentajeNocturnidad=[], $bonificaciones=[], $vacaciones=[], $reintegroSalarial=[], $asueto=[],
    $otrosIngresos=[], $anticipoSueldo=[], $prestamosEmpresarial=[], $otrosDescuentos=[], $fechaplanilla_fin, $afp=[], $seguro=[], $renta=[];

    public function mount($idPlanilla){
        $planilla = Planilla::find($idPlanilla);
        $this->detalle_planilla = DetallePlanilladb::where('planilla_encabezado', $idPlanilla)->get();

        // Verifica si el registro existe
        if (!$planilla || !$this->detalle_planilla) {
            abort(404, 'Planilla no encontrada');
        }
        $this->fechaplanilla_inicio = Carbon::parse($planilla->fechaplanilla_inicio)->format('d/m/Y');
        $this->detalle = $planilla->detalle;
        $this->numeroplanilla = $planilla->id;
        $this->tipo_pago = $planilla->tipo_pago;
        $this->fechaplanilla_fin = Carbon::parse($planilla->fechaplanilla_fin)->format('d/m/Y');

        $detalles = DetallePlanilladb::where('planilla_encabezado', $idPlanilla)->get();

        // Asignamos los días trabajados al array diasTrabajados
        foreach ($detalles as $detalle) {
            // Asignar los valores de dias_trabajados al array diasTrabajados
            $this->diasTrabajados[$detalle->id] = $detalle->dias_trabajados;
            $this->horasExtrasD[$detalle->id] = $detalle->horas_extras_d;
            $this->horasExtrasN[$detalle->id] = $detalle->horas_extras_n;
            $this->porcentajeNocturnidad[$detalle->id] = $detalle->porcentaje_nocturnidad;
            $this->bonificaciones[$detalle->id] = $detalle->bonificaciones;
            $this->vacaciones[$detalle->id] = $detalle->vacaciones;
            $this->reintegroSalarial[$detalle->id] = $detalle->reintegro_salarial;
            $this->asueto[$detalle->id] = $detalle->asueto;
            $this->otrosIngresos[$detalle->id] = $detalle->otros_ingresos;
            $this->anticipoSueldo[$detalle->id] = $detalle->anticipo_sueldo;
            // NUEVO CAMPO DE AFP
            $this->afp[$detalle->id] = $detalle->afp;
            $this->seguro[$detalle->id] = $detalle->seguro;
            $this->renta[$detalle->id] = $detalle->renta;
            $this->prestamosEmpresarial[$detalle->id] = $detalle->prestamos_empresarial;
            $this->otrosDescuentos[$detalle->id] = $detalle->otros_descuentos;
        }
        

    }

    public function render()
    {
        return view('livewire.detalle_planilla.detalle_planilla')
        ->extends('layouts.theme.app')
        ->section('content');
    }


    public function updateDias($id){

        // Condiciones de dias trabajados
        $dias_trabajados = $this->diasTrabajados[$id];

        if($dias_trabajados <= 31){
            // Calculo de dias trabajados
            if($this->tipo_pago == 'mensual' && $dias_trabajados <= 31){
                $detalle = DetallePlanilladb::find($id);
                $salarioDiario = $detalle->sueldo_base / 30;

                // CALCULOS DE SALARIO TOTAL  

                // -> Ingresos (sumaran al salario)
                $ingresos_generales=[
                    $horas_extras_diurnas = $this->horasExtrasD[$id],
                    $horas_extras_nocturnas = $this->horasExtrasN[$id],
                    $porcentaje_nocturnidad = $this->porcentajeNocturnidad[$id],
                    $bonificaciones_extras = $this->bonificaciones[$id],
                    $vacaciones_extras = $this->vacaciones[$id],
                    $reintegro_salarial_extras = $this->reintegroSalarial[$id],
                    $asueto_extras  = $this->asueto[$id],
                    $otros_ingresos_extras  = $this->otrosIngresos[$id]
                ];

                $totalFinal_ingresos_generales = array_sum($ingresos_generales);

                // -> Descuentos (restara al salario)
                $descuentos_generales=[
                    $anticipo_sueldo_extras   = $this->anticipoSueldo[$id],
                    $prestamos_empresarial_extras   = $this->prestamosEmpresarial[$id],
                    $otros_descuentos_extras   = $this->otrosDescuentos[$id],
                ];

                $totalFinal_descuentos_generales = array_sum($descuentos_generales);

                $totalPago_liquido = $salarioDiario * $dias_trabajados;
    
                $totalPago = $salarioDiario * $dias_trabajados + $totalFinal_ingresos_generales;

                $seguro = Retenciones::where('nombre', 'isss')->first();

                if($totalPago >= 1000){
                    $calculoseguro = 30.0;
                }
                else{
                    $calculoseguro =  $totalPago * floatval($seguro->porcentaje)/ 100;
                }
    
    
                $afp = Retenciones::where('nombre', 'afp')->first();
                $calculoafp =  $totalPago * floatval($afp->porcentaje) / 100;
    
                $deducciones = $totalPago - $calculoafp;
    
                // Calculo de renta por tipo de pago
    
                $renta = RetencionesRenta::where('tipopago', $this->tipo_pago)
                ->where('retenciondesde', '<=', $deducciones)
                ->where('retencionhasta', '>=', $deducciones)
                ->first();
    
    
                if($renta->exceso == 0.0){
                    $rentatotal = 0.0;
                }
                else{
                    $excesoapagar = ($deducciones - $calculoseguro) - $renta->exceso;
    
                    $impuesto = $excesoapagar * ($renta->porcentaje / 100);
                    $rentatotal = $impuesto + $renta->cuotafija;
                }
                // $totalDescuento = $detalle->sueldo_base - $totalPago;
    
                $total_final = $totalPago - ($calculoafp + $calculoseguro + $rentatotal);
                
    
                $detalle->dias_trabajados = $dias_trabajados;
                $detalle->total_descuento = $calculoafp + $calculoseguro + $rentatotal;
                $detalle->seguro = $calculoseguro;
                $detalle->afp = $calculoafp;
                $detalle->renta= $rentatotal;
                $detalle->sueldo_liquido = $totalPago_liquido;

                $detalle->estado = 'Validado';

                // Nuevos Campos Calculos INGRESOS
                $detalle->horas_extras_d = $horas_extras_diurnas;
                $detalle->horas_extras_n = $horas_extras_nocturnas;
                $detalle->porcentaje_nocturnidad = $porcentaje_nocturnidad;
                $detalle->bonificaciones = $bonificaciones_extras;
                $detalle->vacaciones = $vacaciones_extras;
                $detalle->reintegro_salarial = $reintegro_salarial_extras;
                $detalle->asueto = $asueto_extras;
                $detalle->otros_ingresos = $otros_ingresos_extras;

                // Nuevos Campos Calculos DESCUENTOS
                $detalle->anticipo_sueldo = $anticipo_sueldo_extras ;
                $detalle->prestamos_empresarial = $prestamos_empresarial_extras;
                $detalle->otros_descuentos = $otros_descuentos_extras;


                // Nuevos Campos Calculos Totales
                $detalle->total_ingresos_generales = $totalPago;
                $detalle->total_descuentos_generales = $totalFinal_descuentos_generales + ($calculoafp + $calculoseguro + $rentatotal);

                // TOTAL FINAL
                $total_calculos_finales = $totalFinal_descuentos_generales + ($calculoafp + $calculoseguro + $rentatotal);
                $detalle->total_pagar = $totalPago - $total_calculos_finales;

                
                $detalle->save();
    
    
                $this->emit('item-updated', 'Valor actualizado con exito');
                $this->ResetInt();
            }
            elseif($this->tipo_pago == 'quincenal' && $dias_trabajados <= 15){
                $detalle = DetallePlanilladb::find($id);
                $salarioDiario = $detalle->sueldo_base/ 15;
                // CALCULOS DE SALARIO TOTAL  

                // -> Ingresos (sumaran al salario)
                $ingresos_generales=[
                    $horas_extras_diurnas = $this->horasExtrasD[$id],
                    $horas_extras_nocturnas = $this->horasExtrasN[$id],
                    $porcentaje_nocturnidad = $this->porcentajeNocturnidad[$id],
                    $bonificaciones_extras = $this->bonificaciones[$id],
                    $vacaciones_extras = $this->vacaciones[$id],
                    $reintegro_salarial_extras = $this->reintegroSalarial[$id],
                    $asueto_extras  = $this->asueto[$id],
                    $otros_ingresos_extras  = $this->otrosIngresos[$id]
                ];

                $totalFinal_ingresos_generales = array_sum($ingresos_generales);

                // -> Descuentos (restara al salario)
                $descuentos_generales=[
                    $anticipo_sueldo_extras   = $this->anticipoSueldo[$id],
                    $prestamos_empresarial_extras   = $this->prestamosEmpresarial[$id],
                    $otros_descuentos_extras   = $this->otrosDescuentos[$id],
                ];

                $totalFinal_descuentos_generales = array_sum($descuentos_generales);

                $totalPago_liquido = $salarioDiario * $dias_trabajados;
    
                $totalPago = $salarioDiario * $dias_trabajados + $totalFinal_ingresos_generales;

                $seguro = Retenciones::where('nombre', 'isss')->first();

                if($totalPago >= 1000){
                    $calculoseguro = 30.0;
                }
                else{
                    $calculoseguro =  $totalPago * floatval($seguro->porcentaje)/ 100;
                }
    
    
                $afp = Retenciones::where('nombre', 'afp')->first();
                $calculoafp =  $totalPago * floatval($afp->porcentaje) / 100;
    
                $deducciones = $totalPago - $calculoafp;
    
                // Calculo de renta por tipo de pago
    
                $renta = RetencionesRenta::where('tipopago', $this->tipo_pago)
                ->where('retenciondesde', '<=', $deducciones)
                ->where('retencionhasta', '>=', $deducciones)
                ->first();
    
                if($renta->exceso == 0.0){
                    $rentatotal = 0.0;
                }
                else{
                    $excesoapagar = ($deducciones - $calculoseguro) - $renta->exceso;
    
                    $impuesto = $excesoapagar * ($renta->porcentaje / 100);
                    $rentatotal = $impuesto + $renta->cuotafija;
                }
                // $totalDescuento = $detalle->sueldo_base - $totalPago;
    
                $total_final = $totalPago - ($calculoafp + $calculoseguro + $rentatotal);
                
    
                $detalle->dias_trabajados = $dias_trabajados;
                $detalle->total_descuento = $calculoafp + $calculoseguro + $rentatotal;
                $detalle->seguro = $calculoseguro;
                $detalle->afp = $calculoafp;
                $detalle->renta= $rentatotal;
                $detalle->sueldo_liquido = $totalPago_liquido;

                $detalle->estado = 'Validado';

                // Nuevos Campos Calculos INGRESOS
                $detalle->horas_extras_d = $horas_extras_diurnas;
                $detalle->horas_extras_n = $horas_extras_nocturnas;
                $detalle->porcentaje_nocturnidad = $porcentaje_nocturnidad;
                $detalle->bonificaciones = $bonificaciones_extras;
                $detalle->vacaciones = $vacaciones_extras;
                $detalle->reintegro_salarial = $reintegro_salarial_extras;
                $detalle->asueto = $asueto_extras;
                $detalle->otros_ingresos = $otros_ingresos_extras;

                // Nuevos Campos Calculos DESCUENTOS
                $detalle->anticipo_sueldo = $anticipo_sueldo_extras ;
                $detalle->prestamos_empresarial = $prestamos_empresarial_extras;
                $detalle->otros_descuentos = $otros_descuentos_extras;


                // Nuevos Campos Calculos Totales
                $detalle->total_ingresos_generales = $totalPago;
                $detalle->total_descuentos_generales = $totalFinal_descuentos_generales + ($calculoafp + $calculoseguro + $rentatotal);

                // TOTAL FINAL
                $total_calculos_finales = $totalFinal_descuentos_generales + ($calculoafp + $calculoseguro + $rentatotal);
                $detalle->total_pagar = $totalPago - $total_calculos_finales;

                
                $detalle->save();
    
    
                $this->emit('item-updated', 'Valor actualizado con exito');
                $this->ResetInt();
            }
            elseif($this->tipo_pago == 'semanal' && $dias_trabajados <= 7 ){
                $detalle = DetallePlanilladb::find($id);
                $salarioDiario = $detalle->sueldo_base / 7;

                // CALCULOS DE SALARIO TOTAL  

                // -> Ingresos (sumaran al salario)
                $ingresos_generales=[
                    $horas_extras_diurnas = $this->horasExtrasD[$id],
                    $horas_extras_nocturnas = $this->horasExtrasN[$id],
                    $porcentaje_nocturnidad = $this->porcentajeNocturnidad[$id],
                    $bonificaciones_extras = $this->bonificaciones[$id],
                    $vacaciones_extras = $this->vacaciones[$id],
                    $reintegro_salarial_extras = $this->reintegroSalarial[$id],
                    $asueto_extras  = $this->asueto[$id],
                    $otros_ingresos_extras  = $this->otrosIngresos[$id]
                ];

                $totalFinal_ingresos_generales = array_sum($ingresos_generales);

                // -> Descuentos (restara al salario)
                $descuentos_generales=[
                    $anticipo_sueldo_extras   = $this->anticipoSueldo[$id],
                    $prestamos_empresarial_extras   = $this->prestamosEmpresarial[$id],
                    $otros_descuentos_extras   = $this->otrosDescuentos[$id],
                ];

                $totalFinal_descuentos_generales = array_sum($descuentos_generales);

                $totalPago_liquido = $salarioDiario * $dias_trabajados;
    
                $totalPago = $salarioDiario * $dias_trabajados + $totalFinal_ingresos_generales;

                $seguro = Retenciones::where('nombre', 'isss')->first();

                if($totalPago >= 1000){
                    $calculoseguro = 30.0;
                }
                else{
                    $calculoseguro =  $totalPago * floatval($seguro->porcentaje)/ 100;
                }
    
    
                $afp = Retenciones::where('nombre', 'afp')->first();
                $calculoafp =  $totalPago * floatval($afp->porcentaje) / 100;
    
                $deducciones = $totalPago - $calculoafp;
    
                // Calculo de renta por tipo de pago
    
                $renta = RetencionesRenta::where('tipopago', $this->tipo_pago)
                ->where('retenciondesde', '<=', $deducciones)
                ->where('retencionhasta', '>=', $deducciones)
                ->first();
    
    
                if($renta->exceso == 0.0){
                    $rentatotal = 0.0;
                }
                else{
                    $excesoapagar = ($deducciones - $calculoseguro) - $renta->exceso;
    
                    $impuesto = $excesoapagar * ($renta->porcentaje / 100);
                    $rentatotal = $impuesto + $renta->cuotafija;
                }
                // $totalDescuento = $detalle->sueldo_base - $totalPago;
    
                $total_final = $totalPago - ($calculoafp + $calculoseguro + $rentatotal);
                
    
                $detalle->dias_trabajados = $dias_trabajados;
                $detalle->total_descuento = $calculoafp + $calculoseguro + $rentatotal;
                $detalle->seguro = $calculoseguro;
                $detalle->afp = $calculoafp;
                $detalle->renta= $rentatotal;
                $detalle->sueldo_liquido = $totalPago_liquido;

                $detalle->estado = 'Validado';

                // Nuevos Campos Calculos INGRESOS
                $detalle->horas_extras_d = $horas_extras_diurnas;
                $detalle->horas_extras_n = $horas_extras_nocturnas;
                $detalle->porcentaje_nocturnidad = $porcentaje_nocturnidad;
                $detalle->bonificaciones = $bonificaciones_extras;
                $detalle->vacaciones = $vacaciones_extras;
                $detalle->reintegro_salarial = $reintegro_salarial_extras;
                $detalle->asueto = $asueto_extras;
                $detalle->otros_ingresos = $otros_ingresos_extras;

                // Nuevos Campos Calculos DESCUENTOS
                $detalle->anticipo_sueldo = $anticipo_sueldo_extras ;
                $detalle->prestamos_empresarial = $prestamos_empresarial_extras;
                $detalle->otros_descuentos = $otros_descuentos_extras;


                // Nuevos Campos Calculos Totales
                $detalle->total_ingresos_generales = $totalPago;
                $detalle->total_descuentos_generales = $totalFinal_descuentos_generales + ($calculoafp + $calculoseguro + $rentatotal);

                // TOTAL FINAL
                $total_calculos_finales = $totalFinal_descuentos_generales + ($calculoafp + $calculoseguro + $rentatotal);
                $detalle->total_pagar = $totalPago - $total_calculos_finales;

                
                $detalle->save();
    
    
                $this->emit('item-updated', 'Valor actualizado con exito');
                $this->ResetInt();
            }
            else{
                $detalle = DetallePlanilladb::find($id);
                $salarioDiario = $detalle->sueldo_base / 30;
                $totalPago = $salarioDiario * $dias_trabajados;
            }

            $detalle = DetallePlanilladb::find($id);
            $this->mount($detalle->planilla_encabezado);

        }else{
            $this->emit('item-error', 'Los dias no pueden ser mayores a 31');
            $this->ResetInt();
        }
    }


    public function updateDiasEdit($id){

        // Condiciones de dias trabajados
        $dias_trabajados = $this->diasTrabajados[$id];

        if($dias_trabajados <= 31){
            // Calculo de dias trabajados
            if($this->tipo_pago == 'mensual' && $dias_trabajados <= 31){
                $detalle = DetallePlanilladb::find($id);
                $salarioDiario = $detalle->sueldo_base / 30;
                // CALCULOS DE SALARIO TOTAL  

                // -> Ingresos (sumaran al salario)
                $ingresos_generales=[
                    $horas_extras_diurnas = $this->horasExtrasD[$id],
                    $horas_extras_nocturnas = $this->horasExtrasN[$id],
                    $porcentaje_nocturnidad = $this->porcentajeNocturnidad[$id],
                    $bonificaciones_extras = $this->bonificaciones[$id],
                    $vacaciones_extras = $this->vacaciones[$id],
                    $reintegro_salarial_extras = $this->reintegroSalarial[$id],
                    $asueto_extras  = $this->asueto[$id],
                    $otros_ingresos_extras  = $this->otrosIngresos[$id]
                ];

                $totalFinal_ingresos_generales = array_sum($ingresos_generales);

                // -> Descuentos (restara al salario)
                $descuentos_generales=[
                    $anticipo_sueldo_extras   = $this->anticipoSueldo[$id],
                    $prestamos_empresarial_extras   = $this->prestamosEmpresarial[$id],
                    $otros_descuentos_extras   = $this->otrosDescuentos[$id],
                ];

                $totalFinal_descuentos_generales = array_sum($descuentos_generales);

                $totalPago_liquido = $salarioDiario * $dias_trabajados;
    
                $totalPago = $salarioDiario * $dias_trabajados + $totalFinal_ingresos_generales;

                // if($totalPago >= 1000){
                //     $calculoseguro = 30.0;
                // }
                // else{
                //     $calculoseguro =  $totalPago * floatval($this->seguro[$id])/ 100;
                // }

                $calculoseguro = floatval($this->seguro[$id]);

                // $afp = Retenciones::where('nombre', 'afp')->first();
                // $calculoafp =  $totalPago * floatval($this->afp[$id]) / 100;

                $calculoafp =  floatval($this->afp[$id]);
                $deducciones = $totalPago - $this->afp[$id];
    
                // Calculo de renta por tipo de pago
    
                // $renta = RetencionesRenta::where('tipopago', $this->tipo_pago)
                // ->where('retenciondesde', '<=', $deducciones)
                // ->where('retencionhasta', '>=', $deducciones)
                // ->first();
    
    
                // if($renta->exceso == 0.0){
                //     $rentatotal = 0.0;
                // }
                // else{
                //     $excesoapagar = ($deducciones - $calculoseguro) - $renta->exceso;
    
                //     $impuesto = $excesoapagar * ($renta->porcentaje / 100);
                //     $rentatotal = $impuesto + $renta->cuotafija;
                // }
                $rentatotal =  floatval($this->renta[$id]);
                // $totalDescuento = $detalle->sueldo_base - $totalPago;
    
                $total_final = $totalPago - ($this->afp[$id] + $this->seguro[$id] + $rentatotal);
                
    
                $detalle->dias_trabajados = $dias_trabajados;
                $detalle->total_descuento = $calculoafp + $calculoseguro + $rentatotal;
                $detalle->seguro = $this->seguro[$id];
                $detalle->afp = $this->afp[$id];
                $detalle->renta= $rentatotal;
                $detalle->sueldo_liquido = $totalPago_liquido;

                $detalle->estado = 'Validado';

                // Nuevos Campos Calculos INGRESOS
                $detalle->horas_extras_d = $horas_extras_diurnas;
                $detalle->horas_extras_n = $horas_extras_nocturnas;
                $detalle->porcentaje_nocturnidad = $porcentaje_nocturnidad;
                $detalle->bonificaciones = $bonificaciones_extras;
                $detalle->vacaciones = $vacaciones_extras;
                $detalle->reintegro_salarial = $reintegro_salarial_extras;
                $detalle->asueto = $asueto_extras;
                $detalle->otros_ingresos = $otros_ingresos_extras;

                // Nuevos Campos Calculos DESCUENTOS
                $detalle->anticipo_sueldo = $anticipo_sueldo_extras ;
                $detalle->prestamos_empresarial = $prestamos_empresarial_extras;
                $detalle->otros_descuentos = $otros_descuentos_extras;


                // Nuevos Campos Calculos Totales
                $detalle->total_ingresos_generales = $totalPago;
                $detalle->total_descuentos_generales = $totalFinal_descuentos_generales + ($calculoafp + $calculoseguro + $rentatotal);

                // TOTAL FINAL
                $total_calculos_finales = $totalFinal_descuentos_generales + ($calculoafp + $calculoseguro + $rentatotal);
                $detalle->total_pagar = $totalPago - $total_calculos_finales;

                
                $detalle->save();
    
    
                $this->emit('item-updated', 'Valor actualizado con exito');
                $this->ResetInt();
            }
            elseif($this->tipo_pago == 'quincenal' && $dias_trabajados <= 15){
                $detalle = DetallePlanilladb::find($id);
                $salarioDiario = $detalle->sueldo_base / 15;

                // CALCULOS DE SALARIO TOTAL  

                // -> Ingresos (sumaran al salario)
                $ingresos_generales=[
                    $horas_extras_diurnas = $this->horasExtrasD[$id],
                    $horas_extras_nocturnas = $this->horasExtrasN[$id],
                    $porcentaje_nocturnidad = $this->porcentajeNocturnidad[$id],
                    $bonificaciones_extras = $this->bonificaciones[$id],
                    $vacaciones_extras = $this->vacaciones[$id],
                    $reintegro_salarial_extras = $this->reintegroSalarial[$id],
                    $asueto_extras  = $this->asueto[$id],
                    $otros_ingresos_extras  = $this->otrosIngresos[$id]
                ];

                $totalFinal_ingresos_generales = array_sum($ingresos_generales);

                // -> Descuentos (restara al salario)
                $descuentos_generales=[
                    $anticipo_sueldo_extras   = $this->anticipoSueldo[$id],
                    $prestamos_empresarial_extras   = $this->prestamosEmpresarial[$id],
                    $otros_descuentos_extras   = $this->otrosDescuentos[$id],
                ];

                $totalFinal_descuentos_generales = array_sum($descuentos_generales);

                $totalPago_liquido = $salarioDiario * $dias_trabajados;
    
                $totalPago = $salarioDiario * $dias_trabajados + $totalFinal_ingresos_generales;

                // if($totalPago >= 1000){
                //     $calculoseguro = 30.0;
                // }
                // else{
                //     $calculoseguro =  $totalPago * floatval($this->seguro[$id])/ 100;
                // }


                // el calculo sera manual
                $calculoseguro = floatval($this->seguro[$id]);


                // $afp = Retenciones::where('nombre', 'afp')->first();
                // $calculoafp =  $totalPago * floatval($this->afp[$id]) / 100;

                // el calculo de la AFP sera manual
                $calculoafp =  floatval($this->afp[$id]);

                $deducciones = $totalPago - $this->afp[$id];
    
                // Calculo de renta por tipo de pago
    
                // $renta = RetencionesRenta::where('tipopago', $this->tipo_pago)
                // ->where('retenciondesde', '<=', $deducciones)
                // ->where('retencionhasta', '>=', $deducciones)
                // ->first();
    
    
                // if($renta->exceso == 0.0){
                //     $rentatotal = 0.0;
                // }
                // else{
                //     $excesoapagar = ($deducciones - $calculoseguro) - $renta->exceso;
    
                //     $impuesto = $excesoapagar * ($renta->porcentaje / 100);
                //     $rentatotal = $impuesto + $renta->cuotafija;
                // }
                // $totalDescuento = $detalle->sueldo_base - $totalPago;
                $rentatotal =  floatval($this->renta[$id]);
                $total_final = $totalPago - ($this->afp[$id] + $this->seguro[$id] + $rentatotal);
                
    
                $detalle->dias_trabajados = $dias_trabajados;
                $detalle->total_descuento = $calculoafp + $calculoseguro + $rentatotal;
                $detalle->seguro = $this->seguro[$id];
                $detalle->afp = $this->afp[$id];
                $detalle->renta= $rentatotal;
                $detalle->sueldo_liquido = $totalPago_liquido;

                $detalle->estado = 'Validado';

                // Nuevos Campos Calculos INGRESOS
                $detalle->horas_extras_d = $horas_extras_diurnas;
                $detalle->horas_extras_n = $horas_extras_nocturnas;
                $detalle->porcentaje_nocturnidad = $porcentaje_nocturnidad;
                $detalle->bonificaciones = $bonificaciones_extras;
                $detalle->vacaciones = $vacaciones_extras;
                $detalle->reintegro_salarial = $reintegro_salarial_extras;
                $detalle->asueto = $asueto_extras;
                $detalle->otros_ingresos = $otros_ingresos_extras;

                // Nuevos Campos Calculos DESCUENTOS
                $detalle->anticipo_sueldo = $anticipo_sueldo_extras ;
                $detalle->prestamos_empresarial = $prestamos_empresarial_extras;
                $detalle->otros_descuentos = $otros_descuentos_extras;


                // Nuevos Campos Calculos Totales
                $detalle->total_ingresos_generales = $totalPago;
                $detalle->total_descuentos_generales = $totalFinal_descuentos_generales + ($calculoafp + $calculoseguro + $rentatotal);

                // TOTAL FINAL
                $total_calculos_finales = $totalFinal_descuentos_generales + ($calculoafp + $calculoseguro + $rentatotal);
                $detalle->total_pagar = $totalPago - $total_calculos_finales;

                
                $detalle->save();
    
    
                $this->emit('item-updated', 'Valor actualizado con exito');
                $this->ResetInt();
            }
            elseif($this->tipo_pago == 'semanal' && $dias_trabajados <= 7 ){
                $detalle = DetallePlanilladb::find($id);
                $salarioDiario = $detalle->sueldo_base / 7;
                // CALCULOS DE SALARIO TOTAL  

                // -> Ingresos (sumaran al salario)
                $ingresos_generales=[
                    $horas_extras_diurnas = $this->horasExtrasD[$id],
                    $horas_extras_nocturnas = $this->horasExtrasN[$id],
                    $porcentaje_nocturnidad = $this->porcentajeNocturnidad[$id],
                    $bonificaciones_extras = $this->bonificaciones[$id],
                    $vacaciones_extras = $this->vacaciones[$id],
                    $reintegro_salarial_extras = $this->reintegroSalarial[$id],
                    $asueto_extras  = $this->asueto[$id],
                    $otros_ingresos_extras  = $this->otrosIngresos[$id]
                ];

                $totalFinal_ingresos_generales = array_sum($ingresos_generales);

                // -> Descuentos (restara al salario)
                $descuentos_generales=[
                    $anticipo_sueldo_extras   = $this->anticipoSueldo[$id],
                    $prestamos_empresarial_extras   = $this->prestamosEmpresarial[$id],
                    $otros_descuentos_extras   = $this->otrosDescuentos[$id],
                ];

                $totalFinal_descuentos_generales = array_sum($descuentos_generales);

                $totalPago_liquido = $salarioDiario * $dias_trabajados;
    
                $totalPago = $salarioDiario * $dias_trabajados + $totalFinal_ingresos_generales;

                // if($totalPago >= 1000){
                //     $calculoseguro = 30.0;
                // }
                // else{
                //     $calculoseguro =  $totalPago * floatval($this->seguro[$id])/ 100;
                // }

                $calculoseguro = floatval($this->seguro[$id]);

                // $afp = Retenciones::where('nombre', 'afp')->first();
                // $calculoafp =  $totalPago * floatval($this->afp[$id]) / 100;

                $calculoafp =  floatval($this->afp[$id]);
                $deducciones = $totalPago - $this->afp[$id];
    
                // Calculo de renta por tipo de pago
    
                $renta = RetencionesRenta::where('tipopago', $this->tipo_pago)
                ->where('retenciondesde', '<=', $deducciones)
                ->where('retencionhasta', '>=', $deducciones)
                ->first();
    
    
                // if($renta->exceso == 0.0){
                //     $rentatotal = 0.0;
                // }
                // else{
                //     $excesoapagar = ($deducciones - $calculoseguro) - $renta->exceso;
    
                //     $impuesto = $excesoapagar * ($renta->porcentaje / 100);
                //     $rentatotal = $impuesto + $renta->cuotafija;
                // }
                // $totalDescuento = $detalle->sueldo_base - $totalPago;
                $rentatotal =  floatval($this->renta[$id]);
                $total_final = $totalPago - ($this->afp[$id] + $this->seguro[$id] + $rentatotal);
                
    
                $detalle->dias_trabajados = $dias_trabajados;
                $detalle->total_descuento = $calculoafp + $calculoseguro + $rentatotal;
                $detalle->seguro = $this->seguro[$id];
                $detalle->afp = $this->afp[$id];
                $detalle->renta= $rentatotal;
                $detalle->sueldo_liquido = $totalPago_liquido;

                $detalle->estado = 'Validado';

                // Nuevos Campos Calculos INGRESOS
                $detalle->horas_extras_d = $horas_extras_diurnas;
                $detalle->horas_extras_n = $horas_extras_nocturnas;
                $detalle->porcentaje_nocturnidad = $porcentaje_nocturnidad;
                $detalle->bonificaciones = $bonificaciones_extras;
                $detalle->vacaciones = $vacaciones_extras;
                $detalle->reintegro_salarial = $reintegro_salarial_extras;
                $detalle->asueto = $asueto_extras;
                $detalle->otros_ingresos = $otros_ingresos_extras;

                // Nuevos Campos Calculos DESCUENTOS
                $detalle->anticipo_sueldo = $anticipo_sueldo_extras ;
                $detalle->prestamos_empresarial = $prestamos_empresarial_extras;
                $detalle->otros_descuentos = $otros_descuentos_extras;


                // Nuevos Campos Calculos Totales
                $detalle->total_ingresos_generales = $totalPago;
                $detalle->total_descuentos_generales = $totalFinal_descuentos_generales + ($calculoafp + $calculoseguro + $rentatotal);

                // TOTAL FINAL
                $total_calculos_finales = $totalFinal_descuentos_generales + ($calculoafp + $calculoseguro + $rentatotal);
                $detalle->total_pagar = $totalPago - $total_calculos_finales;

                
                $detalle->save();
    
    
                $this->emit('item-updated', 'Valor actualizado con exito');
                $this->ResetInt();
            }
            else{
                $detalle = DetallePlanilladb::find($id);
                $salarioDiario = $detalle->sueldo_base / 30;
                $totalPago = $salarioDiario * $dias_trabajados;
            }
            $detalle = DetallePlanilladb::find($id);
            $this->mount($detalle->planilla_encabezado);

        }else{
            $this->emit('item-error', 'Los dias no pueden ser mayores a 31');
            $this->ResetInt();
        }
    }

    public function resetProcess()
    {
        return redirect()->route('planilla');
    }


    public function ResetInt()
    {
        $this->selected_id = 0;
        $this->resetValidation();
        $this->resetPage();
    }
}
