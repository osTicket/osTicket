@echo off
echo ====================================
echo Starting Localhost Server
echo ====================================
echo.
echo Your local link will be: http://localhost:8085/
echo Press Ctrl+C in this window to stop the server when you're done.
echo.

REM Runs the PowerShell HTTP Server
powershell -ExecutionPolicy Bypass -File server.ps1
