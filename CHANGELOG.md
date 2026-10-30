# Changelog

Todos los cambios notables de este proyecto se documentarán en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

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
