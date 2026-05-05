@echo off
pushd "%~dp0"

REM ---- RUTA EXACTA DEL PHP DE LARAGON ----
set "PHP=C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe"

REM ---- ARCHIVOS DE LOG ----
set "LOG_SCHEDULER=storage\logs\scheduler.log"
set "LOG_QUEUE=storage\logs\queue.log"

if not exist "storage\logs" mkdir "storage\logs"

REM ---- 1) EJECUTA EL SCHEDULER ----
"%PHP%" artisan schedule:run --no-ansi >> "%LOG_SCHEDULER%" 2>&1

REM ---- 2) PROCESA LA COLA DE JOBS ----
"%PHP%" artisan queue:work --stop-when-empty --tries=1 >> "%LOG_QUEUE%" 2>&1

popd
