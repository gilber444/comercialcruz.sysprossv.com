# Changelog

Todos los cambios notables de este proyecto se documentarán en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [1.0.43] - 2026-11-11

### Corregido
- **La consulta a Hacienda nunca encontraba el sello**: la URL de consulta salía `.../fesv/consultadte/` y Hacienda respondía HTTP 401. La oficial es `.../fesv/recepcion/consultadte/`. Se deriva bien desde la recepción configurada como `.../fesv/recepciondte` (un tramo) y se corrige también si en la tabla de APIs está registrada sin `/recepcion/`. Con esto, un rechazo "YA EXISTE UN REGISTRO CON ESE VALOR" toma el sello: DTE PROCESADO y sello en ventas y caja.

## [1.0.42] - 2026-11-11

### Agregado
- Si la consulta a Hacienda no devuelve sello, queda en `storage/logs/laravel.log` ("Consulta DTE en Hacienda sin sello"): resultado, URL, HTTP y la respuesta. Sirve para diagnosticar un rechazo "YA EXISTE" cuyo sello no se toma.
- `dte:consultar-hacienda` acepta el código de generación además del id.

## [1.0.41] - 2026-11-11

### Cambiado
- Ante **cualquier** rechazo de Hacienda (no solo "ya existe") y ante un error de envío (timeout o red), se consulta a Hacienda. Si allá el DTE tiene sello de recepción, se toma: DTE en PROCESADO y sello en ventas y caja. Si no, queda RECHAZADO o ERROR y el proceso de pendientes lo reintenta. Aplica al envío de factura, CCF y nota, incluido el que hace el POS, sin cambiar el código del POS.

### Agregado
- Comando de diagnóstico `dte:consultar-hacienda {ids*} {--tomar}`: muestra la URL de consulta y la respuesta de Hacienda de cada DTE. Con `--tomar`, si Hacienda tiene el sello, lo aplica al DTE, la venta y la caja.
- `ConsultaDteHaciendaService::consultar()`: consulta sin modificar nada.

## [1.0.40] - 2026-11-11

### Corregido
- Importador de catálogos: fallaba con "Unknown column 'sincro_id'" al insertar en `pais`. Los modelos agregan `sincro_id` al crear, pero no todas las tablas de catálogo tienen esa columna, y como todo va en una transacción no se aplicaba ningún cambio. Ahora inserta solo con las columnas que existen en la tabla.
- Una tabla de catálogo con otra estructura (sin `codigo`, `valor` o `status`) ya no cancela toda la importación: se salta y la pantalla muestra el motivo.

## [1.0.39] - 2026-11-11

### Corregido
- Menú: "Documento Contingencia" (Catálogos DTE) apuntaba a la ruta `documento_contingencia`, que no existe. Si alguien creaba el permiso `DocumentoContigencia_Index`, el menú dejaba de cargar. Ahora abre la pantalla de contingencias (`contingencia`). El permiso se agregó a `PermissionsSeeder`.

## [1.0.38] - 2026-11-11

Normativa DTE 2.0: se aplicaron los tres prompts de servimangueras.cpc.lat (actualización DTE 2.0; catálogos, esquemas y eventos; libros de IVA y anexos F07), con los ajustes pedidos por el usuario. Sin cambiar la lógica del POS.

### Envío a Hacienda
- `ProcesamientoDteService`: antes de firmar o transmitir consulta **siempre** a Hacienda, en cualquier estado sin sello. Si Hacienda ya tiene el DTE con sello, toma ese sello y deja el DTE en PROCESADO, con el sello en ventas y caja; no lo vuelve a enviar. Si no, lo envía.
- `app:procesar-dtes-pendientes`:
  - Usa esa consulta y un candado por DTE, para que dos procesos no envíen el mismo.
  - Reintenta también RECHAZADO y ERROR, atendiendo primero los nuevos.
  - Despacha por código CAT-002: 01 factura, 03 CCF y 05 nota con su propio generador (antes la nota salía por el del CCF). El 14 se envía desde su pantalla.
- Un rechazo "ya existe" (HTTP 400 o 200) se consulta y reconcilia en factura, CCF y nota.
- La nota rechazada fallaba por una variable sin definir y ya no degrada una nota que tiene sello.

### Receptor
- Factura a consumidor final con identificación NIT (36): va el NIT real del cliente, solo dígitos (9 o 14), no el DUI. Si el NIT no es válido, se pide corregirlo.
- Crédito fiscal sin NRC (registro): el POS no guarda la venta y avisa "no se puede emitir Crédito Fiscal". Son 6 líneas agregadas en `SaveFiscal`. El generador del CCF tiene el mismo resguardo.

### Invalidación v3 y contingencia v4
- La invalidación sale en el esquema v3 y la contingencia en el v4: `codPuntoVentaMH`, sin `codPuntoVenta`. La versión del sobre y la del documento salen del mismo lugar (`VersionEventoDte`, con `esquema_dtes` y respaldo 3 y 4).
- Reglas de motivo y reemplazo, y de la fecha del evento. La invalidación solo se marca aceptada con PROCESADO y sello.
- Correo de invalidación con adjuntos y botón para reenviarlo.
- Filtros por empresa. La contingencia ya no dice "Procesada" si Hacienda la rechaza.

### PDF
- Logo más grande en el PDF del DTE y en el de invalidación.
- Departamento, municipio y distrito del emisor y del receptor también en la invalidación. Si falta el municipio o el departamento, se toma del distrito.
- "Ventas no sujetas" del PDF del DTE salía siempre en 0.

### Catálogos
- Importador del Excel oficial de Hacienda en Catálogos DTE > Actualizar Catálogos, con vista previa y confirmación. Comandos `catalogos:diagnostico` y `catalogos:fusionar`.
- **Incluye departamentos, municipios y distritos:**
  - Se emparejan por nombre dentro del departamento y se corrige el código, sin cambiar ids ni padres.
  - Lo que no empata se reporta y no se toca.
- `catalogos:bajar-desde-vps`, cada hora en las PC: copia los catálogos del VPS por id, para que las cajas emitan con los códigos vigentes.

### Evento de Retorno (CAT-002 18)
- Nuevo módulo en Documentos DTE > Nuevo Retorno y Eventos de Retorno:
  - aplica sobre factura con sello, dentro de plazo y sin exceder el monto;
  - reingresa el stock en proporción y lo registra en el kardex;
  - genera PDF y JSON, validado contra `fe-eret-v1`.

### Libros de IVA y anexos F07
- Diseño de servimangueras, con la paleta de la empresa y sin tabla por día, en los libros de consumidor final, contribuyentes, invalidaciones y **compras** (nuevo).
- CSV en cada libro, sin opción de menú aparte:
  - consumidor → anexo 2, con B=4 y C=01;
  - contribuyentes → anexos 1 y 7 (retención 1%);
  - compras → anexo 3.
- Formato según el manual F-07: `;` como separador, sin encabezados, DD/MM/AAAA y 2 decimales.
- Solo entran DTE con sello del ambiente de la empresa, con la misma consulta en pantalla, PDF y CSV.
- Compras guardan empresa y desglose fiscal (`App\Support\Decimal`, cuadre al centavo).
- Se quitó la ruta duplicada de `libroVentasConsumidor`.

### Correcciones de QA
- Modelos con el `use` de `Str` mal escrito: `DomicilioFiscal`, `Parametros`, `TipoPagos`.
- Rutas que apuntaban a métodos inexistentes: `productoAdmin/{imagen}` y `modal_nota`.
- Pantallas con spinners y avisos.
- 4 agentes de implementación y 3 de QA, en ciclo hasta cero errores: 115 pruebas Unit pasan, y las vistas y rutas compilan.

### Migraciones nuevas
- `2026_08_06_140000/140100/140200`: Evento de Retorno.
- `2026_08_08_120000`: campos del anexo F07 en compras.

## [1.0.37] - 2026-11-11

### Agregado
- Campo **"Ventas como"** (`filtro`) en la pantalla de Parámetros: `1 - DTE` (por defecto) o `0 - Fisico`, por caja. Se guarda al crear y editar.

## [1.0.36] - 2026-11-11

### Corregido
- Migración `parametros.filtro` (tinyint, por defecto 1). El POS la usa como "filtro inversor" del control de envío. Sin la columna llegaba null, `(int) null = 0`, y el POS invertía el envío: las ventas que debían salir como DTE quedaban como "Fisico". Con 1 el envío es normal; 0 lo invierte en esa caja.

## [1.0.35] - 2026-11-11

### Cambiado
- **Conversión de ventas "Fisico" a DTE encendida** (`dte.convertir_fisicos.activo = true`): las cajas la ejecutan solas cada 5 minutos, de 100 en 100, después de `app:update-from-git`.
- Freno automático: si Hacienda rechaza alguna venta convertida (DTE con fecha de emisión anterior al día en que se creó), la caja deja de convertir y lo avisa en `storage/logs/convertir_fisicos.log`.

## [1.0.34] - 2026-11-11

### Agregado
- Comando `ventas:convertir-fisicos-dte {--desde=} {--hasta=} {--limit=100} {--sucursal=} {--dry-run}` (solo PC locales): convierte en factura DTE las ventas "Fisico" de consumidor final (facturador 1, Cancelado/Crédito, total distinto de 0, sin DTE) con la **fecha y hora original** de la venta. Crea el DTE en estado `Creado` con número de control propio (igual que el POS) y marca la venta como DTE. Lo envía `app:procesar-dtes-pendientes` y la sincronización lo sube al VPS.
- Trabaja por lotes y espera si hay 200 o más DTE pendientes, para no atrasar los del día.
- Programado cada 5 minutos en las PC, **apagado** hasta encender `dte.convertir_fisicos.activo` en `config/dte.php` (llega a las cajas por git), después de probar a mano con `--limit=3`.

### Corregido
- Assets publicados de Livewire actualizados a v2.12.0 (los botones de Livewire no respondían).

## [1.0.33] - 2026-11-04

> En el VPS este paso se registró como 1.0.32, porque el paso 3c no se había registrado. El paso 3c se registró después como 1.0.33.

### Agregado (Normativa DTE 2.0 — paso 8: esquemas en las cajas)
- Columna `contenido` en `esquema_dtes`: el JSON del esquema queda en la misma fila. La migración la llena con los archivos que ya están en `storage` (solo si el hash coincide), y la pantalla Esquemas DTE la guarda al subir un ZIP o un archivo.
- Comando `esquemas:bajar-desde-vps {--dry-run}` (solo PC locales): copia `esquema_dtes` del VPS. El VPS manda: lo activo allá queda activo en la caja, y una fila local que el VPS no tiene se desactiva. Solo baja el contenido de los esquemas nuevos o cambiados.
- Programado cada hora en las PC (`storage/logs/esquemas_dte.log`).
- `ValidadorEsquemaDte` usa el archivo de `storage` y, si no está (como en las cajas), el `contenido` de la fila.
- La lista de la pantalla Esquemas DTE ya no carga el contenido.
- Probado con SQLite: migración, primera copia, segunda corrida sin cambios y validación en la caja sin archivo.

## [1.0.32] - 2026-11-01

> En el VPS este paso se registró como 1.0.33, después del paso 8.

### Agregado (Normativa DTE 2.0 — paso 3c: interruptor del generador 2.0)
- `DTE_GENERADOR_V2` en `.env` (`config('dte.generador_v2')`): lista de tipos que se emiten con el generador 2.0, por ejemplo `DTE_GENERADOR_V2=01` o `01,03`. **Vacío por defecto: nada cambia.**
- Lo aplican los mismos generadores actuales (`GeneraJsonF`, `GeneraJsonC`, `GeneraJsonNota`, `GeneraJsonS`) justo antes de devolver el JSON. Así lo usan todos los caminos de emisión (POS, DTE, cuentas por cobrar, recepción, lotes y sujeto excluido) sin tocar ninguno. Si el tipo está activo, el JSON pasa a 2.0 y se guarda en el DTE junto con su versión.
- El sobre de recepción (`RecepcionDTEF/C/Nota/Sujeto`) toma la versión del documento firmado cuando el tipo está activo. Si está apagado, manda la misma versión de siempre (1, 3, 3 y 1).
- `dte:comparar-generador` sigue comparando contra el JSON actual aunque el interruptor esté encendido.

> Cada caja emite con su propio `.env`: el interruptor se enciende en el VPS y en cada PC por separado.

## [1.0.31] - 2026-11-01

### Corregido
- **Nota de crédito**: faltaban las relaciones `RcondicionOperacion` (con `CondicionOperacion`) y `Rpagos` (con `FormaPagos`) en `resumenDte`, que `GeneraJsonNota` precarga. Era el último error que impedía generar el JSON de la nota. Se detectó con `dte:comparar-generador` en el VPS.

> Comparación con DTE reales en el VPS: 2 facturas (v1 → v2) y 2 CCF (v3 → v4) con los mismos montos, y todos cumplen el esquema oficial.

## [1.0.30] - 2026-10-31

> En el VPS este paso se registró como 1.0.29, porque el paso 2 no se había registrado. El paso 2 se registró después como 1.0.30.

### Corregido
- **Nota de crédito**: `GeneraJsonNota` usaba relaciones que no existían en los modelos (`dte::Rambiente`, `Remisor`, `Rventas`, `Clientes::RactividadEconomica`, etc.), así que generar una nota fallaba desde v1.0.16. Se agregaron las relaciones a `dte`, `Sucursales`, `Clientes` y `Empresas` (solo se agregan; no cambia ninguna existente).

### Agregado (Normativa DTE 2.0 — paso 3a/3b: generador 2.0 sin activar)
- `GeneradorDteV2Service`: arma Factura v2, CCF v4, Nota de Crédito v4 y Sujeto Excluido v2. **No recalcula montos**: parte del JSON del generador actual (con el descuento por caja, la retención y los códigos MH de este sistema) y solo lo pasa a la estructura 2.0 (sin `extension`; emisor sin `tipoEstablecimiento`/códigos MH; direcciones con `distrito`; `ivaRete`/`ivaPerci`; DUI/NIT solo dígitos; montos redondeados a 2 y 8 decimales; ítems numerados).
- `GeneradorDteLegacy`: da acceso a los generadores actuales fuera de los componentes.
- Comando `dte:comparar-generador {ids*}`: compara el JSON actual con el 2.0 **sin guardar nada** (transacción revertida): montos del resumen, ítems, códigos de establecimiento, direcciones, documento del receptor, cambios de estructura y validación contra el esquema oficial. Guarda ambos JSON en `storage/app/comparacion-dte/`.
- Esquemas oficiales de Hacienda (27-07-2026) en `resources/dte/esquemas/`.
- Probado sin BD con SQLite en memoria: los cuatro tipos cumplen los esquemas oficiales.

> El POS y la emisión siguen usando el generador actual. El 2.0 se activará por tipo de documento en el paso 3c, después de revisar la comparación con DTE reales.

## [1.0.29] - 2026-10-31

> En el VPS este paso se registró como 1.0.30, después del paso 3a/3b.

### Agregado (Normativa DTE 2.0 — paso 2: revisión contra el esquema antes de firmar)
- `RevisionEsquemaDte`: justo antes de firmar, revisa el JSON contra el esquema vigente de `esquema_dtes` (facturas, CCF, notas, sujeto excluido, invalidaciones `INV` y contingencias `CON`).
- Enganchado en `FirmadorDTE`, `FirmadorLocal` y `Firmador2`.
- **Solo avisa, nunca detiene la firma.** El resultado queda en `storage/logs/dte_esquema-AAAA-MM-DD.log` (canal propio, 30 días, no depende de `LOG_LEVEL`): `DTE cumple el esquema` o `DTE fuera del esquema oficial` con los campos que fallan.
- Si no hay esquema cargado para ese tipo no hace nada. Se apaga con `DTE_VALIDAR_ESQUEMA=off`.

> No cambia el POS ni el JSON que se manda a Hacienda.

## [1.0.28] - 2026-10-31

> Se registró en el VPS como 1.0.28 (el commit dice v1.0.27): `version:release` incrementó sobre el `config/version.php` que ya se había subido con 1.0.27.

### Agregado (Normativa DTE 2.0 — paso 1: esquemas JSON de Hacienda)
- Paquete `opis/json-schema` 2.6.0 (validación de JSON contra los esquemas de Hacienda).
- Tabla `esquema_dtes` (migración `2026_08_06_120000_create_esquema_dtes_table`) y modelo `EsquemaDte`.
- `EsquemaDteLector` (lee esquemas `.json` o `.zip` y detecta tipo y versión), `ValidadorEsquemaDte` y `Support/EsquemaDteParser` (decimales exactos en `multipleOf`), traídos de servimangueras.
- Pantalla **Catálogos DTE → Esquemas JSON** (`esquemas_dte`, permiso `AdminCatalagos_Index`) para subir, activar y desactivar esquemas. La columna "Usa el sistema" muestra la versión del último DTE emitido de cada tipo y avisa si no coincide con el esquema.

> No cambia el POS ni la emisión de DTE: el validador queda instalado pero todavía no se usa al firmar. Se conectará en un paso posterior.

## [1.0.26] - 2026-10-31

### Corregido
- Ajustes: faltaba el `use App\Services\InventarioSucursalService` en `NuevoAjustesController` → `Class "App\Http\Livewire\InventarioSucursalService" not found` al guardar un ajuste.

## [1.0.25] - 2026-10-30

### Corregido
- Ajustes: al guardar un ajuste de un producto sin inventario en la sucursal se intentaba crear el inventario sin `empresa` → `SQLSTATE[HY000] 1364 Field 'empresa' doesn't have a default value`.
- Ajustes: al escanear un código de barras se pasaba el id del **producto** como id de **precio**, por lo que se abría un producto distinto al escaneado.
- Compras (escáner y agregar producto): ya no fallan cuando el producto no tiene inventario en ninguna sucursal; se usa el id de precio correcto al escanear.
- Solicitudes: al despachar se creaba el inventario destino sin `empresa`.

### Cambiado
- Nuevo servicio `InventarioSucursalService`: en ajustes, compras, precompras y solicitudes, si falta el inventario del producto en la sucursal:
  - en el **VPS** se crea (con la empresa de la sucursal);
  - en las **PC locales ya no se crea**: se descarga del VPS (mismo `sincro_id`). Si el VPS no lo tiene o no hay conexión, se muestra un aviso claro.

### Agregado
- Comando `inventario:completar-desde-vps` (solo PC local): detecta los productos que no tienen inventario en la sucursal de la PC (`APP_SUCURSAL_ID`) y los descarga del VPS. Corre **una sola vez**: al terminar sin pendientes deja la marca `storage/app/inventario_completar_sucursal_{ID}.done`. Opciones `--dry-run` y `--force`. Queda programado cada hora en el scheduler local y se salta solo cuando existe la marca.

## [1.0.24] - 2026-10-30

### Cambiado
- `sync:daemon` ya no depende de la tabla `features` (en este sistema todavía no se usa). Se activa por PC con `SYNC_DAEMON=on` en el `.env`; por defecto está apagado (`off`) y siguen los comandos de sync separados. Las PCs ya no consultan el VPS para leer el flag.
- Se eliminó la migración `2026_10_30_000001_add_sync_daemon_feature_flag.php` (nunca se ejecutó en el VPS).

## [1.0.23] - 2026-10-30

### Agregado
- `sync:daemon`: daemon de sincronización LOCAL ↔ VPS (Fase 1). Un solo proceso por PC corre en orden, una a la vez, todas las sincronizaciones que antes eran comandos separados del scheduler:
  - subir al VPS (`sync:maestro`) cada 20 s, bajar del VPS (`sync:vps-local`) cada 60 s, `sync:orden-pedido` cada 60 s, `producto:nuevo` cada 2 min, `sync:existencias-vps` cada 10 min y `sync:dtes-estado` cada 30 min. Intervalos configurables en `config/sync.php` / `.env` (`SYNC_CADA_*`);
  - máximo una conexión al VPS por PC a la vez;
  - cada tarea corre en un proceso hijo con timeout (600 s); si se cuelga se mata junto con su conexión;
  - si una tarea falla seguido (sin internet), los reintentos se espacian hasta 5 min;
  - cada ciclo dura 10 min y el scheduler lo vuelve a lanzar cada minuto si no está corriendo (no hace falta instalar nada en las PCs).
- Feature flag `sync_daemon` (migración, `activo=1`, `produccion=0`). Cada PC lo lee **en el VPS** cada 5 min. Con `produccion=1` en `sync_daemon` se activa en todas las sucursales; para una sola sucursal se crea `sync_daemon_suc_{ID}` con `activo=1` y `produccion=1`. En una PC se puede forzar con `SYNC_DAEMON=on` o apagar con `SYNC_DAEMON=off`.
- Mientras el daemon está activo, los comandos de sync separados del scheduler no corren, y el daemon no ejecuta un comando si el separado todavía está corriendo (cambio sin choques).

> Registrado en estado de **prueba** (`activo=1`, `produccion=0`). El usuario decide cuándo pasar a producción.

## [1.0.22] - 2026-10-30

### Corregido
- Conexiones al VPS que quedaban abiertas o colgadas por los comandos de sync:
  - Al terminar cualquier comando de consola (bien, con error o por error fatal) Laravel cierra todas las conexiones a BD, incluida `vpsmysql`. Antes `producto:nuevo`, `sync:orden-pedido` y `sync:dtes-estado` nunca la cerraban, y `sync:vps-local`, `sync:existencias-vps` y `sync:solicitudes` solo la cerraban si terminaban sin error.
  - Conexión `vpsmysql`: máximo 10 s para conectar (`PDO::ATTR_TIMEOUT`) y `net_read_timeout`/`net_write_timeout` de 60 s, para que el servidor corte la conexión si la PC pierde internet a mitad de la sync.
  - En la PC local, los comandos `sync:*`, `producto:nuevo` y `push:inventario-vps` tienen un tiempo máximo de 1200 s (configurable con `SYNC_MAX_SEGUNDOS`). En Windows corta los procesos que quedaron esperando una red caída.
  - `sync:vps-local`: `withoutOverlapping` bajó de 59 a 25 minutos, para que un proceso colgado no bloquee la bajada de datos casi una hora.

> Registrado en estado de **prueba** (`activo=1`, `produccion=0`). El usuario decide cuándo pasar a producción.

## [1.0.21] - 2026-09-24

### Corregido
- `sync:local-vps`: `ventas_detalles` fallaba con `1390 Prepared statement contains too many placeholders`. El filtro por sucursal traía todos los IDs de `ventas` de la sucursal y los mandaba en un `WHERE venta IN (...)`, que supera el límite de 65,535 parámetros de MySQL cuando hay muchas ventas. Ahora se usa una subconsulta (`WHERE venta IN (SELECT id FROM ventas WHERE sucursal = ?)`). Se aplicó lo mismo a `ajustes_detalles`, `compras_detalles`, `solicitudes_detalles`, `kardexes`, `cuentas_pagars` y `pagos`, que tenían el mismo problema a futuro.

> Registrado en estado de **prueba** (`activo=1`, `produccion=0`). El usuario decide cuándo pasar a producción.

## [1.0.20] - 2026-09-24

### Corregido
- `app:update-from-git`: restaurada la versión compatible con Windows de la 1.0.15, que el commit de la 1.0.17 había revertido sin querer. En las PCs locales fallaba con `"which" no se reconoce...` y `El sistema no puede encontrar la ruta especificada` porque buscaba `/usr/bin/php82` y `/usr/bin/composer`. Ahora usa el PHP con el que corre artisan y el `composer.phar` de Laragon en Windows, y `/usr/bin/php82` en el VPS. Se pueden forzar con `UPDATE_PHP_BINARY` y `UPDATE_COMPOSER_PATH` en el `.env`.

> Registrado en estado de **prueba** (`activo=1`, `produccion=0`). El usuario decide cuándo pasar a producción.

## [1.0.19] - 2026-09-24

### Corregido
- `sync:maestro`: `sync:local-vps` fallaba con `The "1" argument does not exist` porque las opciones se pasaban a `$this->call()` como lista (`"--opcion=valor"`) en lugar de arreglo asociativo (`'--opcion' => valor`). Ahora `sync:local-vps` vuelve a subir inventario, ventas, compras y cajas de la sucursal.
- `--limit` ahora llega correctamente a `sync:solicitudes` y `sync:bitacora-local-vps` (antes se ignoraba y la bitácora usaba 5000 por defecto; ahora usa el límite del maestro, 1000).

> Registrado en estado de **prueba** (`activo=1`, `produccion=0`). El usuario decide cuándo pasar a producción.

## [1.0.12] - 2026-06-30

### Corregido
- `HojaInventario::abrirInventario`: validación atómica con `lockForUpdate` para evitar aperturas duplicadas cuando dos PCs tienen la pantalla abierta.
- Si ya existe una apertura abierta para la misma empresa, sucursal y fecha, el sistema muestra error y no permite crear otra.

> Registrado en estado de **prueba** (`activo=1`, `produccion=0`). El usuario decide cuándo pasar a producción.

## [1.0.11] - 2026-06-30

### Corregido
- `NuevoAjustesController`: unificado el cálculo de tipo de ajuste (Ingreso/Egreso) mediante `getTipoLogica()`.
- La columna "Existencia Ajustada" en el carrito ahora refleja correctamente egresos para `Gastos`, `Producto Dañado`, `Producto Vencido` y `Egreso`.
- Inicialización por defecto de `$tipo = 'Ingreso'` en `mount()` para evitar valor vacío al cargar el formulario.

> Registrado en estado de **prueba** (`activo=1`, `produccion=0`). El usuario decide cuándo pasar a producción.

## [1.0.10] - 2026-06-30

### Corregido
- Restaurado acceso público a PDFs de DTE y cotización para escaneo de QR/enlaces de clientes.
- `ExportDteController::reportPDF` mantiene protección contra enumeración: solo acepta UUID (`codigoGeneracion`), no IDs numéricos.
- `ExportCotizacionController::reportCotizacion` accesible públicamente por ID, como en la versión original.

> Registrado en estado de **prueba** (`activo=1`, `produccion=0`). El usuario decide cuándo pasar a producción.

## [1.0.9] - 2026-06-30

### Seguridad
- Eliminadas rutas públicas duplicadas de PDFs (`report/pdf/{id}` y `report/pdf/cotizacion/{id}`); ahora requieren autenticación y permiso.
- Verificación de propiedad en `ExportDteController::reportPDF` (solo UUID, filtro por empresa/sucursal).
- Verificación de propiedad en `ExportCotizacionController::reportCotizacion`.
- Verificación de propiedad en `DteController::show`/`download` (prevención de IDOR).
- Endpoint `/api/user` ahora retorna solo campos públicos, sin credenciales.
- `password2` agregado a `$hidden` en modelo `User` y ya no se carga en propiedades Livewire.
- `apiPassword` de empresa ya no se carga en propiedades Livewire; solo se actualiza si se ingresa un nuevo valor.
- Agregado `$hidden` en modelos: `Empresas`, `Parametros`, `Bancos`, `Clientes`, `Proveedores`, `Ventas`, `VentasDetalles`, `Precios`.
- Sanitización de logs: removidos `getTraceAsString`, queries completas, payloads JSON y datos de items en logs de errores.
- Eliminados `dd()` activos en `EmpresaController` y `ModalSolicitud`.
- Eliminados archivos de copia/backup (`* copy.php`, `*_OLD.blade.php`, `app/Traits/bak/`).
- Endurecidos `.htaccess` raíz y `public/.htaccess` para bloquear `.env`, `.git/`, `storage/logs/` y archivos `.key/.pem/.sql/.bak/.old`.

### Cambiado
- `.env.example` ahora usa `APP_ENV=production`, `APP_DEBUG=false` y `LOG_LEVEL=error` por defecto.
- `app/Exceptions/Handler.php` retorna respuestas JSON genéricas en producción cuando `app.debug` es falso.

> Registrado en estado de **prueba** (`activo=1`, `produccion=0`). El usuario decide cuándo pasar a producción.

## [1.0.8] - 2026-06-29

### Corregido
- `sync:local-vps` ahora aborta si se ejecuta en VPS (`APP_MODO=vps`).
- Validación en `Kernel.php` reforzada: los comandos de sincronización local↔VPS solo corren en modo `local`; los comandos del VPS solo corren en modo `vps`.

> Registrado en estado de **prueba** (`activo=1`, `produccion=0`). El usuario decide cuándo pasar a producción.

## [1.0.7] - 2026-06-29

### Agregado
- Archivo `AGENTS.md` con instrucciones para agentes de código:
  - Flujo de trabajo: cambio → `version:release` → `CHANGELOG.md` → commit/push → subir al VPS por FTP.
  - Regla: solo el usuario libera y activa las pruebas de los features.
  - Datos de FTP en `.vscode/sftp.json`.
- Archivo `CHANGELOG.md` para control de versiones.

### Corregido
- Filtro de sucursal en `sync:local-vps` reforzado:
  - Ahora cada PC solo sube datos de su `APP_SUCURSAL_ID`.
  - Si no se especifica `--sucursal-inventarios` ni `APP_SUCURSAL_ID`, el comando aborta.
  - Ninguna PC con `APP_SUCURSAL_ID != 2` puede subir inventario/ajustes de la sucursal 2.

## [1.0.6] - 2026-06-29

### Agregado
- Nuevos controladores de exportaciones separados por módulo:
  - `ExportDteController`
  - `ExportVentaController`
  - `ExportCompraController`
  - `ExportInventarioController`
  - `ExportArqueoController`
  - `ExportCorteZController`
  - `ExportCotizacionController`
  - `ExportSolicitudController`
  - `ExportUtilidadController`
- Trait compartido `HasLogoBase64` para convertir logos a base64 en PDFs.
- Tabla `features` con soporte de feature flags (`codigo`, `activo`, `produccion`).
- Permiso `Utilidad_Costo` para controlar visibilidad de costos en reportes de utilidad.
- Reportes de utilidades detrás de feature flags:
  - `reporte_utilidad_detallado`
  - `reporte_utilidad_sintetizado`
  - `pos_utilidad_costos`

### Cambiado
- Vistas PDF de utilidades rediseñadas con tarjetas e iconos.
- Reporte de utilidades **detallado** ahora solo ofrece vista HTML y Excel (sin PDF).
- Reporte de utilidades **sintetizado** conserva vista HTML, PDF y Excel.
- Cálculo de utilidad como `venta - costo` sin factor adicional.
- `tmpVentas` y `SaveTicket` ahora guardan/recalculan `costo_total` correctamente.
- CRUD de features rediseñado a tarjetas con buscador y filtros.

### Corregido
- Timeout de DomPDF al generar PDFs: logo convertido a base64 y CSS simplificado.
- Nombre de sucursal en reporte de utilidades sintetizado.

### Seguridad
- Versiones liberadas en el CRUD de features quedan bloqueadas para edición.

## [1.0.5] - 2026-06-28

### Agregado
- Logo en PDFs convertido a base64 para evitar peticiones HTTP internas.
- Patrón "vista HTML + botón PDF" para reportes de utilidades (igual que hojas de inventario).

### Corregido
- Optimización de generación de PDFs de utilidades.

## [1.0.0] - 2026-06-XX

### Agregado
- Versión inicial del sistema de control de versiones con `version:release`.
