# Sistema ERP Comercial Cruz

Sistema de gestión comercial privado orientado a supermercados, abarroteras y comercios con múltiples sucursales. Desarrollado con **Laravel 10**, permite administrar inventario, compras, ventas, facturación electrónica (DTE) de El Salvador, cajas, cortes, cuentas por pagar y la sincronización de datos entre un VPS central y las bases de datos locales de cada sucursal.

---

## ¿Qué hace el sistema?

### Módulos principales
- **Inventario:** control de existencias por sucursal, kardex, ajustes, toma física y hojas de inventario.
- **Compras:** registro de compras a proveedores, detalles de compra y cuentas por pagar.
- **Ventas:** punto de venta (POS), cajas, cortes X/Z, remesas y reportes de ventas.
- **Facturación electrónica (DTE):** emisión de facturas, tickets, notas de crédito/débito y envío al Ministerio de Hacienda de El Salvador (MH).
- **Sincronización:** replicación incremental de datos entre el VPS central y cada sucursal local.
- **Correos:** envío automático y manual de DTEs procesados a los clientes.
- **Reportes:** utilidades, inventarios, arqueos, compras, ventas y más.

### Arquitectura
- **VPS (nube):** base de datos central que concentra la información de todas las sucursales.
- **Local (sucursal):** cada sucursal opera con su propia base de datos local.
- **Sync:** tareas programadas permiten sincronizar compras, ventas, inventarios, productos, clientes, DTEs y otros datos en ambas direcciones, usando `sincro_id` e `id_vps` para evitar colisiones entre IDs locales.

---

## Tecnologías

- Laravel 10
- PHP 8.1+
- MySQL / MariaDB
- Livewire
- Bootstrap
- Vite

---

## Notas de seguridad

- Este es un proyecto privado. El acceso al código, configuraciones y credenciales está restringido al equipo autorizado.
- No compartir el archivo `.env` ni las credenciales de base de datos.
- Los scripts de administración del servidor se mantienen fuera del directorio web.
- Los logs de PHP se redirigen a `storage/logs/php_errors.log`, evitando archivos `error_log` expuestos en la raíz o en `public/`.
- Los archivos de respaldo (`.bak`, `.zip`, `.sql`) y temporales deben eliminarse del servidor de producción.

---

## Licencia

Proyecto privado. Uso exclusivo de Comercial Cruz / Sysprossv.
