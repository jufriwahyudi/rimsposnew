@echo off
title RIMS POS - Raw Print Server (Port 9100)
color 0A

echo ======================================================
echo    RIMS POS RAW PRINT SERVER (JETDIRECT 9100)
echo ======================================================
echo.

cd /d "%~dp0"

where python >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Python tidak ditemukan di PATH sistem!
    echo Silakan install Python atau tambahkan Python ke PATH.
    pause
    exit /b 1
)

python raw_print_server.py --printer "POS80-Printer" --port 9100
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Server berhenti dengan error code %ERRORLEVEL%.
    pause
)
