# Sistema ERP Comercial Cruz

Sistema de gestión comercial orientado a supermercados, abarroteras y comercios con múltiples sucursales. Desarrollado con **Laravel 10**, permite administrar inventario, compras, ventas, facturación electrónica (DTE) de El Salvador, cajas, cortes, cuentas por pagar y la sincronización de datos entre un VPS central y las bases de datos locales de cada sucursal.

---

## ¿Qué hace el sistema?

### Módulos principales
- **Inventario:** control de existencias por sucursal, kardex, ajustes, toma física y hojas de inventario.
- **Compras:** registro de compras a proveedores, detalles de compra y cuentas por pagar.
- **Ventas:** punto de venta (POS), cajas, cortes X/Z, remesas y reportes de ventas.
- **Facturación electrónica (DTE):** emisión de facturas, tickets, notas de crédito/débito y envío al Ministerio de Hacienda de El Salvador (MH).
- **Sincronización:** replicación incremental de datos entre el VPS central (`vpsmysql`) y cada sucursal local (`localmysql`).
- **Correos:** envío automático y manual de DTEs procesados a los clientes.
- **Reportes:** utilidades, inventarios, arqueos, compras, ventas y más.

### Arquitectura
- **VPS (nube):** base de datos central que concentra la información de todas las sucursales.
- **Local (sucursal):** cada sucursal opera con su propia base de datos local.
- **Sync:** comandos Artisan programados en un scheduler permiten sincronizar compras, ventas, inventarios, productos, clientes, DTEs y otros datos en ambas direcciones.

---

## Requisitos

- PHP >= 8.1
- Composer
- MySQL / MariaDB
- Node.js + NPM (para compilar assets con Vite)
- Servidor web con Apache/Nginx y soporte para `mod_rewrite`
- Acceso programado a la base de datos del VPS (solo para sincronización)

---

## Instalación

```bash
# 1. Clonar el repositorio
git clone https://github.com/gilber444/comercialcruz.sysprossv.com.git
cd comercialcruz.sysprossv.com

# 2. Instalar dependencias
composer install
npm install && npm run build

# 3. Configurar variables de entorno
cp .env.example .env
php artisan key:generate

# 4. Crear las bases de datos local y (si aplica) sincronizar con el VPS
php artisan migrate --force

# 5. Configurar permisos de carpetas necesarias
chmod -R 775 storage bootstrap/cache
```

---

## Configuración del entorno

Editar `.env` según el modo de ejecución:

```dotenv
# Modo de ejecución: 'local' para sucursales, 'vps' para el servidor central
APP_MODO=local
APP_SUCURSAL_ID=1

# Base de datos local de la sucursal
DB_CONNECTION=localmysql
DB_HOST_LOCAL=127.0.0.1
DB_PORT_LOCAL=3306
DB_DATABASE_LOCAL=sysprossv_comercialcruz
DB_USERNAME_LOCAL=usuario_local
DB_PASSWORD_LOCAL=contraseña_local

# Base de datos central en el VPS (solo para sincronización)
DB_HOST_VPS=130.51.180.91
DB_PORT_VPS=3306
DB_DATABASE_VPS=sysprossv_comercialcruz
DB_USERNAME_VPS=sysprossv_sincro
DB_PASSWORD_VPS=Sincro2025
```

---

## Comandos Artisan principales

```bash
# Sincronizar datos del VPS hacia la base local
php artisan sync:vps-local

# Sincronizar datos de la base local hacia el VPS
php artisan sync:local-vps

# Reconciliar estados de DTEs rechazados contra el VPS
php artisan sync:dtes-estado --limit=500

# Procesar DTEs pendientes (firma y envío a MH)
php artisan dte:procesar-pendientes

# Enviar correos de DTEs procesados manualmente
php artisan dte:enviar-correo-wm --limit=10

# Limpiar logs antiguos
php artisan logs:limpiar --dias=5 --max-mb=20
```

---

## Scheduler (tareas programadas)

En Windows se recomienda ejecutar `run-scheduler.bat` cada minuto con el Programador de Tareas:

```batch
run-scheduler.bat
```

Esto ejecuta `php artisan schedule:run`, que dispara los comandos configurados en `app/Console/Kernel.php`:

- Sincronización VPS ↔ local
- Reconciliación de DTEs
- Procesamiento de DTEs pendientes
- Limpieza de logs

---

## Sincronización multi-sucursal

El sistema usa las columnas `sincro_id` e `id_vps` para reconciliar registros entre el VPS y las sucursales sin depender de los IDs autoincrementales locales. Esto evita colisiones cuando varias sucursales crean registros simultáneamente.

### Tablas sincronizadas principales
- `productos`, `inventarios`, `kardexes`
- `clientes`, `proveedores`
- `compras`, `compras_detalles`, `cuentas_pagars`, `pagos`
- `ventas`, `ventas_detalles`, `cajas`, `cortes`, `remesas`
- `dtes`, `resumen_dtes`, `firmadors`, `recepcion_dtes`

---

## Notas de seguridad

- Nunca subir el archivo `.env` al repositorio.
- Los scripts de administración local (`.bat`) se mantienen fuera del directorio web; en el VPS no deben existir.
- Los logs de PHP se redirigen a `storage/logs/php_errors.log` mediante `public/index.php` y `.htaccess`, evitando archivos `error_log` expuestos en la raíz o en `public/`.
- Los archivos de respaldo (`.bak`, `.zip`, `.sql`) y temporales deben eliminarse del servidor de producción.

---

## Licencia

Proyecto privado. Uso exclusivo de Comercial Cruz / Sysprossv.
