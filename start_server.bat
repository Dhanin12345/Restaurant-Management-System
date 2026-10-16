@echo off
title Restaurant Pro - Startup Launcher
color 0A

echo =======================================================
echo    RESTAURANT PRO - ENTERPRISE MANAGEMENT SYSTEM
echo =======================================================
echo.

:: 1. Check & Start MySQL (XAMPP)
echo [1/3] Checking MySQL database service...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL is already running.
) else (
    echo [INFO] Starting MySQL from C:\xampp\mysql\bin...
    start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
    timeout /t 2 >nul
    echo [OK] MySQL started successfully.
)

:: 2. Launch Browser to application
echo [2/3] Launching web browser...
start http://localhost:8000

:: 3. Start PHP Server
echo [3/3] Starting PHP server on http://localhost:8000...
echo.
echo =======================================================
echo Server is running! Press Ctrl+C in this window to stop.
echo Open in browser: http://localhost:8000
echo =======================================================
echo.

"C:\xampp\php\php.exe" -S localhost:8000 -t "%~dp0"
pause
