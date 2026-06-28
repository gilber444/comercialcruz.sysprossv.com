<?php



use App\Http\Controllers\ExportController;

use App\Http\Controllers\LibroContribuyente;

use App\Http\Controllers\LibroInvalidacion;

use App\Http\Controllers\LibrosConsumidor;

use App\Http\Livewire\AbonosController;

use App\Http\Livewire\ActividadEconomicaController; 

use App\Http\Livewire\ActividadesController;

use App\Http\Livewire\AjustesController;

use App\Http\Livewire\AmbienteDestinoController;

use App\Http\Livewire\ApisController;

use App\Http\Livewire\AsignarController;

use App\Http\Livewire\BancosController;

use App\Http\Livewire\CatalagosController;

use App\Http\Livewire\CatalagosEstructuraController;

use App\Http\Livewire\CategoriasController;

use App\Http\Livewire\ClaseDocumentoController;

use App\Http\Livewire\ClientesController;

use App\Http\Livewire\ConciliacionInventarioController;

use App\Http\Livewire\ReconciliarSincroIdController;

use App\Http\Livewire\ComprasController;

use App\Http\Livewire\CondicionOperacionController;

use App\Http\Livewire\ContingenciaDTEController;

use App\Http\Livewire\CortesController;

use App\Http\Livewire\CotizacionesController;

use App\Http\Livewire\CuentasCobrarController;

use App\Http\Livewire\CuentasPagarController;

use App\Http\Livewire\DepartamentosController;

use App\Http\Livewire\DistritosController;

use App\Http\Livewire\DocumentosAsociadosController;

use App\Http\Livewire\DomicilioFiscalController;

use App\Http\Livewire\DonacionController;

use App\Http\Livewire\DteController;

use App\Http\Livewire\EditarCompra;

use App\Http\Livewire\EditarCompraController;

use App\Http\Livewire\EditarHojaController;

use App\Http\Livewire\EditarProductoController;

use App\Http\Livewire\EditarSolicitudController;

use App\Http\Livewire\EmpresaController;

use App\Http\Livewire\FeaturesController;

use App\Http\Livewire\ExistenciasController;

use App\Http\Livewire\FamiliasController;

use App\Http\Livewire\FirmadorController;

use App\Http\Livewire\FormaPagosController;

use App\Http\Livewire\HojaInventario;

use App\Http\Livewire\IdentificacionReceptorController;

use App\Http\Livewire\IncotermsController;

use App\Http\Livewire\InvalidacionesDTEController;

use App\Http\Livewire\KardexController;

use App\Http\Livewire\LibroContribuyenteController;

use App\Http\Livewire\LibroInvalidacionesController;

use App\Http\Livewire\LibroVentasConsumidorController;

use App\Http\Livewire\LotedteController;

use App\Http\Livewire\MedidasController;

use App\Http\Livewire\MensajeriaTicket;

use App\Http\Livewire\ModalNota;

use App\Http\Livewire\ModeloFacturacionController;

use App\Http\Livewire\MunicipiosController;

use App\Http\Livewire\NotaCreditoController;

use App\Http\Livewire\NuevaCompraController;

use App\Http\Livewire\NuevaCotizacionController;

use App\Http\Livewire\NuevaHojaController;

use App\Http\Livewire\NuevaNotaCreditoController;

use App\Http\Livewire\NuevoAjustesController;

use App\Http\Livewire\NuevoSujetoExcluidoController;

use App\Http\Livewire\PagosController;

use App\Http\Livewire\PaisController;

use App\Http\Livewire\ParametrosController;

use App\Http\Livewire\PermisosController;

use App\Http\Livewire\PlazoController;

use App\Http\Livewire\PosController;

use App\Http\Livewire\PrecompraController;

use App\Http\Livewire\ProcesaController;

use App\Http\Livewire\ProductoAdminController;

use App\Http\Livewire\ProductosController;

use App\Http\Livewire\ProveedoresController;

use App\Http\Livewire\Pruebas;

use App\Http\Livewire\RemesasController;

use App\Http\Livewire\RecintoFiscalController;

use App\Http\Livewire\RegimenController;

use App\Http\Livewire\ReportArqueosController;

use App\Http\Livewire\ReportComprasController;

use App\Http\Livewire\ReportCortesZController;

use App\Http\Livewire\ReportInventarioCategoriaController;

use App\Http\Livewire\ReportInventarioController;

use App\Http\Livewire\ReportUtilidadController;

use App\Http\Livewire\ReportUtilidadSinController;

use App\Http\Livewire\ReportVentasController;

use App\Http\Livewire\ReportVentasSintetizadoController;

use App\Http\Livewire\RetencionController;

use App\Http\Livewire\RolesController;

use App\Http\Livewire\ServicioMedicoController;

use App\Http\Livewire\SolicitudesController;

use App\Http\Livewire\SucursalesController;

use App\Http\Livewire\SujetoExcluidosController;

use App\Http\Livewire\TipoContigenciaController;

use App\Http\Livewire\TipoDocumentoController;

use App\Http\Livewire\TipoEstablecimientoController;

use App\Http\Livewire\TipoGeneracionDocumentoController;

use App\Http\Livewire\TipoInvalidacionController;

use App\Http\Livewire\TipoItemController;

use App\Http\Livewire\TipoPagosController;

use App\Http\Livewire\TipoPersonaController;

use App\Http\Livewire\TipoTransmisionController;

use App\Http\Livewire\TituloRemiteBienesController;

use App\Http\Livewire\TockenController;

use App\Http\Livewire\TransporteController;

use App\Http\Livewire\TributoController;

use App\Http\Livewire\UbicacionesController;

use App\Http\Livewire\UnidadMedidaController;

use App\Http\Livewire\UsersController;

use App\Http\Livewire\VerSolicitudesController;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Route;



/*

|--------------------------------------------------------------------------

| Web Routes

|--------------------------------------------------------------------------

|

| Here is where you can register web routes for your application. These

| routes are loaded by the RouteServiceProvider and all of them will

| be assigned to the "web" middleware group. Make something great!

|

*/



Route::get('/', function () {

    return view('home');

})->middleware('auth');



Auth::routes();



Route::middleware(['auth'])->group(function () {

    Route::get('pruebas', Pruebas::class)->name('pruebas');

    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::get('/home/{imagen}', [UsersController::class, 'renderImagen'])->name('home.mostrar');

    Route::get('users', UsersController::class)->name('users')->can('User_Index');

    Route::get('/users/{imagen}', [UsersController::class, 'renderImagen'])->name('users.mostrar');

    Route::get('roles', RolesController::class)->name('roles')->can('Roles_Index');

    Route::get('permisos', PermisosController::class)->name('permisos')->can('Permisos_Index');

    Route::get('asignar', AsignarController::class)->name('asignar')->can('Asignar_Index');

    Route::get('ubicaciones', UbicacionesController::class)->name('ubicaciones')->can('Ubicaciones_Index');

    Route::get('empresa', EmpresaController::class)->name('empresa')->can('Empresa_Index');

    Route::get('sucursales', SucursalesController::class)->name('sucursales')->can('Sucursales_Index');

    Route::get('parametros', ParametrosController::class)->name('parametros')->can('Parametros_Index');
    Route::get('features', FeaturesController::class)->name('features')->can('Features_Index');

    Route::get('/empresa/{imagen}', [EmpresaController::class, 'renderImagen'])->name('empresa.mostrar');

    Route::get('productoAdmin', ProductoAdminController::class)->name('productoAdmin')->can('Admin_Productos');

    Route::get('/productoAdmin/{imagen}', [ProductoAdminController::class, 'renderImagen'])->name('productoAdmin.mostrar');

    Route::get('medidas', MedidasController::class)->name('medidas')->can('Medidas_Index');

    Route::get('categorias', CategoriasController::class)->name('categorias')->can('Categorias_Index');

    Route::get('familia', FamiliasController::class)->name('familia');

    //->can('Familia_Productos');

    Route::get('productos', ProductosController::class)->name('productos')->can('Productos_Index');

    Route::get('editarProduct/{id}', EditarProductoController::class)->name('editarProduct')->can('Productos_Update');

    Route::get('existencias', ExistenciasController::class)->name('existencias')->can('Existencias_Index');

    Route::get('solicitudes', SolicitudesController::class)->name('solicitudes')->can('Solicitudes_Create');

    Route::get('solicitudesVer', VerSolicitudesController::class)->name('solicitudesVer')->can('Solicitudes_Index');

    Route::get('editar-solicitud/{id}', EditarSolicitudController::class)->name('editar-solicitud')->can('Solicitudes_Update');

    Route::get('compras', ComprasController::class)->name('compras')->can('Compras_Index');

    Route::get('nueva-compra', NuevaCompraController::class)->name('nueva-comra')->can('Compras_Create');

    Route::get('editarCompra/{id}', EditarCompraController::class)->name('editarCompra')->can('Compras_Update');

    Route::get('estructuras', CatalagosEstructuraController::class)->name('estructuras')->can('Estructura_Index');

    Route::get('catalagos', CatalagosController::class)->name('catalagos')->can('Catalagos_Index');

    Route::get('pos', PosController::class)->name('pos')->can('Pos_Index');

    Route::get('tipoPagos', TipoPagosController::class)->name('tipoPagos')->can('TipoPagos_Index');

    Route::get('actividad_economica', ActividadEconomicaController::class)->name('actividad_economica')->can('ActividadEconomica_Index');

    Route::get('pais', PaisController::class)->name('pais')->can('Pais_Index');

    Route::get('departamentos', DepartamentosController::class)->name('departamentos')->can('Departamentos_Index');

    Route::get('municipios', MunicipiosController::class)->name('municipios')->can('Municipios_Index');

    Route::get('distritos', DistritosController::class)->name('distritos')->can('Distritos_Index');

    Route::get('ambiente_destinos', AmbienteDestinoController::class)->name('ambiente_destinos')->can('AmbienteDestino_Index');

    Route::get('tipo_documentos', TipoDocumentoController::class)->name('tipo_documentos')->can('TipoDocumentos_Index');

    Route::get('modelo_facturacion', ModeloFacturacionController::class)->name('modelo_facturacion')->can('ModeloFacturacion_Index');

    Route::get('tipo_transmision', TipoTransmisionController::class)->name('tipo_transmision')->can('TipoTransmision_Index');

    Route::get('tipo_contingencias', TipoContigenciaController::class)->name('tipo_contingencias')->can('TipoContingencias');

    Route::get('retencion', RetencionController::class)->name('retencion')->can('Retencion_Index');

    Route::get('tipo_generacion_documento', TipoGeneracionDocumentoController::class)->name('tipo_generacion_documento')->can('TipoGeneracion_Index');

    Route::get('tipo_establecimiento', TipoEstablecimientoController::class)->name('tipo_establecimiento')->can('TipoEstablecimiento_Index');

    Route::get('servicio_medico', ServicioMedicoController::class)->name('servicio_medico')->can('ServicioMedico_Index');

    Route::get('tipo_item', TipoItemController::class)->name('tipo_item')->can('TipoItem_Index');

    Route::get('unidad_medida', UnidadMedidaController::class)->name('unidad_medida')->can('UnidadMedida_Index');

    Route::get('tributos', TributoController::class)->name('tributos')->can('Tributos_Index');

    Route::get('condicion_operacion', CondicionOperacionController::class)->name('condicion_operacion')->can('CondicionOperacion_Index');

    Route::get('forma_pago', FormaPagosController::class)->name('forma_pago')->can('FormaPagos_Index');

    Route::get('plazos', PlazoController::class)->name('plazos')->can('Plazos_Index');

    Route::get('documentos_asociados', DocumentosAsociadosController::class)->name('documentos_asociados')->can('DocumentoAsociado_Index');

    Route::get('identificacion_receptor', IdentificacionReceptorController::class)->name('identificacion_receptor')->can('IdentificacionReceptor_Index');

    Route::get('tipo_invalidacion', TipoInvalidacionController::class)->name('tipo_invalidacion')->can('TipoInvalidacion_Index');

    Route::get('titulo_remiten_bienes', TituloRemiteBienesController::class)->name('titulo_remiten_bienes')->can('TituloRemiteBienes_Index');

    Route::get('donacion', DonacionController::class)->name('donacion')->can('Donacion_Index');

    Route::get('recinto_fiscal', RecintoFiscalController::class)->name('recinto_fiscal')->can('RecintoFiscal_Index');

    Route::get('regimen', RegimenController::class)->name('regimen')->can('Regimen_Index');

    Route::get('tipo_persona', TipoPersonaController::class)->name('tipo_persona')->can('TipoPersona_Index');

    Route::get('transporte', TransporteController::class)->name('transporte')->can('Transporte_Index');

    Route::get('incoterms', IncotermsController::class)->name('incoterms')->can('Incoterms_Index');

    Route::get('domicilio_fiscal', DomicilioFiscalController::class)->name('domicilio_fiscal')->can('DomicilioFiscal_Index');

    Route::get('proveedores', ProveedoresController::class)->name('proveedores')->can('Proveedores_Index');

    Route::get('clientes', ClientesController::class)->name('clientes')->can('Clientes_Index');

    Route::get('firmador', FirmadorController::class)->name('firmador')->can('Firmador_Index');

    Route::get('tocken', TockenController::class)->name('tocken')->can('Tocken_Index');

    Route::get('ap', ApisController::class)->name('ap')->can('Apis_Index');

    Route::get('dtes', DteController::class)->name('dtes')->can('DTE_Index');

    Route::get('/json/{id}', [DteController::class, 'show'])->name('json.show')->can('DTE_Index');

    Route::get('/json/download/{id}', [DteController::class, 'download'])->name('json.download');

    Route::get('report/pdf/{id}', [ExportController::class, 'reportPDF'])->name('reportPDF');

    Route::get('invalidaciones', InvalidacionesDTEController::class)->name('invalidaciones')->can('Invalidaciones_Index');

    Route::get('lote', LotedteController::class)->name('lote')->can('Lote_Index');

    Route::get('contingencia', ContingenciaDTEController::class)->name('contingencia')->can('Contingencias_Index');

    Route::get('actividad', ActividadesController::class)->name('actividad')->can('Actividades_Index');

    Route::get('precompra', PrecompraController::class)->name('precompra')->can('Precompra_Index');

    Route::get('procesar/{id}', ProcesaController::class)->name('procesar')->can('Precompra_Update');

    Route::get('reportsVenta', ReportVentasController::class)->can('ReportVentas_Index')->name('reportsVenta');

    Route::get('reportsCompra', ReportComprasController::class)->can('ReportCompras_Index')->name('reportsCompra');

    Route::get('reportsInventario', ReportInventarioController::class)->can('ReporteInventario_Index')->name('reportsInventario');

    Route::get('reportsInventarioCategorias', ReportInventarioCategoriaController::class)->can('ReporteInventarioCategorias_Index')->name('reportsInventarioCategorias');

    Route::get('reportArqueos', ReportArqueosController::class)->can('ReportArqueos_Index')->name('reportArqueos');

    Route::get('reportCortesZ', ReportCortesZController::class)->can('ReportCortesZ_Index')->name('reportCortesZ');



    ////Reportes Ventas//////

    Route::get('report/pdfVentas/{sucursal}/{type}/{f1}/{f2}', [ExportController::class, 'reportPDFVentas'])->can('ReportVentasPDF_Print');

    Route::get('report/pdfVentas/{sucursal}/{type}', [ExportController::class, 'reportPDFVentas'])->can('ReportVentasPDF_Print');



    Route::get('report/excelVentas/{sucursal}/{type}/{f1}/{f2}', [ExportController::class, 'reportExcelVentas'])->can('ReportVentasEXCEL_Print');

    Route::get('report/excelVentas/{sucursal}/{type}', [ExportController::class, 'reportExcelVentas'])->can('ReportVentasEXCEL_Print');



    ////Reportes Compras//////

    Route::get('report/pdfCompras/{proveedor}/{type}/{f1}/{f2}', [ExportController::class, 'reportPDFCompras'])->can('ReportComprasPDF_Print');

    // Route::get('report/pdfCompras/{proveedor}/{type}', [ExportController::class, 'reportPDFCompras'])->can('ReportComprasPDF_Print');



    Route::get('report/excelCompras/{proveedor}/{type}/{f1}/{f2}', [ExportController::class, 'reportExcelCompras'])->can('ReportComprasEXCEL_Print');

    //Route::get('report/excelCompras/{proveedor}/{type}', [ExportController::class, 'reportExcelCompras'])->can('ReportComprasEXCEL_Print');



    //////////Reporte Inventario///////////////////

    Route::get('report/pdfInventario/{sucursal}', [ExportController::class, 'reportPDFInventario'])->can('ReportInventarioPDF_Print');



    Route::get('report/excelInventario/{sucursal}', [ExportController::class, 'reportExcelInventario'])->can('ReportInventarioEXCEL_Print');



    //////////Reporte de Inventario por Categorias/////////////

    Route::get('report/pdfInventarioCategoria/{sucursal}/{categoria}', [ExportController::class, 'reportPDFInventarioCategoria'])->can('ReporteInventarioCategoriaPDF_Print');



    Route::get('report/excelInventarioCategoria/{sucursal}/{categoria}', [ExportController::class, 'reportExcelInventarioCategoria'])->can('ReporteInventarioCategoriaEXCEL_Print');



    //////Reporte Arqueos/////////////

    Route::get('report/pdfArqueos/{sucursal}/{caja}/{user}/{f1}/{f2}', [ExportController::class, 'reportPDFArqueos'])->can('ReportArqueosPDF_Print')->name('pdfArqueos');



    Route::get('report/excelArqueos/{sucursal}/{caja}/{user}/{f1}/{f2}', [ExportController::class, 'reportExcelArqueos'])->can('ReportArqueosEXCEL_Print')->name('ExcelArqueos');



    ////Reporte CortesZ////////////////////////////

    Route::get('report/pdfCorteZ/{sucursal}/{caja}/{f1}/{f2}', [ExportController::class, 'reportPDFCorteZ'])->can('ReportCorteZPDF_Print')->name('pdfCorteZ');



    Route::get('report/excelCorteZ/{sucursal}/{caja}/{f1}/{f2}', [ExportController::class, 'reportExcelCorteZ'])->can('ReportCorteZEXCEL_Print')->name('ExcelCorteZ');



    Route::get('clase_documento', ClaseDocumentoController::class)->can('ClaseDocumento_Index')->name('clase_documento');



    Route::get('libroVentasConsumidor', LibroVentasConsumidorController::class)->can('LibroVentasConsumidor_Index')->name('libroVentasConsumidor');



    Route::get('bancos', BancosController::class)->can('Bancos_Index')->name('bancos');



    Route::get('kardex', KardexController::class)->middleware('auth')->name('kardex')->can('Kardex_Index');

    Route::get('conciliacion-inventario', ConciliacionInventarioController::class)->middleware('auth')->name('conciliacion-inventario')->can('ConciliacionInventario_Index');

    Route::get('reconciliar-sincro-id', ReconciliarSincroIdController::class)->middleware('auth')->name('reconciliar-sincro-id')->can('ReconciliarSincroId_Index');

    Route::get('comparar-inventario', \App\Http\Livewire\CompararInventarioController::class)->middleware('auth')->name('comparar-inventario')->can('CompararInventario_Index');

    Route::get('ajustes', AjustesController::class)->middleware('auth')->name('ajustes')->can('Ajustes_Index');

    Route::get('nuevo-ajuste', NuevoAjustesController::class)->middleware('auth')->name('nuevo-ajuste')->can('Ajustes_Index');



    Route::get('cortes', CortesController::class)->middleware('auth')->can('Cortez_Index')->name('cortes');



    Route::get('cotizaciones', CotizacionesController::class)->middleware('auth')->name('cotizaciones')->can('Cotizaciones_Index');



    Route::get('nueva-cotizacion', NuevaCotizacionController::class)->can('Cotizaciones_Create')->name('nueva-cotizacion');



    //Route::get('report/pdf/{id}', [ExportController::class, 'reportCotizacion'])->name('reportCotizacion')->can('Cotizaciones_Print');

    Route::get('report/pdf/cotizacion/{id}', [ExportController::class, 'reportCotizacion'])

        ->name('reportCotizacion')

        ->can('Cotizaciones_Print');



    Route::get('cuentas_pagar', CuentasPagarController::class)->middleware('auth')->name('cuentas_pagar')->can('CuentasPagar_Index');



    Route::get('pagos', PagosController::class)->middleware('auth')->name('pagos')->can('Pagos_Index');



    Route::get('cuentas_cobrar', CuentasCobrarController::class)->middleware('auth')->name('cuentas_cobrar')->can('CuentasCobrar_Index');



    Route::get('abonos', AbonosController::class)->middleware('auth')->name('abonos')->can('Abonos_Index');



    Route::get('remesas', RemesasController::class, 'index')->middleware('auth')->name('remesas')->can('Remesas_Index');

    ///////////////////////////////////////////////////////////////////////

    Route::get('mensajerias', MensajeriaTicket::class)->name('mensajerias')->can('Mensajerias_Index');



    Route::get('hoja_inventarios', HojaInventario::class)->middleware('auth')->can('Inventario_Index')->name('hoja_inventarios');



    Route::get('head-hoja', NuevaHojaController::class)->name('head-hoja')->can('hojaInventario_Create');



    Route::get('editar-hoja/{hojaId}', EditarHojaController::class)->name('editar-hoja')->can('hojaInventario_Update');



    Route::get('hoja_inventarios/vistaHoja/{id}', [HojaInventario::class, 'vistaPrevia'])->name('hoja_inventarios.vistaHoja');

    Route::get('hoja_inventarios/vistaHojaCompleta/{id}', [HojaInventario::class, 'vistaPreviaLibro'])->name('hoja_inventarios.vistaHojaCompleta');

    Route::get('hoja_inventarios/pdf/{id}', [HojaInventario::class, 'pdfHoja'])->name('hoja_inventarios.pdf');

    Route::get('hoja_inventarios/pdfApertura/{id}', [HojaInventario::class, 'pdfApertura'])->name('hoja_inventarios.pdfApertura');

    Route::get('hoja_inventarios/excelHoja/{id}', [HojaInventario::class, 'excelHoja'])->name('hoja_inventarios.excelHoja');

    Route::get('hoja_inventarios/excelApertura/{id}', [HojaInventario::class, 'excelApertura'])->name('hoja_inventarios.excelApertura');

    Route::get('hoja_inventarios/noContados/{id}', [HojaInventario::class, 'noContados'])->name('hoja_inventarios.noContados');

    Route::get('hoja_inventarios/noContadosPdf/{id}', [HojaInventario::class, 'noContadosPdf'])->name('hoja_inventarios.noContadosPdf');

    Route::get('hoja_inventarios/noContadosExcel/{id}', [HojaInventario::class, 'noContadosExcel'])->name('hoja_inventarios.noContadosExcel');



    Route::get('kardex/pdf/{sucursal}/{producto}/{desde?}/{hasta?}', [KardexController::class, 'reportPDFKardex']);



    Route::get('existencias/pdf/{id}', [ExportController::class, 'ReportSolicitud'])->can('Solicitudes_Print');



    ////Reporte de utilidad //////

    Route::get('reportsUtilidad', ReportUtilidadController::class)->can('ReportUtilidad_Index')->name('reportsUtilidad');

    Route::get('report/pdfUtilidad/{sucursal}/{caja}/{type}/{facturador}/{f1?}/{f2?}', [ExportController::class, 'pdfUtilidad'])->can('ReportUtilidadPDF_Print');

    Route::get('report/excelUtilidad/{sucursal}/{caja}/{type}/{facturador}/{f1}/{f2}', [ExportController::class, 'reportExcelUtilidad'])->can('ReportUtilidadEXCEL_Print');

    Route::get('reportsUtilidadSintetizado', ReportUtilidadSinController::class)->can('ReportUtilidadSintetizado_Index')->name('reportsUtilidadSintetizado');

    Route::get('report/pdfUtilidadSin/{sucursal}/{caja}/{type}/{f1?}/{f2?}', [ExportController::class, 'pdfUtilidadSin'])->can('ReportUtilidadSinPDF_Print');

    Route::get('report/excelUtilidadSin/{sucursal}/{caja}/{type}/{f1}/{f2}', [ExportController::class, 'reportExcelUtilidadSinExcel'])->can('ReportUtilidadSinEXCEL_Print');

    //////////////////////libros de ventas y contribuyentes////////////////////

    Route::get('libroVentasConsumidor', LibroVentasConsumidorController::class)->can('LibroVentasConsumidor_Index')->name('libroVentasConsumidor');



    Route::get('libroVentasContribuyente', LibroContribuyenteController::class)->can('LibroVentasContribuyente_Index')->name('libroVentasContribuyente');



    Route::get('pdf/libroConsumidor/{empresa}/{sucursal}/{caja}/{desde}/{hasta}', [LibrosConsumidor::class, 'generarPDF'])->name('libroConsumidor')->can('GenerarLibroConsumidor_PDF');



    Route::get('pdf/libroContribuyente/{empresa}/{sucursal}/{caja}/{desde}/{hasta}', [LibroContribuyente::class, 'generarPDF'])->name('libroVentasContribuyente')->can('GenerarLibroContribuyente_PDF');





    Route::get('libroInvalidaciones', LibroInvalidacionesController::class)->can('LibroInvalidaciones_Index')->name('libroInvalidaciones');



    Route::get('pdf/libroInvalidaciones/{empresa}/{sucursal}/{facturador}/{desde}/{hasta}', [LibroInvalidacion::class, 'generarPDF'])->name('libroVentasContribuyente')->can('GenerarLibroInvalidacion_PDF');



    Route::get('sujeto-excluidos', SujetoExcluidosController::class)->middleware('auth')->name('sujeto-excluidos')->can('SujetoExcluidos_Index');



    Route::get('nuevo-sujetoexcluido', NuevoSujetoExcluidoController::class)->middleware('auth')->name('nuevo-sujetoexcluido')->can('SujetoExcluidos_Create');



    Route::get('nueva_nota_credito', NuevaNotaCreditoController::class)->name('nueva_nota_credito')->can('NuevaNotaCredito_Index');



    Route::get('nota_credito', NotaCreditoController::class)->name('nota_credito')->can('NotaCredito_Index');



    Route::get('modal_nota', [ModalNota::class, 'modalNota'])->name('modal_nota');

    ////Reportes Ventas Sintetizado//////

    Route::get('reportsVentasSintetizado', ReportVentasSintetizadoController::class)->can('ReportVentas_Index')->name('reportsVentasSintetizado');

    Route::get('report/pdfVentasSintetizado/{sucursal}/{type}/{facturador}/{f1?}/{f2?}', [ExportController::class, 'reportPDFVentasSintetizado'])->can('ReportVentasPDF_Print');

    Route::get('report/excelVentasSintetizado/{sucursal}/{type}/{facturador}/{f1?}/{f2?}', [ExportController::class, 'reportExcelVentasSintetizado'])->can('ReportVentasEXCEL_Print');

});



Route::get('report/pdf/{id}', [ExportController::class, 'reportPDF'])->name('reportPDF');

Route::get('report/pdf/cotizacion/{id}', [ExportController::class, 'reportCotizacion'])->name('reportCotizacion');

