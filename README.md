# Comercial Cruz — Sistema de Gestión Comercial

Sistema ERP web desarrollado en **Laravel + Livewire** para la gestión de Comercial Cruz. Permite administrar inventario, ventas, compras, kardex, sincronización local-VPS y facturación electrónica (DTE).

## Características principales

- **Punto de venta (POS)** — Facturación rápida con código de barras
- **Inventario y Kardex** — Control de existencias con historial completo de movimientos
- **Ajustes de inventario** — Ingresos y egresos manuales con soporte modo local/VPS
- **Compras** — Registro de compras y actualización automática de existencias
- **Créditos y cuentas por cobrar** — Control de clientes con saldo
- **Facturación electrónica DTE** — Emisión de documentos tributarios electrónicos (El Salvador)
- **Sincronización local ↔ VPS** — Arquitectura multi-sucursal con sync bidireccional automático
- **Comparar inventario** — Comparación en tiempo real entre la PC local y el VPS con push de diferencias
- **Solicitudes de productos** — Flujo de pedidos entre sucursales
- **Hojas de inventario** — Toma física de inventario
- **Reportes** — Utilidad, existencias, kardex, ventas por período

## Stack tecnológico

- PHP 8.x / Laravel 10
- Livewire 2
- MySQL (conexión local `localmysql` + VPS `vpsmysql`)
- Bootstrap 5 + Sneat UI
- FTP deploy a VPS Hostinger

## Arquitectura multi-sucursal

Cada PC local tiene su propia base de datos MySQL y se sincroniza con el VPS cada minuto mediante comandos Artisan programados. El campo `APP_MODO` en `.env` define si la instancia corre en modo `local` o `vps`.

### Comandos de sincronización

| Comando | Descripción | Frecuencia |
|---|---|---|
| `sync:local-vps` | Sube tablas generales al VPS | Cada minuto |
| `sync:vps-local` | Descarga cambios del VPS | Cada 5 min |
| `sync:existencias-vps` | Sincroniza existencias (sin kardex) | Cada 10 min |
| `push:inventario-vps` | Fuerza existencia + kardex de un producto al VPS | Manual |
| `sync:bitacora-local-vps` | Sube bitácora de sincronización | Cada minuto |
| `sync:solicitudes` | Sincroniza solicitudes de productos | Cada minuto |

## Requisitos

- PHP >= 8.1
- MySQL >= 8.0
- Composer
- Node.js (para compilar assets)

## Instalación local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Configurar en `.env`:

```env
APP_MODO=local
APP_SUCURSAL_ID=1
DB_CONNECTION=localmysql
```
