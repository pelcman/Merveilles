@echo off
chcp 65001 >nul
echo ================================
echo  Merveilles - Database Setup
echo ================================
echo.

set DB_HOST=localhost
set DB_USER=root
set /p DB_HOST="MySQL Host [%DB_HOST%]: " || set DB_HOST=localhost
set /p DB_USER="MySQL User [%DB_USER%]: " || set DB_USER=root
set /p DB_PASS="MySQL Password (empty for none): "

echo.
echo Importing schema from sql\schema.sql ...

if "%DB_PASS%"=="" (
    mysql -h %DB_HOST% -u %DB_USER% < "%~dp0sql\schema.sql"
) else (
    mysql -h %DB_HOST% -u %DB_USER% -p%DB_PASS% < "%~dp0sql\schema.sql"
)

if %ERRORLEVEL% equ 0 (
    echo.
    echo [OK] Database 'merveilles' created successfully.
    echo     - players table
    echo     - monsters table
    echo     - specials table
) else (
    echo.
    echo [ERROR] Failed to import schema. Check that MySQL is running and credentials are correct.
)

echo.
pause
