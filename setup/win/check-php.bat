@echo off
chcp 65001 >nul
echo Loaded extensions:
php -m
echo.
php -r "echo 'php.ini: ' . php_ini_loaded_file() . PHP_EOL . 'ext_dir: ' . ini_get('extension_dir') . PHP_EOL . 'binary: ' . PHP_BINARY . PHP_EOL;"
pause
