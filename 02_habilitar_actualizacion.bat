@echo off
setlocal enableextensions

REM === COMPROBAR ADMIN ===
net session >nul 2>&1
if not %errorlevel%==0 (
  echo Ejecuta este script como Administrador.
  pause & exit /b 1
)

REM ===== CONFIG =====
set "APP=C:\laragon\www\supermercadojosue"
set "PHP_EXE=C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe"
set "APACHE_SERVICE=Apache2.4"
set "SEC_FILE=C:\ProgramData\Secure\deploy_secret.sha256"
set "SEC_DIR=C:\ProgramData\Secure"
set "LASTUSER_FILE=%SEC_DIR%\last_update_user.txt"
REM ===================

if not exist "%PHP_EXE%" set "PHP_EXE=php"

if not exist "%SEC_FILE%" (
  echo No existe %SEC_FILE%. Corre 00_set_password.bat primero.
  pause & exit /b 1
)

echo [SEC] Verificando contrasena...
powershell -NoProfile -Command "$p=Read-Host 'Contrasena' -AsSecureString; $b=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($p); $s=[Runtime.InteropServices.Marshal]::PtrToStringBSTR($b); $hash=([BitConverter]::ToString([Security.Cryptography.SHA256]::Create().ComputeHash([Text.Encoding]::UTF8.GetBytes($s))).Replace('-','').ToLower()); $saved=(Get-Content '%SEC_FILE%' -Raw).Trim(); if($hash -eq $saved){exit 0}else{Write-Host 'Contrasena incorrecta'; exit 1}"
if errorlevel 1 (
  echo Acceso denegado.
  pause & exit /b 1
)

REM Detectar usuario actual (DOMINIO\usuario o EQUIPO\usuario)
for /f "tokens=* usebackq" %%I in (`whoami`) do set "CURUSER=%%I"
echo [USER] Usuario actual: %CURUSER%

if not exist "%SEC_DIR%" mkdir "%SEC_DIR%" >nul 2>&1
echo %CURUSER% > "%LASTUSER_FILE%"

echo [OWN] Tomando propiedad para Administradores...
takeown /F "%APP%" /R /A >nul

echo [ACL] Propietario = Administradores...
icacls "%APP%" /setowner "*S-1-5-32-544" /T /C >nul

echo [ACL] Concediendo acceso temporal (Admins, SYSTEM y %CURUSER%)...
icacls "%APP%" /grant:r "*S-1-5-32-544":(OI)(CI)(F) SYSTEM:(OI)(CI)(F) "%CURUSER%":(OI)(CI)(F) /T /C >nul

echo [APACHE] Deteniendo servicio...
sc stop "%APACHE_SERVICE%" >nul 2>&1

echo [LARAVEL] Modo mantenimiento...
pushd "%APP%" 2>nul
"%PHP_EXE%" artisan down --secret=upd-%random% >nul 2>&1
popd

echo.
echo === MODO ACTUALIZACION ===
echo - Ya puedes: git pull / copiar ZIP / composer / npm / migrate, etc.
echo - Al terminar, ejecuta 01_bloquear_proyecto.bat para volver a bloquear.
echo.
pause
