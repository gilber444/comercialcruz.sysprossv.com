@echo off
setlocal

REM === ADMIN CHECK ===
net session >nul 2>&1
if not %errorlevel%==0 (
  echo Ejecuta este script como Administrador.
  pause & exit /b 1
)

REM === RUTAS (usar ProgramData evita bloqueos de C:\) ===
set "SEC_DIR=C:\ProgramData\Secure"
set "SEC_FILE=%SEC_DIR%\deploy_secret.sha256"

echo Creando carpeta segura: %SEC_DIR%
mkdir "%SEC_DIR%" 2>nul

echo Endureciendo permisos de la carpeta...
icacls "%SEC_DIR%" /inheritance:r >nul
icacls "%SEC_DIR%" /grant:r *S-1-5-32-544:(OI)(CI)(F) SYSTEM:(OI)(CI)(F) >nul

echo Creando archivo de secreto...
type nul > "%SEC_FILE%" 2>nul

echo Permisos de escritura temporales para grabar el hash...
icacls "%SEC_FILE%" /inheritance:r >nul
icacls "%SEC_FILE%" /grant:r *S-1-5-32-544:(W,R) SYSTEM:(W,R) >nul

echo Grabando hash...
powershell -NoProfile -Command "$s='Chipi44741852**'; $sha=[System.Security.Cryptography.SHA256]::Create(); $bytes=[System.Text.Encoding]::UTF8.GetBytes($s); $hash=($sha.ComputeHash($bytes) | ForEach-Object ToString X2) -join ''; Set-Content -Path '%SEC_FILE%' -Value $hash -Encoding ascii"

echo Dejando el archivo solo-lectura para Admins y SYSTEM...
icacls "%SEC_FILE%" /grant:r *S-1-5-32-544:(R) SYSTEM:(R) >nul

echo OK. Contraseña configurada en %SEC_FILE%.
pause
