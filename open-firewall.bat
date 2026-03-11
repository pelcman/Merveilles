@echo off
:: Run this as Administrator to open port 20255 for external access

net session >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo [ERROR] This script must be run as Administrator.
    echo Right-click and select "Run as administrator".
    pause
    exit /b 1
)

echo Adding firewall rules for Merveilles (ports 20255-20256)...

netsh advfirewall firewall delete rule name="Merveilles Game Server" >nul 2>&1

netsh advfirewall firewall add rule name="Merveilles Game Server" dir=in action=allow protocol=TCP localport=20255-20256

if %ERRORLEVEL% equ 0 (
    echo.
    echo [OK] Firewall rules added successfully.
    echo     Ports 20255-20256 TCP are now open for inbound connections.
) else (
    echo.
    echo [ERROR] Failed to add firewall rules.
)

echo.
pause
