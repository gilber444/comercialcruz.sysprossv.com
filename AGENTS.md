# Instrucciones para agentes de código

## Contexto del proyecto

- Proyecto Laravel 10.x desplegado en VPS y usado desde PCs locales.
- Repositorio GitHub: `https://github.com/gilber444/comercialcruz.sysprossv.com.git`, rama `main`.
- VPS FTP: configurado en `.vscode/sftp.json` (host, usuario, contraseña, remotePath).
- Versión actual del sistema: `1.0.12` (ver `config/version.php` y `.env` `APP_VERSION`).

## Flujo de trabajo obligatorio para cada cambio

1. **Realizar el cambio** en el código local.
2. **Actualizar la versión** (sin pasar a producción):
   ```bash
   php artisan version:release "Descripcion del cambio"
   ```
   - Esto registra el cambio en `features` con `activo=1` y `produccion=0` (estado de prueba).
   - Si la BD local no está disponible, actualizar manualmente `config/version.php` y `.env`, y registrar después en el VPS con el mismo comando usando `/usr/bin/php82` **sin `--prod`**.
3. **Actualizar el CHANGELOG.md** con la nueva versión y los cambios realizados.
4. **Hacer commit y push** a GitHub:
   ```bash
   git add -A
   git commit -m "vX.Y.Z: descripcion del cambio"
   git push origin main
   ```
5. **Subir al VPS** usando los datos de FTP de `.vscode/sftp.json`:
   - Host: `130.51.180.91`
   - Protocolo: FTP puerto 21
   - Usuario: `sysprossv`
   - Contraseña: `@Dmin#2023`
   - Ruta remota: `comercialcruz.sysprossv.com/`
6. **En el VPS** ejecutar (agente / desarrollo):
   ```bash
   /usr/bin/php82 artisan config:clear
   /usr/bin/php82 artisan view:clear
   /usr/bin/php82 composer dump-autoload
   /usr/bin/php82 artisan migrate        # si hay migraciones nuevas
   /usr/bin/php82 artisan version:release "Descripcion del cambio"
   ```
7. **Solo el usuario decide el paso a producción.** Cuando el usuario apruebe la prueba, él ejecuta:
   ```bash
   /usr/bin/php82 artisan version:release "Descripcion del cambio" --prod
   ```
   O directamente actualiza el registro en la tabla `features` poniendo `produccion = 1`.

## Feature flags

- Las funcionalidades nuevas/mejordas deben ir detrás de feature flags en la tabla `features`.
- **Solo el usuario/owner del sistema libera y activa las pruebas de los features.**
- El agente registra versiones y features con `activo=1` y `produccion=0` (prueba).
- El agente **NUNCA** usa `--prod` en `version:release` sin autorización expresa del usuario.
- Para que un feature se vea en producción debe tener:
  - `activo = 1`
  - `produccion = 1`
- Métodos de ayuda:
  - `Feature::isEnabled($codigo)` → requiere activo + produccion.
  - `Feature::isActive($codigo)` → solo activo.

## Sincronización local ↔ VPS

- Cada PC local debe tener `APP_SUCURSAL_ID` configurado en `.env`.
- El comando `sync:local-vps` está filtrado por `--sucursal-inventarios` y usa `APP_SUCURSAL_ID`.
- **Ninguna PC con `APP_SUCURSAL_ID` diferente debe poder subir inventario/ajustes de otra sucursal.**
- Si se modifica la lógica de sincronización, verificar especialmente `SyncLocalAVPSTablas` y `SyncDesdeVPS`.

## Versionado

- Formato: `MAJOR.MINOR.PATCH`.
- `PATCH` se incrementa por cada cambio.
- Cuando `PATCH` llega a 99, pasa a `MINOR + 1`.
- Cuando `MINOR` llega a 99, pasa a `MAJOR + 1`.
- El comando `php artisan version:release` actualiza `config/version.php`, `.env` y registra en `features`.

## Notas de seguridad

- Subir `.env` al VPS solo cuando sea estrictamente necesario (por ejemplo, para actualizar `APP_VERSION`). Antes de hacerlo, confirmar que no sobreescribirá credenciales u otra configuración crítica del servidor.
- No ejecutar comandos destructivos en BD del VPS sin confirmación del usuario.
- No hacer `git push --force` ni rebases sin autorización expresa.
- Después de cualquier deploy, verificar en el VPS:
  - `APP_DEBUG=false` y `APP_ENV=production` en `.env`.
  - `LOG_LEVEL=error` o `warning` en producción.
  - Logs rotados/borrados periódicamente (`storage/logs/laravel.log`, `procesar_dtes_pendientes.log`, etc.).
  - Credenciales de BD/Hacienda rotadas si hubo exposición previa en logs.
- Nunca almacenar contraseñas en texto plano en el frontend ni en propiedades Livewire públicas.
