@echo off
chcp 65001 >nul
echo ========================================
echo  Merveilles - Full Setup (Windows)
echo ========================================
echo.

:: -------------------------------------------------------
:: 1. Check / Install PHP
:: -------------------------------------------------------
where php >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo [OK] PHP found:
    php -v | findstr /i "PHP"
    echo.
) else (
    echo [!] PHP not found in PATH.
    echo.
    echo Installing PHP 8.3 via winget ...
    echo.
    winget install --id PHP.PHP.8.3 --accept-source-agreements --accept-package-agreements
    if %ERRORLEVEL% neq 0 (
        echo.
        echo [ERROR] winget install failed.
        echo.
        echo Manual install options:
        echo   1. winget install PHP.PHP.8.3
        echo   2. scoop install php
        echo   3. https://windows.php.net/download/ から ZIP をダウンロードして PATH に追加
        echo.
        pause
        exit /b 1
    )
    echo.
    echo [!] PATH update required. Please RESTART this terminal and run setup.bat again.
    echo.
    pause
    exit /b 0
)

:: -------------------------------------------------------
:: 2. Check PHP extensions (pdo_mysql)
:: -------------------------------------------------------
php -m 2>nul | findstr /i "pdo_mysql" >nul
if %ERRORLEVEL% neq 0 (
    echo [WARNING] pdo_mysql extension not enabled.
    echo   Edit php.ini and uncomment: extension=pdo_mysql
    echo.
    php -r "echo 'php.ini location: ' . php_ini_loaded_file() . PHP_EOL;"
    echo.
)

:: -------------------------------------------------------
:: 3. Setup Database
:: -------------------------------------------------------
echo ----------------------------------------
echo  Database Setup
echo ----------------------------------------
echo.

set DB_HOST=localhost
set DB_USER=root
set DB_PASS=

set /p DB_HOST="MySQL Host [%DB_HOST%]: "
set /p DB_USER="MySQL User [%DB_USER%]: "
set /p DB_PASS="MySQL Password (empty for none): "

echo.
echo Importing sql\schema.sql ...

if "%DB_PASS%"=="" (
    mysql -h %DB_HOST% -u %DB_USER% < "%~dp0sql\schema.sql" 2>&1
) else (
    mysql -h %DB_HOST% -u %DB_USER% -p%DB_PASS% < "%~dp0sql\schema.sql" 2>&1
)

if %ERRORLEVEL% equ 0 (
    echo [OK] Database 'merveilles' ready.
) else (
    echo [ERROR] Schema import failed. Check MySQL is running.
    pause
    exit /b 1
)

:: -------------------------------------------------------
:: 4. Start Server
:: -------------------------------------------------------
echo.
echo ========================================
echo  Starting Merveilles
echo ========================================
echo.

set PORT=8080
set /p PORT="Port [%PORT%]: "

echo.
echo   URL:  http://localhost:%PORT%
echo.
echo Press Ctrl+C to stop.
echo.

start http://localhost:%PORT%

php -S localhost:%PORT% -t "%~dp0public" "%~dp0public\router.php"
