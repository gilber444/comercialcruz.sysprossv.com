# Changelog

Todos los cambios notables de este proyecto se documentarán en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [1.0.8] - 2026-06-29

### Corregido
- `sync:local-vps` ahora aborta si se ejecuta en VPS (`APP_MODO=vps`).
- Validación en `Kernel.php` reforzada: los comandos de sincronización local↔VPS solo corren en modo `local`; los comandos del VPS solo corren en modo `vps`.

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
