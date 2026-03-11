@echo off
chcp 65001 >nul
echo ================================
echo  Merveilles - Game Server
echo ================================
echo.

:: Check PHP
where php >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo [ERROR] PHP not found. Run setup\win\setup.bat first.
    pause
    exit /b 1
)

set PORT=20255
set /p PORT="Port [%PORT%]: "

set BIND=0.0.0.0
set /p BIND="Bind address (0.0.0.0 = external, localhost = local only) [%BIND%]: "

set DB_HOST=localhost
set DB_NAME=merveilles
set DB_USER=root
set /p DB_USER="MySQL User [%DB_USER%]: "
set /p DB_PASS="MySQL Password: "

echo.
echo Starting PHP server...
echo   Local:    http://localhost:%PORT%
echo   Bind:     %BIND%:%PORT%
echo   Root:     %~dp0public
echo.
echo   To allow external access, make sure port %PORT% is open in Windows Firewall.
echo.
echo Press Ctrl+C to stop.
echo.

start http://localhost:%PORT%

php -S %BIND%:%PORT% -t "%~dp0public" "%~dp0public\router.php"
