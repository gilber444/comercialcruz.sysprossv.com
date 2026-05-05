@echo off
setlocal enableextensions

net session >nul 2>&1
if not %errorlevel%==0 (
  echo Ejecuta este script como Administrador.
  pause & exit /b 1
)

set "APP=C:\laragon\www\supermercadojosue"
set "STORAGE=%APP%\storage"
set "CACHE=%APP%\bootstrap\cache"
set "PHP_EXE=C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe"
set "APACHE_SERVICE=Apache2.4"
set "SHARE_NAME=supermercadojosue$"

if not exist "%PHP_EXE%" set "PHP_EXE=php"

echo [FW] Limpiando reglas previas...
netsh advfirewall firewall delete rule name="(Proyecto) Bloquear SMB 445" >nul 2>&1
netsh advfirewall firewall delete rule name="(Proyecto) Bloquear NetBIOS 139" >nul 2>&1
netsh advfirewall firewall delete rule name="(Proyecto) Permitir HTTP 80" >nul 2>&1
netsh advfirewall firewall delete rule name="(Proyecto) Permitir HTTPS 443" >nul 2>&1

echo [FW] Bloqueando SMB y permitiendo HTTP/HTTPS...
netsh advfirewall firewall add rule name="(Proyecto) Bloquear SMB 445" dir=in action=block protocol=TCP localport=445 >nul 2>&1
netsh advfirewall firewall add rule name="(Proyecto) Bloquear NetBIOS 139" dir=in action=block protocol=TCP localport=139 >nul 2>&1
netsh advfirewall firewall add rule name="(Proyecto) Permitir HTTP 80" dir=in action=allow protocol=TCP localport=80 >nul 2>&1
netsh advfirewall firewall add rule name="(Proyecto) Permitir HTTPS 443" dir=in action=allow protocol=TCP localport=443 >nul 2>&1

echo [SMB] Eliminando recurso compartido si existiera...
net share %SHARE_NAME% >nul 2>&1 && net share %SHARE_NAME% /delete /y >nul 2>&1

echo [LARAVEL] Saliendo de mantenimiento...
pushd "%APP%" 2>nul
"%PHP_EXE%" artisan up 1>nul 2>nul
popd

echo [APACHE] Iniciando servicio...
sc start "%APACHE_SERVICE%" >nul 2>&1

echo [ACL] Bloqueando lectura/edicion a todos excepto SYSTEM...
icacls "%APP%" /inheritance:r >nul
icacls "%APP%" /remove:g Users "Authenticated Users" Everyone "*S-1-5-32-544" >nul
icacls "%APP%" /grant:r SYSTEM:(OI)(CI)(F) >nul

echo [ACL] Escritura runtime para SYSTEM en storage y cache...
icacls "%STORAGE%" /grant:r SYSTEM:(OI)(CI)(M) >nul
icacls "%CACHE%"   /grant:r SYSTEM:(OI)(CI)(M) >nul

echo.
echo === PROYECTO BLOQUEADO ===
echo - Solo accesible por HTTP/HTTPS
echo - Codigo sin lectura/edicion (ni clic derecho -> Editar)
echo - Para actualizar: ejecutar 02_habilitar_actualizacion.bat
echo.
pause
