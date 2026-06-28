@echo off
pushd "%~dp0"
set "PHP=C:\laragon\bin\php\php-8.3.16-Win32-vs16-x64\php.exe"
set "LOG=storage\logs\scheduler.log"
if not exist "storage\logs" mkdir "storage\logs"
"%PHP%" artisan schedule:run --no-ansi >> "%LOG%" 2>&1
popd
