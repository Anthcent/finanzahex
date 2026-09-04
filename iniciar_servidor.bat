@echo off
title Finanza Personal - Servidor
cd /d "%~dp0"

echo [1/3] Verificando MySQL...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo MySQL ya esta en ejecucion.
) else (
    echo Iniciando MySQL de XAMPP...
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --console
    timeout /t 3 /nobreak > nul
)

echo [2/3] Abriendo navegador...
start http://localhost:8080/

echo [3/3] Iniciando servidor CodeIgniter 4 en http://localhost:8080...
echo Presiona Ctrl + C para detener el servidor.
echo.
"C:\xampp\php\php.exe" spark serve --port 8080
pause
